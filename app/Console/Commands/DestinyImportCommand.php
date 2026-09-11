<?php

namespace App\Console\Commands;

use App\Jobs\ArchiveItemJob;
use App\Models\Item;
use App\Services\ItemClassifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use PDO;
use ZipArchive;

class DestinyImportCommand extends Command
{
    protected $signature = 'destiny:import
        {--locale=fr : Locale du manifest}
        {--fresh : Vide la table items avant import}
        {--dispatch-icons : Dispatch les jobs de téléchargement d\'icônes}';

    protected $description = 'Importe intégralement le manifest Destiny 1 (aucune perte de donnée)';

    public function handle(ItemClassifier $classifier): int
    {
        $locale = $this->option('locale');
        $apiKey = config('services.bungie.api_key');

        if (!$apiKey) {
            $this->error('BUNGIE_API_KEY manquant dans .env');
            return self::FAILURE;
        }

        $this->info('== 1. Récupération du manifest ==');

        $manifestResponse = Http::withHeaders(['X-API-Key' => $apiKey])
            ->timeout(30)
            ->get('https://www.bungie.net/Platform/Destiny/Manifest/')
            ->throw()
            ->json();

        $contentPaths = $manifestResponse['Response']['mobileWorldContentPaths'] ?? [];

        if (!isset($contentPaths[$locale])) {
            $this->error("Locale '{$locale}' introuvable.");
            return self::FAILURE;
        }

        $url = 'https://www.bungie.net' . $contentPaths[$locale];
        $this->line("URL: {$url}");

        $this->info('== 2. Téléchargement ==');
        $tmpDir = storage_path('app/manifest_tmp');
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }

        $zipPath = "{$tmpDir}/manifest.zip";
        $body = Http::withHeaders(['X-API-Key' => $apiKey])->timeout(120)->get($url)->body();
        file_put_contents($zipPath, $body);
        $this->line('Téléchargé: ' . round(strlen($body) / 1024 / 1024, 2) . ' Mo');

        $this->info('== 3. Décompression ==');
        $zip = new ZipArchive();
        $zip->open($zipPath);
        $sqliteFilename = $zip->getNameIndex(0);
        $zip->extractTo($tmpDir);
        $zip->close();
        $sqlitePath = "{$tmpDir}/{$sqliteFilename}";
        $this->line("Fichier SQLite: {$sqlitePath}");

        $this->info('== 4. Ouverture SQLite ==');
        $pdo = new PDO("sqlite:{$sqlitePath}");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        if ($this->option('fresh')) {
            $this->warn('Suppression des items existants pour cette locale...');
            Item::where('locale', $locale)->delete();
        }

        $this->info('== 5. Import de DestinyInventoryItemDefinition ==');
        $stmt = $pdo->query('SELECT id, json FROM DestinyInventoryItemDefinition');

        $count = 0;
        $bar = $this->output->createProgressBar();
        $bar->start();

        $batch = [];
        $batchSize = 200;

        $columns = [
            'name', 'description', 'icon_url', 'item_type', 'item_type_name',
            'item_sub_type', 'class_type', 'tier_type', 'tier_type_name',
            'bucket_type_hash', 'category_hashes', 'category_slug',
            'subcategory_slug', 'raw_json', 'updated_at',
        ];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $data = json_decode($row['json'], true);

            $categoryHashes = $data['itemCategoryHashes'] ?? [];

            $classification = $classifier->classify(
                $categoryHashes,
                (int) ($data['itemType'] ?? 0),
                (int) ($data['itemSubType'] ?? 0),
                (int) ($data['classType'] ?? 3),
            );

            // Destiny 1 : les propriétés d'affichage sont à plat dans le JSON,
            // il n'y a PAS de sous-objet "displayProperties" comme dans D2.
            $iconPath = $data['icon'] ?? null;

            $batch[] = [
                'hash' => $data['hash'] ?? (string) $row['id'],
                'locale' => $locale,
                'name' => $data['itemName'] ?? null,
                'description' => $data['itemDescription'] ?? null,
                'icon_url' => $iconPath ? 'https://www.bungie.net' . $iconPath : null,
                'item_type' => $data['itemType'] ?? null,
                'item_type_name' => $data['itemTypeName'] ?? null,
                'item_sub_type' => $data['itemSubType'] ?? null,
                'class_type' => $data['classType'] ?? null,
                'tier_type' => $data['tierType'] ?? null,
                'tier_type_name' => $data['tierTypeName'] ?? null,
                'bucket_type_hash' => $data['bucketTypeHash'] ?? null,
                'category_hashes' => json_encode($categoryHashes),
                'category_slug' => $classification['category'],
                'subcategory_slug' => $classification['subcategory'],
                'icon_downloaded' => false,
                'raw_json' => json_encode($data),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            $count++;

            if (count($batch) >= $batchSize) {
                Item::upsert($batch, ['hash', 'locale'], $columns);
                $batch = [];
            }

            $bar->advance();
        }

        if (!empty($batch)) {
            Item::upsert($batch, ['hash', 'locale'], $columns);
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Importé: {$count} items.");

        $this->info('== 6. Nettoyage fichiers temporaires ==');
        @unlink($zipPath);
        @unlink($sqlitePath);

        if ($this->option('dispatch-icons')) {
            $this->info('== 7. Dispatch des jobs de téléchargement d\'icônes ==');

            $newLocaleItems = Item::where('locale', $locale)
                ->where('icon_downloaded', false)
                ->whereNotNull('icon_url');

            $dispatched = 0;
            $reused = 0;

            $newLocaleItems->chunkById(500, function ($items) use (&$dispatched, &$reused) {
                // Récupère en une seule requête tous les hashs déjà archivés (autre locale)
                $hashes = $items->pluck('hash')->all();

                $alreadyArchived = Item::whereIn('hash', $hashes)
                    ->where('icon_downloaded', true)
                    ->whereNotNull('archive_icon_path')
                    ->get(['hash', 'archive_icon_path'])
                    ->keyBy('hash');

                foreach ($items as $item) {
                    if ($archived = $alreadyArchived->get($item->hash)) {
                        // Le fichier a déjà été téléchargé via une autre locale : on réutilise directement.
                        $item->update([
                            'archive_icon_path' => $archived->archive_icon_path,
                            'icon_downloaded' => true,
                        ]);
                        $reused++;
                    } else {
                        ArchiveItemJob::dispatch($item);
                        $dispatched++;
                    }
                }
            });

            $this->info("Jobs dispatchés: {$dispatched} | Réutilisés sans téléchargement: {$reused}");
            $this->info('Lance le worker avec: php artisan queue:work');
        }

        $this->info('Import terminé.');

        return self::SUCCESS;
    }
}
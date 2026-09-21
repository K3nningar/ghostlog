<?php

namespace App\Console\Commands;

use App\Models\Item;
use App\Models\Perk;
use Illuminate\Console\Command;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use PDO;
use Throwable;
use ZipArchive;

class DestinyPerksImportCommand extends Command
{
    protected $signature = 'destiny:import-perks
        {--locale=fr : Locale du manifest}
        {--fresh : Vide les tables perks/item_perk avant import}
        {--skip-icons : Ne télécharge pas les icônes}';

    protected $description = 'Importe les perks depuis les grilles de talents (DestinyTalentGridDefinition) et les lie aux items';

    private const ICONS_DIRECTORY = 'archive/perks';

    public function handle(): int
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

        $this->info('== 2. Téléchargement ==');
        $tmpDir = storage_path('app/manifest_tmp');
        if (!is_dir($tmpDir)) {
            mkdir($tmpDir, 0755, true);
        }

        $zipPath = "{$tmpDir}/manifest_perks.zip";
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

        $this->info('== 4. Ouverture SQLite ==');
        $pdo = new PDO("sqlite:{$sqlitePath}");
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        if ($this->option('fresh')) {
            $this->warn('Suppression des perks et liaisons existantes pour cette locale...');
            $idsToDelete = Perk::where('locale', $locale)->pluck('id');
            DB::table('item_perk')->whereIn('perk_id', $idsToDelete)->delete();
            Perk::where('locale', $locale)->delete();
        }

        // ============================================================
        // 5. Lecture de toutes les talent grids, extraction des "perks"
        // ============================================================
        $this->info('== 5. Lecture de DestinyTalentGridDefinition ==');

        $stmt = $pdo->query('SELECT id, json FROM DestinyTalentGridDefinition');

        // On indexe les steps par nodeStepHash pour dédoublonner
        // (le même step peut apparaître dans plusieurs grilles)
        $perksByHash = [];   // nodeStepHash => données perk
        $pivotsRaw = [];     // liste brute des liaisons à faire (par talentGridHash)

        $gridCount = 0;

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $grid = json_decode($row['json'], true);
            $gridHash = (string) ($grid['hash'] ?? $row['id']);
            $gridCount++;

            $exclusiveGroupMap = $this->buildExclusiveGroupMap($grid['exclusiveSets'] ?? []);

            foreach ($grid['nodes'] ?? [] as $node) {
                $nodeIndex = $node['nodeIndex'] ?? null;
                $column = $node['column'] ?? null;
                $row2 = $node['row'] ?? null;

                // On ignore les nodes techniques cachés (column négatif, ex: node "reroll master")
                if ($column !== null && $column < 0) {
                    continue;
                }

                foreach ($node['steps'] ?? [] as $step) {
                    $stepHash = $step['nodeStepHash'] ?? null;

                    if (!$stepHash) {
                        continue;
                    }

                    $stepHashStr = (string) $stepHash;

                    // On ne garde que les steps qui ont un nom (sinon c'est du bruit / placeholder)
                    if (!empty($step['nodeStepName'])) {
                        if (!isset($perksByHash[$stepHashStr])) {
                            $iconPath = $step['icon'] ?? null;

                            $perksByHash[$stepHashStr] = [
                                'hash' => $stepHashStr,
                                'name' => $step['nodeStepName'],
                                'description' => $step['nodeStepDescription'] ?? null,
                                'icon_url' => $iconPath ? 'https://www.bungie.net' . $iconPath : null,
                                'sandbox_perk_hashes' => $step['perkHashes'] ?? [],
                                'is_displayable' => true,
                                'raw_json' => $step,
                            ];
                        }
                    } elseif (!isset($perksByHash[$stepHashStr])) {
                        // Step sans nom (ex: hash 0 générique) : on l'ignore pour la table perks
                        // mais on le garde en mémoire pour ne pas planter le pivot si référencé
                        continue;
                    }

                    $pivotsRaw[] = [
                        'grid_hash' => $gridHash,
                        'perk_hash' => $stepHashStr,
                        'node_index' => $nodeIndex,
                        'column' => $column,
                        'row' => $row2,
                        'exclusive_group_id' => $exclusiveGroupMap[$nodeIndex] ?? null,
                        'is_default' => (bool) ($node['autoUnlocks'] ?? false),
                        'grid_level_required' => $step['activationRequirement']['gridLevel'] ?? 0,
                        'sort_order' => $nodeIndex ?? 0,
                    ];
                }
            }
        }

        $this->line("Grilles lues: {$gridCount}");
        $this->line('Perks distinctes trouvées: ' . count($perksByHash));

        // ============================================================
        // 6. Upsert des perks
        // ============================================================
        $this->info('== 6. Import des perks en base ==');

        $bar = $this->output->createProgressBar(count($perksByHash));
        $bar->start();

        $batch = [];
        $batchSize = 200;
        $columns = ['name', 'description', 'icon_url', 'is_displayable', 'sandbox_perk_hashes', 'raw_json', 'updated_at'];

        foreach ($perksByHash as $perkData) {
            $batch[] = [
                'hash' => $perkData['hash'],
                'locale' => $locale,
                'name' => $perkData['name'],
                'description' => $perkData['description'],
                'icon_url' => $perkData['icon_url'],
                'is_displayable' => $perkData['is_displayable'],
                'icon_downloaded' => false,
                'sandbox_perk_hashes' => json_encode($perkData['sandbox_perk_hashes']),
                'raw_json' => json_encode($perkData['raw_json']),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($batch) >= $batchSize) {
                Perk::upsert($batch, ['hash', 'locale'], $columns);
                $batch = [];
            }

            $bar->advance();
        }

        if (!empty($batch)) {
            Perk::upsert($batch, ['hash', 'locale'], $columns);
        }

        $bar->finish();
        $this->newLine(2);
        $this->info('Perks importées: ' . count($perksByHash));

        // ============================================================
        // 7. Liaison items <-> perks via talentGridHash
        // ============================================================
        $this->info('== 7. Liaison des perks aux items ==');

        // On regroupe les pivots bruts par grid_hash pour un accès rapide
        $pivotsByGrid = [];
        foreach ($pivotsRaw as $pivot) {
            $pivotsByGrid[$pivot['grid_hash']][] = $pivot;
        }

        $perksMap = Perk::where('locale', $locale)->pluck('id', 'hash'); // hash => perk_id

        $items = Item::where('locale', $locale)->whereNotNull('raw_json')->get(['id', 'hash', 'raw_json']);

        $pivotBatch = [];
        $pivotBatchSize = 500;
        $linked = 0;
        $skippedNoGrid = 0;

        $bar2 = $this->output->createProgressBar($items->count());
        $bar2->start();

        foreach ($items as $item) {
            $rawJson = is_string($item->raw_json) ? json_decode($item->raw_json, true) : $item->raw_json;

            $talentGridHash = (string) ($rawJson['talentGrid']['talentGridHash'] ?? $rawJson['talentGridHash'] ?? '');

            if (!$talentGridHash || !isset($pivotsByGrid[$talentGridHash])) {
                $skippedNoGrid++;
                $bar2->advance();
                continue;
            }

            foreach ($pivotsByGrid[$talentGridHash] as $pivot) {
                $perkId = $perksMap[$pivot['perk_hash']] ?? null;

                if (!$perkId) {
                    continue;
                }

                $pivotBatch[] = [
                    'item_id' => $item->id,
                    'perk_id' => $perkId,
                    'item_hash' => $item->hash,
                    'perk_hash' => $pivot['perk_hash'],
                    'node_index' => $pivot['node_index'],
                    'column' => $pivot['column'],
                    'row' => $pivot['row'],
                    'exclusive_group_id' => $pivot['exclusive_group_id'],
                    'is_default' => $pivot['is_default'],
                    'grid_level_required' => $pivot['grid_level_required'],
                    'sort_order' => $pivot['sort_order'],
                    'meta' => json_encode([]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                $linked++;
            }

            if (count($pivotBatch) >= $pivotBatchSize) {
                DB::table('item_perk')->upsert(
                    $pivotBatch,
                    ['item_id', 'perk_id'],
                    ['item_hash', 'perk_hash', 'node_index', 'column', 'row', 'exclusive_group_id', 'is_default', 'grid_level_required', 'sort_order', 'meta', 'updated_at']
                );
                $pivotBatch = [];
            }

            $bar2->advance();
        }

        if (!empty($pivotBatch)) {
            DB::table('item_perk')->upsert(
                $pivotBatch,
                ['item_id', 'perk_id'],
                ['item_hash', 'perk_hash', 'node_index', 'column', 'row', 'exclusive_group_id', 'is_default', 'grid_level_required', 'sort_order', 'meta', 'updated_at']
            );
        }

        $bar2->finish();
        $this->newLine(2);
        $this->info("Liaisons créées: {$linked}");
        $this->line("Items sans talent grid connue: {$skippedNoGrid}");

        // ============================================================
        // 8. Téléchargement des icônes (sauf si --skip-icons)
        // ============================================================
        if (!$this->option('skip-icons')) {
            $this->info('== 8. Téléchargement des icônes ==');
            $this->downloadIcons($locale);
        }

        $this->info('== 9. Nettoyage fichiers temporaires ==');
        @unlink($zipPath);
        @unlink($sqlitePath);

        $this->info('Import terminé.');

        return self::SUCCESS;
    }

    /**
     * Construit une map nodeIndex => groupId pour les sets exclusifs d'une grille.
     */
    private function buildExclusiveGroupMap(array $exclusiveSets): array
    {
        $map = [];

        foreach ($exclusiveSets as $groupId => $set) {
            foreach ($set['nodeIndexes'] ?? [] as $nodeIndex) {
                $map[$nodeIndex] = $groupId;
            }
        }

        return $map;
    }

    /**
     * Télécharge les icônes manquantes, en réutilisant les fichiers déjà archivés
     * pour le même hash (autre locale) si disponibles.
     */
    private function downloadIcons(string $locale): void
    {
        $perksToProcess = Perk::where('locale', $locale)
            ->where('icon_downloaded', false)
            ->whereNotNull('icon_url')
            ->get();

        if ($perksToProcess->isEmpty()) {
            $this->line('Aucune icône à télécharger.');
            return;
        }

        // Recherche des icônes déjà archivées pour les mêmes hashes (autres locales)
        $hashes = $perksToProcess->pluck('hash')->unique()->all();
        $alreadyArchived = Perk::whereIn('hash', $hashes)
            ->where('icon_downloaded', true)
            ->whereNotNull('archive_icon_path')
            ->get(['hash', 'archive_icon_path'])
            ->keyBy('hash');

        $directory = public_path(self::ICONS_DIRECTORY);
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            $this->error("Impossible de créer le dossier {$directory}");
            return;
        }

        $total = $perksToProcess->count();
        $downloaded = 0;
        $reused = 0;
        $failed = 0;

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        foreach ($perksToProcess as $perk) {
            // Cas 1 : déjà archivé sous ce hash (autre locale) → on référence juste le fichier existant
            if ($archived = $alreadyArchived->get($perk->hash)) {
                $perk->update([
                    'archive_icon_path' => $archived->archive_icon_path,
                    'icon_downloaded' => true,
                ]);
                $reused++;
                $bar->advance();
                continue;
            }

            // Cas 2 : vérifie si un fichier existe déjà physiquement sur disque (sécurité en cas de champ désynchronisé)
            $safeHash = preg_replace('/[^A-Za-z0-9_-]/', '_', $perk->hash);
            $existingFile = $this->findExistingFile($directory, $safeHash);

            if ($existingFile) {
                $perk->update([
                    'archive_icon_path' => self::ICONS_DIRECTORY . '/' . basename($existingFile),
                    'icon_downloaded' => true,
                ]);
                $reused++;
                $bar->advance();
                continue;
            }

            // Cas 3 : téléchargement réel
            try {
                if ($this->downloadSingleIcon($perk, $directory, $safeHash)) {
                    $downloaded++;
                    // On l'ajoute à la map pour réutilisation immédiate si un autre step
                    // partage le même hash plus loin dans la boucle (rare mais possible)
                    $alreadyArchived->put($perk->hash, (object) [
                        'archive_icon_path' => $perk->archive_icon_path,
                    ]);
                } else {
                    $failed++;
                }
            } catch (Throwable $e) {
                $failed++;
                $this->newLine();
                $this->warn("Perk {$perk->hash}: {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Icônes: {$downloaded} téléchargées, {$reused} réutilisées, {$failed} échecs.");
    }

    private function findExistingFile(string $directory, string $safeHash): ?string
    {
        foreach (['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg'] as $ext) {
            $path = $directory . DIRECTORY_SEPARATOR . $safeHash . '.' . $ext;
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }

    private function downloadSingleIcon(Perk $perk, string $directory, string $safeHash): bool
    {
        try {
            $response = Http::timeout(30)->retry(2, 500)->get($perk->icon_url);
        } catch (Throwable $e) {
            return false;
        }

        if (!$response->successful()) {
            return false;
        }

        $contents = $response->body();
        if ($contents === '') {
            return false;
        }

        $extension = $this->detectExtension($response, $perk->icon_url, $contents);
        $filename = $safeHash . '.' . $extension;
        $absolutePath = $directory . DIRECTORY_SEPARATOR . $filename;
        $temporaryPath = $absolutePath . '.tmp';

        if (file_put_contents($temporaryPath, $contents, LOCK_EX) === false || !rename($temporaryPath, $absolutePath)) {
            @unlink($temporaryPath);
            return false;
        }

        $perk->forceFill([
            'archive_icon_path' => self::ICONS_DIRECTORY . '/' . $filename,
            'icon_downloaded' => true,
        ])->save();

        return true;
    }

    private function detectExtension(Response $response, string $url, string $contents): string
    {
        $mime = strtolower((string) $response->header('Content-Type'));
        $fromMime = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
            'image/svg+xml' => 'svg',
        ];
        if (isset($fromMime[$mime])) {
            return $fromMime[$mime];
        }

        $extension = strtolower((string) pathinfo((string) parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
        if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'], true)) {
            return $extension === 'jpeg' ? 'jpg' : $extension;
        }

        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $detectedMime = $finfo ? finfo_buffer($finfo, $contents) : false;
            if ($finfo) {
                finfo_close($finfo);
            }
            if (is_string($detectedMime) && isset($fromMime[$detectedMime])) {
                return $fromMime[$detectedMime];
            }
        }

        return 'jpg';
    }
}
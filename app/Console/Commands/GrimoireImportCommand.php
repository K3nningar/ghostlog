<?php

namespace App\Console\Commands;

use App\Models\GrimoireEntry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Throwable;

class GrimoireImportCommand extends Command
{
    protected $signature = 'destiny:import-grimoire
        {--locale=fr : Locale à importer (ignoré si --all-locales)}
        {--all-locales : Importe toutes les locales disponibles}
        {--fresh : Supprime les entrées existantes de chaque locale avant import}';

    protected $description = 'Importe les cartes du Grimoire et leurs images depuis les manifests SQLite locaux';

    protected array $manifestFiles = [
        'ja' => 'world_sql_content_8cc917057903da39656f9f69b27103fa.content',
        'en' => 'world_sql_content_39adcbfdc2021172e8ccf4720ca76023.content',
        'de' => 'world_sql_content_341e6d9076bb4e536c940778ae1f5412.content',
        'fr' => 'world_sql_content_1153862bacd580695916190810bed2d8.content',
        'es' => 'world_sql_content_af72a78c9e19265155bc2a9f5207f641.content',
        'pt-br' => 'world_sql_content_534310aab42109d23debf0fabed02aea.content',
        'it' => 'world_sql_content_845ca2d5909dd2ae14728085a3ab3799.content',
    ];

    /** Cache mémoire des sprites déjà lus pendant l'import courant. */
    protected array $spriteCache = [];

    public function handle(): int
    {
        $locales = $this->option('all-locales')
            ? array_keys($this->manifestFiles)
            : [$this->option('locale')];
        $total = 0;

        foreach ($locales as $locale) {
            $this->info("=== Locale : {$locale} ===");
            $count = $this->importLocale($locale);
            if ($count !== null) {
                $total += $count;
            }
        }

        $this->newLine();
        $this->info("Import terminé : {$total} carte(s).");
        return self::SUCCESS;
    }

    protected function importLocale(string $locale): ?int
    {
        if (!isset($this->manifestFiles[$locale])) {
            $this->error("Locale inconnue : {$locale}.");
            return null;
        }

        $path = storage_path('app/manifest_tmp/' . $this->manifestFiles[$locale]);
        if (!is_file($path)) {
            $this->error("Manifest introuvable : {$path}");
            return null;
        }

        // Important : purger avant chaque locale, sinon Laravel réutilise le précédent SQLite.
        config(['database.connections.grimoire_sqlite' => [
            'driver' => 'sqlite', 'database' => $path, 'prefix' => '',
        ]]);
        DB::purge('grimoire_sqlite');
        $connection = DB::connection('grimoire_sqlite');

        if (empty($connection->select("SELECT name FROM sqlite_master WHERE type='table' AND name='DestinyGrimoireCardDefinition'"))) {
            $this->error("Table DestinyGrimoireCardDefinition absente dans {$path}.");
            return null;
        }

        if ($this->option('fresh')) {
            GrimoireEntry::where('locale', $locale)->delete();
        }

        $rows = $connection->table('DestinyGrimoireCardDefinition')->get();
        $bar = $this->output->createProgressBar($rows->count());
        $bar->start();
        $batch = [];
        $count = 0;

        foreach ($rows as $row) {
            $data = json_decode((string) $row->json, true);
            if (!is_array($data)) {
                $bar->advance();
                continue;
            }

            $entryKey = (string) ($data['identifier'] ?? $data['cardId'] ?? $row->id ?? $data['hash'] ?? '');
            if ($entryKey === '') {
                $bar->advance();
                continue;
            }

            $images = $this->importCardImages($data, $locale, $entryKey);
            $batch[] = [
                'entry_key' => $entryKey,
                'locale' => $locale,
                'category' => $data['cardCategoryDescription'] ?? $data['cardCategoryId'] ?? null,
                'name' => $this->cleanHtml($data['cardName'] ?? $data['name'] ?? '', false),
                'description' => $this->cleanHtml($data['cardDescription'] ?? $data['description'] ?? '', true),
                'content' => $this->cleanHtml($data['cardIntro'] ?? $data['cardCompleteInfo'] ?? $data['unlockHowToText'] ?? '', true),
                'image_path' => $data['originalIcon'] ?? $data['icon'] ?? null,
                'image_normal' => $images['image_normal'],
                'image_normal_sm' => $images['image_normal_sm'],
                'image_hr' => $images['image_hr'],
                'image_hr_sm' => $images['image_hr_sm'],
                'updated_at' => now(),
                'created_at' => now(),
            ];
            $count++;

            if (count($batch) >= 250) {
                $this->upsertBatch($batch);
                $batch = [];
            }
            $bar->advance();
        }

        if ($batch) {
            $this->upsertBatch($batch);
        }
        $bar->finish();
        $this->newLine();
        $this->info("{$count} carte(s) importée(s) pour {$locale}.");
        return $count;
    }

    protected function upsertBatch(array $batch): void
    {
        GrimoireEntry::upsert($batch, ['entry_key', 'locale'], [
            'category', 'name', 'description', 'content', 'image_path',
            'image_normal', 'image_normal_sm', 'image_hr', 'image_hr_sm', 'updated_at',
        ]);
    }

    protected function cleanHtml(mixed $value, bool $allowMarkup): string
    {
        $decoded = html_entity_decode((string) $value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        return $allowMarkup
            ? strip_tags($decoded, '<b><i><br><strong><em>')
            : strip_tags($decoded);
    }

    /** Télécharge chaque sheet une fois, puis produit les quatre crops demandés. */
    protected function importCardImages(array $data, string $locale, string $entryKey): array
    {
        $result = ['image_normal' => null, 'image_normal_sm' => null, 'image_hr' => null, 'image_hr_sm' => null];
        foreach ([
            ['normalResolution', 'image_normal'], ['normalResolution', 'image_normal_sm', 'smallImage'],
            ['highResolution', 'image_hr'], ['highResolution', 'image_hr_sm', 'smallImage'],
        ] as $spec) {
            [$resolution, $column, $variant] = [$spec[0], $spec[1], $spec[2] ?? 'image'];
            $definition = $data[$resolution][$variant] ?? null;
            if (!is_array($definition)) {
                continue;
            }
            $relative = $this->cropSprite($definition, $locale, $entryKey, str_replace('image_', '', $column));
            if ($relative !== null) {
                $result[$column] = $relative;
            }
        }
        return $result;
    }

    protected function cropSprite(array $definition, string $locale, string $entryKey, string $suffix): ?string
    {
        $sheetPath = $definition['sheetPath'] ?? null;
        $rect = $definition['rect'] ?? null;
        if (!is_string($sheetPath) || !is_array($rect)) {
            $this->warn("Définition d'image incomplète pour {$entryKey} ({$suffix}).");
            return null;
        }

        $disk = Storage::disk('public');
        $relative = "grimoire/{$locale}/{$entryKey}_{$suffix}.jpg";
        $disk->makeDirectory("grimoire/{$locale}");
        if ($disk->exists($relative)) {
            return $relative; // Le crop final existe déjà : pas de téléchargement ni traitement inutile.
        }

        $sheet = $this->loadSprite($sheetPath);
        if ($sheet === null) {
            return null;
        }
        [$x, $y, $width, $height] = array_map('intval', [
            $rect['x'] ?? 0, $rect['y'] ?? 0, $rect['width'] ?? 0, $rect['height'] ?? 0,
        ]);
        if ($width <= 0 || $height <= 0 || $x < 0 || $y < 0 || $x + $width > imagesx($sheet) || $y + $height > imagesy($sheet)) {
            $this->warn("Rectangle hors limites pour {$entryKey} ({$suffix}).");
            imagedestroy($sheet);
            return null;
        }

        $crop = imagecreatetruecolor($width, $height);
        imagecopy($crop, $sheet, 0, 0, $x, $y, $width, $height);
        ob_start();
        imagejpeg($crop, null, 90);
        $bytes = ob_get_clean();
        imagedestroy($crop);
        imagedestroy($sheet);
        if ($bytes === false || !$disk->put($relative, $bytes)) {
            $this->warn("Impossible d'enregistrer {$relative}.");
            return null;
        }
        return $relative;
    }

    /** @return resource|false|null */
    protected function loadSprite(string $sheetPath)
    {
        $cacheKey = trim($sheetPath);
        if (array_key_exists($cacheKey, $this->spriteCache)) {
            $bytes = $this->spriteCache[$cacheKey];
        } else {
            $local = 'grimoire/.sprites/' . sha1($cacheKey) . '.jpg';
            $disk = Storage::disk('public');
            $disk->makeDirectory('grimoire/.sprites');
            try {
                if (!$disk->exists($local)) {
                    $url = str_starts_with($cacheKey, 'http') ? $cacheKey : 'https://www.bungie.net' . '/' . ltrim($cacheKey, '/');
                    $response = Http::timeout(30)->retry(2, 500)->get($url);
                    if (!$response->successful()) {
                        throw new \RuntimeException('HTTP ' . $response->status());
                    }
                    $disk->put($local, $response->body());
                }
                $bytes = $disk->get($local);
                $this->spriteCache[$cacheKey] = $bytes; // un seul téléchargement par sheetPath dans ce run.
            } catch (Throwable $e) {
                $this->warn("Sprite introuvable {$cacheKey} : {$e->getMessage()}");
                return null;
            }
        }
        $image = @imagecreatefromstring($bytes);
        if ($image === false) {
            $this->warn("Sprite JPEG illisible : {$cacheKey}");
            return null;
        }
        return $image;
    }
}
<?php

// app/Console/Commands/ArchiveDestinyCommand.php
class ArchiveDestinyCommand extends Command
{
    protected $signature = 'destiny:archive {--locale=fr}';
    protected $description = 'Télécharge le manifest D1, parse les items et archive les images par catégorie';

    public function handle(
        BungieApiService $bungie,
        ManifestParserService $parser
    ): int {
        $locale = $this->option('locale');

        $this->info('Récupération des infos du manifest...');
        $manifestInfo = $bungie->getManifest();

        $mobileWorldContentPaths = $manifestInfo['mobileWorldContentPaths'] ?? [];
        $relativePath = $mobileWorldContentPaths[$locale] ?? $mobileWorldContentPaths['en'] ?? null;

        if (!$relativePath) {
            $this->error("Aucun manifest trouvé pour la locale '{$locale}'.");
            return self::FAILURE;
        }

        $this->info('Téléchargement et extraction du manifest SQLite...');
        $sqlitePath = $bungie->downloadManifestDatabase($relativePath);

        $version = ManifestVersion::create([
            'version' => $manifestInfo['version'] ?? now()->toDateString(),
            'locale' => $locale,
            'sqlite_path' => $sqlitePath,
            'fetched_at' => now(),
        ]);

        $this->info('Parsing et dispatch des jobs...');
        $parser->parse($sqlitePath, $version);

        $this->info('Jobs dispatchés en queue.');
        $this->info('Lance maintenant : php artisan queue:work --queue=parsing,images');

        return self::SUCCESS;
    }
}
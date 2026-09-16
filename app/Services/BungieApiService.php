<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use ZipArchive;

class BungieApiService
{
    protected string $baseUrl = 'https://www.bungie.net';
    protected string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.bungie.api_key');
    }

    public function getManifest(): array
    {
        $response = Http::withHeaders(['X-API-Key' => $this->apiKey])
            ->get("{$this->baseUrl}/D1/Platform/Destiny/Manifest/");

        $response->throw();

        return $response->json()['Response'];
    }

    public function downloadManifestDatabase(string $relativePath): string
    {
        $url = $this->baseUrl . $relativePath;
        $response = Http::timeout(120)->get($url);
        $response->throw();

        $zipPath = storage_path('app/manifest_tmp/manifest.zip');
        File::ensureDirectoryExists(dirname($zipPath));
        File::put($zipPath, $response->body());

        $extractPath = storage_path('app/manifest_tmp/extracted');
        File::ensureDirectoryExists($extractPath);

        $zip = new ZipArchive();
        $zip->open($zipPath);
        $zip->extractTo($extractPath);
        $zip->close();

        // Le zip contient un seul fichier .content
        $files = File::files($extractPath);
        $sqliteFile = $files[0]->getPathname();

        $finalPath = storage_path('app/manifests/manifest_' . now()->format('Y_m_d_His') . '.sqlite3');
        File::ensureDirectoryExists(dirname($finalPath));
        File::move($sqliteFile, $finalPath);

        // Nettoyage
        File::deleteDirectory(storage_path('app/manifest_tmp'));

        return $finalPath;
    }
}
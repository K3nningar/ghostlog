<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Item;
use Illuminate\Console\Command;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

class DownloadEmblemSecondaryIcons extends Command
{
    protected $signature = 'emblems:download-secondary-icons {--limit=} {--force}';

    protected $description = 'Télécharge les visuels secondaryIcon des emblèmes';

    private const ARCHIVE_DIRECTORY = 'archive/emblems/secondaryIcon';

    public function handle(): int
    {
        $limit = $this->option('limit');
        if ($limit !== null && (!ctype_digit((string) $limit) || (int) $limit < 1)) {
            $this->error("L'option --limit doit être un entier positif.");

            return self::FAILURE;
        }

        $force = (bool) $this->option('force');
        $query = Item::query()
            ->where('category_slug', 'emblems')
            ->whereNotNull('raw_json')
            ->where('raw_json', 'like', '%secondaryIcon%')
            ->orderBy('id');

        if (!$force) {
            $query->where(function ($builder): void {
                $builder->whereNull('archive_icon_path_secondary')
                    ->orWhere('archive_icon_path_secondary', '');
            });
        }

        $items = $query->get()->unique('hash')->values();
        if ($limit !== null) {
            $items = $items->take((int) $limit);
        }

        $total = $items->count();
        if ($total === 0) {
            $this->info('Aucun secondaryIcon d’emblème à traiter.');

            return self::SUCCESS;
        }

        $this->info("Téléchargement de {$total} secondaryIcon(s)...");
        $succeeded = 0;
        $failed = 0;
        $processed = 0;

        foreach ($items as $item) {
            ++$processed;
            try {
                if ($this->downloadSecondaryIcon($item, $force)) {
                    ++$succeeded;
                } else {
                    ++$failed;
                }
            } catch (Throwable $exception) {
                ++$failed;
                $this->newLine();
                $this->error("Emblème {$item->hash} : {$exception->getMessage()}");
            }

            $this->renderProgress($processed, $total);
        }

        $this->newLine(2);
        $this->info("Terminé : {$succeeded} réussite(s), {$failed} échec(s).");

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function downloadSecondaryIcon(Item $item, bool $force): bool
    {
        $secondaryIcon = trim((string) data_get($item->raw_json, 'secondaryIcon', ''));
        if ($secondaryIcon === '') {
            $this->reportItemError($item, 'secondaryIcon absent');

            return false;
        }

        $url = $this->resolveUrl($secondaryIcon);
        $hash = preg_replace('/[^A-Za-z0-9_-]/', '_', (string) $item->hash);
        $directory = public_path(self::ARCHIVE_DIRECTORY);
        $existingPath = (string) ($item->archive_icon_path_secondary ?? '');

        if (!$force && $existingPath !== '' && file_exists(public_path($existingPath))) {
            return true;
        }

        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            $this->reportItemError($item, "Impossible de créer le dossier {$directory}");

            return false;
        }

        try {
            $response = Http::timeout(30)->retry(2, 500)->get($url);
        } catch (Throwable $exception) {
            $this->reportItemError($item, "Échec HTTP : {$exception->getMessage()}");

            return false;
        }

        if (!$response->successful() || $response->body() === '') {
            $this->reportItemError($item, "Échec HTTP : statut {$response->status()}");

            return false;
        }

        $filename = $hash . '.' . $this->detectExtension($response, $url, $response->body());
        $absolutePath = $directory . DIRECTORY_SEPARATOR . $filename;
        $temporaryPath = $absolutePath . '.tmp';

        if (file_put_contents($temporaryPath, $response->body(), LOCK_EX) === false || !rename($temporaryPath, $absolutePath)) {
            @unlink($temporaryPath);
            $this->reportItemError($item, "Impossible d'écrire {$absolutePath}");

            return false;
        }

        $relativePath = self::ARCHIVE_DIRECTORY . '/' . $filename;
        Item::where('hash', $item->hash)->update([
            'archive_icon_path_secondary' => $relativePath,
        ]);

        return true;
    }

    private function resolveUrl(string $path): string
    {
        if (preg_match('#^https?://#i', $path)) {
            return $path;
        }

        return 'https://www.bungie.net/' . ltrim($path, '/');
    }

    private function detectExtension(Response $response, string $url, string $contents): string
    {
        $mime = strtolower(trim(explode(';', (string) $response->header('Content-Type'))[0]));
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

    private function reportItemError(Item $item, string $message): void
    {
        $this->newLine();
        $this->warn("Emblème {$item->hash} : {$message}");
    }

    private function renderProgress(int $current, int $total): void
    {
        $width = 30;
        $percent = (int) floor(($current / $total) * 100);
        $filled = (int) floor(($current / $total) * $width);
        $bar = str_repeat('=', $filled) . str_repeat(' ', $width - $filled);
        $this->output->write("\r{$current}/{$total} [{$bar}] {$percent}%");
    }
}

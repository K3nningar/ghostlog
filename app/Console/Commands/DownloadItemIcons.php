<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Item;
use App\Support\ArchivePathResolver;
use Illuminate\Console\Command;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Télécharge les icônes des Items dans l'arborescence d'archive publique.
 */
class DownloadItemIcons extends Command
{
    protected $signature = 'items:download-icons {--limit=} {--force}';

    protected $description = 'Télécharge les icônes des items dans public/archive';

    public function handle(): int
    {
        $limit = $this->option('limit');
        if ($limit !== null && (!ctype_digit((string) $limit) || (int) $limit < 1)) {
            $this->error("L'option --limit doit être un entier positif.");

            return self::FAILURE;
        }

        $force = (bool) $this->option('force');
        $query = Item::query()->orderBy('id');

        if (!$force) {
            $query->where(function ($builder): void {
                $builder->whereNull('icon_downloaded')->orWhere('icon_downloaded', false);
            });
        }

        $totalAvailable = (clone $query)->count();

        if ($limit !== null) {
            $query->limit((int) $limit);
        }

        $total = $limit !== null ? min((int) $limit, $totalAvailable) : $totalAvailable;

        if ($total === 0) {
            $this->info('Aucun item à traiter.');

            return self::SUCCESS;
        }

        $this->info("Téléchargement de {$total} icône(s)...");
        $processed = 0;
        $succeeded = 0;
        $failed = 0;

        foreach ($query->get() as $item) {
            ++$processed;
            try {
                if ($this->downloadItem($item)) {
                    ++$succeeded;
                } else {
                    ++$failed;
                }
            } catch (Throwable $exception) {
                ++$failed;
                $this->newLine();
                $this->error("Item {$item->id} : {$exception->getMessage()}");
            }

            $this->renderProgress($processed, $total);
        }

        $this->newLine(2);
        $this->info("Terminé : {$succeeded} réussite(s), {$failed} échec(s).");

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function downloadItem(Item $item): bool
    {
        $url = trim((string) ($item->icon_url ?? ''));
        if ($url === '') {
            $this->reportItemError($item, "URL d'icône absente");

            return false;
        }

        $relativeDirectory = ArchivePathResolver::resolve(
            (string) ($item->category_slug ?? ''),
            $item->subcategory_slug !== null ? (string) $item->subcategory_slug : null,
        );
        $directory = public_path($relativeDirectory);

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

        if (!$response->successful()) {
            $this->reportItemError($item, "Échec HTTP : statut {$response->status()}");

            return false;
        }

        $contents = $response->body();
        if ($contents === '') {
            $this->reportItemError($item, 'Réponse HTTP vide');

            return false;
        }

        $hash = preg_replace('/[^A-Za-z0-9_-]/', '_', (string) ($item->hash ?: $item->id));
        $filename = $hash . '.' . $this->detectExtension($response, $url, $contents);
        $absolutePath = $directory . DIRECTORY_SEPARATOR . $filename;
        $temporaryPath = $absolutePath . '.tmp';

        if (file_put_contents($temporaryPath, $contents, LOCK_EX) === false || !rename($temporaryPath, $absolutePath)) {
            @unlink($temporaryPath);
            $this->reportItemError($item, "Impossible d'écrire {$absolutePath}");

            return false;
        }

        $item->forceFill([
            'archive_icon_path' => str_replace(DIRECTORY_SEPARATOR, '/', $relativeDirectory . '/' . $filename),
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

    private function reportItemError(Item $item, string $message): void
    {
        $this->newLine();
        $this->warn("Item {$item->id} : {$message}");
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
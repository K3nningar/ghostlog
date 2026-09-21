<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Perk;
use Illuminate\Console\Command;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Télécharge les icônes des Perks dans l'arborescence d'archive publique.
 */
class DownloadPerkIcons extends Command
{
    protected $signature = 'perks:download-icons {--limit=} {--force}';

    protected $description = 'Télécharge les icônes des perks dans public/archive';

    private const RELATIVE_DIRECTORY = 'archive/perks';

    public function handle(): int
    {
        $limit = $this->option('limit');
        if ($limit !== null && (!ctype_digit((string) $limit) || (int) $limit < 1)) {
            $this->error("L'option --limit doit être un entier positif.");

            return self::FAILURE;
        }

        $force = (bool) $this->option('force');
        $query = Perk::query()->orderBy('id');

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
            $this->info('Aucun perk à traiter.');

            return self::SUCCESS;
        }

        $this->info("Téléchargement de {$total} icône(s)...");
        $processed = 0;
        $succeeded = 0;
        $failed = 0;

        foreach ($query->get() as $perk) {
            ++$processed;
            try {
                if ($this->downloadPerk($perk)) {
                    ++$succeeded;
                } else {
                    ++$failed;
                }
            } catch (Throwable $exception) {
                ++$failed;
                $this->newLine();
                $this->error("Perk {$perk->id} : {$exception->getMessage()}");
            }

            $this->renderProgress($processed, $total);
        }

        $this->newLine(2);
        $this->info("Terminé : {$succeeded} réussite(s), {$failed} échec(s).");

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function downloadPerk(Perk $perk): bool
    {
        $url = trim((string) ($perk->icon_url ?? ''));
        if ($url === '') {
            $this->reportPerkError($perk, "URL d'icône absente");

            return false;
        }

        $directory = public_path(self::RELATIVE_DIRECTORY);

        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            $this->reportPerkError($perk, "Impossible de créer le dossier {$directory}");

            return false;
        }

        try {
            $response = Http::timeout(30)->retry(2, 500)->get($url);
        } catch (Throwable $exception) {
            $this->reportPerkError($perk, "Échec HTTP : {$exception->getMessage()}");

            return false;
        }

        if (!$response->successful()) {
            $this->reportPerkError($perk, "Échec HTTP : statut {$response->status()}");

            return false;
        }

        $contents = $response->body();
        if ($contents === '') {
            $this->reportPerkError($perk, 'Réponse HTTP vide');

            return false;
        }

        $hash = preg_replace('/[^A-Za-z0-9_-]/', '_', (string) ($perk->hash ?: $perk->id));
        $filename = $hash . '.' . $this->detectExtension($response, $url, $contents);
        $absolutePath = $directory . DIRECTORY_SEPARATOR . $filename;
        $temporaryPath = $absolutePath . '.tmp';

        if (file_put_contents($temporaryPath, $contents, LOCK_EX) === false || !rename($temporaryPath, $absolutePath)) {
            @unlink($temporaryPath);
            $this->reportPerkError($perk, "Impossible d'écrire {$absolutePath}");

            return false;
        }

        $perk->forceFill([
            'archive_icon_path' => self::RELATIVE_DIRECTORY . '/' . $filename,
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

    private function reportPerkError(Perk $perk, string $message): void
    {
        $this->newLine();
        $this->warn("Perk {$perk->id} : {$message}");
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
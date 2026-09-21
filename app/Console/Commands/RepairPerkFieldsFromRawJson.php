<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Perk;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Répare les perks dont icon_url / name / description sont NULL
 * alors que le raw_json contient les informations nécessaires.
 */
class RepairPerkFieldsFromRawJson extends Command
{
    protected $signature = 'perks:repair-from-raw-json {--limit=} {--dry-run}';

    protected $description = 'Backfill icon_url/name/description des perks à partir de raw_json';

    public function handle(): int
    {
        $limit = $this->option('limit');
        if ($limit !== null && (!ctype_digit((string) $limit) || (int) $limit < 1)) {
            $this->error("L'option --limit doit être un entier positif.");

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');

        $query = Perk::query()
            ->where(function ($builder): void {
                $builder->whereNull('icon_url')
                    ->orWhereNull('name')
                    ->orWhereNull('description');
            })
            ->orderBy('id');

        $totalAvailable = (clone $query)->count();

        if ($limit !== null) {
            $query->limit((int) $limit);
        }

        $total = $limit !== null ? min((int) $limit, $totalAvailable) : $totalAvailable;

        if ($total === 0) {
            $this->info('Aucun perk à réparer.');

            return self::SUCCESS;
        }

        $this->info(($dryRun ? '[DRY-RUN] ' : '') . "Réparation de {$total} perk(s)...");

        $processed = 0;
        $updated = 0;
        $skipped = 0;

        foreach ($query->get() as $perk) {
            ++$processed;

            try {
                $result = $this->repairPerk($perk, $dryRun);
                if ($result) {
                    ++$updated;
                } else {
                    ++$skipped;
                }
            } catch (Throwable $exception) {
                ++$skipped;
                $this->newLine();
                $this->warn("Perk {$perk->id} : {$exception->getMessage()}");
            }

            $this->renderProgress($processed, $total);
        }

        $this->newLine(2);
        $this->info("Terminé : {$updated} mis à jour, {$skipped} ignoré(s)/sans changement.");

        return self::SUCCESS;
    }

    private function repairPerk(Perk $perk, bool $dryRun): bool
    {
        $raw = $perk->raw_json;
        $data = is_array($raw) ? $raw : json_decode((string) $raw, true);

        if (!is_array($data)) {
            return false;
        }

        $changes = [];

        // displayName -> name
        $displayName = $data['displayName'] ?? null;
        if ($perk->name === null && is_string($displayName) && trim($displayName) !== '') {
            $changes['name'] = $displayName;
        }

        // displayDescription -> description
        $displayDescription = $data['displayDescription'] ?? null;
        if ($perk->description === null && is_string($displayDescription) && trim($displayDescription) !== '') {
            $changes['description'] = $displayDescription;
        }

        // displayIcon -> icon_url (URL complète Bungie)
        $displayIcon = $data['displayIcon'] ?? null;
        if ($perk->icon_url === null && is_string($displayIcon) && trim($displayIcon) !== '') {
            $changes['icon_url'] = $this->buildFullIconUrl($displayIcon);
        }

        if ($changes === []) {
            return false;
        }

        if (!$dryRun) {
            $perk->forceFill($changes)->save();
        }

        return true;
    }

    private function buildFullIconUrl(string $path): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $path = '/' . ltrim($path, '/');

        return 'https://www.bungie.net' . $path;
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
<?php

namespace App\Jobs;

use App\Models\Item;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ArchiveItemJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 5;

    public function __construct(private readonly Item $item)
    {
    }

    public function handle(): void
    {
        if (!$this->item->icon_url) {
            return;
        }

        $category = $this->item->category_slug ?? 'misc';
        $subcategory = $this->item->subcategory_slug;

        $relativeDir = $subcategory
            ? "archive/{$category}/{$subcategory}"
            : "archive/{$category}";

        $fullDir = public_path($relativeDir);
        if (!is_dir($fullDir)) {
            mkdir($fullDir, 0755, true);
        }

        $filename = "{$this->item->hash}.jpg";
        $fullPath = "{$fullDir}/{$filename}";
        $relativePath = "{$relativeDir}/{$filename}";

        // Le fichier existe déjà sur disque (téléchargé via une autre locale) : pas besoin de retélécharger.
        if (!file_exists($fullPath)) {
            $response = Http::timeout(30)->get($this->item->icon_url);

            if (!$response->successful()) {
                Log::warning("Échec téléchargement icône pour item {$this->item->hash}", [
                    'status' => $response->status(),
                    'url' => $this->item->icon_url,
                ]);
                return; // le job repartira via tries
            }

            file_put_contents($fullPath, $response->body());
        }

        // Met à jour TOUTES les locales de ce hash d'un coup : évite de re-télécharger
        // pour chaque langue et garde la DB cohérente immédiatement.
        Item::where('hash', $this->item->hash)
            ->update([
                'archive_icon_path' => $relativePath,
                'icon_downloaded' => true,
            ]);
    }
}
<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

// app/Jobs/DownloadImageJob.php
class DownloadImageJob implements ShouldQueue
{
    use Queueable, Dispatchable, InteractsWithQueue, SerializesModels;

    public $tries = 3;
    public $backoff = 5;

    public function __construct(
        protected int $itemId,
        protected string $type,
        protected string $bungiePath,
        protected string $category
    ) {}

    public function handle(): void
    {
        $item = Item::find($this->itemId);
        if (!$item) return;

        $directory = public_path("archive/{$this->category}");

        if (!File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $extension = pathinfo($this->bungiePath, PATHINFO_EXTENSION) ?: 'jpg';
        $filename = "{$item->hash}_{$this->type}.{$extension}";
        $fullPath = "{$directory}/{$filename}";
        $publicPath = "archive/{$this->category}/{$filename}";

        if (File::exists($fullPath)) {
            $this->saveRecord($item, $filename, $publicPath);
            return;
        }

        $url = 'https://www.bungie.net' . $this->bungiePath;

        try {
            $response = Http::timeout(30)->get($url);
        } catch (\Exception $e) {
            Log::warning("Erreur réseau image {$url}: {$e->getMessage()}");
            $this->release(10);
            return;
        }

        if (!$response->successful()) {
            Log::warning("Échec téléchargement image ({$response->status()}): {$url}");
            return;
        }

        File::put($fullPath, $response->body());

        $this->saveRecord($item, $filename, $publicPath);
    }

    protected function saveRecord(Item $item, string $filename, string $publicPath): void
    {
        ItemImage::updateOrCreate(
            ['item_id' => $item->id, 'type' => $this->type],
            [
                'category' => $this->category,
                'filename' => $filename,
                'public_path' => $publicPath,
            ]
        );
    }
}

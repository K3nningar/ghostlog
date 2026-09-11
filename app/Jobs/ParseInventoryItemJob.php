<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

// app/Jobs/ParseInventoryItemJob.php
class ParseInventoryItemJob implements ShouldQueue
{
    use Queueable, Dispatchable, InteractsWithQueue, SerializesModels;

    public $tries = 3;

    public function __construct(
        protected array $data,
        protected int $manifestVersionId
    ) {}

    public function handle(ItemCategorizerService $categorizer): void
    {
        $displayProps = $this->data['displayProperties'] ?? [];
        $name = $displayProps['name'] ?? null;

        // Ignorer les items sans nom (souvent des items techniques/internes)
        if (empty($name)) {
            return;
        }

        $item = Item::updateOrCreate(
            ['hash' => $this->data['hash']],
            [
                'name' => $name,
                'description' => $displayProps['description'] ?? null,
                'flavor_text' => $this->data['flavorText'] ?? null,
                'item_type' => $this->data['itemType'] ?? null,
                'item_sub_type' => $this->data['itemSubType'] ?? null,
                'tier_type' => $this->data['inventory']['tierTypeName'] ?? null,
                'class_type' => $this->data['classType'] ?? null,
                'bucket_hash' => $this->data['inventory']['bucketTypeHash'] ?? null,
                'raw_data' => $this->data,
                'manifest_version_id' => $this->manifestVersionId,
            ]
        );

        $categorySlugs = $categorizer->categorize($this->data);

        $categoryIds = Category::whereIn('slug', $categorySlugs)->pluck('id');
        $item->categories()->sync($categoryIds);

        $primaryCategory = $categorySlugs[0] ?? 'uncategorized';

        if (!empty($displayProps['icon'])) {
            DownloadImageJob::dispatch(
                $item->id,
                'icon',
                $displayProps['icon'],
                $primaryCategory
            )->onQueue('images');
        }

        if (!empty($this->data['screenshot'])) {
            DownloadImageJob::dispatch(
                $item->id,
                'screenshot',
                $this->data['screenshot'],
                $primaryCategory
            )->onQueue('images');
        }
    }
}

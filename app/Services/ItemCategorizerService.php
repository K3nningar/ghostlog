<?php

namespace App\Services;

use App\Enums\DestinyItemType;

class ItemCategorizerService
{
    /**
     * Retourne un tableau de slugs de catégories, la première étant la principale.
     */
    public function categorize(array $manifestItem): array
    {
        $categories = [];

        $itemType = $manifestItem['itemType'] ?? null;

        if ($itemType !== null) {
            $slug = DestinyItemType::toCategorySlug($itemType);
            $categories[] = $slug;
        }

        // Sous-catégorisation optionnelle par classe pour armures
        if (($categories[0] ?? null) === 'armor' && isset($manifestItem['classType'])) {
            $classSlug = $this->classTypeToSlug($manifestItem['classType']);
            if ($classSlug) {
                $categories[] = "armor-{$classSlug}";
            }
        }

        // Fallback
        if (empty($categories)) {
            $categories[] = 'uncategorized';
        }

        return array_unique($categories);
    }

    protected function classTypeToSlug(int $classType): ?string
    {
        return match ($classType) {
            0 => 'titan',
            1 => 'hunter',
            2 => 'warlock',
            default => null,
        };
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VendorSparrowController extends Controller
{
    public function index(Request $request): Response
    {
        $locale = $request->session()->get('locale', 'fr');
        $vendor = config('vendor_sparrows');
        $hashes = collect($vendor['categories'])
            ->flatMap(fn (array $category): array => $category['sparrows'])
            ->unique()
            ->values();

        $sparrows = Item::query()
            ->where('category_slug', 'sparrows')
            ->where('locale', $locale)
            ->whereIn('hash', $hashes)
            ->get([
                'id', 'hash', 'locale', 'name', 'description',
                'archive_icon_path', 'archive_icon_path_secondary', 'ingame_image_path',
                'icon_downloaded', 'tier_type', 'tier_type_name', 'subcategory_slug',
            ])
            ->map(function (Item $item): Item {
                foreach (['archive_icon_path', 'archive_icon_path_secondary', 'ingame_image_path'] as $field) {
                    $path = $item->{$field};
                    if ($path && ! preg_match('/^https?:\\/\\//i', $path)) {
                        $item->{$field} = '/'.ltrim($path, '/');
                    }
                }

                return $item;
            })
            ->keyBy('hash');

        $categories = collect($vendor['categories'])
            ->map(function (array $category) use ($sparrows, $locale): array {
                $title = $category['titles'][$locale]
                    ?? $category['titles']['en']
                    ?? $category['titles']['fr'];

                return [
                    'hash' => $category['hash'],
                    'name' => $title,
                    'sparrows' => collect($category['sparrows'])
                        ->map(fn (string $hash) => $sparrows->get($hash))
                        ->filter()
                        ->values(),
                ];
            })
            ->filter(fn (array $category): bool => $category['sparrows']->isNotEmpty())
            ->values();

        return Inertia::render('Collections/Sparrows', [
            'vendorHash' => $vendor['vendor_hash'],
            'categories' => $categories,
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VendorArgentumController extends Controller
{
    public function index(Request $request): Response
    {
        $locale = $request->session()->get('locale', 'fr');
        $vendor = config('vendor_argentum_items');
        $hashes = collect($vendor['categories'])
            ->flatMap(fn (array $category): array => $category['items'])
            ->unique()
            ->values();
        $items = Item::query()
            ->where('locale', $locale)
            ->whereIn('hash', $hashes)
            ->get([
                'id', 'hash', 'locale', 'name', 'description', 'category_slug',
                'archive_icon_path', 'archive_icon_path_secondary', 'ingame_image_path',
                'icon_downloaded', 'tier_type', 'tier_type_name', 'subcategory_slug',
            ])
            ->map(function (Item $item): Item {
                foreach (['archive_icon_path', 'archive_icon_path_secondary', 'ingame_image_path'] as $field) {
                    $path = $item->{$field};
                    if ($path && ! preg_match('/^https?:\/\//i', $path)) {
                        $item->{$field} = '/'.ltrim($path, '/');
                    }
                }

                return $item;
            })
            ->keyBy('hash');

        $categories = collect($vendor['categories'])
            ->map(function (array $category) use ($items, $locale): array {
                $title = $category['titles'][$locale]
                    ?? $category['titles']['en']
                    ?? $category['titles']['fr'];

                return [
                    'hash' => $category['hash'],
                    'name' => $title !== '' ? $title : __('collections.other'),
                    'items' => collect($category['items'])
                        ->map(fn (string $hash) => $items->get($hash))
                        ->filter()
                        ->values(),
                ];
            })
            ->filter(fn (array $category): bool => $category['items']->isNotEmpty())
            ->values();

        return Inertia::render('Collections/Argentum', [
            'vendorHash' => $vendor['vendor_hash'],
            'categories' => $categories,
        ]);
    }
}

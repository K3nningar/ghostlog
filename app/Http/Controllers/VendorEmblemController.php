<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class VendorEmblemController extends Controller
{
    public function index(Request $request): Response
    {
        $locale = $request->session()->get('locale', 'fr');
        $vendor = config('vendor_emblems');
        $hashes = collect($vendor['categories'])
            ->flatMap(fn (array $category): array => $category['emblems'])
            ->unique()
            ->values();

        $emblems = Item::query()
            ->where('category_slug', 'emblems')
            ->where('locale', $locale)
            ->whereIn('hash', $hashes)
            ->get([
                'id', 'hash', 'locale', 'name', 'description',
                'archive_icon_path', 'archive_icon_path_secondary', 'ingame_image_path',
                'icon_downloaded', 'tier_type', 'tier_type_name', 'subcategory_slug',
            ])
            ->keyBy('hash');

        $categories = collect($vendor['categories'])
            ->map(function (array $category) use ($emblems, $locale): array {
                $title = $category['titles'][$locale]
                    ?? $category['titles']['en']
                    ?? $category['titles']['fr'];

                return [
                    'hash' => $category['hash'],
                    'name' => $title !== '' ? $title : __('collections.other'),
                    'emblems' => collect($category['emblems'])
                        ->map(fn (string $hash) => $emblems->get($hash))
                        ->filter()
                        ->values(),
                ];
            })
            ->filter(fn (array $category): bool => $category['emblems']->isNotEmpty())
            ->values();

        return Inertia::render('Collections/Emblems', [
            'vendorHash' => $vendor['vendor_hash'],
            'categories' => $categories,
        ]);
    }
}

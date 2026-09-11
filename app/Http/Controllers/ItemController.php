<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ItemController extends Controller
{
    /**
     * Colonnes autorisées pour le tri, afin d'éviter toute injection
     * ou erreur SQL via un paramètre non contrôlé.
     */
    private const ALLOWED_SORTS = [
        'name',
        'tier_type_name',
        'item_type_name',
        'created_at',
    ];

    public function index(Request $request)
    {
        $sort = in_array($request->sort, self::ALLOWED_SORTS, true)
            ? $request->sort
            : 'name';

        $direction = $request->direction === 'desc' ? 'desc' : 'asc';

        $items = Item::query()
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->when($request->category, function ($query, $category) {
                $query->where('category_slug', $category);
            })
            ->when($request->subcategory, function ($query, $subcategory) {
                $query->where('subcategory_slug', $subcategory);
            })
            ->when($request->tier, function ($query, $tier) {
                $query->where('tier_type_name', $tier);
            })
            ->when($request->class_type !== null && $request->class_type !== '', function ($query) use ($request) {
                $query->where('class_type', $request->class_type);
            })
            ->orderBy($sort, $direction)
            ->paginate(48)
            ->withQueryString();

        $categories = Item::query()
            ->whereNotNull('category_slug')
            ->select('category_slug')
            ->distinct()
            ->orderBy('category_slug')
            ->pluck('category_slug');

        $subcategories = Item::query()
            ->whereNotNull('subcategory_slug')
            ->select('subcategory_slug', 'category_slug', 'item_type_name')
            ->distinct()
            ->get()
            ->unique('subcategory_slug')
            ->sortBy('item_type_name')
            ->values();

        $tiers = Item::query()
            ->whereNotNull('tier_type_name')
            ->select('tier_type_name')
            ->distinct()
            ->pluck('tier_type_name');

        return Inertia::render('Items/Index', [
            'items' => $items,
            'categories' => $categories,
            'subcategories' => $subcategories,
            'tiers' => $tiers,
            'filters' => $request->only([
                'category', 'subcategory', 'search', 'tier',
                'class_type', 'sort', 'direction',
            ]),
        ]);
    }

    public function show(Item $item)
    {
        return Inertia::render('Items/Show', [
            'item' => $item,
        ]);
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SparrowController extends Controller
{
    public function index(Request $request)
    {
        $locale = $request->session()->get('locale', 'fr');

        $sparrows = Item::where('category_slug', 'sparrows')
            ->where('locale', $locale)
            ->orderBy('hash', 'desc')
        ->get([
            'id',
            'hash',
            'locale',
            'name',
            'description',
            'archive_icon_path',
            'ingame_image_path',
            'icon_downloaded',
            'tier_type',
            'tier_type_name',
            'subcategory_slug',
        ]);

        return Inertia::render('Sparrows/Index', [
            'sparrows' => $sparrows,
        ]);
    }
}
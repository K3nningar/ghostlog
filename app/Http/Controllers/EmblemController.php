<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EmblemController extends Controller
{
    public function index(Request $request)
    {
        $locale = $request->session()->get('locale', 'fr');

        $emblems = Item::where('category_slug', 'emblems')
            ->where('locale', $locale)
            ->orderBy('hash', 'asc')
            ->get([
                'id',
                'hash',
                'locale',
                'name',
                'description',
                'archive_icon_path',
                'archive_icon_path_secondary',
                'ingame_image_path',
                'icon_downloaded',
                'tier_type',
                'tier_type_name',
                'subcategory_slug',
            ]);

        return Inertia::render('Emblems/Index', [
            'emblems' => $emblems,
        ]);
    }
}

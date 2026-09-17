<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ArmorController extends Controller
{
    public function index(Request $request)
    {
        $locale = $request->session()->get('locale', 'fr');

        $armors = Item::where('category_slug', 'armors')
            ->where('locale', $locale)
            ->orderBy('hash', 'asc')
            ->get([
                'id', 'hash', 'locale', 'name', 'description',
                'archive_icon_path', 'ingame_image_path', 'icon_downloaded',
                'tier_type', 'tier_type_name', 'subcategory_slug',
            ]);

        return Inertia::render('Armors/Index', [
            'armors' => $armors,
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $locale = $request->session()->get('locale', 'fr');
        $stats = $this->computeStats($locale);

        return Inertia::render('Home', [
            'stats' => $stats,
        ]);
    }

    /**
     * Calcule les statistiques sur le même périmètre que les pages de catégories.
     */
    private function computeStats(string $locale): array
    {
        return Item::query()
            ->where('locale', $locale)
            ->whereIn('category_slug', [
                'weapons',
                'armors',
                'ships',
                'sparrows',
                'emblems',
                'ghosts',
                'consumables',
            ])
            ->selectRaw('category_slug, COUNT(*) as total')
            ->groupBy('category_slug')
            ->pluck('total', 'category_slug')
            ->union([
                'weapons' => 0,
                'armors' => 0,
                'ships' => 0,
                'sparrows' => 0,
                'emblems' => 0,
                'ghosts' => 0,
                'consumables' => 0,
            ])
            ->only([
                'weapons',
                'armors',
                'ships',
                'sparrows',
                'emblems',
                'ghosts',
                'consumables',
            ])
            ->all();
    }
}
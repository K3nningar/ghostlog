<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class HomeController extends Controller
{
    /**
     * Regroupement des item_type_name par catégorie affichée sur la home.
     */
    private const TYPE_GROUPS = [
        'weapons' => [
            "Fusil d'éclaireur",
            'Revolver',
            'Fusil de précision',
            'Fusil automatique',
            'Fusil à fusion',
            'Fusil à pompe',
            'Fusil à impulsion',
        ],
        'armors' => [
            'Casque',
            'Ceinture de Titan',
            'Armure de torse',
            'Armure de jambe',
            'Gantelets',
        ],
        'ships' => [
            'Vaisseau',
            'Schémas de vaisseau',
        ],
        'sparrows' => [
            'Véhicule',
        ],
        'emblems' => [
            'Emblème',
        ],
        'consumables' => [
            'Objet à usage unique',
            'Matériau',
            'Colis',
        ],
    ];

    public function index()
    {
        $stats = Cache::remember('home_stats', now()->addHours(24), function () {
            return $this->computeStats();
        });

        return Inertia::render('Home', [
            'stats' => $stats,
        ]);
    }

    /**
     * Calcule les statistiques par catégorie en une seule requête SQL,
     * au lieu de 6 requêtes COUNT() séparées.
     */
    private function computeStats(): array
    {
        $counts = Item::selectRaw('item_type_name, COUNT(*) as total')
            ->whereNotNull('item_type_name')
            ->groupBy('item_type_name')
            ->pluck('total', 'item_type_name');

        $stats = [];

        foreach (self::TYPE_GROUPS as $key => $types) {
            $stats[$key] = collect($types)
                ->sum(fn (string $type) => $counts[$type] ?? 0);
        }

        return $stats;
    }
}
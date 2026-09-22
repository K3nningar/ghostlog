<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EmblemController extends Controller
{
    /**
     * Liste paginée des emblèmes (catalogue).
     *
     * Même logique que WeaponController::index() : filtrage et tri poussés
     * côté serveur AVANT la pagination, afin de ne tirer que la page courante.
     * La pagination serveur remplace l'ancien `get()` complet chargé côté front
     * (filtres appliqués en JS sur l'intégralité des emblèmes).
     */
    public function index(Request $request)
    {
        $locale = $request->session()->get('locale', 'fr');

        $query = Item::query()
            ->where('category_slug', 'emblems')
            ->where('locale', $locale);

        // ---- Filtres serveur (avant la pagination) ----
        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $query->where('name', 'like', '%' . $search . '%');
        }

        $tier = $request->query('tier', '');
        if ($tier !== '') {
            $query->where(function ($q) use ($tier) {
                if (is_numeric($tier)) {
                    $q->where('tier_type', (int) $tier);
                }
                $q->orWhere('tier_type_name', $tier);
            });
        }

        $confidential = $request->query('confidential', '');
        if ($confidential === 'classified') {
            $query->whereIn('subcategory_slug', ['classified', 'censored', 'secret']);
        } elseif ($confidential === 'public') {
            $query->whereNotIn('subcategory_slug', ['classified', 'censored', 'secret']);
        }

        // ---- Tri serveur ----
        [$sortColumn, $sortDirection] = $this->resolveSort($request->query('sort', 'default'));

        if ($sortColumn !== null) {
            $query->orderBy($sortColumn, $sortDirection);
        } else {
            $query->orderBy('hash', 'asc');
        }

        $emblems = $query
            ->select([
                'id', 'hash', 'locale', 'name', 'description',
                'archive_icon_path', 'archive_icon_path_secondary', 'ingame_image_path',
                'icon_downloaded', 'tier_type', 'tier_type_name', 'subcategory_slug',
            ])
            ->paginate(48)
            ->withQueryString();

        return Inertia::render('Emblems/Index', [
            'emblems' => $emblems,
            'filters' => [
                'search'        => $search,
                'tier'          => $tier,
                'confidential'  => $confidential,
                'sort'          => $request->query('sort', 'default'),
            ],
        ]);
    }

    /**
     * Résout l'option de tri du front en couple (colonne SQL, direction).
     * "default" → null (ordre par hash géré dans index()).
     *
     * @return array{0:?string,1:string}
     */
    protected function resolveSort(string $sort): array
    {
        return match ($sort) {
            'tier-asc'   => ['tier_type', 'asc'],
            'tier-desc'  => ['tier_type', 'desc'],
            'name-asc'   => ['name', 'asc'],
            'name-desc'  => ['name', 'desc'],
            'hash-asc'   => ['hash', 'asc'],
            'hash-desc'  => ['hash', 'desc'],
            default      => [null, 'asc'],
        };
    }
}

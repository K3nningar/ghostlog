<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SparrowController extends Controller
{
    /**
     * Liste paginée des passereaux (catalogue).
     *
     * Même logique que WeaponController::index() : filtrage et tri poussés
     * côté serveur AVANT la pagination, afin de ne tirer que la page courante.
     * La pagination serveur remplace l'ancien `get()` complet chargé côté front
     * (filtres appliqués en JS sur l'intégralité des passereaux).
     */
    public function index(Request $request)
    {
        $locale = $request->session()->get('locale', 'fr');

        $query = Item::query()
            ->where('category_slug', 'sparrows')
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

        // ---- Pagination ----
        // Taille de page sélectionnable via le front (sélecteur "Par page"),
        // restreinte à une whitelist pour éviter tout abus de paramètre.
        $perPageParam = (int) $request->query('per_page', '48');
        $perPage = in_array($perPageParam, [24, 48, 96, 192], true) ? $perPageParam : 48;

        // ---- Tri serveur ----
        [$sortColumn, $sortDirection] = $this->resolveSort($request->query('sort', 'default'));

        if ($sortColumn !== null) {
            $query->orderBy($sortColumn, $sortDirection);
        } else {
            $query->orderBy('hash', 'asc');
        }

        $sparrows = $query
            ->select([
                'id', 'hash', 'locale', 'name', 'description',
                'archive_icon_path', 'ingame_image_path', 'icon_downloaded',
                'tier_type', 'tier_type_name', 'subcategory_slug',
            ])
            ->paginate($perPage)
            ->withQueryString();

        // Liste exhaustive des raretés de la catégorie (toutes pages
        // confondues) : le filtre du front doit proposer toutes les options
        // même si aucune ne figure dans la page courante. Chaque entrée
        // embarque son tier_type afin que le front puisse colorer l'option
        // selon la rareté, indépendamment de la langue. Tri par tier_type
        // décroissant (exotique en premier), valeurs NULL en dernier.
        $tiers = Item::query()
            ->where('category_slug', 'sparrows')
            ->where('locale', $locale)
            ->whereNotNull('tier_type_name')
            ->groupBy('tier_type', 'tier_type_name')
            ->orderByRaw('tier_type IS NULL, tier_type DESC')
            ->get(['tier_type', 'tier_type_name'])
            ->unique('tier_type_name')
            ->values();

        return Inertia::render('Sparrows/Index', [
            'sparrows' => $sparrows,
            'tiers' => $tiers,
            'filters' => [
                'search'        => $search,
                'tier'          => $tier,
                'confidential'  => $confidential,
                'sort'          => $request->query('sort', 'default'),
                'per_page'      => $perPage,
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

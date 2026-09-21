<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

class WeaponController extends Controller
{
    /**
     * Liste paginée des armes (catalogue).
     *
     * Optimisation : la relation "perks" (BelongsToMany sur item_perk) n'est PLUS
     * chargée ici — elle coûtait ~80 000 lignes pivot sur 8 148 armes avec en
     * moyenne ~10 perks chacune. Les perks d'une arme sont désormais récupérés à
     * la demande via l'endpoint perks() quand le drawer de détail s'ouvre.
     *
     * On sélectionne uniquement les colonnes strictement nécessaires à la grille :
     *   id, hash, locale, name, description, archive_icon_path, ingame_image_path,
     *   icon_downloaded, tier_type, tier_type_name, subcategory_slug.
     *
     * Le filtrage / tri est poussé côté serveur AVANT la pagination afin de ne
     * tirer que la page courante.
     *
     * Route (à ajouter dans routes/web.php si non présente) :
     *   Route::get('/weapons', [WeaponController::class, 'index'])->name('weapons.index');
     */
    public function index(Request $request)
    {
        $locale = $request->session()->get('locale', 'fr');

        $query = Item::query()
            ->where('category_slug', 'weapons')
            ->where('locale', $locale);

        // ---- Filtres serveur (avant la pagination) ----

        // Recherche texte sur le nom (équivalent du filtre "search" du front).
        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $query->where('name', 'like', '%' . $search . '%');
        }

        // Filtre tier (rarity). Le front envoie soit un libellé (tier_type_name),
        // soit un id numérique (tier_type) — on accepte les deux.
        $tier = $request->query('tier', '');
        if ($tier !== '') {
            $query->where(function ($q) use ($tier) {
                if (is_numeric($tier)) {
                    $q->where('tier_type', (int) $tier);
                }
                $q->orWhere('tier_type_name', $tier);
            });
        }

        // Filtre "classified" vs "public".
        //   - classified = sous-catégorie parmi classified / censored / secret
        //   - public     = tout le reste
        $confidential = $request->query('confidential', '');
        if ($confidential === 'classified') {
            $query->whereIn('subcategory_slug', ['classified', 'censored', 'secret']);
        } elseif ($confidential === 'public') {
            $query->whereNotIn('subcategory_slug', ['classified', 'censored', 'secret']);
        }

        // ---- Tri serveur ----
        // On utilise les paramètres "sort" + "dir" envoyés par le front
        // (compatibles avec les anciennes options : default / tier-asc / name-asc / ...)
        [$sortColumn, $sortDirection] = $this->resolveSort($request->query('sort', 'default'));

        if ($sortColumn !== null) {
            $query->orderBy($sortColumn, $sortDirection);
        } else {
            // Tri par défaut : ordre stable par hash croissant.
            $query->orderBy('hash', 'asc');
        }

        // ---- Colonnes strictement nécessaires à la grille ----
        $weapons = $query
            ->select([
                'id', 'hash', 'locale', 'name', 'description',
                'archive_icon_path', 'ingame_image_path', 'icon_downloaded',
                'tier_type', 'tier_type_name', 'subcategory_slug',
            ])
            // 48 par page : ~6 colonnes de grille de 8 items sur écrans XL,
            // avec un padding visuel confortable. Pagination serveur Inertia.
            ->paginate(48)
            ->withQueryString();

        return Inertia::render('Weapons/Index', [
            'weapons' => $weapons,
            'filters' => [
                'search'        => $search,
                'tier'          => $tier,
                'confidential'  => $confidential,
                'sort'          => $request->query('sort', 'default'),
            ],
        ]);
    }

    /**
     * Retourne en JSON les perks d'une arme identifiée par son hash.
     *
     * Pourquoi hash et pas id ?
     * La table `items` contient des doublons de traduction (un même hash Bungie
     * existe pour plusieurs `locale`). Le `hash` est donc l'identifiant métier
     * unique ; l'`id` auto-incrémenté ne l'est pas. On filtre sur la locale
     * courante pour ne renvoyer que les perks de la langue affichée.
     *
     * Colonnes pivot exposées (via `withPivot()` pour qu'Eloquent les hydrate
     * sous `perk.pivot.*` — c'est ce que le front attend) :
     *   - sort_order   (colonne d'affichage original — toujours présent)
     *   - column       (bucket / colonne dans l'UI — si la colonne existe)
     *   - node_index   (position dans la colonne — si la colonne existe)
     *   - is_default_step (perk "par défaut" mis en avant — si la colonne existe)
     * Les colonnes pivot optionnelles sont ajoutées dynamiquement en fonction
     * du schéma réel de `item_perk` (cf. migration
     * 2026_09_17_145036_create_table_pivot_item_perk.php), pour rester
     * compatible si l'une d'elles n'a pas encore été migrée.
     *
     * Tri final : sort_order ASC puis hash ASC (stabilité).
     *
     * Route (à ajouter dans routes/web.php si non présente) :
     *   Route::get('/weapons/{hash}/perks', [WeaponController::class, 'perks'])
     *       ->name('weapons.perks');
     */
    public function perks(Request $request, string $hash)
    {
        $locale = $request->session()->get('locale', 'fr');

        // Récupère l'item métier (par hash + locale) pour avoir l'id pivot
        // unique correspondant à la langue courante. Cela évite toute ambiguïté
        // entre traductions lors du join sur item_perk.
        $item = Item::query()
            ->where('hash', $hash)
            ->where('locale', $locale)
            ->select(['id', 'hash', 'locale'])
            ->first();

        if (!$item) {
            return response()->json(['perks' => []], 404);
        }

        // Pivot optionnel : on ne demande avecPivot() que les colonnes qui
        // existent réellement en base.
        $available = Schema::getColumnListing('item_perk');
        $optionalPivot = array_values(array_intersect(
            ['column', 'node_index', 'is_default_step'],
            $available
        ));

        // Tri principal : sort_order ASC, puis hash ASC (stabilité du tri
        // quand plusieurs perks partagent le même sort_order).
        $perks = $item->perks()
            ->withPivot(array_merge(['sort_order'], $optionalPivot))
            ->orderBy('item_perk.sort_order', 'asc')
            ->orderBy('perks.hash', 'asc')
            ->get([
                'perks.id', 'perks.hash', 'perks.name', 'perks.description',
                'perks.icon_url', 'perks.archive_icon_path', 'perks.icon_downloaded',
            ]);

        return response()->json([
            'perks' => $perks,
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
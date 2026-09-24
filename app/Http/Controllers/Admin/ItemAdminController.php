<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Item;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ItemAdminController extends Controller
{
    /**
     * Colonnes éditables via l'interface d'administration. Tout champ
     * `items` utile est modifiable ; `id` et `created_at` restent
     * system-managed. `raw_json` est éditable en JSON (validé).
     */
    private const EDITABLE_COLUMNS = [
        'hash', 'locale', 'name', 'description', 'icon_url',
        'item_type', 'item_type_name', 'item_sub_type', 'class_type',
        'tier_type', 'tier_type_name', 'bucket_type_hash',
        'category_hashes', 'category_slug', 'subcategory_slug',
        'archive_icon_path', 'archive_icon_path_secondary',
        'ingame_image_path', 'icon_downloaded', 'raw_json',
    ];

    /**
     * Liste des items, recherche + filtre catégorie, pagination serveur.
     */
    public function index(Request $request)
    {
        $query = Item::query()
            ->select([
                'id', 'hash', 'locale', 'name', 'category_slug', 'subcategory_slug',
                'tier_type', 'tier_type_name', 'icon_downloaded', 'updated_at',
            ]);

        $search = trim((string) $request->query('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('hash', 'like', '%' . $search . '%');
            });
        }

        $category = trim((string) $request->query('category', ''));
        if ($category !== '') {
            $query->where('category_slug', $category);
        }

        $locale = trim((string) $request->query('locale', ''));
        if ($locale !== '') {
            $query->where('locale', $locale);
        }

        $items = $query
            ->orderBy('updated_at', 'desc')
            ->paginate(25)
            ->withQueryString();

        return Inertia::render('Admin/Items/Index', [
            'items' => $items,
            'categories' => Item::query()
                ->whereNotNull('category_slug')
                ->distinct()
                ->orderBy('category_slug')
                ->pluck('category_slug'),
            'locales' => Item::query()
                ->distinct()
                ->orderBy('locale')
                ->pluck('locale'),
            'filters' => [
                'search'   => $search,
                'category' => $category,
                'locale'   => $locale,
            ],
        ]);
    }

    /**
     * Formulaire de création.
     */
    public function create()
    {
        return Inertia::render('Admin/Items/Create', [
            'categories' => $this->knownCategories(),
            'locales' => $this->knownLocales(),
        ]);
    }

    /**
     * Formulaire d'édition : toutes les colonnes de l'item sont exposées.
     * L'item est résolu par `id` (pas le hash) pour cibler précisément
     * UNE traduction, sans ambiguïté entre locales.
     */
    public function edit(Item $item)
    {
        return Inertia::render('Admin/Items/Edit', [
            'item' => $item,
            'categories' => $this->knownCategories(),
            'locales' => $this->knownLocales(),
        ]);
    }

    /**
     * Création d'un item (avec collision hash+locale interceptée).
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $duplicate = Item::query()
            ->where('hash', $data['hash'])
            ->where('locale', $data['locale'])
            ->exists();
        if ($duplicate) {
            return back()->withErrors([
                'hash' => "Un item avec ce hash existe déjà pour la locale « {$data['locale']} ».",
            ])->withInput();
        }

        // Uploads éventuels : les chemins stockés sont relatifs à public/.
        $data['archive_icon_path'] = $this->handleIconUpload($request, $data['archive_icon_path'] ?? null);
        $data['archive_icon_path_secondary'] = $this->handleIconUpload($request, $data['archive_icon_path_secondary'] ?? null, 'secondary');
        $data['ingame_image_path'] = $this->handleMediaUpload($request, $data['ingame_image_path'] ?? null);
        if ($data['archive_icon_path'] !== null) {
            $data['icon_downloaded'] = true;
        }

        $item = Item::create($data);

        return redirect()
            ->route('admin.items.edit', $item)
            ->with('success', 'Item créé avec succès.');
    }

    /**
     * Mise à jour : toutes les colonnes éditables sont acceptées.
     * Si hash ou locale change, on vérifie l'unicité (hash, locale).
     */
    public function update(Request $request, Item $item): RedirectResponse
    {
        $data = $this->validated($request, $item);

        $duplicate = Item::query()
            ->where('hash', $data['hash'])
            ->where('locale', $data['locale'])
            ->whereKeyNot($item->getKey())
            ->exists();
        if ($duplicate) {
            return back()->withErrors([
                'hash' => "Un autre item utilise déjà ce hash pour la locale « {$data['locale']} ».",
            ])->withInput();
        }

        // Uploads éventuels : un nouveau fichier écrase le champ correspondant
        // (les chemins stockés restent relatifs à public/).
        $data['archive_icon_path'] = $this->handleIconUpload($request, $data['archive_icon_path'] ?? null);
        $data['archive_icon_path_secondary'] = $this->handleIconUpload($request, $data['archive_icon_path_secondary'] ?? null, 'secondary');
        $data['ingame_image_path'] = $this->handleMediaUpload($request, $data['ingame_image_path'] ?? null);
        if ($data['archive_icon_path'] !== null && $data['archive_icon_path'] !== $item->archive_icon_path) {
            $data['icon_downloaded'] = true;
        }

        $item->update($data);

        return back()->with('success', 'Item mis à jour avec succès.');
    }

    /**
     * Stocke une icône uploadée dans public/archive/{catégorie}/ et retourne
     * le chemin relatif à public/. Les icônes secondaires (emblèmes) vont
     * dans public/archive/emblems/secondaryIcon/. Le nom du fichier est
     * dérivé du hash de l'item (stabilité entre locales) + suffixe de
     * variante + extension sûre.
     */
    private function handleIconUpload(Request $request, ?string $current, string $variant = ''): ?string
    {
        $file = $request->file($variant === 'secondary' ? 'archive_icon_secondary_file' : 'archive_icon_file');

        if (!$file || !$file->isValid()) {
            return $current;
        }

        $data = $this->uploadContext($request);
        $hash = $data['hash'];

        $directory = $variant === 'secondary'
            ? 'archive/emblems/secondaryIcon'
            : 'archive/' . ($data['category_slug'] ?: 'misc');

        $extension = strtolower($file->getClientOriginalExtension() ?: 'png');
        if (!in_array($extension, $this->allowedImageExtensions(), true)) {
            $extension = 'png';
        }

        // Nom dérivé du hash uniquement (identique pour les icônes
        // secondaires) : les répertoires de dépôt diffèrent, donc aucune
        // collision possible.
        $filename = "{$hash}.{$extension}";
        $file->move(public_path($directory), $filename);

        return "{$directory}/{$filename}";
    }

    /**
     * Stocke le média "in game" (image ou vidéo) dans
     * public/ingame/{catégorie}/ et retourne le chemin relatif à public/.
     * Le nom du fichier est celui du fichier uploadé (assaini), le format
     * est libre — seules les extensions exécutables sont refusées (risque
     * d'exécution de code depuis public/).
     */
    private function handleMediaUpload(Request $request, ?string $current): ?string
    {
        $file = $request->file('ingame_media_file');

        if (!$file || !$file->isValid()) {
            return $current;
        }

        $extension = strtolower($file->getClientOriginalExtension());

        if (in_array($extension, $this->forbiddenExecutableExtensions(), true)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'ingame_media_file' => 'Ce type de fichier (exécutable/script) est interdit.',
            ]);
        }

        $data = $this->uploadContext($request);
        $directory = 'ingame/' . ($data['category_slug'] ?: 'misc');

        $filename = $this->sanitizeFilename($file->getClientOriginalName());
        $file->move(public_path($directory), $filename);

        return "{$directory}/{$filename}";
    }

    /**
     * Assainit un nom de fichier client : caractères sûrs uniquement,
     * pas de traversal de répertoire, longueur bornée.
     */
    private function sanitizeFilename(string $original): string
    {
        $basename = pathinfo($original, PATHINFO_FILENAME);
        $extension = pathinfo($original, PATHINFO_EXTENSION);

        $safeBase = trim(preg_replace('/[^A-Za-z0-9._\- ]/', '_', $basename) ?: 'ingame');
        $safeBase = substr($safeBase, 0, 120);

        $safeExtension = strtolower(preg_replace('/[^A-Za-z0-9]/', '', $extension) ?: 'bin');

        return "{$safeBase}.{$safeExtension}";
    }

    private function forbiddenExecutableExtensions(): array
    {
        return [
            'php', 'php3', 'php4', 'php5', 'php7', 'phtml', 'pht', 'phar',
            'cgi', 'pl', 'py', 'sh', 'asp', 'aspx', 'jsp', 'exe', 'dll',
            'bat', 'cmd', 'htaccess',
        ];
    }

    /**
     * Contexte d'upload : les champs hash/catégorie du formulaire, en
     * priorité depuis les valeurs soumises, sinon depuis l'item existant.
     */
    private function uploadContext(Request $request): array
    {
        $validated = $request->validate([
            'hash' => ['required', 'string', 'max:255'],
            'category_slug' => ['nullable', 'string', 'max:255'],
            'subcategory_slug' => ['nullable', 'string', 'max:255'],
        ]);

        return [
            'hash' => $validated['hash'],
            'category_slug' => $validated['category_slug'] ?? null,
            'subcategory_slug' => $validated['subcategory_slug'] ?? null,
        ];
    }

    private function allowedImageExtensions(): array
    {
        return ['png', 'jpg', 'jpeg', 'gif', 'webp'];
    }

    /**
     * Validation dynamique : les règles sont dérivées du schéma réel
     * (types, nullabilité) pour couvrir toutes les colonnes éditables.
     */
    private function validated(Request $request, ?Item $item = null): array
    {
        $rules = [
            'hash' => ['required', 'string', 'max:255'],
            'locale' => ['required', 'string', 'max:8'],
            'name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'icon_url' => ['nullable', 'string', 'max:2048'],
            'item_type' => ['nullable', 'integer'],
            'item_type_name' => ['nullable', 'string', 'max:255'],
            'item_sub_type' => ['nullable', 'integer'],
            'class_type' => ['nullable', 'integer'],
            'tier_type' => ['nullable', 'integer'],
            'tier_type_name' => ['nullable', 'string', 'max:255'],
            'bucket_type_hash' => ['nullable', 'integer'],
            'category_hashes' => ['nullable', 'array'],
            'category_hashes.*' => ['integer'],
            'category_slug' => ['nullable', 'string', 'max:255'],
            'subcategory_slug' => ['nullable', 'string', 'max:255'],
            'archive_icon_path' => ['nullable', 'string', 'max:2048'],
            'archive_icon_path_secondary' => ['nullable', 'string', 'max:2048'],
            'ingame_image_path' => ['nullable', 'string', 'max:2048'],
            'icon_downloaded' => ['nullable', 'boolean'],
            'raw_json' => ['nullable', 'json'],
        ];

        $validated = $request->validate($rules);

        // Valeurs par défaut à la création (raw_json est NOT NULL en base).
        if ($item === null) {
            $validated['raw_json'] ??= '{}';
        }

        // Normalisation des champs optionnels : chaîne vide => null.
        foreach (['name', 'description', 'icon_url', 'item_type_name', 'tier_type_name',
            'category_slug', 'subcategory_slug', 'archive_icon_path',
            'archive_icon_path_secondary', 'ingame_image_path'] as $field) {
            if (array_key_exists($field, $validated) && $validated[$field] === '') {
                $validated[$field] = null;
            }
        }

        foreach (['item_type', 'item_sub_type', 'class_type', 'tier_type', 'bucket_type_hash'] as $field) {
            if (array_key_exists($field, $validated) && ($validated[$field] === '' || $validated[$field] === null)) {
                $validated[$field] = null;
            } elseif (isset($validated[$field])) {
                $validated[$field] = (int) $validated[$field];
            }
        }

        if (array_key_exists('icon_downloaded', $validated)) {
            $validated['icon_downloaded'] = $request->boolean('icon_downloaded');
        } else {
            $validated['icon_downloaded'] = $item->icon_downloaded ?? false;
        }

        // Ne jamais persister un raw_json invalide ou vide à la mise à jour.
        if (isset($validated['raw_json'])) {
            $decoded = json_decode($validated['raw_json'], true);
            if (!is_array($decoded)) {
                $validated['raw_json'] = $item->raw_json ?? '{}';
            } else {
                $validated['raw_json'] = json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
        } elseif ($item === null) {
            $validated['raw_json'] = '{}';
        }

        return collect($validated)
            ->only(self::EDITABLE_COLUMNS)
            ->all();
    }

    /**
     * Catégories connues du classifieur (pour les <select> du formulaire),
     * complétées par celles réellement présentes en base.
     */
    private function knownCategories(): array
    {
        $known = [
            'weapons', 'armors', 'ships', 'sparrows', 'emblems', 'ghosts',
            'shaders', 'emotes', 'ornaments', 'masks', 'engrams',
            'consumables', 'materials', 'bounties', 'quests', 'misc',
        ];

        return collect($known)
            ->merge(Item::query()->whereNotNull('category_slug')->distinct()->pluck('category_slug'))
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    private function knownLocales(): array
    {
        return collect(['en', 'fr', 'de', 'es', 'it', 'ja', 'pt-br'])
            ->merge(Item::query()->distinct()->pluck('locale'))
            ->unique()
            ->sort()
            ->values()
            ->all();
    }
}

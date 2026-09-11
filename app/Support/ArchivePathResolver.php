<?php

namespace App\Support;

class ArchivePathResolver
{
    /**
     * Nom du sous-dossier ajouté systématiquement en fin de chemin.
     */
    protected const ICONS_SUBFOLDER = 'icons';

    /**
     * Résout le chemin relatif (depuis public/archive) où stocker l'icône
     * d'un item, en fonction de son category_slug et subcategory_slug.
     *
     * Exemple retour : "armors/titan_helmets/icons"
     */
    public static function resolve(?string $categorySlug, ?string $subcategorySlug): string
    {
        $base = self::resolveBasePath($categorySlug, $subcategorySlug);

        return rtrim($base, '/') . '/' . self::ICONS_SUBFOLDER;
    }

    /**
     * Résout le chemin absolu complet (disque) pour public/archive/...
     */
    public static function resolveAbsolute(?string $categorySlug, ?string $subcategorySlug): string
    {
        return public_path('archive/' . self::resolve($categorySlug, $subcategorySlug));
    }

    /**
     * Logique de résolution du chemin de base (sans le sous-dossier icons/).
     */
    protected static function resolveBasePath(?string $categorySlug, ?string $subcategorySlug): string
    {
        if (empty($categorySlug)) {
            return 'misc';
        }

        return match ($categorySlug) {
            'weapons' => self::resolveWeaponPath($subcategorySlug),
            'armors' => self::resolveArmorPath($subcategorySlug),
            'ships' => self::resolveShipPath($subcategorySlug),
            'ghosts' => 'ghosts',
            'sparrows' => 'sparrows',
            'emblems' => 'emblems',
            'shaders' => 'shaders',
            'emotes' => 'emotes',
            'ornaments' => 'ornaments',
            'masks' => 'masks',
            'engrams' => 'engrams',
            'consumables' => 'consumables',
            'quests' => 'quests',
            default => 'misc',
        };
    }

    protected static function resolveWeaponPath(?string $subcategorySlug): string
    {
        $valid = [
            'auto_rifles', 'hand_cannons', 'scout_rifles', 'pulse_rifles',
            'sniper_rifles', 'fusion_rifles', 'shotguns', 'rocket_launchers',
            'machine_guns', 'sidearms', 'swords',
        ];

        if (in_array($subcategorySlug, $valid, true)) {
            return "weapons/{$subcategorySlug}";
        }

        return 'weapons/misc';
    }

    protected static function resolveArmorPath(?string $subcategorySlug): string
    {
        $valid = [
            'titan_helmets', 'titan_arms', 'titan_chest', 'titan_legs', 'titan_class_items',
            'hunter_helmets', 'hunter_arms', 'hunter_chest', 'hunter_legs', 'hunter_class_items',
            'warlock_helmets', 'warlock_arms', 'warlock_chest', 'warlock_legs', 'warlock_class_items',
        ];

        if (in_array($subcategorySlug, $valid, true)) {
            return "armors/{$subcategorySlug}";
        }

        return 'armors/misc';
    }

    protected static function resolveShipPath(?string $subcategorySlug): string
    {
        if ($subcategorySlug === 'blueprints') {
            return 'ships/blueprints';
        }

        return 'ships';
    }
}
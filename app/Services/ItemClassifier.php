<?php

namespace App\Services;

class ItemClassifier
{
    // Hashes de catégories D1 (résolus depuis DestinyItemCategoryDefinition)
    private const CAT_WEAPON = 1;
    private const CAT_ARMOR = 20;
    private const CAT_CLASS_WARLOCK = 21;
    private const CAT_CLASS_TITAN = 22;
    private const CAT_CLASS_HUNTER = 23;
    private const CAT_ARMOR_HEAD = 45;
    private const CAT_ARMOR_ARMS = 46;
    private const CAT_ARMOR_CHEST = 47;
    private const CAT_ARMOR_LEGS = 48;
    private const CAT_ARMOR_CLASS_ITEM = 49;
    private const CAT_GHOST = 39;
    private const CAT_EMBLEM = 19;
    private const CAT_SHADER = 41;
    private const CAT_EMOTE = 44;
    private const CAT_ORNAMENT = 56;
    private const CAT_MASK = 55;
    private const CAT_ENGRAM = 34;
    private const CAT_SHIP = 42;
    private const CAT_SHIP_BLUEPRINT = 51;
    private const CAT_VEHICLE = 43; // = passereaux uniquement en D1
    private const CAT_CONSUMABLE = 35;
    private const CAT_EXCHANGE_MATERIAL = 36;
    private const CAT_BOUNTY = 26;
    private const CAT_QUEST_STEP = 16;

    // Sous-types d'armes (itemSubType) => slug
    private const WEAPON_SUBTYPES = [
        5 => 'auto_rifles',
        6 => 'auto_rifles',
        7 => 'shotguns',
        8 => 'machine_guns',
        9 => 'hand_cannons',
        10 => 'sniper_rifles',
        11 => 'fusion_rifles',
        12 => 'sniper_rifles',
        13 => 'pulse_rifles',
        14 => 'scout_rifles',
        17 => 'sidearms',
        18 => 'swords',
    ];

    /**
     * Retourne ['category' => 'weapons', 'subcategory' => 'auto_rifles']
     */
    public function classify(array $categoryHashes, int $itemType, int $itemSubType, int $classType): array
    {
        $hashes = $categoryHashes;

        // Armes
        if (in_array(self::CAT_WEAPON, $hashes)) {
            $subcat = self::WEAPON_SUBTYPES[$itemSubType] ?? 'other';
            return ['category' => 'weapons', 'subcategory' => $subcat];
        }

        // Armures (avec classe + slot)
        if (in_array(self::CAT_ARMOR, $hashes)) {
            $class = match (true) {
                in_array(self::CAT_CLASS_TITAN, $hashes) => 'titan',
                in_array(self::CAT_CLASS_HUNTER, $hashes) => 'hunter',
                in_array(self::CAT_CLASS_WARLOCK, $hashes) => 'warlock',
                default => 'any',
            };

            $slot = match (true) {
                in_array(self::CAT_ARMOR_HEAD, $hashes) => 'helmets',
                in_array(self::CAT_ARMOR_ARMS, $hashes) => 'arms',
                in_array(self::CAT_ARMOR_CHEST, $hashes) => 'chest',
                in_array(self::CAT_ARMOR_LEGS, $hashes) => 'legs',
                in_array(self::CAT_ARMOR_CLASS_ITEM, $hashes) => 'class_items',
                in_array(self::CAT_GHOST, $hashes) => 'ghosts',
                default => 'other',
            };

            if ($slot === 'ghosts') {
                return ['category' => 'ghosts', 'subcategory' => null];
            }

            return ['category' => 'armors', 'subcategory' => "{$class}_{$slot}"];
        }

        // Vaisseaux
        if (in_array(self::CAT_SHIP, $hashes)) {
            $subcat = in_array(self::CAT_SHIP_BLUEPRINT, $hashes) ? 'blueprints' : null;
            return ['category' => 'ships', 'subcategory' => $subcat];
        }

        // Passereaux (tous les vehicles en D1)
        if (in_array(self::CAT_VEHICLE, $hashes)) {
            return ['category' => 'sparrows', 'subcategory' => null];
        }

        // Emblèmes
        if (in_array(self::CAT_EMBLEM, $hashes)) {
            return ['category' => 'emblems', 'subcategory' => null];
        }

        // Shaders
        if (in_array(self::CAT_SHADER, $hashes)) {
            return ['category' => 'shaders', 'subcategory' => null];
        }

        // Emotes
        if (in_array(self::CAT_EMOTE, $hashes)) {
            return ['category' => 'emotes', 'subcategory' => null];
        }

        // Ornements
        if (in_array(self::CAT_ORNAMENT, $hashes)) {
            return ['category' => 'ornaments', 'subcategory' => null];
        }

        // Masques
        if (in_array(self::CAT_MASK, $hashes)) {
            return ['category' => 'masks', 'subcategory' => null];
        }

        // Engrammes
        if (in_array(self::CAT_ENGRAM, $hashes)) {
            return ['category' => 'engrams', 'subcategory' => null];
        }

        // Consommables / matériaux
        if (in_array(self::CAT_CONSUMABLE, $hashes) || in_array(self::CAT_EXCHANGE_MATERIAL, $hashes)) {
            return ['category' => 'consumables', 'subcategory' => null];
        }

        // Quêtes / primes
        if (in_array(self::CAT_BOUNTY, $hashes) || in_array(self::CAT_QUEST_STEP, $hashes)) {
            return ['category' => 'quests', 'subcategory' => null];
        }

        // Fallback
        return ['category' => 'misc', 'subcategory' => null];
    }
}
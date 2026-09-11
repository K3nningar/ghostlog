<?php

enum DestinyItemType: int
{
    case Weapon = 3;
    case Armor = 2;
    case Ship = 21;
    case Sparrow = 22;
    case Emblem = 19;
    case Shader = 20;
    case Emote = 23; // approximatif selon version manifest
    case Consumable = 9;
    case Material = 10;
    case Bounty = 26;
    case QuestStep = 16;
    case LoreBook = 8;

    public static function toCategorySlug(int $itemType): string
    {
        return match (self::tryFrom($itemType)) {
            self::Weapon => 'weapons',
            self::Armor => 'armor',
            self::Ship => 'ships',
            self::Sparrow => 'sparrows',
            self::Emblem => 'emblems',
            self::Shader => 'shaders',
            self::Emote => 'emotes',
            self::Consumable => 'consumables',
            self::Material => 'materials',
            self::Bounty => 'bounties',
            self::LoreBook => 'lore',
            default => 'uncategorized',
        };
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $fillable = [
        'hash', 'locale', 'name', 'description', 'icon_url',
        'item_type', 'item_type_name', 'item_sub_type', 'class_type',
        'tier_type', 'tier_type_name', 'bucket_type_hash',
        'category_hashes', 'category_slug', 'subcategory_slug',
        'icon_downloaded', 'archive_icon_path', 'archive_icon_path_secondary', 'raw_json',
    ];

    protected $casts = [
        'category_hashes' => 'array',
        'raw_json' => 'array',
        'icon_downloaded' => 'boolean',
    ];

    protected $appends = ['display_icon'];

    public function getRouteKeyName(): string
    {
        return 'hash';
    }

    /**
     * Accès à n'importe quel champ brut du manifest, jamais perdu.
     * Ex: $item->raw('stats.statGroupHash')
     */
    public function raw(?string $dotPath = null)
    {
        if (is_null($dotPath)) {
            return $this->raw_json;
        }

        return data_get($this->raw_json, $dotPath);
    }

    /**
     * Icône à afficher sur le site : priorité à la version locale archivée
     * (offline-proof), sinon fallback sur l'URL Bungie complète stockée en DB.
     */
    protected function displayIcon(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->icon_downloaded && $this->archive_icon_path) {
                    return asset($this->archive_icon_path);
                }

                return $this->icon_url;
            },
        );
    }
}
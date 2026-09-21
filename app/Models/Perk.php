<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Perk extends Model
{
    protected $fillable = [
        'hash', 'locale', 'name', 'description', 'icon_url',
        'icon_downloaded', 'archive_icon_path', 'is_displayable',
        'sandbox_perk_hashes', 'raw_json',
    ];

    protected $casts = [
        'raw_json' => 'array',
        'sandbox_perk_hashes' => 'array',
        'icon_downloaded' => 'boolean',
        'is_displayable' => 'boolean',
    ];

    protected $appends = ['display_icon'];

    public function getRouteKeyName(): string
    {
        return 'hash';
    }

    public function items(): BelongsToMany
    {
        return $this->belongsToMany(Item::class, 'item_perk')
            ->withPivot(['node_index', 'column', 'row', 'exclusive_group_id', 'is_default', 'grid_level_required', 'sort_order', 'meta'])
            ->withTimestamps();
    }

    public function raw(?string $dotPath = null)
    {
        if (is_null($dotPath)) {
            return $this->raw_json;
        }

        return data_get($this->raw_json, $dotPath);
    }

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
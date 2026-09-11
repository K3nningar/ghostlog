<?php

class ManifestVersion extends Model
{
    protected $fillable = ['version', 'locale', 'sqlite_path', 'fetched_at'];
    protected $casts = ['fetched_at' => 'datetime'];

    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }
}
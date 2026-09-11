<?php

class Category extends Model
{
    protected $fillable = ['slug', 'name', 'parent_id', 'sort_order'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function items(): BelongsToMany
    {
        return $this->belongsToMany(Item::class, 'item_category');
    }
}
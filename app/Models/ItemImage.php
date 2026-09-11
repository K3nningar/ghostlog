<?php

class ItemImage extends Model
{
    protected $fillable = ['item_id', 'type', 'category', 'filename', 'public_path'];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function url(): string
    {
        return asset($this->public_path);
    }
}
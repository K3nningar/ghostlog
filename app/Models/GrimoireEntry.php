<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GrimoireEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'entry_key',
        'locale',
        'category',
        'name',
        'description',
        'content',
        'image_path',
    ];
}

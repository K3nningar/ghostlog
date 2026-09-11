<?php

namespace App\Http\Controllers;

use App\Models\Item;

class ItemLocaleController extends Controller
{
    public function index(string $hash)
    {
        return Item::where('hash', $hash)
            ->get(['locale', 'name', 'description']);
    }
}
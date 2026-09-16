<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['slug' => 'weapons', 'name' => 'Armes', 'sort_order' => 1],
            ['slug' => 'armor', 'name' => 'Armures', 'sort_order' => 2],
            ['slug' => 'ships', 'name' => 'Vaisseaux', 'sort_order' => 3],
            ['slug' => 'sparrows', 'name' => 'Passereaux', 'sort_order' => 4],
            ['slug' => 'emblems', 'name' => 'Emblèmes', 'sort_order' => 5],
            ['slug' => 'shaders', 'name' => 'Shaders', 'sort_order' => 6],
            ['slug' => 'emotes', 'name' => 'Emotes', 'sort_order' => 7],
            ['slug' => 'lore', 'name' => 'Objets de lore', 'sort_order' => 8],
            ['slug' => 'consumables', 'name' => 'Consommables', 'sort_order' => 9],
            ['slug' => 'materials', 'name' => 'Matériaux', 'sort_order' => 10],
            ['slug' => 'bounties', 'name' => 'Contrats', 'sort_order' => 11],
            ['slug' => 'uncategorized', 'name' => 'Non catégorisé', 'sort_order' => 99],
        ];

        foreach ($categories as $cat) {
            Category::updateOrCreate(['slug' => $cat['slug']], $cat);
        }
    }
}
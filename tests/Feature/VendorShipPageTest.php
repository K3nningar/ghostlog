<?php

namespace Tests\Feature;

use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorShipPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_open_vendor_ships_page_from_its_url(): void
    {
        $categories = config('vendor_ships.categories');
        $this->assertCount(12, $categories);
        $this->assertCount(121, collect($categories)->flatMap(fn (array $category) => $category['ships'])->unique());

        Item::factory()->create([
            'hash' => '3644912838',
            'locale' => 'fr',
            'category_slug' => 'ships',
            'name' => 'Vaisseau du Roi des Corrompus',
            'archive_icon_path' => 'archive/ships/3644912838.jpg',
            'ingame_image_path' => 'ingame/ships/ship-01.png',
        ]);
        Item::factory()->create([
            'hash' => '3926270234',
            'locale' => 'fr',
            'category_slug' => 'ships',
            'name' => 'Autre vaisseau du Roi des Corrompus',
        ]);
        Item::factory()->create([
            'hash' => '3644912838',
            'locale' => 'en',
            'category_slug' => 'ships',
            'name' => 'Taken King ship',
        ]);
        Item::factory()->create([
            'hash' => '976743008',
            'locale' => 'fr',
            'category_slug' => 'ships',
            'name' => 'Vaisseau d’une autre catégorie vendeur',
        ]);
        Item::factory()->create([
            'hash' => '3644912839',
            'locale' => 'fr',
            'category_slug' => 'weapons',
            'name' => 'Objet non vaisseau',
        ]);

        $this->withoutVite()
            ->get('/collections/ships')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Collections/Ships')
                ->where('vendorHash', '2244880194')
                ->has('categories', 2)
                ->where('categories.0.hash', '2528079380')
                ->where('categories.0.name', 'Le Roi des Corrompus')
                ->has('categories.0.ships', 2)
                ->where('categories.0.ships.0.hash', '3644912838')
                ->where('categories.0.ships.0.archive_icon_path', '/archive/ships/3644912838.jpg')
                ->where('categories.0.ships.0.ingame_image_path', '/ingame/ships/ship-01.png')
                ->where('categories.0.ships.1.hash', '3926270234')
                ->where('categories.1.hash', '2091754884')
                ->where('categories.1.ships.0.hash', '976743008')
            );
    }
}

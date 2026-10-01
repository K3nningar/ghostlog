<?php

namespace Tests\Feature;

use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorSparrowPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_open_vendor_sparrows_page_from_its_url(): void
    {
        $categories = config('vendor_sparrows.categories');
        $this->assertCount(14, $categories);
        $this->assertCount(119, collect($categories)->flatMap(fn (array $category) => $category['sparrows'])->unique());

        Item::factory()->create([
            'hash' => '2092342454',
            'locale' => 'fr',
            'category_slug' => 'sparrows',
            'name' => 'Passereau SRL',
            'archive_icon_path' => '/archive/sparrows/2092342454.jpg',
        ]);
        Item::factory()->create([
            'hash' => '2092342452',
            'locale' => 'fr',
            'category_slug' => 'sparrows',
            'name' => 'Autre passereau SRL',
        ]);
        Item::factory()->create([
            'hash' => '2092342454',
            'locale' => 'en',
            'category_slug' => 'sparrows',
            'name' => 'SRL Sparrow',
        ]);
        Item::factory()->create([
            'hash' => '2227954476',
            'locale' => 'fr',
            'category_slug' => 'sparrows',
            'name' => 'Passereau d’une autre catégorie vendeur',
        ]);
        Item::factory()->create([
            'hash' => '3955061687',
            'locale' => 'fr',
            'category_slug' => 'sparrows',
            'name' => 'Matinal S-99',
            'archive_icon_path' => '/archive/sparrows/3955061687.jpg',
            'ingame_image_path' => 'ingame/sparrows/s99dawnchaser.png',
        ]);
        Item::factory()->create([
            'hash' => '2092342453',
            'locale' => 'fr',
            'category_slug' => 'weapons',
            'name' => 'Objet non passereau',
        ]);

        $this->withoutVite()
            ->get('/collections/sparrows')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Collections/Sparrows')
                ->where('vendorHash', '44395194')
                ->has('categories', 3)
                ->where('categories.0.hash', '271120251')
                ->where('categories.0.name', 'SRL')
                ->has('categories.0.sparrows', 2)
                ->where('categories.0.sparrows.0.hash', '2092342454')
                ->where('categories.0.sparrows.0.archive_icon_path', '/archive/sparrows/2092342454.jpg')
                ->where('categories.0.sparrows.1.hash', '2092342452')
                ->missing('categories.0.sparrows.2')
                ->where('categories.1.hash', '2177236414')
                ->where('categories.1.sparrows.0.hash', '2227954476')
                ->where('categories.2.hash', '933577791')
                ->where('categories.2.sparrows.0.name', 'Matinal S-99')
                ->where('categories.2.sparrows.0.archive_icon_path', '/archive/sparrows/3955061687.jpg')
                ->where('categories.2.sparrows.0.ingame_image_path', '/ingame/sparrows/s99dawnchaser.png')
            );
    }
}

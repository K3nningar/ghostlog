<?php

namespace Tests\Feature;

use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorEmblemPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_open_vendor_emblems_page_from_its_url(): void
    {
        $categories = config('vendor_emblems.categories');
        $this->assertCount(18, $categories);
        $this->assertCount(268, collect($categories)->flatMap(fn (array $category) => $category['emblems'])->unique());

        Item::factory()->create([
            'hash' => '1723894001',
            'locale' => 'fr',
            'category_slug' => 'emblems',
            'name' => 'Emblème Première classe',
            'archive_icon_path' => 'archive/emblems/1723894001.jpg',
        ]);
        Item::factory()->create([
            'hash' => '1600609907',
            'locale' => 'fr',
            'category_slug' => 'emblems',
            'name' => 'Emblème de classe',
        ]);
        Item::factory()->create([
            'hash' => '1723894001',
            'locale' => 'en',
            'category_slug' => 'emblems',
            'name' => 'First Class Emblem',
        ]);
        Item::factory()->create([
            'hash' => '1723894000',
            'locale' => 'fr',
            'category_slug' => 'weapons',
            'name' => 'Élément non emblème',
        ]);

        $response = $this->get('/collections/emblems');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Collections/Emblems')
            ->where('vendorHash', '3301500998')
            ->has('categories', 2)
            ->where('categories.0.hash', '2243720024')
            ->where('categories.0.emblems.0.hash', '1723894001')
            ->where('categories.0.emblems.0.archive_icon_path', 'archive/emblems/1723894001.jpg')
            ->missing('categories.0.emblems.1')
            ->where('categories.1.hash', '2565602980')
            ->where('categories.1.emblems.0.hash', '1600609907')
        );
    }
}

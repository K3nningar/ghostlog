<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminItemManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/admin/items');

        $response->assertRedirect('/login');
    }

    public function test_non_admin_user_is_forbidden(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $response = $this->actingAs($user)->get('/admin/items');

        $response->assertForbidden();
    }

    public function test_admin_user_can_view_items_index(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        Item::factory()->create(['hash' => '12345', 'locale' => 'fr']);

        $response = $this->actingAs($admin)->get('/admin/items');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Items/Index')
            ->has('items.data', 1)
        );
    }

    public function test_admin_can_create_item(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->post('/admin/items', [
            'hash' => '999888777',
            'locale' => 'fr',
            'name' => 'Item de test',
            'description' => 'Cree via l\'admin',
            'category_slug' => 'ghosts',
            'tier_type' => 6,
            'tier_type_name' => 'Exotique',
            'icon_downloaded' => false,
            'raw_json' => json_encode(['displayProperties' => ['name' => 'Item de test']]),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('items', [
            'hash' => '999888777',
            'locale' => 'fr',
            'name' => 'Item de test',
            'category_slug' => 'ghosts',
        ]);
    }

    public function test_create_rejects_duplicate_hash_locale(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        Item::factory()->create(['hash' => '12345', 'locale' => 'fr']);

        $response = $this->actingAs($admin)->post('/admin/items', [
            'hash' => '12345',
            'locale' => 'fr',
            'raw_json' => '{}',
        ]);

        $response->assertSessionHasErrors('hash');
    }

    public function test_admin_can_update_item(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $item = Item::factory()->create([
            'hash' => '12345',
            'locale' => 'fr',
            'name' => 'Ancien nom',
            'tier_type' => 2,
        ]);

        $response = $this->actingAs($admin)->put("/admin/items/{$item->id}", [
            'hash' => '12345',
            'locale' => 'fr',
            'name' => 'Nouveau nom',
            'description' => 'Mis a jour',
            'tier_type' => 6,
            'tier_type_name' => 'Exotique',
            'category_slug' => 'ghosts',
            'subcategory_slug' => 'secret',
            'icon_downloaded' => true,
            'raw_json' => json_encode(['displayProperties' => ['name' => 'Nouveau nom']]),
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('items', [
            'id' => $item->id,
            'name' => 'Nouveau nom',
            'tier_type' => 6,
            'subcategory_slug' => 'secret',
            'icon_downloaded' => true,
        ]);
    }

    public function test_update_rejects_hash_locale_collision_with_other_item(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        Item::factory()->create(['hash' => 'AAAA', 'locale' => 'fr']);
        $item = Item::factory()->create(['hash' => 'BBBB', 'locale' => 'fr']);

        $response = $this->actingAs($admin)->put("/admin/items/{$item->id}", [
            'hash' => 'AAAA',
            'locale' => 'fr',
            'raw_json' => '{}',
        ]);

        $response->assertSessionHasErrors('hash');
    }

    public function test_non_admin_cannot_create_or_update(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $item = Item::factory()->create(['hash' => '12345', 'locale' => 'fr']);

        $this->actingAs($user)->post('/admin/items', [
            'hash' => '777', 'locale' => 'fr',
        ])->assertForbidden();

        $this->actingAs($user)->put("/admin/items/{$item->id}", [
            'hash' => '12345', 'locale' => 'fr', 'name' => 'Pirate',
        ])->assertForbidden();

        $this->assertDatabaseMissing('items', ['hash' => '777']);
        $this->assertDatabaseMissing('items', ['name' => 'Pirate']);
    }
}

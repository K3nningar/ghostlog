<?php

namespace Tests\Feature;

use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminItemUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_ingame_upload_saves_relative_path(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $item = Item::factory()->create([
            'hash' => '4158489060',
            'locale' => 'fr',
            'category_slug' => 'ghosts',
        ]);

        $response = $this->actingAs($admin)->post("/admin/items/{$item->id}", [
            '_method' => 'PUT',
            'hash' => '4158489060',
            'locale' => 'fr',
            'name' => 'Coque de Fer',
            'category_slug' => 'ghosts',
            'ingame_image_path' => '',
            'icon_downloaded' => '0',
            'raw_json' => '{}',
            'ingame_media_file' => UploadedFile::fake()->image('ironshell.png'),
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $item->refresh();

        $this->assertNotNull($item->ingame_image_path, 'Le chemin relatif doit être sauvegardé');
        $this->assertStringStartsWith('ingame/ghosts/', $item->ingame_image_path);
        $this->assertFileExists(public_path($item->ingame_image_path));
    }
}

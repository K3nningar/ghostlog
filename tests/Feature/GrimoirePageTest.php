<?php

namespace Tests\Feature;

use App\Models\GrimoireEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GrimoirePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_and_search_grimoire_entries_in_the_selected_locale(): void
    {
        GrimoireEntry::create([
            'entry_key' => 'the-darkness',
            'locale' => 'fr',
            'category' => 'Histoire',
            'name' => 'Les Ténèbres',
            'description' => 'Une ancienne puissance.',
            'content' => 'Récit conservé dans le grimoire.',
        ]);
        GrimoireEntry::create([
            'entry_key' => 'the-darkness',
            'locale' => 'en',
            'name' => 'The Darkness',
        ]);
        GrimoireEntry::create([
            'entry_key' => 'the-traveler',
            'locale' => 'fr',
            'name' => 'Le Voyageur',
        ]);

        $response = $this->withSession(['locale' => 'fr'])->get('/grimoire?search=Ténèbres');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Grimoire/Index')
            ->where('search', 'Ténèbres')
            ->has('entries.data', 1)
            ->where('entries.data.0.entry_key', 'the-darkness')
            ->where('entries.data.0.content', 'Récit conservé dans le grimoire.')
        );
    }
}

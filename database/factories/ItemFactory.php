<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Item>
 */
class ItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'hash' => (string) fake()->unique()->numberBetween(100000, 999999999),
            'locale' => 'fr',
            'name' => fake()->words(3, true),
            'description' => fake()->sentence(),
            'icon_url' => null,
            'item_type' => 3,
            'item_type_name' => 'Weapon',
            'item_sub_type' => null,
            'class_type' => null,
            'tier_type' => 5,
            'tier_type_name' => 'Légendaire',
            'bucket_type_hash' => null,
            'category_hashes' => [1],
            'category_slug' => 'weapons',
            'subcategory_slug' => null,
            'archive_icon_path' => null,
            'ingame_image_path' => null,
            'icon_downloaded' => false,
            'raw_json' => json_encode([
                'displayProperties' => [
                    'name' => fake()->words(3, true),
                    'description' => fake()->sentence(),
                ],
            ]),
        ];
    }
}

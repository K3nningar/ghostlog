<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('perks', function (Blueprint $table) {
            $table->id();

            // hash = nodeStepHash (identifiant unique de la perk dans DestinyTalentGridDefinition)
            $table->string('hash')->index();
            $table->string('locale', 8)->default('fr');

            $table->string('name')->nullable()->index();
            $table->text('description')->nullable();
            $table->string('icon_url')->nullable();
            $table->string('archive_icon_path')->nullable();
            $table->boolean('icon_downloaded')->default(false);

            $table->boolean('is_displayable')->default(true);

            // sandboxPerkHashes réellement appliqués par ce step (peut être vide, ex: viseurs)
            $table->json('sandbox_perk_hashes')->nullable();

            $table->longText('raw_json');

            $table->timestamps();

            $table->unique(['hash', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('perks');
    }
};
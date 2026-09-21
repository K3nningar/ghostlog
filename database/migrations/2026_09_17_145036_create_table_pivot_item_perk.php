<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_perk', function (Blueprint $table) {
            $table->id();

            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->foreignId('perk_id')->constrained()->cascadeOnDelete();

            // Gardés en clair pour debug / requêtes directes sans jointure
            $table->string('item_hash')->index();
            $table->string('perk_hash')->index();

            $table->unsignedInteger('node_index')->nullable();
            $table->integer('column')->nullable();
            $table->integer('row')->nullable();
            $table->unsignedInteger('exclusive_group_id')->nullable();
            $table->boolean('is_default')->default(false);
            $table->unsignedInteger('grid_level_required')->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('meta')->nullable();

            $table->timestamps();

            $table->unique(['item_id', 'perk_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_perk');
    }
};
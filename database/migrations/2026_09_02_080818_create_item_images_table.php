<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('item_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['icon', 'screenshot']);
            $table->string('category'); // slug catégorie principale (nom du sous-dossier)
            $table->string('filename');
            $table->string('public_path'); // archive/weapons/xxx.jpg
            $table->timestamps();

            $table->unique(['item_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('item_images');
    }
};

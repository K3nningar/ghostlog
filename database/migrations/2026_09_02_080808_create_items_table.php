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
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('hash')->unique()->index();
            $table->string('locale', 8)->default('fr');

            // Champs "utiles" dénormalisés pour recherche/tri rapide (indexés)
            $table->string('name')->nullable()->index();
            $table->text('description')->nullable();
            $table->string('icon_url')->nullable();
            $table->unsignedInteger('item_type')->nullable()->index();
            $table->string('item_type_name')->nullable();
            $table->unsignedInteger('item_sub_type')->nullable()->index();
            $table->unsignedInteger('class_type')->nullable()->index();
            $table->unsignedInteger('tier_type')->nullable()->index();
            $table->string('tier_type_name')->nullable();
            $table->unsignedBigInteger('bucket_type_hash')->nullable();
            $table->json('category_hashes')->nullable();

            // Notre classification (dossier d'archive)
            $table->string('category_slug')->nullable()->index();
            $table->string('subcategory_slug')->nullable()->index();
            $table->string('archive_icon_path')->nullable();
            $table->boolean('icon_downloaded')->default(false);

            // TOUT le reste, sans exception : le JSON complet de la row manifest
            $table->longText('raw_json');

            $table->timestamps();

            $table->unique(['hash', 'locale']);
        });
    }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('items');
    }
};

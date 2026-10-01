<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grimoire_entries', function (Blueprint $table) {
            $table->id();
            $table->string('entry_key')->index();
            $table->string('locale', 8)->default('en');
            $table->string('category')->nullable()->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->longText('content')->nullable();
            $table->string('image_path')->nullable();
            $table->timestamps();

            $table->unique(['entry_key', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grimoire_entries');
    }
};

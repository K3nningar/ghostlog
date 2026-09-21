<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $indexExists = collect(DB::select("SHOW INDEX FROM items WHERE Key_name = 'items_locale_category_index'"))->isNotEmpty();

        if (!$indexExists) {
            Schema::table('items', function (Blueprint $table) {
                $table->index(['locale', 'category_slug'], 'items_locale_category_index');
            });
        }
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropIndex('items_locale_category_index');
        });
    }
};
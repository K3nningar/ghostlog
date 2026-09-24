<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Détection de l'index compatible multi-SGBD (SHOW INDEX est du
        // MySQL pur et casse les migrations sous SQLite, ex. tests).
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            $indexExists = collect(DB::select("SHOW INDEX FROM items WHERE Key_name = 'items_locale_category_index'"))->isNotEmpty();

            if (!$indexExists) {
                Schema::table('items', function (Blueprint $table) {
                    $table->index(['locale', 'category_slug'], 'items_locale_category_index');
                });
            }

            return;
        }

        // SQLite / Postgres : on tente la création et on ignore l'erreur si
        // l'index existe déjà.
        try {
            Schema::table('items', function (Blueprint $table) {
                $table->index(['locale', 'category_slug'], 'items_locale_category_index');
            });
        } catch (\Throwable) {
            // Index déjà présent : rien à faire.
        }
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropIndex('items_locale_category_index');
        });
    }
};
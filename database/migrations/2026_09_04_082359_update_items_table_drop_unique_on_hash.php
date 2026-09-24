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
        // Détection de l'index compatible multi-SGBD (SHOW INDEX est du
        // MySQL pur et casse les migrations sous SQLite, ex. tests).
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            $indexExists = collect(DB::select("SHOW INDEX FROM items WHERE Key_name = 'items_hash_unique'"))->isNotEmpty();

            if ($indexExists) {
                Schema::table('items', function (Blueprint $table) {
                    $table->dropUnique('items_hash_unique');
                });
            }

            return;
        }

        // SQLite / Postgres : on tente le drop et on ignore l'erreur si
        // l'index n'existe pas (comportement équivalent au check MySQL).
        try {
            Schema::table('items', function (Blueprint $table) {
                $table->dropUnique('items_hash_unique');
            });
        } catch (\Throwable) {
            // Index absent : rien à faire.
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};

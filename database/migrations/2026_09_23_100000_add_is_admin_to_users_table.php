<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajoute le flag administrateur aux users (ACL de l'interface d'admin).
     * ingame_image_path est ajouté ici pour la compatibilité avec les
     * environnements qui n'ont pas encore la migration dédiée.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false)->after('password');
        });

        if (!Schema::hasColumn('items', 'ingame_image_path')) {
            Schema::table('items', function (Blueprint $table) {
                $table->string('ingame_image_path')->nullable()->after('archive_icon_path');
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_admin');
        });
    }
};

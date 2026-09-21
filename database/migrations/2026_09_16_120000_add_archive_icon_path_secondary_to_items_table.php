<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('items', 'archive_icon_path_secondary')) {
            Schema::table('items', function (Blueprint $table) {
                $table->string('archive_icon_path_secondary')->nullable();
                // garde les mêmes options (after, etc.) que dans ton fichier original
            });
        }
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn('archive_icon_path_secondary');
        });
    }
};

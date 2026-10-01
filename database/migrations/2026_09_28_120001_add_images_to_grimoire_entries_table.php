<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grimoire_entries', function (Blueprint $table): void {
            $table->string('image_normal')->nullable()->after('image_path');
            $table->string('image_normal_sm')->nullable()->after('image_normal');
            $table->string('image_hr')->nullable()->after('image_normal_sm');
            $table->string('image_hr_sm')->nullable()->after('image_hr');
        });
    }

    public function down(): void
    {
        Schema::table('grimoire_entries', function (Blueprint $table): void {
            $table->dropColumn(['image_normal', 'image_normal_sm', 'image_hr', 'image_hr_sm']);
        });
    }
};

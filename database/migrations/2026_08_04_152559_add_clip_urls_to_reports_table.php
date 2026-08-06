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
        Schema::table('reports', function (Blueprint $table) {
            $table->string('reporter_driver_name')->nullable()->after('reported_driver_name');
            $table->string('clip_good_driver_url')->nullable()->after('video_url');
            $table->string('clip_bad_driver_url')->nullable()->after('clip_good_driver_url');
            $table->string('clip_heli_url')->nullable()->after('clip_bad_driver_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->dropColumn(['reporter_driver_name', 'clip_good_driver_url', 'clip_bad_driver_url', 'clip_heli_url']);
        });
    }
};

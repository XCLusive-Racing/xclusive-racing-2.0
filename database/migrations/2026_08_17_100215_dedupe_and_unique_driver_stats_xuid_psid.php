<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Keep only the newest row per xuid_psid (highest id among the rows sharing
        // that xuid's latest updated_at) — repeated xcl:import:driverstats runs kept
        // inserting instead of updating because xuid_psid had no unique constraint.
        DB::statement('
            DELETE ds FROM driver_stats ds
            INNER JOIN (
                SELECT xuid_psid, MAX(id) as keep_id
                FROM driver_stats ds2
                WHERE updated_at = (SELECT MAX(updated_at) FROM driver_stats ds3 WHERE ds3.xuid_psid = ds2.xuid_psid)
                GROUP BY xuid_psid
            ) keep ON keep.xuid_psid = ds.xuid_psid
            WHERE ds.id != keep.keep_id
        ');

        Schema::table('driver_stats', function (Blueprint $table) {
            $table->dropIndex(['xuid_psid']);
            $table->unique('xuid_psid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('driver_stats', function (Blueprint $table) {
            $table->dropUnique(['xuid_psid']);
            $table->index('xuid_psid');
        });
    }
};

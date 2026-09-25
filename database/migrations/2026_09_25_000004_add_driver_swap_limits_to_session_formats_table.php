<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// League feedback (NLRL, 2026-09): the round's driver-swap time limits belong to
// the race format too, so an endurance format prefills them like its session
// lengths instead of the manager re-typing them per round.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('session_formats', function (Blueprint $table) {
            $table->unsignedInteger('driver_stint_time_mins')->nullable()->after('tyre_set_count');
            $table->unsignedInteger('max_total_driving_time_mins')->nullable()->after('driver_stint_time_mins');
        });
    }

    public function down(): void
    {
        Schema::table('session_formats', function (Blueprint $table) {
            $table->dropColumn(['driver_stint_time_mins', 'max_total_driving_time_mins']);
        });
    }
};

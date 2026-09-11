<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('points_schemes', function (Blueprint $table) {
            $table->id();
            // Historical note: this column was nullable with null meaning "an
            // XCL-provided template" until Phase 2.5 (docs/championships/PLAN.md)
            // gave XCL its own real League row and backfilled every null here to
            // it — see 2026_09_10_000002_create_xcl_league_and_backfill_tenants.
            // Stays `cascadeOnDelete()`: harmless today since no route can hard-delete
            // a League (LeagueController::archive() only flips `status`, and is
            // itself guarded against XCL's system league — see League::system()).
            $table->foreignId('league_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->json('points_map'); // finishing position (1-based) => points
            $table->unsignedInteger('fastest_lap_points')->default(0);
            $table->unsignedInteger('pole_points')->default(0);
            $table->boolean('is_template')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('points_schemes');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Replaces the Phase 2 placeholder shape (points_map: {drivers, teams}) with the
// real data model: a generator type, its config, and an always-resolved,
// always-stored points_table (flat position => points, no drivers/teams split —
// team scoring reuses the same table against team finishing order). Existing
// placeholder rows (only ever seeded templates, no real season data yet) are
// migrated in place rather than dropped, so a manually-created scheme someone
// already has isn't silently lost.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('points_schemes', function (Blueprint $table) {
            $table->string('type')->default('manual')->after('name');
            $table->text('description')->nullable()->after('type');
            $table->json('config')->nullable()->after('description');
            $table->json('points_table')->nullable()->after('config');
            $table->unsignedInteger('leading_lap_points')->default(0)->after('pole_points');
            $table->text('scope_note')->nullable()->after('is_template');
        });

        foreach (DB::table('points_schemes')->get() as $row) {
            $pointsMap = json_decode($row->points_map ?? '[]', true) ?: [];
            $drivers   = $pointsMap['drivers'] ?? [];

            DB::table('points_schemes')->where('id', $row->id)->update([
                'type'         => 'manual',
                'points_table' => json_encode($drivers ?: []),
            ]);
        }

        Schema::table('points_schemes', function (Blueprint $table) {
            $table->json('points_table')->nullable(false)->change();
            $table->dropColumn('points_map');
        });
    }

    public function down(): void
    {
        Schema::table('points_schemes', function (Blueprint $table) {
            $table->json('points_map')->nullable()->after('name');
        });

        foreach (DB::table('points_schemes')->get() as $row) {
            $table = json_decode($row->points_table ?? '[]', true) ?: [];
            DB::table('points_schemes')->where('id', $row->id)->update([
                'points_map' => json_encode(['drivers' => $table, 'teams' => null]),
            ]);
        }

        Schema::table('points_schemes', function (Blueprint $table) {
            $table->dropColumn(['type', 'description', 'config', 'points_table', 'leading_lap_points', 'scope_note']);
        });
    }
};

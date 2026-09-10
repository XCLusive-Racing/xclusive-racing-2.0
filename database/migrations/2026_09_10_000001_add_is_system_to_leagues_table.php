<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leagues', function (Blueprint $table) {
            // Marks XCL's own permanent League row (Phase 2.5 — see
            // docs/championships/PLAN.md), replacing the old "league_id = NULL
            // means XCL's own" convention. Never settable through a form —
            // deliberately absent from League::$fillable — only ever set true by
            // the migration that creates that one row.
            $table->boolean('is_system')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('leagues', function (Blueprint $table) {
            $table->dropColumn('is_system');
        });
    }
};

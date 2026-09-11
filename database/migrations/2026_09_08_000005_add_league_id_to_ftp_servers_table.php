<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ftp_servers', function (Blueprint $table) {
            // Historical note: this column was nullable with null meaning "belongs
            // to XCL itself" until Phase 2.5 (docs/championships/PLAN.md), which
            // gave XCL its own real League row and backfilled every null here to
            // it — see 2026_09_10_000002_create_xcl_league_and_backfill_tenants.
            $table->foreignId('league_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ftp_servers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('league_id');
        });
    }
};

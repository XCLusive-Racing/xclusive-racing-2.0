<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Phase 3 (docs/championships/PLAN.md): the console-vs-PC assumption was
// hardcoded in AccServerConfigService's defaults, not schema — this lets a
// league server declare its game/platform so a future non-ACC generator
// (Phase 3's ServerConfigGenerator extraction) has something to branch on.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ftp_servers', function (Blueprint $table) {
            $table->string('game')->default('acc')->after('league_id');
            $table->string('platform')->nullable()->after('game');
        });

        // Every existing server is, in fact, an ACC console server today
        // (AccServerConfigService hardcodes "Playstation 5 & Xbox Series S/X"
        // into every serverName) — backfill accurately rather than leaving new
        // rows ambiguous.
        DB::table('ftp_servers')->update(['platform' => 'console']);
    }

    public function down(): void
    {
        Schema::table('ftp_servers', function (Blueprint $table) {
            $table->dropColumn(['game', 'platform']);
        });
    }
};

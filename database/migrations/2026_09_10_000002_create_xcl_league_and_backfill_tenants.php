<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Phase 2.5 (docs/championships/PLAN.md): XCL becomes a real League row
// instead of `league_id = NULL` meaning "belongs to XCL." Creates that row
// (idempotently — safe to re-run against a DB that already has it) and
// backfills every `league_id IS NULL` row on the three tenant-scoped tables
// that predate this migration. Raw DB::table() throughout, not Eloquent —
// League/FtpServer/Championship/PointsScheme all carry a TenantScope global
// scope that would otherwise hide rows from this no-auth migration context.
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        $xclLeagueId = DB::table('leagues')->where('is_system', true)->value('id');

        if (!$xclLeagueId) {
            $xclLeagueId = DB::table('leagues')->insertGetId([
                'name'                        => 'XCLusive Racing',
                'slug'                        => 'xclusive-racing',
                'primary_color'               => '#7c3aed',
                'accent_color'                => '#db2777',
                'requires_discord_membership' => false,
                'status'                      => 'active',
                'is_system'                   => true,
                'created_at'                  => $now,
                'updated_at'                  => $now,
            ]);
        }

        DB::table('ftp_servers')->whereNull('league_id')->update(['league_id' => $xclLeagueId]);
        DB::table('championships')->whereNull('league_id')->update(['league_id' => $xclLeagueId]);
        DB::table('points_schemes')->whereNull('league_id')->update(['league_id' => $xclLeagueId]);
    }

    public function down(): void
    {
        $xclLeagueId = DB::table('leagues')->where('is_system', true)->value('id');

        if (!$xclLeagueId) {
            return;
        }

        DB::table('ftp_servers')->where('league_id', $xclLeagueId)->update(['league_id' => null]);
        DB::table('championships')->where('league_id', $xclLeagueId)->update(['league_id' => null]);
        DB::table('points_schemes')->where('league_id', $xclLeagueId)->update(['league_id' => null]);
        DB::table('leagues')->where('id', $xclLeagueId)->delete();
    }
};

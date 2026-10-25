<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// A league's own admin (league feedback 2026-10-25): between manager and steward —
// edits championships, servers and event settings, never creates or deletes. Like
// league_manager/league_steward this is only the global flag that User::syncLeagueRoleFlags()
// keeps in step with league_user.role = 'admin'.
return new class extends Migration
{
    public function up(): void
    {
        if (! DB::table('roles')->where('slug', 'league_admin')->exists()) {
            DB::table('roles')->insert([
                'name' => 'League Admin',
                'slug' => 'league_admin',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('roles')->where('slug', 'league_admin')->delete();
    }
};

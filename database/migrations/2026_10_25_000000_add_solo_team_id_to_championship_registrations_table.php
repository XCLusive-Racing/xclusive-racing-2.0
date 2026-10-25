<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// The team a solo driver races for in a championship with team points (NLRL feedback,
// 2026-10-25): a driver in several teams picks one when signing up, instead of the
// site guessing (User::primaryRacingTeam()). Null = that fallback.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('championship_registrations', function (Blueprint $table) {
            $table->foreignId('solo_team_id')->nullable()->after('racing_team_id')
                ->constrained('racing_teams')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('championship_registrations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('solo_team_id');
        });
    }
};

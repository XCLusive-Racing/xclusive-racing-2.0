<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// League feedback (NLRL, 2026-09): a round can run more than one race. Each race
// session's results are their own rows, told apart by race_number (1 for every
// existing and every single-race result). The new unique key is added before the
// old one is dropped, so race_id's foreign key always has an index to lean on.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('race_results', function (Blueprint $table) {
            $table->unsignedTinyInteger('race_number')->default(1)->after('session_type');
        });

        Schema::table('race_results', function (Blueprint $table) {
            $table->unique(['race_id', 'session_type', 'race_number', 'player_id'], 'race_results_session_race_player_unique');
        });

        Schema::table('race_results', function (Blueprint $table) {
            $table->dropUnique('race_results_session_player_unique');
        });
    }

    public function down(): void
    {
        Schema::table('race_results', function (Blueprint $table) {
            $table->unique(['race_id', 'session_type', 'player_id'], 'race_results_session_player_unique');
        });

        Schema::table('race_results', function (Blueprint $table) {
            $table->dropUnique('race_results_session_race_player_unique');
            $table->dropColumn('race_number');
        });
    }
};

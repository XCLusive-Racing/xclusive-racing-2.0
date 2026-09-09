<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // This migration's timestamp predates create_race_team_entries_table's, so on a
    // fresh install (e.g. the sqlite test database) the table doesn't exist yet when
    // this runs — the create migration adds the column itself in that case instead.
    public function up(): void
    {
        if (!Schema::hasTable('race_team_entries') || Schema::hasColumn('race_team_entries', 'starting_driver_id')) {
            return;
        }

        Schema::table('race_team_entries', function (Blueprint $table) {
            $table->foreignId('starting_driver_id')->nullable()->after('car_model')
                  ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('race_team_entries') || !Schema::hasColumn('race_team_entries', 'starting_driver_id')) {
            return;
        }

        Schema::table('race_team_entries', function (Blueprint $table) {
            $table->dropForeign(['starting_driver_id']);
            $table->dropColumn('starting_driver_id');
        });
    }
};

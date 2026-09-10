<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Refinement request: team registration needs a "per round" vs "whole
// championship" option — in the latter, a team's car number/model/starting
// driver are captured once at championship-registration time so every round
// can get its own RaceTeamEntry/RaceRegistration auto-created from them,
// instead of the team re-registering every round. Same shape as
// race_team_entries so the auto-create just copies these across.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('championship_registrations', function (Blueprint $table) {
            $table->unsignedSmallInteger('car_number')->nullable()->after('racing_team_id');
            $table->string('car_model')->nullable()->after('car_number');
            $table->foreignId('starting_driver_id')->nullable()->after('car_model')
                  ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('championship_registrations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('starting_driver_id');
            $table->dropColumn(['car_number', 'car_model']);
        });
    }
};

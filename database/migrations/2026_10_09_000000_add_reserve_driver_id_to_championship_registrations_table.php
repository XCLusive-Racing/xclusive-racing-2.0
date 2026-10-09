<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// A team car's reserve driver (league feedback, MTSS 2026-10): a third team member
// picked after registration who can swap in for one of the car's drivers in a round
// (RaceController::swapTeamDriver()). Not entered into rounds on its own.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('championship_registrations', function (Blueprint $table) {
            $table->foreignId('reserve_driver_id')->nullable()->after('starting_driver_id')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('championship_registrations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reserve_driver_id');
        });
    }
};

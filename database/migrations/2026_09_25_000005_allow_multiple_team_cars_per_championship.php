<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// League feedback (NLRL, 2026-09): a team can enter more than one car in a
// driver-swap championship (settings.format.max_cars_per_team). A team car's
// registration row belongs to whoever registered it, so a manager entering a
// second car needs a second row for the same (championship, user) — the unique
// index goes; ChampionshipController::register() still stops a solo driver
// registering twice.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('championship_registrations', function (Blueprint $table) {
            // Added first: MySQL keeps the championship_id foreign key on the
            // unique index otherwise, and refuses to drop it.
            $table->index(['championship_id', 'user_id']);
            $table->dropUnique(['championship_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::table('championship_registrations', function (Blueprint $table) {
            $table->unique(['championship_id', 'user_id']);
            $table->dropIndex(['championship_id', 'user_id']);
        });
    }
};

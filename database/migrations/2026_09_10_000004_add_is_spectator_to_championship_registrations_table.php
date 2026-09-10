<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Phase 4 (docs/championships/PLAN.md): a spectator slot pool, separate from
// max_drivers — settings.format.spectator_slots already existed on the schema
// but nothing tracked which registrations were spectators, so this column is
// that. Also adds racing_team_id: for a driver-swaps-enabled championship, a
// registration can represent a team rather than a lone driver, so the team
// doesn't have to re-register per round (see Championship::registerTeam()).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('championship_registrations', function (Blueprint $table) {
            $table->boolean('is_spectator')->default(false)->after('championship_class_id');
            $table->foreignId('racing_team_id')->nullable()->after('is_spectator')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('championship_registrations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('racing_team_id');
            $table->dropColumn('is_spectator');
        });
    }
};

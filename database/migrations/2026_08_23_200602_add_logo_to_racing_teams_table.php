<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // This migration's timestamp predates create_racing_teams_table's, so on a
    // fresh install (e.g. the sqlite test database) the table doesn't exist yet
    // when this runs — the create migration adds the column itself in that case.
    public function up(): void
    {
        if (!Schema::hasTable('racing_teams') || Schema::hasColumn('racing_teams', 'logo')) {
            return;
        }

        Schema::table('racing_teams', function (Blueprint $table) {
            $table->string('logo')->nullable()->after('tag');
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('racing_teams') || !Schema::hasColumn('racing_teams', 'logo')) {
            return;
        }

        Schema::table('racing_teams', function (Blueprint $table) {
            $table->dropColumn('logo');
        });
    }
};

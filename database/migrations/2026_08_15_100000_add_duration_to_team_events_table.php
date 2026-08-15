<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('team_events', function (Blueprint $table) {
            $table->unsignedInteger('duration_minutes')->default(60)->after('starts_at');
            $table->timestamp('ends_at')->nullable()->after('duration_minutes');
        });

        // Backfill ends_at for rows that existed before this column, so the
        // ends_at-based "upcoming" scope doesn't hide them immediately.
        DB::table('team_events')->whereNull('ends_at')->update([
            'ends_at' => DB::raw('DATE_ADD(starts_at, INTERVAL duration_minutes MINUTE)'),
        ]);
    }

    public function down(): void
    {
        Schema::table('team_events', function (Blueprint $table) {
            $table->dropColumn(['duration_minutes', 'ends_at']);
        });
    }
};

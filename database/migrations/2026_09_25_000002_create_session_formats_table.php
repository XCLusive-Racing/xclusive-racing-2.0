<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// League feedback (NLRL, 2026-09): reusable per-league race formats (like points
// schemes) picked round by round, so rounds can differ in session lengths and in
// how many races they run. A round keeps its own copy of the values (the format
// only prefills them), plus race_durations for a multi-race round.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('session_formats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('league_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('description', 255)->nullable();
            $table->unsignedSmallInteger('practice_duration')->nullable();
            $table->unsignedSmallInteger('qualifying_duration')->nullable();
            $table->json('race_durations');
            $table->unsignedTinyInteger('pitstop_count')->default(0);
            $table->boolean('fixed_stop_time')->default(false);
            $table->unsignedTinyInteger('tyre_set_count')->nullable();
            $table->timestamps();
        });

        Schema::table('races', function (Blueprint $table) {
            $table->foreignId('session_format_id')->nullable()->after('event_format_id')
                ->constrained('session_formats')->nullOnDelete();
            // Every race's length in minutes when a round runs more than one race;
            // null for the usual single race (race_duration alone).
            $table->json('race_durations')->nullable()->after('race_duration');
        });
    }

    public function down(): void
    {
        Schema::table('races', function (Blueprint $table) {
            $table->dropConstrainedForeignId('session_format_id');
            $table->dropColumn('race_durations');
        });

        Schema::dropIfExists('session_formats');
    }
};

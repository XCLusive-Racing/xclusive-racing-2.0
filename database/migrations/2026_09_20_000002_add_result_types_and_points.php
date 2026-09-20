<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('results', function (Blueprint $table) {
            // race = a single race result, standings = live championship standings,
            // final = a championship's final result.
            $table->string('type', 20)->default('race')->after('category');
            $table->string('round_label')->nullable()->after('title'); // e.g. "After round 4 of 8"
        });

        Schema::table('result_races', function (Blueprint $table) {
            // Standings/final results aren't tied to a single track.
            $table->string('track')->nullable()->change();
        });

        Schema::table('result_race_positions', function (Blueprint $table) {
            $table->string('points', 20)->nullable()->after('position');
        });
    }

    public function down(): void
    {
        Schema::table('result_race_positions', function (Blueprint $table) {
            $table->dropColumn('points');
        });

        Schema::table('result_races', function (Blueprint $table) {
            $table->string('track')->nullable(false)->change();
        });

        Schema::table('results', function (Blueprint $table) {
            $table->dropColumn(['type', 'round_label']);
        });
    }
};

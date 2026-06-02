<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('race_results', function (Blueprint $table) {
            $table->string('race_title')->nullable()->after('race_id');
            $table->string('race_track')->nullable()->after('race_title');
            $table->string('race_game', 20)->nullable()->after('race_track');
            $table->timestamp('race_scheduled_at')->nullable()->after('race_game');
        });

        // Backfill from races table (SQLite & MySQL compatible)
        DB::statement('
            UPDATE race_results SET
                race_title        = (SELECT title        FROM races WHERE id = race_results.race_id),
                race_track        = (SELECT track        FROM races WHERE id = race_results.race_id),
                race_game         = (SELECT game         FROM races WHERE id = race_results.race_id),
                race_scheduled_at = (SELECT scheduled_at FROM races WHERE id = race_results.race_id)
            WHERE race_id IS NOT NULL
        ');

        Schema::table('race_results', function (Blueprint $table) {
            $table->dropForeign(['race_id']);
            $table->unsignedBigInteger('race_id')->nullable()->change();
            $table->foreign('race_id')->references('id')->on('races')->nullOnDelete();
        });
    }

    public function down(): void
    {
        DB::table('race_results')->whereNull('race_id')->delete();

        $foreignKeys = collect(Schema::getForeignKeys('race_results'))->pluck('name')->toArray();

        Schema::table('race_results', function (Blueprint $table) use ($foreignKeys) {
            if (in_array('race_results_race_id_foreign', $foreignKeys)) {
                $table->dropForeign(['race_id']);
            }
            $table->unsignedBigInteger('race_id')->nullable(false)->change();
            $table->foreign('race_id')->references('id')->on('races')->cascadeOnDelete();
            $table->dropColumn(['race_title', 'race_track', 'race_game', 'race_scheduled_at']);
        });
    }
};
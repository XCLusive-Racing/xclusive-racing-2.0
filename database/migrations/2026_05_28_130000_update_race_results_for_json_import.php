<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite (used by the test suite) has no MySQL-style raw ALTER syntax —
        // achieve the same schema change through the portable Schema builder instead.
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            Schema::table('race_results', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->unsignedBigInteger('user_id')->nullable()->change();
                $table->string('session_type', 10)->default('race')->after('race_id');
                $table->string('player_id', 60)->nullable()->after('user_id');
                $table->string('driver_name', 100)->nullable()->after('player_id');
                $table->unsignedSmallInteger('car_number')->nullable()->after('driver_name');
                $table->unsignedInteger('best_lap')->nullable();
                $table->unsignedSmallInteger('lap_count')->nullable();
                $table->unsignedBigInteger('total_time')->nullable();
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                $table->unique(['race_id', 'session_type', 'player_id'], 'race_results_session_player_unique');
            });

            return;
        }

        // Drop user_id FK so we can make the column nullable
        DB::statement('ALTER TABLE race_results DROP FOREIGN KEY race_results_user_id_foreign');

        DB::statement('
            ALTER TABLE race_results
                MODIFY user_id BIGINT UNSIGNED NULL,
                ADD COLUMN session_type VARCHAR(10) NOT NULL DEFAULT \'race\' AFTER race_id,
                ADD COLUMN player_id VARCHAR(60) NULL AFTER user_id,
                ADD COLUMN driver_name VARCHAR(100) NULL AFTER player_id,
                ADD COLUMN car_number SMALLINT UNSIGNED NULL AFTER driver_name,
                ADD COLUMN best_lap INT UNSIGNED NULL,
                ADD COLUMN lap_count SMALLINT UNSIGNED NULL,
                ADD COLUMN total_time BIGINT UNSIGNED NULL,
                ADD CONSTRAINT race_results_user_id_foreign
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                ADD UNIQUE INDEX race_results_session_player_unique (race_id, session_type, player_id)
        ');
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            DB::table('race_results')->whereNull('user_id')->delete();

            Schema::table('race_results', function (Blueprint $table) {
                $table->dropUnique('race_results_session_player_unique');
                $table->dropForeign(['user_id']);
                $table->unsignedBigInteger('user_id')->nullable(false)->change();
                $table->dropColumn(['session_type', 'player_id', 'driver_name', 'car_number', 'best_lap', 'lap_count', 'total_time']);
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            });

            return;
        }

        try {
            DB::statement('ALTER TABLE race_results DROP FOREIGN KEY race_results_user_id_foreign');
        } catch (\Exception) {}

        try {
            DB::statement('ALTER TABLE race_results DROP INDEX race_results_session_player_unique');
        } catch (\Exception) {}

        DB::table('race_results')->whereNull('user_id')->delete();

        DB::statement('
            ALTER TABLE race_results
                MODIFY user_id BIGINT UNSIGNED NOT NULL,
                DROP COLUMN session_type,
                DROP COLUMN player_id,
                DROP COLUMN driver_name,
                DROP COLUMN car_number,
                DROP COLUMN best_lap,
                DROP COLUMN lap_count,
                DROP COLUMN total_time,
                ADD CONSTRAINT race_results_user_id_foreign
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ');
    }
};
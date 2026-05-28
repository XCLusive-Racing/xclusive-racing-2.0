<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
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
        DB::statement('ALTER TABLE race_results DROP FOREIGN KEY race_results_user_id_foreign');
        DB::statement('ALTER TABLE race_results DROP INDEX race_results_session_player_unique');

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
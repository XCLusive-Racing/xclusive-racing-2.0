<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// The plain unique(race_id, car_number) index predates soft deletes on this table —
// a soft-deleted entry (e.g. after unregisterTeam()) still occupies its car number at
// the DB level, so re-registering the same number for the same race fails with a
// duplicate-key error even though the app considers that number free again.
// Fix: index a generated column that's NULL for soft-deleted rows instead — MySQL
// treats each NULL as distinct in a unique index, so soft-deleted rows never collide
// with each other or with the currently-active row for that (race, car number).
return new class extends Migration
{
    public function up(): void
    {
        // The old unique(race_id, car_number) index is also what currently satisfies the
        // race_id foreign key (no separate plain index on race_id exists) — MySQL refuses
        // to drop it while it's the only index covering that FK, so the new composite
        // index (which also starts with race_id) has to exist first.
        DB::statement('ALTER TABLE race_team_entries ADD COLUMN car_number_active SMALLINT UNSIGNED GENERATED ALWAYS AS (IF(deleted_at IS NULL, car_number, NULL)) VIRTUAL');

        Schema::table('race_team_entries', function (Blueprint $table) {
            $table->unique(['race_id', 'car_number_active']);
            $table->dropUnique(['race_id', 'car_number']);
        });
    }

    public function down(): void
    {
        Schema::table('race_team_entries', function (Blueprint $table) {
            $table->unique(['race_id', 'car_number']);
            $table->dropUnique(['race_id', 'car_number_active']);
            $table->dropColumn('car_number_active');
        });
    }
};

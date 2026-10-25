<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Championship review 2026-10-25: rounds only copied car_class / max_drivers /
// is_multiclass when they were created, and the wizard's Rounds step comes before
// Format — so most rounds pushed a FreeForAll server with 30 slots, and a
// multiclass championship's rounds a single car class. One-off fix of the rounds
// still to run; from now on the Format step keeps them in step
// (Championship::syncRoundFields()). Finished rounds keep what they ran with.
return new class extends Migration
{
    public function up(): void
    {
        foreach (DB::table('championships')->whereNull('deleted_at')->get() as $championship) {
            DB::table('races')
                ->where('championship_id', $championship->id)
                ->where('status', '!=', 'finished')
                ->update([
                    'car_class' => $championship->is_multiclass ? null : $championship->car_class,
                    'max_drivers' => $championship->max_drivers,
                    'is_multiclass' => (bool) $championship->is_multiclass,
                ]);
        }
    }

    public function down(): void
    {
        // A data fix: the old values were wrong, there's nothing to restore.
    }
};

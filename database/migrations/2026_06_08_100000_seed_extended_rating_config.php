<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $defaults = [
            // ELO & Effective Rating
            'ELO_SCALE'              => 800,
            'WIN_PCT_SCALE'          => 600,
            'EFF_RATING_ALPHA'       => 0.55,
            'EFF_RATING_MIN'         => 500,
            'EFF_RATING_MAX_INPUT'   => 10000,
            'EFF_RATING_MAX_OUTPUT'  => 2200,

            // Prestige Penalty
            'PRESTIGE_GAMMA'         => 1.5,
            'PRESTIGE_MIN_FACTOR'    => 0.05,

            // Rating ceiling
            'RATING_SOFT_MAX'        => 12000,

            // License thresholds (minimum rating per rank)
            'RANK_BRONZE_MIN'        => 2000,
            'RANK_SILVER_MIN'        => 3500,
            'RANK_GOLD_MIN'          => 5000,
            'RANK_PLATINUM_MIN'      => 6500,
            'RANK_ALIEN_MIN'         => 8000,
            'RANK_LEGEND_MIN'        => 10000,

            // Race duration multipliers
            'MULT_15'   => 0.6,
            'MULT_20'   => 0.8,
            'MULT_30'   => 1.0,
            'MULT_30P'  => 1.2,
            'MULT_30PP' => 1.3,
            'MULT_45'   => 1.5,
            'MULT_45P'  => 1.6,
            'MULT_60'   => 2.0,
            'MULT_60P'  => 2.1,
            'MULT_90'   => 2.5,
            'MULT_90P'  => 2.6,
        ];

        foreach ($defaults as $key => $value) {
            DB::table('rating_configs')->insertOrIgnore([
                'key'        => $key,
                'value'      => $value,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        $keys = [
            'ELO_SCALE','WIN_PCT_SCALE','EFF_RATING_ALPHA','EFF_RATING_MIN',
            'EFF_RATING_MAX_INPUT','EFF_RATING_MAX_OUTPUT',
            'PRESTIGE_GAMMA','PRESTIGE_MIN_FACTOR','RATING_SOFT_MAX',
            'RANK_BRONZE_MIN','RANK_SILVER_MIN','RANK_GOLD_MIN',
            'RANK_PLATINUM_MIN','RANK_ALIEN_MIN','RANK_LEGEND_MIN',
            'MULT_15','MULT_20','MULT_30','MULT_30P','MULT_30PP',
            'MULT_45','MULT_45P','MULT_60','MULT_60P','MULT_90','MULT_90P',
        ];
        DB::table('rating_configs')->whereIn('key', $keys)->delete();
    }
};
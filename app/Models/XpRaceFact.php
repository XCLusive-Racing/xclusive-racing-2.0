<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

// What the stored raw results file says about one race result (App\Xp\RaceFacts).
class XpRaceFact extends Model
{
    protected $fillable = ['race_result_id', 'penalty_free', 'all_laps_valid'];

    protected function casts(): array
    {
        return ['penalty_free' => 'boolean', 'all_laps_valid' => 'boolean'];
    }
}

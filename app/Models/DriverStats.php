<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'driver_id', 'xuid_psid',
    'wins', 'seconds', 'thirds', 'total_races', 'podiums', 'top5s', 'top10s',
    'fastest_race_laps', 'fastest_qualifying_laps',
    'total_spectated', 'total_race_positions', 'total_qualy_positions',
    'positions_gained', 'total_races_2024',
    'penalty_pit_speeding', 'penalty_wrong_way', 'penalty_cutting', 'penalty_trolling',
    'penalty_start_speeding', 'penalty_out_of_start_pos', 'penalty_stop_go_30',
    'penalty_disqualified', 'penalty_drive_through', 'penalty_post_race_time', 'penalty_other',
])]
class DriverStats extends Model
{
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }
}
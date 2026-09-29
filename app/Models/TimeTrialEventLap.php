<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// A driver's best valid lap in one car in one hourly result file of a weekly event.
#[Fillable([
    'time_trial_event_id', 'user_id', 'platform_identifier', 'platform', 'driver_name', 'car_id',
    'car_class', 'lap_time_ms', 'sector1_ms', 'sector2_ms', 'sector3_ms', 'result_file', 'recorded_at',
])]
class TimeTrialEventLap extends Model
{
    protected function casts(): array
    {
        return ['recorded_at' => 'datetime'];
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(TimeTrialEvent::class, 'time_trial_event_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function car(): BelongsTo
    {
        return $this->belongsTo(TimeTrialCar::class, 'car_id');
    }
}

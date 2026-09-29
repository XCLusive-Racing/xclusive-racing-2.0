<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'time_trial_event_id', 'user_id', 'platform_identifier', 'driver_name', 'position', 'car_id',
    'lap_time_ms', 'rating_change', 'rating_before',
])]
class TimeTrialEventResult extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function car(): BelongsTo
    {
        return $this->belongsTo(TimeTrialCar::class, 'car_id');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['driver_id', 'xuid_psid', 'track', 'best_race_lap', 'best_qualifying_lap'])]
class DriverTrackTime extends Model
{
    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }
}
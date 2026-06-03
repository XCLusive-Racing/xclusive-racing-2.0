<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'driver_id', 'xuid_psid', 'driver_name', 'car_id', 'car_name', 'car_model',
    'best_lap', 'laps_driven', 'xcl_rating_at_time', 'rating_change', 'new_xcl_rating',
])]
class Hotlap extends Model
{
    protected function casts(): array
    {
        return [
            'xcl_rating_at_time' => 'decimal:4',
            'rating_change'      => 'decimal:4',
            'new_xcl_rating'     => 'decimal:4',
        ];
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(Driver::class);
    }
}
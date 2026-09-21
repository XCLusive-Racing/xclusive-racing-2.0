<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResultRacePosition extends Model
{
    protected $fillable = ['result_race_id', 'esports_driver_id', 'position', 'points', 'car', 'car_number', 'sort_order'];

    public function race(): BelongsTo
    {
        return $this->belongsTo(ResultRace::class, 'result_race_id');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(EsportsDriver::class, 'esports_driver_id');
    }
}

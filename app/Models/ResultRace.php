<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResultRace extends Model
{
    protected $fillable = ['result_id', 'track', 'car_class', 'race_date', 'sort_order'];

    protected $casts = ['race_date' => 'date'];

    public function result(): BelongsTo
    {
        return $this->belongsTo(Result::class);
    }

    public function positions(): HasMany
    {
        return $this->hasMany(ResultRacePosition::class)->orderBy('sort_order');
    }
}

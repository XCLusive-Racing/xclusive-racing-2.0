<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChampionshipClass extends Model
{
    protected $fillable = [
        'championship_id', 'name', 'color', 'car_class',
        'max_drivers', 'sr_requirement', 'min_rating', 'sort_order',
    ];

    public function championship(): BelongsTo
    {
        return $this->belongsTo(Championship::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(ChampionshipRegistration::class);
    }
}

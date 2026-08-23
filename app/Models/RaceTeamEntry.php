<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RaceTeamEntry extends Model
{
    protected $fillable = ['race_id', 'racing_team_id', 'car_number', 'car_model'];

    public function race(): BelongsTo
    {
        return $this->belongsTo(Race::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(RacingTeam::class, 'racing_team_id');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(RaceRegistration::class, 'team_entry_id');
    }
}
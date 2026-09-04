<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RaceTeamEntry extends Model
{
    use SoftDeletes;
    protected $fillable = ['race_id', 'racing_team_id', 'car_number', 'car_model', 'starting_driver_id'];

    public function race(): BelongsTo
    {
        return $this->belongsTo(Race::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(RacingTeam::class, 'racing_team_id');
    }

    public function startingDriver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'starting_driver_id');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(RaceRegistration::class, 'team_entry_id');
    }
}
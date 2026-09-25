<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChampionshipRegistration extends Model
{
    protected $fillable = [
        'championship_id', 'user_id', 'championship_class_id', 'is_spectator', 'racing_team_id',
        // Only set when the championship's team_registration_scope is
        // "championship" — a team's car number/model/starting driver, captured
        // once so every round can auto-generate its own RaceTeamEntry from them.
        'car_number', 'car_model', 'starting_driver_id',
        // The team members picked to drive this car. In "championship" scope they're
        // the ones entered into every round; in "per_round" scope they're the
        // pre-selected line-up on each round's team sign-up. Null = whole team.
        'driver_ids',
    ];

    protected function casts(): array
    {
        return ['is_spectator' => 'boolean', 'driver_ids' => 'array'];
    }

    // The picked line-up, falling back to every member of the team (owner included)
    // for registrations made before drivers could be picked.
    public function driverIds(): array
    {
        if ($this->driver_ids) {
            return array_map('intval', $this->driver_ids);
        }

        $team = $this->racingTeam;

        return $team
            ? $team->members->pluck('id')->push($team->owner_id)->unique()->values()->all()
            : [];
    }

    public function championship(): BelongsTo
    {
        return $this->belongsTo(Championship::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function championshipClass(): BelongsTo
    {
        return $this->belongsTo(ChampionshipClass::class);
    }

    public function racingTeam(): BelongsTo
    {
        return $this->belongsTo(RacingTeam::class);
    }

    public function startingDriver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'starting_driver_id');
    }
}

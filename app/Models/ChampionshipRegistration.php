<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChampionshipRegistration extends Model
{
    protected $fillable = ['championship_id', 'user_id', 'championship_class_id', 'is_spectator', 'racing_team_id'];

    protected function casts(): array
    {
        return ['is_spectator' => 'boolean'];
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
}

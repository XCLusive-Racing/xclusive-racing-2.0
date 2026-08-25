<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RacingTeamInvitation extends Model
{
    protected $fillable = ['racing_team_id', 'user_id'];

    public function team(): BelongsTo
    {
        return $this->belongsTo(RacingTeam::class, 'racing_team_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// A round type a league added itself from the round form, next to the built-in
// Race::ROUND_TYPES — offered in that league's round type dropdown from then on.
class LeagueRoundType extends Model
{
    protected $fillable = ['league_id', 'name'];

    public function league(): BelongsTo
    {
        return $this->belongsTo(League::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class RacingTeam extends Model
{
    protected $fillable = ['name', 'tag', 'owner_id'];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'racing_team_members');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(RaceTeamEntry::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(RacingTeamInvitation::class);
    }

    public function hasMember(User $user): bool
    {
        return $this->owner_id === $user->id || $this->members->contains($user);
    }
}
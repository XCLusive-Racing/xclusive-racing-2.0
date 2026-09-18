<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class RacingTeam extends Model
{
    protected $fillable = ['name', 'tag', 'logo', 'owner_id'];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'racing_team_members')->withPivot('role');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(RaceTeamEntry::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(RacingTeamInvitation::class);
    }

    public function logoUrl(): ?string
    {
        if (! $this->logo) {
            return null;
        }
        if (str_starts_with($this->logo, 'http')) {
            return $this->logo;
        }

        return Storage::disk('media')->url($this->logo);
    }

    public function hasMember(User $user): bool
    {
        return $this->owner_id === $user->id || $this->members->contains($user);
    }

    // A member promoted to 'manager' -- distinct from the owner, but trusted the same
    // way for entering the team into an event/championship (see canManage()). Roster,
    // logo, and delete-team actions stay owner-only.
    public function isManager(User $user): bool
    {
        $member = $this->members->firstWhere('id', $user->id);

        return $member?->pivot->role === 'manager';
    }

    public function canManage(User $user): bool
    {
        return $this->owner_id === $user->id || $this->isManager($user);
    }
}

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

    // Seat limit: the owner's membership plan sets how many drivers the team can have,
    // owner included (config/memberships.php — 6 without a plan). null = unlimited. A team
    // that's already over its limit (a plan ran out) keeps everyone, it just can't add more.
    public function seatLimit(): ?int
    {
        return $this->owner->teamSeatLimit();
    }

    // The owner isn't a member row of their own team.
    public function driverCount(): int
    {
        return 1 + $this->members()->where('users.id', '!=', $this->owner_id)->count();
    }

    // Seats free for new invites — pending invitations already hold a seat each.
    public function freeSeats(): ?int
    {
        $limit = $this->seatLimit();

        return $limit === null ? null : max(0, $limit - $this->driverCount() - $this->invitations()->count());
    }
}

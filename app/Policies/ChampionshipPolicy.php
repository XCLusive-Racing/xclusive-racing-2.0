<?php

namespace App\Policies;

use App\Models\Championship;
use App\Models\League;
use App\Models\User;

class ChampionshipPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManage() || $user->isLeagueManager() || $user->isLeagueSteward();
    }

    public function view(User $user, Championship $championship): bool
    {
        return $user->canManage()
            || $user->managesLeague($championship->league)
            || $user->stewardsLeague($championship->league);
    }

    public function create(User $user, League $league): bool
    {
        return $user->canManage() || $user->managesLeague($league);
    }

    public function update(User $user, Championship $championship): bool
    {
        return $user->canManage() || $user->managesLeague($championship->league);
    }

    public function delete(User $user, Championship $championship): bool
    {
        return $user->canManage() || $user->managesLeague($championship->league);
    }

    // A league manager may raise the request (this is the same gate as "update").
    public function requestRating(User $user, Championship $championship): bool
    {
        return $this->update($user, $championship);
    }

    // Same gate as update() — user-directed 2026-09 (was XCL-staff-only; a
    // league manager can now enable/disable XCL Rating for their own
    // championship directly from the Basics step, no XCL admin approval step
    // needed any more).
    public function approveRating(User $user, Championship $championship): bool
    {
        return $this->update($user, $championship);
    }
}

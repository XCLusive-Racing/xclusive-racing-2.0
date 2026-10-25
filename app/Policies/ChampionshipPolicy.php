<?php

namespace App\Policies;

use App\Models\Championship;
use App\Models\League;
use App\Models\User;

class ChampionshipPolicy
{
    public function viewAny(User $user): bool
    {
        // The league_* flags are bare role-flag checks here (no specific League to
        // test managesLeague()/editsLeague()/stewardsLeague() against yet — this
        // only gates whether the picker/index is reachable at all), so
        // Championship Manager needs its own explicit check rather than routing
        // through managesLeague() like every other method in this policy does.
        return $user->canManage() || $user->isChampionshipManager() || $user->hasAnyRole(User::LEAGUE_ROLE_FLAGS);
    }

    public function view(User $user, Championship $championship): bool
    {
        return $user->canManage()
            || $user->editsLeague($championship->league)
            || $user->stewardsLeague($championship->league);
    }

    // Creating and deleting is the league manager's alone; a league admin only
    // edits what's there (update()).
    public function create(User $user, League $league): bool
    {
        return $user->canManage() || $user->managesLeague($league);
    }

    public function update(User $user, Championship $championship): bool
    {
        return $user->canManage() || $user->editsLeague($championship->league);
    }

    public function delete(User $user, Championship $championship): bool
    {
        return $user->canManage() || $user->managesLeague($championship->league);
    }

    // Same gate as update() — user-directed 2026-09 (was XCL-staff-only; a
    // league manager (or admin) can now enable/disable XCL Rating for their own
    // championship directly from the Basics step, no XCL admin approval step
    // needed any more).
    public function approveRating(User $user, Championship $championship): bool
    {
        return $this->update($user, $championship);
    }
}

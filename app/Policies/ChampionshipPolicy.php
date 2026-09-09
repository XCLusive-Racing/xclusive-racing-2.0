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

    // Only XCL staff — xcl_rating_enabled is never a League Manager's call, because
    // the rating only means something because XCL controls what feeds it.
    public function approveRating(User $user, Championship $championship): bool
    {
        return $user->canManage();
    }
}

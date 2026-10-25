<?php

namespace App\Policies;

use App\Models\PointsScheme;
use App\Models\User;

// Templates (league_id null) are never editable or deletable by anyone through
// this policy, XCL admin included — the only way a template's content reaches
// a league is copyFor(), never a direct edit. An owned scheme is editable only
// by an XCL admin or that scheme's own league's manager or admin (deletable by
// the manager only), and only that league's — never another league's, enforced
// here on top of the tenant scope so a canManage()-bypassing admin request
// still can't cross leagues by a bad id.
class PointsSchemePolicy
{
    public function view(User $user, PointsScheme $scheme): bool
    {
        // Read access is deliberately wide — see PointsSchemeController::browse().
        return $user->canManage() || $user->isChampionshipManager() || $user->hasAnyRole(User::LEAGUE_ROLE_FLAGS);
    }

    public function update(User $user, PointsScheme $scheme): bool
    {
        if ($scheme->is_template) {
            return false;
        }

        return $user->canManage() || ($scheme->league && $user->editsLeague($scheme->league));
    }

    public function delete(User $user, PointsScheme $scheme): bool
    {
        if ($scheme->is_template) {
            return false;
        }

        return $user->canManage() || ($scheme->league && $user->managesLeague($scheme->league));
    }
}

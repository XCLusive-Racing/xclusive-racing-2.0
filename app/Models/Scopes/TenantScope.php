<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $user = auth()->user();

        // Owner/Admin/Event Manager act across every league without restriction.
        // Championship Manager is deliberately NOT included here any more (was
        // until 2026-09): it used to bypass every league's scope regardless of
        // membership, but that meant anyone holding it — even with zero league
        // memberships — saw every league/championship/server/points-scheme on
        // the platform. It's scoped by leagueIds() like everyone else now; a
        // Championship Manager only manages the league(s) they're actually a
        // member of.
        if ($user && $user->canManage()) {
            return;
        }

        $leagueIds = $user ? $user->leagueIds() : collect();
        $column    = $model->qualifyColumn($model->getTenantKeyName());

        // Phase 2.5 (docs/championships/PLAN.md): XCL is a real League row now, not
        // `league_id = NULL` — there is no more universally-visible null tenant.
        // Someone with no league membership at all sees zero league-owned rows,
        // XCL's own included; a read site that must show XCL's own data to everyone
        // regardless of tenant (e.g. RaceController::register() loading the race's
        // FTP server) does so via an explicit ->withoutTenantScope() of its own.
        $builder->whereIn($column, $leagueIds->isNotEmpty() ? $leagueIds->all() : [0]);
    }
}

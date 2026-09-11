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

        // Owner/Admin/Event Manager act across every league without restriction —
        // same for a Championship Manager (a global role, not a per-league
        // membership row, so there's no leagueIds() entry to filter by below).
        if ($user && ($user->canManage() || $user->isChampionshipManager())) {
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

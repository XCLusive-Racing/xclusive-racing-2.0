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
        if ($user && $user->canManage()) {
            return;
        }

        $leagueIds = $user ? $user->leagueIds() : collect();
        $column    = $model->qualifyColumn($model->getTenantKeyName());

        $builder->where(function (Builder $query) use ($column, $leagueIds) {
            // A null tenant key means the row belongs to XCL itself (e.g. an FtpServer
            // XCL owns directly, not any league), so it stays visible to everyone —
            // isolation only needs to bite on rows that actually belong to a league.
            // For models keyed by their own id (League itself), this branch never
            // matches anything, since a primary key is never null.
            $query->whereNull($column);

            if ($leagueIds->isNotEmpty()) {
                $query->orWhereIn($column, $leagueIds);
            }
        });
    }
}

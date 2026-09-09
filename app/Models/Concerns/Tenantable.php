<?php

namespace App\Models\Concerns;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Builder;

// Applied to every model that belongs to a league. Enforces isolation as a global
// query scope rather than in controllers, so a forgotten `where` clause can't leak
// one league's data into another's admin screen.
trait Tenantable
{
    public static function bootTenantable(): void
    {
        static::addGlobalScope(new TenantScope());
    }

    // Explicit bypass for background jobs and console commands that legitimately
    // run without an authenticated user, e.g. League::withoutTenantScope()->get().
    public function scopeWithoutTenantScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope(TenantScope::class);
    }

    // The column that ties a row to a league. Override on a model whose tenant
    // key isn't a plain `league_id` foreign key (the League model itself uses
    // its own `id`).
    public function getTenantKeyName(): string
    {
        return 'league_id';
    }
}

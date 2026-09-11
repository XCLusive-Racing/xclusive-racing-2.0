<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\League;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

// External users are operating inside our admin panel, so every meaningful action
// they (or an XCL admin, acting on their behalf) take needs a trail. Never pass
// secret values (FTP credentials, etc.) in $changes — the log is not a vault.
class AuditLogger
{
    public static function record(?User $actor, Model $auditable, string $action, ?array $changes = null, ?int $leagueId = null): AuditLog
    {
        return AuditLog::create([
            'user_id'        => $actor?->id,
            'league_id'      => $leagueId ?? self::resolveLeagueId($auditable),
            'action'         => $action,
            'auditable_type' => $auditable::class,
            'auditable_id'   => $auditable->getKey(),
            'changes'        => $changes,
        ]);
    }

    private static function resolveLeagueId(Model $auditable): ?int
    {
        if ($auditable instanceof League) {
            return $auditable->id;
        }

        return $auditable->getAttribute('league_id');
    }
}

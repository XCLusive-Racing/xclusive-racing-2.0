<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Guards the /admin/leagues surface: XCL staff (Owner/Admin/Event Manager) reach it
// unconditionally; everyone else needs the League Manager, League Steward or
// Championship Manager role. Which specific league(s) a non-staff user may act on
// is enforced separately by the TenantScope global scope on League/FtpServer, not
// by this middleware (a Championship Manager bypasses that scope entirely, same as
// canManage() — see TenantScope::apply()).
class LeagueAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (!$user || !($user->canManage() || $user->isChampionshipManager() || $user->hasAnyRole(['league_manager', 'league_steward']))) {
            abort(403, 'Access denied.');
        }

        return $next($request);
    }
}

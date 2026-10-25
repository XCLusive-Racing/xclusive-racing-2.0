<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Guards the /admin/leagues surface: XCL staff (Owner/Admin/Event Manager) reach it
// unconditionally; everyone else needs the League Manager, League Admin, League Steward or
// Championship Manager role. Which specific league(s) a non-staff user may act on
// is enforced separately by the TenantScope global scope on League/FtpServer, not
// by this middleware. Only canManage() bypasses that scope; a Championship Manager
// is scoped to the leagues they're a member of like everyone else (since 2026-09,
// see TenantScope::apply()).
class LeagueAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (! $user || ! ($user->canManage() || $user->isChampionshipManager() || $user->hasAnyRole(User::LEAGUE_ROLE_FLAGS))) {
            abort(403, 'Access denied.');
        }

        return $next($request);
    }
}

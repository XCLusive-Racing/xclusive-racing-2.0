<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

// The XP & Achievements admin screens need the XP tables. Until the migrations have run
// they show a notice instead of a database error.
class EnsureXpIsInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! Schema::hasTable('rank_levels')) {
            return response()->view('admin.xp.not-installed', [], 503);
        }

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class UpdateLastSeen
{
    // Throttled to avoid a write on every single request.
    private const THROTTLE_MINUTES = 2;

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && (!$user->last_seen_at || $user->last_seen_at->lt(now()->subMinutes(self::THROTTLE_MINUTES)))) {
            $user->timestamps = false;
            $user->update(['last_seen_at' => now()]);
        }

        return $next($request);
    }
}

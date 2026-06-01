<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordIsSet
{
    public function handle(Request $request, Closure $next): Response
    {
        if (
            $request->user() &&
            $request->user()->must_set_password &&
            !$request->routeIs('password.setup', 'password.setup.store', 'logout')
        ) {
            return redirect()->route('password.setup');
        }

        return $next($request);
    }
}
<?php

use App\Http\Middleware\EnsurePasswordIsSet;
use App\Http\Middleware\HasRole;
use App\Http\Middleware\IsAdmin;
use App\Http\Middleware\IsSuperAdmin;
use App\Http\Middleware\LeagueAccess;
use App\Http\Middleware\UpdateLastSeen;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'admin' => IsAdmin::class,
            'superadmin' => IsSuperAdmin::class,
            'role' => HasRole::class,
            'password.setup' => EnsurePasswordIsSet::class,
            'league.access' => LeagueAccess::class,
        ]);

        // Mollie posts payment updates here without a CSRF token.
        $middleware->validateCsrfTokens(except: ['webhooks/mollie']);

        $middleware->appendToGroup('web', EnsurePasswordIsSet::class);
        $middleware->appendToGroup('web', UpdateLastSeen::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();

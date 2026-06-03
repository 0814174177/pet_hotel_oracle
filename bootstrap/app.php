<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use App\Http\Middleware\PreventBackHistory;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Auth;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web/web.php',
        api: __DIR__ . '/../routes/api/api.php',
        apiPrefix: 'api',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'auth' => Authenticate::class,
            'guest' => RedirectIfAuthenticated::class,
            'no.cache' => PreventBackHistory::class,
            'role' => RoleMiddleware::class,
        ]);

        $middleware->redirectGuestsTo('/login');
        $middleware->redirectUsersTo(function (): string {
            return match (Auth::user()?->role) {
                'ADMIN', 'CEO' => '/ceo/dashboard',
                'MANAGER', 'RECEPTIONIST', 'GROOMER' => '/manager/dashboard',
                default => '/',
            };
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();

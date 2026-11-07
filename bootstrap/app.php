<?php

use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\EnsureUserHasRole;
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
        // En production (Railway), le HTTPS est géré par un proxy : on lui fait
        // confiance pour que Laravel produise des adresses en https (BNF-05).
        $middleware->trustProxies(at: '*');

        // Alias utilisé dans les routes : ->middleware('role:doctor,secretary')
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
        ]);

        // RG-02 : déconnecte un compte désactivé pendant sa session.
        $middleware->web(append: [
            EnsureAccountIsActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();

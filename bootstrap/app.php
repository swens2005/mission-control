<?php

use App\Http\Middleware\ContentSecurityPolicy;
use App\Http\Middleware\EnsurePortal;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SandboxGuardrails;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::group([], __DIR__.'/../routes/deploy.php');
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['sidebar_state']);

        $middleware->alias(['portal' => EnsurePortal::class]);

        // Check the portal before route-model binding, so the wrong role gets
        // a 403 without its route parameters ever being looked up.
        $middleware->prependToPriorityList(before: SubstituteBindings::class, prepend: EnsurePortal::class);

        $middleware->web(append: [
            ContentSecurityPolicy::class,
            SandboxGuardrails::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();

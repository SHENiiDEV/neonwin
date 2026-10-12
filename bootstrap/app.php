<?php

use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trimStrings(except: [fn (Request $request) => $request->is('api/flexrix/*')]);
        $middleware->convertEmptyStringsToNull(except: [fn (Request $request) => $request->is('api/flexrix/*')]);

        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);

        $middleware->validateCsrfTokens(except: [
            '/gold_api',
            'gold_api',
            '/api/ggr/*',
            '/api/ggr-gold/*',
            'api/ggr-gold/callback',
            'api/ggr/callback',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontFlash(['telegram_token', 'contact_handle']);
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->is('gold_api') || $request->expectsJson(),
        );
    })->create();

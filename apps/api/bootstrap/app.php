<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use App\Http\Middleware\AssignRequestId;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (): void {
            $aiRoutes = base_path('routes/ai.php');

            if (is_file($aiRoutes)) {
                require $aiRoutes;
            }
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Cloudflare Tunnel terminates HTTPS before forwarding to FrankenPHP
        // over HTTP. Trust only the forwarded scheme so Laravel generates HTTPS
        // asset URLs without trusting client-supplied host or client-IP headers.
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_PROTO,
        );

        $middleware->append(AssignRequestId::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();

<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');

        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'superadmin' => \App\Http\Middleware\EnsureSuperAdmin::class,
            'menu.auth' => \App\Http\Middleware\EnsureMenuAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->respond(function (Response $response, \Throwable $exception, Request $request) {
            $status = $response->getStatusCode();

            if ($request->expectsJson()) {
                if ($status >= 500 && ! config('app.debug')) {
                    return response()->json(['message' => 'Terjadi kesalahan pada server.'], $status);
                }

                return $response;
            }

            if ($status === 403) {
                return Inertia::render('Errors/Forbidden')
                    ->toResponse($request)
                    ->setStatusCode(403);
            }

            $dedicatedPages = [
                400 => 'Errors/400',
                401 => 'Errors/401',
                404 => 'Errors/404',
                405 => 'Errors/405',
                413 => 'Errors/413',
                419 => 'Errors/419',
                422 => 'Errors/422',
                429 => 'Errors/429',
                503 => 'Errors/503',
            ];

            $shouldRenderErrorPage = isset($dedicatedPages[$status])
                || $status >= 500
                || ($status >= 400 && ! config('app.debug'));

            if ($shouldRenderErrorPage) {
                if ($status >= 500 && config('app.debug')) {
                    return $response;
                }

                try {
                    $component = $dedicatedPages[$status] ?? 'Errors/ServerError';

                    return Inertia::render($component, ['status' => $status])
                        ->toResponse($request)
                        ->setStatusCode($status);
                } catch (\Throwable) {
                    return response('Terjadi kesalahan pada server. Silakan coba beberapa saat lagi.', $status);
                }
            }

            return $response;
        });
    })->create();

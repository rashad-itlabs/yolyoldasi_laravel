<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->throttleApi('60,1');

        // Saytın hesab səhifələri qonağı `/giris`-ə yönləndirir. API-yə
        // yönləndirmə yoxdur — tətbiq həmişə JSON 401 alır (aşağıya bax).
        $middleware->redirectGuestsTo(
            fn ($request) => $request->is('api/*') ? null : route('login')
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn ($request) => $request->is('api/*') || $request->expectsJson()
        );

        // MÜVƏQQƏTİ diaqnostika: uğursuz 401-lərin səbəbini loga yazır.
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            // Sayt səhifəsi: null qaytarılır ki, Laravel özü `/giris`-ə
            // yönləndirsin. Əks halda brauzer JSON 401 görərdi.
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null;
            }

            if ($request->is('api/*')) {
                $auth = (string) $request->header('Authorization', '');
                \Illuminate\Support\Facades\Log::warning('API-401', [
                    'path'       => $request->path(),
                    'ua'         => $request->userAgent(),
                    'auth_var'   => $auth !== '',
                    'auth_uzun'  => strlen($auth),
                    'auth_ilk15' => substr($auth, 0, 15),
                    'bearer_ok'  => $request->bearerToken() !== null,
                    'basliqlar'  => array_keys($request->headers->all()),
                ]);
            }

            return response()->json(['message' => 'Unauthenticated.'], 401);
        });
    })->create();

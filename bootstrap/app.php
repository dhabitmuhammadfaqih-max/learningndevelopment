<?php

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
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);

        // Wajib supaya halaman standalone PWA (terutama di iPhone) selalu
        // ambil versi terbaru dari server, tidak nyangkut di cache lokal
        // device - lihat catatan lengkap di PreventPwaPageCaching.php.
        $middleware->web(append: [
            \App\Http\Middleware\PreventPwaPageCaching::class,
        ]);

        // Percayai header X-Forwarded-* dari SEMUA proxy di depan aplikasi
        // (ngrok saat development/testing, dan nanti reverse proxy Nginx
        // di production) - supaya Laravel tahu request aslinya HTTPS
        // walau di internal diteruskan sebagai HTTP biasa. Tanpa ini,
        // asset()/@vite()/route() akan generate URL http:// meski
        // browser sudah mengakses lewat https://, yang menyebabkan
        // CSS/JS diblokir browser (mixed content).
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
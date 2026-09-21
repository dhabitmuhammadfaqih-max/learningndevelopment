<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Safari iOS (terutama saat dibuka sebagai standalone web app dari Home
 * Screen) sering nge-cache halaman HTML secara agresif dan TIDAK refetch
 * walau app di-swipe-tutup total dari App Switcher lalu dibuka ulang -
 * karena standalone mode tidak punya tombol reload/address bar buat maksa
 * refresh manual seperti tab Safari biasa.
 *
 * Ini nyebabin device tertentu (terutama iPhone) masih "ngeliat" versi
 * HTML lama walau server & deploy-nya udah update, sementara device lain
 * (browser biasa di laptop) langsung dapet versi terbaru.
 *
 * Middleware ini maksa browser/iOS supaya SELALU minta versi terbaru ke
 * server untuk response halaman (HTML), bukan pakai cache lokal. Aset
 * statis (CSS/JS/gambar via @vite dan public/) TIDAK kena ini - itu
 * dihandle terpisah dan memang boleh di-cache karena sudah versioned.
 */
class PreventPwaPageCaching
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Cuma terapkan ke response HTML (halaman biasa), bukan ke
        // response JSON/file/download supaya tidak mengganggu hal lain.
        $contentType = $response->headers->get('Content-Type', '');

        if (str_contains($contentType, 'text/html')) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('Expires', '0');
        }

        return $response;
    }
}

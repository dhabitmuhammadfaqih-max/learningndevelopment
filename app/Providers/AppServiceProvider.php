<?php

namespace App\Providers;

use App\Listeners\AuditAuthSubscriber;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Audit Log: catat login/logout/gagal login/diblokir.
        Event::subscribe(AuditAuthSubscriber::class);

        // Fix umum Laravel: migration yang memakai ->change() akan gagal
        // kalau ada kolom bertipe "enum" di tabel yang sama, karena
        // Doctrine DBAL tidak tahu cara membaca tipe "enum" (mis. kolom
        // "role" di tabel users). Daftarkan mapping-nya sebagai string
        // supaya Doctrine DBAL tidak error saat introspeksi tabel.
        if ($this->app->bound('db')) {
            try {
                DB::connection()
                    ->getDoctrineSchemaManager()
                    ->getDatabasePlatform()
                    ->registerDoctrineTypeMapping('enum', 'string');
            } catch (\Throwable $e) {
                // Diamkan kalau driver tidak mendukung / dbal tidak terpasang,
                // supaya boot() tidak ikut gagal di lingkungan lain.
            }
        }
    }
}

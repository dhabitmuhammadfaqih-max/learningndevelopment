<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Audit Log aktif/nonaktif
    |--------------------------------------------------------------------------
    | Matikan lewat .env (AUDIT_ENABLED=false) kalau perlu, mis. saat
    | menjalankan import data besar sekali jalan. Lihat juga
    | App\Support\AuditLogger::withoutAuditing() untuk mematikan sementara
    | di dalam kode.
    */
    'enabled' => env('AUDIT_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Lama penyimpanan (hari)
    |--------------------------------------------------------------------------
    | Dipakai command `php artisan audit:prune` (dijadwalkan harian di
    | routes/console.php). Isi 0 untuk menyimpan selamanya.
    */
    'retention_days' => (int) env('AUDIT_RETENTION_DAYS', 365),

    /*
    |--------------------------------------------------------------------------
    | Jumlah baris per halaman di halaman Audit Log HRD
    |--------------------------------------------------------------------------
    */
    'per_page' => 25,

    /*
    |--------------------------------------------------------------------------
    | Batas baris untuk ekspor CSV
    |--------------------------------------------------------------------------
    */
    'export_limit' => 50000,

    /*
    |--------------------------------------------------------------------------
    | Kolom yang nilainya DISEMBUNYIKAN di log
    |--------------------------------------------------------------------------
    | Perubahan kolom ini tetap tercatat (supaya kelihatan kolomnya berubah),
    | tapi isinya diganti "[disembunyikan]". Mendukung wildcard (*).
    | Kode pertemuan ikut disembunyikan karena sifatnya sekali-pakai.
    */
    'masked_attributes' => [
        'password',
        'remember_token',
        'code',
        '*_kode',
    ],

    /*
    |--------------------------------------------------------------------------
    | Kolom yang diabaikan sepenuhnya
    |--------------------------------------------------------------------------
    | Tidak pernah dicatat, dan perubahan yang HANYA menyentuh kolom-kolom
    | ini tidak menghasilkan baris log (mis. remember_token yang berganti
    | tiap logout).
    */
    'ignored_attributes' => [
        'created_at',
        'updated_at',
        'remember_token',
    ],

    /*
    |--------------------------------------------------------------------------
    | Panjang maksimum satu nilai yang disimpan di log
    |--------------------------------------------------------------------------
    */
    'max_value_length' => 500,

];

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel key-value sederhana untuk pengaturan aplikasi yang bisa diubah
 * HRD lewat UI (bukan lewat .env/config, karena butuh diubah sewaktu-waktu
 * tanpa deploy ulang).
 *
 * Dipakai pertama kali untuk 'active_evaluation_year' - lihat
 * App\Support\ActivePeriod::year(). Sengaja dibuat generic (key-value)
 * bukan tabel khusus 1 kolom, supaya kalau nanti butuh setting lain
 * (mis. "batas tanggal checklist pertemuan", "pesan pengumuman global")
 * tinggal tambah baris baru, tidak perlu migration baru tiap kali.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};

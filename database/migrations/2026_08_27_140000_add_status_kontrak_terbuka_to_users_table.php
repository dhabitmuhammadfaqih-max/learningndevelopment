<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambahan kolom "status_kontrak_terbuka" untuk kolom "Status" (bukan
 * `contract_status`) milik pegawai/pejabat.
 *
 * HRD kadang perlu MENYEMBUNYIKAN badge Status ("Kontrak"/"PHL"/"Tetap")
 * dari dashboard pegawai/pejabat sendiri - misalnya saat status
 * karyawan sedang diproses ulang dan rawan disalahgunakan ("dijokiin")
 * kalau keburu terlihat oleh yang bersangkutan. Defaultnya TERBUKA
 * (true) supaya perilaku akun-akun yang sudah ada tidak berubah -
 * hanya akun yang secara eksplisit "ditutup" oleh HRD yang badge-nya
 * disembunyikan. Lihat User::statusKontrakTerbuka() dan
 * HrdController::toggleStatusKontrak().
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'status_kontrak_terbuka')) {
                $table->boolean('status_kontrak_terbuka')->default(true)->after('status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'status_kontrak_terbuka')) {
                $table->dropColumn('status_kontrak_terbuka');
            }
        });
    }
};

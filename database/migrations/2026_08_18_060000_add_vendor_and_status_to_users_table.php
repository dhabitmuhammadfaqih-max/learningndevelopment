<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tambahan kolom "Vendor" dan "Status" untuk data karyawan.
 *
 * Dua kolom ini terpisah dari `contract_status` yang sudah ada
 * (dipakai di halaman detail Admin, pilihan Harian/Bulanan/Tahunan/
 * Tetap). Kolom `status` di sini mengikuti kolom "Status" pada file
 * Excel HRD (mis. "Tetap", "PHL"), dan `vendor` mengikuti kolom
 * "Vendor" (mis. "Dagsap", "OS ABM").
 *
 * Khusus baris SPG: file Excel HRD (sheet "SPG") tidak punya kolom
 * Vendor/Status sama sekali - semua akun SPG selalu diisi otomatis
 * status = "PHL" dan vendor = "OS ABM" (lihat HrdController::importAccounts()).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'vendor')) {
                $table->string('vendor')->nullable()->after('departemen');
            }

            if (! Schema::hasColumn('users', 'status')) {
                $table->string('status')->nullable()->after('vendor');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'status')) {
                $table->dropColumn('status');
            }

            if (Schema::hasColumn('users', 'vendor')) {
                $table->dropColumn('vendor');
            }
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Total menit keterlambatan (rekap, bukan per kejadian). Sama
            // pola dengan jumlah_izin/jumlah_sakit/jumlah_alpa/jumlah_terlambat:
            // HRD input angka total yang sudah direkap dari sistem absensi
            // mereka, ditimpa penuh tiap kali sheet kehadiran diimport ulang
            // (lihat HrdController::importAccounts()) - kolom Excel kosong
            // otomatis dianggap 0, bukan dibiarkan.
            if (! Schema::hasColumn('users', 'menit_terlambat')) {
                $table->unsignedInteger('menit_terlambat')->default(0)->after('jumlah_terlambat');
            }

            // Tanggal mulai kerja - dipakai untuk menghitung masa kerja
            // (tahun/bulan) secara otomatis lewat accessor
            // User::getMasaKerjaAttribute(), bukan disimpan sebagai angka
            // tahun/bulan statis supaya tidak perlu diupdate manual tiap
            // bulan. Nullable karena tidak semua akun (mis. data lama)
            // punya tanggal masuk yang diketahui.
            if (! Schema::hasColumn('users', 'tanggal_masuk')) {
                $table->date('tanggal_masuk')->nullable()->after('menit_terlambat');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['tanggal_masuk', 'menit_terlambat'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

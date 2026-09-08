<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kolom penanda kapan HRD terakhir mengisi/menyimpan data kehadiran
        // pegawai ini. Dibutuhkan supaya "belum diisi" bisa dibedakan dari
        // "sudah diisi tapi nol semua" (jumlah_izin dkk default 0), karena
        // 0 di semua kolom kehadiran tetap data yang valid (pegawai dengan
        // kehadiran sempurna). Dipakai sebagai salah satu syarat sebelum
        // penilai boleh mulai menilai pegawai - lihat
        // User::kehadiranSudahDiisiHrd().
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'kehadiran_diisi_at')) {
                $table->timestamp('kehadiran_diisi_at')->nullable()->after('jumlah_terlambat');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'kehadiran_diisi_at')) {
                $table->dropColumn('kehadiran_diisi_at');
            }
        });
    }
};

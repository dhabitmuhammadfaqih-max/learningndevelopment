<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Flag per pejabat (mirip tanggapan_atasan_manual milik pegawai): kalau
     * dicentang, HRD boleh cetak PDF penilaian pejabat ini walau Penilaian
     * dari Penilai (OfficialEvaluation) belum diisi lewat sistem - karena
     * penilai pejabat ini akan mengisi penilaiannya secara manual (di luar
     * aplikasi, mis. di kertas). Lihat HrdController::officialPdf() &
     * User::tanggapanPenilaiPejabatManual().
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('tanggapan_penilai_pejabat_manual')
                ->default(false)
                ->after('tanggapan_atasan_manual');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('tanggapan_penilai_pejabat_manual');
        });
    }
};

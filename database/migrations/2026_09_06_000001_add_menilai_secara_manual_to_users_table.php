<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Flag di akun EVALUATOR (pejabat/hrd) itu sendiri - beda dari
     * tanggapan_atasan_manual/tanggapan_penilai_pejabat_manual yang
     * dicentang per-akun yang DINILAI. Kalau dicentang di sini, SEMUA
     * pegawai/pejabat yang ditugaskan ke akun ini sebagai Penilai
     * (users.supervisor_id) ATAUPUN sebagai Atasan Penilai
     * (users.atasan_pejabat_id / users.atasan_penilai_pejabat_id) otomatis
     * dianggap "manual" tanpa perlu dicentang satu-satu - karena akun ini
     * memang menilai semua bawahannya secara manual di luar aplikasi.
     * Lihat User::menilaiSecaraManual(), User::penilaianUtamaManual(),
     * User::tanggapanAtasanManual(), User::tanggapanPenilaiPejabatManual().
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('menilai_secara_manual')
                ->default(false)
                ->after('tanggapan_penilai_pejabat_manual');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('menilai_secara_manual');
        });
    }
};

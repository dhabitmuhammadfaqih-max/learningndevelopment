<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dulu ada 3 flag "manual" berbeda: tanggapan_atasan_manual &
 * tanggapan_penilai_pejabat_manual (dicentang satu-satu di akun yang
 * DINILAI), dan menilai_secara_manual (dicentang sekali di akun
 * EVALUATOR, otomatis berlaku ke semua bawahannya). Dua flag pertama
 * ternyata cuma menduplikasi tujuan yang sama dengan menilai_secara_manual
 * dan bikin bingung (harus dicentang manual per-akun, gampang lupa/tidak
 * konsisten). Sekarang disederhanakan: hanya menilai_secara_manual yang
 * dipakai - lihat User::tanggapanAtasanManual()/tanggapanPenilaiPejabatManual()
 * yang sekarang murni turunan dari menilai_secara_manual milik
 * atasanPejabat()/supervisor().
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'tanggapan_atasan_manual',
                'tanggapan_penilai_pejabat_manual',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('tanggapan_atasan_manual')->default(false)->after('is_spg');
            $table->boolean('tanggapan_penilai_pejabat_manual')->default(false)->after('tanggapan_atasan_manual');
        });
    }
};

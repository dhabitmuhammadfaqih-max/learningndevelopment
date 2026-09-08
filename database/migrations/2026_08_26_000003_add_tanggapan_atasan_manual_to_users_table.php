<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Flag per pegawai (mirip is_spg): kalau dicentang, HRD boleh cetak
     * PDF penilaian tanpa menunggu Tanggapan Atasan (SupervisorFeedback /
     * atasan Evaluation) diisi lewat sistem - karena atasan pegawai ini
     * akan mengisi tanggapannya secara manual (di luar aplikasi, mis. di
     * kertas). Lihat HrdController::pdf() & User::siapDicetakTanpaAtasan().
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('tanggapan_atasan_manual')
                ->default(false)
                ->after('is_spg');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('tanggapan_atasan_manual');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * atasan_penilai_pejabat_id adalah versi "atasan_pejabat_id" (lihat
     * migration add_atasan_pejabat_id_to_users_table) tapi untuk pejabat,
     * bukan pegawai. Menandai siapa yang berhak mengisi "Tanggapan Atasan"
     * (OfficialSupervisorFeedback) untuk pejabat tsb, terpisah dari
     * supervisor_id yang dipakai untuk Penilaian Kinerja pejabat
     * (OfficialEvaluation, lihat SupervisorController::evaluateOfficial()).
     * Sengaja dipisah supaya pejabat yang ditugaskan sebagai Atasan
     * Penilai HANYA bisa memberi tanggapan, bukan ikut menilai - sama
     * seperti aturan atasan_pejabat_id untuk pegawai.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'atasan_penilai_pejabat_id')) {
                $table->foreignId('atasan_penilai_pejabat_id')
                    ->nullable()
                    ->after('atasan_pejabat_id')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'atasan_penilai_pejabat_id')) {
                $table->dropConstrainedForeignId('atasan_penilai_pejabat_id');
            }
        });
    }
};

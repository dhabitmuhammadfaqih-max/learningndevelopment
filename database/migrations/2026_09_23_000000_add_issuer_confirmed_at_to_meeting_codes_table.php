<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Alur MeetingCode disederhanakan lagi:
     * - HANYA Penilai/Atasan ($issuer) yang menekan "Minta Kode"
     *   (issuer_requested_at). Pihak yang dinilai tidak lagi meminta,
     *   jadi subject_requested_at tidak dipakai lagi (kolomnya
     *   dibiarkan supaya data lama tetap utuh).
     * - Setelah HRD membuat kode, KEDUA pihak menekan "Sudah Bertemu":
     *   pihak yang dinilai lewat used_at/used_by (kode diverifikasi),
     *   Penilai/Atasan lewat kolom baru di bawah ini.
     */
    public function up(): void
    {
        Schema::table('meeting_codes', function (Blueprint $table) {
            $table->timestamp('issuer_confirmed_at')->nullable()->after('used_by');
        });
    }

    public function down(): void
    {
        Schema::table('meeting_codes', function (Blueprint $table) {
            $table->dropColumn('issuer_confirmed_at');
        });
    }
};

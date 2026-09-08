<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan tanda tangan digital pejabat sendiri saat menanggapi
     * penilaian (OfficialEvaluation::employee_response), sama seperti
     * pola employee_signature di tabel evaluations (lihat migrasi
     * add_employee_hrd_signature_to_evaluations_table). Dipakai untuk
     * mengunci Penilaian Pejabat & Tanggapan Atasan Pejabat begitu
     * pejabat yang dinilai sudah tanda tangan.
     */
    public function up(): void
    {
        Schema::table('official_evaluations', function (Blueprint $table) {
            if (! Schema::hasColumn('official_evaluations', 'employee_signature')) {
                $table->string('employee_signature')->nullable()->after('employee_response_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('official_evaluations', function (Blueprint $table) {
            if (Schema::hasColumn('official_evaluations', 'employee_signature')) {
                $table->dropColumn('employee_signature');
            }
        });
    }
};

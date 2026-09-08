<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sama seperti evaluations/official_evaluations: kalau Atasan Penilai
     * memilih rekomendasi "Promosi" saat memberi Tanggapan Atasan (baik
     * untuk pegawai maupun untuk pejabat), keterangan tujuan promosi wajib
     * diisi. Lihat App\Models\SupervisorFeedback &
     * App\Models\OfficialSupervisorFeedback.
     */
    public function up(): void
    {
        Schema::table('supervisor_feedbacks', function (Blueprint $table) {
            if (! Schema::hasColumn('supervisor_feedbacks', 'promosi_keterangan')) {
                $table->string('promosi_keterangan', 255)->nullable()->after('kenaikan_gaji_amount');
            }
        });

        Schema::table('official_supervisor_feedbacks', function (Blueprint $table) {
            if (! Schema::hasColumn('official_supervisor_feedbacks', 'promosi_keterangan')) {
                $table->string('promosi_keterangan', 255)->nullable()->after('kenaikan_gaji_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('supervisor_feedbacks', function (Blueprint $table) {
            if (Schema::hasColumn('supervisor_feedbacks', 'promosi_keterangan')) {
                $table->dropColumn('promosi_keterangan');
            }
        });

        Schema::table('official_supervisor_feedbacks', function (Blueprint $table) {
            if (Schema::hasColumn('official_supervisor_feedbacks', 'promosi_keterangan')) {
                $table->dropColumn('promosi_keterangan');
            }
        });
    }
};

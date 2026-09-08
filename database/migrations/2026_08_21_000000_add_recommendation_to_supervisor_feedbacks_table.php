<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambahan supaya Atasan Penilai (pejabat yang ditugaskan lewat
     * users.atasan_pejabat_id) juga bisa kasih Rekomendasi ke pegawai saat
     * memberi Tanggapan Atasan, sama seperti rekomendasi yang sudah ada di
     * penilaian pejabat->pegawai (Evaluation) & atasan->pejabat
     * (OfficialEvaluation). Lihat App\Models\SupervisorFeedback.
     */
    public function up(): void
    {
        Schema::table('supervisor_feedbacks', function (Blueprint $table) {
            if (! Schema::hasColumn('supervisor_feedbacks', 'recommendation')) {
                $table->string('recommendation', 500)->default('tidak_ada')->after('feedback');
            }

            if (! Schema::hasColumn('supervisor_feedbacks', 'kenaikan_gaji_amount')) {
                $table->unsignedInteger('kenaikan_gaji_amount')->nullable()->after('recommendation');
            }
        });
    }

    public function down(): void
    {
        Schema::table('supervisor_feedbacks', function (Blueprint $table) {
            if (Schema::hasColumn('supervisor_feedbacks', 'kenaikan_gaji_amount')) {
                $table->dropColumn('kenaikan_gaji_amount');
            }

            if (Schema::hasColumn('supervisor_feedbacks', 'recommendation')) {
                $table->dropColumn('recommendation');
            }
        });
    }
};

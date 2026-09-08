<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * atasan_pejabat_id dipakai KHUSUS untuk pegawai: menandai siapa yang
     * berhak mengisi "Tanggapan Atasan" (SupervisorFeedback) untuk pegawai
     * tsb. Sengaja dipisah dari supervisor_id, karena supervisor_id dipakai
     * untuk peran lain (Pejabat Penilai yang mengisi Penilaian Kinerja,
     * lihat OfficialController::canEvaluate()). Sebelumnya kedua peran ini
     * numpuk di satu kolom yang sama sehingga ambigu.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'atasan_pejabat_id')) {
                $table->foreignId('atasan_pejabat_id')
                    ->nullable()
                    ->after('supervisor_id')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'atasan_pejabat_id')) {
                $table->dropConstrainedForeignId('atasan_pejabat_id');
            }
        });
    }
};

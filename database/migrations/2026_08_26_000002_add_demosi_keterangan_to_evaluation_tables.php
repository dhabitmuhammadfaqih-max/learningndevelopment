<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Simpan jabatan/posisi tujuan ketika rekomendasi "Demosi" dipilih.
     * Field dibuat terpisah dari promosi agar setiap rekomendasi memiliki
     * keterangan tujuan yang dapat ditampilkan kembali pada histori/laporan.
     */
    public function up(): void
    {
        foreach ([
            'evaluations',
            'official_evaluations',
            'supervisor_feedbacks',
            'official_supervisor_feedbacks',
        ] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (! Schema::hasColumn($tableName, 'demosi_keterangan')) {
                    $table->string('demosi_keterangan', 255)
                        ->nullable()
                        ->after('promosi_keterangan');
                }
            });
        }
    }

    public function down(): void
    {
        foreach ([
            'evaluations',
            'official_evaluations',
            'supervisor_feedbacks',
            'official_supervisor_feedbacks',
        ] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                if (Schema::hasColumn($tableName, 'demosi_keterangan')) {
                    $table->dropColumn('demosi_keterangan');
                }
            });
        }
    }
};

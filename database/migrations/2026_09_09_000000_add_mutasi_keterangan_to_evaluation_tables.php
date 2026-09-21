<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Simpan tujuan mutasi (posisi/unit kerja tujuan) ketika rekomendasi
     * "Mutasi" dipilih. Mengikuti pola yang sama dengan
     * promosi_keterangan / demosi_keterangan.
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
                if (! Schema::hasColumn($tableName, 'mutasi_keterangan')) {
                    $table->string('mutasi_keterangan', 255)
                        ->nullable()
                        ->after('demosi_keterangan');
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
                if (Schema::hasColumn($tableName, 'mutasi_keterangan')) {
                    $table->dropColumn('mutasi_keterangan');
                }
            });
        }
    }
};

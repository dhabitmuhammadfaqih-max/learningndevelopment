<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Saat pejabat/atasan pejabat memilih rekomendasi "Promosi", mereka wajib
     * mengisi keterangan tujuan promosi (mis. "Kepala Bagian Operasional").
     * Kolom ini menyimpan keterangan tsb, sama seperti kenaikan_gaji_amount
     * menyimpan nominal untuk rekomendasi "Kenaikan Gaji".
     */
    public function up(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            if (! Schema::hasColumn('evaluations', 'promosi_keterangan')) {
                $table->string('promosi_keterangan', 255)->nullable()->after('kenaikan_gaji_amount');
            }
        });

        Schema::table('official_evaluations', function (Blueprint $table) {
            if (! Schema::hasColumn('official_evaluations', 'promosi_keterangan')) {
                $table->string('promosi_keterangan', 255)->nullable()->after('kenaikan_gaji_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            if (Schema::hasColumn('evaluations', 'promosi_keterangan')) {
                $table->dropColumn('promosi_keterangan');
            }
        });

        Schema::table('official_evaluations', function (Blueprint $table) {
            if (Schema::hasColumn('official_evaluations', 'promosi_keterangan')) {
                $table->dropColumn('promosi_keterangan');
            }
        });
    }
};

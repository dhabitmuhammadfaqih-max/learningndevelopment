<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            // Setiap komponen dinilai 0-100, lalu dikalikan bobotnya
            // masing-masing untuk menghasilkan kolom `score` (nilai akhir).
            $table->unsignedTinyInteger('pengetahuan_kerja')->default(0)->after('employee_id');        // bobot 15%
            $table->unsignedTinyInteger('penguasaan_peralatan')->default(0)->after('pengetahuan_kerja'); // bobot 15%
            $table->unsignedTinyInteger('volume_kerja')->default(0)->after('penguasaan_peralatan');      // bobot 10%
            $table->unsignedTinyInteger('mutu_tanggung_jawab')->default(0)->after('volume_kerja');       // bobot 10%
            $table->unsignedTinyInteger('disiplin_dedikasi_loyalitas')->default(0)->after('mutu_tanggung_jawab'); // bobot 15%
            $table->decimal('prakarsa', 5, 2)->default(0)->after('disiplin_dedikasi_loyalitas');         // bobot 7.5%
            $table->unsignedTinyInteger('daya_serap')->default(0)->after('prakarsa');                    // bobot 10%
            $table->unsignedTinyInteger('kerajinan')->default(0)->after('daya_serap');                   // bobot 10%
            $table->decimal('kerjasama', 5, 2)->default(0)->after('kerajinan');                          // bobot 7.5%
        });
    }

    public function down(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            $table->dropColumn([
                'pengetahuan_kerja',
                'penguasaan_peralatan',
                'volume_kerja',
                'mutu_tanggung_jawab',
                'disiplin_dedikasi_loyalitas',
                'prakarsa',
                'daya_serap',
                'kerajinan',
                'kerjasama',
            ]);
        });
    }
};
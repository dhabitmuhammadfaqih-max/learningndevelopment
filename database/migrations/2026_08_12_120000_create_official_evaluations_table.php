<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('official_evaluations', function (Blueprint $table) {
            $table->id();

            // Pejabat yang dinilai
            $table->foreignId('official_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Atasan pejabat yang menilai (harus sama dengan
            // users.supervisor_id milik pejabat tsb — dicek di controller)
            $table->foreignId('supervisor_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Setiap komponen dinilai 0-100, lalu dikalikan bobotnya
            // masing-masing untuk menghasilkan kolom `score` (nilai akhir).
            $table->unsignedTinyInteger('kepemimpinan')->default(0);                                        // bobot 15%
            $table->unsignedTinyInteger('kemampuan_merencanakan_mengoordinasikan')->default(0);              // bobot 15%
            $table->unsignedTinyInteger('kemampuan_analisa_evaluasi_pengambilan_keputusan')->default(0);     // bobot 10%
            $table->unsignedTinyInteger('kemampuan_memotivasi_aplikasi_manajemen')->default(0);              // bobot 10%
            $table->unsignedTinyInteger('tanggung_jawab_manajemen')->default(0);                             // bobot 10%
            $table->unsignedTinyInteger('kerjasama')->default(0);                                            // bobot 10%
            $table->unsignedTinyInteger('prakarsa')->default(0);                                             // bobot 10%
            $table->unsignedTinyInteger('integritas')->default(0);                                           // bobot 15%
            $table->unsignedTinyInteger('pengetahuan_teknik_operasi')->default(0);                           // bobot 5%

            $table->decimal('score', 5, 2)->default(0);

            $table->text('feedback');

            // Sama seperti evaluations: bisa pilih lebih dari satu,
            // disimpan dipisah koma.
            $table->string('recommendation', 150)->default('tidak_ada');
            $table->unsignedInteger('kenaikan_gaji_amount')->nullable();

            $table->string('signature')->nullable();

            $table->text('employee_response')->nullable();
            $table->timestamp('employee_response_at')->nullable();

            $table->timestamps();

            $table->unique(['official_id', 'supervisor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('official_evaluations');
    }
};

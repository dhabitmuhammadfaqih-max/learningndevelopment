<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluations', function (Blueprint $table) {
            $table->id();

            // Karyawan yang dinilai
            $table->foreignId('employee_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Pejabat yang menilai
            $table->foreignId('official_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // 0 - 100
            $table->unsignedTinyInteger('score');

            $table->text('feedback');

            $table->enum('recommendation', [
                'Perpanjang Kontrak',
                'Promosi',
                'Kenaikan Gaji'
            ]);

            $table->timestamps();

            $table->unique(['employee_id', 'official_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluations');
    }
};
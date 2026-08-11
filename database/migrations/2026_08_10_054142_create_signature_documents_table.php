<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signature_documents', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_dokumen')->unique();
            $table->string('judul')->nullable();

            $table->string('karyawan_nama')->nullable();
            $table->string('karyawan_jabatan')->nullable();
            $table->string('karyawan_signature')->nullable(); // storage path
            $table->timestamp('karyawan_signed_at')->nullable();

            $table->string('pejabat_nama')->nullable();
            $table->string('pejabat_jabatan')->nullable();
            $table->string('pejabat_signature')->nullable();
            $table->timestamp('pejabat_signed_at')->nullable();

            $table->string('atasan_nama')->nullable();
            $table->string('atasan_jabatan')->nullable();
            $table->string('atasan_signature')->nullable();
            $table->timestamp('atasan_signed_at')->nullable();

            $table->enum('status', ['draft', 'karyawan_signed', 'pejabat_signed', 'completed'])
                ->default('draft');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signature_documents');
    }
};
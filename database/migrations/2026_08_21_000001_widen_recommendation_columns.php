<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Daftar rekomendasi ditambah jadi 15 opsi (lihat Evaluation::RECOMMENDATIONS
     * & OfficialEvaluation::RECOMMENDATIONS) dan tetap bisa dipilih lebih dari
     * satu sekaligus (disimpan dipisah koma). Kolom lama (50/150 karakter)
     * tidak cukup lagi kalau banyak opsi dicentang bersamaan, jadi dilebarkan.
     */
    public function up(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            $table->string('recommendation', 500)->default('tidak_ada')->change();
        });

        Schema::table('official_evaluations', function (Blueprint $table) {
            $table->string('recommendation', 500)->default('tidak_ada')->change();
        });
    }

    public function down(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            $table->string('recommendation', 50)->default('tidak_ada')->change();
        });

        Schema::table('official_evaluations', function (Blueprint $table) {
            $table->string('recommendation', 150)->default('tidak_ada')->change();
        });
    }
};

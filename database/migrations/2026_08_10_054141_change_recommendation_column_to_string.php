<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            $table->string('recommendation', 50)->default('tidak_ada')->change();
        });
    }

    public function down(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            $table->enum('recommendation', [
                'perpanjang_kontrak',
                'promosi',
                'kenaikan_gaji',
                'tidak_ada',
            ])->default('tidak_ada')->change();
        });
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Kolom recommendation sekarang bisa berisi lebih dari satu nilai
        // yang dipisahkan koma (mis. "promosi,kenaikan_gaji"), jadi
        // panjangnya perlu ditambah dari 50 -> 150 karakter.
        Schema::table('evaluations', function (Blueprint $table) {
            $table->string('recommendation', 150)->default('tidak_ada')->change();
        });

        if (! Schema::hasColumn('evaluations', 'kenaikan_gaji_amount')) {
            Schema::table('evaluations', function (Blueprint $table) {
                // Nominal kenaikan gaji yang diusulkan pejabat, maksimal 750.000.
                $table->unsignedInteger('kenaikan_gaji_amount')->nullable()->after('recommendation');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('evaluations', 'kenaikan_gaji_amount')) {
            Schema::table('evaluations', function (Blueprint $table) {
                $table->dropColumn('kenaikan_gaji_amount');
            });
        }

        Schema::table('evaluations', function (Blueprint $table) {
            $table->string('recommendation', 50)->default('tidak_ada')->change();
        });
    }
};

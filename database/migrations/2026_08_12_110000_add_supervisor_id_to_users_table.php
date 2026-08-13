<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // supervisor_id dipakai untuk menugaskan satu atasan_pejabat tertentu
        // ke seorang pejabat. Hanya bermakna kalau role user = pejabat, tapi
        // kolomnya tidak dibatasi khusus lewat DB (tidak semua database
        // mendukung constraint bersyarat) — pembatasannya dicek di level
        // aplikasi (controller).
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'supervisor_id')) {
                $table->foreignId('supervisor_id')
                    ->nullable()
                    ->after('role')
                    ->constrained('users')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'supervisor_id')) {
                $table->dropConstrainedForeignId('supervisor_id');
            }
        });
    }
};

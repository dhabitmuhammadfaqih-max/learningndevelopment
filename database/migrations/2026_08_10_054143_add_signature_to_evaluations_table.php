<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cek dulu supaya migration ini aman dijalankan ulang
        // (kolomnya kemungkinan sudah pernah ditambahkan sebelumnya
        // lewat cara lain, jadi tidak tercatat sebagai "Ran").
        if (! Schema::hasColumn('evaluations', 'signature')) {
            Schema::table('evaluations', function (Blueprint $table) {
                $table->string('signature')->nullable()->after('recommendation');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('evaluations', 'signature')) {
            Schema::table('evaluations', function (Blueprint $table) {
                $table->dropColumn('signature');
            });
        }
    }
};
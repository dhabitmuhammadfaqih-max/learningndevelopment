<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cek dulu supaya migration ini aman dijalankan ulang
        // (mis. kalau kolomnya sudah pernah ditambahkan manual sebelumnya).
        if (! Schema::hasColumn('feedbacks', 'signature')) {
            Schema::table('feedbacks', function (Blueprint $table) {
                $table->string('signature')->nullable()->after('feedback');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('feedbacks', 'signature')) {
            Schema::table('feedbacks', function (Blueprint $table) {
                $table->dropColumn('signature');
            });
        }
    }
};
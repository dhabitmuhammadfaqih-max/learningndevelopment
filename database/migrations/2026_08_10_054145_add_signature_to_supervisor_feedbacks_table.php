<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cek dulu supaya migration ini aman dijalankan ulang
        // (mengikuti pola migration signature lain di project ini).
        if (! Schema::hasColumn('supervisor_feedbacks', 'signature')) {
            Schema::table('supervisor_feedbacks', function (Blueprint $table) {
                $table->string('signature')->nullable()->after('feedback');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('supervisor_feedbacks', 'signature')) {
            Schema::table('supervisor_feedbacks', function (Blueprint $table) {
                $table->dropColumn('signature');
            });
        }
    }
};
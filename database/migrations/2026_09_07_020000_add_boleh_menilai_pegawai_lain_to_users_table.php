<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Flag khusus supaya HRD bisa menandai akun PEGAWAI tertentu (mis.
     * Dewi) sebagai boleh ditugaskan jadi atasan/penilai untuk pegawai
     * lain lewat users.supervisor_id - lihat
     * AdminController::EVALUATOR_ROLES. Sengaja berupa flag per-akun
     * (bukan buka semua role pegawai) supaya cuma akun yang memang
     * ditandai HRD yang muncul di dropdown "Atasan/Penilai", bukan
     * seluruh pegawai.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('boleh_menilai_pegawai_lain')->default(false)->after('menilai_secara_manual');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('boleh_menilai_pegawai_lain');
        });
    }
};

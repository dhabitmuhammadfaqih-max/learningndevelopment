<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tanda tangan "akun" - disimpan SEKALI per user, lalu dipakai ulang
     * setiap kali user itu perlu menandatangani sesuatu (tanggapan,
     * penilaian, dsb), di device manapun dia login. Berbeda dari kolom
     * signature/employee_signature/hrd_signature dsb yang ada di tabel
     * evaluations/feedbacks - kolom-kolom itu tetap ada dan tetap
     * menyimpan file gambar per-dokumen (untuk jejak audit), tapi isinya
     * sekarang diisi otomatis dari signature_path milik user ini,
     * bukan digambar ulang tiap kali.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('signature_path')->nullable()->after('remember_token');
            $table->timestamp('signature_saved_at')->nullable()->after('signature_path');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['signature_path', 'signature_saved_at']);
        });
    }
};

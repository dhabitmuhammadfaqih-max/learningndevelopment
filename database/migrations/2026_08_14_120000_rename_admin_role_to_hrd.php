<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Ganti nilai role "admin" -> "hrd" di seluruh data yang ada.
     *
     * Kolom users.role sudah berupa VARCHAR biasa (bukan ENUM lagi) sejak
     * migration rename_karyawan_role_to_pegawai, jadi di sini cukup
     * UPDATE data saja tanpa perlu ubah struktur kolom. Validasi nilai
     * role tetap dijaga di level aplikasi (lihat HrdController: validasi
     * 'in:pegawai,pejabat,atasan_pejabat,hrd').
     */
    public function up(): void
    {
        DB::table('users')->where('role', 'admin')->update(['role' => 'hrd']);
    }

    public function down(): void
    {
        DB::table('users')->where('role', 'hrd')->update(['role' => 'admin']);
    }
};

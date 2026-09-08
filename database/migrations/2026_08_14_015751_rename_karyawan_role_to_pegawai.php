<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ganti nilai role "karyawan" -> "pegawai" di seluruh data yang ada,
     * dan ubah kolom users.role dari ENUM jadi VARCHAR biasa. Alasannya:
     * ENUM di database (khususnya MySQL) susah diubah lewat migration
     * Laravel biasa, jadi supaya penggantian istilah role di masa depan
     * tidak serumit ini lagi, validasi nilai role cukup dijaga di level
     * aplikasi (lihat HrdController: validasi 'in:pegawai,pejabat,...').
     */
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            // SQLite: kolom "role" dibuat dengan CHECK constraint bawaan
            // Laravel saat CREATE TABLE, dan SQLite tidak bisa ALTER
            // CHECK constraint secara langsung. Solusinya: tambah kolom
            // baru tanpa constraint, pindahkan datanya, lalu ganti nama.
            Schema::table('users', function (Blueprint $table) {
                $table->string('role_new', 20)->nullable()->after('role');
            });

            DB::statement("UPDATE users SET role_new = CASE WHEN role = 'karyawan' THEN 'pegawai' ELSE role END");

            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('role');
            });

            Schema::table('users', function (Blueprint $table) {
                $table->renameColumn('role_new', 'role');
            });
        } else {
            // MySQL/MariaDB/Postgres: ganti tipe kolom jadi varchar dulu
            // (menghapus constraint ENUM lama), baru update datanya.
            Schema::table('users', function (Blueprint $table) {
                $table->string('role', 20)->default('pegawai')->change();
            });

            DB::table('users')->where('role', 'karyawan')->update(['role' => 'pegawai']);
        }
    }

    public function down(): void
    {
        DB::table('users')->where('role', 'pegawai')->update(['role' => 'karyawan']);

        // Catatan: kolom tetap varchar biasa (tidak dikembalikan ke ENUM),
        // karena validasi nilai role sudah cukup dijaga di level aplikasi.
    }
};

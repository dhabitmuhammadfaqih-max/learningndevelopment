<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tambah kolom is_spg dulu (default false) sebelum data role "spg"
        // dipindahkan, supaya statusnya tidak hilang.
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'is_spg')) {
                $table->boolean('is_spg')->default(false)->after('role');
            }
        });

        // Akun yang sebelumnya berrole "spg" jadi role "karyawan" biasa,
        // tapi ditandai is_spg = true supaya perilakunya (korelasi opsional)
        // tetap sama seperti sebelumnya.
        DB::table('users')->where('role', 'spg')->update([
            'role'   => 'karyawan',
            'is_spg' => true,
        ]);

        // "spg" dikeluarkan dari daftar pilihan role yang sah.
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', [
                'karyawan',
                'pejabat',
                'atasan_pejabat',
                'admin',
            ])->default('karyawan')->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', [
                'karyawan',
                'pejabat',
                'atasan_pejabat',
                'admin',
                'spg',
            ])->default('karyawan')->change();
        });

        DB::table('users')->where('is_spg', true)->update([
            'role' => 'spg',
        ]);

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'is_spg')) {
                $table->dropColumn('is_spg');
            }
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'username')) {
                $table->string('username')->nullable()->unique()->after('name');
            }

            if (! Schema::hasColumn('users', 'nik')) {
                // NIK dipakai juga sebagai password login karyawan.
                $table->string('nik')->nullable()->unique()->after('username');
            }

            if (! Schema::hasColumn('users', 'unit_kerja')) {
                $table->string('unit_kerja')->nullable()->after('nik');
            }
        });

        // Catatan: kolom "email" SENGAJA tidak diubah jadi nullable di sini.
        // Mengubah nullability kolom di tabel yang punya kolom enum (users.role)
        // butuh Doctrine DBAL dan sering gagal di SQLite. Supaya migration ini
        // aman dijalankan di environment mana pun, kita biarkan "email" tetap
        // wajib & unik — akun karyawan akan diberi email placeholder otomatis
        // dari username (lihat OfficialController::storeEmployee).
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'username')) {
                $table->dropColumn('username');
            }

            if (Schema::hasColumn('users', 'nik')) {
                $table->dropColumn('nik');
            }

            if (Schema::hasColumn('users', 'unit_kerja')) {
                $table->dropColumn('unit_kerja');
            }
        });
    }
};

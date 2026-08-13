<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // "role" tetap dipakai sebagai indikator hak akses (karyawan, pejabat,
        // atasan_pejabat, admin, spg) dan TIDAK diubah. "jabatan" di sini
        // adalah jabatan struktural/tekstual (mis. "Staff Marketing",
        // "Supervisor Produksi"), dipisah dari role karena satu role bisa
        // punya banyak variasi jabatan.
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'jabatan')) {
                $table->string('jabatan')->nullable()->after('unit_kerja');
            }

            if (! Schema::hasColumn('users', 'departemen')) {
                $table->string('departemen')->nullable()->after('jabatan');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'departemen')) {
                $table->dropColumn('departemen');
            }

            if (Schema::hasColumn('users', 'jabatan')) {
                $table->dropColumn('jabatan');
            }
        });
    }
};

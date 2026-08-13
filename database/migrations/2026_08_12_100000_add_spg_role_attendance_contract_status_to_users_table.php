<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tambahkan role baru "spg" ke enum role yang sudah ada
        // (karyawan, pejabat, atasan_pejabat, admin, spg).
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', [
                'karyawan',
                'pejabat',
                'atasan_pejabat',
                'admin',
                'spg',
            ])->default('karyawan')->change();
        });

        // Kolom baru untuk admin: jumlah izin/sakit/alpa/terlambat, masing-masing
        // dipisah sendiri-sendiri (bukan satu status tunggal) supaya admin
        // tinggal mengisi jumlah kejadian per kategori. Status kontrak dipakai
        // sebagai string (bukan enum) supaya konsisten dengan pola migration
        // "recommendation" sebelumnya & aman dijalankan di SQLite.
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'jumlah_izin')) {
                $table->unsignedInteger('jumlah_izin')->default(0)->after('unit_kerja');
            }

            if (! Schema::hasColumn('users', 'jumlah_sakit')) {
                $table->unsignedInteger('jumlah_sakit')->default(0)->after('jumlah_izin');
            }

            if (! Schema::hasColumn('users', 'jumlah_alpa')) {
                $table->unsignedInteger('jumlah_alpa')->default(0)->after('jumlah_sakit');
            }

            if (! Schema::hasColumn('users', 'jumlah_terlambat')) {
                $table->unsignedInteger('jumlah_terlambat')->default(0)->after('jumlah_alpa');
            }

            if (! Schema::hasColumn('users', 'contract_status')) {
                // Salah satu: harian, bulanan, tahunan, tetap.
                $table->string('contract_status', 20)->nullable()->after('jumlah_terlambat');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['contract_status', 'jumlah_terlambat', 'jumlah_alpa', 'jumlah_sakit', 'jumlah_izin'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', [
                'karyawan',
                'pejabat',
                'atasan_pejabat',
                'admin',
            ])->default('karyawan')->change();
        });
    }
};

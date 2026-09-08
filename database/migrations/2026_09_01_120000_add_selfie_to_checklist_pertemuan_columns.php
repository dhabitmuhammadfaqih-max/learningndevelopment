<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bukti selfie untuk masing-masing checklist "sudah bertemu &
     * evaluasi" yang sudah ada (lihat migration
     * add_checklist_pertemuan_to_users_table &
     * add_checklist_pertemuan_pejabat_to_users_table). Selfie WAJIB
     * diambil langsung dari kamera perangkat (bukan upload
     * galeri/file manager) setiap kali checklist yang bersangkutan
     * DICENTANG - lihat EmployeeController::toggleChecklistPertemuan(),
     * OfficialController::toggleChecklistPertemuanSaya()/
     * toggleChecklistPertemuanPegawai(), &
     * SupervisorController::toggleChecklistPertemuanPejabat().
     *
     * Menyimpan PATH file (disk 'public'), bukan base64, supaya tidak
     * membengkakkan tabel users - lihat Storage::disk('public') di
     * masing-masing controller di atas.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'pegawai_konfirmasi_pertemuan_selfie')) {
                $table->string('pegawai_konfirmasi_pertemuan_selfie')->nullable()->after('pegawai_konfirmasi_pertemuan_at');
            }

            if (! Schema::hasColumn('users', 'penilai_konfirmasi_pertemuan_selfie')) {
                $table->string('penilai_konfirmasi_pertemuan_selfie')->nullable()->after('penilai_konfirmasi_pertemuan_at');
            }

            if (! Schema::hasColumn('users', 'pejabat_konfirmasi_pertemuan_selfie')) {
                $table->string('pejabat_konfirmasi_pertemuan_selfie')->nullable()->after('pejabat_konfirmasi_pertemuan_at');
            }

            if (! Schema::hasColumn('users', 'atasan_konfirmasi_pertemuan_selfie')) {
                $table->string('atasan_konfirmasi_pertemuan_selfie')->nullable()->after('atasan_konfirmasi_pertemuan_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach ([
                'pegawai_konfirmasi_pertemuan_selfie',
                'penilai_konfirmasi_pertemuan_selfie',
                'pejabat_konfirmasi_pertemuan_selfie',
                'atasan_konfirmasi_pertemuan_selfie',
            ] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

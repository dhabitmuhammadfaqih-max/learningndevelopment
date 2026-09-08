<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan penanda jenis bukti checklist "sudah bertemu &
     * evaluasi": 'upload' (file dipilih dari perangkat) atau 'selfie'
     * (foto langsung dari kamera). Kolom PATH file/selfie TETAP
     * memakai kolom existing (*_konfirmasi_pertemuan_selfie, lihat
     * migration add_selfie_to_checklist_pertemuan_columns) - baik
     * untuk upload maupun selfie, supaya tidak perlu tabel/kolom path
     * baru. Kolom ini hanya menyimpan METODE-nya, dipakai HRD untuk
     * menampilkan label "Bukti: Upload File" / "Bukti: Selfie" - lihat
     * resources/views/admin/detail.blade.php &
     * resources/views/partials/checklist-selfie-toggle.blade.php.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'pegawai_konfirmasi_pertemuan_evidence_type')) {
                $table->string('pegawai_konfirmasi_pertemuan_evidence_type')->nullable()->after('pegawai_konfirmasi_pertemuan_selfie');
            }

            if (! Schema::hasColumn('users', 'penilai_konfirmasi_pertemuan_evidence_type')) {
                $table->string('penilai_konfirmasi_pertemuan_evidence_type')->nullable()->after('penilai_konfirmasi_pertemuan_selfie');
            }

            if (! Schema::hasColumn('users', 'pejabat_konfirmasi_pertemuan_evidence_type')) {
                $table->string('pejabat_konfirmasi_pertemuan_evidence_type')->nullable()->after('pejabat_konfirmasi_pertemuan_selfie');
            }

            if (! Schema::hasColumn('users', 'atasan_konfirmasi_pertemuan_evidence_type')) {
                $table->string('atasan_konfirmasi_pertemuan_evidence_type')->nullable()->after('atasan_konfirmasi_pertemuan_selfie');
            }
        });

        // Data lama (sebelum fitur ini ada) semuanya berasal dari alur
        // selfie kamera - tandai retroaktif supaya HRD tetap melihat
        // label yang benar untuk data existing, bukan kosong.
        Schema::table('users', function (Blueprint $table) {
            //
        });

        \DB::table('users')->whereNotNull('pegawai_konfirmasi_pertemuan_selfie')->whereNull('pegawai_konfirmasi_pertemuan_evidence_type')->update(['pegawai_konfirmasi_pertemuan_evidence_type' => 'selfie']);
        \DB::table('users')->whereNotNull('penilai_konfirmasi_pertemuan_selfie')->whereNull('penilai_konfirmasi_pertemuan_evidence_type')->update(['penilai_konfirmasi_pertemuan_evidence_type' => 'selfie']);
        \DB::table('users')->whereNotNull('pejabat_konfirmasi_pertemuan_selfie')->whereNull('pejabat_konfirmasi_pertemuan_evidence_type')->update(['pejabat_konfirmasi_pertemuan_evidence_type' => 'selfie']);
        \DB::table('users')->whereNotNull('atasan_konfirmasi_pertemuan_selfie')->whereNull('atasan_konfirmasi_pertemuan_evidence_type')->update(['atasan_konfirmasi_pertemuan_evidence_type' => 'selfie']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach ([
                'pegawai_konfirmasi_pertemuan_evidence_type',
                'penilai_konfirmasi_pertemuan_evidence_type',
                'pejabat_konfirmasi_pertemuan_evidence_type',
                'atasan_konfirmasi_pertemuan_evidence_type',
            ] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Perbaikan untuk migration 2026_08_14_140000
     * (rename_karyawan_columns_to_pegawai_in_signature_documents_table).
     *
     * Migration itu punya bug: kondisi Schema::hasColumn()-nya membanding-
     * kan nama kolom 'pegawai_*' dengan dirinya sendiri (harusnya dengan
     * 'karyawan_*'), jadi kondisinya selalu false dan renameColumn() TIDAK
     * PERNAH benar-benar dijalankan - walau migration itu sudah tercatat
     * "selesai" di tabel migrations, kolom di database sebenarnya masih
     * bernama karyawan_nama/karyawan_jabatan/karyawan_signature/
     * karyawan_signed_at, bukan pegawai_*, sehingga kode yang sudah
     * memakai nama kolom baru (mis. HrdController::referencedStoragePaths())
     * gagal dengan error "Unknown column 'pegawai_signature'".
     *
     * Migration BARU ini (bukan mengedit file lama - migration yang
     * sudah tercatat jalan tidak boleh diubah isinya) melakukan rename
     * yang SEHARUSNYA terjadi, dengan kondisi yang benar.
     */
    public function up(): void
    {
        Schema::table('signature_documents', function (Blueprint $table) {
            if (Schema::hasColumn('signature_documents', 'karyawan_nama')
                && ! Schema::hasColumn('signature_documents', 'pegawai_nama')) {
                $table->renameColumn('karyawan_nama', 'pegawai_nama');
            }

            if (Schema::hasColumn('signature_documents', 'karyawan_jabatan')
                && ! Schema::hasColumn('signature_documents', 'pegawai_jabatan')) {
                $table->renameColumn('karyawan_jabatan', 'pegawai_jabatan');
            }

            if (Schema::hasColumn('signature_documents', 'karyawan_signature')
                && ! Schema::hasColumn('signature_documents', 'pegawai_signature')) {
                $table->renameColumn('karyawan_signature', 'pegawai_signature');
            }

            if (Schema::hasColumn('signature_documents', 'karyawan_signed_at')
                && ! Schema::hasColumn('signature_documents', 'pegawai_signed_at')) {
                $table->renameColumn('karyawan_signed_at', 'pegawai_signed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('signature_documents', function (Blueprint $table) {
            if (Schema::hasColumn('signature_documents', 'pegawai_nama')) {
                $table->renameColumn('pegawai_nama', 'karyawan_nama');
            }

            if (Schema::hasColumn('signature_documents', 'pegawai_jabatan')) {
                $table->renameColumn('pegawai_jabatan', 'karyawan_jabatan');
            }

            if (Schema::hasColumn('signature_documents', 'pegawai_signature')) {
                $table->renameColumn('pegawai_signature', 'karyawan_signature');
            }

            if (Schema::hasColumn('signature_documents', 'pegawai_signed_at')) {
                $table->renameColumn('pegawai_signed_at', 'karyawan_signed_at');
            }
        });
    }
};

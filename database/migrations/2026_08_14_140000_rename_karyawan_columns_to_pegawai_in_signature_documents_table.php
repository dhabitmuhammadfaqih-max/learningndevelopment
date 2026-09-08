<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Selaraskan istilah "karyawan" -> "pegawai" di fitur tanda tangan
     * dokumen (signature_documents), supaya konsisten dengan istilah yang
     * sudah dipakai di seluruh aplikasi (lihat rename_karyawan_role_to_pegawai
     * dan rename_admin_role_to_hrd).
     */
    public function up(): void
    {
        Schema::table('signature_documents', function (Blueprint $table) {
            if (Schema::hasColumn('signature_documents', 'pegawai_nama')
                && ! Schema::hasColumn('signature_documents', 'pegawai_nama')) {
                $table->renameColumn('pegawai_nama', 'pegawai_nama');
            }

            if (Schema::hasColumn('signature_documents', 'pegawai_jabatan')
                && ! Schema::hasColumn('signature_documents', 'pegawai_jabatan')) {
                $table->renameColumn('pegawai_jabatan', 'pegawai_jabatan');
            }

            if (Schema::hasColumn('signature_documents', 'pegawai_signature')
                && ! Schema::hasColumn('signature_documents', 'pegawai_signature')) {
                $table->renameColumn('pegawai_signature', 'pegawai_signature');
            }

            if (Schema::hasColumn('signature_documents', 'pegawai_signed_at')
                && ! Schema::hasColumn('signature_documents', 'pegawai_signed_at')) {
                $table->renameColumn('pegawai_signed_at', 'pegawai_signed_at');
            }
        });

        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            // SQLite: kolom "status" dibuat dengan CHECK constraint bawaan
            // Laravel untuk enum, dan tidak bisa diubah langsung. Solusinya
            // sama seperti rename_karyawan_role_to_pegawai: tambah kolom
            // baru tanpa constraint, pindahkan datanya, lalu ganti nama.
            Schema::table('signature_documents', function (Blueprint $table) {
                $table->string('status_new', 20)->nullable()->after('status');
            });

            DB::statement(
                "UPDATE signature_documents SET status_new = CASE WHEN status = 'karyawan_signed' THEN 'pegawai_signed' ELSE status END"
            );

            Schema::table('signature_documents', function (Blueprint $table) {
                $table->dropColumn('status');
            });

            Schema::table('signature_documents', function (Blueprint $table) {
                $table->renameColumn('status_new', 'status');
            });
        } else {
            // MySQL/MariaDB/Postgres: ganti tipe kolom jadi varchar dulu
            // (menghapus constraint ENUM lama), baru update datanya.
            Schema::table('signature_documents', function (Blueprint $table) {
                $table->string('status', 20)->default('draft')->change();
            });

            DB::table('signature_documents')
                ->where('status', 'karyawan_signed')
                ->update(['status' => 'pegawai_signed']);
        }
    }

    public function down(): void
    {
        DB::table('signature_documents')
            ->where('status', 'pegawai_signed')
            ->update(['status' => 'karyawan_signed']);

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

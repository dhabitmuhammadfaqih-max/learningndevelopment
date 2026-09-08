<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Versi pejabat dari checklist "sudah bertemu & evaluasi" yang
        // sudah ada untuk pegawai (lihat migration
        // add_checklist_pertemuan_to_users_table). Dipakai untuk siklus
        // penilaian PEJABAT (OfficialEvaluation/OfficialSupervisorFeedback),
        // independen dari checklist pegawai di atas:
        // - pejabat_konfirmasi_pertemuan_at: milik PEJABAT yang sedang
        //   dinilai sendiri. Lihat User::pejabatSudahKonfirmasiPertemuan()
        //   & OfficialController::toggleChecklistPertemuanSaya().
        // - atasan_konfirmasi_pertemuan_at: milik ATASAN (users.supervisor_id
        //   pejabat tsb) yang menilai lewat OfficialEvaluation. Disimpan di
        //   baris pejabat (bukan baris atasan), sama pola-nya seperti
        //   penilai_konfirmasi_pertemuan_at - satu pejabat hanya punya satu
        //   Atasan yang ditugaskan pada satu waktu. Lihat
        //   User::atasanSudahKonfirmasiPertemuan() &
        //   SupervisorController::toggleChecklistPertemuanPejabat().
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'pejabat_konfirmasi_pertemuan_at')) {
                $table->timestamp('pejabat_konfirmasi_pertemuan_at')->nullable()->after('penilai_konfirmasi_pertemuan_at');
            }

            if (! Schema::hasColumn('users', 'atasan_konfirmasi_pertemuan_at')) {
                $table->timestamp('atasan_konfirmasi_pertemuan_at')->nullable()->after('pejabat_konfirmasi_pertemuan_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['pejabat_konfirmasi_pertemuan_at', 'atasan_konfirmasi_pertemuan_at'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

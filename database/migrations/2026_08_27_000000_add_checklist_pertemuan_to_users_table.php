<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Checklist "sudah bertemu & evaluasi" - independen dari
        // kehadiran_diisi_at (HRD) dan dari form Penilaian/Tanggapan yang
        // sudah ada. Pegawai & Penilai (users.supervisor_id pegawai
        // tsb) masing-masing punya tombol centang sendiri, bisa
        // dicentang/dibatalkan kapan saja setelah mereka bertemu
        // langsung untuk membahas evaluasi. Dipakai sebagai salah satu
        // syarat sebelum HRD boleh mencetak PDF - lihat
        // User::checklistPertemuanLengkap() & HrdController::pdf().
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'pegawai_konfirmasi_pertemuan_at')) {
                $table->timestamp('pegawai_konfirmasi_pertemuan_at')->nullable()->after('kehadiran_diisi_at');
            }

            if (! Schema::hasColumn('users', 'penilai_konfirmasi_pertemuan_at')) {
                $table->timestamp('penilai_konfirmasi_pertemuan_at')->nullable()->after('pegawai_konfirmasi_pertemuan_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['pegawai_konfirmasi_pertemuan_at', 'penilai_konfirmasi_pertemuan_at'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

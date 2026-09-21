<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Arti kolom korelasi_assignments sempat terbalik saat fitur Atur
     * Korelasi diuji: penugasan yang dibuat lewat halaman Atur Korelasi
     * tercatat sebagai "akun yang diatur MEMBERI tanggapan ke yang
     * dicentang". Arti yang benar (sekarang): yang dicentang MEMBERI
     * tanggapan ke akun yang diatur (reviewer_id = pemberi, target_id =
     * akun yang diatur/ditanggapi).
     *
     * Baris hasil penugasan manual (assigned_by terisi) tidak bisa
     * dibedakan arahnya secara otomatis, jadi dihapus - Penilai/Atasan
     * tinggal mencentang ulang lewat Atur Korelasi. Baris hasil backfill
     * dari tanggapan yang sudah terkirim (assigned_by = null) arahnya
     * sudah benar dan TIDAK disentuh.
     */
    public function up(): void
    {
        DB::table('korelasi_assignments')
            ->whereNotNull('assigned_by')
            ->delete();
    }

    public function down(): void
    {
        // Tidak ada yang bisa dikembalikan.
    }
};

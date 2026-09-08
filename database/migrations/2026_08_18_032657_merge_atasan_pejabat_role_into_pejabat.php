<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Role "atasan_pejabat" dihapus sebagai indikator/role terpisah.
     *
     * Alasan: seorang pejabat sudah bisa punya "Atasan Penilai"
     * (users.supervisor_id) yang juga ber-role pejabat — itulah yang
     * dulu diwakili oleh role atasan_pejabat. Sekarang keduanya cukup
     * memakai role "pejabat" yang sama, dan level di atasnya (atasan
     * dari atasan) otomatis boleh ikut menilai pegawai yang berada di
     * bawah pejabat yang ia awasi (lihat OfficialController::canEvaluate()).
     *
     * Semua akun yang sebelumnya ber-role atasan_pejabat diubah jadi
     * pejabat. Relasi supervisor_id yang sudah ada TIDAK diubah — jadi
     * pola "pejabat dinilai oleh pejabat lain (dulu atasan_pejabat)"
     * tetap sama persis, hanya nama role-nya yang disatukan.
     */
    public function up(): void
    {
        DB::table('users')->where('role', 'atasan_pejabat')->update(['role' => 'pejabat']);
    }

    public function down(): void
    {
        // Tidak bisa dikembalikan dengan aman: setelah digabung, tidak ada
        // cara membedakan mana akun pejabat yang dulunya atasan_pejabat.
    }
};

<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Storage;

/**
 * Dipakai oleh semua controller yang punya alur "tanda tangan" (feedback,
 * tanggapan pegawai, penilaian pejabat/atasan, pengesahan HRD, dsb).
 *
 * Sebelumnya tiap alur ini minta user menggambar tanda tangan baru lewat
 * canvas setiap kali submit. Sekarang tanda tangan cukup disimpan SEKALI
 * di akun user (lihat User::signature_path, diisi lewat
 * AccountSignatureController), dan setiap alur tanda tangan tinggal
 * menyalin file itu ke path baru khusus dokumen yang bersangkutan.
 *
 * Kenapa DISALIN (bukan cuma referensi path yang sama) ke file baru per
 * dokumen: supaya kalau suatu saat user memperbarui tanda tangan
 * akunnya, dokumen-dokumen yang SUDAH ditandatangani sebelumnya tidak
 * ikut berubah tampilannya - tetap jadi jejak audit yang sah untuk versi
 * dokumen saat itu ditandatangani.
 */
class AccountSignature
{
    /**
     * Salin tanda tangan akun milik $user ke path baru dengan prefix yang
     * diberikan (mis. "evaluation_response_123"). Return path barunya,
     * atau null kalau user belum punya tanda tangan tersimpan sama sekali
     * (seharusnya tidak terjadi karena modal wajib mengisi di awal, tapi
     * tetap dijaga di sini sebagai lapisan aman terakhir).
     */
    public static function copyFor(User $user, string $prefix): ?string
    {
        if (! $user->hasSavedSignature()) {
            return null;
        }

        $newPath = 'signatures/' . $prefix . '_' . time() . '_' . $user->id . '.png';

        Storage::disk('public')->put(
            $newPath,
            Storage::disk('public')->get($user->signature_path)
        );

        return $newPath;
    }
}

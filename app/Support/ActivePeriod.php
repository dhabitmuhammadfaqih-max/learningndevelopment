<?php

namespace App\Support;

use App\Models\AppSetting;

/**
 * Sumber kebenaran TUNGGAL untuk "tahun penilaian yang sedang aktif".
 *
 * SEBELUM ada class ini: setiap scopeTahunAktif() di 4 model
 * (Evaluation, OfficialEvaluation, SupervisorFeedback,
 * OfficialSupervisorFeedback) + beberapa method di User.php pakai
 * `now()->year` langsung. Masalahnya: siklus penilaian kadang baru
 * kelar Februari tahun berikutnya (proses tanggapan, checklist
 * pertemuan, dst belum semua selesai) - begitu kalender ganti ke 1
 * Januari, `now()->year` otomatis lompat ke tahun baru padahal HRD
 * masih proses menyelesaikan tahun sebelumnya. Akibatnya pegawai/pejabat
 * yang buka dashboard di bulan Januari-Februari itu akan melihat "tahun
 * aktif"-nya sudah kosong (tahun baru, belum ada data), padahal
 * seharusnya masih menyelesaikan tahun lalu dulu.
 *
 * SEKARANG: tahun aktif disimpan eksplisit di tabel app_settings
 * (key 'active_evaluation_year'), diubah manual oleh HRD lewat halaman
 * Pengaturan Periode (lihat SettingsController) begitu siklus penilaian
 * tahun tersebut BENAR-BENAR selesai - bukan otomatis ikut kalender.
 *
 * Kalau belum pernah di-set sama sekali (instalasi baru / migration
 * baru jalan pertama kali), fallback ke now()->year supaya perilaku
 * lama tetap jalan tanpa perlu setup manual dulu.
 */
class ActivePeriod
{
    public const SETTING_KEY = 'active_evaluation_year';

    public static function year(): int
    {
        $value = AppSetting::get(self::SETTING_KEY);

        return $value !== null ? (int) $value : now()->year;
    }

    public static function setYear(int $year): void
    {
        AppSetting::set(self::SETTING_KEY, (string) $year);
    }
}

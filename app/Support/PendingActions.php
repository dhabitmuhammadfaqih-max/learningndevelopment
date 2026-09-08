<?php

namespace App\Support;

use App\Models\Evaluation;
use App\Models\OfficialEvaluation;
use App\Models\OfficialSupervisorFeedback;
use App\Models\SupervisorFeedback;
use App\Models\User;

/**
 * Menghitung jumlah item yang "bisa ditanggapi/dinilai" per menu sidebar,
 * untuk akun yang sedang login. Dipakai di
 * resources/views/components/dashboard-layout.blade.php supaya badge
 * notifikasi di sidebar konsisten di semua halaman tanpa perlu di-passing
 * manual dari tiap controller.
 *
 * Semua query di sini SENGAJA meniru syarat/logika yang sudah ada di
 * masing-masing controller (EmployeeController, OfficialController,
 * SupervisorController, HrdController) supaya angka badge tidak pernah
 * menyimpang dari apa yang sebenarnya bisa dikerjakan di halaman terkait.
 * Kalau syarat di salah satu controller itu berubah, sesuaikan juga di sini.
 */
class PendingActions
{
    /**
     * Return: array nama-route => jumlah (int). Route yang tidak relevan
     * untuk role akun ini tidak akan muncul di array (dianggap 0 di view).
     */
    public static function forUser(?User $user): array
    {
        if (! $user) {
            return [];
        }

        return match ($user->role) {
            'pegawai' => self::forPegawai($user),
            'pejabat' => self::forPejabat($user),
            'hrd'     => self::forHrd(),
            default   => [],
        };
    }

    /**
     * Pegawai: badge di "Dashboard Saya" kalau penilaian tahun berjalan
     * sudah bisa ditanggapi (PEJABAT & ATASAN PEJABAT sama-sama sudah
     * menilai) tapi belum ditanggapi. Syarat sama dengan
     * EmployeeController::respondEvaluation()/index().
     */
    private static function forPegawai(User $user): array
    {
        $evaluation = Evaluation::where('employee_id', $user->id)
            ->tahunAktif()
            ->first();

        $atasanPejabatSudahMenilai = SupervisorFeedback::where('employee_id', $user->id)
            ->tahunAktif()
            ->exists();

        $pending = ($evaluation && ! $evaluation->employee_response && $atasanPejabatSudahMenilai)
            ? 1
            : 0;

        return ['employee.dashboard' => $pending];
    }

    /**
     * Pejabat: badge di "Dashboard Saya" untuk total pegawai/pejabat
     * binaan yang menunggu dinilai atau menunggu Tanggapan Atasan, dan
     * badge di "Nilai Saya" untuk penilaian milik pejabat ini sendiri yang
     * sudah siap ditanggapi. Syarat sama dengan OfficialController::index()/
     * myEvaluations() & SupervisorController.
     */
    private static function forPejabat(User $user): array
    {
        // 1) Pegawai yang ditugaskan (langsung, atau via atasan-dari-atasan)
        //    ke pejabat ini, korelasinya sudah cukup & kehadiran HRD sudah
        //    diisi (siapDinilaiPenilai()), tapi belum dinilai tahun ini.
        $pegawaiPerluDinilai = User::where('role', 'pegawai')
            ->where(function ($query) use ($user) {
                $query->whereNull('atasan_pejabat_id')
                    ->orWhere('atasan_pejabat_id', '!=', $user->id);
            })
            ->where(function ($query) use ($user) {
                $query->where('supervisor_id', $user->id)
                    ->orWhereHas('supervisor', function ($query) use ($user) {
                        $query->where('supervisor_id', $user->id);
                    });
            })
            ->withCount('feedbacksReceived')
            ->whereDoesntHave('evaluations', function ($query) use ($user) {
                $query->where('official_id', $user->id)->tahunAktif();
            })
            ->get()
            ->filter(fn ($employee) => $employee->siapDinilaiPenilai())
            ->count();

        // 2) Pegawai yang pejabat ini jadi Atasan Penilai-nya
        //    (atasan_pejabat_id), sudah dinilai penilai langsungnya tahun
        //    ini, tapi Tanggapan Atasan dari pejabat ini belum diisi.
        $pegawaiPerluTanggapanAtasan = User::where('role', 'pegawai')
            ->where('atasan_pejabat_id', $user->id)
            ->whereHas('evaluations', function ($query) {
                $query->tahunAktif();
            })
            ->whereDoesntHave('supervisorFeedbacks', function ($query) use ($user) {
                $query->where('supervisor_id', $user->id)->tahunAktif();
            })
            ->count();

        // 3) Pejabat binaan (supervisor_id) yang korelasinya sudah cukup
        //    (MIN_TANGGAPAN_KORELASI_PEJABAT) tapi belum dinilai tahun ini.
        $pejabatPerluDinilai = User::where('role', 'pejabat')
            ->where('supervisor_id', $user->id)
            ->withCount('feedbacksReceived')
            ->whereDoesntHave('officialEvaluations', function ($query) use ($user) {
                $query->where('supervisor_id', $user->id)->tahunAktif();
            })
            ->get()
            ->filter(fn ($pejabat) => $pejabat->korelasiPejabatSudahMemberiTanggapan())
            ->count();

        // 4) Pejabat yang pejabat ini jadi Atasan Penilai-nya
        //    (atasan_penilai_pejabat_id), sudah dinilai atasannya tahun
        //    ini, tapi Tanggapan Atasan Pejabat dari akun ini belum diisi.
        $pejabatPerluTanggapanAtasan = User::where('role', 'pejabat')
            ->where('atasan_penilai_pejabat_id', $user->id)
            ->whereHas('officialEvaluations', function ($query) {
                $query->tahunAktif();
            })
            ->whereDoesntHave('officialSupervisorFeedbacks', function ($query) use ($user) {
                $query->where('supervisor_id', $user->id)->tahunAktif();
            })
            ->count();

        $dashboardPending = $pegawaiPerluDinilai
            + $pegawaiPerluTanggapanAtasan
            + $pejabatPerluDinilai
            + $pejabatPerluTanggapanAtasan;

        // "Nilai Saya": penilaian milik akun ini sendiri (sebagai pejabat
        // yang dinilai), sudah siap ditanggapi (Atasan Pejabat sudah
        // memberi Tanggapan Atasan) tapi belum ditanggapi tahun ini.
        $myEvaluation = OfficialEvaluation::where('official_id', $user->id)
            ->where('supervisor_id', $user->supervisor_id)
            ->tahunAktif()
            ->first();

        $atasanPejabatSudahMenilai = OfficialSupervisorFeedback::where('official_id', $user->id)
            ->tahunAktif()
            ->exists();

        $myEvaluationPending = ($myEvaluation && ! $myEvaluation->employee_response && $atasanPejabatSudahMenilai)
            ? 1
            : 0;

        return [
            'official.dashboard'      => $dashboardPending,
            'official.my-evaluations' => $myEvaluationPending,
        ];
    }

    /**
     * HRD tidak menilai/menanggapi - "perlu dikerjakan"-nya HRD adalah
     * menandatangani penilaian pegawai/pejabat yang pegawai/pejabatnya
     * sendiri sudah menanggapi tapi HRD belum tanda tangan tahun ini.
     * Lihat HrdController::signAsHrd()/signAsHrdOfficial().
     */
    private static function forHrd(): array
    {
        $employeePending = Evaluation::whereNotNull('employee_response')
            ->whereNull('hrd_id')
            ->tahunAktif()
            ->count();

        $officialPending = OfficialEvaluation::whereNotNull('employee_response')
            ->whereNull('hrd_id')
            ->tahunAktif()
            ->count();

        return [
            'admin.employees' => $employeePending,
            'admin.officials' => $officialPending,
        ];
    }
}

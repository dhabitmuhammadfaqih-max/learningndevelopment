<?php

namespace App\Services;

use App\Jobs\SendSiapDinilaiNotification;
use App\Jobs\SendTriggeredFcmNotification;
use App\Models\FcmNotificationLog;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Service untuk menentukan kapan dan kepada siapa notifikasi FCM dikirim.
 *
 * Dipisah dari controller agar logika trigger tidak tersebar di banyak file.
 * Controller cukup memanggil satu method dari service ini setelah data utama
 * berhasil disimpan.
 */
class NotificationTriggerService
{
    /**
     * Cek apakah $employee sekarang siap dinilai penilai, dan jika ya,
     * dispatch job untuk mengirim notifikasi ke supervisor-nya.
     *
     * Dipanggil dari:
     * - HrdController::updateAttendance() — setelah kehadiran diisi
     * - EmployeeController::feedback()    — setelah feedback korelasi tersimpan
     *
     * Method ini TIDAK melempar exception ke caller - semua error di-log
     * dan proses bisnis caller tetap berhasil.
     */
    public function triggerSiapDinilaiPenilaiJikaPerlu(User $employee): void
    {
        try {
            // Reload fresh dari DB agar nilai kehadiran_diisi_at &
            // feedbacks_received_count yang baru saja disimpan terbaca.
            $employee->refresh();

            // Cek kondisi utama
            if (! $employee->siapDinilaiPenilai()) {
                return;
            }

            // Pastikan supervisor ada
            $supervisor = $employee->supervisor;

            if (! $supervisor) {
                Log::warning('FCM SiapDinilai trigger: pegawai tidak punya supervisor, notifikasi tidak dikirim.', [
                    'employee_id' => $employee->id,
                ]);

                return;
            }

            // Dispatch job ke queue - FCM dikirim secara async.
            // Jika queue belum berjalan (php artisan queue:work), job
            // tetap tersimpan di tabel jobs dan akan diproses saat worker jalan.
            // Duplicate check ada di dalam job itu sendiri.
            SendSiapDinilaiNotification::dispatch(
                $employee->id,
                $supervisor->id,
            );

            Log::info('FCM SiapDinilai trigger: job dispatched.', [
                'employee_id'   => $employee->id,
                'supervisor_id' => $supervisor->id,
            ]);
        } catch (\Throwable $e) {
            // Jangan sampai error notifikasi merusak proses utama
            Log::error('FCM SiapDinilai trigger: exception saat dispatch job.', [
                'employee_id' => $employee->id,
                'error'       => $e->getMessage(),
            ]);
        }
    }

    /**
     * Dispatcher generik dipanggil dari HrdController::updateAttendance() -
     * satu route yang sama dipakai untuk mengisi kehadiran pegawai MAUPUN
     * pejabat, jadi trigger yang tepat (versi pegawai atau versi pejabat)
     * dipilih berdasarkan role $user.
     */
    public function triggerSiapDinilaiJikaPerlu(User $user): void
    {
        if ($user->role === 'pejabat') {
            $this->triggerSiapDinilaiPenilaiPejabatJikaPerlu($user);

            return;
        }

        $this->triggerSiapDinilaiPenilaiJikaPerlu($user);
    }

    /**
     * Versi pejabat dari triggerSiapDinilaiPenilaiJikaPerlu() di atas -
     * cek apakah $pejabat sekarang siap dinilai Atasan-nya
     * (User::siapDinilaiPenilaiPejabat()), dan jika ya, kirim notifikasi
     * ke Atasan (users.supervisor_id pejabat ini).
     *
     * Dipanggil dari:
     * - HrdController::updateAttendance() (lewat triggerSiapDinilaiJikaPerlu())
     * - OfficialController::feedback()    — setelah tanggapan korelasi antar pejabat tersimpan
     */
    public function triggerSiapDinilaiPenilaiPejabatJikaPerlu(User $pejabat): void
    {
        try {
            $pejabat->refresh();

            if (! $pejabat->siapDinilaiPenilaiPejabat()) {
                return;
            }

            $atasan = $pejabat->supervisor;

            if (! $atasan) {
                Log::warning('FCM SiapDinilaiPejabat trigger: pejabat tidak punya atasan, notifikasi tidak dikirim.', [
                    'official_id' => $pejabat->id,
                ]);

                return;
            }

            $this->dispatchTriggered(
                subjectUserId: $pejabat->id,
                recipientUserId: $atasan->id,
                type: FcmNotificationLog::TYPE_SIAP_DINILAI_PENILAI_PEJABAT,
                title: 'Penilaian Pejabat Baru Tersedia',
                body: "Ada pejabat ({$pejabat->name}) yang sudah dapat Anda nilai. Silakan buka halaman penilaian.",
                routeName: 'supervisor.official',
                routeParam: $pejabat->id,
                fallbackRouteName: 'official.dashboard',
            );
        } catch (\Throwable $e) {
            Log::error('FCM SiapDinilaiPejabat trigger: exception.', [
                'official_id' => $pejabat->id,
                'error'       => $e->getMessage(),
            ]);
        }
    }

    /**
     * Cek apakah Atasan Penilai (SupervisorFeedback) pegawai kini sudah
     * bisa mulai mengisi Tanggapan Atasan, karena Penilai baru saja
     * menyelesaikan Evaluation untuk pegawai ini di tahun berjalan, dan
     * jika ya, kirim notifikasi ke Atasan Penilai tsb
     * (users.atasan_pejabat_id pegawai ini).
     *
     * Dipanggil dari OfficialController::evaluate() — setelah Evaluation
     * berhasil disimpan.
     */
    public function triggerSiapTanggapanAtasanPenilaiJikaPerlu(User $employee): void
    {
        try {
            $employee->refresh();

            $atasanPenilai = $employee->atasanPejabat;

            if (! $atasanPenilai) {
                Log::warning('FCM SiapTanggapanAtasanPenilai trigger: pegawai tidak punya Atasan Penilai, notifikasi tidak dikirim.', [
                    'employee_id' => $employee->id,
                ]);

                return;
            }

            $this->dispatchTriggered(
                subjectUserId: $employee->id,
                recipientUserId: $atasanPenilai->id,
                type: FcmNotificationLog::TYPE_SIAP_TANGGAPAN_ATASAN_PENILAI,
                title: 'Tanggapan Atasan Sudah Bisa Diisi',
                body: "Penilai sudah menyelesaikan penilaian untuk {$employee->name}. Silakan isi Tanggapan Atasan.",
                routeName: 'official.employee.tanggapan',
                routeParam: $employee->id,
                fallbackRouteName: 'official.dashboard',
            );
        } catch (\Throwable $e) {
            Log::error('FCM SiapTanggapanAtasanPenilai trigger: exception.', [
                'employee_id' => $employee->id,
                'error'       => $e->getMessage(),
            ]);
        }
    }

    /**
     * Versi pejabat dari triggerSiapTanggapanAtasanPenilaiJikaPerlu() di
     * atas. Dipanggil dari SupervisorController::evaluateOfficial() —
     * setelah OfficialEvaluation berhasil disimpan.
     */
    public function triggerSiapTanggapanAtasanPenilaiPejabatJikaPerlu(User $pejabat): void
    {
        try {
            $pejabat->refresh();

            $atasanPenilai = $pejabat->atasanPenilaiPejabat;

            if (! $atasanPenilai) {
                Log::warning('FCM SiapTanggapanAtasanPenilaiPejabat trigger: pejabat tidak punya Atasan Penilai, notifikasi tidak dikirim.', [
                    'official_id' => $pejabat->id,
                ]);

                return;
            }

            $this->dispatchTriggered(
                subjectUserId: $pejabat->id,
                recipientUserId: $atasanPenilai->id,
                type: FcmNotificationLog::TYPE_SIAP_TANGGAPAN_ATASAN_PENILAI_PEJABAT,
                title: 'Tanggapan Atasan Sudah Bisa Diisi',
                body: "Atasan sudah menyelesaikan penilaian untuk {$pejabat->name}. Silakan isi Tanggapan Atasan.",
                routeName: 'official.pejabat.tanggapan',
                routeParam: $pejabat->id,
                fallbackRouteName: 'official.dashboard',
            );
        } catch (\Throwable $e) {
            Log::error('FCM SiapTanggapanAtasanPenilaiPejabat trigger: exception.', [
                'official_id' => $pejabat->id,
                'error'       => $e->getMessage(),
            ]);
        }
    }

    /**
     * Cek apakah pegawai kini sudah bisa mulai mengisi tanggapan
     * (Evaluation::employee_response) & checklist "sudah bertemu &
     * evaluasi" - lihat User::checklistPertemuanBolehDiisi() -
     * karena Atasan Penilai (SupervisorFeedback) baru saja
     * menyelesaikan tanggapannya, dan jika ya, kirim notifikasi ke
     * PEGAWAI itu sendiri.
     *
     * CATATAN: penerima notifikasi ini adalah pegawai sendiri (bukan
     * atasan/penilai), jadi $subjectUserId & $recipientUserId pada
     * FcmNotificationLog bernilai SAMA untuk jenis notifikasi ini -
     * lihat SendTriggeredFcmNotification.
     *
     * Dipanggil dari OfficialController::giveTanggapanPegawai() —
     * setelah SupervisorFeedback berhasil disimpan.
     */
    public function triggerSiapTanggapanChecklistPegawaiJikaPerlu(User $employee): void
    {
        try {
            $employee->refresh();

            if (! $employee->checklistPertemuanBolehDiisi()) {
                return;
            }

            $this->dispatchTriggered(
                subjectUserId: $employee->id,
                recipientUserId: $employee->id,
                type: FcmNotificationLog::TYPE_SIAP_TANGGAPAN_CHECKLIST_PEGAWAI,
                title: 'Tanggapan & Checklist Sudah Bisa Diisi',
                body: 'Atasan Penilai sudah memberikan tanggapan. Silakan isi tanggapan Anda dan centang checklist pertemuan.',
                routeName: 'employee.dashboard',
                routeParam: null,
                fallbackRouteName: 'employee.dashboard',
            );
        } catch (\Throwable $e) {
            Log::error('FCM SiapTanggapanChecklistPegawai trigger: exception.', [
                'employee_id' => $employee->id,
                'error'       => $e->getMessage(),
            ]);
        }
    }

    /**
     * Versi pejabat dari triggerSiapTanggapanChecklistPegawaiJikaPerlu()
     * di atas - lihat User::checklistPertemuanPejabatBolehDiisi().
     * Dipanggil dari OfficialController::giveTanggapanPejabat() —
     * setelah OfficialSupervisorFeedback berhasil disimpan.
     */
    public function triggerSiapTanggapanChecklistPejabatJikaPerlu(User $pejabat): void
    {
        try {
            $pejabat->refresh();

            if (! $pejabat->checklistPertemuanPejabatBolehDiisi()) {
                return;
            }

            $this->dispatchTriggered(
                subjectUserId: $pejabat->id,
                recipientUserId: $pejabat->id,
                type: FcmNotificationLog::TYPE_SIAP_TANGGAPAN_CHECKLIST_PEJABAT,
                title: 'Tanggapan & Checklist Sudah Bisa Diisi',
                body: 'Atasan Penilai sudah memberikan tanggapan. Silakan isi tanggapan Anda dan centang checklist pertemuan.',
                routeName: 'official.my-evaluations',
                routeParam: null,
                fallbackRouteName: 'official.dashboard',
            );
        } catch (\Throwable $e) {
            Log::error('FCM SiapTanggapanChecklistPejabat trigger: exception.', [
                'official_id' => $pejabat->id,
                'error'       => $e->getMessage(),
            ]);
        }
    }

    /**
     * Cek apakah PENILAI (users.supervisor_id pegawai ini) kini sudah
     * boleh mencentang checklist "sudah bertemu & evaluasi" miliknya
     * sendiri - lihat User::checklistPertemuanPenilaiBolehDiisi() -
     * karena Atasan Penilai (SupervisorFeedback) baru saja menyelesaikan
     * tanggapannya, dan jika ya, kirim notifikasi ke PENILAI tsb.
     *
     * Pasangan dari triggerSiapTanggapanChecklistPegawaiJikaPerlu() di
     * atas - dipanggil dari OfficialController::giveTanggapanPegawai()
     * juga, setelah SupervisorFeedback berhasil disimpan, supaya Penilai
     * ikut diberi tahu (bukan cuma pegawai) bahwa checklist-nya sudah
     * bisa dicentang.
     */
    public function triggerSiapChecklistPenilaiJikaPerlu(User $employee): void
    {
        try {
            $employee->refresh();

            if (! $employee->checklistPertemuanPenilaiBolehDiisi()) {
                return;
            }

            $penilai = $employee->supervisor;

            if (! $penilai) {
                Log::warning('FCM SiapChecklistPenilai trigger: pegawai tidak punya Penilai, notifikasi tidak dikirim.', [
                    'employee_id' => $employee->id,
                ]);

                return;
            }

            $this->dispatchTriggered(
                subjectUserId: $employee->id,
                recipientUserId: $penilai->id,
                type: FcmNotificationLog::TYPE_SIAP_CHECKLIST_PENILAI,
                title: 'Checklist Pertemuan Sudah Bisa Dicentang',
                body: "Atasan Penilai sudah memberikan tanggapan untuk {$employee->name}. Silakan centang checklist pertemuan Anda.",
                routeName: 'official.employee',
                routeParam: $employee->id,
                fallbackRouteName: 'official.dashboard',
            );
        } catch (\Throwable $e) {
            Log::error('FCM SiapChecklistPenilai trigger: exception.', [
                'employee_id' => $employee->id,
                'error'       => $e->getMessage(),
            ]);
        }
    }

    /**
     * Versi pejabat dari triggerSiapChecklistPenilaiJikaPerlu() di atas -
     * kirim notifikasi ke ATASAN (users.supervisor_id pejabat ini) bahwa
     * checklist miliknya sendiri sudah boleh dicentang, karena Atasan
     * Penilai pejabat (OfficialSupervisorFeedback) baru saja
     * menyelesaikan tanggapannya. Dipanggil dari
     * OfficialController::giveTanggapanPejabat().
     */
    public function triggerSiapChecklistAtasanPejabatJikaPerlu(User $pejabat): void
    {
        try {
            $pejabat->refresh();

            if (! $pejabat->checklistPertemuanPejabatBolehDiisi()) {
                return;
            }

            $atasan = $pejabat->supervisor;

            if (! $atasan) {
                Log::warning('FCM SiapChecklistAtasanPejabat trigger: pejabat tidak punya Atasan, notifikasi tidak dikirim.', [
                    'official_id' => $pejabat->id,
                ]);

                return;
            }

            $this->dispatchTriggered(
                subjectUserId: $pejabat->id,
                recipientUserId: $atasan->id,
                type: FcmNotificationLog::TYPE_SIAP_CHECKLIST_ATASAN_PEJABAT,
                title: 'Checklist Pertemuan Sudah Bisa Dicentang',
                body: "Atasan Penilai sudah memberikan tanggapan untuk {$pejabat->name}. Silakan centang checklist pertemuan Anda.",
                routeName: 'supervisor.official',
                routeParam: $pejabat->id,
                fallbackRouteName: 'official.dashboard',
            );
        } catch (\Throwable $e) {
            Log::error('FCM SiapChecklistAtasanPejabat trigger: exception.', [
                'official_id' => $pejabat->id,
                'error'       => $e->getMessage(),
            ]);
        }
    }

    /**
     * Beri tahu PEGAWAI itu sendiri bahwa Penilai baru saja menyimpan
     * Evaluation untuk dirinya, jadi nilainya sudah muncul/bisa dilihat.
     *
     * Berbeda dari trigger lain di service ini yang mengecek dulu apakah
     * suatu kondisi "siap" terpenuhi (mis. siapDinilaiPenilai()) - method
     * ini tidak perlu pengecekan tambahan karena dipanggil PERSIS setelah
     * Evaluation berhasil disimpan, jadi kondisinya sudah pasti terpenuhi.
     *
     * Dipanggil dari OfficialController::evaluate() — setelah Evaluation
     * berhasil disimpan oleh Penilai.
     */
    public function triggerNilaiMunculPegawai(User $employee, ?float $score = null): void
    {
        try {
            $body = $score !== null
                ? "Penilai sudah memberikan penilaian untuk Anda. Nilai akhir: {$score}."
                : 'Penilai sudah memberikan penilaian untuk Anda. Silakan cek hasilnya.';

            $this->dispatchTriggered(
                subjectUserId: $employee->id,
                recipientUserId: $employee->id,
                type: FcmNotificationLog::TYPE_NILAI_MUNCUL_PEGAWAI,
                title: 'Nilai Penilaian Sudah Muncul',
                body: $body,
                routeName: 'employee.dashboard',
                routeParam: null,
                fallbackRouteName: 'employee.dashboard',
            );
        } catch (\Throwable $e) {
            Log::error('FCM NilaiMunculPegawai trigger: exception.', [
                'employee_id' => $employee->id,
                'error'       => $e->getMessage(),
            ]);
        }
    }

    /**
     * Helper bersama untuk dispatch SendTriggeredFcmNotification, dengan
     * route yang dibungkus try/catch (nama route tertentu bisa saja belum
     * terdaftar di beberapa environment/versi) supaya kegagalan resolve
     * URL tidak menggagalkan pengiriman notifikasi.
     */
    private function dispatchTriggered(
        int $subjectUserId,
        int $recipientUserId,
        string $type,
        string $title,
        string $body,
        string $routeName,
        mixed $routeParam,
        string $fallbackRouteName,
    ): void {
        try {
            $url = $routeParam !== null ? route($routeName, $routeParam) : route($routeName);
        } catch (\Throwable) {
            try {
                $url = route($fallbackRouteName);
            } catch (\Throwable) {
                $url = null;
            }
        }

        SendTriggeredFcmNotification::dispatch(
            $subjectUserId,
            $recipientUserId,
            $type,
            $title,
            $body,
            $url,
        );

        Log::info('FCM Triggered: job dispatched.', [
            'subject_id'   => $subjectUserId,
            'recipient_id' => $recipientUserId,
            'type'         => $type,
        ]);
    }
}
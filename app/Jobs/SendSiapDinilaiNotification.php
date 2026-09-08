<?php

namespace App\Jobs;

use App\Models\FcmNotificationLog;
use App\Models\User;
use App\Services\FirebaseCloudMessagingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job: Kirim push notification FCM ke supervisor bahwa ada pegawai
 * yang siap dinilai (siapDinilaiPenilai() == true).
 *
 * Dijalankan lewat queue agar proses penyimpanan data utama (kehadiran
 * / feedback) tidak tertunda jika FCM lambat atau gagal. Proses bisnis
 * penilaian tetap berhasil walau job ini gagal.
 *
 * Flow pencegahan duplicate:
 * 1) Sebelum kirim, cek FcmNotificationLog - jika sudah ada, skip.
 * 2) Jika belum, tandai dulu (insert log) sebelum kirim FCM.
 *    Ini memastikan bahwa walau job dijalankan dua kali bersamaan
 *    (race condition), hanya satu yang bisa insert (unique constraint).
 * 3) Kirim FCM via FirebaseCloudMessagingService yang sudah ada.
 */
class SendSiapDinilaiNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Berapa kali job ini boleh dicoba ulang jika gagal.
     * Notifikasi bukan proses kritis, 3x sudah cukup.
     */
    public int $tries = 3;

    /**
     * Timeout per attempt (detik).
     */
    public int $timeout = 30;

    public function __construct(
        public readonly int $employeeId,
        public readonly int $supervisorId,
    ) {}

    public function handle(FirebaseCloudMessagingService $fcm): void
    {
        // --- 1. Guard: cek apakah notifikasi sudah pernah dikirim ---
        if (FcmNotificationLog::sudahDikirim(
            $this->employeeId,
            $this->supervisorId,
            FcmNotificationLog::TYPE_SIAP_DINILAI_PENILAI
        )) {
            Log::info('FCM SiapDinilai: notifikasi sudah pernah dikirim, skip.', [
                'employee_id'   => $this->employeeId,
                'supervisor_id' => $this->supervisorId,
            ]);

            return;
        }

        // --- 2. Load model ---
        $employee   = User::find($this->employeeId);
        $supervisor = User::find($this->supervisorId);

        if (! $employee || ! $supervisor) {
            Log::warning('FCM SiapDinilai: employee atau supervisor tidak ditemukan.', [
                'employee_id'   => $this->employeeId,
                'supervisor_id' => $this->supervisorId,
            ]);

            return;
        }

        // --- 3. Re-check kondisi (data bisa berubah sejak job di-dispatch) ---
        // Reload fresh dari DB supaya tidak pakai nilai yang sudah stale
        $employee->refresh();

        if (! $employee->siapDinilaiPenilai()) {
            Log::info('FCM SiapDinilai: kondisi siapDinilaiPenilai() sudah tidak terpenuhi saat job dijalankan, skip.', [
                'employee_id' => $this->employeeId,
            ]);

            return;
        }

        // --- 4. Tandai sudah dikirim SEBELUM kirim (cegah race condition) ---
        // Jika insert gagal karena unique constraint (job lain lebih dulu),
        // tidak perlu panic - itu memang yang diinginkan.
        try {
            FcmNotificationLog::tandaiSudahDikirim(
                $this->employeeId,
                $this->supervisorId,
                FcmNotificationLog::TYPE_SIAP_DINILAI_PENILAI
            );
        } catch (\Throwable $e) {
            // Unique constraint violation berarti notifikasi sudah ditandai
            // oleh job lain yang berjalan hampir bersamaan -> skip.
            Log::info('FCM SiapDinilai: gagal insert log (mungkin race condition), skip.', [
                'employee_id'   => $this->employeeId,
                'supervisor_id' => $this->supervisorId,
                'error'         => $e->getMessage(),
            ]);

            return;
        }

        // --- 5. Kirim FCM ---
        // Buat URL ke halaman penilaian pegawai ini (official.employee)
        // agar notifikasi bisa di-tap untuk langsung buka halaman.
        try {
            $url = route('official.employee', $this->employeeId);
        } catch (\Throwable) {
            $url = route('official.dashboard');
        }

        $result = $fcm->sendToUser(
            $supervisor,
            'Penilaian Baru Tersedia',
            "Ada pegawai ({$employee->name}) yang sudah dapat Anda nilai. Silakan buka halaman penilaian.",
            ['url' => $url]
        );

        Log::info('FCM SiapDinilai: notifikasi dikirim.', [
            'employee_id'   => $this->employeeId,
            'supervisor_id' => $this->supervisorId,
            'sent'          => $result['sent'],
            'failed'        => $result['failed'],
            'no_token'      => $result['no_token'],
        ]);
    }

    /**
     * Jika job gagal semua attempt, log error tanpa melempar exception
     * supaya proses bisnis lain tidak terdampak.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('FCM SiapDinilai: job gagal setelah semua attempt.', [
            'employee_id'   => $this->employeeId,
            'supervisor_id' => $this->supervisorId,
            'error'         => $exception->getMessage(),
        ]);
    }
}

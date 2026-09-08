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
 * Job generik untuk mengirim notifikasi FCM dari
 * NotificationTriggerService, dipakai oleh semua jenis trigger BARU
 * (siap dinilai pejabat, siap tanggapan atasan penilai, siap
 * tanggapan+checklist pegawai/pejabat - lihat
 * FcmNotificationLog::TYPE_*).
 *
 * Sengaja dibuat generik (bukan satu job per jenis notifikasi seperti
 * SendSiapDinilaiNotification) supaya menambah jenis notifikasi baru
 * berikutnya tidak perlu bikin class job baru lagi - cukup tambah
 * constant TYPE_* baru dan panggil method baru di
 * NotificationTriggerService.
 *
 * BEDA dari SendSiapDinilaiNotification: job ini TIDAK mengulang cek
 * kondisi bisnis (mis. siapDinilaiPenilai()) saat job dijalankan,
 * karena kondisinya sudah dicek sesaat sebelum dispatch (di
 * NotificationTriggerService, dalam request yang sama) dan tiap jenis
 * kondisinya beda-beda sehingga tidak praktis diserialize sebagai
 * closure. Race condition jangka pendek (data berubah lagi tepat di
 * antara dispatch & job jalan) dianggap dapat diterima untuk
 * notifikasi tambahan seperti ini - guard utama tetap ada di
 * FcmNotificationLog (dedupe) supaya user tidak menerima notifikasi
 * duplikat.
 */
class SendTriggeredFcmNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(
        // "Subjek" siklus penilaian (pegawai/pejabat yang sedang
        // dinilai) - disimpan di kolom employee_id pada
        // FcmNotificationLog, sama seperti job lama, supaya dedupe
        // tetap bekerja per subjek+penerima+jenis.
        public readonly int $subjectUserId,
        // Penerima notifikasi - disimpan di kolom supervisor_id pada
        // FcmNotificationLog. Untuk jenis notifikasi yang penerimanya
        // adalah si subjek sendiri (mis. "siap tanggapan & checklist
        // pegawai"), nilainya SAMA dengan $subjectUserId - lihat
        // catatan di NotificationTriggerService.
        public readonly int $recipientUserId,
        public readonly string $notificationType,
        public readonly string $title,
        public readonly string $body,
        public readonly ?string $url = null,
    ) {}

    public function handle(FirebaseCloudMessagingService $fcm): void
    {
        if (FcmNotificationLog::sudahDikirim($this->subjectUserId, $this->recipientUserId, $this->notificationType)) {
            Log::info('FCM Triggered: notifikasi sudah pernah dikirim, skip.', [
                'subject_id'   => $this->subjectUserId,
                'recipient_id' => $this->recipientUserId,
                'type'         => $this->notificationType,
            ]);

            return;
        }

        $recipient = User::find($this->recipientUserId);

        if (! $recipient) {
            Log::warning('FCM Triggered: penerima tidak ditemukan.', [
                'recipient_id' => $this->recipientUserId,
                'type'         => $this->notificationType,
            ]);

            return;
        }

        try {
            FcmNotificationLog::tandaiSudahDikirim(
                $this->subjectUserId,
                $this->recipientUserId,
                $this->notificationType
            );
        } catch (\Throwable $e) {
            // Unique constraint violation berarti notifikasi sudah
            // ditandai oleh job lain yang berjalan hampir bersamaan.
            Log::info('FCM Triggered: gagal insert log (mungkin race condition), skip.', [
                'subject_id'   => $this->subjectUserId,
                'recipient_id' => $this->recipientUserId,
                'type'         => $this->notificationType,
                'error'        => $e->getMessage(),
            ]);

            return;
        }

        $result = $fcm->sendToUser(
            $recipient,
            $this->title,
            $this->body,
            $this->url ? ['url' => $this->url] : []
        );

        Log::info('FCM Triggered: notifikasi dikirim.', [
            'subject_id'   => $this->subjectUserId,
            'recipient_id' => $this->recipientUserId,
            'type'         => $this->notificationType,
            'sent'         => $result['sent'],
            'failed'       => $result['failed'],
            'no_token'     => $result['no_token'],
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('FCM Triggered: job gagal setelah semua attempt.', [
            'subject_id'   => $this->subjectUserId,
            'recipient_id' => $this->recipientUserId,
            'type'         => $this->notificationType,
            'error'        => $exception->getMessage(),
        ]);
    }
}

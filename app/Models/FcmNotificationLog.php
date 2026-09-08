<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Log notifikasi FCM yang sudah dikirim.
 *
 * Dipakai untuk mencegah duplicate notification: sebelum kirim FCM,
 * cek apakah baris dengan (employee_id, supervisor_id, notification_type)
 * yang sama sudah ada. Jika sudah ada, skip pengiriman.
 *
 * @property int    $employee_id
 * @property int    $supervisor_id
 * @property string $notification_type
 * @property \Carbon\Carbon $sent_at
 */
class FcmNotificationLog extends Model
{
    public const TYPE_SIAP_DINILAI_PENILAI = 'siap_dinilai_penilai';

    /**
     * Pejabat siap dinilai oleh Atasan-nya (OfficialEvaluation) - versi
     * pejabat dari TYPE_SIAP_DINILAI_PENILAI. Dikirim ke Atasan
     * (users.supervisor_id pejabat ini). Lihat
     * NotificationTriggerService::triggerSiapDinilaiPenilaiPejabatJikaPerlu().
     */
    public const TYPE_SIAP_DINILAI_PENILAI_PEJABAT = 'siap_dinilai_penilai_pejabat';

    /**
     * Atasan Penilai (SupervisorFeedback) pegawai siap memberi tanggapan,
     * karena Penilai sudah menyelesaikan Evaluation untuk pegawai
     * tsb. Dikirim ke Atasan Penilai (users.atasan_pejabat_id pegawai
     * ini). Lihat
     * NotificationTriggerService::triggerSiapTanggapanAtasanPenilaiJikaPerlu().
     */
    public const TYPE_SIAP_TANGGAPAN_ATASAN_PENILAI = 'siap_tanggapan_atasan_penilai';

    /**
     * Versi pejabat dari TYPE_SIAP_TANGGAPAN_ATASAN_PENILAI - Atasan
     * Penilai pejabat (OfficialSupervisorFeedback) siap memberi
     * tanggapan, karena Atasan sudah menyelesaikan OfficialEvaluation
     * untuk pejabat tsb. Dikirim ke users.atasan_penilai_pejabat_id
     * pejabat ini.
     */
    public const TYPE_SIAP_TANGGAPAN_ATASAN_PENILAI_PEJABAT = 'siap_tanggapan_atasan_penilai_pejabat';

    /**
     * Pegawai siap mengisi tanggapan (Evaluation::employee_response) &
     * checklist "sudah bertemu & evaluasi", karena Atasan Penilai
     * (SupervisorFeedback) sudah menyelesaikan tanggapannya. Dikirim ke
     * pegawai itu sendiri - lihat catatan di
     * NotificationTriggerService::triggerSiapTanggapanChecklistPegawaiJikaPerlu()
     * soal employee_id/supervisor_id yang bernilai sama pada baris log
     * untuk jenis ini.
     */
    public const TYPE_SIAP_TANGGAPAN_CHECKLIST_PEGAWAI = 'siap_tanggapan_checklist_pegawai';

    /**
     * Versi pejabat dari TYPE_SIAP_TANGGAPAN_CHECKLIST_PEGAWAI - pejabat
     * siap mengisi tanggapan & checklist setelah Atasan Penilai pejabat
     * (OfficialSupervisorFeedback) menyelesaikan tanggapannya. Dikirim
     * ke pejabat itu sendiri.
     */
    public const TYPE_SIAP_TANGGAPAN_CHECKLIST_PEJABAT = 'siap_tanggapan_checklist_pejabat';

    /**
     * PENILAI (users.supervisor_id pegawai ini) sudah boleh mencentang
     * checklist "sudah bertemu & evaluasi" miliknya sendiri, karena
     * Atasan Penilai (SupervisorFeedback) baru saja menyelesaikan
     * tanggapannya. Dikirim ke Penilai - lihat
     * User::checklistPertemuanPenilaiBolehDiisi() &
     * NotificationTriggerService::triggerSiapChecklistPenilaiJikaPerlu().
     */
    public const TYPE_SIAP_CHECKLIST_PENILAI = 'siap_checklist_penilai';

    /**
     * Versi pejabat dari TYPE_SIAP_CHECKLIST_PENILAI - ATASAN
     * (users.supervisor_id pejabat ini) sudah boleh mencentang checklist
     * miliknya sendiri, karena Atasan Penilai pejabat
     * (OfficialSupervisorFeedback) baru saja menyelesaikan tanggapannya.
     */
    public const TYPE_SIAP_CHECKLIST_ATASAN_PEJABAT = 'siap_checklist_atasan_pejabat';

    protected $fillable = [
        'employee_id',
        'supervisor_id',
        'notification_type',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    /**
     * Cek apakah notifikasi jenis ini sudah pernah dikirim untuk
     * employee + supervisor tertentu.
     */
    public static function sudahDikirim(int $employeeId, int $supervisorId, string $type): bool
    {
        return self::where('employee_id', $employeeId)
            ->where('supervisor_id', $supervisorId)
            ->where('notification_type', $type)
            ->exists();
    }

    /**
     * Tandai bahwa notifikasi sudah dikirim (insert ke log).
     * Menggunakan firstOrCreate agar aman dari race condition
     * (insert kedua akan diabaikan karena unique constraint).
     */
    public static function tandaiSudahDikirim(int $employeeId, int $supervisorId, string $type): void
    {
        self::firstOrCreate([
            'employee_id'       => $employeeId,
            'supervisor_id'     => $supervisorId,
            'notification_type' => $type,
        ], [
            'sent_at' => now(),
        ]);
    }
}

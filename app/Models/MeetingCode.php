<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

/**
 * Kode pertemuan sekali-pakai - bukti checklist "sudah bertemu &
 * evaluasi" metode OFFLINE, menggantikan selfie kamera.
 *
 * Kenapa ini bisa jadi bukti pertemuan: kode HANYA ditampilkan di layar
 * pihak yang menilai (Penilai/Atasan) dan TIDAK PERNAH dikirim sistem
 * ke pihak yang dinilai - tidak lewat notifikasi, email, maupun
 * dashboard. Satu-satunya cara pegawai/pejabat tahu kodenya adalah
 * dibacakan/ditunjukkan langsung saat mereka benar-benar berhadapan.
 * Masa berlaku singkat (VALID_MINUTES) menutup celah "dikirim lewat
 * WhatsApp nanti sore".
 *
 * Lihat migration create_meeting_codes_table untuk alur lengkapnya.
 */
class MeetingCode extends Model
{
    use HasFactory;

    /**
     * Masa berlaku kode sejak di-generate. Sengaja pendek - kode ini
     * mewakili "kita sedang duduk bersama SEKARANG", bukan janji
     * pertemuan. Kalau kedaluwarsa, Penilai tinggal generate ulang
     * (tidak ada batas jumlah generate).
     */
    public const VALID_MINUTES = 15;

    /**
     * Panjang kode & karakter yang dipakai. Karakter ambigu saat
     * dibacakan/diketik SENGAJA dibuang: 0 vs O, 1 vs I vs L.
     */
    public const LENGTH = 6;
    public const CHARSET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

    /**
     * Siklus penilaian tempat kode ini berlaku - menentukan kolom
     * checklist mana di users yang akan dicentang.
     */
    public const CONTEXT_PEGAWAI = 'pegawai';
    public const CONTEXT_PEJABAT = 'pejabat';

    protected $fillable = [
        'code',
        'context',
        'subject_id',
        'issuer_id',
        'tahun',
        'expires_at',
        'used_at',
        'used_by',
    ];

    protected $casts = [
        'tahun'      => 'integer',
        'expires_at' => 'datetime',
        'used_at'    => 'datetime',
    ];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(User::class, 'subject_id');
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issuer_id');
    }

    public function sudahDipakai(): bool
    {
        return ! is_null($this->used_at);
    }

    public function sudahKedaluwarsa(): bool
    {
        return $this->expires_at->isPast();
    }

    public function masihBerlaku(): bool
    {
        return ! $this->sudahDipakai() && ! $this->sudahKedaluwarsa();
    }

    /**
     * Sisa waktu berlaku dalam DETIK (0 kalau sudah lewat) - dipakai
     * partial checklist untuk hitung mundur di layar Penilai.
     */
    public function sisaDetik(): int
    {
        return max(0, now()->diffInSeconds($this->expires_at, false));
    }

    /**
     * Buat kode baru untuk pasangan (subject, issuer) di tahun
     * tertentu.
     *
     * Kode lama milik pasangan yang sama yang masih hidup langsung
     * dimatikan (expires_at dimajukan ke sekarang) - supaya tidak ada
     * 2 kode aktif bersamaan untuk satu pegawai, yang bikin bingung
     * kalau penilai menekan generate dua kali.
     */
    public static function issue(User $subject, User $issuer, string $context, int $tahun): self
    {
        self::where('subject_id', $subject->id)
            ->where('context', $context)
            ->where('tahun', $tahun)
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->update(['expires_at' => now()]);

        // Undi ulang kalau kebetulan bentrok dengan kode lama yang
        // masih tersimpan di tabel (unique index). Batas percobaan
        // hanya jaring pengaman supaya tidak pernah jadi loop tak
        // terbatas - peluang bentrok 10x berturut-turut praktis nol.
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $code = self::randomCode();

            if (self::where('code', $code)->exists()) {
                continue;
            }

            return self::create([
                'code'       => $code,
                'context'    => $context,
                'subject_id' => $subject->id,
                'issuer_id'  => $issuer->id,
                'tahun'      => $tahun,
                'expires_at' => now()->addMinutes(self::VALID_MINUTES),
            ]);
        }

        throw ValidationException::withMessages([
            'meeting_code' => 'Gagal membuat kode pertemuan, silakan coba lagi.',
        ]);
    }

    /**
     * Kode yang MASIH berlaku untuk pasangan ini, kalau ada - dipakai
     * supaya kode tetap tampil di layar Penilai setelah halaman
     * di-refresh, tanpa perlu generate ulang.
     */
    public static function activeFor(int $subjectId, string $context, ?int $tahun = null): ?self
    {
        return self::where('subject_id', $subjectId)
            ->where('context', $context)
            ->where('tahun', $tahun ?? \App\Support\ActivePeriod::year())
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();
    }

    /**
     * Validasi & pakai kode yang diketik pihak yang dinilai.
     *
     * Kode yang SUDAH dipakai oleh $redeemer sendiri dan belum
     * kedaluwarsa sengaja masih diterima - supaya kalau checklist
     * tidak sengaja dibatalkan, pegawai bisa langsung mengetik ulang
     * kode yang sama alih-alih harus memanggil penilainya lagi.
     * Setelah lewat masa berlaku, tetap harus kode baru.
     *
     * @throws ValidationException
     */
    public static function redeem(string $code, User $redeemer, string $context, int $tahun): self
    {
        $normalized = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $code));

        $record = self::where('code', $normalized)
            ->where('context', $context)
            ->where('subject_id', $redeemer->id)
            ->where('tahun', $tahun)
            ->latest('id')
            ->first();

        // Pesan error SENGAJA sama persis untuk "kode tidak ada" dan
        // "kode milik orang lain" - kalau dibedakan, orang bisa
        // menebak-nebak kode yang valid milik pegawai lain dari
        // perbedaan pesannya.
        if (! $record) {
            throw ValidationException::withMessages([
                'meeting_code' => 'Kode pertemuan tidak valid. Pastikan kode diketik persis seperti yang ditunjukkan Penilai Anda.',
            ]);
        }

        if ($record->sudahKedaluwarsa()) {
            throw ValidationException::withMessages([
                'meeting_code' => 'Kode pertemuan sudah kedaluwarsa (berlaku ' . self::VALID_MINUTES . ' menit). Minta Penilai Anda membuat kode baru.',
            ]);
        }

        if ($record->sudahDipakai() && (int) $record->used_by !== $redeemer->id) {
            throw ValidationException::withMessages([
                'meeting_code' => 'Kode pertemuan sudah pernah dipakai. Minta Penilai Anda membuat kode baru.',
            ]);
        }

        $record->update([
            'used_at' => $record->used_at ?? now(),
            'used_by' => $redeemer->id,
        ]);

        return $record;
    }

    private static function randomCode(): string
    {
        $charset = self::CHARSET;
        $max = strlen($charset) - 1;
        $code = '';

        for ($i = 0; $i < self::LENGTH; $i++) {
            // random_int (CSPRNG), bukan rand()/mt_rand() - kode ini
            // dipakai sebagai bukti, jadi tidak boleh bisa ditebak
            // dari kode-kode sebelumnya.
            $code .= $charset[random_int(0, $max)];
        }

        return $code;
    }
}

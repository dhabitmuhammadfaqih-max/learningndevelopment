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
 * ALUR (lewat HRD):
 * 1. Hanya PENILAI/ATASAN ($issuer) yang menekan "Minta Kode" di
 *    dashboard-nya - lihat self::request(). Pihak yang dinilai
 *    ($subject) tidak meminta.
 * 2. Permintaan langsung muncul di halaman "Permintaan Kode" milik
 *    HRD - lihat self::scopeSiapDigenerate() &
 *    HrdController::meetingCodeRequests().
 * 3. HRD menekan "Buat Kode" - self::generate() mengisi kode & masa
 *    berlaku singkat (self::VALID_MINUTES). Kode tampil di dashboard
 *    KEDUA pihak.
 * 4. KEDUA pihak menekan "Sudah Bertemu" (urutan bebas, selama kode
 *    belum kedaluwarsa):
 *    - pihak yang dinilai -> self::redeem() (used_at/used_by),
 *      mencentang checklist-nya sendiri;
 *    - Penilai/Atasan -> self::confirmByIssuer() (issuer_confirmed_at),
 *      mencentang checklist-nya sendiri.
 *    Tiap pihak hanya mencentang checklist miliknya, jadi HRD baru
 *    bisa tanda tangan setelah keduanya menekan tombol.
 *
 * Yang menjamin pertemuan itu sungguhan adalah verifikasi HRD sebelum
 * membuat kode, bukan kerahasiaan kodenya.
 */
class MeetingCode extends Model
{
    use HasFactory;

    /**
     * Masa berlaku kode sejak DI-GENERATE HRD (bukan sejak diminta).
     * Sengaja pendek - kode ini mewakili "kita sedang duduk bersama
     * SEKARANG", bukan janji pertemuan. Kalau kedaluwarsa, kedua pihak
     * tinggal minta lagi (tidak ada batas jumlah permintaan).
     */
    public const VALID_MINUTES = 30;

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
        'subject_requested_at',
        'issuer_requested_at',
        'issuer_confirmed_at',
        'expires_at',
        'generated_by',
        'generated_at',
        'used_at',
        'used_by',
    ];

    protected $casts = [
        'tahun'                => 'integer',
        'subject_requested_at' => 'datetime',
        'issuer_requested_at'  => 'datetime',
        'issuer_confirmed_at'  => 'datetime',
        'expires_at'           => 'datetime',
        'generated_at'         => 'datetime',
        'used_at'              => 'datetime',
    ];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(User::class, 'subject_id');
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issuer_id');
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function usedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'used_by');
    }

    public function sudahDigenerate(): bool
    {
        return ! is_null($this->code);
    }

    /**
     * Penilai/Atasan sudah meminta, tapi HRD belum generate kodenya -
     * inilah yang membuat baris ini muncul di halaman "Permintaan
     * Kode" HRD.
     */
    public function siapDigenerate(): bool
    {
        return $this->issuer_requested_at
            && ! $this->sudahDigenerate()
            && ! $this->sudahDipakai();
    }

    public function issuerSudahKonfirmasi(): bool
    {
        return ! is_null($this->issuer_confirmed_at);
    }

    /**
     * Kode sudah dibuat HRD dan belum kedaluwarsa - terlepas dari
     * sudah/belum dipakai pihak yang dinilai. Dipakai partial supaya
     * Penilai tetap melihat kodenya & tombol "Sudah Bertemu" walau
     * pihak yang dinilai sudah lebih dulu menekan tombolnya.
     */
    public function kodeHidup(): bool
    {
        return $this->sudahDigenerate() && ! $this->sudahKedaluwarsa();
    }

    public function sudahDipakai(): bool
    {
        return ! is_null($this->used_at);
    }

    public function sudahKedaluwarsa(): bool
    {
        return $this->sudahDigenerate() && $this->expires_at->isPast();
    }

    public function masihBerlaku(): bool
    {
        return $this->sudahDigenerate() && ! $this->sudahDipakai() && ! $this->sudahKedaluwarsa();
    }

    /**
     * Sisa waktu berlaku dalam DETIK (0 kalau sudah lewat/belum
     * di-generate) - dipakai partial checklist untuk hitung mundur di
     * layar kedua pihak.
     */
    public function sisaDetik(): int
    {
        if (! $this->sudahDigenerate()) {
            return 0;
        }

        return max(0, now()->diffInSeconds($this->expires_at, false));
    }

    /**
     * Baris siklus yang MASIH TERBUKA untuk pasangan (subject, context,
     * tahun) ini, kalau ada - "terbuka" artinya belum di-generate,
     * atau sudah di-generate dan kodenya belum kedaluwarsa (walau
     * sudah dipakai salah satu pihak, karena pihak lain masih perlu
     * menekan tombolnya).
     * Dipakai baik oleh request() (supaya tidak membuat baris duplikat)
     * maupun view (untuk tahu status permintaan saat ini).
     */
    public static function openFor(int $subjectId, string $context, int $tahun): ?self
    {
        return self::where('subject_id', $subjectId)
            ->where('context', $context)
            ->where('tahun', $tahun)
            ->where(function ($query) {
                $query->whereNull('code')->orWhere('expires_at', '>', now());
            })
            ->latest('id')
            ->first();
    }

    /**
     * PENILAI/ATASAN menekan "Minta Kode". Idempotent - menekan
     * berkali-kali tidak membuat baris baru maupun mereset waktu
     * permintaan.
     */
    public static function request(User $subject, User $issuer, string $context, int $tahun): self
    {
        $row = self::openFor($subject->id, $context, $tahun);

        if (! $row) {
            $row = self::create([
                'context'    => $context,
                'subject_id' => $subject->id,
                'issuer_id'  => $issuer->id,
                'tahun'      => $tahun,
            ]);
        }

        if (! $row->issuer_requested_at) {
            $row->update(['issuer_requested_at' => now(), 'issuer_id' => $issuer->id]);
        }

        return $row->fresh();
    }

    /**
     * Semua permintaan Penilai/Atasan yang belum di-generate HRD - dipakai HrdController::meetingCodeRequests().
     */
    public function scopeSiapDigenerate($query)
    {
        return $query->whereNotNull('issuer_requested_at')
            ->whereNull('code')
            ->whereNull('used_at');
    }

    /**
     * HRD menekan "Buat Kode" untuk baris permintaan $row. Kode lama
     * milik pasangan yang sama yang masih hidup (harusnya tidak pernah
     * ada, karena request() memakai baris yang sama - jaring pengaman
     * saja) ikut dimatikan, supaya tidak ada 2 kode aktif bersamaan.
     *
     * @throws ValidationException
     */
    public static function generate(self $row, User $hrd): self
    {
        if (! $row->siapDigenerate()) {
            throw ValidationException::withMessages([
                'meeting_code' => 'Permintaan ini belum siap di-generate - Penilai/Atasan belum menekan "Minta Kode", atau kodenya sudah pernah dibuat.',
            ]);
        }

        self::where('subject_id', $row->subject_id)
            ->where('context', $row->context)
            ->where('tahun', $row->tahun)
            ->where('id', '!=', $row->id)
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

            $row->update([
                'code'         => $code,
                'expires_at'   => now()->addMinutes(self::VALID_MINUTES),
                'generated_by' => $hrd->id,
                'generated_at' => now(),
            ]);

            return $row->fresh();
        }

        throw ValidationException::withMessages([
            'meeting_code' => 'Gagal membuat kode pertemuan, silakan coba lagi.',
        ]);
    }

    /**
     * Kode yang MASIH berlaku untuk pasangan ini, kalau ada - dipakai
     * supaya kode tetap tampil di layar KEDUA pihak (subject maupun
     * issuer) setelah halaman di-refresh, tanpa perlu minta ulang.
     */
    public static function activeFor(int $subjectId, string $context, ?int $tahun = null): ?self
    {
        return self::where('subject_id', $subjectId)
            ->where('context', $context)
            ->where('tahun', $tahun ?? \App\Support\ActivePeriod::year())
            ->whereNotNull('code')
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
     * kode yang sama alih-alih harus meminta kode baru lagi.
     * Setelah lewat masa berlaku, tetap harus kode baru.
     *
     * @throws ValidationException
     */
    public static function redeem(string $code, User $redeemer, string $context, int $tahun): self
    {
        $normalized = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $code));

        $record = self::whereNotNull('code')
            ->where('code', $normalized)
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
                'meeting_code' => 'Kode pertemuan tidak valid. Pastikan kode diketik persis seperti yang ditampilkan di dashboard.',
            ]);
        }

        if ($record->sudahKedaluwarsa()) {
            throw ValidationException::withMessages([
                'meeting_code' => 'Kode pertemuan sudah kedaluwarsa (berlaku ' . self::VALID_MINUTES . ' menit). Minta kode baru ke HRD.',
            ]);
        }

        if ($record->sudahDipakai() && (int) $record->used_by !== $redeemer->id) {
            throw ValidationException::withMessages([
                'meeting_code' => 'Kode pertemuan sudah pernah dipakai. Minta kode baru ke HRD.',
            ]);
        }

        $record->update([
            'used_at' => $record->used_at ?? now(),
            'used_by' => $redeemer->id,
        ]);

        return $record;
    }

    /**
     * Penilai/Atasan menekan "Sudah Bertemu" untuk kode yang sudah
     * dibuat HRD. Sisi issuer dari redeem(): tidak ada yang diketik,
     * cukup tercatat bahwa Penilai ikut membenarkan pertemuannya.
     * Idempotent selama kode belum kedaluwarsa - supaya kalau
     * checklist tidak sengaja dibatalkan, Penilai bisa menekan lagi
     * tanpa meminta kode baru.
     *
     * @throws ValidationException
     */
    public static function confirmByIssuer(User $subject, User $issuer, string $context, int $tahun): self
    {
        $row = self::whereNotNull('code')
            ->where('context', $context)
            ->where('subject_id', $subject->id)
            ->where('issuer_id', $issuer->id)
            ->where('tahun', $tahun)
            ->latest('id')
            ->first();

        if (! $row) {
            throw ValidationException::withMessages([
                'meeting_code' => 'Belum ada kode pertemuan dari HRD. Tekan "Minta Kode" dan tunggu HRD membuat kodenya.',
            ]);
        }

        if ($row->sudahKedaluwarsa()) {
            throw ValidationException::withMessages([
                'meeting_code' => 'Kode pertemuan sudah kedaluwarsa (berlaku ' . self::VALID_MINUTES . ' menit). Minta kode baru ke HRD.',
            ]);
        }

        $row->update(['issuer_confirmed_at' => $row->issuer_confirmed_at ?? now()]);

        return $row;
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
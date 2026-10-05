<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

/**
 * Satu baris catatan audit. LIHAT App\Support\AuditLogger untuk cara
 * baris ini dibuat.
 *
 * Baris log bersifat tetap (append-only): mengubah atau menghapus lewat
 * instance model akan melempar exception. Satu-satunya jalur penghapusan
 * yang sah adalah pembersihan berdasarkan umur (command audit:prune),
 * yang memakai query builder.
 *
 * @property int                  $id
 * @property int|null             $actor_id
 * @property string|null          $actor_name
 * @property string|null          $actor_role
 * @property string               $event
 * @property string               $description
 * @property string|null          $auditable_type   nama tabel objek
 * @property int|null             $auditable_id
 * @property string|null          $auditable_label
 * @property array|null           $old_values
 * @property array|null           $new_values
 * @property array|null           $meta
 * @property \Carbon\Carbon|null  $created_at
 */
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    public const EVENT_CREATED       = 'created';
    public const EVENT_UPDATED       = 'updated';
    public const EVENT_DELETED       = 'deleted';
    public const EVENT_LOGIN         = 'login';
    public const EVENT_LOGOUT        = 'logout';
    public const EVENT_LOGIN_FAILED  = 'login_failed';
    public const EVENT_LOCKOUT       = 'lockout';
    public const EVENT_PDF           = 'pdf_downloaded';
    public const EVENT_FILE_DELETED  = 'file_deleted';
    public const EVENT_IMPORT        = 'import';
    public const EVENT_EXPORT        = 'export';
    public const EVENT_PRUNED        = 'pruned';

    /**
     * Label tampilan + warna badge per jenis kejadian.
     * Format: event => [label, kelas badge Tailwind].
     */
    public const EVENTS = [
        self::EVENT_CREATED      => ['Dibuat',            'bg-green-50 text-green-700 border-green-200'],
        self::EVENT_UPDATED      => ['Diubah',            'bg-blue-50 text-blue-700 border-blue-200'],
        self::EVENT_DELETED      => ['Dihapus',           'bg-red-50 text-red-700 border-red-200'],
        self::EVENT_LOGIN        => ['Login',             'bg-slate-50 text-slate-600 border-slate-200'],
        self::EVENT_LOGOUT       => ['Logout',            'bg-slate-50 text-slate-600 border-slate-200'],
        self::EVENT_LOGIN_FAILED => ['Gagal Login',       'bg-amber-50 text-amber-700 border-amber-200'],
        self::EVENT_LOCKOUT      => ['Login Diblokir',    'bg-amber-50 text-amber-700 border-amber-200'],
        self::EVENT_PDF          => ['Unduh PDF',         'bg-violet-50 text-violet-700 border-violet-200'],
        self::EVENT_FILE_DELETED => ['Hapus File',        'bg-red-50 text-red-700 border-red-200'],
        self::EVENT_IMPORT       => ['Import Akun',       'bg-violet-50 text-violet-700 border-violet-200'],
        self::EVENT_EXPORT       => ['Ekspor Audit Log',  'bg-violet-50 text-violet-700 border-violet-200'],
        self::EVENT_PRUNED       => ['Pembersihan Log',   'bg-slate-50 text-slate-600 border-slate-200'],
    ];

    /**
     * Label jenis objek, dikunci dengan NAMA TABEL (nilai
     * auditable_type) - nama tabel lebih stabil daripada nama class.
     */
    public const TYPES = [
        'users'                        => 'Akun',
        'evaluations'                  => 'Penilaian Pegawai',
        'feedbacks'                    => 'Tanggapan Korelasi',
        'supervisor_feedbacks'         => 'Tanggapan Atasan Penilai',
        'official_evaluations'         => 'Penilaian Pejabat',
        'official_supervisor_feedbacks' => 'Tanggapan Atasan Penilai (Pejabat)',
        'meeting_codes'                => 'Kode Pertemuan',
        'korelasi_assignments'         => 'Penugasan Korelasi',
        'app_settings'                 => 'Pengaturan',
    ];

    protected $fillable = [
        'actor_id',
        'actor_name',
        'actor_role',
        'event',
        'description',
        'auditable_type',
        'auditable_id',
        'auditable_label',
        'old_values',
        'new_values',
        'meta',
        'ip_address',
        'user_agent',
        'method',
        'url',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'meta'       => 'array',
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function () {
            throw new LogicException('Audit log tidak boleh diubah.');
        });

        static::deleting(function () {
            throw new LogicException('Audit log tidak boleh dihapus satu per satu. Gunakan audit:prune.');
        });
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function eventLabel(): string
    {
        return self::EVENTS[$this->event][0] ?? $this->event;
    }

    public function eventBadgeClass(): string
    {
        return self::EVENTS[$this->event][1] ?? 'bg-slate-50 text-slate-600 border-slate-200';
    }

    public function typeLabel(): ?string
    {
        if (! $this->auditable_type) {
            return null;
        }

        return self::TYPES[$this->auditable_type] ?? $this->auditable_type;
    }

    /**
     * Filter bersama untuk halaman daftar & ekspor CSV, supaya keduanya
     * SELALU memakai aturan yang sama.
     *
     * Kunci yang dikenali: event, type, actor, from, to, q. Nilai kosong
     * diabaikan.
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        $query
            ->when($filters['event'] ?? null, fn (Builder $q, $v) => $q->where('event', $v))
            ->when($filters['type'] ?? null, fn (Builder $q, $v) => $q->where('auditable_type', $v))
            ->when($filters['actor'] ?? null, fn (Builder $q, $v) => $q->where('actor_id', (int) $v))
            ->when($filters['from'] ?? null, fn (Builder $q, $v) => $q->where('created_at', '>=', Carbon::parse($v)->startOfDay()))
            ->when($filters['to'] ?? null, fn (Builder $q, $v) => $q->where('created_at', '<=', Carbon::parse($v)->endOfDay()));

        if (! empty($filters['q'])) {
            // Escape wildcard LIKE dengan "!" - satu-satunya karakter escape
            // yang berperilaku sama di MySQL & SQLite.
            $like = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $filters['q']) . '%';

            $query->where(function (Builder $q) use ($like) {
                $q->whereRaw("description LIKE ? ESCAPE '!'", [$like])
                    ->orWhereRaw("actor_name LIKE ? ESCAPE '!'", [$like])
                    ->orWhereRaw("auditable_label LIKE ? ESCAPE '!'", [$like]);
            });
        }

        return $query;
    }
}

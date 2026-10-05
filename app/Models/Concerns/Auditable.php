<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;
use App\Support\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Pasang di model yang perubahannya perlu masuk Audit Log.
 *
 * Otomatis mencatat created / updated / deleted lewat event Eloquent -
 * lihat App\Support\AuditLogger untuk detail & batasannya (update massal
 * lewat query builder tidak tercatat).
 *
 * Override auditLabel() di model untuk teks objek yang mudah dibaca
 * (yang ditampilkan di halaman Audit Log), mis. "Akun Budi (pegawai)".
 * Label disalin ke baris log saat kejadian, jadi tetap terbaca walau
 * objeknya kemudian dihapus/diganti nama.
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(fn (Model $model) => AuditLogger::model($model, AuditLog::EVENT_CREATED));
        static::updated(fn (Model $model) => AuditLogger::model($model, AuditLog::EVENT_UPDATED));
        static::deleted(fn (Model $model) => AuditLogger::model($model, AuditLog::EVENT_DELETED));
    }

    public function auditLabel(): string
    {
        return Str::headline(class_basename($this)) . ' #' . $this->getKey();
    }
}

<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Throwable;

/**
 * Pintu tunggal untuk menulis Audit Log.
 *
 * DUA jalur pemakaian:
 *
 * 1. OTOMATIS untuk perubahan data - model yang memakai trait
 *    App\Models\Concerns\Auditable memanggil self::model() setiap kali
 *    dibuat / diubah / dihapus lewat Eloquent.
 *
 *    BATAS yang perlu diketahui: update massal lewat query builder
 *    (User::where(...)->update([...])), penghapusan massal, dan
 *    ON DELETE CASCADE di database TIDAK memicu event Eloquent, jadi
 *    TIDAK tercatat per baris. Untuk aksi seperti itu panggil self::log()
 *    secara eksplisit kalau memang perlu jejaknya.
 *
 * 2. MANUAL untuk kejadian yang bukan perubahan satu model (unduh PDF,
 *    hapus file, import, ekspor, dsb.) lewat self::log().
 *
 * PRINSIP: pencatatan audit TIDAK BOLEH menggagalkan aksi aslinya. Semua
 * kegagalan (mis. tabel belum di-migrate saat deploy) ditelan dan hanya
 * dilaporkan ke log aplikasi lewat report().
 */
class AuditLogger
{
    private const MASK = '[disembunyikan]';

    /** Kedalaman "pause" - >0 artinya pencatatan sedang dimatikan. */
    private static int $suspended = 0;

    public static function enabled(): bool
    {
        return (bool) config('audit.enabled', true) && self::$suspended === 0;
    }

    /**
     * Jalankan $callback tanpa mencatat audit (mis. seeding/backfill
     * massal yang bukan aksi pengguna).
     */
    public static function withoutAuditing(callable $callback): mixed
    {
        self::$suspended++;

        try {
            return $callback();
        } finally {
            self::$suspended--;
        }
    }

    /**
     * Catat perubahan satu model. Dipanggil dari trait Auditable.
     *
     * @param  'created'|'updated'|'deleted'  $event
     */
    public static function model(Model $model, string $event): void
    {
        if (! self::enabled()) {
            return;
        }

        try {
            $label = method_exists($model, 'auditLabel')
                ? $model->auditLabel()
                : Str::headline(class_basename($model)) . ' #' . $model->getKey();

            $old = null;
            $new = null;

            if ($event === AuditLog::EVENT_CREATED) {
                $new = self::snapshot($model->getAttributes());
            } elseif ($event === AuditLog::EVENT_DELETED) {
                $old = self::snapshot($model->getAttributes());
            } else {
                // Di event "updated": getChanges() = nilai baru (mentah),
                // getRawOriginal() = nilai lama (mentah). Keduanya mentah
                // supaya tipenya sebanding (tanpa cast Carbon/bool).
                $changes = Arr::except($model->getChanges(), config('audit.ignored_attributes', []));

                if ($changes === []) {
                    return; // yang berubah cuma kolom "noise" - tidak perlu dicatat
                }

                $old = self::sanitize(Arr::only($model->getRawOriginal(), array_keys($changes)));
                $new = self::sanitize($changes);
            }

            $description = match ($event) {
                AuditLog::EVENT_CREATED => "Membuat {$label}",
                AuditLog::EVENT_DELETED => "Menghapus {$label}",
                default                 => "Mengubah {$label} (" . self::fieldList(array_keys($new ?? [])) . ')',
            };

            self::write([
                'event'           => $event,
                'description'     => $description,
                'auditable_type'  => $model->getTable(),
                'auditable_id'    => $model->getKey(),
                'auditable_label' => $label,
                'old_values'      => $old ?: null,
                'new_values'      => $new ?: null,
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }

    /**
     * Catat kejadian manual.
     *
     * Opsi $options:
     *  - subject : Model|null  objek yang terkena (diambil tabel, id, label-nya)
     *  - old/new : array|null  data sebelum/sesudah (otomatis disamarkan)
     *  - meta    : array|null  info tambahan bebas (mis. daftar path file)
     *  - actor   : User|null   paksa pelaku tertentu (mis. percobaan login
     *              yang gagal - saat itu belum ada user yang login). Kalau
     *              kuncinya tidak diberikan, dipakai user yang sedang login.
     */
    public static function log(string $event, string $description, array $options = []): void
    {
        if (! self::enabled()) {
            return;
        }

        try {
            $subject = $options['subject'] ?? null;

            $data = [
                'event'       => $event,
                'description' => $description,
                'old_values'  => isset($options['old']) ? self::sanitize($options['old']) : null,
                'new_values'  => isset($options['new']) ? self::sanitize($options['new']) : null,
                'meta'        => $options['meta'] ?? null,
            ];

            if ($subject instanceof Model) {
                $data['auditable_type']  = $subject->getTable();
                $data['auditable_id']    = $subject->getKey();
                $data['auditable_label'] = method_exists($subject, 'auditLabel')
                    ? $subject->auditLabel()
                    : Str::headline(class_basename($subject)) . ' #' . $subject->getKey();
            }

            if (array_key_exists('actor', $options)) {
                $data['__actor'] = $options['actor'];
            }

            self::write($data);
        } catch (Throwable $e) {
            report($e);
        }
    }

    /**
     * Nama akun untuk dipakai di label objek. Sengaja tanpa cache antar
     * panggilan: nama bisa berubah, dan id bisa dipakai ulang di test.
     */
    public static function userName(?int $id): string
    {
        if (! $id) {
            return '-';
        }

        return User::query()->whereKey($id)->value('name') ?? "#{$id}";
    }

    // ------------------------------------------------------------------
    // Internal
    // ------------------------------------------------------------------

    private static function write(array $data): void
    {
        $hasExplicitActor = array_key_exists('__actor', $data);
        $actor = $hasExplicitActor ? $data['__actor'] : Auth::user();
        unset($data['__actor']);

        $data['actor_id']   = $actor?->getAuthIdentifier();
        $data['actor_name'] = $actor?->name ?? 'Sistem';
        $data['actor_role'] = $actor?->role ?? null;

        // Perintah artisan/queue/seeder tidak punya request HTTP - jangan
        // catat IP/URL palsu dari request kosong. (Saat unit test, request
        // HTTP simulasi tetap dianggap request.)
        $hasRequest = ! app()->runningInConsole() || app()->runningUnitTests();

        if ($hasRequest && app()->bound('request')) {
            $request = request();

            $data['ip_address'] = $request->ip();
            $data['user_agent'] = Str::limit((string) $request->userAgent(), 250, '');
            $data['method']     = $request->method();
            // Path saja, TANPA query string - query bisa memuat token/data sensitif.
            $data['url']        = Str::limit('/' . ltrim($request->path(), '/'), 250, '');
        }

        AuditLog::query()->create($data);
    }

    /**
     * Snapshot kolom untuk event created/deleted: buang kolom noise &
     * kolom bernilai null (supaya baris tidak penuh "—"), lalu samarkan.
     */
    private static function snapshot(array $attributes): array
    {
        $attributes = Arr::except($attributes, config('audit.ignored_attributes', []));
        $attributes = array_filter($attributes, fn ($v) => $v !== null);

        return self::sanitize($attributes);
    }

    /**
     * Samarkan kolom sensitif & pangkas nilai yang terlalu panjang.
     */
    private static function sanitize(array $attributes): array
    {
        $masked = (array) config('audit.masked_attributes', []);
        $max    = (int) config('audit.max_value_length', 500);

        foreach ($attributes as $key => $value) {
            if (Str::is($masked, (string) $key)) {
                $attributes[$key] = self::MASK;
                continue;
            }

            if (is_string($value) && mb_strlen($value) > $max) {
                $attributes[$key] = mb_substr($value, 0, $max) . '…';
            } elseif (is_array($value) || is_object($value)) {
                $attributes[$key] = Str::limit((string) json_encode($value), $max);
            }
        }

        return $attributes;
    }

    private static function fieldList(array $fields): string
    {
        $shown = array_slice($fields, 0, 8);
        $text  = implode(', ', $shown);

        return count($fields) > count($shown)
            ? $text . ', +' . (count($fields) - count($shown)) . ' lainnya'
            : $text;
    }
}

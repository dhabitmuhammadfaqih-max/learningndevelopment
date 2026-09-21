<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AppSetting extends Model
{
    protected $fillable = ['key', 'value'];

    /**
     * Baca satu setting, cache 60 detik supaya tidak query DB tiap request
     * (dipanggil dari ActivePeriod::year() yang jalan di HAMPIR SETIAP
     * query penilaian di seluruh aplikasi).
     */
    public static function get(string $key, ?string $default = null): ?string
    {
        return Cache::remember("app_setting:{$key}", 60, function () use ($key, $default) {
            return static::query()->where('key', $key)->value('value') ?? $default;
        });
    }

    /**
     * Simpan/timpa satu setting, sekaligus hapus cache-nya supaya
     * perubahan langsung kepakai di request berikutnya (tidak perlu
     * nunggu cache 60 detik habis).
     */
    public static function set(string $key, ?string $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);

        Cache::forget("app_setting:{$key}");
    }
}

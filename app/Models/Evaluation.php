<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Evaluation extends Model
{
    protected $table = 'evaluations';

    protected $fillable = [
        'employee_id',
        'official_id',
        'pengetahuan_kerja',
        'penguasaan_peralatan',
        'volume_kerja',
        'mutu_tanggung_jawab',
        'disiplin_dedikasi_loyalitas',
        'prakarsa',
        'daya_serap',
        'kerajinan',
        'kerjasama',
        'score',
        'feedback',
        'recommendation',
        'employee_response',
        'signature',
    ];

    // Bobot setiap komponen penilaian (total harus 100)
    public const WEIGHTS = [
        'pengetahuan_kerja'           => 15,
        'penguasaan_peralatan'        => 15,
        'volume_kerja'                => 10,
        'mutu_tanggung_jawab'         => 10,
        'disiplin_dedikasi_loyalitas' => 15,
        'prakarsa'                    => 7.5,
        'daya_serap'                  => 10,
        'kerajinan'                   => 10,
        'kerjasama'                   => 7.5,
    ];

    // Label untuk ditampilkan di form/view
    public const LABELS = [
        'pengetahuan_kerja'           => 'Pengetahuan Kerja',
        'penguasaan_peralatan'        => 'Penguasaan Peralatan/Perangkat Kerja',
        'volume_kerja'                => 'Volume Kerja',
        'mutu_tanggung_jawab'         => 'Mutu Tanggung Jawab Pekerjaan',
        'disiplin_dedikasi_loyalitas' => 'Disiplin, Dedikasi & Loyalitas',
        'prakarsa'                    => 'Prakarsa',
        'daya_serap'                  => 'Daya Serap',
        'kerajinan'                   => 'Kerajinan',
        'kerjasama'                   => 'Kerjasama',
    ];

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function official()
    {
        return $this->belongsTo(User::class, 'official_id');
    }

    public function recommendationLabel(): string
    {
        return match ($this->recommendation) {
            'perpanjang_kontrak' => 'Perpanjang Kontrak',
            'promosi' => 'Promosi',
            'kenaikan_gaji' => 'Kenaikan Gaji',
            default => (string) $this->recommendation,
        };
    }

    public static function calculateScore(array $components): float
    {
        $total = 0;

        foreach (self::WEIGHTS as $key => $weight) {
            $value = (float) ($components[$key] ?? 0);
            $total += $value * ($weight / 100);
        }

        return round($total, 2);
    }
}
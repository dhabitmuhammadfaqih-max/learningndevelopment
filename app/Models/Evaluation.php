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
        'kenaikan_gaji_amount',
        'employee_response',
        'employee_response_at',
        'signature',
    ];

    protected $casts = [
        'employee_response_at' => 'datetime',
    ];

    // Nominal maksimal yang boleh diusulkan pejabat untuk rekomendasi kenaikan gaji.
    public const KENAIKAN_GAJI_MAX = 750000;

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

    // Deskripsi tiap faktor penilaian, ditampilkan di bawah nama faktor
    // pada form penilaian pejabat.
    public const DESCRIPTIONS = [
        'pengetahuan_kerja'           => 'Kemampuan dalam memahami dan melaksanakan pekerjaan secara efisien dan efektif.',
        'penguasaan_peralatan'        => 'Kemampuan dalam menggunakan dan mengoperasikan peralatan/perangkat kerja dengan baik dan benar.',
        'volume_kerja'                => 'Jumlah/kuantitas pekerjaan yang mampu diselesaikan sesuai target yang ditetapkan.',
        'mutu_tanggung_jawab'         => 'Kualitas hasil pekerjaan serta rasa tanggung jawab terhadap tugas yang diberikan.',
        'disiplin_dedikasi_loyalitas' => 'Ketaatan terhadap peraturan kerja serta dedikasi dan loyalitas terhadap perusahaan.',
        'prakarsa'                    => 'Inisiatif dalam menyelesaikan pekerjaan tanpa harus selalu menunggu perintah atasan.',
        'daya_serap'                  => 'Kemampuan memahami dan menyerap instruksi maupun pengetahuan baru dengan cepat.',
        'kerajinan'                   => 'Ketekunan dan keuletan dalam menjalankan tugas sehari-hari.',
        'kerjasama'                   => 'Kemampuan bekerja sama dan berkoordinasi dengan rekan kerja maupun tim.',
    ];

    // Skala index penilaian (I/A/B/C/D) beserta rentang nilai & keterangannya.
    public const SCALE = [
        'I' => ['min' => 90, 'max' => 100, 'label' => 'Sangat Bagus'],
        'A' => ['min' => 80, 'max' => 89,  'label' => 'Bagus'],
        'B' => ['min' => 65, 'max' => 79,  'label' => 'Cukup Bagus'],
        'C' => ['min' => 50, 'max' => 64,  'label' => 'Kurang Bagus'],
        'D' => ['min' => 35, 'max' => 49,  'label' => 'Sangat Kurang Bagus'],
    ];

    // Pilihan rekomendasi yang bisa dicentang lebih dari satu oleh pejabat.
    public const RECOMMENDATIONS = [
        'perpanjang_kontrak' => 'Perpanjang Kontrak',
        'promosi'            => 'Promosi',
        'kenaikan_gaji'      => 'Kenaikan Gaji',
    ];

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function official()
    {
        return $this->belongsTo(User::class, 'official_id');
    }

    /**
     * Rekomendasi disimpan sebagai string dipisah koma (mis. "promosi,kenaikan_gaji")
     * supaya pejabat bisa memilih lebih dari satu rekomendasi sekaligus.
     */
    public function recommendationList(): array
    {
        if (! $this->recommendation || $this->recommendation === 'tidak_ada') {
            return [];
        }

        return array_values(array_filter(explode(',', $this->recommendation)));
    }

    public function recommendationLabel(): string
    {
        $list = $this->recommendationList();

        if (empty($list)) {
            return 'Tidak Ada';
        }

        $labels = array_map(
            fn ($value) => self::RECOMMENDATIONS[$value] ?? $value,
            $list
        );

        return implode(', ', $labels);
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

    /**
     * Ambil huruf index (I/A/B/C/D) dari sebuah nilai mentah 0-100.
     */
    public static function scaleIndex(float $value): string
    {
        foreach (self::SCALE as $index => $range) {
            if ($value >= $range['min']) {
                return $index;
            }
        }

        return 'D';
    }
}

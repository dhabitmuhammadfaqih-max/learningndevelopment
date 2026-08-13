<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfficialEvaluation extends Model
{
    protected $table = 'official_evaluations';

    protected $fillable = [
        'official_id',
        'supervisor_id',
        'kepemimpinan',
        'kemampuan_merencanakan_mengoordinasikan',
        'kemampuan_analisa_evaluasi_pengambilan_keputusan',
        'kemampuan_memotivasi_aplikasi_manajemen',
        'tanggung_jawab_manajemen',
        'kerjasama',
        'prakarsa',
        'integritas',
        'pengetahuan_teknik_operasi',
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

    // Nominal maksimal yang boleh diusulkan atasan untuk rekomendasi kenaikan gaji.
    // Disamakan dengan Evaluation::KENAIKAN_GAJI_MAX supaya konsisten.
    public const KENAIKAN_GAJI_MAX = 750000;

    // Bobot setiap komponen penilaian pejabat (total harus 100)
    public const WEIGHTS = [
        'kepemimpinan'                                      => 15,
        'kemampuan_merencanakan_mengoordinasikan'            => 15,
        'kemampuan_analisa_evaluasi_pengambilan_keputusan'   => 10,
        'kemampuan_memotivasi_aplikasi_manajemen'            => 10,
        'tanggung_jawab_manajemen'                           => 10,
        'kerjasama'                                          => 10,
        'prakarsa'                                           => 10,
        'integritas'                                         => 15,
        'pengetahuan_teknik_operasi'                         => 5,
    ];

    // Label untuk ditampilkan di form/view
    public const LABELS = [
        'kepemimpinan'                                    => 'Kepemimpinan',
        'kemampuan_merencanakan_mengoordinasikan'          => 'Kemampuan Merencanakan & Mengoordinasikan',
        'kemampuan_analisa_evaluasi_pengambilan_keputusan' => 'Kemampuan Analisa dan Evaluasi serta Pengambilan Keputusan',
        'kemampuan_memotivasi_aplikasi_manajemen'          => 'Kemampuan Memotivasi Aplikasi Manajemen',
        'tanggung_jawab_manajemen'                         => 'Tanggung Jawab Manajemen',
        'kerjasama'                                        => 'Kerjasama',
        'prakarsa'                                         => 'Prakarsa',
        'integritas'                                       => 'Integritas',
        'pengetahuan_teknik_operasi'                       => 'Pengetahuan Teknik Operasi',
    ];

    // Deskripsi tiap faktor penilaian, ditampilkan di bawah nama faktor
    // pada form penilaian atasan.
    public const DESCRIPTIONS = [
        'kepemimpinan'                                    => 'Kemampuan memimpin, mengarahkan, dan menjadi teladan bagi bawahan.',
        'kemampuan_merencanakan_mengoordinasikan'          => 'Kemampuan menyusun rencana kerja serta mengoordinasikan pelaksanaannya.',
        'kemampuan_analisa_evaluasi_pengambilan_keputusan' => 'Kemampuan menganalisa situasi, mengevaluasi hasil, dan mengambil keputusan yang tepat.',
        'kemampuan_memotivasi_aplikasi_manajemen'          => 'Kemampuan memotivasi tim dan menerapkan prinsip-prinsip manajemen dalam pekerjaan.',
        'tanggung_jawab_manajemen'                         => 'Rasa tanggung jawab terhadap tugas dan hasil kerja unit yang dipimpin.',
        'kerjasama'                                        => 'Kemampuan bekerja sama dan berkoordinasi dengan rekan kerja maupun unit lain.',
        'prakarsa'                                         => 'Inisiatif dalam menyelesaikan pekerjaan tanpa harus selalu menunggu perintah.',
        'integritas'                                       => 'Kejujuran, konsistensi antara ucapan dan tindakan, serta kepatuhan terhadap aturan.',
        'pengetahuan_teknik_operasi'                       => 'Pemahaman terhadap teknik dan proses operasional di bidang tugasnya.',
    ];

    // Skala index penilaian (I/A/B/C/D), disamakan dengan Evaluation::SCALE.
    public const SCALE = [
        'I' => ['min' => 90, 'max' => 100, 'label' => 'Sangat Bagus'],
        'A' => ['min' => 80, 'max' => 89,  'label' => 'Bagus'],
        'B' => ['min' => 65, 'max' => 79,  'label' => 'Cukup Bagus'],
        'C' => ['min' => 50, 'max' => 64,  'label' => 'Kurang Bagus'],
        'D' => ['min' => 35, 'max' => 49,  'label' => 'Sangat Kurang Bagus'],
    ];

    // Pilihan rekomendasi, sama seperti penilaian karyawan.
    public const RECOMMENDATIONS = [
        'perpanjang_kontrak' => 'Perpanjang Kontrak',
        'promosi'            => 'Promosi',
        'kenaikan_gaji'      => 'Kenaikan Gaji',
    ];

    public function official()
    {
        return $this->belongsTo(User::class, 'official_id');
    }

    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    /**
     * Rekomendasi disimpan sebagai string dipisah koma, sama seperti
     * Evaluation::recommendationList().
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

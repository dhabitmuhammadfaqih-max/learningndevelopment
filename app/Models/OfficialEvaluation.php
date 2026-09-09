<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfficialEvaluation extends Model
{
    protected $table = 'official_evaluations';

    protected $fillable = [
        'official_id',
        'supervisor_id',
        'tahun',
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
        'teguran',
        'recommendation',
        'kenaikan_gaji_amount',
        'promosi_keterangan',
        'demosi_keterangan',
        'mutasi_keterangan',
        'employee_response',
        'employee_response_at',
        'employee_signature',
        'signature',
        'hrd_id',
        'hrd_signature',
        'hrd_signed_at',
    ];

    protected $casts = [
        'employee_response_at' => 'datetime',
        'hrd_signed_at' => 'datetime',
        'score' => 'integer',
    ];

    /**
     * Teks ringkas untuk ditampilkan di PDF/laporan, mis. "Pernah - terlambat berulang kali"
     * atau "-" kalau tidak pernah ditegur.
     */
    public function teguranRingkas(): string
    {
        return $this->teguran ? $this->teguran : '-';
    }

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
        'kepemimpinan'                                    => 'Kemampuan meyakinkan orang lain sehingga dapat dikerahkan secara maksimal untuk melaksanakan tugas dan tanggungjawab.',
        'kemampuan_merencanakan_mengoordinasikan'          => 'Kemampuan membuat rencana dan atau konsep serta dapat mengkoordinasikan anggota/tim untuk mencapai tujuan yang telah ditetapkan.',
        'kemampuan_analisa_evaluasi_pengambilan_keputusan' => 'Proses berpikir yang melibatkan pemecahan masalah, pengumpulan, dan analisis data atau informasi secara sistematis untuk mencapai pemahaman yang lebih mendalam tentang suatu permasalahan.',
        'kemampuan_memotivasi_aplikasi_manajemen'          => 'Motivasi dan semangat dalam menggunakan aplikasi (sistem) yang ditetapkan.',
        'tanggung_jawab_manajemen'                         => 'Kesanggupan menyelesaikan pekerjaan dengan sebaik-baiknya dan tepat waktu serta bertanggungjawab atas keputusan yang diambil dan tindakan yang dilakukan.',
        'kerjasama'                                        => 'Kemampuan untuk bekerja bersama-sama dalam menyelesaikan tugas sehingga mencapai dayaguna dan hasilguna yang lebih maksimal.',
        'prakarsa'                                         => 'Langkah-langkah atau melaksanakan sesuatu tindakan yang diperlukan dalam melaksanakan tugas tanpa menunggu perintah (inisiatif).',
        'integritas'                                       => 'Mutu, sifat, atau keadaan yang menunjukan kesatuan yang utuh, sehingga memiliki potensi dan kemampuan yang mencerminkan kewibawaan dan kejujuran.',
        'pengetahuan_teknik_operasi'                       => 'Kemampuan yang digunakan untuk memilih suatu pilihan atau mengambil keputusan agar hasilnya optimal.',
    ];

    // Skala index penilaian (I/A/B/C/D), disamakan dengan Evaluation::SCALE.
    public const SCALE = [
        'I' => ['min' => 90, 'max' => 100, 'label' => 'Sangat Bagus'],
        'A' => ['min' => 80, 'max' => 89,  'label' => 'Bagus'],
        'B' => ['min' => 65, 'max' => 79,  'label' => 'Cukup Bagus'],
        'C' => ['min' => 50, 'max' => 64,  'label' => 'Kurang Bagus'],
        'D' => ['min' => 35, 'max' => 49,  'label' => 'Sangat Kurang Bagus'],
    ];

    // Pilihan rekomendasi, sama seperti penilaian pegawai.
    public const RECOMMENDATIONS = [
        'lulus_probation'              => 'Lulus Probation',
        'review_3_bulan'               => 'Review 3 Bulan',
        'review_6_bulan'               => 'Review 6 Bulan',
        'tidak_diperpanjang'           => 'Tidak Diperpanjang',
        'phl_ke_kontrak'               => 'PHL OS ke Kontrak OS',
        'perpanjang_kontrak_os'        => 'Perpanjang Kontrak OS',
        'kontrak_os_ke_kontrak_dagsap' => 'Kontrak OS ke Kontrak Dagsap',
        'perpanjang_kontrak_dagsap'    => 'Perpanjang Kontrak Dagsap',
        'kontrak_dagsap_ke_tetap'      => 'Kontrak Dagsap ke Tetap',
        'perpanjang_status_tetap'      => 'Perpanjang Status Tetap',
        'mendapatkan_uang_makan'       => 'Mendapatkan Uang Makan',
        'demosi'                       => 'Demosi',
        'mutasi'                       => 'Mutasi',
        'promosi'                      => 'Promosi',
        'kenaikan_gaji'                => 'Kenaikan Gaji',
    ];

    // Keterangan singkat tiap pilihan rekomendasi, ditampilkan di form supaya lebih jelas.
    public const RECOMMENDATION_DESCRIPTIONS = [
        'lulus_probation'              => 'Pejabat dinyatakan lulus masa percobaan (probation)',
        'review_3_bulan'               => 'Kinerja perlu dievaluasi ulang setelah 3 bulan ke depan',
        'review_6_bulan'               => 'Kinerja perlu dievaluasi ulang setelah 6 bulan ke depan',
        'tidak_diperpanjang'           => 'Kontrak/masa kerja pejabat tidak dilanjutkan',
        'phl_ke_kontrak'               => 'Perubahan status dari PHL (Pekerja Harian Lepas) menjadi kontrak',
        'perpanjang_kontrak_os'        => 'Perpanjangan kontrak untuk pejabat status Outsourcing (OS)',
        'kontrak_os_ke_kontrak_dagsap' => 'Perubahan status dari kontrak Outsourcing (OS) menjadi kontrak Dagsap',
        'perpanjang_kontrak_dagsap'    => 'Perpanjangan kontrak untuk pejabat status Dagsap',
        'kontrak_dagsap_ke_tetap'      => 'Perubahan status dari kontrak Dagsap menjadi pejabat tetap',
        'perpanjang_status_tetap'      => 'Perpanjangan status sebagai pejabat tetap',
        'mendapatkan_uang_makan'       => 'Pejabat direkomendasikan mendapat tunjangan uang makan',
        'demosi'                       => 'Penurunan jabatan/posisi pejabat',
        'mutasi'                       => 'Pemindahan pejabat ke posisi atau unit kerja lain',
        'promosi'                      => 'Kenaikan jabatan/posisi pejabat',
        'kenaikan_gaji'                => 'Kenaikan nominal gaji pokok pejabat',
    ];

    public function official()
    {
        return $this->belongsTo(User::class, 'official_id');
    }

    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    public function hrd()
    {
        return $this->belongsTo(User::class, 'hrd_id');
    }

    /**
     * Scope: batasi query ke satu tahun tertentu (default tahun berjalan
     * kalau $tahun tidak diisi). Lihat catatan yang sama di
     * App\Models\Evaluation::scopeTahunAktif().
     */
    public function scopeTahunAktif($query, ?int $tahun = null)
    {
        return $query->where('tahun', $tahun ?? now()->year);
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

        $labels = array_map(function ($value) {
            $label = self::RECOMMENDATIONS[$value] ?? $value;

            if ($value === 'promosi' && $this->promosi_keterangan) {
                $label .= ' (ke ' . $this->promosi_keterangan . ')';
            }

            if ($value === 'demosi' && $this->demosi_keterangan) {
                $label .= ' (ke ' . $this->demosi_keterangan . ')';
            }

            if ($value === 'mutasi' && $this->mutasi_keterangan) {
                $label .= ' (ke ' . $this->mutasi_keterangan . ')';
            }

            return $label;
        }, $list);

        return implode(', ', $labels);
    }

    public static function calculateScore(array $components): float
    {
        $total = 0;

        foreach (self::WEIGHTS as $key => $weight) {
            $value = (float) ($components[$key] ?? 0);
            $total += $value * ($weight / 100);
        }

        return (int) round($total);
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

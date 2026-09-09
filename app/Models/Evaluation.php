<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Evaluation extends Model
{
    protected $table = 'evaluations';

    protected $fillable = [
        'employee_id',
        'official_id',
        'tahun',
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
        'teguran',
        'recommendation',
        'kenaikan_gaji_amount',
        'promosi_keterangan',
        'demosi_keterangan',
        'mutasi_keterangan',
        'employee_response',
        'employee_response_at',
        'employee_signature',
        'hrd_id',
        'hrd_signature',
        'hrd_signed_at',
        'signature',
    ];

    protected $casts = [
        'employee_response_at' => 'datetime',
        'hrd_signed_at' => 'datetime',
        'score' => 'integer',
        'prakarsa' => 'integer',
        'kerjasama' => 'integer',
    ];

    public function hrd()
    {
        return $this->belongsTo(User::class, 'hrd_id');
    }

    /**
     * Scope: batasi query ke satu tahun tertentu. Tanpa argumen, default-nya
     * tahun berjalan (now()->year) - dipakai di controller supaya
     * pengecekan "sudah pernah dinilai" & pengambilan data "penilaian saat
     * ini" konsisten per tahun, tanpa ikut menyentuh histori tahun-tahun
     * sebelumnya. Beri argumen $tahun untuk secara eksplisit membaca
     * histori tahun lain (dipakai selector tahun di halaman HRD). Contoh:
     * Evaluation::where('employee_id', $id)->tahunAktif()->first();
     * Evaluation::where('employee_id', $id)->tahunAktif(2025)->first().
     */
    public function scopeTahunAktif($query, ?int $tahun = null)
    {
        return $query->where('tahun', $tahun ?? now()->year);
    }

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
        'penguasaan_peralatan'        => 'Kemampuan dalam menguasai alat kerja yang digunakan.',
        'volume_kerja'                => 'Kemampuan dalam menyelesaikan tugas/pekerjaan sesuai dengan waktu yang sudah ditetapkan.',
        'mutu_tanggung_jawab'         => 'Kesanggupan menyelesaikan pekerjaan dengan sebaik-baiknya dan tepat waktu serta bertanggungjawab atas pekerjaannya.',
        'disiplin_dedikasi_loyalitas' => 'Kesadaran dan kesediaan dalam menaati semua peraturan yang berlaku pada perusahaan.',
        'prakarsa'                    => 'Langkah-langkah atau melaksanakan sesuatu tindakan yang diperlukan dalam melaksanakan tugas pokok tanpa menunggu perintah (inisiatif).',
        'daya_serap'                  => 'Kemampuan dalam menyerap atau memahami tugas/pekerjaan yang diberikan.',
        'kerajinan'                   => 'Kemampuan melakukan pekerjaan dengan sungguh-sungguh untuk mencapai tujuan/target yang diberikan.',
        'kerjasama'                   => 'Kemampuan untuk bekerja bersama-sama dalam menyelesaikan tugas sehingga mencapai dayaguna dan hasilguna yang lebih maksimal.',
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
        'lulus_probation'              => 'Pegawai dinyatakan lulus masa percobaan (probation)',
        'review_3_bulan'               => 'Kinerja perlu dievaluasi ulang setelah 3 bulan ke depan',
        'review_6_bulan'               => 'Kinerja perlu dievaluasi ulang setelah 6 bulan ke depan',
        'tidak_diperpanjang'           => 'Kontrak/masa kerja pegawai tidak dilanjutkan',
        'phl_ke_kontrak'               => 'Perubahan status dari PHL (Pekerja Harian Lepas) menjadi pegawai kontrak',
        'perpanjang_kontrak_os'        => 'Perpanjangan kontrak untuk pegawai status Outsourcing (OS)',
        'kontrak_os_ke_kontrak_dagsap' => 'Perubahan status dari kontrak Outsourcing (OS) menjadi kontrak Dagsap',
        'perpanjang_kontrak_dagsap'    => 'Perpanjangan kontrak untuk pegawai status Dagsap',
        'kontrak_dagsap_ke_tetap'      => 'Perubahan status dari kontrak Dagsap menjadi pegawai tetap',
        'perpanjang_status_tetap'      => 'Perpanjangan status sebagai pegawai tetap',
        'mendapatkan_uang_makan'       => 'Pegawai direkomendasikan mendapat tunjangan uang makan',
        'demosi'                       => 'Penurunan jabatan/posisi pegawai',
        'mutasi'                       => 'Pemindahan pegawai ke posisi atau unit kerja lain',
        'promosi'                      => 'Kenaikan jabatan/posisi pegawai',
        'kenaikan_gaji'                => 'Kenaikan nominal gaji pokok pegawai',
    ];

    /**
     * Teks ringkas untuk ditampilkan di PDF/laporan, mis. "Pernah - terlambat berulang kali"
     * atau "-" kalau tidak pernah ditegur.
     */
    public function teguranRingkas(): string
    {
        return $this->teguran ? $this->teguran : '-';
    }

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

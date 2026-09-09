<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Evaluation;
use App\Models\Feedback;
use App\Models\SupervisorFeedback;
use App\Models\OfficialEvaluation;
use App\Models\OfficialSupervisorFeedback;

class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;

    protected $fillable = [
        'name',
        'username',
        'nik',
        'unit_kerja',
        'jabatan',
        'departemen',
        'vendor',
        'status',
        'status_kontrak_terbuka',
        'email',
        'password',
        'role',
        'is_spg',
        'menilai_secara_manual',
        'boleh_menilai_pegawai_lain',
        'boleh_menilai_pegawai_lain',
        'supervisor_id',
        'atasan_pejabat_id',
        'atasan_penilai_pejabat_id',
        'jumlah_izin',
        'jumlah_sakit',
        'jumlah_alpa',
        'jumlah_terlambat',
        'menit_terlambat',
        'tanggal_masuk',
        'contract_status',
        'kehadiran_diisi_at',
        'pegawai_konfirmasi_pertemuan_at',
        'penilai_konfirmasi_pertemuan_at',
        'pejabat_konfirmasi_pertemuan_at',
        'atasan_konfirmasi_pertemuan_at',
        'pegawai_konfirmasi_pertemuan_selfie',
        'penilai_konfirmasi_pertemuan_selfie',
        'pejabat_konfirmasi_pertemuan_selfie',
        'atasan_konfirmasi_pertemuan_selfie',
        'pegawai_konfirmasi_pertemuan_evidence_type',
        'penilai_konfirmasi_pertemuan_evidence_type',
        'pejabat_konfirmasi_pertemuan_evidence_type',
        'atasan_konfirmasi_pertemuan_evidence_type',
        'pegawai_konfirmasi_pertemuan_metode',
        'penilai_konfirmasi_pertemuan_metode',
        'pejabat_konfirmasi_pertemuan_metode',
        'atasan_konfirmasi_pertemuan_metode',
        'pegawai_konfirmasi_pertemuan_tahun',
        'penilai_konfirmasi_pertemuan_tahun',
        'pejabat_konfirmasi_pertemuan_tahun',
        'atasan_konfirmasi_pertemuan_tahun',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_spg' => 'boolean',
        'menilai_secara_manual' => 'boolean',
        'boleh_menilai_pegawai_lain' => 'boolean',
        'boleh_menilai_pegawai_lain' => 'boolean',
        'status_kontrak_terbuka' => 'boolean',
        'kehadiran_diisi_at' => 'datetime',
        'pegawai_konfirmasi_pertemuan_at' => 'datetime',
        'penilai_konfirmasi_pertemuan_at' => 'datetime',
        'pejabat_konfirmasi_pertemuan_at' => 'datetime',
        'atasan_konfirmasi_pertemuan_at' => 'datetime',
        'pegawai_konfirmasi_pertemuan_tahun' => 'integer',
        'penilai_konfirmasi_pertemuan_tahun' => 'integer',
        'pejabat_konfirmasi_pertemuan_tahun' => 'integer',
        'atasan_konfirmasi_pertemuan_tahun' => 'integer',
        'tanggal_masuk' => 'date',
    ];

    // Kolom jumlah kehadiran yang bisa diisi hrd (masing-masing kategori
    // dihitung terpisah, bukan satu status tunggal).
    public const ATTENDANCE_COUNTERS = [
        'jumlah_izin'      => 'Izin',
        'jumlah_sakit'     => 'Sakit',
        'jumlah_alpa'      => 'Alpa',
        'jumlah_terlambat' => 'Terlambat',
        'menit_terlambat'  => 'Menit Terlambat',
    ];

    /**
     * Masa kerja dalam bentuk teks "X tahun Y bulan", dihitung otomatis
     * dari tanggal_masuk (bukan disimpan sebagai angka statis) supaya
     * selalu akurat tanpa perlu diupdate manual. Null kalau tanggal_masuk
     * belum diisi.
     */
    public function getMasaKerjaAttribute(): ?string
    {
        if (! $this->tanggal_masuk) {
            return null;
        }

        $diff = now()->diff($this->tanggal_masuk);

        return "{$diff->y} tahun {$diff->m} bulan";
    }

    /**
     * Total menit_terlambat (rekap tahun berjalan, lihat
     * ATTENDANCE_COUNTERS) dalam bentuk teks "X jam Y menit" supaya
     * lebih mudah dibaca di dashboard/halaman penilaian daripada angka
     * menit mentah.
     */
    public function getMenitTerlambatFormattedAttribute(): string
    {
        $menit = (int) ($this->menit_terlambat ?? 0);
        $jam = intdiv($menit, 60);
        $sisaMenit = $menit % 60;

        if ($jam > 0 && $sisaMenit > 0) {
            return "{$jam} jam {$sisaMenit} menit";
        }

        if ($jam > 0) {
            return "{$jam} jam";
        }

        return "{$sisaMenit} menit";
    }

    // Pilihan Vendor untuk kolom "Vendor" (Excel HRD & form akun).
    // Dipakai sebagai dropdown, sama seperti Role.
    public const VENDORS = [
        'Dagsap',
        'HGF',
        'OS ABM',
        'OS Bantul',
        'OS Cakra',
    ];

    // Pilihan Status untuk kolom "Status" (Excel HRD & form akun).
    // "PHL" = default otomatis untuk sheet yang tidak punya kolom
    // Vendor/Status (lihat HrdController::importAccounts()).
    public const EMPLOYMENT_STATUSES = [
        'Kontrak',
        'PHL',
        'Tetap',
    ];

    // Pilihan status kontrak yang bisa diberikan admin.
    public const CONTRACT_STATUSES = [
        'harian'  => 'Harian',
        'bulanan' => 'Bulanan',
        'tahunan' => 'Tahunan',
        'tetap'   => 'Tetap',
    ];

    /**
     * Rekomendasi "Kontrak Dagsap ke Tetap" cuma masuk akal kalau status
     * kontrak user ini SAAT INI memang sudah "Kontrak Dagsap" (status
     * Kontrak + vendor Dagsap). Kalau statusnya masih PHL atau Kontrak OS
     * (vendor "OS ABM"/"OS Bantul"/"OS Cakra"), rekomendasi ini belum
     * relevan - harusnya lewat "PHL ke Kontrak" / "Kontrak OS ke Kontrak
     * Dagsap" dulu. Dipakai bersama oleh blade (untuk menonaktifkan
     * checkbox) dan controller (validasi server-side).
     */
    public function isEligibleForDagsapTetap(): bool
    {
        return $this->status === 'Kontrak' && $this->vendor === 'Dagsap';
    }

    // Jumlah minimal tanggapan korelasi yang harus diterima pegawai
    // sebelum penilai boleh mulai menilai (Evaluation). SPG dikecualikan.
    // Disamakan dengan MIN_TANGGAPAN_KORELASI_PEJABAT (3) supaya syarat
    // korelasi pegawai konsisten dengan syarat korelasi pejabat, dan
    // dengan aturan minimal 3 tanggapan korelasi untuk cetak PDF di
    // HrdController::pdf() / AdminController - lihat korelasiSudahMemberiTanggapan().
    public const MIN_TANGGAPAN_KORELASI = 3;

    // Jumlah minimal tanggapan korelasi (Feedback antar pejabat, lihat
    // OfficialController::feedback()) yang harus diterima PEJABAT sebelum
    // atasannya (SupervisorController::evaluateOfficial()) boleh mulai
    // menilai. Beda konstanta dari MIN_TANGGAPAN_KORELASI (pegawai) karena
    // angkanya memang sengaja beda (3, bukan 1) - lihat
    // korelasiPejabatSudahMemberiTanggapan().
    public const MIN_TANGGAPAN_KORELASI_PEJABAT = 3;

    public function contractStatusLabel(): string
    {
        return self::CONTRACT_STATUSES[$this->contract_status] ?? '-';
    }

    /**
     * Apakah badge kolom "Status" (mis. "Kontrak"/"PHL"/"Tetap") boleh
     * ditampilkan ke akun ini di halaman pegawai/pejabat (dashboard,
     * evaluate, tanggapan, dsb).
     *
     * PENTING: ini dicek dari akun yang SEDANG LOGIN (auth()->user()),
     * bukan dari pemilik data yang sedang ditampilkan. Jadi kalau status
     * akun A ditutup, A tidak akan melihat badge Status SIAPAPUN di
     * halaman-halaman itu - baik status A sendiri, maupun status
     * pegawai/pejabat lain yang muncul di daftar A (mis. daftar Pegawai /
     * Pejabat Binaan di dashboard, atau saat A membuka halaman evaluate
     * orang lain). Lihat pemakaiannya di masing-masing view (dicari
     * lewat: auth()->user()->statusKontrakTerbuka()).
     *
     * Toggle ini TIDAK mempengaruhi HRD - HRD tetap selalu bisa melihat
     * kolom Status di halaman "Semua Akun"/"Lihat Pegawai"/"Lihat
     * Pejabat" apapun nilai kolom status_kontrak_terbuka akun manapun.
     * Default true (lihat migrasi status_kontrak_terbuka) supaya akun
     * lama tidak berubah perilakunya sampai HRD secara eksplisit
     * menutupnya lewat HrdController::toggleStatusKontrak().
     */
    public function statusKontrakTerbuka(): bool
    {
        return (bool) $this->status_kontrak_terbuka;
    }

    /**
     * Apakah HRD sudah mengisi/menyimpan data kehadiran pegawai ini.
     * Memakai kolom kehadiran_diisi_at (bukan cuma cek jumlah_izin dkk != 0),
     * karena kehadiran sempurna (semua kolom 0) tetap harus dianggap "sudah
     * diisi" kalau memang HRD sudah pernah menyimpan form-nya.
     */
    public function kehadiranSudahDiisiHrd(): bool
    {
        return ! is_null($this->kehadiran_diisi_at);
    }

    /**
     * Apakah pegawai ini sudah menerima tanggapan korelasi (Feedback) dari
     * rekan sejawat, minimal MIN_TANGGAPAN_KORELASI. SPG dikecualikan dari
     * syarat ini.
     */
    public function korelasiSudahMemberiTanggapan(): bool
    {
        if ($this->is_spg) {
            return true;
        }

        // Pakai kolom hasil withCount('feedbacksReceived') kalau sudah
        // di-eager-load (dipakai di daftar dashboard supaya tidak query
        // ulang per-baris), fallback ke query langsung kalau belum.
        $count = $this->feedbacks_received_count ?? $this->feedbacksReceived()->count();

        return $count >= self::MIN_TANGGAPAN_KORELASI;
    }

    /**
     * Syarat gabungan sebelum penilai (pejabat/hrd) boleh menilai pegawai
     * ini: korelasi sudah memberi tanggapan DAN hrd sudah mengisi
     * kehadiran. Dipakai OfficialController::evaluate() & tampilan
     * official.evaluate.
     */
    public function siapDinilaiPenilai(): bool
    {
        return $this->korelasiSudahMemberiTanggapan() && $this->kehadiranSudahDiisiHrd();
    }

    /**
     * Apakah PEJABAT ini sudah menerima tanggapan korelasi (Feedback) dari
     * pejabat lain, minimal MIN_TANGGAPAN_KORELASI_PEJABAT (3). Dipakai
     * sebagai syarat sebelum Atasan (users.supervisor_id pejabat ini)
     * boleh mulai mengisi Penilaian Kinerja (OfficialEvaluation) lewat
     * SupervisorController::evaluateOfficial(). Tidak ada pengecualian
     * SPG di sini karena is_spg hanya berlaku untuk role pegawai.
     */
    public function korelasiPejabatSudahMemberiTanggapan(): bool
    {
        // Pakai kolom hasil withCount('feedbacksReceived') kalau sudah
        // di-eager-load (dipakai di daftar dashboard supaya tidak query
        // ulang per-baris), fallback ke query langsung kalau belum.
        $count = $this->feedbacks_received_count ?? $this->feedbacksReceived()->count();

        return $count >= self::MIN_TANGGAPAN_KORELASI_PEJABAT;
    }

    /**
     * Kalau true, PDF penilaian pegawai ini boleh dicetak HRD walau
     * Tanggapan Atasan (SupervisorFeedback / atasan Evaluation) belum
     * diisi lewat sistem. True kalau salah satu dari dua kondisi ini:
     * (1) Atasan Pejabat (users.atasan_pejabat_id) yang ditugaskan ke
     * akun ini menilai SEMUA bawahannya secara manual, atau (2) akun ini
     * tidak punya Atasan Pejabat sama sekali DAN Penilai-nya
     * (users.supervisor_id) menilai secara manual - lihat
     * penilaianUtamaManual(). Dipakai di HrdController::pdf()/show() &
     * view admin.detail/admin.pdf.
     */
    public function tanggapanAtasanManual(): bool
    {
        if ($this->atasan_pejabat_id) {
            return (bool) ($this->atasanPejabat && $this->atasanPejabat->menilaiSecaraManual());
        }

        // Tidak ada Atasan Pejabat yang ditugaskan sama sekali (mis.
        // Penilai pegawai ini sudah level tertinggi/Direktur, jadi tidak
        // ada lagi orang di atasnya untuk mengisi Tanggapan Atasan).
        // Dalam kasus ini Tanggapan Atasan dianggap ikut tercakup manual
        // kalau Penilai (users.supervisor_id) pegawai ini menilai secara
        // manual - karena memang tidak akan pernah ada yang bisa
        // mengisinya lewat sistem. Lihat penilaianUtamaManual().
        return $this->penilaianUtamaManual();
    }

    /**
     * Kalau true, PDF penilaian PEJABAT ini boleh dicetak HRD & HRD boleh
     * tanda tangan kapan saja - walau Penilaian dari Penilai
     * (OfficialEvaluation) DAN/ATAU Tanggapan dari Atasan Penilai
     * (OfficialSupervisorFeedback) belum diisi lewat sistem - karena
     * salah satu atau kedua pihak itu akan mengisi penilaiannya secara
     * manual di luar aplikasi. True kalau salah satu dari dua kondisi ini:
     * (1) Penilai (users.supervisor_id) pejabat ini menilai secara manual
     * - lihat penilaianUtamaManual() (versi pegawai dari pengecekan yang
     * sama), atau (2) Atasan Penilai (users.atasan_penilai_pejabat_id)
     * pejabat ini menilai secara manual. Dipakai di
     * HrdController::signAsHrdOfficial()/officialPdf() & view
     * admin.detail (bagian pejabat).
     */
    public function tanggapanPenilaiPejabatManual(): bool
    {
        return $this->penilaianUtamaManual()
            || (bool) ($this->atasanPenilaiPejabat && $this->atasanPenilaiPejabat->menilaiSecaraManual());
    }

    /**
     * Kalau true, akun ini (harus role pejabat/hrd) menilai SEMUA
     * pegawai/pejabat yang ditugaskan ke akun ini - baik sebagai Penilai
     * (users.supervisor_id) maupun Atasan Penilai
     * (users.atasan_pejabat_id / users.atasan_penilai_pejabat_id) - secara
     * manual di luar aplikasi. Flag ini dicentang SEKALI di akun evaluator
     * itu sendiri, dan otomatis berlaku ke semua akun yang ditugaskan
     * kepadanya. Lihat penilaianUtamaManual(), tanggapanAtasanManual(),
     * tanggapanPenilaiPejabatManual().
     */
    public function menilaiSecaraManual(): bool
    {
        return (bool) $this->menilai_secara_manual;
    }

    /**
     * Kalau true, PDF penilaian akun ini (pegawai ATAU pejabat) boleh
     * dicetak HRD walau penilaian UTAMA dari Penilai
     * (Evaluation/OfficialEvaluation, users.supervisor_id) belum diisi
     * lewat sistem - karena Penilai yang ditugaskan (users.supervisor_id)
     * menilai secara manual (lihat menilaiSecaraManual()). Dipakai
     * langsung di HrdController::pdf() untuk pegawai; untuk pejabat sudah
     * tercakup lewat tanggapanPenilaiPejabatManual() di atas.
     */
    public function penilaianUtamaManual(): bool
    {
        return (bool) ($this->supervisor && $this->supervisor->menilaiSecaraManual());
    }

    /**
     * Apakah PEGAWAI sudah mencentang checklist "sudah bertemu & evaluasi"
     * untuk dirinya sendiri. Independen dari tanggapan tertulis
     * (Evaluation::employee_response) - checklist ini cuma konfirmasi
     * pertemuan, bisa dicentang/dibatalkan kapan saja lewat dashboard
     * pegawai. Lihat EmployeeController::toggleChecklistPertemuan().
     */
    /**
     * $tahun default ke tahun berjalan (now()->year) - beri argumen
     * eksplisit untuk membaca histori tahun lain (dipakai selector tahun
     * di halaman HRD, sama pola-nya seperti Evaluation::scopeTahunAktif()).
     * Checklist yang tersimpan TAPI milik tahun lain dianggap belum
     * dicentang untuk tahun yang dicek - lihat migration
     * add_tahun_to_checklist_pertemuan_columns.
     */
    public function pegawaiSudahKonfirmasiPertemuan(?int $tahun = null): bool
    {
        return ! is_null($this->pegawai_konfirmasi_pertemuan_at)
            && $this->pegawai_konfirmasi_pertemuan_tahun === ($tahun ?? now()->year);
    }

    /**
     * Apakah PENILAI (users.supervisor_id akun ini) sudah mencentang
     * checklist "sudah bertemu & evaluasi" untuk pegawai ini. Kolom ini
     * disimpan di baris pegawai (bukan baris penilai), karena satu
     * pegawai hanya punya satu Penilai yang ditugaskan pada satu waktu.
     * Lihat OfficialController::toggleChecklistPertemuanPegawai().
     */
    public function penilaiSudahKonfirmasiPertemuan(?int $tahun = null): bool
    {
        return ! is_null($this->penilai_konfirmasi_pertemuan_at)
            && $this->penilai_konfirmasi_pertemuan_tahun === ($tahun ?? now()->year);
    }

    /**
     * Syarat tambahan sebelum HRD boleh mencetak PDF: checklist
     * pertemuan dari PEGAWAI dan PENILAI harus sama-sama sudah
     * dicentang, UNTUK TAHUN YANG SAMA. Lihat HrdController::pdf().
     */
    public function checklistPertemuanLengkap(?int $tahun = null): bool
    {
        // Kalau Penilai (users.supervisor_id) ATAU Atasan Pejabat
        // (users.atasan_pejabat_id) pegawai ini menilai secara manual,
        // seluruh proses pertemuan & evaluasi dianggap berjalan di luar
        // sistem - jadi checklist "sudah bertemu" (baik bagian PEGAWAI
        // maupun PENILAI) tidak perlu dicentang lewat sistem sama sekali.
        // Lihat penilaianUtamaManual() & tanggapanAtasanManual().
        if ($this->penilaianUtamaManual() || $this->tanggapanAtasanManual()) {
            return true;
        }

        return $this->pegawaiSudahKonfirmasiPertemuan($tahun)
            && $this->penilaiSudahKonfirmasiPertemuan($tahun);
    }

    /**
     * Apakah HRD sudah menandatangani penilaian PEGAWAI ini (Evaluation
     * dari Penilai langsung, users.supervisor_id) untuk tahun yang
     * diberikan. Dipakai untuk MENGUNCI checklist pertemuan (baik milik
     * PEGAWAI maupun PENILAI) beserta selfie/bukti-nya begitu HRD sudah
     * tanda tangan - lihat EmployeeController::toggleChecklistPertemuan()
     * & OfficialController::toggleChecklistPertemuanPegawai(). Alurnya:
     * selfie/checklist -> HRD tanda tangan -> checklist (& selfie-nya)
     * terkunci, tidak bisa dicentang/dibatalkan/diganti lagi.
     */
    public function hrdSudahMenandatanganiPenilaian(?int $tahun = null): bool
    {
        return Evaluation::where('employee_id', $this->id)
            ->where('official_id', $this->supervisor_id)
            ->tahunAktif($tahun)
            ->whereNotNull('hrd_signature')
            ->exists();
    }

    /**
     * Versi PEJABAT dari hrdSudahMenandatanganiPenilaian() di atas - cek
     * OfficialEvaluation (bukan Evaluation) untuk tahun yang diberikan.
     * Lihat OfficialController::toggleChecklistPertemuanSaya() &
     * SupervisorController::toggleChecklistPertemuanPejabat().
     */
    public function hrdSudahMenandatanganiPenilaianPejabat(?int $tahun = null): bool
    {
        return OfficialEvaluation::where('official_id', $this->id)
            ->where('supervisor_id', $this->supervisor_id)
            ->tahunAktif($tahun)
            ->whereNotNull('hrd_signature')
            ->exists();
    }

    /**
     * Apakah ATASAN PENILAI (SupervisorFeedback - lihat
     * OfficialController::giveTanggapanPegawai()) sudah mengisi Tanggapan
     * Atasan untuk pegawai ini, di tahun aktif berjalan. Dipakai sebagai
     * syarat sebelum checklist pertemuan pegawai (baik milik PEGAWAI
     * maupun PENILAI-nya, lihat checklistPertemuanBolehDiisi() &
     * checklistPertemuanPenilaiBolehDiisi()) boleh mulai dicentang - versi
     * pegawai dari atasanPenilaiPejabatSudahMenanggapi() di bawah.
     */
    public function atasanPenilaiSudahMenanggapi(): bool
    {
        return SupervisorFeedback::where('employee_id', $this->id)
            ->tahunAktif()
            ->exists();
    }

    /**
     * Syarat sebelum checklist "sudah bertemu & evaluasi" - baik milik
     * PEGAWAI (pegawai_konfirmasi_pertemuan_at) maupun milik PENILAI
     * (penilai_konfirmasi_pertemuan_at) - boleh MULAI dicentang untuk
     * pegawai ini: checklist baru boleh dicentang setelah Atasan Penilai
     * (SupervisorFeedback) menyelesaikan tanggapannya, DAN pegawai ini
     * memang sudah "siap dinilai" (korelasi & kehadiran lengkap - lihat
     * siapDinilaiPenilai()). Syarat kedua ini seharusnya otomatis
     * terpenuhi kalau alurnya normal (evaluate() sendiri sudah dikunci
     * siapDinilaiPenilai(), lihat OfficialController::evaluate()), tapi
     * tetap dicek eksplisit di sini sebagai jaring pengaman supaya
     * tombol checklist tidak pernah aktif untuk pegawai yang statusnya
     * masih "Belum bisa dinilai" di dashboard (mis. data tidak
     * konsisten/sisa siklus lama). Membatalkan checklist yang sudah
     * tercentang tetap selalu boleh kapan saja (lihat pengecekan di
     * controller, method ini hanya dipakai sebelum MENCENTANG, bukan
     * sebelum membatalkan). Versi pegawai dari
     * checklistPertemuanPejabatBolehDiisi() di bawah.
     */
    public function checklistPertemuanBolehDiisi(): bool
    {
        return $this->siapDinilaiPenilai() && (
            $this->atasanPenilaiSudahMenanggapi()
            // Atau: Atasan Pejabat (users.atasan_pejabat_id) yang
            // ditugaskan ke akun ini menilai secara manual - dia tidak
            // akan pernah mengisi SupervisorFeedback lewat sistem, jadi
            // checklist tidak boleh terkunci menunggu itu selamanya.
            // Lihat tanggapanAtasanManual().
            || $this->tanggapanAtasanManual()
        );
    }

    /**
     * Versi PENILAI dari checklistPertemuanBolehDiisi() di atas - syarat
     * yang sama (siap dinilai DAN Atasan Penilai/SupervisorFeedback sudah
     * menanggapi) berlaku juga sebelum checklist milik PENILAI boleh
     * mulai dicentang.
     */
    public function checklistPertemuanPenilaiBolehDiisi(): bool
    {
        return $this->siapDinilaiPenilai()
            && ($this->atasanPenilaiSudahMenanggapi() || $this->tanggapanAtasanManual());
    }

    /**
     * Apakah PEJABAT ini (akun yang sedang dinilai lewat OfficialEvaluation)
     * sudah mencentang checklist "sudah bertemu & evaluasi" untuk dirinya
     * sendiri. Versi pejabat dari pegawaiSudahKonfirmasiPertemuan() - lihat
     * OfficialController::toggleChecklistPertemuanSaya().
     */
    public function pejabatSudahKonfirmasiPertemuan(?int $tahun = null): bool
    {
        return ! is_null($this->pejabat_konfirmasi_pertemuan_at)
            && $this->pejabat_konfirmasi_pertemuan_tahun === ($tahun ?? now()->year);
    }

    /**
     * Apakah ATASAN (users.supervisor_id akun pejabat ini, yang menilai
     * lewat OfficialEvaluation) sudah mencentang checklist "sudah bertemu &
     * evaluasi" untuk pejabat ini. Kolom ini disimpan di baris pejabat
     * (bukan baris atasan), sama pola-nya seperti
     * penilaiSudahKonfirmasiPertemuan(). Lihat
     * SupervisorController::toggleChecklistPertemuanPejabat().
     */
    public function atasanSudahKonfirmasiPertemuan(?int $tahun = null): bool
    {
        return ! is_null($this->atasan_konfirmasi_pertemuan_at)
            && $this->atasan_konfirmasi_pertemuan_tahun === ($tahun ?? now()->year);
    }

    /**
     * Syarat tambahan sebelum HRD boleh mencetak PDF penilaian PEJABAT:
     * checklist pertemuan dari PEJABAT dan ATASAN harus sama-sama sudah
     * dicentang, UNTUK TAHUN YANG SAMA. Versi pejabat dari
     * checklistPertemuanLengkap(). Lihat HrdController::officialPdf().
     */
    public function checklistPertemuanPejabatLengkap(?int $tahun = null): bool
    {
        // Sama seperti checklistPertemuanLengkap() versi pegawai - kalau
        // Penilai (users.supervisor_id) ATAU Atasan Penilai (users.
        // atasan_penilai_pejabat_id) pejabat ini menilai secara manual
        // (lihat tanggapanPenilaiPejabatManual()), checklist "sudah
        // bertemu" (bagian PEJABAT maupun ATASAN) tidak perlu dicentang
        // lewat sistem sama sekali.
        if ($this->tanggapanPenilaiPejabatManual()) {
            return true;
        }

        return $this->pejabatSudahKonfirmasiPertemuan($tahun)
            && $this->atasanSudahKonfirmasiPertemuan($tahun);
    }

    /**
     * Label bukti checklist "sudah bertemu & evaluasi" untuk
     * ditampilkan ke HRD/pegawai/penilai/pejabat/atasan - lihat kolom
     * *_konfirmasi_pertemuan_evidence_type (migration
     * add_evidence_type_to_checklist_pertemuan_columns) & partial
     * resources/views/partials/checklist-selfie-toggle.blade.php.
     * 'upload' = Online (upload bukti Zoom/Telpon/Chat), 'selfie' =
     * Offline (ketemu langsung, foto selfie kamera). Data lama
     * (sebelum fitur pilihan metode ada) selalu berasal dari selfie
     * kamera, jadi null/tidak dikenali dianggap 'selfie' (Offline)
     * supaya data lama tetap tampil benar.
     */
    public static function checklistEvidenceLabel(?string $evidenceType): string
    {
        return $evidenceType === 'upload' ? 'Online' : 'Offline';
    }

    /**
     * Daftar metode pertemuan yang bisa dipilih saat bukti checklist
     * "sudah bertemu & evaluasi" adalah Online (evidence_type =
     * 'upload') - lihat migration
     * add_meeting_metode_to_checklist_pertemuan_columns & partial
     * resources/views/partials/checklist-selfie-toggle.blade.php.
     * Sekadar keterangan, tidak mempengaruhi cara file disimpan.
     */
    public const CHECKLIST_MEETING_METHODS = [
        'zoom'   => 'Zoom',
        'telpon' => 'Telpon',
        'chat'   => 'Chat',
    ];

    /**
     * Label metode pertemuan (Zoom/Telpon/Chat) untuk ditampilkan di
     * samping label bukti checklist. Null kalau memang tidak diisi
     * (mis. bukti Offline/selfie, yang tidak punya metode pertemuan).
     */
    public static function checklistMeetingMethodLabel(?string $method): ?string
    {
        return self::CHECKLIST_MEETING_METHODS[$method] ?? null;
    }

    /**
     * Apakah ATASAN PENILAI (users.atasan_penilai_pejabat_id pejabat ini)
     * sudah mengisi Tanggapan Atasan (OfficialSupervisorFeedback) untuk
     * pejabat ini, di tahun aktif berjalan. Dipakai sebagai syarat
     * sebelum checklist pertemuan pejabat (baik milik PEJABAT maupun
     * ATASAN/Penilai-nya, lihat checklistPertemuanPejabatBolehDiisi())
     * boleh mulai dicentang - sama pola-nya seperti pengecekan di
     * OfficialController::respondEvaluation().
     */
    public function atasanPenilaiPejabatSudahMenanggapi(): bool
    {
        return OfficialSupervisorFeedback::where('official_id', $this->id)
            ->tahunAktif()
            ->exists();
    }

    /**
     * Syarat sebelum checklist pertemuan pejabat - baik milik PEJABAT
     * (pejabat_konfirmasi_pertemuan_at, lihat
     * OfficialController::toggleChecklistPertemuanSaya()) maupun milik
     * ATASAN/Penilai pejabat (atasan_konfirmasi_pertemuan_at, lihat
     * SupervisorController::toggleChecklistPertemuanPejabat()) - boleh
     * MULAI dicentang untuk pejabat ini.
     *
     * CATATAN: sebelumnya checklist ini INDEPENDEN dari Tanggapan Atasan
     * (OfficialSupervisorFeedback / "Atasan Penilai"), tapi atas
     * permintaan terbaru, dependency ini DIHIDUPKAN KEMBALI khusus untuk
     * checklist pejabat - checklist baru boleh dicentang setelah Atasan
     * Penilai menyelesaikan tanggapannya, DAN pejabat ini memang sudah
     * "siap dinilai" (korelasi & kehadiran lengkap - lihat
     * siapDinilaiPenilaiPejabat()) - jaring pengaman yang sama seperti
     * checklistPertemuanPenilaiBolehDiisi() versi pegawai, supaya tombol
     * checklist tidak pernah aktif untuk pejabat yang statusnya masih
     * "Belum bisa dinilai" di dashboard. Membatalkan checklist yang
     * sudah tercentang tetap selalu boleh kapan saja (lihat
     * pengecekan di controller, method ini hanya dipakai sebelum
     * MENCENTANG, bukan sebelum membatalkan).
     */
    public function checklistPertemuanPejabatBolehDiisi(): bool
    {
        return $this->siapDinilaiPenilaiPejabat() && $this->atasanPenilaiPejabatSudahMenanggapi();
    }

    /**
     * Syarat gabungan sebelum ATASAN (users.supervisor_id pejabat ini)
     * boleh menilai pejabat ini lewat OfficialEvaluation: korelasi sudah
     * memberi tanggapan (minimal MIN_TANGGAPAN_KORELASI_PEJABAT) DAN hrd
     * sudah mengisi data kehadiran. Versi pejabat dari
     * siapDinilaiPenilai() di atas - dipakai
     * SupervisorController::showOfficial()/evaluateOfficial() supaya
     * alur penilaian pejabat sama persis dengan alur penilaian pegawai,
     * cuma beda penamaan.
     */
    public function siapDinilaiPenilaiPejabat(): bool
    {
        return $this->korelasiPejabatSudahMemberiTanggapan() && $this->kehadiranSudahDiisiHrd();
    }

    public function feedbacksGiven()
    {
        return $this->hasMany(
            Feedback::class,
            'reviewer_id'
        );
    }

    public function feedbacksReceived()
    {
        return $this->hasMany(
            Feedback::class,
            'employee_id'
        );
    }

    public function evaluations()
    {
        return $this->hasMany(
            Evaluation::class,
            'employee_id'
        );
    }

    public function evaluationsGiven()
    {
        return $this->hasMany(
            Evaluation::class,
            'official_id'
        );
    }

    public function supervisorFeedbacks()
    {
        return $this->hasMany(
            SupervisorFeedback::class,
            'employee_id'
        );
    }

    // Atasan penilai (juga ber-role "pejabat") yang ditugaskan ke akun ini.
    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    // Daftar pejabat/pegawai yang berada di bawah bimbingan akun ini
    // sebagai "Atasan Penilai" (users.supervisor_id).
    public function pejabatBinaan()
    {
        return $this->hasMany(User::class, 'supervisor_id');
    }

    // Atasan Pejabat yang ditugaskan khusus untuk mengisi "Tanggapan
    // Atasan" (SupervisorFeedback) pegawai ini. Terpisah dari supervisor()
    // yang dipakai untuk Penilaian Kinerja.
    public function atasanPejabat()
    {
        return $this->belongsTo(User::class, 'atasan_pejabat_id');
    }

    // Daftar pegawai yang ditugaskan ke akun ini sebagai Atasan Pejabat
    // (users.atasan_pejabat_id).
    public function pegawaiDenganAtasanPejabatIni()
    {
        return $this->hasMany(User::class, 'atasan_pejabat_id');
    }

    // Penilaian yang diterima akun ini sebagai pejabat.
    public function officialEvaluations()
    {
        return $this->hasMany(
            OfficialEvaluation::class,
            'official_id'
        );
    }

    // Penilaian yang diberikan akun ini sebagai atasan dari pejabat lain.
    public function officialEvaluationsGiven()
    {
        return $this->hasMany(
            OfficialEvaluation::class,
            'supervisor_id'
        );
    }

    // Atasan Penilai (juga ber-role pejabat/hrd) yang ditugaskan khusus
    // untuk mengisi "Tanggapan Atasan" (OfficialSupervisorFeedback) untuk
    // akun pejabat ini. Terpisah dari supervisor() yang dipakai untuk
    // Penilaian Kinerja (OfficialEvaluation) - sama pola-nya seperti
    // atasan_pejabat_id untuk pegawai.
    public function atasanPenilaiPejabat()
    {
        return $this->belongsTo(User::class, 'atasan_penilai_pejabat_id');
    }

    // Daftar pejabat yang ditugaskan ke akun ini sebagai Atasan Penilai
    // (users.atasan_penilai_pejabat_id).
    public function pejabatDenganAtasanPenilaiIni()
    {
        return $this->hasMany(User::class, 'atasan_penilai_pejabat_id');
    }

    // Tanggapan Atasan (OfficialSupervisorFeedback) yang diterima akun ini
    // sebagai pejabat.
    public function officialSupervisorFeedbacks()
    {
        return $this->hasMany(
            OfficialSupervisorFeedback::class,
            'official_id'
        );
    }

    // Daftar FCM token (device/browser) milik akun ini. Satu user bisa
    // login dari beberapa device/browser sekaligus - lihat FcmToken &
    // FcmTokenController. Dipakai FirebaseCloudMessagingService untuk
    // mengirim push notification ke semua device user ini.
    public function fcmTokens()
    {
        return $this->hasMany(FcmToken::class);
    }

    // Inbox notifikasi in-app milik user ini (lihat Notification model &
    // FirebaseCloudMessagingService::sendToUser() untuk titik pengisiannya).
    public function notifications()
    {
        return $this->hasMany(\App\Models\Notification::class)->latest();
    }
}
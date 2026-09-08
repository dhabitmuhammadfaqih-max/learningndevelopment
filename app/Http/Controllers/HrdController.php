<?php

namespace App\Http\Controllers;

use App\Http\Concerns\FiltersByTahun;
use App\Models\User;
use App\Models\Feedback;
use App\Services\NotificationTriggerService;
use App\Models\Evaluation;
use App\Models\SupervisorFeedback;
use App\Models\OfficialEvaluation;
use App\Models\OfficialSupervisorFeedback;
use App\Models\SignatureDocument;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\IOFactory;

class HrdController extends Controller
{
    use FiltersByTahun;

    private const EVALUATOR_ROLES = ['pejabat', 'hrd'];

    private const ROLES_WITH_EVALUATOR = ['pejabat', 'pegawai'];

    // Role yang memakai kolom atasan_penilai_pejabat_id sebagai "Atasan
    // Penilai"-nya (Tanggapan Atasan). Pegawai punya kolom sendiri
    // (atasan_pejabat_id) - lihat storeAccount()/updateAccount().
    private const ROLES_WITH_ATASAN_PENILAI_PEJABAT = ['pejabat', 'hrd'];

    /**
     * Halaman Ringkasan (Dashboard Saya) - HRD tidak menilai pegawai/
     * pejabat (itu tugas Pejabat/Atasan Penilai), jadi halaman ini
     * berisi ringkasan angka saja: total akun per role, dan berapa
     * pegawai/pejabat yang datanya sudah lengkap & bisa di-print PDF vs
     * yang belum. Detail per orang ada di halaman Lihat Pegawai/Lihat
     * Pejabat/Semua Akun masing-masing (lihat accounts(),
     * employeesIndex(), officialsIndex()).
     */
    public function index(Request $request)
    {
        // Selector Tahun: lihat App\Http\Concerns\FiltersByTahun. Default-nya
        // tahun berjalan, tapi HRD bisa ganti lewat dropdown di halaman ini
        // untuk melihat ringkasan tahun-tahun sebelumnya.
        $tahun = $this->selectedTahun($request);
        $availableTahun = $this->availableTahunOptions();

        $accountsByRole = User::selectRaw('role, count(*) as total')
            ->groupBy('role')
            ->pluck('total', 'role');

        // Dibatasi ke tahun yang dipilih ($tahun) supaya status "siap
        // di-print PDF" di dashboard ini konsisten dengan pdf()/
        // officialPdf() yang juga dibatasi ke tahun yang sama - kalau
        // tidak, badge bisa bilang "siap" berdasarkan data tahun lain
        // padahal PDF tahun yang sedang dilihat belum bisa dibuat.
        $employees = User::where('role', 'pegawai')
            // supervisor & atasanPejabat di-eager-load supaya
            // penilaianUtamaManual()/tanggapanAtasanManual() tidak N+1
            // query per baris pegawai.
            ->with(['supervisor', 'atasanPejabat'])
            ->withCount('feedbacksReceived')
            ->withCount(['evaluations as evaluations_count' => function ($query) use ($tahun) {
                $query->tahunAktif($tahun);
            }])
            ->withCount([
                'evaluations as primary_evaluation_count' => function ($query) use ($tahun) {
                    $query->whereColumn('official_id', 'users.supervisor_id')->tahunAktif($tahun);
                },
            ])
            ->withCount(['supervisorFeedbacks as supervisor_feedbacks_count' => function ($query) use ($tahun) {
                $query->tahunAktif($tahun);
            }])
            ->withCount([
                // Dipakai untuk ringkasan "Siap Ditandatangani HRD" di
                // dashboard - lihat HrdController::signAsHrd() yang
                // mewajibkan pegawai tanda tangan dulu sebelum HRD boleh
                // menandatangani.
                'evaluations as primary_evaluation_employee_signed_count' => function ($query) use ($tahun) {
                    $query->whereColumn('official_id', 'users.supervisor_id')
                        ->tahunAktif($tahun)
                        ->whereNotNull('employee_signature');
                },
            ])
            ->withCount([
                'evaluations as primary_evaluation_hrd_signed_count' => function ($query) use ($tahun) {
                    $query->whereColumn('official_id', 'users.supervisor_id')
                        ->tahunAktif($tahun)
                        ->whereNotNull('hrd_signature');
                },
            ])
            ->get();

        // Syarat "siap di-print PDF" harus sama dengan yang dipakai di
        // employeesIndex()/pdf() supaya angkanya konsisten di semua
        // halaman.
        $employeeReadyCount = $employees->filter(function ($employee) use ($tahun) {
            $hasPrimaryEvaluation = $employee->primary_evaluation_count > 0
                || $employee->penilaianUtamaManual();
            $hasAtasanEvaluation =
                ($employee->evaluations_count > $employee->primary_evaluation_count)
                || $employee->supervisor_feedbacks_count > 0
                || $employee->tanggapanAtasanManual();

            return ($employee->is_spg || $employee->feedbacks_received_count >= 3)
                && $hasPrimaryEvaluation
                && $hasAtasanEvaluation
                // Checklist pertemuan pegawai & penilai harus lengkap juga
                // supaya angka "siap print" konsisten dengan pdf() - lihat
                // User::checklistPertemuanLengkap().
                && $employee->checklistPertemuanLengkap($tahun);
        })->count();

        // Pegawai yang sudah tanda tangan tapi HRD belum - lihat
        // HrdController::signAsHrd(). Kalau Penilai/Atasan pegawai ini
        // menilai secara manual, HRD boleh tanda tangan begitu baris
        // Evaluation-nya ada, TANPA menunggu tanda tangan pegawai dulu -
        // jadi dihitung "siap tanda tangan" cukup dari
        // primary_evaluation_count, bukan primary_evaluation_employee_signed_count.
        $employeeReadyToSignCount = $employees->filter(function ($employee) {
            if ($employee->primary_evaluation_hrd_signed_count > 0) {
                return false;
            }

            if ($employee->penilaianUtamaManual() || $employee->tanggapanAtasanManual()) {
                return $employee->primary_evaluation_count > 0;
            }

            return $employee->primary_evaluation_employee_signed_count > 0;
        })->count();

        $officials = User::where('role', 'pejabat')
            // supervisor di-eager-load supaya tanggapanPenilaiPejabatManual()
            // tidak N+1 query per baris pejabat.
            ->with('supervisor')
            ->withCount('feedbacksReceived')
            ->withCount([
                'officialEvaluations as official_evaluation_count' => function ($query) use ($tahun) {
                    $query->whereColumn('supervisor_id', 'users.supervisor_id')->tahunAktif($tahun);
                },
            ])
            // Tanda tangan HRD pada OfficialEvaluation yang aktif - syarat
            // tambahan supaya angka "siap print" konsisten dengan
            // officialPdf() yang sekarang juga mewajibkan hrd_signature.
            ->withCount([
                'officialEvaluations as official_evaluation_hrd_signed_count' => function ($query) use ($tahun) {
                    $query->whereColumn('supervisor_id', 'users.supervisor_id')
                        ->whereNotNull('hrd_signature')
                        ->tahunAktif($tahun);
                },
            ])
            ->withCount([
                // Dipakai untuk ringkasan "Siap Ditandatangani HRD" di
                // dashboard - lihat HrdController::signAsHrdOfficial().
                'officialEvaluations as official_evaluation_employee_signed_count' => function ($query) use ($tahun) {
                    $query->whereColumn('supervisor_id', 'users.supervisor_id')
                        ->whereNotNull('employee_signature')
                        ->tahunAktif($tahun);
                },
            ])
            ->get();

        $officialReadyCount = $officials->filter(function ($official) use ($tahun) {
            // tanggapanPenilaiPejabatManual() juga true kalau Penilai
            // (users.supervisor_id) pejabat ini menilai SEMUA bawahannya
            // secara manual (User::menilaiSecaraManual()) - lihat catatan
            // di HrdController::officialPdf().
            $penilaiManual = $official->tanggapanPenilaiPejabatManual();

            return $official->feedbacks_received_count >= User::MIN_TANGGAPAN_KORELASI
                && ($official->official_evaluation_count > 0 || $penilaiManual)
                // Checklist pertemuan pejabat & atasan harus lengkap juga
                // supaya angka "siap print" konsisten dengan officialPdf() -
                // lihat User::checklistPertemuanPejabatLengkap().
                && $official->checklistPertemuanPejabatLengkap($tahun)
                // Tanda tangan HRD wajib ada sebelum dianggap "siap print" -
                // lihat officialPdf().
                && ($official->official_evaluation_hrd_signed_count > 0 || $penilaiManual);
        })->count();

        // Pejabat yang sudah tanda tangan tapi HRD belum - lihat
        // HrdController::signAsHrdOfficial(). Kalau Penilai pejabat ini
        // menilai secara manual, HRD boleh tanda tangan begitu baris
        // OfficialEvaluation-nya ada, TANPA menunggu tanda tangan pejabat
        // dulu - jadi dihitung "siap tanda tangan" cukup dari
        // official_evaluation_count, bukan official_evaluation_employee_signed_count.
        $officialReadyToSignCount = $officials->filter(function ($official) {
            if ($official->official_evaluation_hrd_signed_count > 0) {
                return false;
            }

            if ($official->tanggapanPenilaiPejabatManual()) {
                return $official->official_evaluation_count > 0;
            }

            return $official->official_evaluation_employee_signed_count > 0;
        })->count();

        $employeeTotal = $employees->count();
        $officialTotal = $officials->count();

        return view('admin.dashboard', compact(
            'accountsByRole',
            'employeeTotal',
            'employeeReadyCount',
            'employeeReadyToSignCount',
            'officialTotal',
            'officialReadyCount',
            'officialReadyToSignCount',
            'tahun',
            'availableTahun'
        ));
    }

    /**
     * AJAX polling status untuk admin.dashboard - lihat pola & alasan di
     * EmployeeController::statusVersion(). HRD melihat ringkasan seluruh
     * sistem, jadi versinya diambil dari total baris + waktu perubahan
     * terakhir di tabel-tabel utama, dilingkupi ke tahun yang sedang
     * dilihat ($tahun) supaya konsisten dengan angka di index().
     */
    public function statusVersion(Request $request)
    {
        $tahun = $this->selectedTahun($request);

        $latestEvaluation = Evaluation::tahunAktif($tahun)->latest()->value('updated_at');
        $latestOfficialEvaluation = OfficialEvaluation::tahunAktif($tahun)->latest()->value('updated_at');
        $latestSupervisorFeedback = SupervisorFeedback::tahunAktif($tahun)->latest()->value('updated_at');
        $userCount = User::count();

        $version = md5(
            $latestEvaluation . '|' . $latestOfficialEvaluation . '|'
            . $latestSupervisorFeedback . '|' . $userCount
        );

        return response()->json(['version' => $version]);
    }

    /**
     * Semua kolom database yang mungkin menyimpan path ke disk 'public'
     * (folder checklist-selfies/ & signatures/) - dipakai sebagai daftar
     * "yang masih dipakai" oleh storageCleanup*() di bawah, supaya file
     * yang masih dirujuk kolom manapun TIDAK PERNAH ikut kehapus, walau
     * umurnya sudah lama. Kalau nanti nambah kolom path baru (fitur baru
     * yang simpan selfie/signature), WAJIB ditambahkan juga di sini -
     * kalau lupa, file dari fitur itu akan salah dianggap "orphan" dan
     * bisa ikut terhapus.
     */
    private function referencedStoragePaths()
    {
        return collect()
            ->merge(User::query()->pluck('pegawai_konfirmasi_pertemuan_selfie'))
            ->merge(User::query()->pluck('penilai_konfirmasi_pertemuan_selfie'))
            ->merge(User::query()->pluck('pejabat_konfirmasi_pertemuan_selfie'))
            ->merge(User::query()->pluck('atasan_konfirmasi_pertemuan_selfie'))
            ->merge(Evaluation::query()->pluck('signature'))
            ->merge(Evaluation::query()->pluck('employee_signature'))
            ->merge(Evaluation::query()->pluck('hrd_signature'))
            ->merge(OfficialEvaluation::query()->pluck('signature'))
            ->merge(OfficialEvaluation::query()->pluck('employee_signature'))
            ->merge(OfficialEvaluation::query()->pluck('hrd_signature'))
            ->merge(Feedback::query()->pluck('signature'))
            ->merge(SupervisorFeedback::query()->pluck('signature'))
            ->merge(OfficialSupervisorFeedback::query()->pluck('signature'))
            ->merge(SignatureDocument::query()->pluck('pegawai_signature'))
            ->merge(SignatureDocument::query()->pluck('pejabat_signature'))
            ->merge(SignatureDocument::query()->pluck('atasan_signature'))
            ->filter()
            ->unique()
            ->flip(); // flip supaya cek "sudah dipakai?" nanti O(1) pakai isset(), bukan in_array() yang lambat kalau datanya ribuan.
    }

    /**
     * Halaman "Bersihkan File Lama": daftar file ORPHAN (file fisik di
     * disk 'public' folder checklist-selfies/ & signatures/ yang TIDAK
     * dirujuk kolom manapun di database - lihat referencedStoragePaths())
     * beserta umurnya, supaya HRD bisa pilih & hapus file sampah yang
     * aman dihapus tanpa perlu masuk ke server.
     *
     * Filter umur (?older_than=1_bulan / 3_bulan / 6_bulan / 1_tahun)
     * HANYA mempersempit daftar yang ditampilkan - tetap hanya dari
     * kumpulan file yang SUDAH orphan, bukan filter umur ke SEMUA file
     * (yang masih dipakai tidak pernah muncul di halaman ini sama
     * sekali, seberapa pun lamanya).
     */
    public function storageCleanupIndex(Request $request)
    {
        $olderThan = $request->query('older_than');

        $thresholds = [
            '1_bulan' => now()->subMonth(),
            '3_bulan' => now()->subMonths(3),
            '6_bulan' => now()->subMonths(6),
            '1_tahun' => now()->subYear(),
        ];

        $referenced = $this->referencedStoragePaths();

        $diskFiles = collect(Storage::disk('public')->files('checklist-selfies'))
            ->merge(Storage::disk('public')->files('signatures'));

        $orphans = $diskFiles
            ->reject(fn ($path) => isset($referenced[$path]))
            ->map(function ($path) {
                return [
                    'path'        => $path,
                    'size'        => Storage::disk('public')->size($path),
                    'modified_at' => \Illuminate\Support\Carbon::createFromTimestamp(
                        Storage::disk('public')->lastModified($path)
                    ),
                ];
            })
            ->when($olderThan && isset($thresholds[$olderThan]), function ($collection) use ($olderThan, $thresholds) {
                return $collection->filter(fn ($f) => $f['modified_at']->lt($thresholds[$olderThan]));
            })
            ->sortBy('modified_at')
            ->values();

        $totalSize = $orphans->sum('size');

        return view('admin.storage-cleanup', [
            'orphans'   => $orphans->map(function ($f) {
                $f['size_formatted'] = $this->formatBytes($f['size']);
                return $f;
            }),
            'totalSize'          => $totalSize,
            'totalSizeFormatted' => $this->formatBytes($totalSize),
            'olderThan'          => $olderThan,
        ]);
    }

    /**
     * Format byte jadi string yang gampang dibaca (KB/MB/GB) - dipakai
     * storageCleanupIndex() untuk ukuran per file & total.
     */
    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
        if ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
        if ($bytes >= 1024) return number_format($bytes / 1024, 2) . ' KB';
        return $bytes . ' B';
    }

    /**
     * Hapus file-file yang dipilih HRD dari halaman storage-cleanup.
     *
     * JARING PENGAMAN: path yang boleh dihapus HARUS lolos KEDUA syarat
     * ini, dicek ULANG di sini (bukan cuma percaya checkbox dari
     * browser) - supaya request yang diakali/diedit manual tidak bisa
     * menghapus file yang sebenarnya masih dipakai:
     * 1) Path-nya memang ada secara fisik di folder checklist-selfies/
     *    atau signatures/ (bukan path sembarangan/di luar folder itu),
     * 2) Path-nya TIDAK ada di referencedStoragePaths() saat request ini
     *    diproses (dicek ulang real-time, bukan pakai daftar orphan lama
     *    dari saat halaman dimuat - kalau di antara halaman dimuat & klik
     *    hapus ternyata path itu baru saja dipakai lagi, otomatis dilewati).
     */
    public function storageCleanupDestroy(Request $request)
    {
        $validated = $request->validate([
            'paths'   => 'required|array|min:1',
            'paths.*' => 'string',
        ]);

        $allowedPrefixes = ['checklist-selfies/', 'signatures/'];
        $referenced = $this->referencedStoragePaths();

        $safeToDelete = collect($validated['paths'])
            ->filter(function ($path) use ($allowedPrefixes) {
                foreach ($allowedPrefixes as $prefix) {
                    if (str_starts_with($path, $prefix)) return true;
                }
                return false;
            })
            ->filter(fn ($path) => ! isset($referenced[$path]))
            ->filter(fn ($path) => Storage::disk('public')->exists($path))
            ->values();

        $skippedCount = count($validated['paths']) - $safeToDelete->count();

        if ($safeToDelete->isNotEmpty()) {
            Storage::disk('public')->delete($safeToDelete->all());
        }

        $message = $safeToDelete->count() . ' file berhasil dihapus.';
        if ($skippedCount > 0) {
            $message .= ' ' . $skippedCount . ' file dilewati (sudah tidak ada / ternyata masih dipakai).';
        }

        return back()->with('success', $message);
    }

    /**
     * Halaman "Semua Akun": kelola (tambah/edit/hapus/import) akun.
     */
    public function accounts()
    {
        $accounts = User::orderBy('role')
            ->orderBy('name')
            ->with(['supervisor', 'atasanPejabat', 'atasanPenilaiPejabat'])
            ->get();

        $atasanList = User::where(function ($query) {
                $query->whereIn('role', self::EVALUATOR_ROLES)
                    ->orWhere(function ($query) {
                        $query->where('role', 'pegawai')
                            ->where('boleh_menilai_pegawai_lain', true);
                    });
            })
            ->orderBy('name')
            ->get();

        return view('admin.accounts', compact('accounts', 'atasanList'));
    }

    /**
     * Halaman "Lihat Pegawai": kehadiran & status kontrak pegawai.
     */
    public function employeesIndex(Request $request)
    {
        $tahun = $this->selectedTahun($request);
        $availableTahun = $this->availableTahunOptions();

        // Dibatasi ke tahun yang dipilih ($tahun), sama seperti index() di
        // atas, supaya badge status konsisten dengan pdf() yang juga
        // dibatasi ke tahun yang sama.
        $employees = User::where('role', 'pegawai')
            // supervisor & atasanPejabat di-eager-load supaya
            // penilaianUtamaManual()/tanggapanAtasanManual() (dipakai di
            // admin.employees untuk badge "siap PDF") tidak N+1 query per
            // baris pegawai.
            ->with(['supervisor', 'atasanPejabat'])
            ->withCount('feedbacksReceived')
            ->withCount(['evaluations as evaluations_count' => function ($query) use ($tahun) {
                $query->tahunAktif($tahun);
            }])
            ->withCount([
                // Penilaian dari "Penilai" langsung yang ditugaskan HRD
                // (users.supervisor_id) - lihat catatan di HrdController::show().
                // Kalau total penilaian (evaluations_count) lebih besar dari
                // ini, berarti ada penilaian tambahan dari Atasan Penilai
                // yang sekarang berfungsi sebagai "Tanggapan Atasan".
                'evaluations as primary_evaluation_count' => function ($query) use ($tahun) {
                    $query->whereColumn('official_id', 'users.supervisor_id')->tahunAktif($tahun);
                },
            ])
            ->withCount([
                // Dipakai untuk syarat baru "pegawai wajib mengisi
                // tanggapan atas penilaiannya sendiri sebelum PDF" - lihat
                // Evaluation::employee_response & HrdController::pdf().
                // Dibatasi ke penilaian utama (official_id = supervisor_id)
                // yang sama seperti primary_evaluation_count di atas.
                'evaluations as primary_evaluation_responded_count' => function ($query) use ($tahun) {
                    $query->whereColumn('official_id', 'users.supervisor_id')
                        ->tahunAktif($tahun)
                        ->whereNotNull('employee_response')
                        ->where('employee_response', '!=', '');
                },
            ])
            ->withCount([
                // Dipakai untuk badge "Siap Ditandatangani HRD" - pegawai
                // sudah tanda tangan (employee_signature) tapi HRD belum -
                // lihat HrdController::signAsHrd() yang sekarang mewajibkan
                // employee_signature terisi dulu sebelum HRD boleh
                // menandatangani.
                'evaluations as primary_evaluation_employee_signed_count' => function ($query) use ($tahun) {
                    $query->whereColumn('official_id', 'users.supervisor_id')
                        ->tahunAktif($tahun)
                        ->whereNotNull('employee_signature');
                },
            ])
            ->withCount([
                'evaluations as primary_evaluation_hrd_signed_count' => function ($query) use ($tahun) {
                    $query->whereColumn('official_id', 'users.supervisor_id')
                        ->tahunAktif($tahun)
                        ->whereNotNull('hrd_signature');
                },
            ])
            // Sumber kedua untuk "Tanggapan Atasan": SupervisorFeedback dari
            // Atasan Pejabat. HrdController::pdf() menerima salah satu dari
            // dua sumber ini (lihat $atasanEvaluation / $atasanFeedback di
            // sana), jadi badge status di halaman ini juga harus ikut
            // menghitung ini - kalau tidak, badge bisa salah bilang "Belum
            // bisa di-print PDF" padahal PDF-nya sudah bisa dibuat.
            ->withCount(['supervisorFeedbacks as supervisor_feedbacks_count' => function ($query) use ($tahun) {
                $query->tahunAktif($tahun);
            }])
            ->get();

        return view('admin.employees', compact('employees', 'tahun', 'availableTahun'));
    }

    /**
     * Halaman "Lihat Pejabat": kehadiran & status kontrak pejabat, sama
     * seperti halaman pegawai di atas.
     */
    public function officialsIndex(Request $request)
    {
        $tahun = $this->selectedTahun($request);
        $availableTahun = $this->availableTahunOptions();

        $officials = User::where('role', 'pejabat')
            ->orderBy('name')
            // supervisor di-eager-load supaya tanggapanPenilaiPejabatManual()
            // (dipakai di admin.officials untuk badge "siap PDF") tidak N+1
            // query per baris pejabat.
            ->with('supervisor')
            ->withCount('feedbacksReceived')
            ->withCount([
                // Penilaian dari Atasan Pejabat yang ditugaskan HRD
                // (users.supervisor_id milik pejabat ini) - dipakai sebagai
                // syarat PDF pejabat sudah bisa di-print, sama seperti
                // HrdController::officialPdf(). Dibatasi ke tahun yang
                // dipilih supaya konsisten dengan officialPdf() yang juga
                // dibatasi ke tahun yang sama.
                'officialEvaluations as official_evaluation_count' => function ($query) use ($tahun) {
                    $query->whereColumn('supervisor_id', 'users.supervisor_id')->tahunAktif($tahun);
                },
            ])
            // Tanda tangan HRD pada OfficialEvaluation yang aktif - dipakai
            // badge "Siap PDF" di admin.officials supaya konsisten dengan
            // officialPdf() yang mewajibkan hrd_signature sebelum dicetak.
            ->withCount([
                'officialEvaluations as official_evaluation_hrd_signed_count' => function ($query) use ($tahun) {
                    $query->whereColumn('supervisor_id', 'users.supervisor_id')
                        ->whereNotNull('hrd_signature')
                        ->tahunAktif($tahun);
                },
            ])
            ->withCount([
                // Dipakai untuk syarat baru "pejabat wajib mengisi
                // tanggapan atas penilaiannya sendiri sebelum PDF" - lihat
                // OfficialEvaluation::employee_response &
                // HrdController::officialPdf().
                'officialEvaluations as official_evaluation_responded_count' => function ($query) use ($tahun) {
                    $query->whereColumn('supervisor_id', 'users.supervisor_id')
                        ->whereNotNull('employee_response')
                        ->where('employee_response', '!=', '')
                        ->tahunAktif($tahun);
                },
            ])
            ->withCount([
                // Dipakai untuk badge "Siap Ditandatangani HRD" - pejabat
                // sudah tanda tangan (employee_signature) tapi HRD belum -
                // lihat HrdController::signAsHrdOfficial().
                'officialEvaluations as official_evaluation_employee_signed_count' => function ($query) use ($tahun) {
                    $query->whereColumn('supervisor_id', 'users.supervisor_id')
                        ->whereNotNull('employee_signature')
                        ->tahunAktif($tahun);
                },
            ])
            ->get();

        // Daftar unit kerja pejabat yang sudah ada, dipakai untuk dropdown
        // filter "Unit" di halaman ini - sama pola-nya seperti
        // EmployeeController::index() (employeeUnits).
        $officialUnits = $officials
            ->pluck('unit_kerja')
            ->map(fn ($unit) => $unit ?: 'Tanpa Unit')
            ->unique()
            ->sort()
            ->values();

        return view('admin.officials', compact('officials', 'officialUnits', 'tahun', 'availableTahun'));
    }

    public function storeAccount(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:100|alpha_dash|unique:users,username',
            'nik' => 'required|digits_between:1,8|unique:users,nik',
            'unit_kerja' => 'nullable|string|max:255',
            'jabatan' => 'nullable|string|max:255',
            'vendor' => 'nullable|string|in:' . implode(',', User::VENDORS),
            'status' => 'nullable|string|in:' . implode(',', User::EMPLOYMENT_STATUSES),
            'role' => 'required|in:pegawai,pejabat,hrd',
            'is_spg' => 'nullable|boolean',
            'boleh_menilai_pegawai_lain' => 'nullable|boolean',
            'menilai_secara_manual' => 'nullable|boolean',
            'supervisor_id' => [
                'nullable',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->whereIn('role', self::EVALUATOR_ROLES)
                        ->orWhere(function ($query) {
                            $query->where('role', 'pegawai')
                                ->where('boleh_menilai_pegawai_lain', true);
                        });
                }),
            ],
            'atasan_pejabat_id' => [
                'nullable',
                Rule::exists('users', 'id')->whereIn('role', self::EVALUATOR_ROLES),
            ],
            'atasan_penilai_pejabat_id' => [
                'nullable',
                Rule::exists('users', 'id')->whereIn('role', self::EVALUATOR_ROLES),
            ],
        ]);

        User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'nik' => $validated['nik'],
            'unit_kerja' => $validated['unit_kerja'] ?? null,
            'jabatan' => $validated['jabatan'] ?? null,
            'vendor' => $validated['vendor'] ?? null,
            'status' => $validated['status'] ?? null,
            'is_spg' => $validated['role'] === 'pegawai' && !empty($validated['is_spg']),
            'boleh_menilai_pegawai_lain' => $validated['role'] === 'pegawai' && !empty($validated['boleh_menilai_pegawai_lain']),
            // menilai_secara_manual cuma relevan buat akun yang bisa jadi
            // EVALUATOR (Penilai/Atasan Penilai akun lain) - pejabat & hrd.
            // Lihat User::menilaiSecaraManual().
            'menilai_secara_manual' => in_array($validated['role'], self::EVALUATOR_ROLES, true)
                && !empty($validated['menilai_secara_manual']),
            // Atasan (Penilai) sekarang boleh ditugaskan ke akun apapun,
            // termasuk HRD - lihat catatan di accounts.blade.php.
            'supervisor_id' => $validated['supervisor_id'] ?? null,
            // atasan_pejabat_id (Tanggapan Atasan) cuma relevan buat pegawai.
            'atasan_pejabat_id' => $validated['role'] === 'pegawai'
                ? ($validated['atasan_pejabat_id'] ?? null)
                : null,
            // atasan_penilai_pejabat_id (Tanggapan Atasan) relevan buat
            // pejabat & hrd - versi atasan_pejabat_id di atas, tapi untuk
            // akun selain pegawai.
            'atasan_penilai_pejabat_id' => in_array($validated['role'], self::ROLES_WITH_ATASAN_PENILAI_PEJABAT, true)
                ? ($validated['atasan_penilai_pejabat_id'] ?? null)
                : null,
            'email' => $validated['username'] . '@pegawai.local',
            'password' => bcrypt($validated['nik']),
            'role' => $validated['role'],
        ]);

        return back()->with('success', 'Akun berhasil ditambahkan.');
    }

    /**
     * Bersihkan & rapikan NIK dari sel Excel.
     *
     * - Kalau nilainya angka desimal (mis. Excel salah baca "16940821,6"
     *   sebagai angka pecahan lalu jadi "16940821.5999999999" karena
     *   pembulatan float), dibulatkan dulu ke bilangan bulat terdekat
     *   SEBELUM diubah jadi teks, supaya hasilnya "16940822" - bukan
     *   angka pecahan yang salah.
     * - Titik/koma pemisah ribuan atau sisa karakter non-digit dibuang.
     * - Dipotong maksimal 8 karakter.
     */
    private static function normalizeNik($raw): string
    {
        if (is_float($raw)) {
            // Excel bisa membaca nilai seperti "2222,4444" sebagai float
            // 2222.4444. Jangan dibulatkan, karena bagian desimal tersebut
            // merupakan digit NIK yang harus tetap dipertahankan.
            $asString = sprintf('%.10f', $raw);
            $asString = rtrim(rtrim($asString, '0'), '.');
        } elseif (is_int($raw)) {
            $asString = (string) $raw;
        } else {
            $asString = (string) $raw;
        }

        // Hapus pemisah/desimal dan karakter non-digit.
        // Contoh: 2222.4444 -> 22224444.
        $digitsOnly = preg_replace('/[^0-9]/', '', trim($asString));

        return substr($digitsOnly, 0, 8);
    }

    /**
     * Parsing kolom "Tanggal Masuk" dari Excel/CSV. Mendukung 3 bentuk
     * nilai mentah:
     * 1. Cell Date asli Excel (serial number, mis. 46000) - paling akurat,
     *    tidak mungkin salah tafsir urutan hari/bulan.
     * 2. Teks "dd/mm/yyyy" (mis. "19/06/2026").
     * 3. Teks "dd-mm-yyyy" (mis. "19-06-2026").
     *
     * Mengembalikan null kalau nilainya kosong atau tidak bisa
     * dikenali sama sekali di salah satu dari 3 bentuk di atas (masuk ke
     * $warnings di pemanggil, bukan membatalkan seluruh baris import).
     */
    private static function parseTanggalMasuk($raw): ?string
    {
        if (is_numeric($raw) && ! is_string($raw)) {
            // Serial number Excel (cell Date asli).
            try {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $raw)
                    ->format('Y-m-d');
            } catch (\Throwable $e) {
                return null;
            }
        }

        $value = trim((string) $raw);

        if ($value === '') {
            return null;
        }

        // Kadang PhpSpreadsheet tetap mengembalikan cell Date sebagai
        // string angka murni (mis. "46000") kalau formatnya campur -
        // coba tafsirkan sebagai serial number dulu kalau seluruhnya digit.
        if (ctype_digit($value)) {
            try {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $value)
                    ->format('Y-m-d');
            } catch (\Throwable $e) {
                // Lanjut coba format teks tanggal di bawah.
            }
        }

        foreach (['d/m/Y', 'd-m-Y'] as $format) {
            $date = \DateTime::createFromFormat($format, $value);

            // createFromFormat() bisa "toleran" ke input yang sebenarnya
            // salah (mis. "31-04-2026" dianggap 1 Mei) - getLastErrors()
            // dicek supaya tanggal yang jelas tidak valid tidak diam-diam
            // digeser ke tanggal lain.
            if ($date instanceof \DateTime) {
                $errors = \DateTime::getLastErrors();

                if (! $errors || ($errors['warning_count'] === 0 && $errors['error_count'] === 0)) {
                    return $date->format('Y-m-d');
                }
            }
        }

        return null;
    }

    private const IMPORT_COLUMN_MAP = [
        'nik' => 'nik',
        'nama lengkap' => 'name',
        'jabatan' => 'jabatan',
        'departemen' => 'departemen',
        'unit' => 'unit_kerja',
        // Kolom Vendor & Status ada di sheet "Office" pada file Excel
        // HRD, tapi TIDAK ADA di sheet "SPG" - lihat default di bawah
        // (di dalam loop baris) buat sheet yang tidak punya kolom ini.
        'vendor' => 'vendor',
        'status' => 'status',
        'penilai' => 'penilai',
        // Kolom "Atasan Penilai": diisi NAMA orang yang ditugaskan sebagai
        // Atasan Penilai (Tanggapan Atasan) untuk baris ini. Satu kolom
        // ini dipakai untuk SEMUA role (Pegawai, Pejabat, HRD) - saat
        // diproses, otomatis dirutekan ke kolom database yang sesuai:
        // pegawai -> users.atasan_pejabat_id, pejabat/hrd ->
        // users.atasan_penilai_pejabat_id (lihat penanganannya di bawah,
        // variabel $atasanPenilaiName).
        'atasan penilai' => 'atasan_pejabat',
        // Alias header lama, tetap didukung supaya file Excel lama yang
        // masih memisahkan kolom "Atasan Penilai Pejabat" untuk baris
        // pejabat tidak perlu diubah. Kalau kolom "Atasan Penilai" di
        // atas sudah diisi, isi kolom ini diabaikan (lihat $atasanPenilaiName).
        'atasan penilai pejabat' => 'atasan_penilai_pejabat',
        'indikator' => 'indikator',
        // Rekap kehadiran (izin/sakit/alpa/terlambat) - HRD sudah
        // menjumlahkan di luar sistem, bukan dihitung otomatis dari
        // beberapa baris. Kalau sel kosong dianggap 0 (sama seperti
        // menit_terlambat). Kalau kolom ini memang tidak ada di sheet
        // (mis. sheet "SPG" belum punya kolom ini), nilai lama di
        // database TIDAK diubah saat update - lihat $jumlahIzin dkk di
        // bawah. Mengisi kolom-kolom ini (atau "Menit Terlambat") lewat
        // import JUGA otomatis menandai kehadiran_diisi_at, supaya HRD
        // tidak perlu lagi isi "Kehadiran" manual satu-satu per akun
        // setelah import (lihat kehadiranDiisiViaImport di bawah).
        'izin' => 'jumlah_izin',
        'sakit' => 'jumlah_sakit',
        'alpa' => 'jumlah_alpa',
        'terlambat' => 'jumlah_terlambat',
        // Alias kalau header Excel-nya pakai awalan "Jumlah ...".
        'jumlah izin' => 'jumlah_izin',
        'jumlah sakit' => 'jumlah_sakit',
        'jumlah alpa' => 'jumlah_alpa',
        'jumlah terlambat' => 'jumlah_terlambat',
        // Total menit keterlambatan (rekap, sudah dijumlahkan HRD di luar
        // sistem - bukan dihitung otomatis dari beberapa baris). Kalau sel
        // kosong dianggap 0, sama seperti jumlah_izin dkk. Kalau kolom ini
        // memang tidak ada di sheet (mis. sheet "SPG"), nilai lama di
        // database TIDAK diubah saat update.
        'menit terlambat' => 'menit_terlambat',
        // Header aktual yang dipakai di file Excel HRD saat ini.
        'menit keterlambatan (total)' => 'menit_terlambat',
        // Tanggal mulai kerja - dipakai untuk menghitung masa kerja
        // otomatis (lihat User::getMasaKerjaAttribute()). Diterima dalam
        // 3 bentuk: cell Date asli Excel (serial number), teks dd/mm/yyyy,
        // atau teks dd-mm-yyyy (lihat parseTanggalMasuk() di bawah).
        'tanggal masuk' => 'tanggal_masuk',
    ];

    // Dipakai kalau sheet yang diimport tidak punya kolom Vendor/Status
    // sama sekali (mis. sheet "SPG") - semua barisnya otomatis diisi
    // nilai ini.
    private const DEFAULT_VENDOR_WHEN_MISSING = 'OS ABM';
    private const DEFAULT_STATUS_WHEN_MISSING = 'PHL';

    private const INDIKATOR_ROLE_ALIASES = [
        'pegawai' => 'pegawai',
        'karyawan' => 'pegawai',
        'pejabat' => 'pejabat',
        // Role "atasan pejabat" sudah digabung ke "pejabat". Alias di
        // bawah tetap dipertahankan supaya file Excel/CSV lama yang
        // masih memakai istilah ini tetap bisa diimport dengan benar.
        'atasan pejabat' => 'pejabat',
        'atasan_pejabat' => 'pejabat',
        'atasanpejabat' => 'pejabat',
        'hrd' => 'hrd',
        'admin' => 'hrd',
    ];

    /**
     * Import akun dari Excel/CSV.
     *
     * File upload dipindahkan terlebih dahulu ke storage/app/imports.
     * Ini menghindari getRealPath() yang pada kondisi tertentu dapat
     * menghasilkan path kosong setelah request upload diproses.
     */
    public function importAccounts(Request $request)
    {
        // Import bisa memproses ratusan baris (tiap baris baru butuh hashing
        // bcrypt + beberapa query DB), jadi butuh waktu eksekusi lebih
        // panjang dari limit PHP default. Dinaikkan cuma untuk request ini
        // saja (tidak mengubah limit global server). Kalau shared hosting
        // membatasi set_time_limit lewat safe mode/disable_functions, baris
        // ini otomatis diabaikan tanpa error - naikkan max_execution_time
        // di php.ini sebagai cadangan.
        if (function_exists('set_time_limit')) {
            @set_time_limit(900);
        }

        $request->validate([
            'import_file' => 'required|file|max:10240',
        ]);

        $file = $request->file('import_file');

        // Validasi upload terlebih dahulu.
        if (!$file) {
            return back()->with(
                'error',
                'File tidak ditemukan. Pastikan file Excel/CSV sudah dipilih.'
            );
        }

        if (!$file->isValid()) {
            return back()->with(
                'error',
                'Upload file gagal. Kode error upload: ' . $file->getError()
            );
        }

        $extension = strtolower(
            $file->getClientOriginalExtension()
        );

        if (!in_array($extension, ['xlsx', 'xls', 'csv'], true)) {
            return back()->with(
                'error',
                'Format file tidak didukung. Gunakan .xlsx, .xls, atau .csv.'
            );
        }

        /*
         * Jangan menggunakan Storage::disk('local')->path() untuk import.
         * File upload langsung disalin dari temporary PHP ke storage/app/imports
         * lalu PhpSpreadsheet diberi absolute path yang sudah diverifikasi.
         */
        $importDirectory = storage_path('app/imports');

        if (!is_dir($importDirectory)) {
            if (!mkdir($importDirectory, 0777, true) && !is_dir($importDirectory)) {
                return back()->with(
                    'error',
                    'Folder import tidak dapat dibuat: ' . $importDirectory
                );
            }
        }

        $temporaryPath = $file->getPathname();

        if (empty($temporaryPath)) {
            return back()->with(
                'error',
                'Path temporary upload kosong. PHP tidak memberikan lokasi file upload.'
            );
        }

        if (!is_file($temporaryPath) || !is_readable($temporaryPath)) {
            return back()->with(
                'error',
                'File temporary upload tidak dapat dibaca: ' . $temporaryPath
            );
        }

        $filename = 'import_' . Str::uuid()->toString() . '.' . $extension;
        $absolutePath = $importDirectory . DIRECTORY_SEPARATOR . $filename;

        try {
            if (!copy($temporaryPath, $absolutePath)) {
                return back()->with(
                    'error',
                    'File upload gagal disalin ke storage Laravel.'
                );
            }

            if (
                empty($absolutePath) ||
                !is_file($absolutePath) ||
                !is_readable($absolutePath)
            ) {
                return back()->with(
                    'error',
                    'File hasil upload tidak dapat dibaca: ' . $absolutePath
                );
            }

            // Pilih reader berdasarkan ekstensi supaya PhpSpreadsheet
            // tidak perlu menebak format file.
            switch ($extension) {
                case 'xlsx':
                    $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
                    break;

                case 'xls':
                    $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xls');
                    break;

                case 'csv':
                    $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Csv');
                                                                    break;

                default:
                    throw new \RuntimeException(
                        'Format file tidak didukung: ' . $extension
                    );
            }

            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($absolutePath);

            // =====================================================
            // BACA SEMUA SHEET, DIGABUNG JADI SATU IMPORT
            // =====================================================
            //
            // Sebelumnya cuma sheet yang aktif yang dibaca
            // (getActiveSheet()). Sekarang setiap sheet di file yang sama
            // diproses satu-satu dengan header masing-masing (karena tiap
            // sheet bisa saja beda urutan kolom), lalu hasilnya digabung
            // jadi satu laporan import.

            $created = [];
            $updated = [];
            $skipped = [];
            $warnings = [];
            $penilaiQueue = [];
            $atasanPejabatQueue = [];
            $atasanPenilaiPejabatQueue = [];
            $anySheetProcessed = false;
            $processedSheets = [];

            foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
                $sheetName = $sheet->getTitle();
                // formatData = false: ambil nilai MENTAH tiap sel (bukan
                // teks tampilan yang sudah diformat Excel), supaya kolom
                // NIK bisa dibaca sebagai angka asli lalu dibulatkan
                // dengan benar (lihat normalizeNik()), bukan ikut format
                // tampilan Excel yang bisa memunculkan titik ribuan atau
                // sisa desimal yang salah.
                $rows = $sheet->toArray(null, true, false, false);

                if (count($rows) < 2) {
                    // Sheet kosong (mis. sheet catatan/instruksi) - lewati
                    // diam-diam, bukan error, supaya sheet lain tetap diproses.
                    continue;
                }

                // =================================================
                // HEADER (per sheet, karena urutan kolom bisa beda)
                // =================================================

                $headerRow = array_map(function ($cell) {
                    // Hapus BOM yang kadang muncul pada CSV.
                    $header = preg_replace('/^\xEF\xBB\xBF/', '', (string) $cell);

                    return strtolower(
                        trim(
                            preg_replace('/\s+/', ' ', $header)
                        )
                    );
                }, $rows[0]);

                $columnIndex = [];

                foreach ($headerRow as $index => $header) {
                    if (isset(self::IMPORT_COLUMN_MAP[$header])) {
                        $columnIndex[self::IMPORT_COLUMN_MAP[$header]] = $index;
                    }
                }

                $missingRequired = [];

                foreach (['nik', 'name', 'indikator'] as $required) {
                    if (!isset($columnIndex[$required])) {
                        $missingRequired[] = $required;
                    }
                }

                if (!empty($missingRequired)) {
                    // Kolom wajib tidak ada di sheet ini - lewati sheet
                    // ini saja (bukan seluruh file), supaya sheet lain yang
                    // valid tetap ke-import.
                    $warnings[] =
                        "Sheet \"{$sheetName}\": dilewati, kolom wajib (" .
                        implode(', ', $missingRequired) .
                        ') tidak ditemukan di baris header.';
                    continue;
                }

                $anySheetProcessed = true;
                $processedSheets[] = $sheetName;

                // Sheet "SPG" ditandai dari NAMA sheet-nya (bukan dari
                // ada/tidaknya kolom Vendor/Status), supaya baris di
                // dalamnya otomatis diberi is_spg = true baik saat
                // membuat akun baru maupun saat meng-update akun lama.
                $isSpgSheet = (bool) preg_match('/spg/i', $sheetName);

                // =================================================
                // IMPORT BARIS DI SHEET INI
                // =================================================

                for ($rowNum = 1; $rowNum < count($rows); $rowNum++) {
                    $row = $rows[$rowNum];
                    $excelRowLabel = "Sheet \"{$sheetName}\", Baris " . ($rowNum + 1);

                    $cell = function (string $field) use ($columnIndex, $row) {
                        if (!isset($columnIndex[$field])) {
                            return null;
                        }

                    return trim(
                        (string) ($row[$columnIndex[$field]] ?? '')
                    );
                };

                // NIK diambil dari nilai MENTAH (belum di-cast ke string),
                // supaya kalau Excel menyimpannya sebagai angka desimal
                // (mis. orang salah ketik pakai koma sebagai pemisah
                // desimal), kita bisa membulatkannya dengan benar sebelum
                // artefak pembulatan float (mis. "...5999999999") ikut
                // tercetak ke string.
                $nikRaw = isset($columnIndex['nik'])
                    ? ($row[$columnIndex['nik']] ?? '')
                    : '';
                $nik = self::normalizeNik($nikRaw);
                $name = $cell('name');
                $jabatan = $cell('jabatan') ?: null;
                $departemen = $cell('departemen') ?: null;
                $unitKerja = $cell('unit_kerja') ?: null;

                // Sheet "SPG" tidak punya kolom Vendor/Status - kalau
                // kolomnya memang tidak ada di header sheet ini, semua
                // barisnya otomatis dianggap Vendor "OS ABM" & Status
                // "PHL". Kalau kolomnya ADA (mis. sheet "Office") tapi
                // selnya kosong, dibiarkan kosong (bukan default).
                $vendor = isset($columnIndex['vendor'])
                    ? ($cell('vendor') ?: null)
                    : self::DEFAULT_VENDOR_WHEN_MISSING;
                $status = isset($columnIndex['status'])
                    ? ($cell('status') ?: null)
                    : self::DEFAULT_STATUS_WHEN_MISSING;

                $penilaiName = $cell('penilai') ?: null;
                // Satu kolom "Atasan Penilai" dipakai untuk SEMUA role
                // (Pegawai, Pejabat, HRD) - dirutekan ke kolom database
                // yang berbeda tergantung role baris ini (lihat di bawah:
                // pegawai -> atasan_pejabat_id, pejabat/hrd ->
                // atasan_penilai_pejabat_id). Header lama "Atasan Penilai
                // Pejabat" tetap didukung sebagai alias/fallback supaya
                // file Excel lama tidak perlu diubah.
                $atasanPenilaiName = $cell('atasan_pejabat')
                    ?: $cell('atasan_penilai_pejabat')
                    ?: null;
                $indikatorRaw = $cell('indikator');

                // Menit terlambat: kalau kolomnya ADA di sheet ini, sel
                // kosong dianggap 0 (rekap penuh tiap import). Kalau
                // kolomnya TIDAK ADA sama sekali di sheet ini, nilainya
                // dibiarkan null supaya tidak menimpa data lama saat update
                // (lihat pemakaiannya di bawah).
                $menitTerlambatCell = isset($columnIndex['menit_terlambat'])
                    ? $cell('menit_terlambat')
                    : null;
                $menitTerlambat = $menitTerlambatCell !== null
                    ? (int) ($menitTerlambatCell ?: 0)
                    : null;
                $menitTerlambatFilled = $menitTerlambatCell !== null && $menitTerlambatCell !== '';

                // Rekap izin/sakit/alpa/terlambat: pola sama seperti
                // $menitTerlambat di atas - null kalau kolomnya memang
                // tidak ada di sheet ini (tidak menimpa data lama), 0
                // kalau kolomnya ada tapi selnya kosong. *Cell mentahnya
                // (sebelum di-default-kan ke 0) tetap disimpan terpisah
                // ($xxxCell) supaya bisa dibedakan "selnya kosong" vs
                // "selnya diisi 0" - lihat $kehadiranDiisiViaImport di
                // bawah, yang HANYA menganggap baris ini "sudah diisi"
                // kalau selnya benar-benar ada isinya, bukan cuma karena
                // kolomnya ada di header sheet.
                $izinCell = isset($columnIndex['jumlah_izin']) ? $cell('jumlah_izin') : null;
                $jumlahIzin = $izinCell !== null ? (int) ($izinCell ?: 0) : null;
                $izinFilled = $izinCell !== null && $izinCell !== '';

                $sakitCell = isset($columnIndex['jumlah_sakit']) ? $cell('jumlah_sakit') : null;
                $jumlahSakit = $sakitCell !== null ? (int) ($sakitCell ?: 0) : null;
                $sakitFilled = $sakitCell !== null && $sakitCell !== '';

                $alpaCell = isset($columnIndex['jumlah_alpa']) ? $cell('jumlah_alpa') : null;
                $jumlahAlpa = $alpaCell !== null ? (int) ($alpaCell ?: 0) : null;
                $alpaFilled = $alpaCell !== null && $alpaCell !== '';

                $terlambatCell = isset($columnIndex['jumlah_terlambat']) ? $cell('jumlah_terlambat') : null;
                $jumlahTerlambat = $terlambatCell !== null ? (int) ($terlambatCell ?: 0) : null;
                $terlambatFilled = $terlambatCell !== null && $terlambatCell !== '';

                // Kalau SALAH SATU kolom kehadiran (izin/sakit/alpa/
                // terlambat/menit terlambat) BENAR-BENAR TERISI (bukan
                // cuma ada kolomnya) di BARIS INI, baris ini dianggap
                // "kehadirannya sudah diisi lewat import" - ditandai sama
                // seperti kalau HRD mengisi manual lewat halaman "Lihat
                // Detail" (lihat updateAttendance()) - supaya badge
                // "Kehadiran" & tombol "Isi Kehadiran" di halaman Lihat
                // Pegawai/Pejabat otomatis berubah jadi "sudah diisi"
                // tanpa HRD perlu klik satu-satu lagi. Baris yang semua
                // sel kehadirannya kosong (walau kolomnya ada di header)
                // TIDAK dianggap sudah diisi - supaya HRD tidak salah
                // kira orang itu "0 sakit / 0 izin / 0 alpa" padahal
                // datanya memang belum sempat diisi di Excel.
                $kehadiranDiisiViaImport = $izinFilled
                    || $sakitFilled
                    || $alpaFilled
                    || $terlambatFilled
                    || $menitTerlambatFilled;

                // Tanggal masuk: diambil dari nilai MENTAH (bukan lewat
                // $cell(), yang sudah men-trim jadi string) supaya serial
                // number Excel bisa dibedakan dari teks. Kosong/tidak
                // dikenali -> null (tidak menimpa data lama saat update,
                // lihat parseTanggalMasuk()).
                $tanggalMasuk = null;
                if (isset($columnIndex['tanggal_masuk'])) {
                    $tanggalMasukRaw = $row[$columnIndex['tanggal_masuk']] ?? '';
                    $tanggalMasuk = self::parseTanggalMasuk($tanggalMasukRaw);

                    if ($tanggalMasuk === null && trim((string) $tanggalMasukRaw) !== '') {
                        $warnings[] =
                            "{$excelRowLabel}: nilai Tanggal Masuk \"{$tanggalMasukRaw}\" " .
                            "tidak dikenali formatnya (gunakan dd/mm/yyyy atau dd-mm-yyyy), diabaikan.";
                    }
                }

                if ($nik === '' && $name === '' && $indikatorRaw === '') {
                    continue;
                }

                if ($nik === '' || $name === '') {
                    $skipped[] =
                        "{$excelRowLabel}: NIK atau Nama Lengkap kosong.";
                    continue;
                }

                // =================================================
                // ROLE / INDIKATOR
                // =================================================

                $roleKey = strtolower(
                    trim(
                        preg_replace(
                            '/\s+/',
                            ' ',
                            str_replace(
                                ['-', '_'],
                                ' ',
                                $indikatorRaw
                            )
                        )
                    )
                );

                $role = self::INDIKATOR_ROLE_ALIASES[$roleKey] ?? null;

                if (!$role) {
                    $skipped[] =
                        "{$excelRowLabel}: nilai Indikator \"{$indikatorRaw}\" " .
                        "tidak dikenali. Gunakan Pegawai, Pejabat, atau HRD.";
                    continue;
                }

                // =================================================
                // CEK NIK - KALAU SUDAH ADA, UPDATE (BUKAN DILEWATI)
                // =================================================
                //
                // Sebelumnya baris dengan NIK yang sudah terdaftar
                // otomatis dilewati. Sekarang datanya di-UPDATE dengan
                // nilai terbaru dari Excel (nama, jabatan, departemen,
                // unit kerja, vendor, status, role, is_spg), supaya file
                // Excel yang sama bisa dipakai berulang kali untuk
                // memperbarui data (termasuk data SPG) tanpa perlu
                // hapus akun lama dulu. Username, password, dan email
                // akun lama TIDAK diubah supaya login pegawai tidak
                // terganggu.

                $existingUser = User::where('nik', $nik)->first();

                if ($existingUser) {
                    try {
                        $updatePayload = [
                            'name' => $name,
                            'unit_kerja' => $unitKerja,
                            'jabatan' => $jabatan,
                            'departemen' => $departemen,
                            'vendor' => $vendor,
                            'status' => $status,
                            'role' => $role,
                            'is_spg' => $isSpgSheet
                                ? true
                                : $existingUser->is_spg,
                        ];

                        // Cuma disertakan kalau kolomnya memang ada di
                        // sheet ini - kalau tidak ada, nilai lama di
                        // database dibiarkan (lihat komentar di atas).
                        if ($menitTerlambat !== null) {
                            $updatePayload['menit_terlambat'] = $menitTerlambat;
                        }

                        if ($jumlahIzin !== null) {
                            $updatePayload['jumlah_izin'] = $jumlahIzin;
                        }

                        if ($jumlahSakit !== null) {
                            $updatePayload['jumlah_sakit'] = $jumlahSakit;
                        }

                        if ($jumlahAlpa !== null) {
                            $updatePayload['jumlah_alpa'] = $jumlahAlpa;
                        }

                        if ($jumlahTerlambat !== null) {
                            $updatePayload['jumlah_terlambat'] = $jumlahTerlambat;
                        }

                        if ($kehadiranDiisiViaImport) {
                            $updatePayload['kehadiran_diisi_at'] = now();
                        }

                        if ($tanggalMasuk !== null) {
                            $updatePayload['tanggal_masuk'] = $tanggalMasuk;
                        }

                        $existingUser->update($updatePayload);

                        $updated[] =
                            "{$excelRowLabel}: {$name} (NIK {$nik}) " .
                            "berhasil diupdate.";

                        if (
                            $penilaiName &&
                            in_array(
                                $role,
                                self::ROLES_WITH_EVALUATOR,
                                true
                            )
                        ) {
                            $penilaiQueue[$existingUser->id] = $penilaiName;
                        } elseif ($penilaiName) {
                            $warnings[] =
                                "{$excelRowLabel}: Penilai \"{$penilaiName}\" " .
                                "diisi tetapi role \"{$role}\" tidak memiliki " .
                                "Atasan Penilai, sehingga diabaikan.";
                        }

                        // Satu kolom "Atasan Penilai" dirutekan ke kolom
                        // database berbeda sesuai role baris ini: pegawai
                        // -> atasan_pejabat_id, pejabat/hrd ->
                        // atasan_penilai_pejabat_id.
                        if ($atasanPenilaiName && $role === 'pegawai') {
                            $atasanPejabatQueue[$existingUser->id] = $atasanPenilaiName;
                        } elseif (
                            $atasanPenilaiName
                            && in_array($role, self::ROLES_WITH_ATASAN_PENILAI_PEJABAT, true)
                        ) {
                            $atasanPenilaiPejabatQueue[$existingUser->id] = $atasanPenilaiName;
                        }
                    } catch (\Throwable $e) {
                        $detail = trim($e->getMessage());

                        if ($e instanceof \Illuminate\Database\QueryException) {
                            $detail = 'Database: ' . $e->getMessage();
                        }

                        $skipped[] =
                            "{$excelRowLabel}: GAGAL diupdate. {$detail}";
                    }

                    continue;
                }

                // =================================================
                // USERNAME OTOMATIS
                // =================================================

                $baseUsername = Str::slug($name, '_');

                if ($baseUsername === '') {
                    $baseUsername = 'user_' . $nik;
                }

                $username = $baseUsername;
                $suffix = 1;

                while (User::where('username', $username)->exists()) {
                    $username =
                        $baseUsername . '_' . (++$suffix);
                }

                // =================================================
                // SIMPAN USER
                // =================================================

                try {
                    $user = User::create([
                        'name' => $name,
                        'username' => $username,
                        'nik' => $nik,
                        'unit_kerja' => $unitKerja,
                        'jabatan' => $jabatan,
                        'departemen' => $departemen,
                        'vendor' => $vendor,
                        'status' => $status,
                        'role' => $role,
                        'is_spg' => $isSpgSheet,
                        'email' => $username . '@pegawai.local',
                        'password' => bcrypt($nik),
                        // Akun baru: kolom belum ada isinya sama sekali di
                        // database, jadi kalau sheet tidak punya kolom ini
                        // dianggap 0/kosong (bukan dibiarkan mengambang).
                        'menit_terlambat' => $menitTerlambat ?? 0,
                        'jumlah_izin' => $jumlahIzin ?? 0,
                        'jumlah_sakit' => $jumlahSakit ?? 0,
                        'jumlah_alpa' => $jumlahAlpa ?? 0,
                        'jumlah_terlambat' => $jumlahTerlambat ?? 0,
                        'kehadiran_diisi_at' => $kehadiranDiisiViaImport ? now() : null,
                        'tanggal_masuk' => $tanggalMasuk,
                    ]);

                    $created[] =
                        "{$excelRowLabel}: {$name} ({$username}) berhasil " .
                        "dibuat sebagai " .
                        ucfirst(str_replace('_', ' ', $role)) . '.';

                    if (
                        $penilaiName &&
                        in_array(
                            $role,
                            self::ROLES_WITH_EVALUATOR,
                            true
                        )
                    ) {
                        $penilaiQueue[$user->id] = $penilaiName;
                    } elseif ($penilaiName) {
                        $warnings[] =
                            "{$excelRowLabel}: Penilai \"{$penilaiName}\" " .
                            "diisi tetapi role \"{$role}\" tidak memiliki " .
                            "Atasan Penilai, sehingga diabaikan.";
                    }

                    // Kolom "Atasan Penilai" dirutekan ke kolom database
                    // berbeda sesuai role baris ini: pegawai ->
                    // atasan_pejabat_id, pejabat/hrd ->
                    // atasan_penilai_pejabat_id.
                    if ($atasanPenilaiName && $role === 'pegawai') {
                        $atasanPejabatQueue[$user->id] = $atasanPenilaiName;
                    } elseif (
                        $atasanPenilaiName
                        && in_array($role, self::ROLES_WITH_ATASAN_PENILAI_PEJABAT, true)
                    ) {
                        $atasanPenilaiPejabatQueue[$user->id] = $atasanPenilaiName;
                    }
                } catch (\Throwable $e) {
                    /*
                     * Jangan lagi menyembunyikan error sebenarnya.
                     * Ini akan menunjukkan apakah masalahnya email, NIK,
                     * username, kolom database, foreign key, dsb.
                     */
                    $detail = trim($e->getMessage());

                    if ($e instanceof \Illuminate\Database\QueryException) {
                        $detail =
                            'Database: ' . $e->getMessage();
                    }

                    $skipped[] =
                        "{$excelRowLabel}: GAGAL disimpan. {$detail}";

                    continue;
                }
                }
            }

            if (!$anySheetProcessed && empty($created)) {
                return back()->with(
                    'error',
                    'Tidak ada sheet yang punya kolom wajib (NIK, Nama Lengkap, Indikator). ' .
                    'Cek nama header di setiap sheet pada file tsb.'
                );
            }

            // =====================================================
            // HUBUNGKAN PENILAI
            // =====================================================

            foreach ($penilaiQueue as $userId => $penilaiName) {
                $penilai = User::whereRaw(
                    'LOWER(TRIM(name)) = ?',
                    [strtolower(trim($penilaiName))]
                )
                    ->whereIn('role', self::EVALUATOR_ROLES)
                    ->first();

                if (!$penilai) {
                    $warnings[] =
                        "\"{$penilaiName}\" (Penilai) tidak ditemukan " .
                        "sebagai akun Pejabat/HRD. " .
                        "Silakan tugaskan manual.";
                    continue;
                }

                if ($penilai->id === $userId) {
                    $warnings[] =
                        "\"{$penilaiName}\" tidak bisa ditugaskan " .
                        "sebagai penilai untuk dirinya sendiri.";
                    continue;
                }

                User::where('id', $userId)->update([
                    'supervisor_id' => $penilai->id,
                ]);
            }

            // =====================================================
            // HUBUNGKAN ATASAN PEJABAT
            // =====================================================

            foreach ($atasanPejabatQueue as $userId => $atasanPejabatName) {
                $atasanPejabat = User::whereRaw(
                    'LOWER(TRIM(name)) = ?',
                    [strtolower(trim($atasanPejabatName))]
                )
                    ->whereIn('role', self::EVALUATOR_ROLES)
                    ->first();

                if (!$atasanPejabat) {
                    $warnings[] =
                        "\"{$atasanPejabatName}\" (Atasan Pejabat) tidak ditemukan " .
                        "sebagai akun Pejabat/HRD. " .
                        "Silakan tugaskan manual.";
                    continue;
                }

                if ($atasanPejabat->id === $userId) {
                    $warnings[] =
                        "\"{$atasanPejabatName}\" tidak bisa ditugaskan " .
                        "sebagai Atasan Pejabat untuk dirinya sendiri.";
                    continue;
                }

                User::where('id', $userId)->update([
                    'atasan_pejabat_id' => $atasanPejabat->id,
                ]);
            }

            // =====================================================
            // HUBUNGKAN ATASAN PENILAI PEJABAT
            // =====================================================
            // Versi HUBUNGKAN ATASAN PEJABAT di atas, tapi untuk role
            // pejabat (users.atasan_penilai_pejabat_id) - supaya kolom
            // "Atasan Penilai Pejabat" di Excel bisa otomatis mengisi
            // Atasan Penilai pejabat, sama seperti kolom "Atasan Penilai"
            // sudah bisa otomatis mengisi Atasan Pejabat pegawai di atas.

            foreach ($atasanPenilaiPejabatQueue as $userId => $atasanPenilaiPejabatName) {
                $atasanPenilaiPejabat = User::whereRaw(
                    'LOWER(TRIM(name)) = ?',
                    [strtolower(trim($atasanPenilaiPejabatName))]
                )
                    ->whereIn('role', self::EVALUATOR_ROLES)
                    ->first();

                if (!$atasanPenilaiPejabat) {
                    $warnings[] =
                        "\"{$atasanPenilaiPejabatName}\" (Atasan Penilai Pejabat) tidak ditemukan " .
                        "sebagai akun Pejabat/HRD. " .
                        "Silakan tugaskan manual.";
                    continue;
                }

                if ($atasanPenilaiPejabat->id === $userId) {
                    $warnings[] =
                        "\"{$atasanPenilaiPejabatName}\" tidak bisa ditugaskan " .
                        "sebagai Atasan Penilai Pejabat untuk dirinya sendiri.";
                    continue;
                }

                User::where('id', $userId)->update([
                    'atasan_penilai_pejabat_id' => $atasanPenilaiPejabat->id,
                ]);
            }

            // =====================================================
            // KEMBALIKAN HASIL IMPORT
            // =====================================================

            return back()->with('import_report', [
                'created' => $created,
                'updated' => $updated,
                'skipped' => $skipped,
                'warnings' => $warnings,
                'processed_sheets' => $processedSheets,
            ]);

        } catch (\Throwable $e) {
            return back()->with(
                'error',
                'File Excel gagal dibaca. Detail: ' . $e->getMessage()
            );
        } finally {
            if (
                isset($absolutePath) &&
                !empty($absolutePath) &&
                file_exists($absolutePath)
            ) {
                @unlink($absolutePath);
            }
        }
    }

    public function updateAccount(Request $request, $id)
    {
        $account = User::findOrFail($id);
        $oldNik = $account->nik;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:100|alpha_dash|unique:users,username,' . $account->id,
            'nik' => 'required|digits_between:1,8|unique:users,nik,' . $account->id,
            'unit_kerja' => 'nullable|string|max:255',
            'jabatan' => 'nullable|string|max:255',
            'vendor' => 'nullable|string|in:' . implode(',', User::VENDORS),
            'status' => 'nullable|string|in:' . implode(',', User::EMPLOYMENT_STATUSES),
            'role' => 'required|in:pegawai,pejabat,hrd',
            'is_spg' => 'nullable|boolean',
            'boleh_menilai_pegawai_lain' => 'nullable|boolean',
            'menilai_secara_manual' => 'nullable|boolean',
            'supervisor_id' => [
                'nullable',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->whereIn('role', self::EVALUATOR_ROLES)
                        ->orWhere(function ($query) {
                            $query->where('role', 'pegawai')
                                ->where('boleh_menilai_pegawai_lain', true);
                        });
                }),
            ],
            'atasan_pejabat_id' => [
                'nullable',
                Rule::exists('users', 'id')->whereIn('role', self::EVALUATOR_ROLES),
            ],
            'atasan_penilai_pejabat_id' => [
                'nullable',
                Rule::exists('users', 'id')->whereIn('role', self::EVALUATOR_ROLES),
            ],
        ]);

        if (
            (int) ($validated['supervisor_id'] ?? 0) === $account->id
        ) {
            return back()
                ->withErrors([
                    'supervisor_id' => 'Akun tidak bisa ditugaskan sebagai atasan/penilainya sendiri.'
                ])
                ->withInput();
        }

        if (
            $validated['role'] === 'pegawai'
            && (int) ($validated['atasan_pejabat_id'] ?? 0) === $account->id
        ) {
            return back()
                ->withErrors([
                    'atasan_pejabat_id' => 'Akun tidak bisa ditugaskan sebagai Atasan Pejabat untuk dirinya sendiri.'
                ])
                ->withInput();
        }

        if (
            in_array($validated['role'], self::ROLES_WITH_ATASAN_PENILAI_PEJABAT, true)
            && (int) ($validated['atasan_penilai_pejabat_id'] ?? 0) === $account->id
        ) {
            return back()
                ->withErrors([
                    'atasan_penilai_pejabat_id' => 'Akun tidak bisa ditugaskan sebagai Atasan Penilai untuk dirinya sendiri.'
                ])
                ->withInput();
        }

        $accountBolehMenilaiPegawaiLain = $validated['role'] === 'pegawai' && !empty($validated['boleh_menilai_pegawai_lain']);

        $account->update([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'nik' => $validated['nik'],
            'unit_kerja' => $validated['unit_kerja'] ?? null,
            'jabatan' => $validated['jabatan'] ?? null,
            'vendor' => $validated['vendor'] ?? null,
            'status' => $validated['status'] ?? null,
            'is_spg' => $validated['role'] === 'pegawai' && !empty($validated['is_spg']),
            'boleh_menilai_pegawai_lain' => $accountBolehMenilaiPegawaiLain,
            // menilai_secara_manual cuma relevan buat akun yang bisa jadi
            // EVALUATOR (Penilai/Atasan Penilai akun lain) - pejabat & hrd.
            // Lihat User::menilaiSecaraManual().
            'menilai_secara_manual' => in_array($validated['role'], self::EVALUATOR_ROLES, true)
                && !empty($validated['menilai_secara_manual']),
            // Atasan (Penilai) sekarang boleh ditugaskan ke akun apapun,
            // termasuk HRD - lihat catatan di accounts.blade.php.
            'supervisor_id' => $validated['supervisor_id'] ?? null,
            // atasan_pejabat_id (Tanggapan Atasan) cuma relevan buat pegawai.
            'atasan_pejabat_id' => $validated['role'] === 'pegawai'
                ? ($validated['atasan_pejabat_id'] ?? null)
                : null,
            // atasan_penilai_pejabat_id (Tanggapan Atasan) relevan buat
            // pejabat & hrd.
            'atasan_penilai_pejabat_id' => in_array($validated['role'], self::ROLES_WITH_ATASAN_PENILAI_PEJABAT, true)
                ? ($validated['atasan_penilai_pejabat_id'] ?? null)
                : null,
            'role' => $validated['role'],
        ]);

        // Password akun dibuat otomatis dari NIK saat akun pertama kali
        // dibuat (lihat storeAccount / import Excel). Kalau NIK diubah
        // di sini, password (yang berupa hash dari NIK lama) harus ikut
        // disinkronkan, kalau tidak akun tidak akan bisa login pakai
        // NIK baru sebagai password.
        if ((string) $validated['nik'] !== (string) $oldNik) {
            $account->update([
                'password' => bcrypt($validated['nik']),
            ]);
        }

        // Kalau akun ini bukan (lagi) pejabat/hrd, DAN (kalau pegawai)
        // sudah tidak ditandai "Boleh Menilai Pegawai Lain", putuskan
        // relasi supervisor_id semua akun yang sebelumnya ditugaskan ke
        // akun ini - supaya tidak ada pegawai yang "menggantung" dinilai
        // oleh akun yang sudah tidak berhak menilai lagi. Kalau masih
        // ditandai boleh menilai, biarkan relasinya tetap ada.
        if (
            !in_array($validated['role'], self::EVALUATOR_ROLES, true)
            && !$accountBolehMenilaiPegawaiLain
        ) {
            User::where('supervisor_id', $account->id)
                ->update(['supervisor_id' => null]);

            User::where('atasan_pejabat_id', $account->id)
                ->update(['atasan_pejabat_id' => null]);

            User::where('atasan_penilai_pejabat_id', $account->id)
                ->update(['atasan_penilai_pejabat_id' => null]);
        }

        return back()->with('success', 'Akun berhasil diperbarui.');
    }

    public function destroyAccount($id)
    {
        $account = User::findOrFail($id);

        if ($account->id === Auth::id()) {
            return back()->with('error', 'Akun yang sedang digunakan tidak boleh dihapus.');
        }

        if (
            $account->role === 'hrd'
            && User::where('role', 'hrd')->count() <= 1
        ) {
            return back()->with(
                'error',
                'HRD terakhir tidak boleh dihapus. Tambahkan HRD lain terlebih dahulu.'
            );
        }

        $signaturePaths = collect()
            ->merge(
                Evaluation::where('employee_id', $account->id)
                    ->orWhere('official_id', $account->id)
                    ->pluck('signature')
            )
            ->merge(
                Feedback::where('employee_id', $account->id)
                    ->orWhere('reviewer_id', $account->id)
                    ->pluck('signature')
            )
            ->merge(
                SupervisorFeedback::where('employee_id', $account->id)
                    ->orWhere('supervisor_id', $account->id)
                    ->pluck('signature')
            )
            ->merge(
                OfficialEvaluation::where('official_id', $account->id)
                    ->orWhere('supervisor_id', $account->id)
                    ->pluck('signature')
            )
            ->filter()
            ->unique()
            ->values();

        $account->delete();

        if ($signaturePaths->isNotEmpty()) {
            Storage::disk('public')->delete($signaturePaths->all());
        }

        return back()->with('success', 'Akun berhasil dihapus.');
    }

    /**
     * Buka/tutup tampilan badge "Status" (mis. "Kontrak"/"PHL"/"Tetap")
     * untuk akun PEGAWAI/PEJABAT yang bersangkutan.
     *
     * Kalau ditutup, akun ini jadi tidak bisa melihat badge Status
     * SIAPAPUN selama dia login - baik statusnya sendiri, maupun status
     * pegawai/pejabat lain yang muncul di dashboard/evaluate/tanggapan
     * miliknya (lihat User::statusKontrakTerbuka(), dicek dari akun yang
     * sedang login di tiap view terkait).
     *
     * Cuma berlaku untuk akun ber-role pegawai/pejabat - status HRD
     * sendiri tidak ditampilkan di dashboard manapun jadi tidak relevan
     * untuk ditutup/dibuka. Toggle ini TIDAK mempengaruhi apa yang HRD
     * lihat di halaman "Semua Akun"/"Lihat Pegawai"/"Lihat Pejabat" -
     * HRD selalu bisa melihat kolom Status di sana.
     */
    public function toggleStatusKontrak($id)
    {
        $account = User::findOrFail($id);

        if (! in_array($account->role, self::ROLES_WITH_EVALUATOR, true)) {
            return back()->with(
                'error',
                'Buka/tutup Status hanya berlaku untuk akun Pegawai atau Pejabat.'
            );
        }

        $account->update([
            'status_kontrak_terbuka' => ! $account->statusKontrakTerbuka(),
        ]);

        return back()->with(
            'success',
            $account->statusKontrakTerbuka()
                ? "Status {$account->name} sekarang TERBUKA - badge Status akan tampil lagi untuknya."
                : "Status {$account->name} sekarang DITUTUP - {$account->name} tidak akan bisa melihat badge Status siapapun (baik miliknya sendiri maupun pegawai/pejabat lain) selama status ini ditutup."
        );
    }

    public function updateAttendance(Request $request, $id)
    {
        $employee = User::findOrFail($id);

        $validated = $request->validate([
            'jumlah_izin' => 'nullable|integer|min:0',
            'jumlah_sakit' => 'nullable|integer|min:0',
            'jumlah_alpa' => 'nullable|integer|min:0',
            'jumlah_terlambat' => 'nullable|integer|min:0',
        ]);

        // Status Kontrak sudah tidak ditampilkan/diisi lewat form ini,
        // jadi kolomnya tidak ikut disentuh supaya nilai yang tersimpan
        // sebelumnya (kalau ada) tidak ikut ter-hapus tiap kali form
        // kehadiran disimpan.
        $employee->update([
            'jumlah_izin' => $validated['jumlah_izin'] ?? 0,
            'jumlah_sakit' => $validated['jumlah_sakit'] ?? 0,
            'jumlah_alpa' => $validated['jumlah_alpa'] ?? 0,
            'jumlah_terlambat' => $validated['jumlah_terlambat'] ?? 0,
            // Tandai kehadiran sudah diisi HRD - jadi penilai boleh mulai
            // menilai pegawai ini (lihat User::siapDinilaiPenilai()).
            'kehadiran_diisi_at' => now(),
        ]);

        // Kirim notifikasi FCM ke supervisor/atasan jika pegawai/pejabat
        // ini kini siap dinilai. Route ini dipakai untuk kedua role
        // (kolom kehadiran_diisi_at sama), jadi dispatcher generik yang
        // memilih trigger sesuai role - lihat
        // NotificationTriggerService::triggerSiapDinilaiJikaPerlu().
        // Dilakukan SETELAH update berhasil - jika FCM gagal, data kehadiran
        // tetap tersimpan (notification bersifat tambahan, bukan blocking).
        app(NotificationTriggerService::class)
            ->triggerSiapDinilaiJikaPerlu($employee);

        return back()->with(
            'success',
            'Data kehadiran berhasil diperbarui.'
        );
    }

    public function show(Request $request, $id)
    {
        $employee = User::findOrFail($id);

        $tahun = $this->selectedTahun($request);
        $availableTahun = $this->availableTahunOptions();

        $feedbacks = Feedback::where('employee_id', $id)
            ->with('reviewer')
            ->get();

        // Alur penilaian pejabat sama seperti pegawai (kehadiran hrd ->
        // korelasi -> penilaian atasan -> tanggapan pejabat -> pdf hrd),
        // tapi sumber datanya beda tabel (OfficialEvaluation, bukan
        // Evaluation/SupervisorFeedback), jadi dipisah di sini supaya
        // tidak nyampur dengan logika pegawai di bawah.
        // Semua query penilaian di bawah dibatasi ke tahun yang dipilih
        // ($tahun, lihat selector tahun) supaya halaman detail bisa
        // menampilkan siklus penilaian tahun manapun - penilaian
        // tahun-tahun lain tetap tersimpan di database sebagai histori,
        // tidak ikut ketimpa/hilang.
        if ($employee->role === 'pejabat') {
            $officialEvaluation = OfficialEvaluation::where('official_id', $id)
                ->where('supervisor_id', $employee->supervisor_id)
                ->tahunAktif($tahun)
                ->with('supervisor')
                ->first();

            $officialSupervisorFeedback = \App\Models\OfficialSupervisorFeedback::where('official_id', $id)
                ->tahunAktif($tahun)
                ->with('supervisor')
                ->latest()
                ->first();

            return view(
                'admin.detail',
                compact('employee', 'feedbacks', 'officialEvaluation', 'officialSupervisorFeedback', 'tahun', 'availableTahun')
            );
        }

        // Penilaian dari "Penilai" langsung yang ditugaskan HRD
        // (users.supervisor_id). Ini yang ditampilkan sebagai "Penilaian
        // Pejabat" utama, supaya tidak tertukar kalau ada penilaian
        // tambahan dari pejabat lain (mis. Atasan Penilai dari Penilai
        // langsung pegawai ini, lihat OfficialController::canEvaluate()).
        $evaluation = Evaluation::where('employee_id', $id)
            ->where('official_id', $employee->supervisor_id)
            ->tahunAktif($tahun)
            ->with('official')
            ->first();

        // Tanggapan Atasan sekarang diambil dari penilaian tambahan yang
        // diberikan Atasan Penilai (mis. atasan dari Penilai langsung
        // pegawai ini) - BUKAN lagi dari form Tanggapan Atasan
        // (SupervisorFeedback) yang lama. Ambil yang paling baru saja.
        $atasanEvaluation = Evaluation::where('employee_id', $id)
            ->when(
                $employee->supervisor_id,
                fn ($query) => $query->where('official_id', '!=', $employee->supervisor_id)
            )
            ->tahunAktif($tahun)
            ->with('official')
            ->latest()
            ->first();

        // Jika tidak ada Evaluation dari atasan penilai, coba ambil SupervisorFeedback
        // (tanggapan sederhana dari atasan penilai via form biasa)
        $atasanFeedback = null;
        if (! $atasanEvaluation) {
            $atasanFeedback = SupervisorFeedback::where('employee_id', $id)
                ->tahunAktif($tahun)
                ->with('supervisor')
                ->latest()
                ->first();
        }

        return view(
            'admin.detail',
            compact('employee', 'feedbacks', 'evaluation', 'atasanEvaluation', 'atasanFeedback', 'tahun', 'availableTahun')
        );
    }

    public function signAsHrd(Request $request, $id)
    {
        $employee = User::findOrFail($id);
        $tahun = $this->selectedTahun($request);

        // Tanda tangan HRD disimpan pada penilaian pejabat/penilai
        // langsung, sumber yang sama dipakai saat generate PDF. Dibatasi
        // ke tahun yang sedang dipilih HRD ($tahun, dari ?tahun=... di
        // halaman detail - lihat show()) supaya HRD menandatangani baris
        // yang sama persis dengan yang sedang dia lihat, BUKAN selalu
        // tahun kalender berjalan. Kalau ini dibiarkan pakai tahunAktif()
        // tanpa argumen, tanda tangan bisa tersimpan ke baris yang salah
        // (tahun berjalan) sementara baris yang sedang ditampilkan (tahun
        // lain) tidak pernah ikut terkunci.
        $evaluation = Evaluation::where('employee_id', $id)
            ->where('official_id', $employee->supervisor_id)
            ->tahunAktif($tahun)
            ->first();

        if (! $evaluation) {
            return back()->with(
                'error',
                'Penilaian pejabat belum tersedia, tanda tangan HRD belum bisa disimpan.'
            );
        }

        // Kalau Penilai (users.supervisor_id) ATAU Atasan Pejabat pegawai
        // ini menilai secara manual (User::menilaiSecaraManual()), seluruh
        // alur persetujuan dianggap berjalan di luar sistem - jadi HRD
        // boleh tanda tangan KAPAN SAJA begitu baris Evaluation-nya ada,
        // tanpa menunggu tanggapan & tanda tangan pegawai lebih dulu.
        // Lihat User::penilaianUtamaManual() & tanggapanAtasanManual().
        $manualBypass = $employee->penilaianUtamaManual() || $employee->tanggapanAtasanManual();

        // HRD baru boleh tanda tangan setelah PEGAWAI sendiri sudah
        // menanggapi & menandatangani penilaiannya (Evaluation::employee_response
        // & employee_signature - lihat EmployeeController::respondEvaluation()).
        // Urutannya harus pegawai dulu baru HRD, bukan sebaliknya - kecuali
        // $manualBypass true (lihat catatan di atas).
        if (! $manualBypass && empty($evaluation->employee_signature)) {
            return back()->with(
                'error',
                'Tanda tangan HRD belum bisa disimpan. Pegawai belum memberikan tanggapan & tanda tangan atas penilaiannya sendiri.'
            );
        }

        // Tambahan: HRD baru boleh tanda tangan kalau checklist pertemuan
        // (PEGAWAI & PENILAI, untuk tahun yang sama - lihat
        // User::checklistPertemuanLengkap()) sudah lengkap. Syarat ini
        // sebelumnya hanya dicek sebelum cetak PDF (lihat pdf() di bawah),
        // sekarang dicek juga di sini supaya HRD tidak bisa tanda tangan
        // duluan sebelum checklist selesai.
        if (! $employee->checklistPertemuanLengkap($tahun)) {
            return back()->with(
                'error',
                'Tanda tangan HRD belum bisa disimpan. Checklist pertemuan (Pegawai & Penilai) belum lengkap untuk tahun ini.'
            );
        }

        // Sekali tanda tangan HRD tersimpan, tidak boleh ditimpa lagi lewat
        // request ini (form-nya sendiri sudah disembunyikan di view begitu
        // hrd_signature terisi - lihat admin.detail - tapi dicek juga di
        // sini supaya tidak bisa diakali dengan mengirim request langsung
        // ke route ini). Kalau HRD perlu mengulang, harus lewat mekanisme
        // lain (mis. hapus tanda tangan) - bukan menimpa langsung di sini.
        if ($evaluation->hrd_signature) {
            return back()->with(
                'error',
                'Penilaian ini sudah ditanda-tangani HRD dan tidak bisa ditanda-tangani ulang.'
            );
        }

        $validated = $request->validate([
            'hrd_signature' => 'required|string',
        ]);

        // Pastikan data yang dikirim benar-benar gambar base64 dari canvas
        if (! preg_match('/^data:image\/png;base64,/', $validated['hrd_signature'])) {
            return back()->withErrors(['hrd_signature' => 'Format tanda tangan tidak valid.']);
        }

        $imageContent = base64_decode(substr($validated['hrd_signature'], strpos($validated['hrd_signature'], ',') + 1));
        $signaturePath = 'signatures/hrd_' . $evaluation->id . '_' . time() . '.png';
        Storage::disk('public')->put($signaturePath, $imageContent);

        $evaluation->update([
            'hrd_id'         => Auth::id(),
            'hrd_signature'  => $signaturePath,
            'hrd_signed_at'  => now(),
        ]);

        return back()->with(
            'success',
            'Tanda tangan HRD berhasil disimpan.'
        );
    }

    /**
     * Simpan tanda tangan HRD untuk penilaian pejabat (OfficialEvaluation),
     * analog dengan signAsHrd() yang menyimpan tanda tangan HRD untuk
     * penilaian pegawai (Evaluation).
     */
    public function signAsHrdOfficial(Request $request, $id)
    {
        $employee = User::findOrFail($id);
        $tahun = $this->selectedTahun($request);

        // Sama seperti signAsHrd(): dibatasi ke tahun yang sedang dipilih
        // ($tahun), bukan selalu tahun kalender berjalan, supaya tanda
        // tangan tersimpan ke baris yang sama dengan yang sedang dilihat
        // HRD.
        $officialEvaluation = \App\Models\OfficialEvaluation::where('official_id', $id)
            ->where('supervisor_id', $employee->supervisor_id)
            ->tahunAktif($tahun)
            ->first();

        if (! $officialEvaluation) {
            return back()->with(
                'error',
                'Penilaian atasan pejabat belum tersedia, tanda tangan HRD belum bisa disimpan.'
            );
        }

        // Kalau Penilai (users.supervisor_id) pejabat ini menilai secara
        // manual (User::menilaiSecaraManual()), seluruh alur persetujuan
        // dianggap berjalan di luar sistem - jadi HRD boleh tanda tangan
        // KAPAN SAJA begitu baris OfficialEvaluation-nya ada, tanpa
        // menunggu tanggapan & tanda tangan pejabat lebih dulu. Lihat
        // User::tanggapanPenilaiPejabatManual().
        $manualBypass = $employee->tanggapanPenilaiPejabatManual();

        // HRD baru boleh tanda tangan setelah PEJABAT yang dinilai sudah
        // menanggapi & menandatangani penilaiannya sendiri
        // (OfficialEvaluation::employee_response & employee_signature -
        // lihat OfficialController::respondEvaluation()). Sama pola-nya
        // dengan signAsHrd() di atas - kecuali $manualBypass true.
        if (! $manualBypass && empty($officialEvaluation->employee_signature)) {
            return back()->with(
                'error',
                'Tanda tangan HRD belum bisa disimpan. Pejabat belum memberikan tanggapan & tanda tangan atas penilaiannya sendiri.'
            );
        }

        // Tambahan: sama seperti signAsHrd(), HRD baru boleh tanda tangan
        // kalau checklist pertemuan (PEJABAT & ATASAN, untuk tahun yang
        // sama - lihat User::checklistPertemuanPejabatLengkap()) sudah
        // lengkap. Sebelumnya hanya dicek sebelum cetak PDF (lihat
        // officialPdf() di bawah).
        if (! $employee->checklistPertemuanPejabatLengkap($tahun)) {
            return back()->with(
                'error',
                'Tanda tangan HRD belum bisa disimpan. Checklist pertemuan (Pejabat & Atasan) belum lengkap untuk tahun ini.'
            );
        }

        // Sama seperti signAsHrd(): sekali tanda tangan HRD tersimpan,
        // tidak boleh ditimpa lagi lewat request ini.
        if ($officialEvaluation->hrd_signature) {
            return back()->with(
                'error',
                'Penilaian ini sudah ditanda-tangani HRD dan tidak bisa ditanda-tangani ulang.'
            );
        }

        $validated = $request->validate([
            'hrd_signature' => 'required|string',
        ]);

        if (! preg_match('/^data:image\/png;base64,/', $validated['hrd_signature'])) {
            return back()->withErrors(['hrd_signature' => 'Format tanda tangan tidak valid.']);
        }

        $imageContent = base64_decode(substr($validated['hrd_signature'], strpos($validated['hrd_signature'], ',') + 1));
        $signaturePath = 'signatures/hrd_official_' . $officialEvaluation->id . '_' . time() . '.png';
        Storage::disk('public')->put($signaturePath, $imageContent);

        $officialEvaluation->update([
            'hrd_id'         => Auth::id(),
            'hrd_signature'  => $signaturePath,
            'hrd_signed_at'  => now(),
        ]);

        return back()->with(
            'success',
            'Tanda tangan HRD untuk pejabat berhasil disimpan.'
        );
    }

    public function pdf(Request $request, $id)
    {
        $employee = User::findOrFail($id);
        $tahun = $this->selectedTahun($request);

        // Penilaian pejabat memakai alur & tabel yang berbeda
        // (OfficialEvaluation, bukan Evaluation/SupervisorFeedback),
        // jadi PDF-nya dibuat lewat method terpisah di bawah supaya
        // tidak nyampur dengan logika PDF pegawai selanjutnya di sini.
        if ($employee->role === 'pejabat') {
            return $this->officialPdf($employee, $tahun);
        }

        $feedbacks = Feedback::where('employee_id', $id)
            ->with('reviewer')
            ->get();

        if (!$employee->is_spg && $feedbacks->count() < 3) {
            return back()->with(
                'error',
                'PDF belum dapat dibuat. Minimal 3 tanggapan korelasi diperlukan.'
            );
        }

        // Penilaian dari "Penilai" langsung yang ditugaskan HRD
        // (users.supervisor_id) - lihat catatan di show(). Dibatasi ke
        // tahun yang sedang dipilih ($tahun) supaya PDF yang dicetak
        // sesuai dengan siklus penilaian tahun yang sedang dilihat.
        $evaluation = Evaluation::where('employee_id', $id)
            ->where('official_id', $employee->supervisor_id)
            ->tahunAktif($tahun)
            ->with('official')
            ->first();

        // Kalau Penilai (users.supervisor_id) yang ditugaskan ke pegawai
        // ini menilai secara manual (lihat User::menilaiSecaraManual() &
        // penilaianUtamaManual()), HRD tetap boleh cetak PDF walau belum
        // ada Evaluation dari Penilai lewat sistem.
        if (!$evaluation && !$employee->penilaianUtamaManual()) {
            return back()->with(
                'error',
                'Penilaian pejabat belum tersedia.'
            );
        }

        // Pegawai yang dinilai wajib mengisi tanggapan atas penilaiannya
        // sendiri (Evaluation::employee_response) sebelum PDF boleh
        // dicetak - lihat EmployeeController::respondEvaluation(). Kalau
        // $evaluation tidak ada (mode manual, dinilai di luar aplikasi),
        // tidak ada baris untuk ditanggapi lewat sistem, jadi syarat ini
        // dilewati - sama pola bypass-nya dengan penilaianUtamaManual().
        if ($evaluation && empty($evaluation->employee_response)) {
            return back()->with(
                'error',
                'PDF belum dapat dicetak. Pegawai belum memberikan tanggapan atas penilaiannya sendiri.'
            );
        }

        // Tanggapan Atasan sekarang diambil dari penilaian tambahan yang
        // diberikan Atasan Penilai (mis. atasan dari Penilai langsung
        // pegawai ini) - lihat catatan di show().
        $atasanEvaluation = Evaluation::where('employee_id', $id)
            ->when(
                $employee->supervisor_id,
                fn ($query) => $query->where('official_id', '!=', $employee->supervisor_id)
            )
            ->tahunAktif($tahun)
            ->with('official')
            ->latest()
            ->first();

        // Jika tidak ada Evaluation dari atasan penilai, coba ambil SupervisorFeedback
        $atasanFeedback = null;
        if (!$atasanEvaluation) {
            $atasanFeedback = SupervisorFeedback::where('employee_id', $id)
                ->tahunAktif($tahun)
                ->with('supervisor')
                ->latest()
                ->first();
        }

        // Kalau Atasan Pejabat (users.atasan_pejabat_id) pegawai ini
        // menilai secara manual (menilai_secara_manual), HRD tetap boleh
        // cetak PDF walau atasan belum mengisi tanggapannya lewat sistem -
        // atasan akan mengisinya secara manual di luar aplikasi. Lihat
        // User::tanggapanAtasanManual().
        if (!$atasanEvaluation && !$atasanFeedback && !$employee->tanggapanAtasanManual()) {
            return back()->with(
                'error',
                'Tanggapan atasan belum tersedia.'
            );
        }

        // Checklist "sudah bertemu & evaluasi" dari PEGAWAI dan PENILAI
        // harus sama-sama sudah dicentang sebelum PDF boleh dicetak -
        // lihat EmployeeController::toggleChecklistPertemuan() &
        // OfficialController::toggleChecklistPertemuanPegawai().
        if (! $employee->checklistPertemuanLengkap($tahun)) {
            $pesan = 'PDF belum dapat dicetak. Checklist pertemuan & evaluasi belum lengkap ('
                . ($employee->pegawaiSudahKonfirmasiPertemuan($tahun) ? 'pegawai sudah centang' : 'pegawai belum centang')
                . ', '
                . ($employee->penilaiSudahKonfirmasiPertemuan($tahun) ? 'penilai sudah centang' : 'penilai belum centang')
                . ').';

            return back()->with('error', $pesan);
        }

        $toBase64 = function (?string $path) {
            if (!$path || !Storage::disk('public')->exists($path)) {
                return null;
            }

            return 'data:image/png;base64,' .
                base64_encode(Storage::disk('public')->get($path));
        };

        $korelasiSignatures = $feedbacks->map(function ($feedback) use ($toBase64) {
            return [
                'nama' => $feedback->reviewer->name ?? '-',
                'signature' => $toBase64($feedback->signature),
            ];
        })->values();

        // Tentukan signature atasan: dari Evaluation jika ada, atau dari SupervisorFeedback
        $atasanSignature = $atasanEvaluation 
            ? $toBase64($atasanEvaluation->signature) 
            : $toBase64($atasanFeedback->signature ?? null);

        $signatures = [
            'pejabat' => $toBase64($evaluation?->signature),
            'atasan' => $atasanSignature,
            'korelasi' => $korelasiSignatures,
            'pegawai' => $toBase64($evaluation?->employee_signature),
            'hrd' => $toBase64($evaluation?->hrd_signature),
        ];

        $hrd = $evaluation?->hrd;

        $pdf = Pdf::loadView(
            'admin.pdf',
            compact(
                'employee',
                'feedbacks',
                'evaluation',
                'atasanEvaluation',
                'atasanFeedback',
                'signatures',
                'hrd'
            )
        );

        return $pdf->download(
            'penilaian-' .
            str_replace(' ', '-', strtolower($employee->name)) .
            '.pdf'
        );
    }

    /**
     * Buat & unduh PDF penilaian pejabat. Alurnya sama seperti pdf()
     * untuk pegawai (kehadiran hrd -> korelasi -> penilaian atasan ->
     * tanggapan pejabat -> pdf), tapi sumbernya OfficialEvaluation,
     * bukan Evaluation/SupervisorFeedback, jadi dipisah di sini.
     */
    private function officialPdf(User $pejabat, ?int $tahun = null)
    {
        $feedbacks = Feedback::where('employee_id', $pejabat->id)
            ->with('reviewer')
            ->get();

        if ($feedbacks->count() < User::MIN_TANGGAPAN_KORELASI) {
            return back()->with(
                'error',
                'PDF belum dapat dibuat. Minimal ' . User::MIN_TANGGAPAN_KORELASI . ' tanggapan korelasi diperlukan.'
            );
        }

        // Penilaian dari Atasan Pejabat yang ditugaskan HRD
        // (users.supervisor_id milik pejabat ini) - lihat catatan di show().
        // Dibatasi ke tahun yang sedang dipilih ($tahun, default tahun
        // berjalan) supaya PDF yang dicetak sesuai siklus penilaian tahun
        // yang sedang dilihat.
        $evaluation = OfficialEvaluation::where('official_id', $pejabat->id)
            ->where('supervisor_id', $pejabat->supervisor_id)
            ->tahunAktif($tahun)
            ->with('supervisor')
            ->first();

        // Kalau Penilai (users.supervisor_id) pejabat ini menilai secara
        // manual (menilai_secara_manual), HRD tetap boleh cetak PDF walau
        // Penilai belum mengisi penilaiannya lewat sistem - penilai akan
        // mengisinya secara manual di luar aplikasi. Lihat
        // User::tanggapanPenilaiPejabatManual().
        if (! $evaluation && ! $pejabat->tanggapanPenilaiPejabatManual()) {
            return back()->with(
                'error',
                'Penilaian atasan pejabat belum tersedia.'
            );
        }

        // Pejabat yang dinilai wajib mengisi tanggapan atas penilaiannya
        // sendiri (OfficialEvaluation::employee_response) sebelum PDF
        // boleh dicetak - lihat OfficialController::respondEvaluation().
        // Dilewati kalau $evaluation tidak ada (mode manual, sama pola
        // bypass-nya seperti di atas) ATAU kalau Penilai/Atasan Penilai
        // pejabat ini menilai secara manual (User::
        // tanggapanPenilaiPejabatManual()) - walau baris OfficialEvaluation-
        // nya sudah ada, pejabat tidak wajib menanggapi lewat sistem kalau
        // seluruh alur persetujuannya berjalan manual di luar aplikasi.
        if ($evaluation && empty($evaluation->employee_response) && ! $pejabat->tanggapanPenilaiPejabatManual()) {
            return back()->with(
                'error',
                'PDF belum dapat dicetak. Pejabat belum memberikan tanggapan atas penilaiannya sendiri.'
            );
        }

        // Checklist "sudah bertemu & evaluasi" dari PEJABAT dan ATASAN
        // harus sama-sama sudah dicentang sebelum PDF boleh dicetak -
        // versi pejabat dari pengecekan yang sama di pdf() untuk pegawai.
        // Lihat OfficialController::toggleChecklistPertemuanSaya() &
        // SupervisorController::toggleChecklistPertemuanPejabat().
        if (! $pejabat->checklistPertemuanPejabatLengkap($tahun)) {
            $pesan = 'PDF belum dapat dicetak. Checklist pertemuan & evaluasi belum lengkap ('
                . ($pejabat->pejabatSudahKonfirmasiPertemuan($tahun) ? 'pejabat sudah centang' : 'pejabat belum centang')
                . ', '
                . ($pejabat->atasanSudahKonfirmasiPertemuan($tahun) ? 'atasan sudah centang' : 'atasan belum centang')
                . ').';

            return back()->with('error', $pesan);
        }

        // Tanda tangan HRD wajib ada sebelum PDF pejabat boleh dicetak -
        // tanpa pengecekan ini, PDF tetap bisa diunduh walau HRD belum
        // menandatangani (lihat SignatureDocumentController / HRD::signAsHrdOfficial()
        // untuk alur pengisian hrd_signature pada OfficialEvaluation).
        // Kalau $evaluation belum ada (mode manual, penilai akan mengisi di
        // luar aplikasi), belum ada baris untuk ditandatangani HRD, jadi
        // pengecekan ini dilewati. SAMA HALNYA kalau Penilai (users.
        // supervisor_id) pejabat ini menilai secara manual
        // (User::tanggapanPenilaiPejabatManual()) walau baris
        // OfficialEvaluation-nya sudah ada - PDF tetap boleh diunduh tanpa
        // menunggu tanda tangan HRD, karena persetujuannya berjalan di
        // luar sistem.
        if ($evaluation && ! $evaluation->hrd_signature && ! $pejabat->tanggapanPenilaiPejabatManual()) {
            return back()->with(
                'error',
                'PDF belum dapat dicetak. Tanda tangan HRD belum tersedia.'
            );
        }

        $toBase64 = function (?string $path) {
            if (!$path || !Storage::disk('public')->exists($path)) {
                return null;
            }

            return 'data:image/png;base64,' .
                base64_encode(Storage::disk('public')->get($path));
        };

        $korelasiSignatures = $feedbacks->map(function ($feedback) use ($toBase64) {
            return [
                'nama' => $feedback->reviewer->name ?? '-',
                'signature' => $toBase64($feedback->signature),
            ];
        })->values();

        // Tanggapan dari Atasan Penilai pejabat (OfficialSupervisorFeedback),
        // dipakai untuk menampilkan tanggapan atasan penilai di PDF - versi
        // pejabat dari $atasanEvaluation/$atasanFeedback pada pdf() pegawai.
        $officialSupervisorFeedback = \App\Models\OfficialSupervisorFeedback::where('official_id', $pejabat->id)
            ->tahunAktif($tahun)
            ->with('supervisor')
            ->latest()
            ->first();

        $signatures = [
            'atasan' => $toBase64($evaluation?->signature),
            'atasan_penilai' => $toBase64($officialSupervisorFeedback->signature ?? null),
            'pejabat' => $toBase64($evaluation?->employee_signature),
            'hrd' => $toBase64($evaluation?->hrd_signature),
            'hrd_name' => $evaluation?->hrd?->name ?? 'HRD & GA',
            'korelasi' => $korelasiSignatures,
        ];

        $pdf = Pdf::loadView(
            'admin.pdf-pejabat',
            compact('pejabat', 'feedbacks', 'evaluation', 'signatures', 'officialSupervisorFeedback')
        );

        return $pdf->download(
            'penilaian-pejabat-' .
            str_replace(' ', '-', strtolower($pejabat->name)) .
            '.pdf'
        );
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Feedback;
use App\Models\Evaluation;
use App\Models\SupervisorFeedback;
use App\Models\OfficialEvaluation;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use PhpOffice\PhpSpreadsheet\IOFactory;

class AdminController extends Controller
{
    private const EVALUATOR_ROLES = ['pejabat', 'hrd'];

    private const ROLES_WITH_EVALUATOR = ['pejabat', 'pegawai'];

    /**
     * Query builder untuk akun yang boleh ditugaskan sebagai
     * atasan/penilai (users.supervisor_id): akun pejabat/hrd (selalu
     * boleh), ATAU akun pegawai yang khusus ditandai HRD lewat checkbox
     * "Boleh Menilai Pegawai Lain" (users.boleh_menilai_pegawai_lain).
     * Dipakai untuk isi dropdown "Atasan/Penilai" di form akun, dan untuk
     * validasi supervisor_id supaya konsisten dengan apa yang muncul di
     * dropdown tsb.
     */
    private function evaluatorCandidatesQuery()
    {
        // whereRaw dengan LOWER(TRIM(role)) supaya akun yang role-nya
        // kesimpan dengan variasi spasi/huruf besar-kecil (mis. dari hasil
        // import Excel) tetap terhitung, bukan hilang diam-diam dari
        // dropdown "Atasan/Penilai".
        return User::where(function ($query) {
            $query->whereRaw('LOWER(TRIM(role)) IN (?, ?)', self::EVALUATOR_ROLES)
                ->orWhere(function ($query) {
                    $query->whereRaw('LOWER(TRIM(role)) = ?', ['pegawai'])
                        ->where('boleh_menilai_pegawai_lain', true);
                });
        });
    }

    public function index()
    {
        // Dibatasi ke tahun berjalan (tahunAktif) supaya dashboard admin
        // konsisten dengan OfficialController::index() dan tidak membuat
        // pegawai/pejabat terlihat "sudah dinilai" selamanya begitu tahun
        // baru mulai - penilaian tahun lalu tetap tersimpan sebagai
        // histori.
        $employees = User::where('role', 'pegawai')
            ->withCount('feedbacksReceived')
            ->with(['evaluations' => function ($query) {
                $query->tahunAktif()->latest()->limit(1);
            }])
            ->with(['supervisorFeedbacks' => function ($query) {
                $query->tahunAktif()->latest()->limit(1);
            }])
            ->get();

        $accounts = User::orderBy('role')
            ->orderBy('name')
            ->with('supervisor')
            ->get();

        $atasanList = $this->evaluatorCandidatesQuery()
            ->orderBy('name')
            ->get();

        $pejabatBinaan = User::where('role', 'pejabat')
            ->where('supervisor_id', Auth::id())
            ->withCount([
                'officialEvaluations as evaluated_count' => function ($query) {
                    $query->where('supervisor_id', Auth::id())->tahunAktif();
                }
            ])
            ->get();

        $pegawaiBindung = User::where('role', 'pegawai')
            ->where('supervisor_id', Auth::id())
            ->withCount('feedbacksReceived')
            ->with(['evaluations' => function ($query) {
                $query->where('official_id', Auth::id())->tahunAktif();
            }])
            ->get();

        return view(
            'admin.dashboard',
            compact('employees', 'accounts', 'atasanList', 'pejabatBinaan', 'pegawaiBindung')
        );
    }

    public function storeAccount(Request $request)
    {
        // Normalisasi role dulu sebelum divalidasi/dipakai, supaya spasi
        // atau selisih huruf besar/kecil dari input (mis. " Pegawai")
        // tidak bikin perbandingan `=== 'pegawai'` di bawah gagal diam-diam.
        if ($request->has('role')) {
            $request->merge([
                'role' => strtolower(trim((string) $request->input('role'))),
            ]);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:100|alpha_dash|unique:users,username',
            'nik' => 'required|string|max:50|unique:users,nik',
            'unit_kerja' => 'nullable|string|max:255',
            'jabatan' => 'nullable|string|max:255',
            'role' => 'required|in:pegawai,pejabat,hrd',
            'is_spg' => 'nullable|boolean',
            'boleh_menilai_pegawai_lain' => 'nullable|boolean',
            'supervisor_id' => [
                'nullable',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where(function ($query) {
                        $query->whereRaw('LOWER(TRIM(role)) IN (?, ?)', self::EVALUATOR_ROLES)
                            ->orWhere(function ($query) {
                                $query->whereRaw('LOWER(TRIM(role)) = ?', ['pegawai'])
                                    ->where('boleh_menilai_pegawai_lain', true);
                            });
                    });
                }),
            ],
        ]);

        // Pakai $request->boolean() (bukan !empty($validated[...])) supaya
        // checkbox yang tidak dicentang (key tidak terkirim sama sekali)
        // maupun yang dikirim '0'/'false' konsisten dibaca sebagai false.
        User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'nik' => $validated['nik'],
            'unit_kerja' => $validated['unit_kerja'] ?? null,
            'jabatan' => $validated['jabatan'] ?? null,
            'is_spg' => $validated['role'] === 'pegawai' && $request->boolean('is_spg'),
            'boleh_menilai_pegawai_lain' => $validated['role'] === 'pegawai' && $request->boolean('boleh_menilai_pegawai_lain'),
            'supervisor_id' => in_array($validated['role'], self::ROLES_WITH_EVALUATOR, true)
                ? ($validated['supervisor_id'] ?? null)
                : null,
            'email' => $validated['username'] . '@pegawai.local',
            'password' => bcrypt($validated['nik']),
            'role' => $validated['role'],
        ]);

        return back()->with('success', 'Akun berhasil ditambahkan.');
    }

    private const IMPORT_COLUMN_MAP = [
        'nik' => 'nik',
        'nama lengkap' => 'name',
        'jabatan' => 'jabatan',
        'unit' => 'unit_kerja',
        'penilai' => 'penilai',
        'indikator' => 'indikator',
    ];

    private const INDIKATOR_ROLE_ALIASES = [
        'pegawai' => 'pegawai',
        'karyawan' => 'pegawai',
        'pejabat' => 'pejabat',
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

            $spreadsheet = $reader->load($absolutePath);

            $rows = $spreadsheet
                ->getActiveSheet()
                ->toArray(null, true, true, false);

            if (count($rows) < 2) {
                return back()->with(
                    'error',
                    'File kosong atau tidak memiliki baris data.'
                );
            }

            // =====================================================
            // HEADER
            // =====================================================

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

            foreach (['nik', 'name', 'indikator'] as $required) {
                if (!isset($columnIndex[$required])) {
                    return back()->with(
                        'error',
                        'Kolom wajib "' . $required . '" tidak ditemukan. ' .
                        'Minimal harus ada: NIK, Nama Lengkap, dan Indikator.'
                    );
                }
            }

            // =====================================================
            // IMPORT
            // =====================================================

            $created = [];
            $skipped = [];
            $warnings = [];
            $penilaiQueue = [];

            for ($rowNum = 1; $rowNum < count($rows); $rowNum++) {
                $row = $rows[$rowNum];
                $excelRowLabel = 'Baris ' . ($rowNum + 1);

                $cell = function (string $field) use ($columnIndex, $row) {
                    if (!isset($columnIndex[$field])) {
                        return null;
                    }

                    return trim(
                        (string) ($row[$columnIndex[$field]] ?? '')
                    );
                };

                $nik = $cell('nik');
                $name = $cell('name');
                $jabatan = $cell('jabatan') ?: null;
                $unitKerja = $cell('unit_kerja') ?: null;
                $penilaiName = $cell('penilai') ?: null;
                $indikatorRaw = $cell('indikator');

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
                // CEK NIK
                // =================================================

                if (User::where('nik', $nik)->exists()) {
                    $skipped[] =
                        "{$excelRowLabel}: NIK {$nik} sudah terdaftar, dilewati.";
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
                        'role' => $role,
                        'is_spg' => false,
                        'email' => $username . '@pegawai.local',
                        'password' => bcrypt($nik),
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

            // =====================================================
            // HUBUNGKAN PENILAI
            // =====================================================

            foreach ($penilaiQueue as $userId => $penilaiName) {
                $penilai = $this->evaluatorCandidatesQuery()
                    ->whereRaw(
                        'LOWER(TRIM(name)) = ?',
                        [strtolower(trim($penilaiName))]
                    )
                    ->first();

                if (!$penilai) {
                    $warnings[] =
                        "\"{$penilaiName}\" (Penilai) tidak ditemukan " .
                        "sebagai akun Pejabat/Admin. " .
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
            // KEMBALIKAN HASIL IMPORT
            // =====================================================

            return back()->with('import_report', [
                'created' => $created,
                'skipped' => $skipped,
                'warnings' => $warnings,
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

        // Sama seperti storeAccount(): normalisasi role sebelum divalidasi
        // supaya perbandingan role === 'pegawai' di bawah tidak meleset
        // gara-gara spasi/huruf besar-kecil yang ikut terkirim dari form.
        if ($request->has('role')) {
            $request->merge([
                'role' => strtolower(trim((string) $request->input('role'))),
            ]);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:100|alpha_dash|unique:users,username,' . $account->id,
            'nik' => 'required|string|max:50|unique:users,nik,' . $account->id,
            'unit_kerja' => 'nullable|string|max:255',
            'jabatan' => 'nullable|string|max:255',
            'role' => 'required|in:pegawai,pejabat,hrd',
            'is_spg' => 'nullable|boolean',
            'boleh_menilai_pegawai_lain' => 'nullable|boolean',
            'supervisor_id' => [
                'nullable',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where(function ($query) {
                        $query->whereRaw('LOWER(TRIM(role)) IN (?, ?)', self::EVALUATOR_ROLES)
                            ->orWhere(function ($query) {
                                $query->whereRaw('LOWER(TRIM(role)) = ?', ['pegawai'])
                                    ->where('boleh_menilai_pegawai_lain', true);
                            });
                    });
                }),
            ],
        ]);

        if (
            in_array($validated['role'], self::ROLES_WITH_EVALUATOR, true)
            && (int) ($validated['supervisor_id'] ?? 0) === $account->id
        ) {
            return back()
                ->withErrors([
                    'supervisor_id' => 'Akun tidak bisa ditugaskan sebagai atasan/penilainya sendiri.'
                ])
                ->withInput();
        }

        $accountBolehMenilaiPegawaiLain = $validated['role'] === 'pegawai' && $request->boolean('boleh_menilai_pegawai_lain');

        $account->update([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'nik' => $validated['nik'],
            'unit_kerja' => $validated['unit_kerja'] ?? null,
            'jabatan' => $validated['jabatan'] ?? null,
            'is_spg' => $validated['role'] === 'pegawai' && $request->boolean('is_spg'),
            'boleh_menilai_pegawai_lain' => $accountBolehMenilaiPegawaiLain,
            'supervisor_id' => in_array($validated['role'], self::ROLES_WITH_EVALUATOR, true)
                ? ($validated['supervisor_id'] ?? null)
                : null,
            'role' => $validated['role'],
        ]);

        // Kalau akun ini bukan lagi pejabat/hrd, DAN (kalau pegawai) sudah
        // tidak ditandai "Boleh Menilai Pegawai Lain", putuskan relasi
        // supervisor_id semua akun yang sebelumnya ditugaskan ke akun ini
        // - supaya tidak ada pegawai yang "menggantung" dinilai oleh akun
        // yang sudah tidak berhak menilai lagi.
        if (
            !in_array($validated['role'], self::EVALUATOR_ROLES, true)
            && !$accountBolehMenilaiPegawaiLain
        ) {
            User::where('supervisor_id', $account->id)
                ->update(['supervisor_id' => null]);
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

    public function updateAttendance(Request $request, $id)
    {
        $employee = User::findOrFail($id);

        $validated = $request->validate([
            'jumlah_izin' => 'nullable|integer|min:0',
            'jumlah_sakit' => 'nullable|integer|min:0',
            'jumlah_alpa' => 'nullable|integer|min:0',
            'jumlah_terlambat' => 'nullable|integer|min:0',
            'contract_status' => 'nullable|in:' . implode(',', array_keys(User::CONTRACT_STATUSES)),
        ]);

        $employee->update([
            'jumlah_izin' => $validated['jumlah_izin'] ?? 0,
            'jumlah_sakit' => $validated['jumlah_sakit'] ?? 0,
            'jumlah_alpa' => $validated['jumlah_alpa'] ?? 0,
            'jumlah_terlambat' => $validated['jumlah_terlambat'] ?? 0,
            'contract_status' => $validated['contract_status'] ?? null,
            // Tandai kehadiran sudah diisi HRD - jadi penilai boleh mulai
            // menilai pegawai ini (lihat User::siapDinilaiPenilai()).
            'kehadiran_diisi_at' => now(),
        ]);

        return back()->with(
            'success',
            'Data kehadiran & status kontrak berhasil diperbarui.'
        );
    }

    public function show($id)
    {
        $employee = User::findOrFail($id);

        $feedbacks = Feedback::where('employee_id', $id)
            ->with('reviewer')
            ->get();

        // Dibatasi ke tahun berjalan supaya halaman detail selalu
        // menampilkan siklus penilaian tahun ini - penilaian tahun-tahun
        // sebelumnya tetap tersimpan di database sebagai histori.
        $evaluation = Evaluation::where('employee_id', $id)
            ->tahunAktif()
            ->with('official')
            ->first();

        $supervisorFeedback = SupervisorFeedback::where('employee_id', $id)
            ->tahunAktif()
            ->with('supervisor')
            ->first();

        return view(
            'admin.detail',
            compact('employee', 'feedbacks', 'evaluation', 'supervisorFeedback')
        );
    }

    public function pdf($id)
    {
        $employee = User::findOrFail($id);

        $feedbacks = Feedback::where('employee_id', $id)
            ->with('reviewer')
            ->get();

        if (!$employee->is_spg && $feedbacks->count() < 3) {
            return back()->with(
                'error',
                'PDF belum dapat dibuat. Minimal 3 tanggapan korelasi diperlukan.'
            );
        }

        // Dibatasi ke tahun berjalan supaya PDF yang dicetak selalu untuk
        // siklus penilaian tahun ini.
        $evaluation = Evaluation::where('employee_id', $id)
            ->tahunAktif()
            ->with('official')
            ->first();

        if (!$evaluation) {
            return back()->with(
                'error',
                'Penilaian pejabat belum tersedia.'
            );
        }

        $supervisorFeedback = SupervisorFeedback::where('employee_id', $id)
            ->tahunAktif()
            ->with('supervisor')
            ->first();

        if (!$supervisorFeedback) {
            return back()->with(
                'error',
                'Tanggapan atasan belum tersedia.'
            );
        }

        // Template admin.pdf butuh $atasanEvaluation / $atasanFeedback / $hrd
        // (sama seperti di HrdController@pdf). Di alur Admin, tanggapan atasan
        // hanya tersedia lewat SupervisorFeedback, jadi kita petakan ke situ.
        $atasanEvaluation = null;
        $atasanFeedback = $supervisorFeedback;
        $hrd = $evaluation->hrd ?? null;

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

        $signatures = [
            'pejabat' => $toBase64($evaluation->signature),
            'atasan' => $toBase64($supervisorFeedback->signature),
            'korelasi' => $korelasiSignatures,
            'pegawai' => $toBase64($evaluation->employee_signature),
            'hrd' => $toBase64($evaluation->hrd_signature),
        ];

        $pdf = Pdf::loadView(
            'admin.pdf',
            compact(
                'employee',
                'feedbacks',
                'evaluation',
                'supervisorFeedback',
                'atasanEvaluation',
                'atasanFeedback',
                'hrd',
                'signatures'
            )
        );

        return $pdf->download(
            'penilaian-' .
            str_replace(' ', '-', strtolower($employee->name)) .
            '.pdf'
        );
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Evaluation;
use App\Models\Feedback;
use App\Models\OfficialEvaluation;
use App\Models\SupervisorFeedback;
use App\Models\OfficialSupervisorFeedback;
use App\Services\NotificationTriggerService;
use App\Http\Controllers\Concerns\HandlesChecklistEvidence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\QueryException;

class OfficialController extends Controller
{
    use HandlesChecklistEvidence;

    public function index()
    {
        $employees = $this->scopedEmployeesQuery()
            ->withCount('feedbacksReceived')
            ->withCount('supervisorFeedbacks')
            ->with(['evaluations' => function ($query) {
                // Difilter ke tahun berjalan supaya status "sudah dinilai"
                // di dashboard cuma mencerminkan siklus penilaian tahun
                // ini - penilaian tahun lalu tetap tersimpan sebagai
                // histori tapi tidak bikin pegawai terlihat "sudah
                // dinilai" selamanya begitu tahun baru mulai.
                $query->where('official_id', Auth::id())->tahunAktif();
            }])
            ->get();

        // Pegawai yang pejabat ini ditugaskan sebagai "Atasan Penilai"-nya
        // (users.atasan_pejabat_id) - HANYA berhak diberi Tanggapan Atasan
        // (SupervisorFeedback), TIDAK boleh dinilai (lihat canEvaluate()).
        // Ditampilkan di sini juga (bukan cuma di /atasan/dashboard) karena
        // pejabat selalu diarahkan ke /pejabat/dashboard setelah login,
        // jadi kalau cuma ada di /atasan/dashboard mereka tidak akan pernah
        // menemukannya.
        // Status di bagian Atasan Penilai harus mengikuti siklus tahun berjalan.
        // Sebelumnya withCount('evaluations') menghitung seluruh histori, sehingga
        // status dapat tidak sinkron dengan penilaian yang baru saja diberikan.
        $atasanPenilaiEmployees = User::where('role', 'pegawai')
            ->where('atasan_pejabat_id', Auth::id())
            ->withCount(['supervisorFeedbacks as supervisor_feedbacks_count' => function ($query) {
                $query->where('supervisor_id', Auth::id())->tahunAktif();
            }])
            ->withCount(['evaluations as evaluations_count' => function ($query) {
                $query->tahunAktif();
            }])
            ->with(['evaluations' => function ($query) {
                // Dipakai di view untuk mengecek employee_signature (lihat
                // catatan "tanggapanLocked" di showTanggapanPegawai()),
                // supaya tombol "Beri Tanggapan" di dashboard ikut
                // menunjukkan status terkunci begitu pegawai sudah tanda
                // tangan, bukan cuma diblokir di server saja.
                $query->tahunAktif();
            }])
            ->get();

        // Pejabat lain yang secara khusus ditugaskan untuk dinilai oleh
        // pejabat yang sedang login (users.supervisor_id bisa menunjuk ke
        // akun pejabat, bukan cuma atasan_pejabat).
        $pejabatBinaan = User::where('role', 'pejabat')
            ->where('supervisor_id', Auth::id())
            ->withCount(
                ['officialEvaluations as evaluated_count' => function ($query) {
                    $query->where('supervisor_id', Auth::id())->tahunAktif();
                }]
            )
            ->withCount('feedbacksReceived')
            ->with(['officialEvaluations' => function ($query) {
                // Dipakai untuk mengecek employee_signature supaya tombol
                // "Nilai Pejabat" ikut berubah jadi "Lihat Penilaian" +
                // indikator terkunci begitu pejabat yang dinilai sudah
                // tanda tangan - sama pola-nya seperti $atasanPenilaiPejabatList
                // & $atasanPenilaiEmployees di bawah.
                $query->where('supervisor_id', Auth::id())->tahunAktif();
            }])
            ->get();

        // Pejabat yang pejabat ini ditugaskan sebagai "Atasan Penilai"-nya
        // (users.atasan_penilai_pejabat_id) - HANYA berhak diberi Tanggapan
        // Atasan (OfficialSupervisorFeedback), TIDAK boleh ikut menilai.
        // Sama pola-nya seperti $atasanPenilaiEmployees di atas, tapi untuk
        // pejabat->pejabat, bukan pejabat->pegawai.
        $atasanPenilaiPejabatList = User::where('role', 'pejabat')
            ->where('atasan_penilai_pejabat_id', Auth::id())
            ->withCount(['officialEvaluations as official_evaluations_count' => function ($query) {
                $query->tahunAktif();
            }])
            ->withCount(['officialSupervisorFeedbacks as official_supervisor_feedbacks_count' => function ($query) {
                $query->where('supervisor_id', Auth::id())->tahunAktif();
            }])
            ->with(['officialEvaluations' => function ($query) {
                // Sama seperti $atasanPenilaiEmployees di atas: dipakai
                // untuk mengecek employee_signature supaya tombol "Beri
                // Tanggapan" ikut menunjukkan status terkunci.
                $query->tahunAktif();
            }])
            ->get();

        // Pejabat lain (di semua unit kerja, bukan cuma unit kerja yang
        // sama dengan pejabat yang sedang login), untuk fitur "Beri
        // Tanggapan ke Pejabat Lain". Memakai model Feedback yang sama
        // dengan tanggapan korelasi antar pegawai (employee_id = pejabat
        // yang ditanggapi, reviewer_id = pejabat yang memberi tanggapan),
        // jadi tidak perlu tabel baru.
        //
        // Pejabat yang SUDAH diberi tanggapan oleh pejabat yang sedang
        // login dikecualikan dari daftar pilihan - satu akun hanya boleh
        // memberi tanggapan sekali ke satu orang yang sama (lihat juga
        // pengecekan di feedback()).
        $alreadyGivenPeerFeedbackIds = Feedback::where('reviewer_id', Auth::id())
            ->pluck('employee_id');

        $peerOfficials = User::where('role', 'pejabat')
            ->where('id', '!=', Auth::id())
            ->whereNotIn('id', $alreadyGivenPeerFeedbackIds)
            ->orderBy('name')
            ->get();

        $myPeerFeedbacks = Feedback::where('employee_id', Auth::id())
            ->whereHas('reviewer', function ($query) {
                $query->where('role', 'pejabat');
            })
            ->with('reviewer')
            ->latest()
            ->get();

        // Pejabat lain yang SUDAH diberi tanggapan (korelasi) oleh pejabat
        // yang sedang login — kebalikan dari $myPeerFeedbacks di atas.
        $myGivenPeerFeedbacks = Feedback::where('reviewer_id', Auth::id())
            ->whereHas('employee', function ($query) {
                $query->where('role', 'pejabat');
            })
            ->with('employee')
            ->latest()
            ->get();

        // Pegawai yang SUDAH dinilai (Evaluation) oleh pejabat yang sedang
        // login — diambil dari koleksi $employees yang relasi 'evaluations'-nya
        // sudah difilter official_id = Auth::id() di query di atas.
        $sudahDinilaiEmployees = $employees->filter(
            fn ($employee) => $employee->evaluations->isNotEmpty()
        )->values();

        return view('official.dashboard', compact(
            'employees',
            'atasanPenilaiEmployees',
            'pejabatBinaan',
            'atasanPenilaiPejabatList',
            'peerOfficials',
            'myPeerFeedbacks',
            'myGivenPeerFeedbacks',
            'sudahDinilaiEmployees'
        ));
    }

    /**
     * Query dasar "pegawai yang boleh dinilai oleh pejabat yang sedang
     * login" - diekstrak dari index() supaya bisa dipakai ulang di
     * statusVersion() tanpa duplikasi logika scoping (lihat catatan
     * lengkapnya yang aslinya ada di index()):
     * 1) pegawai yang secara khusus ditugaskan (users.supervisor_id)
     *    ke pejabat ini, ATAU
     * 2) pegawai yang "Atasan Penilai"-nya ditugaskan ke pejabat lain,
     *    tapi pejabat lain itu sendiri diawasi oleh pejabat yang sedang
     *    login (atasan dari atasan) — karena mereka berada dalam satu
     *    unit yang sama.
     * KECUALI kalau pejabat yang sedang login ini adalah "Atasan
     * Penilai" (users.atasan_pejabat_id) pegawai tsb.
     */
    private function scopedEmployeesQuery()
    {
        return User::where('role', 'pegawai')
            ->where(function ($query) {
                $query->whereNull('atasan_pejabat_id')
                    ->orWhere('atasan_pejabat_id', '!=', Auth::id());
            })
            ->where(function ($query) {
                $query->where('supervisor_id', Auth::id())
                    ->orWhereHas('supervisor', function ($query) {
                        $query->where('supervisor_id', Auth::id());
                    });
            });
    }

    /**
     * AJAX polling status untuk official.dashboard - lihat pola & alasan
     * di EmployeeController::statusVersion(). Dilingkupi ke:
     * 1) tanggapan korelasi baru masuk ke pejabat ini,
     * 2) penilaian baru yang dibuat pejabat ini,
     * 3) SupervisorFeedback (tanggapan Atasan Penilai) untuk
     *    pegawai-pegawai di bawah pejabat ini - INI PENTING karena
     *    dashboard menunggu status ini selesai sebelum pejabat bisa
     *    memberi checklist ke pegawai (lihat User::checklistPertemuanBolehDiisi()),
     * 4) perubahan kolom checklist di tabel users milik pegawai-pegawai
     *    itu (mis. toggle checklist pertemuan).
     */
    public function statusVersion()
    {
        $officialId = Auth::id();

        $employeeIds = $this->scopedEmployeesQuery()->pluck('id');

        $latestPeerFeedback = Feedback::where('employee_id', $officialId)
            ->latest()
            ->value('updated_at');

        $latestEvaluation = Evaluation::where('official_id', $officialId)
            ->latest()
            ->value('updated_at');

        $latestSupervisorFeedback = SupervisorFeedback::whereIn('employee_id', $employeeIds)
            ->latest()
            ->value('updated_at');

        $latestEmployeeUpdate = User::whereIn('id', $employeeIds)
            ->max('updated_at');

        $version = md5(
            $latestPeerFeedback . '|' . $latestEvaluation . '|'
            . $latestSupervisorFeedback . '|' . $latestEmployeeUpdate
        );

        return response()->json(['version' => $version]);
    }

    /**
     * Pejabat memberi tanggapan (korelasi) ke pejabat lain, di unit kerja
     * manapun (tidak dibatasi hanya ke satu unit kerja yang sama). Memakai
     * model & pola yang sama dengan EmployeeController::feedback, tapi
     * target & pemberinya sama-sama ber-role pejabat.
     */
    public function feedback(Request $request)
    {
        $validated = $request->validate([
            'official_id' => 'required|exists:users,id',
            'feedback'    => 'required|string|min:10',
            'signature'   => 'required|string',
        ]);

        $targetOfficial = User::where('role', 'pejabat')->findOrFail($validated['official_id']);

        if ($targetOfficial->id === Auth::id()) {
            return back()
                ->withErrors(['official_id' => 'Anda tidak bisa memberi tanggapan untuk diri sendiri.'])
                ->withInput();
        }

        // Satu akun hanya boleh memberi tanggapan SEKALI ke satu orang
        // yang sama - dicek di sini (bukan cuma disembunyikan dari
        // picker di index()) supaya tetap aman walau request dikirim
        // langsung/lewat form yang sudah kadaluarsa.
        $alreadyGivenFeedback = Feedback::where('employee_id', $targetOfficial->id)
            ->where('reviewer_id', Auth::id())
            ->exists();

        if ($alreadyGivenFeedback) {
            return back()
                ->withErrors(['official_id' => 'Anda sudah pernah memberi tanggapan untuk pejabat ini.'])
                ->withInput();
        }

        // Pastikan data yang dikirim benar-benar gambar base64 dari canvas
        if (! preg_match('/^data:image\/png;base64,/', $validated['signature'])) {
            return back()->withErrors(['signature' => 'Format tanda tangan tidak valid.'])->withInput();
        }

        $imageContent = base64_decode(substr($validated['signature'], strpos($validated['signature'], ',') + 1));
        $signaturePath = 'signatures/official_feedback_' . $targetOfficial->id . '_' . Auth::id() . '_' . time() . '.png';
        Storage::disk('public')->put($signaturePath, $imageContent);

        try {
            Feedback::create([
                'employee_id' => $targetOfficial->id,
                'reviewer_id' => Auth::id(),
                'feedback'    => $validated['feedback'],
                'signature'   => $signaturePath,
            ]);
        } catch (QueryException $e) {
            // Jaga-jaga kalau dua submit terjadi hampir bersamaan dan
            // lolos dari pengecekan exists() di atas - unique index di
            // migrasi feedbacks (reviewer_id, employee_id) akan menolak
            // baris kedua di level database.
            if (str_contains($e->getMessage(), 'feedbacks_reviewer_id_employee_id_unique')) {
                return back()
                    ->withErrors(['official_id' => 'Anda sudah pernah memberi tanggapan untuk pejabat ini.'])
                    ->withInput();
            }

            throw $e;
        }

        // Kirim notifikasi FCM ke Atasan jika pejabat ini kini siap
        // dinilai (korelasi cukup + kehadiran sudah diisi HRD). Dilakukan
        // SETELAH tersimpan - notifikasi bersifat tambahan, bukan blocking.
        app(NotificationTriggerService::class)
            ->triggerSiapDinilaiPenilaiPejabatJikaPerlu($targetOfficial);

        return back()->with(
            'success',
            'Tanggapan untuk pejabat berhasil dikirim.'
        );
    }

    /**
     * Halaman "Nilai Saya": menampilkan seluruh hasil penilaian yang
     * diterima pejabat yang sedang login dari atasan pejabatnya,
     * lengkap per-komponen (bukan cuma total nilai).
     */
    public function myEvaluations()
    {
        $myOfficialEvaluations = Auth::user()
            ->officialEvaluations()
            ->with('supervisor')
            ->latest()
            ->get();

        // Tanggapan/rekomendasi dari Atasan Penilai (OfficialSupervisorFeedback)
        // untuk pejabat ini, dikelompokkan per tahun supaya bisa dipasangkan
        // dengan penilaian dari Penilai (OfficialEvaluation) pada tahun yang sama.
        $myOfficialSupervisorFeedbacks = Auth::user()
            ->officialSupervisorFeedbacks()
            ->with('supervisor')
            ->get()
            ->keyBy('tahun');

        return view('official.my-evaluations', compact('myOfficialEvaluations', 'myOfficialSupervisorFeedbacks'));
    }

    /**
     * Pejabat menanggapi salah satu penilaian yang diterimanya dari
     * atasan. Mirip EmployeeController::respondEvaluation, tapi untuk
     * OfficialEvaluation.
     */
    public function respondEvaluation(Request $request, OfficialEvaluation $evaluation)
    {
        // Pastikan pejabat hanya bisa menanggapi penilaian miliknya sendiri.
        if ($evaluation->official_id !== Auth::id()) {
            abort(403);
        }

        // Pejabat baru boleh mengisi tanggapan setelah ATASAN PENILAI
        // (OfficialSupervisorFeedback) sudah menyelesaikan tanggapannya
        // untuk tahun yang sama. Ini menyamakan alur dengan pegawai,
        // agar pejabat tidak bisa menanggapi sebelum menerima balasan.
        $atasanPenilaiSudahMenanggapi = OfficialSupervisorFeedback::where(
            'official_id',
            $evaluation->official_id
        )
            ->where('tahun', $evaluation->tahun)
            ->exists();

        if (! $atasanPenilaiSudahMenanggapi) {
            return back()->with(
                'error',
                'Anda belum dapat mengisi tanggapan. Tanggapan dari Atasan Penilai belum diselesaikan.'
            );
        }

        $validated = $request->validate([
            'employee_response'  => 'required|string|min:5',
            'employee_signature' => 'required|string',
        ]);

        // Pastikan data yang dikirim benar-benar gambar base64 dari canvas
        // (sama seperti EmployeeController::respondEvaluation).
        if (! preg_match('/^data:image\/png;base64,/', $validated['employee_signature'])) {
            return back()->withErrors(['employee_signature' => 'Format tanda tangan tidak valid.'])->withInput();
        }

        $imageContent = base64_decode(substr($validated['employee_signature'], strpos($validated['employee_signature'], ',') + 1));
        $signaturePath = 'signatures/official_evaluation_response_' . $evaluation->id . '_' . time() . '.png';
        Storage::disk('public')->put($signaturePath, $imageContent);

        $evaluation->update([
            'employee_response'    => $validated['employee_response'],
            'employee_response_at' => now(),
            'employee_signature'   => $signaturePath,
        ]);

        return back()->with(
            'success',
            'Tanggapan Anda atas penilaian berhasil dikirim.'
        );
    }

    public function show($id)
    {
        $employee = User::where('role', 'pegawai')->findOrFail($id);

        // "Atasan Penilai" yang ditugaskan hrd untuk pegawai ini
        // (pejabat/hrd), ATAU atasan dari atasan pegawai tsb, yang boleh
        // membuka & mengisi penilaiannya. Lihat canEvaluate().
        if (! $this->canEvaluate($employee)) {
            abort(403, 'Anda bukan penilai yang ditugaskan untuk pegawai ini.');
        }

        $peerFeedbacks = $employee->feedbacksReceived()
            ->with('reviewer')
            ->latest()
            ->get();

        // Penilaian yang SUDAH diberikan penilai lain untuk pegawai ini
        // (mis. penilai langsung), supaya atasan dari atasan bisa melihat
        // nilai per-poin & total score-nya sebelum ikut menilai sendiri.
        // Dibatasi ke tahun berjalan supaya tidak tercampur dengan
        // penilaian tahun-tahun sebelumnya.
        $otherEvaluations = $employee->evaluations()
            ->where('official_id', '!=', Auth::id())
            ->tahunAktif()
            ->with('official')
            ->latest()
            ->get();

        $myEvaluation = $employee->evaluations()
            ->where('official_id', Auth::id())
            ->tahunAktif()
            ->latest()
            ->first();

        // Begitu atasan pejabat sudah mengirim tanggapan untuk pegawai ini
        // PADA TAHUN BERJALAN, nilai yang sudah diberikan pejabat untuk
        // tahun ini terkunci dan tidak bisa diedit lagi. Tanggapan atasan
        // dari tahun-tahun sebelumnya tidak ikut mengunci penilaian tahun
        // ini.
        $evaluationLocked = SupervisorFeedback::where('employee_id', $employee->id)
            ->tahunAktif()
            ->exists();

        // Syarat sebelum penilai boleh mulai menilai: korelasi sudah
        // memberi tanggapan DAN hrd sudah mengisi kehadiran. Dipakai untuk
        // menyembunyikan/menonaktifkan form penilaian di view kalau belum
        // terpenuhi - lihat juga evaluate() yang menjaga di sisi server.
        $readyToEvaluate = $employee->siapDinilaiPenilai();

        return view('official.evaluate', compact(
            'employee',
            'peerFeedbacks',
            'otherEvaluations',
            'myEvaluation',
            'evaluationLocked',
            'readyToEvaluate'
        ));
    }

    public function updateEmployee(Request $request, $id)
    {
        $employee = User::where('role', 'pegawai')->findOrFail($id);

        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'username'   => 'required|string|max:100|alpha_dash|unique:users,username,' . $employee->id,
            'nik'        => 'required|string|max:50|unique:users,nik,' . $employee->id,
            'unit_kerja' => 'nullable|string|max:255',
            'jabatan'    => 'nullable|string|max:255',
        ]);

        $employee->name       = $validated['name'];
        $employee->username   = $validated['username'];
        $employee->unit_kerja = $validated['unit_kerja'] ?? null;
        $employee->jabatan    = $validated['jabatan'] ?? null;
        // Ikut sinkronkan email placeholder kalau username berubah.
        $employee->email      = $validated['username'] . '@pegawai.local';

        // Kalau NIK diubah, password ikut diperbarui (password = NIK).
        if ($validated['nik'] !== $employee->nik) {
            $employee->password = bcrypt($validated['nik']);
        }

        $employee->nik = $validated['nik'];

        $employee->save();

        return back()->with('success', 'Data pegawai berhasil diperbarui.');
    }

    public function evaluate(Request $request, $id)
    {
        $employee = User::where('role', 'pegawai')->findOrFail($id);

        // "Atasan Penilai" yang ditugaskan hrd untuk pegawai ini, ATAU
        // atasan dari atasan pegawai tsb, yang boleh mengisi penilaiannya.
        if (! $this->canEvaluate($employee)) {
            abort(403, 'Anda bukan penilai yang ditugaskan untuk pegawai ini.');
        }

        // Cegah double-submit: satu pejabat hanya boleh menilai satu pegawai sekali
        // PER TAHUN (constraint unique di tabel evaluations mencakup kolom
        // `tahun`). Cek di sini SEBELUM menyimpan apa pun, supaya tidak ada
        // file tanda tangan yang nyangkut di storage dan tidak muncul error
        // mentah kalau tombol Simpan tidak sengaja diklik dua kali. Tahun
        // depan, pejabat yang sama tetap boleh menilai pegawai yang sama lagi
        // karena baris tahun ini tidak dihapus/ditimpa.
        $tahunIni = now()->year;

        $alreadyEvaluated = $employee->evaluations()
            ->where('official_id', Auth::id())
            ->where('tahun', $tahunIni)
            ->exists();

        if ($alreadyEvaluated) {
            return back()->with('success', 'Pegawai ini sudah pernah Anda nilai untuk tahun ' . $tahunIni . '.');
        }

        // Penilai hanya boleh menilai pegawai kalau korelasi sudah memberi
        // tanggapan DAN hrd sudah mengisi data kehadiran. Lihat
        // User::siapDinilaiPenilai().
        if (! $employee->siapDinilaiPenilai()) {
            return back()->with(
                'error',
                'Pegawai ini belum bisa dinilai. Pastikan korelasi sudah memberikan tanggapan dan HRD sudah mengisi data kehadiran terlebih dahulu.'
            );
        }

        [$validated, $recommendationValue, $kenaikanGajiAmount, $promosiKeterangan, $demosiKeterangan, $mutasiKeterangan, $error] = $this->validateEvaluationInput($request, $employee);

        if ($error) {
            return $error;
        }

        // Pastikan data yang dikirim benar-benar gambar base64 dari canvas
        if (! preg_match('/^data:image\/png;base64,/', $validated['signature'])) {
            return back()->withErrors(['signature' => 'Format tanda tangan tidak valid.'])->withInput();
        }

        $imageContent = base64_decode(substr($validated['signature'], strpos($validated['signature'], ',') + 1));
        $signaturePath = "signatures/evaluation_{$employee->id}_" . Auth::id() . '_' . time() . '.png';
        Storage::disk('public')->put($signaturePath, $imageContent);

        $score = Evaluation::calculateScore($validated);

        // "teguran" hanya diisi kalau pejabat memilih "Pernah" pada
        // pertanyaan apakah pegawai ini pernah ditegur. Kalau "Pernah"
        // dipilih tapi keterangan dikosongkan, tetap simpan penanda
        // "Pernah" supaya informasinya tidak hilang.
        $teguranPernah = $validated['teguran_pernah'] ?? 'tidak';
        $teguranValue = $teguranPernah === 'ya'
            ? (trim((string) ($validated['teguran'] ?? '')) !== '' ? trim($validated['teguran']) : 'Pernah')
            : null;

        try {
            Evaluation::create([
                'employee_id'                 => $employee->id,
                'official_id'                 => Auth::id(),
                'tahun'                       => $tahunIni,
                'pengetahuan_kerja'           => $validated['pengetahuan_kerja'],
                'penguasaan_peralatan'        => $validated['penguasaan_peralatan'],
                'volume_kerja'                => $validated['volume_kerja'],
                'mutu_tanggung_jawab'         => $validated['mutu_tanggung_jawab'],
                'disiplin_dedikasi_loyalitas' => $validated['disiplin_dedikasi_loyalitas'],
                'prakarsa'                    => $validated['prakarsa'],
                'daya_serap'                  => $validated['daya_serap'],
                'kerajinan'                   => $validated['kerajinan'],
                'kerjasama'                   => $validated['kerjasama'],
                'score'                       => $score,
                'feedback'                    => $validated['feedback'],
                'teguran'                     => $teguranValue,
                'recommendation'              => $recommendationValue,
                'kenaikan_gaji_amount'        => $kenaikanGajiAmount,
                'promosi_keterangan'          => $promosiKeterangan,
                'demosi_keterangan'           => $demosiKeterangan,
                'mutasi_keterangan'           => $mutasiKeterangan,
                'signature'                   => $signaturePath,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            Storage::disk('public')->delete($signaturePath);

            if (! str_contains(strtolower($e->getMessage()), 'duplicate')) {
                throw $e;
            }

            return back()->with('success', 'Pegawai ini sudah pernah Anda nilai sebelumnya.');
        }

        // Kirim notifikasi FCM ke Atasan Penilai bahwa Tanggapan Atasan
        // untuk pegawai ini kini sudah bisa mulai diisi. Dilakukan
        // SETELAH tersimpan - notifikasi bersifat tambahan, bukan blocking.
        app(NotificationTriggerService::class)
            ->triggerSiapTanggapanAtasanPenilaiJikaPerlu($employee);

        // Beri tahu pegawai yang dinilai bahwa nilainya sudah muncul.
        app(NotificationTriggerService::class)
            ->triggerNilaiMunculPegawai($employee, $score);

        return back()->with('success', 'Penilaian berhasil disimpan. Nilai akhir: '.$score);
    }

    public function updateEvaluation(Request $request, $id)
    {
        $employee = User::where('role', 'pegawai')->findOrFail($id);

        // "Atasan Penilai" yang ditugaskan admin untuk pegawai ini, ATAU
        // atasan dari atasan pegawai tsb, yang boleh mengubah penilaiannya.
        if (! $this->canEvaluate($employee)) {
            abort(403, 'Anda bukan penilai yang ditugaskan untuk pegawai ini.');
        }

        // Dibatasi ke tahun berjalan supaya yang diedit selalu penilaian
        // tahun ini, bukan tidak sengaja menimpa baris histori tahun lalu.
        $evaluation = $employee->evaluations()
            ->where('official_id', Auth::id())
            ->tahunAktif()
            ->latest()
            ->first();

        if (! $evaluation) {
            return back()->with('error', 'Penilaian belum pernah dibuat untuk pegawai ini pada tahun ini.');
        }

        // Begitu atasan pejabat sudah menanggapi UNTUK TAHUN INI, nilai
        // tidak boleh diubah lagi.
        $locked = SupervisorFeedback::where('employee_id', $employee->id)
            ->tahunAktif()
            ->exists();

        if ($locked) {
            return back()->with(
                'error',
                'Nilai tidak dapat diubah karena atasan pejabat sudah mengirim tanggapan.'
            );
        }

        [$validated, $recommendationValue, $kenaikanGajiAmount, $promosiKeterangan, $demosiKeterangan, $mutasiKeterangan, $error] = $this->validateEvaluationInput($request, $employee, signatureRequired: false);

        if ($error) {
            return $error;
        }

        $signaturePath = $evaluation->signature;

        // Tanda tangan baru bersifat opsional saat mengedit; kalau pejabat
        // menggambar ulang, ganti file lama dengan yang baru.
        if (! empty($validated['signature'])) {
            if (! preg_match('/^data:image\/png;base64,/', $validated['signature'])) {
                return back()->withErrors(['signature' => 'Format tanda tangan tidak valid.'])->withInput();
            }

            $imageContent = base64_decode(substr($validated['signature'], strpos($validated['signature'], ',') + 1));
            $newSignaturePath = "signatures/evaluation_{$employee->id}_" . Auth::id() . '_' . time() . '.png';
            Storage::disk('public')->put($newSignaturePath, $imageContent);

            if ($signaturePath) {
                Storage::disk('public')->delete($signaturePath);
            }

            $signaturePath = $newSignaturePath;
        }

        $score = Evaluation::calculateScore($validated);

        // "teguran" hanya diisi kalau pejabat memilih "Pernah" pada
        // pertanyaan apakah pegawai ini pernah ditegur. Kalau "Pernah"
        // dipilih tapi keterangan dikosongkan, tetap simpan penanda
        // "Pernah" supaya informasinya tidak hilang.
        $teguranPernah = $validated['teguran_pernah'] ?? 'tidak';
        $teguranValue = $teguranPernah === 'ya'
            ? (trim((string) ($validated['teguran'] ?? '')) !== '' ? trim($validated['teguran']) : 'Pernah')
            : null;

        $evaluation->update([
            'pengetahuan_kerja'           => $validated['pengetahuan_kerja'],
            'penguasaan_peralatan'        => $validated['penguasaan_peralatan'],
            'volume_kerja'                => $validated['volume_kerja'],
            'mutu_tanggung_jawab'         => $validated['mutu_tanggung_jawab'],
            'disiplin_dedikasi_loyalitas' => $validated['disiplin_dedikasi_loyalitas'],
            'prakarsa'                    => $validated['prakarsa'],
            'daya_serap'                  => $validated['daya_serap'],
            'kerajinan'                   => $validated['kerajinan'],
            'kerjasama'                   => $validated['kerjasama'],
            'score'                       => $score,
            'feedback'                    => $validated['feedback'],
            'teguran'                     => $teguranValue,
            'recommendation'              => $recommendationValue,
            'kenaikan_gaji_amount'        => $kenaikanGajiAmount,
            'promosi_keterangan'          => $promosiKeterangan,
            'demosi_keterangan'           => $demosiKeterangan,
            'mutasi_keterangan'           => $mutasiKeterangan,
            'signature'                   => $signaturePath,
        ]);

        return back()->with('success', 'Penilaian berhasil diperbarui. Nilai akhir: '.$score);
    }

    /**
     * Halaman "Tanggapan Atasan" untuk pegawai, khusus Atasan Penilai
     * (users.atasan_pejabat_id). Sengaja dibuat method sendiri di sini
     * (bukan dilempar ke SupervisorController/route 'supervisor.*'),
     * supaya pejabat cukup lewat /pejabat/dashboard tanpa harus tahu ada
     * dashboard lain (/atasan/dashboard).
     */
    public function showTanggapanPegawai($id)
    {
        $employee = User::where('role', 'pegawai')->findOrFail($id);

        if ((int) $employee->atasan_pejabat_id !== (int) Auth::id()) {
            abort(403, 'Anda bukan Atasan Penilai yang ditugaskan untuk pegawai ini.');
        }

        $feedbacks = Feedback::where('employee_id', $id)->with('reviewer')->get();

        // Dibatasi ke tahun berjalan: penilaian & tanggapan yang relevan
        // untuk siklus saat ini, bukan histori tahun-tahun sebelumnya.
        $evaluation = Evaluation::where('employee_id', $id)->tahunAktif()->with('official')->first();

        $supervisorFeedback = SupervisorFeedback::where('employee_id', $id)
            ->where('supervisor_id', Auth::id())
            ->tahunAktif()
            ->first();

        // Atasan penilai baru boleh memberi tanggapan setelah penilai
        // memberikan penilaian (Evaluation) untuk pegawai ini.
        $readyForTanggapan = (bool) $evaluation;

        // Begitu pegawai yang dinilai sudah tanda tangan penilaiannya
        // sendiri, form Tanggapan Atasan dikunci (lihat juga pengecekan
        // server-side di giveTanggapanPegawai()).
        $tanggapanLocked = (bool) ($evaluation?->employee_signature);

        return view('official.tanggapan-pegawai', compact(
            'employee',
            'feedbacks',
            'evaluation',
            'supervisorFeedback',
            'readyForTanggapan',
            'tanggapanLocked'
        ));
    }

    /**
     * AJAX polling status untuk official.tanggapan-pegawai - lihat pola &
     * alasan di EmployeeController::statusVersion(). Dilingkupi ke satu
     * pegawai ($id) yang sedang dibuka, memantau:
     * 1) Evaluation-nya - supaya begitu Penilai baru saja memberi nilai,
     *    halaman ini (biasanya dibuka Atasan Penilai di HP, sambil
     *    menunggu) langsung ke-unlock tanpa perlu refresh manual,
     * 2) employee_signature di Evaluation itu - supaya kalau pegawai
     *    baru saja tanda tangan, form Tanggapan Atasan langsung terkunci,
     * 3) SupervisorFeedback miliknya sendiri - kalau tanggapan sudah
     *    tersimpan dari perangkat/tab lain.
     */
    public function tanggapanPegawaiStatusVersion($id)
    {
        $employee = User::where('role', 'pegawai')->findOrFail($id);

        if ((int) $employee->atasan_pejabat_id !== (int) Auth::id()) {
            abort(403);
        }

        $evaluation = Evaluation::where('employee_id', $id)->tahunAktif()->first();

        $version = md5(
            optional($evaluation)->updated_at . '|'
            . optional($evaluation)->employee_signature
        );

        return response()->json(['version' => $version]);
    }

    /**
     * Simpan Tanggapan Atasan dari Atasan Penilai untuk pegawai. Tidak ada
     * jalur nilai di sini sama sekali - cuma feedback + tanda tangan.
     */
    public function giveTanggapanPegawai(Request $request, $id)
    {
        $employee = User::where('role', 'pegawai')->findOrFail($id);

        if ((int) $employee->atasan_pejabat_id !== (int) Auth::id()) {
            abort(403, 'Anda bukan Atasan Penilai yang ditugaskan untuk pegawai ini.');
        }

        // Tahun berjalan dipakai sebagai acuan periode untuk seluruh
        // pengecekan & penyimpanan di bawah, supaya tanggapan atasan tetap
        // per tahun (lihat updateOrCreate di bawah).
        $tahunIni = now()->year;

        // Atasan penilai baru boleh memberi tanggapan setelah penilai
        // memberikan penilaian (Evaluation) untuk pegawai ini PADA TAHUN
        // BERJALAN - bukan penilaian tahun-tahun sebelumnya.
        $evaluationTahunIni = Evaluation::where('employee_id', $id)
            ->where('tahun', $tahunIni)
            ->first();

        if (! $evaluationTahunIni) {
            return back()->with(
                'error',
                'Tanggapan belum bisa diberikan karena penilai belum memberikan penilaian untuk pegawai ini pada tahun ' . $tahunIni . '.'
            );
        }

        // Begitu pegawai yang dinilai sudah tanda tangan (menanggapi &
        // menandatangani penilaiannya sendiri, Evaluation::employee_signature,
        // lihat EmployeeController::respondEvaluation), Tanggapan Atasan
        // untuk tahun ini terkunci dan tidak bisa diisi/diubah lagi - baik
        // untuk mengisi pertama kali maupun mengedit tanggapan yang sudah
        // ada. Sama pola-nya seperti giveTanggapanPejabat().
        if ($evaluationTahunIni->employee_signature) {
            return back()->with(
                'error',
                'Tanggapan tidak dapat diisi/diubah karena pegawai yang dinilai sudah menandatangani penilaiannya.'
            );
        }

        $validated = $request->validate([
            'feedback'             => 'required|string|min:10',
            'recommendation'       => 'nullable|array',
            'recommendation.*'     => 'in:' . implode(',', array_keys(Evaluation::RECOMMENDATIONS)),
            // 'integer', bukan 'numeric' - is_numeric() PHP (yang dipakai
            // rule 'numeric') tetap menganggap valid notasi ilmiah seperti
            // "1e5", jadi validasi bisa ketembus kalau client-side JS
            // dimatikan/diakali. Nominal gaji memang harus bilangan bulat.
            // Khusus Atasan Penilai (giveTanggapanPegawai/giveTanggapanPejabat):
            // TIDAK ada batas atas nominal - beda dari validasi kenaikan
            // gaji Penilai di validateEvaluationInput() yang masih dibatasi
            // max 750.000.
            'kenaikan_gaji_amount' => 'nullable|integer|min:1',
            'promosi_keterangan'   => 'nullable|string|max:255',
            'demosi_keterangan'     => 'nullable|string|max:255',
            'mutasi_keterangan'     => 'nullable|string|max:255',
            'signature'            => 'required|string',
        ]);

        $recommendations = $validated['recommendation'] ?? [];

        // Promosi dan Demosi saling bertolak belakang, tidak boleh
        // dipilih bersamaan dalam satu rekomendasi.
        if (in_array('promosi', $recommendations, true) && in_array('demosi', $recommendations, true)) {
            return back()
                ->withErrors(['recommendation' => 'Rekomendasi Promosi dan Demosi tidak bisa dipilih bersamaan.'])
                ->withInput();
        }

        // "Kontrak Dagsap ke Tetap" hanya boleh diajukan kalau status
        // kontrak pegawai ini SAAT INI memang sudah Kontrak Dagsap.
        if (in_array('kontrak_dagsap_ke_tetap', $recommendations, true) && ! $employee->isEligibleForDagsapTetap()) {
            return back()
                ->withErrors(['recommendation' => 'Rekomendasi "Kontrak Dagsap ke Tetap" tidak bisa diajukan karena status pegawai ini bukan Kontrak Dagsap (masih PHL atau Kontrak OS).'])
                ->withInput();
        }

        // Kalau rekomendasi "Kenaikan Gaji" dicentang, nominalnya wajib diisi.
        if (in_array('kenaikan_gaji', $recommendations, true) && empty($validated['kenaikan_gaji_amount'])) {
            return back()
                ->withErrors(['kenaikan_gaji_amount' => 'Nominal kenaikan gaji wajib diisi.'])
                ->withInput();
        }

        // Kalau rekomendasi "Promosi" dicentang, keterangan tujuan promosi wajib diisi.
        if (in_array('promosi', $recommendations, true) && empty(trim((string) ($validated['promosi_keterangan'] ?? '')))) {
            return back()
                ->withErrors(['promosi_keterangan' => 'Keterangan tujuan promosi wajib diisi (mis. jabatan/posisi tujuan).'])
                ->withInput();
        }

        // Kalau rekomendasi "Demosi" dicentang, keterangan tujuan demosi wajib diisi.
        if (in_array('demosi', $recommendations, true) && empty(trim((string) ($validated['demosi_keterangan'] ?? '')))) {
            return back()
                ->withErrors(['demosi_keterangan' => 'Keterangan tujuan demosi wajib diisi (mis. jabatan/posisi tujuan).'])
                ->withInput();
        }

        // Kalau rekomendasi "Mutasi" dicentang, keterangan tujuan mutasi wajib diisi.
        if (in_array('mutasi', $recommendations, true) && empty(trim((string) ($validated['mutasi_keterangan'] ?? '')))) {
            return back()
                ->withErrors(['mutasi_keterangan' => 'Keterangan tujuan mutasi wajib diisi (mis. posisi/unit kerja tujuan).'])
                ->withInput();
        }

        $recommendationValue = empty($recommendations) ? 'tidak_ada' : implode(',', $recommendations);
        $kenaikanGajiAmount = in_array('kenaikan_gaji', $recommendations, true)
            ? (int) $validated['kenaikan_gaji_amount']
            : null;
        $promosiKeterangan = in_array('promosi', $recommendations, true)
            ? trim($validated['promosi_keterangan'])
            : null;
        $demosiKeterangan = in_array('demosi', $recommendations, true)
            ? trim($validated['demosi_keterangan'])
            : null;
        $mutasiKeterangan = in_array('mutasi', $recommendations, true)
            ? trim($validated['mutasi_keterangan'])
            : null;

        if (! preg_match('/^data:image\/png;base64,/', $validated['signature'])) {
            return back()->withErrors(['signature' => 'Format tanda tangan tidak valid.'])->withInput();
        }

        $imageContent = base64_decode(substr($validated['signature'], strpos($validated['signature'], ',') + 1));
        $signaturePath = 'signatures/atasan_penilai_' . $id . '_' . Auth::id() . '_' . time() . '.png';
        Storage::disk('public')->put($signaturePath, $imageContent);

        // Diubah jadi per tahun: kalau tahun ini sudah pernah mengisi, baris
        // tahun ini yang di-update (masih boleh dikoreksi selama tahun
        // berjalan). Kalau belum ada untuk tahun ini, dibuat baris baru
        // supaya tanggapan tahun-tahun sebelumnya tetap tersimpan sebagai
        // histori dan tidak ikut ketimpa.
        $existing = SupervisorFeedback::where('employee_id', $id)
            ->where('supervisor_id', Auth::id())
            ->where('tahun', $tahunIni)
            ->first();

        if ($existing && $existing->signature) {
            Storage::disk('public')->delete($existing->signature);
        }

        SupervisorFeedback::updateOrCreate(
            [
                'employee_id'   => $id,
                'supervisor_id' => Auth::id(),
                'tahun'         => $tahunIni,
            ],
            [
                'feedback'              => $validated['feedback'],
                'recommendation'        => $recommendationValue,
                'kenaikan_gaji_amount'  => $kenaikanGajiAmount,
                'promosi_keterangan'    => $promosiKeterangan,
                'demosi_keterangan'     => $demosiKeterangan,
                'mutasi_keterangan'     => $mutasiKeterangan,
                'signature'             => $signaturePath,
            ]
        );

        // Kirim notifikasi FCM ke pegawai bahwa tanggapan & checklist
        // pertemuannya kini sudah bisa mulai diisi. Dilakukan SETELAH
        // tersimpan - notifikasi bersifat tambahan, bukan blocking.
        app(NotificationTriggerService::class)
            ->triggerSiapTanggapanChecklistPegawaiJikaPerlu($employee);

        // Kirim juga notifikasi FCM ke PENILAI (users.supervisor_id
        // pegawai ini) bahwa checklist "sudah bertemu & evaluasi"
        // miliknya sendiri kini sudah bisa mulai dicentang - sebelumnya
        // hanya pegawai yang diberi tahu, padahal Penilai juga menunggu
        // tanggapan ini sebelum bisa mencentang checklist-nya.
        app(NotificationTriggerService::class)
            ->triggerSiapChecklistPenilaiJikaPerlu($employee);

        return back()->with(
            'success',
            'Tanggapan atasan berhasil disimpan.'
        );
    }

    /**
     * Halaman "Tanggapan Atasan" untuk pejabat, khusus Atasan Penilai
     * (users.atasan_penilai_pejabat_id). Sama pola-nya seperti
     * showTanggapanPegawai() di atas, tapi untuk pejabat->pejabat: yang
     * dinilai di sini adalah pejabat (OfficialEvaluation), bukan pegawai.
     */
    public function showTanggapanPejabat($id)
    {
        $official = User::where('role', 'pejabat')->findOrFail($id);

        if ($official->atasan_penilai_pejabat_id !== Auth::id()) {
            abort(403, 'Anda bukan Atasan Penilai yang ditugaskan untuk pejabat ini.');
        }

        // Dibatasi ke tahun berjalan: penilaian & tanggapan yang relevan
        // untuk siklus saat ini, bukan histori tahun-tahun sebelumnya.
        $officialEvaluation = OfficialEvaluation::where('official_id', $id)
            ->tahunAktif()
            ->with('supervisor')
            ->first();

        $officialSupervisorFeedback = OfficialSupervisorFeedback::where('official_id', $id)
            ->where('supervisor_id', Auth::id())
            ->tahunAktif()
            ->first();

        // Atasan penilai baru boleh memberi tanggapan setelah penilai
        // memberikan penilaian (OfficialEvaluation) untuk pejabat ini.
        $readyForTanggapan = (bool) $officialEvaluation;

        // Begitu pejabat yang dinilai sudah tanda tangan penilaiannya
        // sendiri, form Tanggapan Atasan dikunci (lihat juga pengecekan
        // server-side di giveTanggapanPejabat()).
        $tanggapanLocked = (bool) ($officialEvaluation?->employee_signature);

        return view('official.tanggapan-pejabat', compact(
            'official',
            'officialEvaluation',
            'officialSupervisorFeedback',
            'readyForTanggapan',
            'tanggapanLocked'
        ));
    }

    /**
     * AJAX polling status untuk official.tanggapan-pejabat - versi
     * pejabat->pejabat dari tanggapanPegawaiStatusVersion() (lihat catatan
     * lengkapnya di sana untuk alasannya).
     */
    public function tanggapanPejabatStatusVersion($id)
    {
        $official = User::where('role', 'pejabat')->findOrFail($id);

        if ($official->atasan_penilai_pejabat_id !== Auth::id()) {
            abort(403);
        }

        $officialEvaluation = OfficialEvaluation::where('official_id', $id)->tahunAktif()->first();

        $version = md5(
            optional($officialEvaluation)->updated_at . '|'
            . optional($officialEvaluation)->employee_signature
        );

        return response()->json(['version' => $version]);
    }

    /**
     * Simpan Tanggapan Atasan dari Atasan Penilai untuk pejabat. Sama
     * seperti giveTanggapanPegawai(): tidak ada jalur nilai di sini,
     * cuma feedback + rekomendasi + tanda tangan.
     */
    public function giveTanggapanPejabat(Request $request, $id)
    {
        $official = User::where('role', 'pejabat')->findOrFail($id);

        if ($official->atasan_penilai_pejabat_id !== Auth::id()) {
            abort(403, 'Anda bukan Atasan Penilai yang ditugaskan untuk pejabat ini.');
        }

        // Tahun berjalan dipakai sebagai acuan periode untuk seluruh
        // pengecekan & penyimpanan di bawah, supaya tanggapan atasan tetap
        // per tahun (lihat updateOrCreate di bawah).
        $tahunIni = now()->year;

        // Atasan penilai baru boleh memberi tanggapan setelah penilai
        // memberikan penilaian untuk pejabat ini PADA TAHUN BERJALAN.
        $officialEvaluationTahunIni = OfficialEvaluation::where('official_id', $id)
            ->where('tahun', $tahunIni)
            ->first();

        if (! $officialEvaluationTahunIni) {
            return back()->with(
                'error',
                'Tanggapan belum bisa diberikan karena penilai belum memberikan penilaian untuk pejabat ini pada tahun ' . $tahunIni . '.'
            );
        }

        // Begitu pejabat yang dinilai sudah tanda tangan (menanggapi &
        // menandatangani penilaiannya sendiri, OfficialEvaluation::
        // employee_signature), Tanggapan Atasan untuk tahun ini terkunci
        // dan tidak bisa diisi/diubah lagi - baik untuk mengisi pertama
        // kali maupun mengedit tanggapan yang sudah ada.
        if ($officialEvaluationTahunIni->employee_signature) {
            return back()->with(
                'error',
                'Tanggapan tidak dapat diisi/diubah karena pejabat yang dinilai sudah menandatangani penilaiannya.'
            );
        }

        $validated = $request->validate([
            'feedback'             => 'required|string|min:10',
            'recommendation'       => 'nullable|array',
            'recommendation.*'     => 'in:' . implode(',', array_keys(OfficialEvaluation::RECOMMENDATIONS)),
            // Lihat catatan 'integer' vs 'numeric' di validasi serupa
            // pada method evaluate() di controller ini.
            // Khusus Atasan Penilai (giveTanggapanPejabat): TIDAK ada
            // batas atas nominal - lihat catatan serupa di
            // giveTanggapanPegawai() di atas.
            'kenaikan_gaji_amount' => 'nullable|integer|min:1',
            'promosi_keterangan'   => 'nullable|string|max:255',
            'demosi_keterangan'     => 'nullable|string|max:255',
            'mutasi_keterangan'     => 'nullable|string|max:255',
            'signature'            => 'required|string',
        ]);

        $recommendations = $validated['recommendation'] ?? [];

        // Promosi dan Demosi saling bertolak belakang, tidak boleh
        // dipilih bersamaan dalam satu rekomendasi.
        if (in_array('promosi', $recommendations, true) && in_array('demosi', $recommendations, true)) {
            return back()
                ->withErrors(['recommendation' => 'Rekomendasi Promosi dan Demosi tidak bisa dipilih bersamaan.'])
                ->withInput();
        }

        // "Kontrak Dagsap ke Tetap" hanya boleh diajukan kalau status
        // kontrak pejabat ini SAAT INI memang sudah Kontrak Dagsap.
        if (in_array('kontrak_dagsap_ke_tetap', $recommendations, true) && ! $official->isEligibleForDagsapTetap()) {
            return back()
                ->withErrors(['recommendation' => 'Rekomendasi "Kontrak Dagsap ke Tetap" tidak bisa diajukan karena status pejabat ini bukan Kontrak Dagsap (masih PHL atau Kontrak OS).'])
                ->withInput();
        }

        // Kalau rekomendasi "Kenaikan Gaji" dicentang, nominalnya wajib diisi.
        if (in_array('kenaikan_gaji', $recommendations, true) && empty($validated['kenaikan_gaji_amount'])) {
            return back()
                ->withErrors(['kenaikan_gaji_amount' => 'Nominal kenaikan gaji wajib diisi.'])
                ->withInput();
        }

        // Kalau rekomendasi "Promosi" dicentang, keterangan tujuan promosi wajib diisi.
        if (in_array('promosi', $recommendations, true) && empty(trim((string) ($validated['promosi_keterangan'] ?? '')))) {
            return back()
                ->withErrors(['promosi_keterangan' => 'Keterangan tujuan promosi wajib diisi (mis. jabatan/posisi tujuan).'])
                ->withInput();
        }

        // Kalau rekomendasi "Demosi" dicentang, keterangan tujuan demosi wajib diisi.
        if (in_array('demosi', $recommendations, true) && empty(trim((string) ($validated['demosi_keterangan'] ?? '')))) {
            return back()
                ->withErrors(['demosi_keterangan' => 'Keterangan tujuan demosi wajib diisi (mis. jabatan/posisi tujuan).'])
                ->withInput();
        }

        // Kalau rekomendasi "Mutasi" dicentang, keterangan tujuan mutasi wajib diisi.
        if (in_array('mutasi', $recommendations, true) && empty(trim((string) ($validated['mutasi_keterangan'] ?? '')))) {
            return back()
                ->withErrors(['mutasi_keterangan' => 'Keterangan tujuan mutasi wajib diisi (mis. posisi/unit kerja tujuan).'])
                ->withInput();
        }

        $recommendationValue = empty($recommendations) ? 'tidak_ada' : implode(',', $recommendations);
        $kenaikanGajiAmount = in_array('kenaikan_gaji', $recommendations, true)
            ? (int) $validated['kenaikan_gaji_amount']
            : null;
        $promosiKeterangan = in_array('promosi', $recommendations, true)
            ? trim($validated['promosi_keterangan'])
            : null;
        $demosiKeterangan = in_array('demosi', $recommendations, true)
            ? trim($validated['demosi_keterangan'])
            : null;
        $mutasiKeterangan = in_array('mutasi', $recommendations, true)
            ? trim($validated['mutasi_keterangan'])
            : null;

        if (! preg_match('/^data:image\/png;base64,/', $validated['signature'])) {
            return back()->withErrors(['signature' => 'Format tanda tangan tidak valid.'])->withInput();
        }

        $imageContent = base64_decode(substr($validated['signature'], strpos($validated['signature'], ',') + 1));
        $signaturePath = 'signatures/atasan_penilai_pejabat_' . $id . '_' . Auth::id() . '_' . time() . '.png';
        Storage::disk('public')->put($signaturePath, $imageContent);

        // Diubah jadi per tahun: kalau tahun ini sudah pernah mengisi, baris
        // tahun ini yang di-update (masih boleh dikoreksi selama tahun
        // berjalan). Kalau belum ada untuk tahun ini, dibuat baris baru
        // supaya tanggapan tahun-tahun sebelumnya tetap tersimpan sebagai
        // histori dan tidak ikut ketimpa.
        $existing = OfficialSupervisorFeedback::where('official_id', $id)
            ->where('supervisor_id', Auth::id())
            ->where('tahun', $tahunIni)
            ->first();

        if ($existing && $existing->signature) {
            Storage::disk('public')->delete($existing->signature);
        }

        OfficialSupervisorFeedback::updateOrCreate(
            [
                'official_id'   => $id,
                'supervisor_id' => Auth::id(),
                'tahun'         => $tahunIni,
            ],
            [
                'feedback'              => $validated['feedback'],
                'recommendation'        => $recommendationValue,
                'kenaikan_gaji_amount'  => $kenaikanGajiAmount,
                'promosi_keterangan'    => $promosiKeterangan,
                'demosi_keterangan'     => $demosiKeterangan,
                'mutasi_keterangan'     => $mutasiKeterangan,
                'signature'             => $signaturePath,
            ]
        );

        // Kirim notifikasi FCM ke pejabat bahwa tanggapan & checklist
        // pertemuannya kini sudah bisa mulai diisi. Dilakukan SETELAH
        // tersimpan - notifikasi bersifat tambahan, bukan blocking.
        app(NotificationTriggerService::class)
            ->triggerSiapTanggapanChecklistPejabatJikaPerlu($official);

        // Kirim juga notifikasi FCM ke ATASAN (users.supervisor_id
        // pejabat ini) bahwa checklist miliknya sendiri kini sudah bisa
        // mulai dicentang - versi pejabat dari
        // triggerSiapChecklistPenilaiJikaPerlu() di atas.
        app(NotificationTriggerService::class)
            ->triggerSiapChecklistAtasanPejabatJikaPerlu($official);

        return back()->with(
            'success',
            'Tanggapan atasan berhasil disimpan.'
        );
    }

    /**
     * Toggle checklist "sudah bertemu & evaluasi" milik pejabat yang
     * sedang login, untuk siklus penilaian PEJABAT (OfficialEvaluation) -
     * versi pejabat dari EmployeeController::toggleChecklistPertemuan().
     * Boleh dibatalkan kapan saja, TAPI baru boleh MULAI DICENTANG
     * setelah Atasan Penilai (OfficialSupervisorFeedback) sudah mengisi
     * tanggapannya - lihat User::checklistPertemuanPejabatBolehDiisi().
     * Setiap kali DICENTANG WAJIB disertai selfie langsung dari kamera
     * perangkat.
     */
    public function toggleChecklistPertemuanSaya(Request $request)
    {
        $user = User::findOrFail(Auth::id());

        // Tambahan: begitu HRD sudah menandatangani penilaian pejabat ini
        // (OfficialEvaluation::hrd_signature - lihat
        // HrdController::signAsHrdOfficial()), checklist pertemuan
        // (termasuk selfie/bukti-nya) TERKUNCI - sama pola-nya seperti
        // EmployeeController::toggleChecklistPertemuan().
        if ($user->hrdSudahMenandatanganiPenilaianPejabat()) {
            return back()->with(
                'error',
                'Checklist pertemuan & evaluasi tidak bisa diubah lagi karena penilaian ini sudah ditanda-tangani HRD.'
            );
        }

        // Cek tahun-aware (bukan cuma kolom _at) - lihat catatan di
        // EmployeeController::toggleChecklistPertemuan() &
        // User::pejabatSudahKonfirmasiPertemuan().
        if ($user->pejabatSudahKonfirmasiPertemuan()) {
            $this->deleteChecklistEvidence($user->pejabat_konfirmasi_pertemuan_selfie);

            $user->update([
                'pejabat_konfirmasi_pertemuan_at' => null,
                'pejabat_konfirmasi_pertemuan_selfie' => null,
                'pejabat_konfirmasi_pertemuan_evidence_type' => null,
                'pejabat_konfirmasi_pertemuan_metode' => null,
                'pejabat_konfirmasi_pertemuan_tahun' => null,
            ]);

            return back()->with('success', 'Checklist pertemuan & evaluasi dibatalkan.');
        }

        if (! $user->checklistPertemuanPejabatBolehDiisi()) {
            return back()->with(
                'error',
                'Checklist pertemuan & evaluasi belum bisa dicentang. Tanggapan dari Atasan Penilai belum diselesaikan.'
            );
        }

        [$selfiePath, $evidenceType, $meetingMethod] = $this->resolveChecklistEvidence($request, 'pejabat', $user->id);

        // Hapus bukti lama (attempt sebelumnya di tahun yang sama, atau
        // sisa checklist tahun lalu) sebelum ditimpa.
        $this->deleteChecklistEvidence($user->pejabat_konfirmasi_pertemuan_selfie);

        $user->update([
            'pejabat_konfirmasi_pertemuan_at' => now(),
            'pejabat_konfirmasi_pertemuan_selfie' => $selfiePath,
            'pejabat_konfirmasi_pertemuan_evidence_type' => $evidenceType,
            'pejabat_konfirmasi_pertemuan_metode' => $meetingMethod,
            'pejabat_konfirmasi_pertemuan_tahun' => now()->year,
        ]);

        // Kalau checklist ATASAN untuk pejabat ini sudah lebih dulu
        // lengkap, checklist PEJABAT barusan ini yang melengkapi syarat
        // - beri tahu HRD bahwa penilaian ini sudah siap ditandatangani.
        app(NotificationTriggerService::class)
            ->triggerSiapTandaTanganHrdPejabatJikaPerlu($user);

        return back()->with('success', 'Checklist pertemuan & evaluasi berhasil dicentang.');
    }

    /**
     * Cek apakah pejabat yang sedang login boleh menilai $employee.
     *
     * Boleh menilai kalau:
     * 1) pejabat ini adalah "Atasan Penilai" langsung yang ditugaskan hrd
     *    untuk pegawai tsb (users.supervisor_id), ATAU
     * 2) pejabat ini adalah atasan dari "Atasan Penilai" langsung pegawai
     *    tsb (atasan dari atasan) — karena keduanya berada dalam satu
     *    unit yang sama. Contoh: udin dinilai ravi, dan dhabit selaku
     *    atasan ravi ikut boleh menilai udin.
     */
    /**
     * Toggle checklist "sudah bertemu & evaluasi" milik PENILAI untuk
     * pegawai ini. Sengaja HANYA untuk Penilai langsung yang ditugaskan
     * HRD (users.supervisor_id pegawai ini), BUKAN canEvaluate() secara
     * umum - Atasan Penilai (yang lolos lewat "atasan dari atasan" di
     * canEvaluate()) checklist-nya adalah tanggapan atasan sendiri, bukan
     * checklist pertemuan Penilai. Independen dari evaluate()/
     * updateEvaluation() di atas, bisa dicentang/dibatalkan kapan saja.
     * Lihat User::penilaiSudahKonfirmasiPertemuan().
     *
     * Boleh dibatalkan kapan saja, TAPI baru boleh MULAI DICENTANG
     * setelah Atasan Penilai (SupervisorFeedback) sudah mengisi
     * tanggapannya untuk pegawai ini - lihat
     * User::checklistPertemuanPenilaiBolehDiisi(). Setiap kali DICENTANG
     * WAJIB disertai selfie langsung dari kamera perangkat.
     */
    public function toggleChecklistPertemuanPegawai(Request $request, $id)
    {
        $employee = User::where('role', 'pegawai')->findOrFail($id);

        if ((int) $employee->supervisor_id !== Auth::id()) {
            abort(403, 'Anda bukan Penilai yang ditugaskan untuk pegawai ini.');
        }

        // Tambahan: sama seperti EmployeeController::toggleChecklistPertemuan()
        // - checklist milik PENILAI ini juga terkunci begitu HRD sudah
        // menandatangani penilaian pegawai tsb.
        if ($employee->hrdSudahMenandatanganiPenilaian()) {
            return back()->with(
                'error',
                'Checklist pertemuan & evaluasi tidak bisa diubah lagi karena penilaian ini sudah ditanda-tangani HRD.'
            );
        }

        if ($employee->penilaiSudahKonfirmasiPertemuan()) {
            $this->deleteChecklistEvidence($employee->penilai_konfirmasi_pertemuan_selfie);

            $employee->update([
                'penilai_konfirmasi_pertemuan_at' => null,
                'penilai_konfirmasi_pertemuan_selfie' => null,
                'penilai_konfirmasi_pertemuan_evidence_type' => null,
                'penilai_konfirmasi_pertemuan_metode' => null,
                'penilai_konfirmasi_pertemuan_tahun' => null,
            ]);

            return back()->with('success', 'Checklist pertemuan & evaluasi dibatalkan.');
        }

        if (! $employee->checklistPertemuanPenilaiBolehDiisi()) {
            return back()->with(
                'error',
                'Checklist pertemuan & evaluasi belum bisa dicentang. Tanggapan dari Atasan Penilai untuk pegawai ini belum diselesaikan.'
            );
        }

        [$selfiePath, $evidenceType, $meetingMethod] = $this->resolveChecklistEvidence($request, 'penilai', $employee->id);

        $this->deleteChecklistEvidence($employee->penilai_konfirmasi_pertemuan_selfie);

        $employee->update([
            'penilai_konfirmasi_pertemuan_at' => now(),
            'penilai_konfirmasi_pertemuan_selfie' => $selfiePath,
            'penilai_konfirmasi_pertemuan_evidence_type' => $evidenceType,
            'penilai_konfirmasi_pertemuan_metode' => $meetingMethod,
            'penilai_konfirmasi_pertemuan_tahun' => now()->year,
        ]);

        // Kalau checklist PEGAWAI sudah lebih dulu lengkap, checklist
        // PENILAI barusan ini yang melengkapi syarat - beri tahu HRD.
        app(NotificationTriggerService::class)
            ->triggerSiapTandaTanganHrdJikaPerlu($employee);

        return back()->with('success', 'Checklist pertemuan & evaluasi berhasil dicentang.');
    }

    private function canEvaluate(User $employee): bool
    {
        if ($employee->supervisor_id === Auth::id()) {
            return true;
        }

        // Atasan Penilai (users.atasan_pejabat_id) HANYA berhak memberi
        // Tanggapan Atasan (SupervisorFeedback, lihat
        // SupervisorController::feedback()), tidak pernah boleh ikut
        // memberi Penilaian Kinerja (Evaluation) untuk pegawai yang sama.
        // Cek ini HARUS di atas pengecekan "atasan dari atasan" di bawah,
        // supaya walau pejabat ini kebetulan juga atasan dari Penilai
        // langsung pegawai tsb, dia tetap tidak lolos kalau memang
        // ditugaskan sebagai Atasan Penilai pegawai ini.
        if ($employee->atasan_pejabat_id === Auth::id()) {
            return false;
        }

        return $employee->supervisor && $employee->supervisor->supervisor_id === Auth::id();
    }

    /**
     * Validasi input form penilaian yang dipakai bersama oleh evaluate() dan
     * updateEvaluation(). Mengembalikan [validated, recommendationValue, kenaikanGajiAmount, errorResponse].
     */
    private function validateEvaluationInput(Request $request, User $employee, bool $signatureRequired = true): array
    {
        $validated = $request->validate([
            'pengetahuan_kerja'           => 'required|numeric|min:0|max:100',
            'penguasaan_peralatan'        => 'required|numeric|min:0|max:100',
            'volume_kerja'                => 'required|numeric|min:0|max:100',
            'mutu_tanggung_jawab'         => 'required|numeric|min:0|max:100',
            'disiplin_dedikasi_loyalitas' => 'required|numeric|min:0|max:100',
            'prakarsa'                    => 'required|numeric|min:0|max:100',
            'daya_serap'                  => 'required|numeric|min:0|max:100',
            'kerajinan'                   => 'required|numeric|min:0|max:100',
            'kerjasama'                   => 'required|numeric|min:0|max:100',
            'feedback'                    => 'required|string|min:10',
            'teguran_pernah'              => 'nullable|in:tidak,ya',
            'teguran'                     => 'nullable|string|max:1000',
            'recommendation'              => 'nullable|array',
            'recommendation.*'            => 'in:' . implode(',', array_keys(Evaluation::RECOMMENDATIONS)),
            // Lihat catatan 'integer' vs 'numeric' di validasi serupa
            // pada method evaluate() di controller ini.
            'kenaikan_gaji_amount'        => 'nullable|integer|min:1',
            'promosi_keterangan'          => 'nullable|string|max:255',
            'demosi_keterangan'           => 'nullable|string|max:255',
            'mutasi_keterangan'           => 'nullable|string|max:255',
            'signature'                   => ($signatureRequired ? 'required' : 'nullable') . '|string',
        ]);

        $recommendations = $validated['recommendation'] ?? [];

        // Promosi dan Demosi saling bertolak belakang, tidak boleh
        // dipilih bersamaan dalam satu rekomendasi.
        if (in_array('promosi', $recommendations, true) && in_array('demosi', $recommendations, true)) {
            $error = back()
                ->withErrors(['recommendation' => 'Rekomendasi Promosi dan Demosi tidak bisa dipilih bersamaan.'])
                ->withInput();

            return [$validated, null, null, null, null, null, $error];
        }

        // "Kontrak Dagsap ke Tetap" hanya boleh diajukan kalau status
        // kontrak pegawai ini SAAT INI memang sudah Kontrak Dagsap.
        // Kalau masih PHL atau Kontrak OS, tolak (lihat juga
        // User::isEligibleForDagsapTetap() dan peringatan di
        // partials.recommendation-fields yang menonaktifkan checkbox-nya).
        if (in_array('kontrak_dagsap_ke_tetap', $recommendations, true) && ! $employee->isEligibleForDagsapTetap()) {
            $error = back()
                ->withErrors(['recommendation' => 'Rekomendasi "Kontrak Dagsap ke Tetap" tidak bisa diajukan karena status pegawai ini bukan Kontrak Dagsap (masih PHL atau Kontrak OS).'])
                ->withInput();

            return [$validated, null, null, null, null, null, $error];
        }

        // Kalau rekomendasi "Kenaikan Gaji" dicentang, nominalnya wajib diisi.
        if (in_array('kenaikan_gaji', $recommendations, true) && empty($validated['kenaikan_gaji_amount'])) {
            $error = back()
                ->withErrors(['kenaikan_gaji_amount' => 'Nominal kenaikan gaji wajib diisi.'])
                ->withInput();

            return [$validated, null, null, null, null, null, $error];
        }

        // Kalau rekomendasi "Promosi" dicentang, keterangan tujuan promosi wajib diisi.
        if (in_array('promosi', $recommendations, true) && empty(trim((string) ($validated['promosi_keterangan'] ?? '')))) {
            $error = back()
                ->withErrors(['promosi_keterangan' => 'Keterangan tujuan promosi wajib diisi (mis. jabatan/posisi tujuan).'])
                ->withInput();

            return [$validated, null, null, null, null, null, $error];
        }

        // Kalau rekomendasi "Demosi" dicentang, keterangan tujuan demosi wajib diisi.
        if (in_array('demosi', $recommendations, true) && empty(trim((string) ($validated['demosi_keterangan'] ?? '')))) {
            $error = back()
                ->withErrors(['demosi_keterangan' => 'Keterangan tujuan demosi wajib diisi (mis. jabatan/posisi tujuan).'])
                ->withInput();

            return [$validated, null, null, null, null, null, $error];
        }

        // Kalau rekomendasi "Mutasi" dicentang, keterangan tujuan mutasi wajib diisi.
        if (in_array('mutasi', $recommendations, true) && empty(trim((string) ($validated['mutasi_keterangan'] ?? '')))) {
            $error = back()
                ->withErrors(['mutasi_keterangan' => 'Keterangan tujuan mutasi wajib diisi (mis. posisi/unit kerja tujuan).'])
                ->withInput();

            return [$validated, null, null, null, null, null, $error];
        }

        $recommendationValue = empty($recommendations) ? 'tidak_ada' : implode(',', $recommendations);
        $kenaikanGajiAmount = in_array('kenaikan_gaji', $recommendations, true)
            ? (int) $validated['kenaikan_gaji_amount']
            : null;
        $promosiKeterangan = in_array('promosi', $recommendations, true)
            ? trim($validated['promosi_keterangan'])
            : null;
        $demosiKeterangan = in_array('demosi', $recommendations, true)
            ? trim($validated['demosi_keterangan'])
            : null;
        $mutasiKeterangan = in_array('mutasi', $recommendations, true)
            ? trim($validated['mutasi_keterangan'])
            : null;

        return [$validated, $recommendationValue, $kenaikanGajiAmount, $promosiKeterangan, $demosiKeterangan, $mutasiKeterangan, null];
    }
}
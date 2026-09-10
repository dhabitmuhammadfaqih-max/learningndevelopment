<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Feedback;
use App\Models\Evaluation;
use App\Models\SupervisorFeedback;
use App\Services\NotificationTriggerService;
use App\Http\Controllers\Concerns\HandlesChecklistEvidence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\QueryException;

class EmployeeController extends Controller
{
    use HandlesChecklistEvidence;

    public function index()
    {
        // Kolega yang SUDAH diberi tanggapan (korelasi) oleh pegawai yang
        // sedang login - dikecualikan dari $employees supaya tidak muncul
        // lagi di daftar pilihan "Berikan Tanggapan Teman" (satu akun
        // hanya boleh memberi tanggapan sekali ke satu orang yang sama -
        // lihat juga pengecekan di feedback()).
        $alreadyGivenFeedbackIds = Feedback::where('reviewer_id', auth::id())
            ->pluck('employee_id');

        $employees = User::where('role', 'pegawai')
            ->where('id', '!=', auth::id())
            ->whereNotIn('id', $alreadyGivenFeedbackIds)
            ->orderBy('name')
            ->get();

        $employeeUnits = $employees
            ->pluck('unit_kerja')
            ->map(fn ($unit) => $unit ?: 'Tanpa Unit')
            ->unique()
            ->sort()
            ->values();

        $myFeedbacks = Feedback::where(
            'employee_id',
            auth::id()
        )->with('reviewer')->get();

        // Tanggapan yang SUDAH diberikan oleh pegawai yang sedang login
        // ke rekan kerja lain — kebalikan dari $myFeedbacks di atas.
        $myGivenFeedbacks = Feedback::where(
            'reviewer_id',
            auth::id()
        )->with('employee')->latest()->get();

        $user = auth::user();
        $myEvaluation = null;

        // Dibatasi ke tahun berjalan supaya dashboard pegawai menampilkan
        // status siklus penilaian tahun ini - penilaian tahun-tahun
        // sebelumnya tetap tersimpan sebagai histori (lihat halaman
        // riwayat/detail), tapi tidak membuat dashboard terlihat "sudah
        // dinilai" selamanya begitu tahun baru mulai.
        if ($user && method_exists($user, 'evaluations')) {
            $myEvaluation = $user
                ->evaluations()
                ->tahunAktif()
                ->with('official')
                ->latest()
                ->first();
        }

        // Tanggapan pegawai atas penilaian baru boleh diisi setelah PEJABAT
        // (di atas, $myEvaluation) DAN ATASAN PEJABAT (SupervisorFeedback)
        // sama-sama sudah menyelesaikan penilaian untuk pegawai ini, PADA
        // TAHUN BERJALAN.
        $mySupervisorFeedback = SupervisorFeedback::where(
            'employee_id',
            auth::id()
        )->tahunAktif()->latest()->first();

        $canRespondEvaluation = (bool) $myEvaluation && (bool) $mySupervisorFeedback;

        // Pegawai lain yang secara khusus ditugaskan HRD untuk dinilai oleh
        // akun pegawai yang sedang login (users.supervisor_id menunjuk ke
        // akun ini) - mekanismenya sama persis dengan "Penilai" versi
        // pejabat/hrd (lihat OfficialController::scopedEmployeesQuery() &
        // canEvaluate()), cuma di sini scope-nya dipersempit ke relasi
        // supervisor_id langsung saja karena biasanya cuma 1 akun pegawai
        // yang ditugaskan seperti ini.
        $pegawaiYangDinilai = User::where('role', 'pegawai')
            ->where('id', '!=', auth::id())
            ->where('supervisor_id', auth::id())
            ->withCount('feedbacksReceived')
            ->with(['evaluations' => function ($query) {
                $query->where('official_id', auth::id())->tahunAktif();
            }])
            ->orderBy('name')
            ->get();

        return view('employee.dashboard', compact(
            'employees',
            'employeeUnits',
            'myFeedbacks',
            'myGivenFeedbacks',
            'mySupervisorFeedback',
            'canRespondEvaluation',
            'myEvaluation',
            'pegawaiYangDinilai'
        ));
    }

    /**
     * Dipanggil berkala lewat AJAX polling dari dashboard.blade.php
     * (lihat script di bagian bawah view). Tujuannya cuma memberi tahu
     * apakah status "sudah dinilai / sudah ditanggapi" milik pegawai yang
     * sedang login berubah sejak dashboard terakhir dimuat - TIDAK
     * mengirim ulang seluruh data, supaya endpoint ini ringan dan aman
     * dipanggil tiap beberapa detik.
     *
     * Versi dihitung dari updated_at baris-baris yang relevan; kalau ada
     * satu saja yang berubah (misal pejabat baru saja memberi nilai),
     * hash-nya otomatis ikut berubah.
     */
    public function statusVersion()
    {
        $userId = Auth::id();

        $evaluationUpdatedAt = Evaluation::where('employee_id', $userId)
            ->tahunAktif()
            ->latest()
            ->value('updated_at');

        $supervisorFeedbackUpdatedAt = SupervisorFeedback::where('employee_id', $userId)
            ->tahunAktif()
            ->latest()
            ->value('updated_at');

        $version = md5(
            $evaluationUpdatedAt . '|' . $supervisorFeedbackUpdatedAt
        );

        return response()->json(['version' => $version]);
    }

    public function feedback(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:users,id',
            'feedback'    => 'required|string|min:10',
            'signature'   => 'required|string',
        ]);

        if ((int) $validated['employee_id'] === (int) Auth::id()) {
            return back()
                ->withErrors(['employee_id' => 'Anda tidak bisa memberi tanggapan untuk diri sendiri.'])
                ->withInput();
        }

        // Satu akun hanya boleh memberi tanggapan SEKALI ke satu orang
        // yang sama - dicek di sini (bukan cuma disembunyikan dari
        // picker di index()) supaya tetap aman walau request dikirim
        // langsung/lewat form yang sudah kadaluarsa.
        $alreadyGivenFeedback = Feedback::where('employee_id', $validated['employee_id'])
            ->where('reviewer_id', Auth::id())
            ->exists();

        if ($alreadyGivenFeedback) {
            return back()
                ->withErrors(['employee_id' => 'Anda sudah pernah memberi tanggapan untuk pegawai ini.'])
                ->withInput();
        }

        // Pastikan data yang dikirim benar-benar gambar base64 dari canvas
        if (! preg_match('/^data:image\/png;base64,/', $validated['signature'])) {
            return back()->withErrors(['signature' => 'Format tanda tangan tidak valid.'])->withInput();
        }

        $imageContent = base64_decode(substr($validated['signature'], strpos($validated['signature'], ',') + 1));
        $signaturePath = 'signatures/feedback_' . $validated['employee_id'] . '_' . Auth::id() . '_' . time() . '.png';
        Storage::disk('public')->put($signaturePath, $imageContent);

        try {
            Feedback::create([
                'employee_id' => $validated['employee_id'],
                'reviewer_id' => auth::id(),
                'feedback'    => $validated['feedback'],
                'signature'   => $signaturePath,
            ]);
        } catch (QueryException $e) {
            // Jaga-jaga kalau dua submit terjadi hampir bersamaan dan
            // lolos dari pengecekan exists() di atas - unique index di
            // migrasi feedbacks (reviewer_id, employee_id) akan menolak
            // baris kedua di level database.
            if ($this->isDuplicateFeedbackError($e)) {
                return back()
                    ->withErrors(['employee_id' => 'Anda sudah pernah memberi tanggapan untuk pegawai ini.'])
                    ->withInput();
            }

            throw $e;
        }

        // Kirim notifikasi FCM ke supervisor jika pegawai yang baru saja
        // menerima tanggapan ini kini memenuhi syarat siapDinilaiPenilai().
        // Dilakukan SETELAH Feedback::create berhasil - jika FCM gagal,
        // feedback tetap tersimpan (notification bersifat tambahan).
        $feedbackTarget = User::find($validated['employee_id']);
        if ($feedbackTarget) {
            app(NotificationTriggerService::class)
                ->triggerSiapDinilaiPenilaiJikaPerlu($feedbackTarget);
        }

        return back()->with(
            'success',
            'Tanggapan berhasil dikirim.'
        );
    }

    private function isDuplicateFeedbackError(QueryException $e): bool
    {
        return str_contains($e->getMessage(), 'feedbacks_reviewer_id_employee_id_unique');
    }

    public function respondEvaluation(Request $request, Evaluation $evaluation)
    {
        // Pastikan pegawai hanya bisa menanggapi penilaian miliknya sendiri.
        if ($evaluation->employee_id !== Auth::id()) {
            abort(403);
        }

        // Pegawai baru boleh mengisi tanggapan setelah PEJABAT (evaluasi ini
        // sendiri, yang berarti sudah ada) DAN ATASAN PEJABAT
        // (SupervisorFeedback) sama-sama sudah menyelesaikan penilaiannya.
        // Dicek juga di sini (bukan cuma disembunyikan di view) supaya tidak
        // bisa diakali dengan mengirim request langsung ke route ini.
        // Dicocokkan ke tahun yang SAMA dengan $evaluation (bukan cuma
        // "pernah ada kapan saja"), supaya tanggapan atasan dari tahun lain
        // tidak ikut membuka tanggapan pegawai untuk evaluasi tahun ini.
        $atasanPejabatSudahMenilai = SupervisorFeedback::where(
            'employee_id',
            $evaluation->employee_id
        )->where('tahun', $evaluation->tahun)->exists();

        if (! $atasanPejabatSudahMenilai) {
            return back()->with(
                'error',
                'Anda belum dapat mengisi tanggapan. Penilaian dari Atasan Pejabat belum diselesaikan.'
            );
        }

        $validated = $request->validate([
            'employee_response' => 'required|string|min:5',
            'employee_signature' => 'required|string',
        ]);

        // Pastikan data yang dikirim benar-benar gambar base64 dari canvas
        if (! preg_match('/^data:image\/png;base64,/', $validated['employee_signature'])) {
            return back()->withErrors(['employee_signature' => 'Format tanda tangan tidak valid.'])->withInput();
        }

        $imageContent = base64_decode(substr($validated['employee_signature'], strpos($validated['employee_signature'], ',') + 1));
        $signaturePath = 'signatures/evaluation_response_' . $evaluation->id . '_' . time() . '.png';
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

    /**
     * Toggle checklist "sudah bertemu & evaluasi" milik pegawai yang
     * sedang login. Boleh dibatalkan kapan saja, TAPI baru boleh MULAI
     * DICENTANG setelah Atasan Penilai (SupervisorFeedback) sudah mengisi
     * tanggapannya untuk pegawai ini - lihat
     * User::checklistPertemuanBolehDiisi(). Setiap kali DICENTANG (dari
     * kosong -> tercentang) WAJIB disertai selfie langsung dari kamera
     * perangkat - lihat
     * resources/views/partials/checklist-selfie-toggle.blade.php.
     */
    public function toggleChecklistPertemuan(Request $request)
    {
        $user = User::findOrFail(Auth::id());

        // Tambahan: begitu HRD sudah menandatangani penilaian pegawai ini
        // (Evaluation::hrd_signature - lihat HrdController::signAsHrd()),
        // checklist pertemuan (termasuk selfie/bukti-nya) TERKUNCI - tidak
        // boleh dicentang ulang maupun dibatalkan lewat sini lagi. Alurnya:
        // selfie/checklist dulu, baru HRD tanda tangan; setelah itu
        // checklist & selfie tidak bisa diubah lagi.
        if ($user->hrdSudahMenandatanganiPenilaian()) {
            return back()->with(
                'error',
                'Checklist pertemuan & evaluasi tidak bisa diubah lagi karena penilaian ini sudah ditanda-tangani HRD.'
            );
        }

        // "Sudah tercentang" di sini WAJIB dicek untuk tahun berjalan
        // (pegawaiSudahKonfirmasiPertemuan(), bukan cuma cek kolom _at
        // langsung) - supaya sisa checklist tahun lalu yang masih
        // tersimpan tidak dianggap "sudah centang" untuk tahun ini, dan
        // pegawai tetap bisa mencentang checklist yang baru begitu tahun
        // penilaian berganti. Lihat migration
        // add_tahun_to_checklist_pertemuan_columns & catatan di
        // User::pegawaiSudahKonfirmasiPertemuan().
        if ($user->pegawaiSudahKonfirmasiPertemuan()) {
            // Batalkan checklist tahun berjalan - tidak perlu selfie &
            // tidak perlu dicek syarat tanggapan atasan. File bukti lama
            // dihapus dari disk (bukan cuma dilepas dari kolom) supaya
            // tidak jadi sampah menumpuk di storage - lihat catatan di
            // HandlesChecklistEvidence::deleteChecklistEvidence().
            $this->deleteChecklistEvidence($user->pegawai_konfirmasi_pertemuan_selfie);

            $user->update([
                'pegawai_konfirmasi_pertemuan_at' => null,
                'pegawai_konfirmasi_pertemuan_selfie' => null,
                'pegawai_konfirmasi_pertemuan_evidence_type' => null,
                'pegawai_konfirmasi_pertemuan_metode' => null,
                'pegawai_konfirmasi_pertemuan_tahun' => null,
            ]);

            return back()->with('success', 'Checklist pertemuan & evaluasi dibatalkan.');
        }

        if (! $user->checklistPertemuanBolehDiisi()) {
            return back()->with(
                'error',
                'Checklist pertemuan & evaluasi belum bisa dicentang. Tanggapan dari Atasan Penilai belum diselesaikan.'
            );
        }

        [$selfiePath, $evidenceType, $meetingMethod] = $this->resolveChecklistEvidence($request, 'pegawai', $user->id);

        // Hapus bukti lama (kalau ada) sebelum ditimpa - baik itu sisa
        // attempt sebelumnya di tahun yang sama, MAUPUN sisa checklist
        // tahun lalu yang belum sempat dibatalkan manual. Dengan begini
        // foto lama tidak menumpuk terus tiap tahun.
        $this->deleteChecklistEvidence($user->pegawai_konfirmasi_pertemuan_selfie);

        $user->update([
            'pegawai_konfirmasi_pertemuan_at' => now(),
            'pegawai_konfirmasi_pertemuan_selfie' => $selfiePath,
            'pegawai_konfirmasi_pertemuan_evidence_type' => $evidenceType,
            'pegawai_konfirmasi_pertemuan_metode' => $meetingMethod,
            'pegawai_konfirmasi_pertemuan_tahun' => now()->year,
        ]);

        // Kalau checklist PENILAI untuk pegawai ini sudah lebih dulu
        // lengkap, checklist PEGAWAI barusan ini yang melengkapi syarat
        // - beri tahu HRD bahwa penilaian ini sudah siap ditandatangani.
        app(NotificationTriggerService::class)
            ->triggerSiapTandaTanganHrdJikaPerlu($user);

        return back()->with('success', 'Checklist pertemuan & evaluasi berhasil dicentang.');
    }
}
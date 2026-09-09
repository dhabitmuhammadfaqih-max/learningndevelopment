<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\OfficialEvaluation;
use App\Services\NotificationTriggerService;
use App\Http\Controllers\Concerns\HandlesChecklistEvidence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SupervisorController extends Controller
{
    use HandlesChecklistEvidence;

    // Method index()/show()/feedback() ("Dashboard Atasan") sudah dihapus.
    // Fitur tanggapan atasan penilai untuk pegawai sekarang hanya bisa
    // diakses lewat /pejabat/dashboard (lihat
    // OfficialController::showTanggapanPegawai() & giveTanggapanPegawai()).

    /**
     * Halaman detail + form penilaian pejabat. Hanya boleh diakses kalau
     * pejabat tsb memang ditugaskan (supervisor_id) ke atasan yang login.
     */
    public function showOfficial($id)
    {
        $pejabat = User::where('role', 'pejabat')->findOrFail($id);

        if ($pejabat->supervisor_id !== Auth::id()) {
            abort(403, 'Anda bukan atasan yang ditugaskan untuk menilai pejabat ini.');
        }

        // Dibatasi ke tahun berjalan supaya form yang tampil selalu
        // mencerminkan siklus penilaian tahun ini - penilaian tahun lalu
        // tetap tersimpan sebagai histori tapi tidak membuat form terlihat
        // "sudah diisi" begitu tahun baru mulai.
        $myEvaluation = OfficialEvaluation::where('official_id', $pejabat->id)
            ->where('supervisor_id', Auth::id())
            ->tahunAktif()
            ->latest()
            ->first();

        // Tanggapan korelasi (Feedback antar pejabat) yang sudah diterima
        // pejabat ini - dipakai untuk menampilkan daftarnya sekaligus
        // menghitung syarat minimal sebelum boleh dinilai. Lihat
        // User::korelasiPejabatSudahMemberiTanggapan().
        $peerFeedbacks = $pejabat->feedbacksReceived()
            ->with('reviewer')
            ->latest()
            ->get();

        // Sama seperti pegawai (lihat OfficialController::showOfficial() /
        // User::siapDinilaiPenilai()): siap dinilai butuh tanggapan
        // korelasi DAN data kehadiran yang sudah diisi HRD - lihat
        // User::siapDinilaiPenilaiPejabat().
        $readyToEvaluate = $pejabat->siapDinilaiPenilaiPejabat();

        return view('supervisor.evaluate_official', compact(
            'pejabat',
            'myEvaluation',
            'peerFeedbacks',
            'readyToEvaluate'
        ));
    }

    /**
     * AJAX polling status untuk supervisor.evaluate_official - lihat pola
     * & alasan di EmployeeController::statusVersion(). Dilingkupi ke satu
     * pejabat ($id) yang sedang dibuka, karena halaman ini juga per
     * pejabat (bukan daftar).
     */
    public function statusVersion($id)
    {
        $pejabat = User::where('role', 'pejabat')->findOrFail($id);

        if ($pejabat->supervisor_id !== Auth::id()) {
            abort(403);
        }

        $latestPeerFeedback = $pejabat->feedbacksReceived()
            ->latest()
            ->value('updated_at');

        $latestEvaluation = OfficialEvaluation::where('official_id', $pejabat->id)
            ->where('supervisor_id', Auth::id())
            ->latest()
            ->value('updated_at');

        $version = md5($latestPeerFeedback . '|' . $latestEvaluation);

        return response()->json(['version' => $version]);
    }

    public function evaluateOfficial(Request $request, $id)
    {
        $pejabat = User::where('role', 'pejabat')->findOrFail($id);

        if ($pejabat->supervisor_id !== Auth::id()) {
            abort(403, 'Anda bukan atasan yang ditugaskan untuk menilai pejabat ini.');
        }

        // Atasan hanya boleh mulai menilai kalau pejabat ini sudah
        // menerima minimal MIN_TANGGAPAN_KORELASI_PEJABAT (3) tanggapan
        // korelasi dari pejabat lain, DAN HRD sudah mengisi data
        // kehadiran - sama pola-nya seperti syarat menilai pegawai
        // (lihat OfficialController::evaluate() / User::siapDinilaiPenilai()).
        // Lihat User::siapDinilaiPenilaiPejabat().
        if (! $pejabat->siapDinilaiPenilaiPejabat()) {
            return back()->with(
                'error',
                'Pejabat ini belum bisa dinilai. Pastikan sudah menerima minimal ' . User::MIN_TANGGAPAN_KORELASI_PEJABAT . ' tanggapan korelasi dari pejabat lain dan HRD sudah mengisi data kehadiran terlebih dahulu.'
            );
        }

        // Sama seperti Evaluation: dibatasi per tahun, bukan sekali selamanya,
        // supaya atasan yang sama tetap bisa menilai pejabat yang sama lagi
        // tahun depan tanpa kehilangan data penilaian tahun-tahun sebelumnya.
        $tahunIni = now()->year;

        $alreadyEvaluated = OfficialEvaluation::where('official_id', $pejabat->id)
            ->where('supervisor_id', Auth::id())
            ->where('tahun', $tahunIni)
            ->exists();

        if ($alreadyEvaluated) {
            return back()->with('success', 'Pejabat ini sudah pernah Anda nilai untuk tahun ' . $tahunIni . '.');
        }

        [$validated, $recommendationValue, $kenaikanGajiAmount, $promosiKeterangan, $demosiKeterangan, $mutasiKeterangan, $error] = $this->validateOfficialEvaluationInput($request, $pejabat);

        if ($error) {
            return $error;
        }

        if (! preg_match('/^data:image\/png;base64,/', $validated['signature'])) {
            return back()->withErrors(['signature' => 'Format tanda tangan tidak valid.'])->withInput();
        }

        $imageContent = base64_decode(substr($validated['signature'], strpos($validated['signature'], ',') + 1));
        $signaturePath = "signatures/official_evaluation_{$pejabat->id}_" . Auth::id() . '_' . time() . '.png';
        Storage::disk('public')->put($signaturePath, $imageContent);

        $score = OfficialEvaluation::calculateScore($validated);

        // "teguran" hanya diisi kalau atasan memilih "Pernah" pada
        // pertanyaan apakah pejabat ini pernah ditegur. Kalau "Pernah"
        // dipilih tapi keterangan dikosongkan, tetap simpan penanda
        // "Pernah" supaya informasinya tidak hilang.
        $teguranPernah = $validated['teguran_pernah'] ?? 'tidak';
        $teguranValue = $teguranPernah === 'ya'
            ? (trim((string) ($validated['teguran'] ?? '')) !== '' ? trim($validated['teguran']) : 'Pernah')
            : null;

        try {
            OfficialEvaluation::create([
                'official_id'                                       => $pejabat->id,
                'supervisor_id'                                     => Auth::id(),
                'tahun'                                              => $tahunIni,
                'kepemimpinan'                                      => $validated['kepemimpinan'],
                'kemampuan_merencanakan_mengoordinasikan'            => $validated['kemampuan_merencanakan_mengoordinasikan'],
                'kemampuan_analisa_evaluasi_pengambilan_keputusan'   => $validated['kemampuan_analisa_evaluasi_pengambilan_keputusan'],
                'kemampuan_memotivasi_aplikasi_manajemen'            => $validated['kemampuan_memotivasi_aplikasi_manajemen'],
                'tanggung_jawab_manajemen'                           => $validated['tanggung_jawab_manajemen'],
                'kerjasama'                                          => $validated['kerjasama'],
                'prakarsa'                                           => $validated['prakarsa'],
                'integritas'                                         => $validated['integritas'],
                'pengetahuan_teknik_operasi'                         => $validated['pengetahuan_teknik_operasi'],
                'score'                                              => $score,
                'feedback'                                           => $validated['feedback'],
                'teguran'                                            => $teguranValue,
                'recommendation'                                     => $recommendationValue,
                'kenaikan_gaji_amount'                               => $kenaikanGajiAmount,
                'promosi_keterangan'                                 => $promosiKeterangan,
                'demosi_keterangan'                                  => $demosiKeterangan,
                'mutasi_keterangan'                                  => $mutasiKeterangan,
                'signature'                                          => $signaturePath,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            Storage::disk('public')->delete($signaturePath);

            return back()->with('success', 'Pejabat ini sudah pernah Anda nilai sebelumnya.');
        }

        // Kirim notifikasi FCM ke Atasan Penilai pejabat bahwa Tanggapan
        // Atasan untuk pejabat ini kini sudah bisa mulai diisi. Dilakukan
        // SETELAH tersimpan - notifikasi bersifat tambahan, bukan blocking.
        app(NotificationTriggerService::class)
            ->triggerSiapTanggapanAtasanPenilaiPejabatJikaPerlu($pejabat);

        return back()->with('success', 'Penilaian berhasil disimpan. Nilai akhir: ' . $score);
    }

    public function updateOfficialEvaluation(Request $request, $id)
    {
        $pejabat = User::where('role', 'pejabat')->findOrFail($id);

        if ($pejabat->supervisor_id !== Auth::id()) {
            abort(403, 'Anda bukan atasan yang ditugaskan untuk menilai pejabat ini.');
        }

        // Dibatasi ke tahun berjalan supaya yang diedit selalu penilaian
        // tahun ini, bukan tidak sengaja menimpa baris histori tahun lalu.
        $evaluation = OfficialEvaluation::where('official_id', $pejabat->id)
            ->where('supervisor_id', Auth::id())
            ->tahunAktif()
            ->latest()
            ->first();

        if (! $evaluation) {
            return back()->with('error', 'Penilaian belum pernah dibuat untuk pejabat ini pada tahun ini.');
        }

        // Begitu pejabat yang dinilai sudah tanda tangan (menanggapi &
        // menandatangani penilaiannya sendiri, lihat
        // OfficialController::respondEvaluation), nilai tidak boleh
        // diubah lagi - sama pola-nya seperti OfficialController::
        // updateEvaluation() yang mengunci penilaian pegawai begitu
        // atasan pejabat sudah mengirim tanggapan.
        if ($evaluation->employee_signature) {
            return back()->with(
                'error',
                'Nilai tidak dapat diubah karena pejabat yang dinilai sudah menandatangani penilaian ini.'
            );
        }

        [$validated, $recommendationValue, $kenaikanGajiAmount, $promosiKeterangan, $demosiKeterangan, $mutasiKeterangan, $error] = $this->validateOfficialEvaluationInput($request, $pejabat, signatureRequired: false);

        if ($error) {
            return $error;
        }

        $signaturePath = $evaluation->signature;

        if (! empty($validated['signature'])) {
            if (! preg_match('/^data:image\/png;base64,/', $validated['signature'])) {
                return back()->withErrors(['signature' => 'Format tanda tangan tidak valid.'])->withInput();
            }

            $imageContent = base64_decode(substr($validated['signature'], strpos($validated['signature'], ',') + 1));
            $newSignaturePath = "signatures/official_evaluation_{$pejabat->id}_" . Auth::id() . '_' . time() . '.png';
            Storage::disk('public')->put($newSignaturePath, $imageContent);

            if ($signaturePath) {
                Storage::disk('public')->delete($signaturePath);
            }

            $signaturePath = $newSignaturePath;
        }

        $score = OfficialEvaluation::calculateScore($validated);

        // "teguran" hanya diisi kalau atasan memilih "Pernah" pada
        // pertanyaan apakah pejabat ini pernah ditegur. Kalau "Pernah"
        // dipilih tapi keterangan dikosongkan, tetap simpan penanda
        // "Pernah" supaya informasinya tidak hilang.
        $teguranPernah = $validated['teguran_pernah'] ?? 'tidak';
        $teguranValue = $teguranPernah === 'ya'
            ? (trim((string) ($validated['teguran'] ?? '')) !== '' ? trim($validated['teguran']) : 'Pernah')
            : null;

        $evaluation->update([
            'kepemimpinan'                                      => $validated['kepemimpinan'],
            'kemampuan_merencanakan_mengoordinasikan'            => $validated['kemampuan_merencanakan_mengoordinasikan'],
            'kemampuan_analisa_evaluasi_pengambilan_keputusan'   => $validated['kemampuan_analisa_evaluasi_pengambilan_keputusan'],
            'kemampuan_memotivasi_aplikasi_manajemen'            => $validated['kemampuan_memotivasi_aplikasi_manajemen'],
            'tanggung_jawab_manajemen'                           => $validated['tanggung_jawab_manajemen'],
            'kerjasama'                                          => $validated['kerjasama'],
            'prakarsa'                                           => $validated['prakarsa'],
            'integritas'                                         => $validated['integritas'],
            'pengetahuan_teknik_operasi'                         => $validated['pengetahuan_teknik_operasi'],
            'score'                                              => $score,
            'feedback'                                           => $validated['feedback'],
            'teguran'                                            => $teguranValue,
            'recommendation'                                     => $recommendationValue,
            'kenaikan_gaji_amount'                               => $kenaikanGajiAmount,
            'promosi_keterangan'                                 => $promosiKeterangan,
            'demosi_keterangan'                                  => $demosiKeterangan,
                'mutasi_keterangan'                                  => $mutasiKeterangan,
            'signature'                                          => $signaturePath,
        ]);

        return back()->with('success', 'Penilaian berhasil diperbarui. Nilai akhir: ' . $score);
    }

    /**
     * Toggle checklist "sudah bertemu & evaluasi" milik ATASAN (Penilai
     * pejabat, users.supervisor_id pejabat ini) untuk pejabat yang
     * dinilainya. Versi pejabat dari
     * OfficialController::toggleChecklistPertemuanPegawai(). Boleh
     * dibatalkan kapan saja, TAPI baru boleh MULAI DICENTANG setelah
     * Atasan Penilai (OfficialSupervisorFeedback) sudah mengisi
     * tanggapannya untuk pejabat ini - lihat
     * User::checklistPertemuanPejabatBolehDiisi(). Setiap kali DICENTANG
     * WAJIB disertai selfie langsung dari kamera perangkat.
     */
    public function toggleChecklistPertemuanPejabat(Request $request, $id)
    {
        $pejabat = User::where('role', 'pejabat')->findOrFail($id);

        if ((int) $pejabat->supervisor_id !== Auth::id()) {
            abort(403, 'Anda bukan Atasan yang ditugaskan untuk menilai pejabat ini.');
        }

        // Tambahan: sama seperti OfficialController::toggleChecklistPertemuanSaya()
        // - checklist milik ATASAN ini juga terkunci begitu HRD sudah
        // menandatangani penilaian pejabat tsb.
        if ($pejabat->hrdSudahMenandatanganiPenilaianPejabat()) {
            return back()->with(
                'error',
                'Checklist pertemuan & evaluasi tidak bisa diubah lagi karena penilaian ini sudah ditanda-tangani HRD.'
            );
        }

        if ($pejabat->atasanSudahKonfirmasiPertemuan()) {
            $this->deleteChecklistEvidence($pejabat->atasan_konfirmasi_pertemuan_selfie);

            $pejabat->update([
                'atasan_konfirmasi_pertemuan_at' => null,
                'atasan_konfirmasi_pertemuan_selfie' => null,
                'atasan_konfirmasi_pertemuan_evidence_type' => null,
                'atasan_konfirmasi_pertemuan_metode' => null,
                'atasan_konfirmasi_pertemuan_tahun' => null,
            ]);

            return back()->with('success', 'Checklist pertemuan & evaluasi dibatalkan.');
        }

        if (! $pejabat->checklistPertemuanPejabatBolehDiisi()) {
            return back()->with(
                'error',
                'Checklist pertemuan & evaluasi belum bisa dicentang. Tanggapan dari Atasan Penilai untuk pejabat ini belum diselesaikan.'
            );
        }

        [$selfiePath, $evidenceType, $meetingMethod] = $this->resolveChecklistEvidence($request, 'atasan', $pejabat->id);

        $this->deleteChecklistEvidence($pejabat->atasan_konfirmasi_pertemuan_selfie);

        $pejabat->update([
            'atasan_konfirmasi_pertemuan_at' => now(),
            'atasan_konfirmasi_pertemuan_selfie' => $selfiePath,
            'atasan_konfirmasi_pertemuan_evidence_type' => $evidenceType,
            'atasan_konfirmasi_pertemuan_metode' => $meetingMethod,
            'atasan_konfirmasi_pertemuan_tahun' => now()->year,
        ]);

        return back()->with('success', 'Checklist pertemuan & evaluasi berhasil dicentang.');
    }

    /**
     * Validasi input form penilaian pejabat, dipakai bersama oleh
     * evaluateOfficial() dan updateOfficialEvaluation().
     */
    private function validateOfficialEvaluationInput(Request $request, User $pejabat, bool $signatureRequired = true): array
    {
        $validated = $request->validate([
            'kepemimpinan'                                      => 'required|numeric|min:0|max:100',
            'kemampuan_merencanakan_mengoordinasikan'            => 'required|numeric|min:0|max:100',
            'kemampuan_analisa_evaluasi_pengambilan_keputusan'   => 'required|numeric|min:0|max:100',
            'kemampuan_memotivasi_aplikasi_manajemen'            => 'required|numeric|min:0|max:100',
            'tanggung_jawab_manajemen'                           => 'required|numeric|min:0|max:100',
            'kerjasama'                                          => 'required|numeric|min:0|max:100',
            'prakarsa'                                           => 'required|numeric|min:0|max:100',
            'integritas'                                         => 'required|numeric|min:0|max:100',
            'pengetahuan_teknik_operasi'                         => 'required|numeric|min:0|max:100',
            'feedback'                                           => 'required|string|min:10',
            'teguran_pernah'                                     => 'nullable|in:tidak,ya',
            'teguran'                                            => 'nullable|string|max:1000',
            'recommendation'                                     => 'nullable|array',
            'recommendation.*'                                   => 'in:' . implode(',', array_keys(OfficialEvaluation::RECOMMENDATIONS)),
            // 'integer', bukan 'numeric' - is_numeric() PHP tetap
            // menganggap valid notasi ilmiah seperti "1e5", jadi validasi
            // bisa ketembus kalau client-side JS dimatikan/diakali.
            'kenaikan_gaji_amount'                               => 'nullable|integer|min:1',
            'promosi_keterangan'                                 => 'nullable|string|max:255',
            'demosi_keterangan'                                  => 'nullable|string|max:255',
            'mutasi_keterangan'                                   => 'nullable|string|max:255',
            'signature'                                          => ($signatureRequired ? 'required' : 'nullable') . '|string',
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
        // kontrak pejabat ini SAAT INI memang sudah Kontrak Dagsap.
        if (in_array('kontrak_dagsap_ke_tetap', $recommendations, true) && ! $pejabat->isEligibleForDagsapTetap()) {
            $error = back()
                ->withErrors(['recommendation' => 'Rekomendasi "Kontrak Dagsap ke Tetap" tidak bisa diajukan karena status pejabat ini bukan Kontrak Dagsap (masih PHL atau Kontrak OS).'])
                ->withInput();

            return [$validated, null, null, null, null, null, $error];
        }

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
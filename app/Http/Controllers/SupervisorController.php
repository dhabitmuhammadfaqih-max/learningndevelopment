<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Feedback;
use App\Models\Evaluation;
use App\Models\SupervisorFeedback;
use App\Models\OfficialEvaluation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SupervisorController extends Controller
{
    public function index()
    {
        $employees = User::where('role', 'karyawan')->get();

        // Pejabat yang secara khusus ditugaskan ke atasan yang sedang login.
        $pejabatBinaan = User::where('role', 'pejabat')
            ->where('supervisor_id', Auth::id())
            ->withCount(
                // Dipakai untuk menampilkan status "sudah/belum dinilai" tanpa N+1.
                ['officialEvaluations as evaluated_count' => function ($query) {
                    $query->where('supervisor_id', Auth::id());
                }]
            )
            ->get();

        return view(
            'supervisor.dashboard',
            compact('employees', 'pejabatBinaan')
        );
    }

    public function show($id)
    {
        $employee = User::findOrFail($id);

        $feedbacks = Feedback::where(
            'employee_id',
            $id
        )->with('reviewer')->get();

        $evaluation = Evaluation::where(
            'employee_id',
            $id
        )->with('official')->first();

        $supervisorFeedback =
            SupervisorFeedback::where(
                'employee_id',
                $id
            )
            ->where(
                'supervisor_id',
                auth::id()
            )
            ->first();

        return view(
            'supervisor.evaluate',
            compact(
                'employee',
                'feedbacks',
                'evaluation',
                'supervisorFeedback'
            )
        );
    }

    public function feedback(Request $request, $id)
    {
        $validated = $request->validate([
            'feedback'  => 'required|string|min:10',
            'signature' => 'required|string',
        ]);

        // Pastikan data yang dikirim benar-benar gambar base64 dari canvas
        if (! preg_match('/^data:image\/png;base64,/', $validated['signature'])) {
            return back()->withErrors(['signature' => 'Format tanda tangan tidak valid.'])->withInput();
        }

        $imageContent = base64_decode(substr($validated['signature'], strpos($validated['signature'], ',') + 1));
        $signaturePath = 'signatures/supervisor_' . $id . '_' . Auth::id() . '_' . time() . '.png';
        Storage::disk('public')->put($signaturePath, $imageContent);

        $existing = SupervisorFeedback::where('employee_id', $id)
            ->where('supervisor_id', auth::id())
            ->first();

        // Hapus file tanda tangan lama jika tanggapan sebelumnya diedit ulang
        if ($existing && $existing->signature) {
            Storage::disk('public')->delete($existing->signature);
        }

        SupervisorFeedback::updateOrCreate(
            [
                'employee_id' => $id,
                'supervisor_id' => auth::id(),
            ],
            [
                'feedback'  => $validated['feedback'],
                'signature' => $signaturePath,
            ]
        );

        return back()->with(
            'success',
            'Tanggapan atasan berhasil disimpan.'
        );
    }

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

        $myEvaluation = OfficialEvaluation::where('official_id', $pejabat->id)
            ->where('supervisor_id', Auth::id())
            ->latest()
            ->first();

        return view('supervisor.evaluate_official', compact('pejabat', 'myEvaluation'));
    }

    public function evaluateOfficial(Request $request, $id)
    {
        $pejabat = User::where('role', 'pejabat')->findOrFail($id);

        if ($pejabat->supervisor_id !== Auth::id()) {
            abort(403, 'Anda bukan atasan yang ditugaskan untuk menilai pejabat ini.');
        }

        $alreadyEvaluated = OfficialEvaluation::where('official_id', $pejabat->id)
            ->where('supervisor_id', Auth::id())
            ->exists();

        if ($alreadyEvaluated) {
            return back()->with('success', 'Pejabat ini sudah pernah Anda nilai sebelumnya.');
        }

        [$validated, $recommendationValue, $kenaikanGajiAmount, $error] = $this->validateOfficialEvaluationInput($request);

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

        try {
            OfficialEvaluation::create([
                'official_id'                                       => $pejabat->id,
                'supervisor_id'                                     => Auth::id(),
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
                'recommendation'                                     => $recommendationValue,
                'kenaikan_gaji_amount'                               => $kenaikanGajiAmount,
                'signature'                                          => $signaturePath,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            Storage::disk('public')->delete($signaturePath);

            return back()->with('success', 'Pejabat ini sudah pernah Anda nilai sebelumnya.');
        }

        return back()->with('success', 'Penilaian berhasil disimpan. Nilai akhir: ' . $score);
    }

    public function updateOfficialEvaluation(Request $request, $id)
    {
        $pejabat = User::where('role', 'pejabat')->findOrFail($id);

        if ($pejabat->supervisor_id !== Auth::id()) {
            abort(403, 'Anda bukan atasan yang ditugaskan untuk menilai pejabat ini.');
        }

        $evaluation = OfficialEvaluation::where('official_id', $pejabat->id)
            ->where('supervisor_id', Auth::id())
            ->latest()
            ->first();

        if (! $evaluation) {
            return back()->with('error', 'Penilaian belum pernah dibuat untuk pejabat ini.');
        }

        [$validated, $recommendationValue, $kenaikanGajiAmount, $error] = $this->validateOfficialEvaluationInput($request, signatureRequired: false);

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
            'recommendation'                                     => $recommendationValue,
            'kenaikan_gaji_amount'                               => $kenaikanGajiAmount,
            'signature'                                          => $signaturePath,
        ]);

        return back()->with('success', 'Penilaian berhasil diperbarui. Nilai akhir: ' . $score);
    }

    /**
     * Validasi input form penilaian pejabat, dipakai bersama oleh
     * evaluateOfficial() dan updateOfficialEvaluation().
     */
    private function validateOfficialEvaluationInput(Request $request, bool $signatureRequired = true): array
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
            'recommendation'                                     => 'nullable|array',
            'recommendation.*'                                   => 'in:' . implode(',', array_keys(OfficialEvaluation::RECOMMENDATIONS)),
            'kenaikan_gaji_amount'                               => 'nullable|numeric|min:1|max:' . OfficialEvaluation::KENAIKAN_GAJI_MAX,
            'signature'                                          => ($signatureRequired ? 'required' : 'nullable') . '|string',
        ]);

        $recommendations = $validated['recommendation'] ?? [];

        if (in_array('kenaikan_gaji', $recommendations, true) && empty($validated['kenaikan_gaji_amount'])) {
            $error = back()
                ->withErrors(['kenaikan_gaji_amount' => 'Nominal kenaikan gaji wajib diisi (maksimal Rp' . number_format(OfficialEvaluation::KENAIKAN_GAJI_MAX, 0, ',', '.') . ').'])
                ->withInput();

            return [$validated, null, null, $error];
        }

        $recommendationValue = empty($recommendations) ? 'tidak_ada' : implode(',', $recommendations);
        $kenaikanGajiAmount = in_array('kenaikan_gaji', $recommendations, true)
            ? (int) $validated['kenaikan_gaji_amount']
            : null;

        return [$validated, $recommendationValue, $kenaikanGajiAmount, null];
    }
}
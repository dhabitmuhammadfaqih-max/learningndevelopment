<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Evaluation;
use App\Models\SupervisorFeedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class OfficialController extends Controller
{
    public function index()
    {
        $employees = User::where('role', 'karyawan')
            ->withCount('feedbacksReceived')
            ->withCount('supervisorFeedbacks')
            ->with(['evaluations' => function ($query) {
                $query->where('official_id', Auth::id());
            }])
            ->get();

        return view('official.dashboard', compact('employees'));
    }

    public function show($id)
    {
        $employee = User::where('role', 'karyawan')->findOrFail($id);

        $peerFeedbacks = $employee->feedbacksReceived()
            ->with('reviewer')
            ->latest()
            ->get();

        $myEvaluation = $employee->evaluations()
            ->where('official_id', Auth::id())
            ->latest()
            ->first();

        // Begitu atasan pejabat sudah mengirim tanggapan untuk karyawan ini,
        // nilai yang sudah diberikan pejabat terkunci dan tidak bisa diedit lagi.
        $evaluationLocked = SupervisorFeedback::where('employee_id', $employee->id)->exists();

        return view('official.evaluate', compact(
            'employee',
            'peerFeedbacks',
            'myEvaluation',
            'evaluationLocked'
        ));
    }

    public function updateEmployee(Request $request, $id)
    {
        $employee = User::where('role', 'karyawan')->findOrFail($id);

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
        $employee->email      = $validated['username'] . '@karyawan.local';

        // Kalau NIK diubah, password ikut diperbarui (password = NIK).
        if ($validated['nik'] !== $employee->nik) {
            $employee->password = bcrypt($validated['nik']);
        }

        $employee->nik = $validated['nik'];

        $employee->save();

        return back()->with('success', 'Data karyawan berhasil diperbarui.');
    }

    public function evaluate(Request $request, $id)
    {
        $employee = User::where('role', 'karyawan')->findOrFail($id);

        // Cegah double-submit: satu pejabat hanya boleh menilai satu karyawan sekali
        // (constraint unique di tabel evaluations). Cek di sini SEBELUM menyimpan
        // apa pun, supaya tidak ada file tanda tangan yang nyangkut di storage
        // dan tidak muncul error mentah kalau tombol Simpan tidak sengaja diklik dua kali.
        $alreadyEvaluated = $employee->evaluations()
            ->where('official_id', Auth::id())
            ->exists();

        if ($alreadyEvaluated) {
            return back()->with('success', 'Karyawan ini sudah pernah Anda nilai sebelumnya.');
        }

        [$validated, $recommendationValue, $kenaikanGajiAmount, $error] = $this->validateEvaluationInput($request);

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

        try {
            Evaluation::create([
                'employee_id'                 => $employee->id,
                'official_id'                 => Auth::id(),
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
                'recommendation'              => $recommendationValue,
                'kenaikan_gaji_amount'        => $kenaikanGajiAmount,
                'signature'                   => $signaturePath,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            Storage::disk('public')->delete($signaturePath);

            return back()->with('success', 'Karyawan ini sudah pernah Anda nilai sebelumnya.');
        }

        return back()->with('success', 'Penilaian berhasil disimpan. Nilai akhir: '.$score);
    }

    public function updateEvaluation(Request $request, $id)
    {
        $employee = User::where('role', 'karyawan')->findOrFail($id);

        $evaluation = $employee->evaluations()
            ->where('official_id', Auth::id())
            ->latest()
            ->first();

        if (! $evaluation) {
            return back()->with('error', 'Penilaian belum pernah dibuat untuk karyawan ini.');
        }

        // Begitu atasan pejabat sudah menanggapi, nilai tidak boleh diubah lagi.
        $locked = SupervisorFeedback::where('employee_id', $employee->id)->exists();

        if ($locked) {
            return back()->with(
                'error',
                'Nilai tidak dapat diubah karena atasan pejabat sudah mengirim tanggapan.'
            );
        }

        [$validated, $recommendationValue, $kenaikanGajiAmount, $error] = $this->validateEvaluationInput($request, signatureRequired: false);

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
            'recommendation'              => $recommendationValue,
            'kenaikan_gaji_amount'        => $kenaikanGajiAmount,
            'signature'                   => $signaturePath,
        ]);

        return back()->with('success', 'Penilaian berhasil diperbarui. Nilai akhir: '.$score);
    }

    /**
     * Validasi input form penilaian yang dipakai bersama oleh evaluate() dan
     * updateEvaluation(). Mengembalikan [validated, recommendationValue, kenaikanGajiAmount, errorResponse].
     */
    private function validateEvaluationInput(Request $request, bool $signatureRequired = true): array
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
            'recommendation'              => 'nullable|array',
            'recommendation.*'            => 'in:' . implode(',', array_keys(Evaluation::RECOMMENDATIONS)),
            'kenaikan_gaji_amount'        => 'nullable|numeric|min:1|max:' . Evaluation::KENAIKAN_GAJI_MAX,
            'signature'                   => ($signatureRequired ? 'required' : 'nullable') . '|string',
        ]);

        $recommendations = $validated['recommendation'] ?? [];

        // Kalau rekomendasi "Kenaikan Gaji" dicentang, nominalnya wajib diisi.
        if (in_array('kenaikan_gaji', $recommendations, true) && empty($validated['kenaikan_gaji_amount'])) {
            $error = back()
                ->withErrors(['kenaikan_gaji_amount' => 'Nominal kenaikan gaji wajib diisi (maksimal Rp' . number_format(Evaluation::KENAIKAN_GAJI_MAX, 0, ',', '.') . ').'])
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

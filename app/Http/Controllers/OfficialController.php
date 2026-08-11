<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Evaluation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OfficialController extends Controller
{
    public function index()
    {
        $employees = User::where('role', 'karyawan')->get();

        // Ambil ID karyawan yang sudah dinilai oleh pejabat yang login
        $evaluatedEmployeeIds = Evaluation::where('official_id', Auth::id())
            ->pluck('employee_id')
            ->toArray();

        return view('official.dashboard', compact(
            'employees',
            'evaluatedEmployeeIds'
        ));
    }

    public function show($id)
    {
        $employee = User::where('role', 'karyawan')->findOrFail($id);

        $peerFeedbacks = $employee->feedbacksReceived()
            ->with('reviewer')
            ->latest()
            ->get();

        // Ambil penilaian dari pejabat yang sedang login
        $myEvaluation = $employee->evaluations()
            ->where('official_id', Auth::id())
            ->latest()
            ->first();

        return view('official.evaluate', compact(
            'employee',
            'peerFeedbacks',
            'myEvaluation'
        ));
    }

    public function evaluate(Request $request, $id)
    {
        $employee = User::where('role', 'karyawan')->findOrFail($id);

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
            'recommendation'              => 'required|in:perpanjang_kontrak,promosi,kenaikan_gaji,tidak_ada',
        ]);

        $score = Evaluation::calculateScore($validated);

        // Cari penilaian yang sudah ada
        $evaluation = Evaluation::where('employee_id', $employee->id)
            ->where('official_id', Auth::id())
            ->first();

        $data = [
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
            'recommendation'              => $validated['recommendation'],
        ];

        if ($evaluation) {
            // Kalau sudah ada, update penilaian lama
            $evaluation->update($data);

            $message = 'Penilaian berhasil diperbarui. Nilai akhir: '.$score;
        } else {
            // Kalau belum ada, buat penilaian baru
            Evaluation::create($data);

            $message = 'Penilaian berhasil disimpan. Nilai akhir: '.$score;
        }

        return redirect()
            ->route('official.employee', $employee->id)
            ->with('success', $message);
    }
}
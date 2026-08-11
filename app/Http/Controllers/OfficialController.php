<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Evaluation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class OfficialController extends Controller
{
    public function index()
    {
        $employees = User::where('role', 'karyawan')
            ->withCount('feedbacksReceived')
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

        return view('official.evaluate', compact('employee', 'peerFeedbacks', 'myEvaluation'));
    }

    public function storeEmployee(Request $request)
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'username'   => 'required|string|max:100|alpha_dash|unique:users,username',
            'nik'        => 'required|string|max:50|unique:users,nik',
            'unit_kerja' => 'nullable|string|max:255',
        ]);

        User::create([
            'name'       => $validated['name'],
            'username'   => $validated['username'],
            'nik'        => $validated['nik'],
            'unit_kerja' => $validated['unit_kerja'] ?? null,
            // Kolom email masih wajib & unik di database, jadi diisi otomatis
            // dari username (karyawan tidak login/pakai email ini sama sekali).
            'email'      => $validated['username'] . '@karyawan.local',
            // Password = NIK karyawan, jadi tidak perlu diinput terpisah.
            'password'   => bcrypt($validated['nik']),
            'role'       => 'karyawan',
        ]);

        return back()->with('success', 'Akun karyawan berhasil ditambahkan.');
    }

    public function updateEmployee(Request $request, $id)
    {
        $employee = User::where('role', 'karyawan')->findOrFail($id);

        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'username'   => 'required|string|max:100|alpha_dash|unique:users,username,' . $employee->id,
            'nik'        => 'required|string|max:50|unique:users,nik,' . $employee->id,
            'unit_kerja' => 'nullable|string|max:255',
        ]);

        $employee->name       = $validated['name'];
        $employee->username   = $validated['username'];
        $employee->unit_kerja = $validated['unit_kerja'] ?? null;
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

    public function destroyEmployee($id)
    {
        $employee = User::where('role', 'karyawan')->findOrFail($id);

        $employee->delete();

        return back()->with('success', 'Data karyawan berhasil dihapus.');
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
            'signature'                   => 'required|string',
        ]);

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
                'recommendation'              => $validated['recommendation'],
                'signature'                   => $signaturePath,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            Storage::disk('public')->delete($signaturePath);

            return back()->with('success', 'Karyawan ini sudah pernah Anda nilai sebelumnya.');
        }

        return back()->with('success', 'Penilaian berhasil disimpan. Nilai akhir: '.$score);
    }
}
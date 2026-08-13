<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Feedback;
use App\Models\Evaluation;
use App\Models\SupervisorFeedback;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    public function index()
    {
        // withCount/with dipakai supaya tiap kartu karyawan bisa menampilkan
        // badge "sudah/belum bisa di-print PDF" tanpa query N+1.
        // "SPG" bukan role tersendiri lagi, melainkan flag is_spg pada akun
        // dengan role "karyawan" (lihat User::is_spg / pdf() di bawah).
        $employees = User::where('role', 'karyawan')
            ->withCount('feedbacksReceived')
            ->with(['evaluations' => function ($query) {
                $query->latest()->limit(1);
            }])
            ->with(['supervisorFeedbacks' => function ($query) {
                $query->latest()->limit(1);
            }])
            ->get();

        // Semua akun dari semua role, buat ditampilkan & dikelola admin.
        $accounts = User::orderBy('role')
            ->orderBy('name')
            ->get();

        return view(
            'admin.dashboard',
            compact('employees', 'accounts')
        );
    }

    public function storeAccount(Request $request)
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'username'   => 'required|string|max:100|alpha_dash|unique:users,username',
            'nik'        => 'required|string|max:50|unique:users,nik',
            'unit_kerja' => 'nullable|string|max:255',
            'jabatan'    => 'nullable|string|max:255',
            'role'       => 'required|in:karyawan,pejabat,atasan_pejabat,admin',
            'is_spg'     => 'nullable|boolean',
        ]);

        User::create([
            'name'       => $validated['name'],
            'username'   => $validated['username'],
            'nik'        => $validated['nik'],
            'unit_kerja' => $validated['unit_kerja'] ?? null,
            'jabatan'    => $validated['jabatan'] ?? null,
            // "SPG" bukan lagi role sendiri, melainkan status tambahan yang
            // hanya berlaku untuk role "karyawan" (lihat catatan di index()).
            'is_spg'     => $validated['role'] === 'karyawan' && ! empty($validated['is_spg']),
            // Kolom email masih wajib & unik di database, jadi diisi otomatis
            // dari username (akun ini login pakai username, bukan email).
            'email'      => $validated['username'] . '@karyawan.local',
            // Password = NIK, konsisten dengan seluruh akun di sistem ini.
            'password'   => bcrypt($validated['nik']),
            'role'       => $validated['role'],
        ]);

        return back()->with('success', 'Akun berhasil ditambahkan.');
    }

    public function updateAttendance(Request $request, $id)
    {
        $employee = User::findOrFail($id);

        $validated = $request->validate([
            'jumlah_izin'      => 'nullable|integer|min:0',
            'jumlah_sakit'     => 'nullable|integer|min:0',
            'jumlah_alpa'      => 'nullable|integer|min:0',
            'jumlah_terlambat' => 'nullable|integer|min:0',
            'contract_status'  => 'nullable|in:' . implode(',', array_keys(User::CONTRACT_STATUSES)),
        ]);

        $employee->update([
            'jumlah_izin'      => $validated['jumlah_izin'] ?? 0,
            'jumlah_sakit'     => $validated['jumlah_sakit'] ?? 0,
            'jumlah_alpa'      => $validated['jumlah_alpa'] ?? 0,
            'jumlah_terlambat' => $validated['jumlah_terlambat'] ?? 0,
            'contract_status'  => $validated['contract_status'] ?? null,
        ]);

        return back()->with(
            'success',
            'Data kehadiran & status kontrak berhasil diperbarui.'
        );
    }

    public function show($id)
    {
        $employee = User::findOrFail($id);

        $feedbacks = Feedback::where(
            'employee_id',
            $id
        )
        ->with('reviewer')
        ->get();

        $evaluation = Evaluation::where(
            'employee_id',
            $id
        )
        ->with('official')
        ->first();

        $supervisorFeedback =
            SupervisorFeedback::where(
                'employee_id',
                $id
            )
            ->with('supervisor')
            ->first();

        return view(
            'admin.detail',
            compact(
                'employee',
                'feedbacks',
                'evaluation',
                'supervisorFeedback'
            )
        );
    }

    public function pdf($id)
    {
        $employee = User::findOrFail($id);

        $feedbacks = Feedback::where(
            'employee_id',
            $id
        )
        ->with('reviewer')
        ->get();

        // Minimal 3 tanggapan korelasi — TIDAK berlaku untuk akun dengan
        // flag is_spg, karena korelasi bersifat opsional untuk mereka.
        if (! $employee->is_spg && $feedbacks->count() < 3) {
            return back()->with(
                'error',
                'PDF belum dapat dibuat. ' .
                'Minimal 3 tanggapan korelasi diperlukan.'
            );
        }

        $evaluation = Evaluation::where(
            'employee_id',
            $id
        )
        ->with('official')
        ->first();

        if (!$evaluation) {
            return back()->with(
                'error',
                'Penilaian pejabat belum tersedia.'
            );
        }

        $supervisorFeedback =
            SupervisorFeedback::where(
                'employee_id',
                $id
            )
            ->with('supervisor')
            ->first();

        if (!$supervisorFeedback) {
            return back()->with(
                'error',
                'Tanggapan atasan belum tersedia.'
            );
        }

        // Sematkan tanda tangan sebagai base64 supaya dompdf tidak perlu
        // mengakses file lewat HTTP (lebih aman & konsisten, sama seperti
        // pola di SignatureDocumentController).
        $toBase64 = function (?string $path) {
            if (! $path || ! Storage::disk('public')->exists($path)) {
                return null;
            }

            return 'data:image/png;base64,' . base64_encode(Storage::disk('public')->get($path));
        };

        // Sematkan tanda tangan SEMUA pemberi tanggapan korelasi (bukan cuma
        // yang pertama), supaya PDF menuliskan seluruh tanda tangan yang ada.
        $korelasiSignatures = $feedbacks->map(function ($feedback) use ($toBase64) {
            return [
                'nama'      => $feedback->reviewer->name ?? '-',
                'signature' => $toBase64($feedback->signature),
            ];
        })->values();

        $signatures = [
            'pejabat'  => $toBase64($evaluation->signature),
            'atasan'   => $toBase64($supervisorFeedback->signature),
            'korelasi' => $korelasiSignatures,
        ];

        $pdf = Pdf::loadView(
            'admin.pdf',
            compact(
                'employee',
                'feedbacks',
                'evaluation',
                'supervisorFeedback',
                'signatures'
            )
        );

        return $pdf->download(
            'penilaian-' .
            str_replace(' ', '-', strtolower(
                $employee->name
            )) .
            '.pdf'
        );
    }
}
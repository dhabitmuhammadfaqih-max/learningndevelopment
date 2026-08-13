<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Feedback;
use App\Models\Evaluation;
use App\Models\SupervisorFeedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SupervisorController extends Controller
{
    public function index()
    {
        $employees = User::where('role', 'karyawan')->get();

        return view(
            'supervisor.dashboard',
            compact('employees')
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
}
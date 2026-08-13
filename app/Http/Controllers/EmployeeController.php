<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Feedback;
use App\Models\Evaluation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class EmployeeController extends Controller
{
    public function index()
    {
        $employees = User::where('role', 'karyawan')
            ->where('id', '!=', auth::id())
            ->get();

        $myFeedbacks = Feedback::where(
            'employee_id',
            auth::id()
        )->with('reviewer')->get();

        $user = auth::user();
        $myEvaluation = null;

        if ($user && method_exists($user, 'evaluations')) {
            $myEvaluation = $user
                ->evaluations()
                ->with('official')
                ->latest()
                ->first();
        }

        return view('employee.dashboard', compact(
            'employees',
            'myFeedbacks',
            'myEvaluation'
        ));
    }

    public function feedback(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|exists:users,id',
            'feedback'    => 'required|string|min:10',
            'signature'   => 'required|string',
        ]);

        // Pastikan data yang dikirim benar-benar gambar base64 dari canvas
        if (! preg_match('/^data:image\/png;base64,/', $validated['signature'])) {
            return back()->withErrors(['signature' => 'Format tanda tangan tidak valid.'])->withInput();
        }

        $imageContent = base64_decode(substr($validated['signature'], strpos($validated['signature'], ',') + 1));
        $signaturePath = 'signatures/feedback_' . $validated['employee_id'] . '_' . Auth::id() . '_' . time() . '.png';
        Storage::disk('public')->put($signaturePath, $imageContent);

        Feedback::create([
            'employee_id' => $validated['employee_id'],
            'reviewer_id' => auth::id(),
            'feedback'    => $validated['feedback'],
            'signature'   => $signaturePath,
        ]);

        return back()->with(
            'success',
            'Tanggapan berhasil dikirim.'
        );
    }

    public function respondEvaluation(Request $request, Evaluation $evaluation)
    {
        // Pastikan karyawan hanya bisa menanggapi penilaian miliknya sendiri.
        if ($evaluation->employee_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'employee_response' => 'required|string|min:5',
        ]);

        $evaluation->update([
            'employee_response'    => $validated['employee_response'],
            'employee_response_at' => now(),
        ]);

        return back()->with(
            'success',
            'Tanggapan Anda atas penilaian berhasil dikirim.'
        );
    }
}
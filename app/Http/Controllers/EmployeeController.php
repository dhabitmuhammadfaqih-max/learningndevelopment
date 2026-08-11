<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
        $request->validate([
            'employee_id' => 'required|exists:users,id',
            'feedback' => 'required|string|min:10',
        ]);

        Feedback::create([
            'employee_id' => $request->employee_id,
            'reviewer_id' => auth::id(),
            'feedback' => $request->feedback,
        ]);

        return back()->with(
            'success',
            'Tanggapan berhasil dikirim.'
        );
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Feedback;
use App\Models\Evaluation;
use App\Models\SupervisorFeedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SupervisorController extends Controller
{
    public function index()
    {
        $employees = User::where(
            'role',
            'karyawan'
        )->get();

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
        $request->validate([
            'feedback' => 'required|string|min:10',
        ]);

        SupervisorFeedback::updateOrCreate(
            [
                'employee_id' => $id,
                'supervisor_id' => auth::id(),
            ],
            [
                'feedback' => $request->feedback,
            ]
        );

        return back()->with(
            'success',
            'Tanggapan atasan berhasil disimpan.'
        );
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Feedback;
use App\Models\Evaluation;
use App\Models\SupervisorFeedback;
use Barryvdh\DomPDF\Facade\Pdf;

class AdminController extends Controller
{
    public function index()
    {
        $employees = User::where(
            'role',
            'karyawan'
        )->get();

        return view(
            'admin.dashboard',
            compact('employees')
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

        // Minimal 3 tanggapan korelasi
        if ($feedbacks->count() < 3) {
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

        $pdf = Pdf::loadView(
            'admin.pdf',
            compact(
                'employee',
                'feedbacks',
                'evaluation',
                'supervisorFeedback'
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
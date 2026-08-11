<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Feedback;
use App\Models\Evaluation;
use App\Models\SupervisorFeedback;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

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

        // Sematkan tanda tangan sebagai base64 supaya dompdf tidak perlu
        // mengakses file lewat HTTP (lebih aman & konsisten, sama seperti
        // pola di SignatureDocumentController).
        $toBase64 = function (?string $path) {
            if (! $path || ! Storage::disk('public')->exists($path)) {
                return null;
            }

            return 'data:image/png;base64,' . base64_encode(Storage::disk('public')->get($path));
        };

        $signatures = [
            'pejabat'  => $toBase64($evaluation->signature),
            'atasan'   => $toBase64($supervisorFeedback->signature),
            'korelasi' => $toBase64($feedbacks->first()->signature ?? null),
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
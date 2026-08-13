<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\OfficialController;
use App\Http\Controllers\SupervisorController;
use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\SignatureDocumentController;

/*
|--------------------------------------------------------------------------
| ROOT REDIRECT
|--------------------------------------------------------------------------
| Arahkan user ke dashboard sesuai role masing-masing.
| Kalau belum login, arahkan ke halaman login.
*/

Route::get('/', function () {
    if (!auth::check()) {
        return redirect('/login');
    }

    return match (auth::user()->role) {
        'karyawan'       => redirect()->route('employee.dashboard'),
        'pejabat'        => redirect()->route('official.dashboard'),
        'atasan_pejabat' => redirect()->route('supervisor.dashboard'),
        'admin'          => redirect()->route('admin.dashboard'),
        default          => redirect('/login'),
    };
});

Route::middleware('auth')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | KARYAWAN
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:karyawan')
        ->prefix('karyawan')
        ->name('employee.')
        ->group(function () {

            Route::get(
                '/dashboard',
                [EmployeeController::class, 'index']
            )->name('dashboard');

            Route::post(
                '/feedback',
                [EmployeeController::class, 'feedback']
            )->name('feedback');

            Route::post(
                '/penilaian/{evaluation}/tanggapan',
                [EmployeeController::class, 'respondEvaluation']
            )->name('evaluation.respond');
        });


    /*
    |--------------------------------------------------------------------------
    | PEJABAT
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:pejabat')
    ->prefix('pejabat')
    ->name('official.')
    ->group(function () {

        Route::get('/dashboard', [OfficialController::class, 'index'])
            ->name('dashboard');

        Route::get('/nilai-saya', [OfficialController::class, 'myEvaluations'])
            ->name('my-evaluations');

        Route::get('/karyawan/{id}', [OfficialController::class, 'show'])
            ->name('employee');

        Route::post('/karyawan/{id}/nilai', [OfficialController::class, 'evaluate'])
            ->name('evaluate');

        Route::put('/karyawan/{id}/nilai', [OfficialController::class, 'updateEvaluation'])
            ->name('evaluate.update');

        Route::put('/karyawan/{id}', [OfficialController::class, 'updateEmployee'])
            ->name('employee.update');

        // Tambahan: kalau ada yang akses /nilai lewat GET, redirect balik
        Route::get('/karyawan/{id}/nilai', function ($id) {
            return redirect()->route('official.employee', $id);
        });
    });


    /*
    |--------------------------------------------------------------------------
    | ATASAN PEJABAT
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:atasan_pejabat')
        ->prefix('atasan')
        ->name('supervisor.')
        ->group(function () {

            Route::get(
                '/dashboard',
                [SupervisorController::class, 'index']
            )->name('dashboard');

            Route::get(
                '/karyawan/{id}',
                [SupervisorController::class, 'show']
            )->name('employee');

            Route::post(
                '/karyawan/{id}/feedback',
                [SupervisorController::class, 'feedback']
            )->name('feedback');
        });

    /*
    |--------------------------------------------------------------------------
    | PENILAIAN PEJABAT (oleh atasan penilai: pejabat/atasan_pejabat/admin)
    |--------------------------------------------------------------------------
    | Dulu hanya role atasan_pejabat yang bisa menilai pejabat. Sekarang
    | siapa pun yang ditugaskan lewat users.supervisor_id boleh menilai,
    | selama role-nya pejabat/atasan_pejabat/admin (dicek di sini via
    | middleware, lalu dicek lagi per-akun di SupervisorController).
    | Tetap pakai prefix 'atasan' & nama 'supervisor.' supaya konsisten
    | dengan route yang sudah dipakai di view (route('supervisor.official', ...)).
    */

    Route::middleware('role:pejabat,atasan_pejabat,admin')
        ->prefix('atasan')
        ->name('supervisor.')
        ->group(function () {

            Route::get(
                '/pejabat/{id}',
                [SupervisorController::class, 'showOfficial']
            )->name('official');

            Route::post(
                '/pejabat/{id}/nilai',
                [SupervisorController::class, 'evaluateOfficial']
            )->name('official.evaluate');

            Route::put(
                '/pejabat/{id}/nilai',
                [SupervisorController::class, 'updateOfficialEvaluation']
            )->name('official.evaluate.update');
        });


    /*
    |--------------------------------------------------------------------------
    | ADMIN
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:admin')
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {

            Route::get(
                '/dashboard',
                [AdminController::class, 'index']
            )->name('dashboard');

            Route::post(
                '/akun',
                [AdminController::class, 'storeAccount']
            )->name('account.store');

            Route::put(
                '/akun/{id}',
                [AdminController::class, 'updateAccount']
            )->name('account.update');

            Route::get(
                '/karyawan/{id}',
                [AdminController::class, 'show']
            )->name('employee');

            Route::put(
                '/karyawan/{id}/kehadiran',
                [AdminController::class, 'updateAttendance']
            )->name('employee.attendance.update');

            Route::get(
                '/karyawan/{id}/pdf',
                [AdminController::class, 'pdf']
            )->name('pdf');
        });
});

    Route::get('/dokumen/baru', [SignatureDocumentController::class, 'create'])
        ->name('signature.create');
 
    Route::get('/dokumen/{document}', [SignatureDocumentController::class, 'edit'])
        ->name('signature.edit');
 
    Route::post('/dokumen/{document}/tanda-tangan/{role}', [SignatureDocumentController::class, 'saveSignature'])
        ->name('signature.save');
 
    Route::get('/dokumen/{document}/pdf', [SignatureDocumentController::class, 'generatePdf'])
        ->name('signature.pdf');

require __DIR__.'/auth.php';
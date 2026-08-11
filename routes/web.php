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

        Route::post('/karyawan', [OfficialController::class, 'storeEmployee'])
            ->name('employee.store');

        Route::get('/karyawan/{id}', [OfficialController::class, 'show'])
            ->name('employee');

        Route::post('/karyawan/{id}/nilai', [OfficialController::class, 'evaluate'])
            ->name('evaluate');

        Route::put('/karyawan/{id}', [OfficialController::class, 'updateEmployee'])
            ->name('employee.update');

        Route::delete('/karyawan/{id}', [OfficialController::class, 'destroyEmployee'])
            ->name('employee.destroy');

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

            Route::get(
                '/karyawan/{id}',
                [AdminController::class, 'show']
            )->name('employee');

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
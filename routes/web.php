<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\OfficialController;
use App\Http\Controllers\SupervisorController;
use App\Http\Controllers\HrdController;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\SignatureDocumentController;
use App\Http\Controllers\FcmController;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| FALLBACK FILE SERVING (storage/app/public)
|--------------------------------------------------------------------------
| Menyajikan file dari disk "public" (mis. tanda tangan) meski symlink
| `public/storage` belum dibuat lewat `php artisan storage:link`.
| Kalau symlink sudah ada, web server akan menyajikan file itu langsung
| sebagai static file dan route ini tidak akan pernah dipanggil.
|
| CATATAN: path sengaja diganti dari /storage/ ke /files/ karena di
| hosting ini path /storage/ diblokir oleh security module server
| (mengembalikan 403 sebelum request sempat sampai ke Laravel).
*/

Route::get('/files/{path}', function (string $path) {
    if (! Storage::disk('public')->exists($path)) {
        abort(404);
    }

    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

    $mimeMap = [
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif'  => 'image/gif',
        'webp' => 'image/webp',
        'svg'  => 'image/svg+xml',
        'pdf'  => 'application/pdf',
    ];

    // Tebak Content-Type dari ekstensi file dulu (tidak butuh extension
    // "fileinfo" PHP, yang kadang tidak aktif di beberapa environment).
    // Kalau ekstensinya tidak dikenal, baru coba mimeType() bawaan Laravel.
    $contentType = $mimeMap[$extension]
        ?? Storage::disk('public')->mimeType($path)
        ?? 'application/octet-stream';

    return response(
        Storage::disk('public')->get($path),
        200,
        ['Content-Type' => $contentType]
    );
})->where('path', '.*')->name('files.fallback');


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
        'pegawai' => redirect()->route('employee.dashboard'),
        'pejabat' => redirect()->route('official.dashboard'),
        'hrd'     => redirect()->route('admin.dashboard'),
        default   => redirect('/login'),
    };
});

Route::middleware('auth')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | PEGAWAI
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:pegawai')
        ->prefix('pegawai')
        ->name('employee.')
        ->group(function () {

            Route::get(
                '/dashboard',
                [EmployeeController::class, 'index']
            )->name('dashboard');

            // Endpoint ringan untuk AJAX polling - dicek berkala dari
            // dashboard.blade.php buat tahu apakah status penilaian/
            // tanggapan atasan berubah sejak halaman dimuat, tanpa perlu
            // pegawai refresh manual. Lihat EmployeeController::statusVersion().
            Route::get(
                '/dashboard/status-version',
                [EmployeeController::class, 'statusVersion']
            )->name('dashboard.status-version');

            Route::post(
                '/feedback',
                [EmployeeController::class, 'feedback']
            )->name('feedback');

            Route::post(
                '/penilaian/{evaluation}/tanggapan',
                [EmployeeController::class, 'respondEvaluation']
            )->name('evaluation.respond');

            // Checklist "sudah bertemu & evaluasi" milik pegawai sendiri -
            // independen dari form tanggapan di atas, bisa
            // dicentang/dibatalkan kapan saja. Lihat
            // User::pegawaiSudahKonfirmasiPertemuan().
            Route::post(
                '/checklist-pertemuan',
                [EmployeeController::class, 'toggleChecklistPertemuan']
            )->name('checklist-pertemuan.toggle');
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

        // AJAX polling status - lihat pola & alasan di
        // EmployeeController::statusVersion(); versi pejabat ini
        // dilingkupi ke data yang tampil di official.dashboard.
        Route::get('/dashboard/status-version', [OfficialController::class, 'statusVersion'])
            ->name('dashboard.status-version');

        Route::get('/nilai-saya', [OfficialController::class, 'myEvaluations'])
            ->name('my-evaluations');

        Route::post('/nilai-saya/{evaluation}/tanggapan', [OfficialController::class, 'respondEvaluation'])
            ->name('evaluation.respond');

        // Checklist "sudah bertemu & evaluasi" milik pejabat sendiri, untuk
        // siklus penilaian PEJABAT (OfficialEvaluation) - independen dari
        // form tanggapan di atas, bisa dicentang/dibatalkan kapan saja
        // (dengan syarat awal, lihat User::checklistPertemuanPejabatBolehDiisi()).
        // Versi pejabat dari 'employee.checklist-pertemuan.toggle'.
        Route::post('/checklist-pertemuan-saya', [OfficialController::class, 'toggleChecklistPertemuanSaya'])
            ->name('checklist-pertemuan-saya.toggle');

        // Tanggapan (korelasi) antar pejabat yang satu unit kerja.
        Route::post('/tanggapan', [OfficialController::class, 'feedback'])
            ->name('feedback');

        Route::put('/pegawai/{id}', [OfficialController::class, 'updateEmployee'])
            ->name('employee.update');

        // Tanggapan Atasan untuk pegawai (khusus Atasan Penilai,
        // users.atasan_pejabat_id) - sengaja berdiri sendiri di bawah
        // prefix 'pejabat', TIDAK memakai route/controller 'supervisor.*'
        // (SupervisorController/prefix 'atasan') sama sekali, supaya
        // pejabat cukup lewat /pejabat/dashboard tanpa perlu tahu URL
        // dashboard lain. Lihat OfficialController::showTanggapanPegawai()
        // & giveTanggapanPegawai().
        Route::get('/pegawai/{id}/tanggapan', [OfficialController::class, 'showTanggapanPegawai'])
            ->name('employee.tanggapan');

        // AJAX polling status - lihat pola & alasan di
        // EmployeeController::statusVersion(). Dilingkupi ke satu pegawai
        // ({id}) yang sedang dibuka, karena halaman ini nunggu $evaluation
        // (penilaian dari Penilai) muncul supaya form Tanggapan Atasan
        // ke-unlock (lihat OfficialController::showTanggapanPegawai()).
        Route::get('/pegawai/{id}/tanggapan/status-version', [OfficialController::class, 'tanggapanPegawaiStatusVersion'])
            ->name('employee.tanggapan.status-version');

        Route::post('/pegawai/{id}/tanggapan', [OfficialController::class, 'giveTanggapanPegawai'])
            ->name('employee.tanggapan.store');

        // Tanggapan Atasan untuk pejabat (khusus Atasan Penilai,
        // users.atasan_penilai_pejabat_id) - versi employee.tanggapan di
        // atas, tapi untuk pejabat->pejabat. Lihat
        // OfficialController::showTanggapanPejabat() &
        // giveTanggapanPejabat().
        Route::get('/pejabat-dinilai/{id}/tanggapan', [OfficialController::class, 'showTanggapanPejabat'])
            ->name('pejabat.tanggapan');

        // AJAX polling status - lihat pola & alasan di
        // OfficialController::tanggapanPegawaiStatusVersion() (versi
        // pegawai) - ini versi pejabat->pejabat-nya.
        Route::get('/pejabat-dinilai/{id}/tanggapan/status-version', [OfficialController::class, 'tanggapanPejabatStatusVersion'])
            ->name('pejabat.tanggapan.status-version');

        Route::post('/pejabat-dinilai/{id}/tanggapan', [OfficialController::class, 'giveTanggapanPejabat'])
            ->name('pejabat.tanggapan.store');
    });

    /*
    |--------------------------------------------------------------------------
    | PENILAIAN PEGAWAI (oleh penilai yang ditugaskan: pejabat/hrd)
    |--------------------------------------------------------------------------
    | Dulu hanya role pejabat yang bisa menilai pegawai, dan bisa menilai
    | siapa saja. Sekarang HRD menugaskan satu "Atasan Penilai" per
    | pegawai lewat users.supervisor_id (bisa akun pejabat/hrd), dan
    | hanya akun itu ATAU atasan dari akun itu (atasan dari atasan
    | pegawai — masih satu unit yang sama) yang boleh menilai (dicek
    | lagi per-akun di OfficialController::canEvaluate()). Nama route
    | tetap 'official.*' supaya seluruh view yang sudah ada tidak perlu
    | diubah.
    */

    Route::middleware('role:pejabat,hrd,pegawai')
        ->prefix('pejabat')
        ->name('official.')
        ->group(function () {

            Route::get('/pegawai/{id}', [OfficialController::class, 'show'])
                ->name('employee');

            Route::post('/pegawai/{id}/nilai', [OfficialController::class, 'evaluate'])
                ->name('evaluate');

            Route::put('/pegawai/{id}/nilai', [OfficialController::class, 'updateEvaluation'])
                ->name('evaluate.update');

            // Tambahan: kalau ada yang akses /nilai lewat GET, redirect balik
            Route::get('/pegawai/{id}/nilai', function ($id) {
                return redirect()->route('official.employee', $id);
            });

            // Checklist "sudah bertemu & evaluasi" milik PENILAI (khusus
            // akun yang ditugaskan sebagai users.supervisor_id pegawai
            // ini, bukan Atasan Penilai) - independen dari form
            // Penilaian di atas, bisa dicentang/dibatalkan kapan saja.
            // Lihat User::penilaiSudahKonfirmasiPertemuan().
            Route::post(
                '/pegawai/{id}/checklist-pertemuan',
                [OfficialController::class, 'toggleChecklistPertemuanPegawai']
            )->name('employee.checklist-pertemuan.toggle');
        });

    // Catatan: role 'pegawai' ditambahkan ke middleware grup 'official.*'
    // di atas supaya SATU akun pegawai tertentu yang ditugaskan HRD lewat
    // users.supervisor_id (sama seperti mekanisme "Atasan Penilai" biasa
    // untuk pejabat/hrd) juga bisa menilai pegawai lain. Otorisasi
    // sebenarnya TETAP dijaga per-akun oleh OfficialController::canEvaluate()
    // (cek supervisor_id === Auth::id()), jadi menambah 'pegawai' di sini
    // TIDAK membuka akses ke semua pegawai - hanya akun yang memang
    // ditunjuk sebagai supervisor_id pegawai tsb yang lolos.


    /*
    |--------------------------------------------------------------------------
    | ATASAN PEJABAT
    |--------------------------------------------------------------------------
    | Role "atasan_pejabat" sudah digabung ke role "pejabat" (lihat migration
    | merge_atasan_pejabat_role_into_pejabat). Dashboard & fitur di bawah ini
    | tetap ada, sekarang diakses oleh akun ber-role pejabat yang berperan
    | sebagai atasan dari pejabat lain.
    */

    // Dashboard Atasan (index/show/feedback) sudah dihapus - fitur ini
    // duplikat dengan section "Pegawai yang Perlu Tanggapan Anda (Atasan
    // Penilai)" yang sudah ada di /pejabat/dashboard (lihat
    // OfficialController::showTanggapanPegawai() & giveTanggapanPegawai()),
    // jadi pejabat cukup lewat satu dashboard saja.

    /*
    |--------------------------------------------------------------------------
    | PENILAIAN PEJABAT (oleh atasan penilai: pejabat/hrd)
    |--------------------------------------------------------------------------
    | Dulu hanya role atasan_pejabat yang bisa menilai pejabat lain (role
    | itu sekarang sudah digabung ke role pejabat). Siapa pun yang
    | ditugaskan lewat users.supervisor_id boleh menilai, selama role-nya
    | pejabat/hrd (dicek di sini via middleware, lalu dicek lagi per-akun
    | di SupervisorController). Tetap pakai prefix 'atasan' & nama
    | 'supervisor.' supaya konsisten dengan route yang sudah dipakai di
    | view (route('supervisor.official', ...)).
    */

    Route::middleware('role:pejabat,hrd')
        ->prefix('atasan')
        ->name('supervisor.')
        ->group(function () {

            Route::get(
                '/pejabat/{id}',
                [SupervisorController::class, 'showOfficial']
            )->name('official');

            // AJAX polling status - lihat pola & alasan di
            // EmployeeController::statusVersion(); versi ini dilingkupi
            // ke satu pejabat ({id}) yang sedang dibuka.
            Route::get(
                '/pejabat/{id}/status-version',
                [SupervisorController::class, 'statusVersion']
            )->name('official.status-version');

            Route::post(
                '/pejabat/{id}/nilai',
                [SupervisorController::class, 'evaluateOfficial']
            )->name('official.evaluate');

            Route::put(
                '/pejabat/{id}/nilai',
                [SupervisorController::class, 'updateOfficialEvaluation']
            )->name('official.evaluate.update');

            // Checklist "sudah bertemu & evaluasi" milik ATASAN (khusus
            // akun yang ditugaskan sebagai users.supervisor_id pejabat
            // ini) - independen dari form Penilaian di atas dan dari
            // Tanggapan Atasan, bisa dicentang/dibatalkan kapan saja.
            // Setiap kali DICENTANG wajib disertai selfie kamera. Versi
            // pejabat dari 'official.employee.checklist-pertemuan.toggle'.
            Route::post(
                '/pejabat/{id}/checklist-pertemuan',
                [SupervisorController::class, 'toggleChecklistPertemuanPejabat']
            )->name('official.checklist-pertemuan.toggle');
        });


    /*
    |--------------------------------------------------------------------------
    | HRD
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:hrd')
        ->prefix('hrd')
        ->name('admin.')
        ->group(function () {

            Route::get(
                '/dashboard',
                [HrdController::class, 'index']
            )->name('dashboard');

            // AJAX polling status - lihat pola & alasan di
            // EmployeeController::statusVersion(); versi HRD ini
            // dilingkupi ke tahun yang sama dengan yang sedang dilihat.
            Route::get(
                '/dashboard/status-version',
                [HrdController::class, 'statusVersion']
            )->name('dashboard.status-version');

            // Halaman "bersihkan file lama" - lihat catatan lengkap di
            // HrdController::storageCleanupIndex(). Selalu cuma
            // menampilkan/menghapus file ORPHAN (tidak dirujuk kolom
            // manapun di database) - file yang masih dipakai TIDAK PERNAH
            // ikut, walau umurnya sudah lama, supaya aman dari salah hapus.
            Route::get(
                '/storage-cleanup',
                [HrdController::class, 'storageCleanupIndex']
            )->name('storage-cleanup');

            Route::post(
                '/storage-cleanup',
                [HrdController::class, 'storageCleanupDestroy']
            )->name('storage-cleanup.destroy');

            Route::get(
                '/akun',
                [HrdController::class, 'accounts']
            )->name('accounts');

            Route::get(
                '/pegawai',
                [HrdController::class, 'employeesIndex']
            )->name('employees');

            Route::get(
                '/pejabat',
                [HrdController::class, 'officialsIndex']
            )->name('officials');

            Route::post(
                '/akun',
                [HrdController::class, 'storeAccount']
            )->name('account.store');

            Route::post(
                '/akun/import',
                [HrdController::class, 'importAccounts']
            )->name('account.import');

            Route::put(
                '/akun/{id}',
                [HrdController::class, 'updateAccount']
            )->name('account.update');

            Route::delete(
                '/akun/{id}',
                [HrdController::class, 'destroyAccount']
            )->name('account.destroy');

            Route::patch(
                '/akun/{id}/status-kontrak',
                [HrdController::class, 'toggleStatusKontrak']
            )->name('account.toggleStatusKontrak');

            Route::get(
                '/pegawai/{id}',
                [HrdController::class, 'show']
            )->name('employee');

            Route::put(
                '/pegawai/{id}/kehadiran',
                [HrdController::class, 'updateAttendance']
            )->name('employee.attendance.update');

            Route::post(
                '/pegawai/{id}/tanda-tangan',
                [HrdController::class, 'signAsHrd']
            )->name('employee.sign');

            Route::post(
                '/pejabat/{id}/tanda-tangan',
                [HrdController::class, 'signAsHrdOfficial']
            )->name('official.sign');

            Route::get(
                '/pegawai/{id}/pdf',
                [HrdController::class, 'pdf']
            )->name('pdf');
        });

    /*
    |--------------------------------------------------------------------------
    | FIREBASE CLOUD MESSAGING (FCM) - Push Notification
    |--------------------------------------------------------------------------
    | Terbuka untuk SEMUA role yang sudah login (pegawai/pejabat/hrd),
    | bukan hanya penilai - karena registrasi token dilakukan otomatis
    | saat dashboard-layout dimuat (lihat public/js/fcm-client.js).
    | Notifikasi "Penilaian Baru Tersedia" hanya akan DIKIRIM ke penilai
    | yang bersangkutan (lihat FirebaseCloudMessagingService::sendToUser()
    | & App\Services\NotificationTriggerService), tapi endpoint registrasi
    | token tetap generik untuk semua user.
    */
    Route::prefix('fcm')
        ->name('fcm.')
        ->group(function () {
            Route::post('/token', [FcmController::class, 'storeToken'])
                ->name('token.store');

            Route::delete('/token', [FcmController::class, 'destroyToken'])
                ->name('token.destroy');
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
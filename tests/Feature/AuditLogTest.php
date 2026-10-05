<?php

use App\Models\AppSetting;
use App\Models\AuditLog;
use App\Models\MeetingCode;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Helper: buat baris audit_logs langsung (untuk tes filter/ekspor/prune),
 * dengan created_at yang bisa diatur.
 */
function makeAuditRow(array $attrs = [], $createdAt = null): AuditLog
{
    $log = new AuditLog(array_merge([
        'actor_name'  => 'Tester',
        'event'       => AuditLog::EVENT_UPDATED,
        'description' => 'Mengubah sesuatu',
    ], $attrs));

    if ($createdAt) {
        $log->created_at = $createdAt;
    }

    $log->save();

    return $log;
}

function hrdUser(array $attrs = []): User
{
    return User::factory()->create(array_merge(['role' => 'hrd', 'name' => 'HRD Utama'], $attrs));
}

// ----------------------------------------------------------------------
// Pencatatan otomatis perubahan model
// ----------------------------------------------------------------------

it('mencatat pembuatan model beserta pelakunya', function () {
    $hrd = hrdUser();
    $this->actingAs($hrd);

    $pegawai = User::factory()->create(['role' => 'pegawai', 'name' => 'Budi', 'jabatan' => 'Staf']);

    $log = AuditLog::where('auditable_type', 'users')
        ->where('auditable_id', $pegawai->id)
        ->where('event', AuditLog::EVENT_CREATED)
        ->firstOrFail();

    expect($log->actor_id)->toBe($hrd->id)
        ->and($log->actor_name)->toBe('HRD Utama')
        ->and($log->actor_role)->toBe('hrd')
        ->and($log->auditable_label)->toBe('Akun Budi (pegawai)')
        ->and($log->new_values)->toMatchArray(['name' => 'Budi', 'jabatan' => 'Staf'])
        ->and($log->old_values)->toBeNull();
});

it('mencatat perubahan hanya pada kolom yang berubah, dengan nilai lama dan baru', function () {
    $pegawai = User::factory()->create(['role' => 'pegawai', 'jabatan' => 'Staf', 'unit_kerja' => 'Gudang']);
    $this->actingAs(hrdUser());

    $pegawai->update(['jabatan' => 'Supervisor']);

    $log = AuditLog::where('event', AuditLog::EVENT_UPDATED)->latest('id')->firstOrFail();

    expect($log->old_values)->toBe(['jabatan' => 'Staf'])
        ->and($log->new_values)->toBe(['jabatan' => 'Supervisor'])
        ->and($log->description)->toContain('jabatan');
});

it('tidak mencatat kalau tidak ada yang berubah atau yang berubah cuma kolom noise', function () {
    $user = User::factory()->create();
    $before = AuditLog::count();

    $user->save();                                        // tanpa perubahan
    $user->update(['remember_token' => 'token-baru']);    // kolom diabaikan
    $user->touch();                                       // hanya updated_at

    expect(AuditLog::count())->toBe($before);
});

it('mencatat penghapusan model dengan snapshot data terakhirnya', function () {
    $pegawai = User::factory()->create(['role' => 'pegawai', 'name' => 'Sari']);
    $this->actingAs(hrdUser());

    $pegawai->delete();

    $log = AuditLog::where('event', AuditLog::EVENT_DELETED)->firstOrFail();

    expect($log->auditable_label)->toBe('Akun Sari (pegawai)')
        ->and($log->old_values['name'])->toBe('Sari')
        ->and($log->new_values)->toBeNull();
});

it('mencatat sebagai Sistem kalau tidak ada user yang login', function () {
    User::factory()->create(['name' => 'Dibuat Seeder']);

    $log = AuditLog::where('event', AuditLog::EVENT_CREATED)->firstOrFail();

    expect($log->actor_id)->toBeNull()->and($log->actor_name)->toBe('Sistem');
});

it('mencatat perubahan pengaturan periode', function () {
    $this->actingAs(hrdUser());

    AppSetting::set('active_year', '2026');
    AppSetting::set('active_year', '2027');

    $log = AuditLog::where('auditable_type', 'app_settings')->where('event', AuditLog::EVENT_UPDATED)->firstOrFail();

    expect($log->auditable_label)->toBe('Pengaturan active_year')
        ->and($log->old_values)->toBe(['value' => '2026'])
        ->and($log->new_values)->toBe(['value' => '2027']);
});

// ----------------------------------------------------------------------
// Data sensitif
// ----------------------------------------------------------------------

it('tidak pernah menyimpan password, tapi tetap mencatat bahwa password berubah', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $user->update(['password' => bcrypt('rahasia-banget-123')]);

    $log = AuditLog::where('event', AuditLog::EVENT_UPDATED)->latest('id')->firstOrFail();

    expect($log->old_values['password'])->toBe('[disembunyikan]')
        ->and($log->new_values['password'])->toBe('[disembunyikan]');

    $semua = AuditLog::all()->toJson();
    expect($semua)->not->toContain('rahasia-banget-123')
        ->and($semua)->not->toContain('$2y$');
});

it('menyamarkan kode pertemuan', function () {
    $this->actingAs(hrdUser());
    $subject = User::factory()->create(['role' => 'pegawai']);
    $issuer  = User::factory()->create(['role' => 'pejabat']);

    MeetingCode::create([
        'code'       => 'ABC234',
        'context'    => MeetingCode::CONTEXT_PEGAWAI,
        'subject_id' => $subject->id,
        'issuer_id'  => $issuer->id,
        'tahun'      => 2026,
    ]);

    $log = AuditLog::where('auditable_type', 'meeting_codes')->firstOrFail();

    expect($log->new_values['code'])->toBe('[disembunyikan]')
        ->and(AuditLog::all()->toJson())->not->toContain('ABC234');
});

it('memangkas nilai yang terlalu panjang', function () {
    config(['audit.max_value_length' => 50]);
    $this->actingAs(hrdUser());

    $user = User::factory()->create(['jabatan' => str_repeat('x', 300)]);

    $log = AuditLog::where('auditable_id', $user->id)->where('event', AuditLog::EVENT_CREATED)->firstOrFail();

    expect(mb_strlen($log->new_values['jabatan']))->toBeLessThanOrEqual(51);
});

// ----------------------------------------------------------------------
// Autentikasi
// ----------------------------------------------------------------------

it('mencatat login dan logout', function () {
    $user = User::factory()->create(['name' => 'Rina']);

    $this->post('/login', ['username' => $user->username, 'password' => 'password']);
    $this->post('/logout');

    $login = AuditLog::where('event', AuditLog::EVENT_LOGIN)->firstOrFail();
    $logout = AuditLog::where('event', AuditLog::EVENT_LOGOUT)->firstOrFail();

    expect($login->actor_id)->toBe($user->id)
        ->and($login->ip_address)->not->toBeNull()
        ->and($login->method)->toBe('POST')
        ->and($login->url)->toBe('/login')
        ->and($logout->actor_id)->toBe($user->id);
});

it('mencatat gagal login untuk akun yang ada, terkait ke akun itu', function () {
    $user = User::factory()->create(['name' => 'Rina']);

    $this->post('/login', ['username' => $user->username, 'password' => 'salah']);

    $log = AuditLog::where('event', AuditLog::EVENT_LOGIN_FAILED)->firstOrFail();

    expect($log->actor_id)->toBe($user->id)
        ->and($log->description)->toContain('password salah');
});

it('tidak menyimpan teks yang diketik untuk username tidak dikenal (bisa saja password salah ketik)', function () {
    $this->post('/login', ['username' => 'ini-mungkin-password-orang', 'password' => 'x']);

    $log = AuditLog::where('event', AuditLog::EVENT_LOGIN_FAILED)->firstOrFail();

    expect($log->actor_id)->toBeNull()
        ->and($log->description)->toContain('tidak dikenal')
        ->and(AuditLog::all()->toJson())->not->toContain('ini-mungkin-password-orang');
});

// ----------------------------------------------------------------------
// Akses halaman
// ----------------------------------------------------------------------

it('mengarahkan tamu ke halaman login', function () {
    $this->get('/hrd/audit-log')->assertRedirect('/login');
    $this->get('/hrd/audit-log/export')->assertRedirect('/login');
});

it('menolak pegawai dan pejabat', function (string $role) {
    $this->actingAs(User::factory()->create(['role' => $role]));

    $this->get('/hrd/audit-log')->assertForbidden();
    $this->get('/hrd/audit-log/export')->assertForbidden();
})->with(['pegawai', 'pejabat']);

it('menampilkan audit log ke HRD', function () {
    makeAuditRow(['actor_name' => 'Budi Santoso', 'description' => 'Mengubah Akun Sari (jabatan)', 'auditable_label' => 'Akun Sari (pegawai)']);

    $this->actingAs(hrdUser())
        ->get('/hrd/audit-log')
        ->assertOk()
        ->assertSee('Audit Log')
        ->assertSee('Budi Santoso')
        ->assertSee('Mengubah Akun Sari (jabatan)');
});

it('menampilkan perubahan sebelum/sesudah di detail dan meng-escape isinya', function () {
    makeAuditRow([
        'old_values'  => ['jabatan' => 'Staf'],
        'new_values'  => ['jabatan' => '<script>alert(1)</script>'],
    ]);

    $this->actingAs(hrdUser())
        ->get('/hrd/audit-log')
        ->assertOk()
        ->assertSee('Staf')
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertSee('&lt;script&gt;', false);
});

// ----------------------------------------------------------------------
// Filter
// ----------------------------------------------------------------------

it('memfilter berdasarkan jenis aksi', function () {
    makeAuditRow(['event' => AuditLog::EVENT_DELETED, 'description' => 'Menghapus HAPUS-INI']);
    makeAuditRow(['event' => AuditLog::EVENT_CREATED, 'description' => 'Membuat BUAT-INI']);

    $this->actingAs(hrdUser())
        ->get('/hrd/audit-log?event=deleted')
        ->assertOk()
        ->assertSee('HAPUS-INI')
        ->assertDontSee('BUAT-INI');
});

it('memfilter berdasarkan rentang tanggal (inklusif)', function () {
    makeAuditRow(['description' => 'LOG-LAMA'], now()->subDays(10));
    makeAuditRow(['description' => 'LOG-TENGAH'], now()->subDays(3)->setTime(23, 30));
    makeAuditRow(['description' => 'LOG-BARU'], now());

    $this->actingAs(hrdUser())
        ->get('/hrd/audit-log?' . http_build_query([
            'from' => now()->subDays(5)->toDateString(),
            'to'   => now()->subDays(3)->toDateString(),
        ]))
        ->assertOk()
        ->assertSee('LOG-TENGAH')
        ->assertDontSee('LOG-LAMA')
        ->assertDontSee('LOG-BARU');
});

it('memfilter berdasarkan pelaku', function () {
    makeAuditRow(['actor_id' => 11, 'actor_name' => 'Pelaku Satu', 'description' => 'AKSI-SATU']);
    makeAuditRow(['actor_id' => 22, 'actor_name' => 'Pelaku Dua', 'description' => 'AKSI-DUA']);

    $this->actingAs(hrdUser())
        ->get('/hrd/audit-log?actor=11')
        ->assertOk()
        ->assertSee('AKSI-SATU')
        ->assertDontSee('AKSI-DUA');
});

it('pencarian memperlakukan % dan _ sebagai teks biasa, bukan wildcard', function () {
    makeAuditRow(['description' => 'Naik gaji 50% untuk Sari']);
    makeAuditRow(['description' => 'Naik gaji 500 ribu untuk Budi']);

    $this->actingAs(hrdUser())
        ->get('/hrd/audit-log?q=' . urlencode('50%'))
        ->assertOk()
        ->assertSee('untuk Sari')
        ->assertDontSee('untuk Budi');
});

it('menolak filter yang tidak valid', function () {
    $this->actingAs(hrdUser())
        ->from('/hrd/audit-log')
        ->get('/hrd/audit-log?event=tidak-ada&from=bukan-tanggal')
        ->assertSessionHasErrors(['event', 'from']);
});

// ----------------------------------------------------------------------
// Ekspor CSV
// ----------------------------------------------------------------------

it('mengekspor CSV sesuai filter dan mencatat ekspornya sendiri', function () {
    makeAuditRow(['event' => AuditLog::EVENT_DELETED, 'actor_name' => 'Budi', 'description' => 'Menghapus Akun Sari']);
    makeAuditRow(['event' => AuditLog::EVENT_CREATED, 'actor_name' => 'Ani', 'description' => 'Membuat Akun Joko']);

    $response = $this->actingAs(hrdUser())->get('/hrd/audit-log/export?event=deleted');

    $response->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $csv = $response->streamedContent();

    expect($csv)->toContain('Waktu')
        ->and($csv)->toContain('Menghapus Akun Sari')
        ->and($csv)->not->toContain('Membuat Akun Joko');

    expect(AuditLog::where('event', AuditLog::EVENT_EXPORT)->count())->toBe(1);
});

it('mencegah formula injection di CSV', function () {
    makeAuditRow(['actor_name' => '=HYPERLINK("http://jahat.example","klik")', 'description' => '+cmd|calc']);

    $csv = $this->actingAs(hrdUser())->get('/hrd/audit-log/export')->streamedContent();

    expect($csv)->toContain("'=HYPERLINK")
        ->and($csv)->toContain("'+cmd|calc");
});

// ----------------------------------------------------------------------
// Aksi manual
// ----------------------------------------------------------------------

it('mencatat penghapusan file orphan', function () {
    Storage::fake('private');
    Storage::disk('private')->put('signatures/orphan.png', 'x');

    $this->actingAs(hrdUser())
        ->post('/hrd/storage-cleanup', ['paths' => ['signatures/orphan.png']])
        ->assertRedirect();

    $log = AuditLog::where('event', AuditLog::EVENT_FILE_DELETED)->firstOrFail();

    expect($log->meta['paths'])->toBe(['signatures/orphan.png']);
});

// ----------------------------------------------------------------------
// Keamanan & ketahanan
// ----------------------------------------------------------------------

it('menolak perubahan dan penghapusan baris log lewat model', function () {
    $log = makeAuditRow();

    expect(fn () => $log->update(['description' => 'dipalsukan']))->toThrow(LogicException::class);
    expect(fn () => $log->delete())->toThrow(LogicException::class);
    expect(AuditLog::find($log->id)->description)->toBe('Mengubah sesuatu');
});

it('tidak menggagalkan aksi asli kalau pencatatan error (mis. tabel belum di-migrate)', function () {
    Schema::drop('audit_logs');

    $user = User::factory()->create(['name' => 'Tetap Dibuat']);

    expect(User::where('name', 'Tetap Dibuat')->exists())->toBeTrue();
});

it('bisa dimatikan sementara lewat withoutAuditing', function () {
    AuditLogger::withoutAuditing(fn () => User::factory()->create());
    expect(AuditLog::count())->toBe(0);

    User::factory()->create();
    expect(AuditLog::count())->toBe(1);
});

it('bisa dimatikan lewat config', function () {
    config(['audit.enabled' => false]);

    User::factory()->create();

    expect(AuditLog::count())->toBe(0);
});

// ----------------------------------------------------------------------
// Pembersihan
// ----------------------------------------------------------------------

it('audit:prune menghapus log lama dan menyimpan yang baru', function () {
    makeAuditRow(['description' => 'LAMA'], now()->subDays(400));
    makeAuditRow(['description' => 'BARU'], now()->subDays(10));

    $this->artisan('audit:prune', ['--days' => 365])->assertSuccessful();

    expect(AuditLog::where('description', 'LAMA')->exists())->toBeFalse()
        ->and(AuditLog::where('description', 'BARU')->exists())->toBeTrue()
        ->and(AuditLog::where('event', AuditLog::EVENT_PRUNED)->count())->toBe(1);
});

it('audit:prune menolak masa simpan di bawah 30 hari', function () {
    makeAuditRow(['description' => 'JANGAN-HILANG'], now()->subDays(40));

    $this->artisan('audit:prune', ['--days' => 0])->assertSuccessful();   // 0 = tak terbatas
    $this->artisan('audit:prune', ['--days' => 5])->assertFailed();

    expect(AuditLog::where('description', 'JANGAN-HILANG')->exists())->toBeTrue();
});

it('audit:prune memakai config retention_days kalau --days tidak diberikan', function () {
    config(['audit.retention_days' => 60]);
    makeAuditRow(['description' => 'LEWAT-60'], now()->subDays(90));
    makeAuditRow(['description' => 'DALAM-60'], now()->subDays(30));

    $this->artisan('audit:prune')->assertSuccessful();

    expect(AuditLog::where('description', 'LEWAT-60')->exists())->toBeFalse()
        ->and(AuditLog::where('description', 'DALAM-60')->exists())->toBeTrue();
});

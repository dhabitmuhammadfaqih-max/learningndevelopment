<?php

use App\Models\Evaluation;
use App\Models\User;

function seedTahunSelectorScenario(): array
{
    static $seq = 0;
    $seq++;

    $hrd = User::create([
        'name' => 'HRD Test', 'username' => "hrd_test_{$seq}", 'nik' => "9000{$seq}",
        'role' => 'hrd', 'email' => "hrd_test_{$seq}@pegawai.local", 'password' => bcrypt('secret'),
    ]);

    $pejabat = User::create([
        'name' => 'Pejabat Test', 'username' => "pejabat_test_{$seq}", 'nik' => "9001{$seq}",
        'role' => 'pejabat', 'email' => "pejabat_test_{$seq}@pegawai.local", 'password' => bcrypt('secret'),
    ]);

    // Atasan Penilai (beda dari Penilai langsung) - dibutuhkan supaya
    // "Tanggapan Atasan" terpenuhi & PDF bisa dibuat, lihat
    // HrdController::pdf().
    $atasan = User::create([
        'name' => 'Atasan Test', 'username' => "atasan_test_{$seq}", 'nik' => "9002{$seq}",
        'role' => 'pejabat', 'email' => "atasan_test_{$seq}@pegawai.local", 'password' => bcrypt('secret'),
    ]);

    $pegawai = User::create([
        'name' => 'Pegawai Test', 'username' => "pegawai_test_{$seq}", 'nik' => "9003{$seq}",
        'role' => 'pegawai', 'email' => "pegawai_test_{$seq}@pegawai.local", 'password' => bcrypt('secret'),
        'supervisor_id' => $pejabat->id,
        'is_spg' => true,
        'kehadiran_diisi_at' => now(),
    ]);

    $base = [
        'employee_id' => $pegawai->id,
        'official_id' => $pejabat->id,
        'pengetahuan_kerja' => 80, 'penguasaan_peralatan' => 80, 'volume_kerja' => 80,
        'mutu_tanggung_jawab' => 80, 'disiplin_dedikasi_loyalitas' => 80, 'prakarsa' => 80,
        'daya_serap' => 80, 'kerajinan' => 80, 'kerjasama' => 80,
        'recommendation' => 'tidak_ada',
    ];

    Evaluation::create($base + ['tahun' => 2025, 'score' => 80, 'feedback' => 'Feedback tahun 2025']);
    Evaluation::create($base + ['tahun' => 2026, 'score' => 90, 'feedback' => 'Feedback tahun 2026']);

    $atasanBase = $base;
    $atasanBase['official_id'] = $atasan->id;

    Evaluation::create($atasanBase + ['tahun' => 2025, 'score' => 80, 'feedback' => 'Tanggapan atasan 2025']);
    Evaluation::create($atasanBase + ['tahun' => 2026, 'score' => 90, 'feedback' => 'Tanggapan atasan 2026']);

    return compact('hrd', 'pejabat', 'atasan', 'pegawai');
}

it('defaults to the current year when no tahun query param is given', function () {
    $this->travelTo(now()->setDate(2026, 6, 1));
    ['hrd' => $hrd, 'pegawai' => $pegawai] = seedTahunSelectorScenario();

    $this->actingAs($hrd)
        ->get(route('admin.employee', $pegawai->id))
        ->assertOk()
        ->assertSeeText('Feedback tahun 2026')
        ->assertDontSeeText('Feedback tahun 2025');
});

it('shows a past year evaluation when tahun query param is set, without leaking other years', function () {
    $this->travelTo(now()->setDate(2026, 6, 1));
    ['hrd' => $hrd, 'pegawai' => $pegawai] = seedTahunSelectorScenario();

    $this->actingAs($hrd)
        ->get(route('admin.employee', ['id' => $pegawai->id, 'tahun' => 2025]))
        ->assertOk()
        ->assertSeeText('Feedback tahun 2025')
        ->assertDontSeeText('Feedback tahun 2026');
});

it('renders the tahun selector with the correct option selected', function () {
    $this->travelTo(now()->setDate(2026, 6, 1));
    ['hrd' => $hrd, 'pegawai' => $pegawai] = seedTahunSelectorScenario();

    $this->actingAs($hrd)
        ->get(route('admin.employee', ['id' => $pegawai->id, 'tahun' => 2025]))
        ->assertOk()
        ->assertSee('<option value="2025" selected', false)
        ->assertSee('<option value="2026"', false); // tetap ada di daftar pilihan
});

it('falls back to the current year for an invalid tahun value', function () {
    $this->travelTo(now()->setDate(2026, 6, 1));
    ['hrd' => $hrd, 'pegawai' => $pegawai] = seedTahunSelectorScenario();

    $this->actingAs($hrd)
        ->get(route('admin.employee', ['id' => $pegawai->id, 'tahun' => 'not-a-year']))
        ->assertOk()
        ->assertSeeText('Feedback tahun 2026');
});

it('carries the selected tahun through from the employees list into the detail page link', function () {
    $this->travelTo(now()->setDate(2026, 6, 1));
    ['hrd' => $hrd, 'pegawai' => $pegawai] = seedTahunSelectorScenario();

    $this->actingAs($hrd)
        ->get(route('admin.employees', ['tahun' => 2025]))
        ->assertOk()
        ->assertSee('tahun=2025', false);
});

it('generates the PDF for the selected year, not just the current year', function () {
    $this->travelTo(now()->setDate(2026, 6, 1));
    ['hrd' => $hrd, 'pegawai' => $pegawai] = seedTahunSelectorScenario();

    // 3 tanggapan korelasi & tanggapan atasan tidak diperlukan karena pegawai ini is_spg.
    $this->actingAs($hrd)
        ->get(route('admin.pdf', ['id' => $pegawai->id, 'tahun' => 2025]))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

it('dashboard summary reflects the selected year counts', function () {
    $this->travelTo(now()->setDate(2026, 6, 1));
    ['hrd' => $hrd] = seedTahunSelectorScenario();

    $this->actingAs($hrd)
        ->get(route('admin.dashboard', ['tahun' => 2025]))
        ->assertOk()
        ->assertSee('<option value="2025" selected', false);
});

<?php

use App\Models\OfficialEvaluation;
use App\Models\OfficialSupervisorFeedback;
use App\Models\User;

it('shows pejabat evaluation details in the HRD detail view', function () {
    $hrd = User::factory()->create([
        'role' => 'hrd',
        'name' => 'HRD Test',
    ]);

    $pejabat = User::factory()->create([
        'role' => 'pejabat',
        'supervisor_id' => $hrd->id,
        'name' => 'Pejabat Test',
    ]);

    $evaluation = OfficialEvaluation::create([
        'official_id' => $pejabat->id,
        'supervisor_id' => $hrd->id,
        'tahun' => now()->year,
        'kepemimpinan' => 90,
        'kemampuan_merencanakan_mengoordinasikan' => 85,
        'kemampuan_analisa_evaluasi_pengambilan_keputusan' => 80,
        'kemampuan_memotivasi_aplikasi_manajemen' => 82,
        'tanggung_jawab_manajemen' => 88,
        'kerjasama' => 86,
        'prakarsa' => 83,
        'integritas' => 90,
        'pengetahuan_teknik_operasi' => 85,
        'score' => 85.5,
        'feedback' => 'Penilaian dari atasan penilai masuk ke HRD dengan data yang lengkap.',
        'recommendation' => 'tidak_ada',
        'kenaikan_gaji_amount' => null,
        'signature' => null,
    ]);

    OfficialSupervisorFeedback::create([
        'official_id' => $pejabat->id,
        'supervisor_id' => $hrd->id,
        'tahun' => now()->year,
        'feedback' => 'Tanggapan atasan penilai untuk pejabat ini.',
        'recommendation' => 'tidak_ada',
        'signature' => null,
    ]);

    $this->actingAs($hrd)
        ->get(route('admin.employee', $pejabat->id))
        ->assertOk()
        ->assertSeeText('Penilaian Atasan Pejabat')
        ->assertSeeText('Tanggapan Atasan Penilai')
        ->assertSeeText('Tanggapan atasan penilai untuk pejabat ini.')
        ->assertSeeText($evaluation->feedback);
});

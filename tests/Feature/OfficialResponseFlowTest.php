<?php

use App\Models\OfficialEvaluation;
use App\Models\OfficialSupervisorFeedback;
use App\Models\User;

it('requires an official supervisor response before the official may respond to the evaluation', function () {
    $supervisor = User::factory()->create([
        'role' => 'hrd',
        'name' => 'Supervisor Test',
    ]);

    $official = User::factory()->create([
        'role' => 'pejabat',
        'supervisor_id' => $supervisor->id,
        'name' => 'Pejabat Test',
    ]);

    $evaluation = OfficialEvaluation::create([
        'official_id' => $official->id,
        'supervisor_id' => $supervisor->id,
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
        'score' => 85,
        'feedback' => 'Penilaian dari atasan penilai masuk dengan lengkap.',
        'recommendation' => 'tidak_ada',
        'signature' => 'signatures/test.png',
    ]);

    $this->actingAs($official)
        ->post(route('official.evaluation.respond', $evaluation->id), [
            'employee_response' => 'Saya menerima hasil penilaian ini dengan baik.',
            'employee_signature' => 'data:image/png;base64,' . base64_encode('mock-signature'),
        ])
        ->assertSessionHas('error', 'Anda belum dapat mengisi tanggapan. Tanggapan dari Atasan Penilai belum diselesaikan.');
});

it('allows the official to respond after the official supervisor feedback is submitted', function () {
    $supervisor = User::factory()->create([
        'role' => 'hrd',
        'name' => 'Supervisor Test',
    ]);

    $official = User::factory()->create([
        'role' => 'pejabat',
        'supervisor_id' => $supervisor->id,
        'name' => 'Pejabat Test',
    ]);

    $evaluation = OfficialEvaluation::create([
        'official_id' => $official->id,
        'supervisor_id' => $supervisor->id,
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
        'score' => 85,
        'feedback' => 'Penilaian dari atasan penilai masuk dengan lengkap.',
        'recommendation' => 'tidak_ada',
        'signature' => 'signatures/test.png',
    ]);

    OfficialSupervisorFeedback::create([
        'official_id' => $official->id,
        'supervisor_id' => $supervisor->id,
        'tahun' => now()->year,
        'feedback' => 'Tanggapan atasan penilai sudah diberikan.',
        'recommendation' => 'tidak_ada',
        'signature' => 'signatures/supervisor-feedback.png',
    ]);

    $this->actingAs($official)
        ->post(route('official.evaluation.respond', $evaluation->id), [
            'employee_response' => 'Saya menerima hasil penilaian ini dengan baik.',
            'employee_signature' => 'data:image/png;base64,' . base64_encode('mock-signature'),
        ])
        ->assertSessionHas('success', 'Tanggapan Anda atas penilaian berhasil dikirim.');

    $evaluation->refresh();
    expect($evaluation->employee_response)->toBe('Saya menerima hasil penilaian ini dengan baik.');
});

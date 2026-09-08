<x-dashboard-layout title="Lihat Pegawai">

@include('admin.partials.styles')

<div id="hrd-page">

<div class="hero-banner">
    <div>
        <h2>Lihat Pegawai</h2>
        <p>Kelola kehadiran (izin, sakit, alpa, terlambat) pegawai. Vendor &amp; Status diambil otomatis dari data akun. Klik salah satu kartu untuk melihat detail penilaian tahun {{ $tahun }}.</p>
    </div>

    <x-tahun-selector :tahun="$tahun" :options="$availableTahun" />
</div>

<?php if ($employees->isEmpty()): ?>

    <p class="empty">Belum ada data pegawai.</p>

<?php else: ?>

    <div class="filter-bar">
        <input type="text"
               id="employee-search"
               placeholder="Cari nama, username, atau unit kerja..."
               oninput="filterEmployees()">

        <select id="employee-status-filter" onchange="filterEmployees()">
            <option value="">Semua Status</option>
            <option value="ready">Sudah bisa di-print PDF</option>
            <option value="not-ready">Belum bisa di-print PDF</option>
        </select>

        <select id="employee-spg-filter" onchange="filterEmployees()">
            <option value="">Semua (SPG & Non-SPG)</option>
            <option value="spg">Hanya SPG</option>
            <option value="non-spg">Hanya Non-SPG</option>
        </select>

        <button type="button"
                class="btn-reset-filter"
                onclick="resetEmployeeFilter()">
            Reset
        </button>

        <span class="filter-count" id="employee-filter-count"></span>
    </div>

    <div class="grid" id="employees-grid">

        <?php foreach ($employees as $employee): ?>

            <?php
                // Tanggapan Atasan bisa datang dari salah satu dari 2 sumber
                // (sama seperti pengecekan di HrdController::pdf()):
                // 1) penilaian tambahan dari Atasan Penilai (evaluations_count
                //    lebih besar dari primary_evaluation_count), ATAU
                // 2) SupervisorFeedback dari Atasan Pejabat.
                $hasPrimaryEvaluation =
                    $employee->primary_evaluation_count > 0
                    // Penilai (users.supervisor_id) yang ditugaskan ke
                    // pegawai ini menilai secara manual - lihat
                    // User::penilaianUtamaManual() & HrdController::pdf().
                    || $employee->penilaianUtamaManual();
                $hasAtasanEvaluation =
                    ($employee->evaluations_count > $employee->primary_evaluation_count)
                    || $employee->supervisor_feedbacks_count > 0
                    // Kalau Atasan Pejabat pegawai ini menilai secara
                    // manual, tidak perlu menunggu Tanggapan Atasan di
                    // sistem - atasannya mengisi manual di luar aplikasi.
                    // Lihat User::tanggapanAtasanManual() & HrdController::pdf().
                    || $employee->tanggapanAtasanManual();

                // Pegawai wajib mengisi tanggapan atas penilaiannya sendiri
                // (employee_response) sebelum PDF boleh dicetak - lihat
                // Evaluation::employee_response & HrdController::pdf().
                // Dilewati kalau penilaian utamanya manual (tidak ada baris
                // Evaluation untuk ditanggapi lewat sistem), sama seperti
                // $hasPrimaryEvaluation di atas.
                $hasEmployeeResponse =
                    $employee->primary_evaluation_responded_count > 0
                    || $employee->penilaianUtamaManual();

                // Status tanda tangan HRD - lihat HrdController::signAsHrd().
                // Kalau Penilai/Atasan pegawai ini menilai secara manual dan
                // baris Evaluation-nya SUDAH ada, HRD boleh menandatangani
                // KAPAN SAJA tanpa menunggu tanda tangan pegawai lebih dulu -
                // jadi "siap tanda tangan" dihitung dari primary_evaluation_count,
                // bukan primary_evaluation_employee_signed_count.
                $manualBypassSign = $employee->penilaianUtamaManual() || $employee->tanggapanAtasanManual();
                $hrdAlreadySigned = $employee->primary_evaluation_hrd_signed_count > 0;
                $canHrdSign = ! $hrdAlreadySigned
                    && ($manualBypassSign
                        ? $employee->primary_evaluation_count > 0
                        : $employee->primary_evaluation_employee_signed_count > 0);

                // Checklist "sudah bertemu & evaluasi" dari pegawai & penilai
                // juga harus lengkap - lihat User::checklistPertemuanLengkap()
                // & HrdController::pdf().
                $isReady =
                    ($employee->is_spg || $employee->feedbacks_received_count >= 3)
                    && $hasPrimaryEvaluation
                    && $hasAtasanEvaluation
                    && $hasEmployeeResponse
                    && $employee->checklistPertemuanLengkap($tahun);
            ?>

            <div class="card employee-card"
                 data-search="{{ strtolower(($employee->name ?? '') . ' ' . ($employee->username ?? '') . ' ' . ($employee->unit_kerja ?? '')) }}"
                 data-status-filter="{{ $isReady ? 'ready' : 'not-ready' }}"
                 data-spg-filter="{{ $employee->is_spg ? 'spg' : 'non-spg' }}">

                <h3>{{ $employee->name }}</h3>

                <p>
                    {{ $employee->username }}

                    <span class="role-badge role-{{ $employee->role }}">
                        {{ strtoupper($employee->role) }}
                    </span>

                    <?php if ($employee->is_spg): ?>
                        <span class="role-badge role-spg">SPG</span>
                    <?php endif; ?>

                    <?php if ($employee->tanggapanAtasanManual()): ?>
                        <span class="role-badge role-spg" title="Atasan Pejabat akun ini menilai secara manual">Atasan Manual</span>
                    <?php endif; ?>
                </p>

                <p style="margin-top:-12px; font-size:12px;">
                    Izin {{ $employee->jumlah_izin ?? 0 }}
                    &middot;
                    Sakit {{ $employee->jumlah_sakit ?? 0 }}
                    &middot;
                    Alpa {{ $employee->jumlah_alpa ?? 0 }}
                    &middot;
                    Terlambat {{ $employee->jumlah_terlambat ?? 0 }}
                    &middot;
                    {{ $employee->menit_terlambat_formatted }}
                </p>

                <p class="badge-row" style="margin-top:8px;">
                    @if($employee->vendor)
                        <span class="vendor-badge">{{ $employee->vendor }}</span>
                    @endif
                    @if($employee->status)
                        <span class="employment-status-badge">{{ $employee->status }}</span>
                    @endif
                </p>

                <p class="badge-row" style="margin-top:4px;">
                    <span class="pdf-badge {{ $employee->kehadiranSudahDiisiHrd() ? 'pdf-badge-ready' : 'pdf-badge-not-ready' }}">
                        {{ $employee->kehadiranSudahDiisiHrd() ? '✓' : '—' }} Kehadiran
                    </span>

                    @if($hrdAlreadySigned)
                        <span class="pdf-badge pdf-badge-ready">✓ Sudah Ditandatangani HRD</span>
                    @elseif($canHrdSign)
                        <span class="pdf-badge pdf-badge-ready">✓ Siap Ditandatangani HRD</span>
                    @else
                        <span class="pdf-badge pdf-badge-not-ready">Belum Siap Ditandatangani HRD</span>
                    @endif

                    <span class="pdf-badge {{ $isReady ? 'pdf-badge-ready' : 'pdf-badge-not-ready' }}">
                        {{ $isReady ? '✓ Siap PDF' : 'Belum siap PDF' }}
                    </span>
                </p>

                <br>

                <a href="{{ route('admin.employee', ['id' => $employee->id, 'tahun' => $tahun]) }}">
                    {{ $employee->kehadiranSudahDiisiHrd() ? 'Lihat Detail' : 'Isi Kehadiran' }}
                </a>
            </div>

        <?php endforeach; ?>

        <div class="no-results"
             id="employees-no-results"
             style="display:none;">
            Tidak ada pegawai yang cocok dengan pencarian/filter.
        </div>

    </div>

<?php endif; ?>

<script>
    function filterEmployees() {
        const input = document.getElementById('employee-search');
        const status = document.getElementById('employee-status-filter');
        const spg = document.getElementById('employee-spg-filter');
        const count = document.getElementById('employee-filter-count');
        const noResults = document.getElementById('employees-no-results');

        if (!input || !status || !spg) {
            return;
        }

        const query = input.value.trim().toLowerCase();
        const statusValue = status.value;
        const spgValue = spg.value;

        const cards = document.querySelectorAll(
            '#employees-grid .employee-card'
        );

        let visible = 0;

        cards.forEach(function (card) {
            const matchesSearch =
                !query ||
                (card.dataset.search || '').includes(query);

            const matchesStatus =
                !statusValue ||
                card.dataset.statusFilter === statusValue;

            const matchesSpg =
                !spgValue ||
                card.dataset.spgFilter === spgValue;

            const show =
                matchesSearch &&
                matchesStatus &&
                matchesSpg;

            card.style.display =
                show ? '' : 'none';

            if (show) {
                visible++;
            }
        });

        if (noResults) {
            noResults.style.display =
                visible === 0 ? '' : 'none';
        }

        if (count) {
            count.textContent =
                visible + ' dari ' + cards.length + ' pegawai ditampilkan';
        }
    }

    function resetEmployeeFilter() {
        const input = document.getElementById('employee-search');
        const status = document.getElementById('employee-status-filter');
        const spg = document.getElementById('employee-spg-filter');

        if (input) {
            input.value = '';
        }

        if (status) {
            status.value = '';
        }

        if (spg) {
            spg.value = '';
        }

        filterEmployees();
    }

    document.addEventListener('DOMContentLoaded', function () {
        filterEmployees();
    });
</script>

</div>

</x-dashboard-layout>
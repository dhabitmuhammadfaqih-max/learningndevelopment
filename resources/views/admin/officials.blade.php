<x-dashboard-layout title="Lihat Pejabat">

@include('admin.partials.styles')

<div id="hrd-page">

<div class="hero-banner">
    <div>
        <h2>Lihat Pejabat</h2>
        <p>Kehadiran &amp; status kontrak pejabat. Vendor &amp; Status diambil otomatis dari data akun. Data untuk tahun {{ $tahun }}.</p>
    </div>

    <x-tahun-selector :tahun="$tahun" :options="$availableTahun" />
</div>

<?php if ($officials->isEmpty()): ?>

    <p class="empty">Belum ada data pejabat.</p>

<?php else: ?>

    <div class="filter-bar">
        <input type="text"
               id="official-search"
               placeholder="Cari nama, username, atau unit kerja..."
               oninput="filterOfficials()">

        <select id="official-status-filter" onchange="filterOfficials()">
            <option value="">Semua Status</option>
            <option value="ready">Sudah bisa di-print PDF</option>
            <option value="not-ready">Belum bisa di-print PDF</option>
        </select>

        <select id="official-unit-filter" onchange="filterOfficials()">
            <option value="">Semua Unit</option>
            <?php foreach ($officialUnits as $unit): ?>
                <option value="{{ strtolower($unit) }}">{{ $unit }}</option>
            <?php endforeach; ?>
        </select>

        <button type="button"
                class="btn-reset-filter"
                onclick="resetOfficialFilter()">
            Reset
        </button>

        <span class="filter-count" id="official-filter-count"></span>
    </div>

    <div class="grid" id="officials-grid">

        <?php foreach ($officials as $official): ?>

            <?php
                // Syarat "siap di-print PDF" pejabat: sudah menerima tanggapan
                // korelasi minimal MIN_TANGGAPAN_KORELASI_PEJABAT, sudah ada
                // penilaian dari Atasan Penilai (official_evaluation_count),
                // checklist pertemuan pejabat & atasan sudah lengkap, dan
                // HRD sudah tanda tangan (official_evaluation_hrd_signed_count).
                // Harus konsisten dengan HrdController::officialsIndex()/
                // officialPdf() supaya badge tidak salah.
                // tanggapanPenilaiPejabatManual() sekarang juga true kalau
                // Penilai (users.supervisor_id) pejabat ini menilai SEMUA
                // bawahannya secara manual (User::menilaiSecaraManual()) -
                // lihat catatan di HrdController::officialPdf(). Kalau
                // manual, tidak ada baris OfficialEvaluation sama sekali,
                // jadi syarat evaluation_count & hrd_signed_count dilewati
                // juga (sama seperti officialPdf() yang skip syarat tanda
                // tangan HRD kalau $evaluation belum ada).
                $penilaiManual = $official->tanggapanPenilaiPejabatManual();

                // Status tanda tangan HRD - lihat
                // HrdController::signAsHrdOfficial(). Kalau mode manual dan
                // baris OfficialEvaluation-nya SUDAH ada, HRD boleh
                // menandatangani KAPAN SAJA tanpa menunggu tanda tangan
                // pejabat lebih dulu - jadi "siap tanda tangan" dihitung
                // dari official_evaluation_count, bukan
                // official_evaluation_employee_signed_count. Kalau mode
                // manual tapi belum ada baris sama sekali, belum ada yang
                // bisa ditandatangani sampai penilai mengisi datanya.
                $hrdAlreadySignedOfficial = $official->official_evaluation_hrd_signed_count > 0;
                $canHrdSignOfficial = ! $hrdAlreadySignedOfficial
                    && ($penilaiManual
                        ? $official->official_evaluation_count > 0
                        : $official->official_evaluation_employee_signed_count > 0);

                // Pejabat wajib mengisi tanggapan atas penilaiannya sendiri
                // (employee_response) sebelum PDF boleh dicetak - lihat
                // OfficialEvaluation::employee_response &
                // HrdController::officialPdf(). Dilewati kalau mode manual,
                // sama seperti syarat lain di atas.
                $isReady =
                    $official->feedbacks_received_count >= \App\Models\User::MIN_TANGGAPAN_KORELASI_PEJABAT
                    && ($official->official_evaluation_count > 0 || $penilaiManual)
                    && $official->checklistPertemuanPejabatLengkap($tahun)
                    && ($official->official_evaluation_hrd_signed_count > 0 || $penilaiManual)
                    && ($official->official_evaluation_responded_count > 0 || $penilaiManual);

                $unitLabel = $official->unit_kerja ?: 'Tanpa Unit';
            ?>

            <div class="card official-card"
                 data-search="{{ strtolower(($official->name ?? '') . ' ' . ($official->username ?? '') . ' ' . $unitLabel) }}"
                 data-status-filter="{{ $isReady ? 'ready' : 'not-ready' }}"
                 data-unit-filter="{{ strtolower($unitLabel) }}">

                <h3>{{ $official->name }}</h3>

                <p>
                    {{ $official->username }}

                    <span class="role-badge role-{{ $official->role }}">
                        {{ strtoupper($official->role) }}
                    </span>
                </p>

                <p style="margin-top:-12px; font-size:12px;">
                    Unit {{ $unitLabel }}
                    &middot;
                    Izin {{ $official->jumlah_izin ?? 0 }}
                    &middot;
                    Sakit {{ $official->jumlah_sakit ?? 0 }}
                    &middot;
                    Alpa {{ $official->jumlah_alpa ?? 0 }}
                    &middot;
                    Terlambat {{ $official->jumlah_terlambat ?? 0 }}
                    &middot;
                    {{ $official->menit_terlambat_formatted }}
                </p>

                <p class="badge-row" style="margin-top:8px;">
                    @if($official->vendor)
                        <span class="vendor-badge">{{ $official->vendor }}</span>
                    @endif
                    @if($official->status)
                        <span class="employment-status-badge">{{ $official->status }}</span>
                    @endif
                </p>

                <p class="badge-row" style="margin-top:4px;">
                    <span class="pdf-badge {{ $official->kehadiranSudahDiisiHrd() ? 'pdf-badge-ready' : 'pdf-badge-not-ready' }}">
                        {{ $official->kehadiranSudahDiisiHrd() ? '✓' : '—' }} Kehadiran
                    </span>

                    @if($hrdAlreadySignedOfficial)
                        <span class="pdf-badge pdf-badge-ready">✓ Sudah Ditandatangani HRD</span>
                    @elseif($canHrdSignOfficial)
                        <span class="pdf-badge pdf-badge-ready">✓ Siap Ditandatangani HRD</span>
                    @else
                        <span class="pdf-badge pdf-badge-not-ready">Belum Siap Ditandatangani HRD</span>
                    @endif

                    <span class="pdf-badge {{ $isReady ? 'pdf-badge-ready' : 'pdf-badge-not-ready' }}">
                        {{ $isReady ? '✓ Siap PDF' : 'Belum siap PDF' }}
                    </span>
                </p>

                <br>

                <a href="{{ route('admin.employee', ['id' => $official->id, 'tahun' => $tahun]) }}">
                    {{ $official->kehadiranSudahDiisiHrd() ? 'Lihat Detail' : 'Isi Kehadiran' }}
                </a>
            </div>

        <?php endforeach; ?>

        <div class="no-results"
             id="officials-no-results"
             style="display:none;">
            Tidak ada pejabat yang cocok dengan pencarian/filter.
        </div>

    </div>

<?php endif; ?>

<script>
    function filterOfficials() {
        const input = document.getElementById('official-search');
        const status = document.getElementById('official-status-filter');
        const unit = document.getElementById('official-unit-filter');
        const count = document.getElementById('official-filter-count');
        const noResults = document.getElementById('officials-no-results');

        if (!input || !status || !unit) {
            return;
        }

        const query = input.value.trim().toLowerCase();
        const statusValue = status.value;
        const unitValue = unit.value;

        const cards = document.querySelectorAll(
            '#officials-grid .official-card'
        );

        let visible = 0;

        cards.forEach(function (card) {
            const matchesSearch =
                !query ||
                (card.dataset.search || '').includes(query);

            const matchesStatus =
                !statusValue ||
                card.dataset.statusFilter === statusValue;

            const matchesUnit =
                !unitValue ||
                card.dataset.unitFilter === unitValue;

            const show =
                matchesSearch &&
                matchesStatus &&
                matchesUnit;

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
                visible + ' dari ' + cards.length + ' pejabat ditampilkan';
        }
    }

    function resetOfficialFilter() {
        const input = document.getElementById('official-search');
        const status = document.getElementById('official-status-filter');
        const unit = document.getElementById('official-unit-filter');

        if (input) {
            input.value = '';
        }

        if (status) {
            status.value = '';
        }

        if (unit) {
            unit.value = '';
        }

        filterOfficials();
    }

    document.addEventListener('DOMContentLoaded', function () {
        filterOfficials();
    });
</script>

</div>

</x-dashboard-layout>

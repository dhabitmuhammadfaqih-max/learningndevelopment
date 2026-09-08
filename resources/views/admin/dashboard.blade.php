<x-dashboard-layout title="Dashboard HRD">

@include('admin.partials.styles')

<style>
    #hrd-page .stat-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 16px;
        margin-bottom: 8px;
    }

    #hrd-page .stat-card {
        background: white;
        border-radius: 18px;
        padding: 22px 24px;
        border: 1px solid #f1f5f9;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04), 0 10px 24px -16px rgba(15, 23, 42, 0.25);
    }

    #hrd-page .stat-card .stat-label {
        font-size: 12.5px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .03em;
        color: #94a3b8;
        margin: 0 0 10px;
    }

    #hrd-page .stat-card .stat-value {
        font-size: 32px;
        font-weight: 800;
        color: #1e293b;
        line-height: 1;
        margin: 0 0 6px;
    }

    #hrd-page .stat-card .stat-sub {
        font-size: 13px;
        color: #64748b;
        margin: 0;
    }

    #hrd-page .stat-card .stat-breakdown {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-top: 12px;
    }

    #hrd-page .progress-track {
        margin-top: 12px;
        height: 8px;
        border-radius: 999px;
        background: #f1f5f9;
        overflow: hidden;
    }

    #hrd-page .progress-fill {
        height: 100%;
        background: #22c55e;
        border-radius: 999px;
    }

    #hrd-page .quick-links {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 24px;
    }

    #hrd-page .quick-links a {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 10px 18px;
        background: white;
        color: #2563eb;
        text-decoration: none;
        border-radius: 999px;
        font-size: 13px;
        font-weight: 600;
        border: 1px solid #eef2f7;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04), 0 6px 16px -10px rgba(15, 23, 42, 0.3);
    }

    #hrd-page .quick-links a:hover {
        background: #eff6ff;
    }
</style>

<div id="hrd-page">

<div class="hero-banner">
    <div>
        <h2>Selamat datang, {{ auth()->user()->name }} 👋</h2>
        <p>Ringkasan akun, kehadiran, dan status penilaian pegawai &amp; pejabat untuk tahun {{ $tahun }}.</p>
    </div>

    <x-tahun-selector :tahun="$tahun" :options="$availableTahun" />
</div>

@if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="alert alert-error">
        {{ session('error') }}
    </div>
@endif

@php
    $totalAkun = $accountsByRole->sum();
    $employeeReadyPercent = $employeeTotal > 0 ? round(($employeeReadyCount / $employeeTotal) * 100) : 0;
    $officialReadyPercent = $officialTotal > 0 ? round(($officialReadyCount / $officialTotal) * 100) : 0;
@endphp

<div class="stat-grid">

    <div class="stat-card">
        <p class="stat-label">Total Akun</p>
        <p class="stat-value">{{ $totalAkun }}</p>
        <p class="stat-sub">Semua role: pegawai, pejabat, HRD.</p>

        <div class="stat-breakdown">
            <span class="role-badge role-pegawai">{{ $accountsByRole->get('pegawai', 0) }} Pegawai</span>
            <span class="role-badge role-pejabat">{{ $accountsByRole->get('pejabat', 0) }} Pejabat</span>
            <span class="role-badge role-hrd">{{ $accountsByRole->get('hrd', 0) }} HRD</span>
        </div>
    </div>

    <div class="stat-card">
        <p class="stat-label">Pegawai Siap Di-print PDF</p>
        <p class="stat-value">{{ $employeeReadyCount }} <span style="font-size:16px; font-weight:600; color:#94a3b8;">/ {{ $employeeTotal }}</span></p>
        <p class="stat-sub">{{ $employeeTotal - $employeeReadyCount }} pegawai masih belum lengkap datanya.</p>

        <div class="progress-track">
            <div class="progress-fill" style="width: {{ $employeeReadyPercent }}%;"></div>
        </div>
    </div>

    <div class="stat-card">
        <p class="stat-label">Pejabat Siap Di-print PDF</p>
        <p class="stat-value">{{ $officialReadyCount }} <span style="font-size:16px; font-weight:600; color:#94a3b8;">/ {{ $officialTotal }}</span></p>
        <p class="stat-sub">{{ $officialTotal - $officialReadyCount }} pejabat masih belum lengkap datanya.</p>

        <div class="progress-track">
            <div class="progress-fill" style="width: {{ $officialReadyPercent }}%;"></div>
        </div>
    </div>

    <div class="stat-card">
        <p class="stat-label">Siap Ditandatangani HRD</p>
        <p class="stat-value">{{ $employeeReadyToSignCount + $officialReadyToSignCount }}</p>
        <p class="stat-sub">
            {{ $employeeReadyToSignCount }} pegawai &amp; {{ $officialReadyToSignCount }} pejabat sudah tanda tangan sendiri, menunggu tanda tangan HRD.
        </p>
    </div>

</div>

<div class="quick-links">
    <a href="{{ route('admin.employees') }}">&#128100; Lihat Pegawai</a>
    <a href="{{ route('admin.officials') }}">&#129489;&#8205;&#128188; Lihat Pejabat</a>
    <a href="{{ route('admin.accounts') }}">&#128203; Semua Akun</a>
    <a href="{{ route('admin.storage-cleanup') }}">&#128465;&#65039; Bersihkan File Lama</a>
</div>

</div>

<script>
    // AJAX polling: cek berkala apakah ada perubahan di ringkasan sistem
    // (penilaian baru, akun baru, dst), lalu reload otomatis. Lihat
    // HrdController::statusVersion() untuk pola/alasannya.
    (function () {
        const POLL_INTERVAL_MS = 5000;
        const STATUS_URL = '{{ route('admin.dashboard.status-version', ['tahun' => $tahun ?? null]) }}';
        let currentVersion = null;

        function isUserTyping() {
            const active = document.activeElement;
            if (!active) return false;
            const tag = active.tagName;
            return tag === 'TEXTAREA' || tag === 'INPUT' || tag === 'CANVAS';
        }

        function poll() {
            // STATUS_URL sudah mengandung '?tahun=...', jadi anti-cache
            // param di sini pakai '&', bukan '?', supaya query string-nya
            // tetap valid.
            fetch(STATUS_URL + '&t=' + Date.now(), { headers: { 'Accept': 'application/json', 'ngrok-skip-browser-warning': 'true' }, cache: 'no-store' })
                .then(res => res.ok ? res.json() : null)
                .then(data => {
                    if (!data) return;
                    if (currentVersion === null) {
                        currentVersion = data.version;
                        return;
                    }
                    if (data.version !== currentVersion) {
                        if (isUserTyping()) return;
                        window.location.reload();
                    }
                })
                .catch((err) => console.error('status-version polling error:', err));
        }

        setInterval(poll, POLL_INTERVAL_MS);
    })();
</script>

</x-dashboard-layout>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Atasan</title>

    <style>
        body {
            font-family: Arial;
            background: #f5f5f5;
            padding: 30px;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        button {
            padding: 10px 20px;
            background: #111;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        /* ===== Toolbar: search + filter ===== */
        .toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin: 20px 0;
        }

        .toolbar input[type="text"] {
            flex: 1;
            min-width: 200px;
            padding: 10px 14px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-family: Arial;
            font-size: 14px;
            box-sizing: border-box;
        }

        .toolbar select {
            padding: 10px 14px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-family: Arial;
            font-size: 14px;
            background: white;
        }

        /* ===== Badge tipe akun ===== */
        .type-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: .3px;
        }

        .type-karyawan {
            background: #e0e7ff;
            color: #3730a3;
        }

        .type-pejabat {
            background: #fef3c7;
            color: #92400e;
        }

        .status-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 11px;
            margin-left: 6px;
        }

        .status-sudah {
            background: #dcfce7;
            color: #166534;
        }

        .status-belum {
            background: #f3f4f6;
            color: #6b7280;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 16px;
            margin-top: 10px;
        }

        .card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .card strong {
            display: block;
            margin-bottom: 8px;
            font-size: 16px;
        }

        .card .badges {
            margin-bottom: 12px;
        }

        .card small {
            display: block;
            color: #888;
            margin-bottom: 12px;
        }

        .card a {
            display: inline-block;
            padding: 8px 16px;
            background: #111;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 14px;
        }

        .card a:hover {
            background: #333;
        }

        .empty {
            color: #999;
            margin-top: 20px;
        }
    </style>
</head>

<body>

<div class="topbar">
    <h1>Dashboard Atasan Pejabat</h1>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit">Logout</button>
    </form>
</div>

<p style="color:#555;">
    Selamat datang, <strong>{{ auth()->user()->name }}</strong>
</p>

@if(session('success'))
    <div style="padding:12px 16px; border-radius:6px; margin-top:16px; background:#dcfce7; color:#166534;">
        {{ session('success') }}
    </div>
@endif

@php
    // Gabungkan karyawan & pejabat jadi satu daftar supaya bisa di-search/filter
    // bareng, masing-masing ditandai "type" untuk keperluan badge & filter JS.
    $items = collect();

    foreach ($employees as $employee) {
        $items->push([
            'id'     => $employee->id,
            'name'   => $employee->name,
            'sub'    => $employee->unit_kerja,
            'type'   => 'karyawan',
            'status' => null, // status penilaian karyawan tidak ditampilkan di sini
            'url'    => route('supervisor.employee', $employee->id),
        ]);
    }

    foreach ($pejabatBinaan as $pejabat) {
        $items->push([
            'id'     => $pejabat->id,
            'name'   => $pejabat->name,
            'sub'    => $pejabat->jabatan ?? $pejabat->unit_kerja,
            'type'   => 'pejabat',
            'status' => $pejabat->evaluated_count > 0 ? 'sudah' : 'belum',
            'url'    => route('supervisor.official', $pejabat->id),
        ]);
    }
@endphp

<div class="toolbar">
    <input type="text" id="search-input" placeholder="Cari nama...">

    <select id="filter-type">
        <option value="semua">Semua Tipe</option>
        <option value="karyawan">Karyawan</option>
        <option value="pejabat">Pejabat</option>
    </select>

    <select id="filter-status">
        <option value="semua">Semua Status</option>
        <option value="sudah">Pejabat &mdash; Sudah Dinilai</option>
        <option value="belum">Pejabat &mdash; Belum Dinilai</option>
    </select>
</div>

@if($items->isEmpty())

    <p class="empty">Belum ada data karyawan maupun pejabat binaan.</p>

@else

    <div class="grid" id="items-grid">

        @foreach($items as $item)

            <div class="card"
                 data-name="{{ strtolower($item['name']) }}"
                 data-type="{{ $item['type'] }}"
                 data-status="{{ $item['status'] ?? '' }}">

                <strong>{{ $item['name'] }}</strong>

                <div class="badges">
                    @if($item['type'] === 'karyawan')
                        <span class="type-badge type-karyawan">Karyawan</span>
                    @else
                        <span class="type-badge type-pejabat">Pejabat</span>
                        @if($item['status'] === 'sudah')
                            <span class="status-badge status-sudah">Sudah Dinilai</span>
                        @else
                            <span class="status-badge status-belum">Belum Dinilai</span>
                        @endif
                    @endif
                </div>

                @if($item['sub'])
                    <small>{{ $item['sub'] }}</small>
                @endif

                <a href="{{ $item['url'] }}">
                    {{ $item['type'] === 'karyawan' ? 'Lihat Penilaian' : 'Nilai Pejabat' }}
                </a>
            </div>

        @endforeach

    </div>

    <p class="empty" id="no-results" style="display:none;">Tidak ada hasil yang cocok.</p>

@endif

<script>
    const searchInput  = document.getElementById('search-input');
    const filterType   = document.getElementById('filter-type');
    const filterStatus = document.getElementById('filter-status');
    const cards        = document.querySelectorAll('#items-grid .card');
    const noResults    = document.getElementById('no-results');

    function applyFilters() {
        const keyword = searchInput.value.trim().toLowerCase();
        const type    = filterType.value;
        const status  = filterStatus.value;

        let visibleCount = 0;

        cards.forEach(function (card) {
            const matchesName   = card.dataset.name.includes(keyword);
            const matchesType   = (type === 'semua') || (card.dataset.type === type);
            const matchesStatus = (status === 'semua') || (card.dataset.status === status);

            const visible = matchesName && matchesType && matchesStatus;
            card.style.display = visible ? '' : 'none';

            if (visible) visibleCount++;
        });

        if (noResults) {
            noResults.style.display = visibleCount === 0 ? 'block' : 'none';
        }
    }

    if (searchInput) {
        searchInput.addEventListener('input', applyFilters);
        filterType.addEventListener('change', applyFilters);
        filterStatus.addEventListener('change', applyFilters);
    }
</script>

</body>
</html>

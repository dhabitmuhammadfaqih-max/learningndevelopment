<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Pejabat</title>

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

        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 16px;
            margin-top: 20px;
        }

        .card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .card strong {
            display: block;
            margin-bottom: 12px;
            font-size: 16px;
        }

        .badges {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 12px;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-korelasi {
            background: #e0e7ff;
            color: #3730a3;
        }

        .badge-dinilai {
            background: #dcfce7;
            color: #166534;
        }

        .badge-belum-dinilai {
            background: #fef3c7;
            color: #92400e;
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
        }

        .card small {
            display: block;
            color: #888;
            margin-bottom: 12px;
        }

        .card-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-top: 10px;
        }

        .btn-edit {
            background: #2563eb;
        }

        .btn-edit:hover {
            background: #1d4ed8;
        }

        .btn-delete {
            background: #dc2626;
        }

        .btn-delete:hover {
            background: #b91c1c;
        }

        .btn-disabled {
            background: #d1d5db;
            color: #6b7280;
            cursor: not-allowed;
        }

        .btn-disabled:hover {
            background: #d1d5db;
        }

        .card-actions a,
        .card-actions button {
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            font-size: 14px;
            color: white;
            text-decoration: none;
            cursor: pointer;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 16px;
        }

        .alert-success {
            background: #dcfce7;
            color: #166534;
        }

        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
            z-index: 50;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-box {
            background: white;
            border-radius: 10px;
            padding: 24px;
            width: 100%;
            max-width: 380px;
        }

        .modal-box h3 {
            margin-top: 0;
        }

        .modal-box label {
            display: block;
            font-size: 13px;
            color: #555;
            margin-bottom: 4px;
            margin-top: 12px;
        }

        .modal-box input {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
        }

        .modal-box .error {
            color: #dc2626;
            font-size: 12px;
            margin-top: 4px;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 8px;
            margin-top: 20px;
        }

        .btn-cancel {
            background: #e5e7eb;
            color: #111;
        }

        .btn-cancel:hover {
            background: #d1d5db;
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
    </style>
</head>

<body>

<div class="topbar">
    <h1>Dashboard Pejabat</h1>

    <div style="display:flex; gap:8px;">
        <a href="{{ route('official.my-evaluations') }}"
           style="padding:10px 20px; background:#2563eb; color:white; border-radius:5px; text-decoration:none; display:inline-block;">
            Lihat Nilai Saya
        </a>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit">Logout</button>
        </form>
    </div>
</div>

<p style="color:#555;">
    Selamat datang, <strong>{{ auth()->user()->name }}</strong>
</p>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if($pejabatBinaan->isNotEmpty())

    <h2 style="margin-top:24px;">Pejabat yang Harus Anda Nilai</h2>

    <div class="grid">

        @foreach($pejabatBinaan as $pejabat)

            <div class="card">
                <strong>{{ $pejabat->name }}</strong>
                <small>
                    {{ $pejabat->username }}
                    @if($pejabat->jabatan)
                        &middot; {{ $pejabat->jabatan }}
                    @endif
                    @if($pejabat->unit_kerja)
                        &middot; {{ $pejabat->unit_kerja }}
                    @endif
                </small>

                <div class="badges">
                    @if($pejabat->evaluated_count > 0)
                        <span class="status-badge status-sudah">&#10003; Sudah Dinilai</span>
                    @else
                        <span class="status-badge status-belum">Belum Dinilai</span>
                    @endif
                </div>

                <a href="{{ route('supervisor.official', $pejabat->id) }}">
                    Nilai Pejabat
                </a>
            </div>

        @endforeach

    </div>

@endif

@if($employees->isEmpty())

    <p class="empty">Belum ada data karyawan.</p>

@else

    @if($pejabatBinaan->isNotEmpty())
        <h2 style="margin-top:24px;">Karyawan</h2>
    @endif

    <div class="grid">

        @foreach($employees as $employee)

            <div class="card">
                <strong>{{ $employee->name }}</strong>
                <small>
                    {{ $employee->username }}
                    @if($employee->jabatan)
                        &middot; {{ $employee->jabatan }}
                    @endif
                    @if($employee->unit_kerja)
                        &middot; {{ $employee->unit_kerja }}
                    @endif
                </small>

                <div class="badges">
                    <span class="badge badge-korelasi">
                        {{ $employee->feedbacks_received_count }} tanggapan korelasi
                    </span>

                    @if($employee->evaluations->isNotEmpty())
                        <span class="badge badge-dinilai">&#10003; Sudah dinilai</span>
                    @else
                        <span class="badge badge-belum-dinilai">Belum dinilai</span>
                    @endif
                </div>

                @php
                    $alreadyEvaluated = $employee->evaluations->isNotEmpty();
                @endphp

                @if($alreadyEvaluated)
                    <button type="button" class="btn-disabled" disabled>
                        Berikan Penilaian
                    </button>
                @else
                    <a href="{{ route('official.employee', $employee->id) }}">
                        Berikan Penilaian
                    </a>
                @endif

                <div class="card-actions">
                    @if($alreadyEvaluated)
                        <button type="button" class="btn-edit"
                                data-url="{{ route('official.employee', $employee->id) }}"
                                data-locked="{{ $employee->supervisor_feedbacks_count > 0 ? '1' : '0' }}"
                                onclick="handleEditClick(this)">
                            Edit Penilaian
                        </button>
                    @endif
                </div>
            </div>

        @endforeach

    </div>

@endif

<script>
    function handleEditClick(btn) {
        if (btn.dataset.locked === '1') {
            alert('Penilaian ini sudah tidak bisa di edit');
            return;
        }

        window.location.href = btn.dataset.url;
    }
</script>

</body>
</html>
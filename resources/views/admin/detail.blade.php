<!DOCTYPE html>
<html>
<head>
    <title>
        Detail {{ $employee->name }}
    </title>

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

        .back {
            display: inline-block;
            margin-bottom: 20px;
            color: #333;
            text-decoration: none;
            font-size: 14px;
        }

        .back:hover {
            text-decoration: underline;
        }

        .card {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .item {
            border: 1px solid #eee;
            padding: 15px;
            margin: 10px 0;
            border-radius: 8px;
            background: #fafafa;
        }

        .item strong {
            display: block;
            margin-bottom: 6px;
        }

        .score {
            font-size: 32px;
            font-weight: bold;
            margin: 8px 0;
        }

        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            background: #111;
            color: white;
            font-size: 13px;
        }

        .empty {
            color: #999;
        }

        button {
            padding: 12px 24px;
            background: #111;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 15px;
        }

        button:hover {
            background: #333;
        }

        .warning {
            background: #fff2f2;
            border: 1px solid #ffcccc;
            color: #b30000;
            padding: 15px;
            border-radius: 8px;
        }
    </style>
</head>

<body>

<a href="{{ route('admin.dashboard') }}" class="back">&larr; Kembali</a>

<div class="topbar">
    <h1>Penilaian {{ $employee->name }}</h1>
</div>
<p style="color:#777; margin-top:-10px;">
    {{ $employee->username }} &middot; <span class="badge">{{ strtoupper($employee->role) }}</span>
</p>

@if(session('success'))
    <div class="badge" style="background:#166534; margin-bottom:16px;">{{ session('success') }}</div>
@endif

<div class="card">
    <h2>Kehadiran &amp; Status Kontrak</h2>

    <form method="POST" action="{{ route('admin.employee.attendance.update', $employee->id) }}">
        @csrf
        @method('PUT')

        <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(140px, 1fr)); gap:12px;">
            @foreach(\App\Models\User::ATTENDANCE_COUNTERS as $field => $label)
                <div>
                    <label for="{{ $field }}">Jumlah {{ $label }}</label><br>
                    <input
                        type="number"
                        min="0"
                        id="{{ $field }}"
                        name="{{ $field }}"
                        value="{{ old($field, $employee->{$field} ?? 0) }}"
                        style="width:100%; padding:6px 8px; border:1px solid #ccc; border-radius:5px; box-sizing:border-box;"
                    >
                </div>
            @endforeach
        </div>

        <br>

        <label for="contract-status">Status Kontrak</label><br>
        <select id="contract-status" name="contract_status">
            <option value="">-- Belum Ditentukan --</option>
            @foreach(\App\Models\User::CONTRACT_STATUSES as $value => $label)
                <option value="{{ $value }}" {{ $employee->contract_status == $value ? 'selected' : '' }}>
                    {{ $label }}
                </option>
            @endforeach
        </select>

        <br><br>

        <button type="submit">Simpan</button>
    </form>
</div>


<div class="card">
    <h2>Tanggapan Korelasi</h2>

    @if($employee->is_spg)
        <p class="empty">Korelasi bersifat opsional untuk akun SPG.</p>
    @endif

    @forelse($feedbacks as $feedback)

        <div class="item">
            <strong>{{ $feedback->reviewer->name }}</strong>
            <p style="margin:0;">{{ $feedback->feedback }}</p>
        </div>

    @empty

        <p class="empty">Belum ada tanggapan.</p>

    @endforelse
</div>


<div class="card">
    <h2>Penilaian Pejabat</h2>

    @if($evaluation)

        <p>
            <strong>Pejabat:</strong>
            {{ $evaluation->official->name }}
        </p>

        <div class="score">{{ $evaluation->score }}/100</div>

        <p>{{ $evaluation->feedback }}</p>

        <span class="badge">{{ $evaluation->recommendationLabel() }}</span>

        @if($evaluation->kenaikan_gaji_amount)
            <p style="margin-top:8px;">
                <strong>Nominal Kenaikan Gaji:</strong>
                Rp {{ number_format($evaluation->kenaikan_gaji_amount, 0, ',', '.') }}
            </p>
        @endif

        @if($evaluation->employee_response)
            <div class="item" style="margin-top:12px;">
                <strong>Tanggapan Karyawan</strong>
                <p style="margin:0;">{{ $evaluation->employee_response }}</p>
            </div>
        @endif

    @else

        <p class="empty">Belum ada penilaian.</p>

    @endif
</div>


<div class="card">
    <h2>Tanggapan Atasan</h2>

    @if($supervisorFeedback)

        <div class="item">
            <strong>{{ $supervisorFeedback->supervisor->name }}</strong>
            <p style="margin:0;">{{ $supervisorFeedback->feedback }}</p>
        </div>

    @else

        <p class="empty">Belum ada tanggapan.</p>

    @endif
</div>


<div class="card">

    @php
        // Minimal 3 korelasi TIDAK berlaku untuk akun dengan flag is_spg (opsional).
        $korelasiOk = $employee->is_spg || $feedbacks->count() >= 3;
    @endphp

    @if(
        $korelasiOk &&
        $evaluation &&
        $supervisorFeedback
    )

        <a href="{{ route('admin.pdf', $employee->id) }}">
            <button>GENERATE PDF</button>
        </a>

    @else

        <div class="warning">
            PDF belum tersedia. Pastikan
            @if(! $employee->is_spg)
                minimal 3 tanggapan korelasi,
            @endif
            penilaian pejabat, dan tanggapan atasan sudah tersedia.
        </div>

    @endif

</div>

</body>
</html>
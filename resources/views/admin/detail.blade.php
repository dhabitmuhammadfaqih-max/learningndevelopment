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
<p style="color:#777; margin-top:-10px;">{{ $employee->email }}</p>


<div class="card">
    <h2>Tanggapan Korelasi</h2>

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

        <span class="badge">{{ $evaluation->recommendation }}</span>

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

    @if(
        $feedbacks->count() >= 3 &&
        $evaluation &&
        $supervisorFeedback
    )

        <a href="{{ route('admin.pdf', $employee->id) }}">
            <button>GENERATE PDF</button>
        </a>

    @else

        <div class="warning">
            PDF belum tersedia. Pastikan minimal 3 tanggapan korelasi,
            penilaian pejabat, dan tanggapan atasan sudah tersedia.
        </div>

    @endif

</div>

</body>
</html>
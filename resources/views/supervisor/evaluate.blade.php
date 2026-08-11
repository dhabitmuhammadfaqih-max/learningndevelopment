<!DOCTYPE html>
<html>
<head>
    <title>Evaluasi Atasan</title>

    <style>
        body {
            font-family: Arial;
            background: #f5f5f5;
            padding: 30px;
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

        .score {
            font-size: 28px;
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

        .item {
            border-bottom: 1px solid #eee;
            padding: 10px 0;
        }

        .item:last-child {
            border-bottom: none;
        }

        .item strong {
            display: block;
            margin-bottom: 4px;
        }

        .empty {
            color: #999;
        }

        textarea {
            width: 100%;
            max-width: 500px;
            min-height: 130px;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-family: Arial;
            font-size: 14px;
            box-sizing: border-box;
            resize: vertical;
        }

        button {
            padding: 12px 24px;
            background: #111;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 15px;
            margin-top: 12px;
        }

        button:hover {
            background: #333;
        }
    </style>
</head>

<body>

<a href="{{ route('supervisor.dashboard') }}" class="back">&larr; Kembali</a>

<h1>Evaluasi {{ $employee->name }}</h1>


<div class="card">
    <h2>Penilaian Pejabat</h2>

    @if($evaluation)

        <p><strong>Pejabat:</strong> {{ $evaluation->official->name }}</p>

        <div class="score">{{ $evaluation->score }}/100</div>

        <p>{{ $evaluation->feedback }}</p>

        <span class="badge">{{ $evaluation->recommendation }}</span>

    @else

        <p class="empty">Belum ada penilaian pejabat.</p>

    @endif
</div>


<div class="card">
    <h2>Tanggapan Korelasi</h2>

    @forelse($feedbacks as $feedback)

        <div class="item">
            <strong>{{ $feedback->reviewer->name }}</strong>
            {{ $feedback->feedback }}
        </div>

    @empty

        <p class="empty">Belum ada tanggapan.</p>

    @endforelse
</div>


<div class="card">
    <h2>Tanggapan Saya</h2>

    <form method="POST" action="{{ route('supervisor.feedback', $employee->id) }}">

        @csrf

        <textarea name="feedback" required>{{ $supervisorFeedback->feedback ?? '' }}</textarea>

        <br>

        <button type="submit">Simpan Tanggapan</button>

    </form>
</div>

</body>
</html>
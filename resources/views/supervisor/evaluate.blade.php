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

        /* Signature pad */
        .signature-wrap {
            margin-top: 16px;
            max-width: 500px;
        }

        .signature-wrap label {
            display: block;
            margin-bottom: 6px;
            font-weight: bold;
        }

        #signature-pad {
            width: 100%;
            height: 140px;
            border: 1px solid #ccc;
            border-radius: 6px;
            background: white;
            touch-action: none;
            cursor: crosshair;
            display: block;
        }

        .signature-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 8px;
        }

        .signature-hint {
            font-size: 12px;
            color: #999;
        }

        .signature-clear {
            background: none;
            border: none;
            color: #666;
            font-size: 13px;
            cursor: pointer;
            padding: 4px 8px;
            margin: 0;
            text-decoration: underline;
        }

        .signature-clear:hover {
            background: none;
            color: #111;
        }

        .signature-saved {
            width: 160px;
            height: 70px;
            object-fit: contain;
            border: 1px solid #eee;
            border-radius: 6px;
            background: #fafafa;
            display: block;
            margin-top: 10px;
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

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
        }

        .alert-error ul {
            margin: 0;
            padding-left: 18px;
        }

        .field-error {
            color: #dc2626;
            font-size: 13px;
            margin-top: 4px;
        }
    </style>
</head>

<body>

<a href="{{ route('supervisor.dashboard') }}" class="back">&larr; Kembali</a>

<h1>Evaluasi {{ $employee->name }}</h1>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if($errors->any())
    <div class="alert alert-error">
        <ul>
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

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

    <form method="POST" action="{{ route('supervisor.feedback', $employee->id) }}" id="form-feedback-atasan">

        @csrf

        <textarea name="feedback" required minlength="10">{{ old('feedback', $supervisorFeedback->feedback ?? '') }}</textarea>
        @error('feedback')
            <div class="field-error">{{ $message }}</div>
        @enderror

        <div class="signature-wrap">
            <label>Tanda Tangan</label>
            <canvas id="signature-pad"></canvas>
            <div class="signature-actions">
                <span class="signature-hint">Gambar tanda tangan di kotak di atas</span>
                <button type="button" class="signature-clear" id="btn-clear-signature">Hapus &amp; ulangi</button>
            </div>
            {{-- Diisi otomatis oleh JS sebelum form dikirim --}}
            <input type="hidden" name="signature" id="signature-input">
            @error('signature')
                <div class="field-error">{{ $message }}</div>
            @enderror

            @if($supervisorFeedback && $supervisorFeedback->signature)
                <img src="{{ Storage::disk('public')->url($supervisorFeedback->signature) }}" class="signature-saved">
            @endif
        </div>

        <button type="submit">Simpan Tanggapan</button>

    </form>
</div>

<script>
    const canvas = document.getElementById('signature-pad');
    const ctx = canvas.getContext('2d');
    const ratio = window.devicePixelRatio || 1;

    function resizeCanvas() {
        canvas.width = canvas.clientWidth * ratio;
        canvas.height = canvas.clientHeight * ratio;
        ctx.scale(ratio, ratio);
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
        ctx.lineWidth = 2.2;
        ctx.strokeStyle = '#111';
    }
    resizeCanvas();

    let drawing = false;
    let last = null;
    let hasStroke = false;

    function getPos(e) {
        const rect = canvas.getBoundingClientRect();
        const point = e.touches ? e.touches[0] : e;
        return { x: point.clientX - rect.left, y: point.clientY - rect.top };
    }

    function start(e) {
        e.preventDefault();
        drawing = true;
        hasStroke = true;
        last = getPos(e);
    }

    function move(e) {
        if (!drawing) return;
        e.preventDefault();
        const pos = getPos(e);
        ctx.beginPath();
        ctx.moveTo(last.x, last.y);
        ctx.lineTo(pos.x, pos.y);
        ctx.stroke();
        last = pos;
    }

    function end() {
        drawing = false;
    }

    canvas.addEventListener('mousedown', start);
    canvas.addEventListener('mousemove', move);
    canvas.addEventListener('mouseup', end);
    canvas.addEventListener('mouseleave', end);
    canvas.addEventListener('touchstart', start);
    canvas.addEventListener('touchmove', move);
    canvas.addEventListener('touchend', end);

    document.getElementById('btn-clear-signature').addEventListener('click', function () {
        ctx.clearRect(0, 0, canvas.clientWidth, canvas.clientHeight);
        hasStroke = false;
    });

    const form = document.getElementById('form-feedback-atasan');
    const signatureInput = document.getElementById('signature-input');

    form.addEventListener('submit', function (e) {
        if (!hasStroke) {
            e.preventDefault();
            alert('Tanda tangan wajib diisi sebelum menyimpan tanggapan.');
            return;
        }
        signatureInput.value = canvas.toDataURL('image/png');
    });
</script>

</body>
</html>
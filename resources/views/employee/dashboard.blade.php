<!DOCTYPE html>
<html>
<head>
    <title>Dashboard Karyawan</title>

    <style>
        body {
            font-family: Arial;
            background: #f5f5f5;
            padding: 30px;
        }

        .card {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 10px;
        }

        textarea {
            width: 100%;
            min-height: 100px;
            margin-top: 10px;
        }

        button {
            padding: 10px 20px;
            background: #111;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        button:disabled {
            background: #999;
            cursor: not-allowed;
        }

        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
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
            margin-top: 6px;
        }
    </style>
</head>

<body>

<div class="topbar">
    <h1>Dashboard Karyawan</h1>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit">Logout</button>
    </form>
</div>

<p>
    Selamat datang,
    <strong>{{ auth()->user()->name }}</strong>
</p>

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

    <h2>Nilai Saya</h2>

    @if($myEvaluation)

        <h1>{{ $myEvaluation->score }}/100</h1>

        <p>
            <strong>Pejabat:</strong>
            {{ $myEvaluation->official->name }}
        </p>

        <p>
            <strong>Rekomendasi:</strong>
            {{ $myEvaluation->recommendation }}
        </p>

        <p>
            {{ $myEvaluation->feedback }}
        </p>

    @else

        <p>Penilaian belum tersedia.</p>

    @endif

</div>


<div class="card">

    <h2>Berikan Tanggapan Teman</h2>

    <form method="POST"
          action="{{ route('employee.feedback') }}"
          id="form-feedback">

        @csrf

        <label>Pilih Karyawan</label>

        <select name="employee_id">

            @foreach($employees as $employee)

                <option value="{{ $employee->id }}" @selected(old('employee_id') == $employee->id)>
                    {{ $employee->name }}
                </option>

            @endforeach

        </select>

        <br><br>

        <textarea
            name="feedback"
            placeholder="Tulis tanggapan..."
            minlength="10"
            required
        >{{ old('feedback') }}</textarea>

        <div class="signature-wrap">
            <label>Tanda Tangan</label>
            <canvas id="signature-pad"></canvas>
            <div class="signature-actions">
                <span class="signature-hint">Gambar tanda tangan di kotak di atas</span>
                <button type="button" class="signature-clear" id="btn-clear-signature">Hapus &amp; ulangi</button>
            </div>
            {{-- Diisi otomatis oleh JS sebelum form dikirim --}}
            <input type="hidden" name="signature" id="signature-input">
        </div>

        <br>

        <button type="submit">
            Kirim Tanggapan
        </button>

    </form>

</div>


<div class="card">

    <h2>Tanggapan Terhadap Saya</h2>

    @forelse($myFeedbacks as $feedback)

        <div>
            <strong>
                {{ $feedback->reviewer->name }}
            </strong>

            <p>
                {{ $feedback->feedback }}
            </p>

            @if($feedback->signature)
                <img src="{{ Storage::disk('public')->url($feedback->signature) }}" class="signature-saved">
            @endif
        </div>

        <hr>

    @empty

        <p>Belum ada tanggapan.</p>

    @endforelse

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

    const form = document.getElementById('form-feedback');
    const signatureInput = document.getElementById('signature-input');

    form.addEventListener('submit', function (e) {
        if (!hasStroke) {
            e.preventDefault();
            alert('Tanda tangan wajib diisi sebelum mengirim tanggapan.');
            return;
        }
        signatureInput.value = canvas.toDataURL('image/png');
    });
</script>

</body>
</html>
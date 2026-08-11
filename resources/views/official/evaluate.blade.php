<!DOCTYPE html>
<html>
<head>
    <title>Penilaian {{ $employee->name }}</title>

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

        .item p {
            margin: 0;
        }

        .empty {
            color: #999;
        }

        table.komponen {
            width: 100%;
            border-collapse: collapse;
        }

        table.komponen th {
            text-align: left;
            font-size: 13px;
            color: #777;
            padding: 6px 8px;
            border-bottom: 2px solid #eee;
        }

        table.komponen td {
            padding: 8px;
            border-bottom: 1px solid #f0f0f0;
            vertical-align: middle;
        }

        table.komponen td.label {
            font-weight: 500;
        }

        table.komponen td.bobot {
            color: #777;
            font-size: 13px;
            white-space: nowrap;
        }

        table.komponen input[type="number"] {
            width: 80px;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-family: Arial;
            font-size: 14px;
            box-sizing: border-box;
        }

        .total-box {
            margin-top: 16px;
            padding: 16px;
            background: #111;
            color: white;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .total-box .angka {
            font-size: 28px;
            font-weight: bold;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-weight: bold;
        }

        textarea, select {
            width: 100%;
            max-width: 500px;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-family: Arial;
            font-size: 14px;
            box-sizing: border-box;
        }

        textarea {
            min-height: 120px;
            resize: vertical;
        }

        .field {
            margin-top: 16px;
        }

        button {
            padding: 12px 24px;
            background: #111;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 15px;
            margin-top: 16px;
        }

        button:hover {
            background: #333;
        }

        button:disabled {
            background: #999;
            cursor: not-allowed;
        }

        /* Signature pad */
        .signature-wrap {
            max-width: 500px;
        }

        #signature-pad {
            width: 100%;
            height: 160px;
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
            width: 100%;
            max-width: 300px;
            height: 120px;
            object-fit: contain;
            border: 1px solid #eee;
            border-radius: 6px;
            background: #fafafa;
            display: block;
        }
    </style>
</head>

<body>

<a href="{{ route('official.dashboard') }}" class="back">&larr; Kembali</a>

<h1>Penilaian {{ $employee->name }}</h1>


<div class="card">
    <h2>Tanggapan Korelasi</h2>

    @forelse($peerFeedbacks as $feedback)

        <div class="item">
            <strong>{{ $feedback->reviewer->name }}</strong>
            <p>{{ $feedback->feedback }}</p>
        </div>

    @empty

        <p class="empty">Belum ada tanggapan.</p>

    @endforelse
</div>


<div class="card">
    <h2>Berikan Penilaian</h2>

    @if($myEvaluation)

        {{-- Sudah pernah dinilai: tampilkan hasilnya, tidak bisa dinilai ulang --}}
        <table class="komponen">
            <tr><th>Komponen</th><th>Bobot</th><th>Nilai</th></tr>
            @foreach (\App\Models\Evaluation::WEIGHTS as $key => $bobot)
                <tr>
                    <td class="label">{{ \App\Models\Evaluation::LABELS[$key] }}</td>
                    <td class="bobot">{{ rtrim(rtrim(number_format($bobot, 1), '0'), '.') }}%</td>
                    <td>{{ $myEvaluation->$key }}</td>
                </tr>
            @endforeach
        </table>

        <div class="total-box">
            <span>Nilai Akhir</span>
            <span class="angka">{{ $myEvaluation->score }}/100</span>
        </div>

        <div class="field">
            <label>Tanggapan</label>
            <p>{{ $myEvaluation->feedback }}</p>
        </div>

        <div class="field">
            <label>Rekomendasi</label>
            <p>{{ $myEvaluation->recommendationLabel() }}</p>
        </div>

        @if ($myEvaluation->signature)
            <div class="field">
                <label>Tanda Tangan Penilai</label>
                <img src="{{ Storage::disk('public')->url($myEvaluation->signature) }}" class="signature-saved">
            </div>
        @endif

    @else

        <form method="POST" action="{{ route('official.evaluate', $employee->id) }}" id="form-penilaian">

            @csrf

            <table class="komponen">
                <tr>
                    <th>Komponen</th>
                    <th>Bobot</th>
                    <th>Nilai (0-100)</th>
                </tr>

                @foreach (\App\Models\Evaluation::WEIGHTS as $key => $bobot)
                    <tr>
                        <td class="label">{{ \App\Models\Evaluation::LABELS[$key] }}</td>
                        <td class="bobot">{{ rtrim(rtrim(number_format($bobot, 1), '0'), '.') }}%</td>
                        <td>
                            <input
                                type="number"
                                name="{{ $key }}"
                                min="0"
                                max="100"
                                step="1"
                                class="komponen-input"
                                data-bobot="{{ $bobot }}"
                                value="{{ old($key, 0) }}"
                                required
                            >
                        </td>
                    </tr>
                @endforeach
            </table>

            <div class="total-box">
                <span>Nilai Akhir (otomatis)</span>
                <span class="angka" id="nilai-akhir">0</span>
            </div>

            <div class="field">
                <label>Tanggapan</label>
                <textarea name="feedback" required>{{ old('feedback') }}</textarea>
            </div>

            <div class="field">
                <label>Rekomendasi</label>
                <select name="recommendation" required>
                    <option value="">-- Pilih Rekomendasi --</option>
                    <option value="perpanjang_kontrak" @selected(old('recommendation') === 'perpanjang_kontrak')>Perpanjang Kontrak</option>
                    <option value="promosi" @selected(old('recommendation') === 'promosi')>Promosi</option>
                    <option value="kenaikan_gaji" @selected(old('recommendation') === 'kenaikan_gaji')>Kenaikan Gaji</option>
                    <option value="tidak_ada" @selected(old('recommendation') === 'tidak_ada')>Tidak Ada</option>
                </select>
            </div>

            <div class="field signature-wrap">
                <label>Tanda Tangan Penilai</label>
                <canvas id="signature-pad"></canvas>
                <div class="signature-actions">
                    <span class="signature-hint">Gambar tanda tangan di kotak di atas</span>
                    <button type="button" class="signature-clear" id="btn-clear-signature">Hapus &amp; ulangi</button>
                </div>
                {{-- Diisi otomatis oleh JS sebelum form dikirim --}}
                <input type="hidden" name="signature" id="signature-input">
            </div>

            <button type="submit" id="btn-submit">Simpan Penilaian</button>

        </form>

    @endif
</div>

<script>
    // Hitung nilai akhir otomatis saat input komponen diubah
    const inputs = document.querySelectorAll('.komponen-input');
    const hasil = document.getElementById('nilai-akhir');

    function hitungTotal() {
        let total = 0;

        inputs.forEach(function (input) {
            const nilai = parseFloat(input.value) || 0;
            const bobot = parseFloat(input.dataset.bobot) || 0;
            total += nilai * (bobot / 100);
        });

        if (hasil) {
            hasil.textContent = total.toFixed(2);
        }
    }

    inputs.forEach(function (input) {
        input.addEventListener('input', hitungTotal);
    });

    hitungTotal();

    // ---- Signature pad ----
    const canvas = document.getElementById('signature-pad');

    if (canvas) {
        const ctx = canvas.getContext('2d');
        const ratio = window.devicePixelRatio || 1;

        function resizeCanvas() {
            const imageData = canvas.toDataURL();
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

        const form = document.getElementById('form-penilaian');
        const signatureInput = document.getElementById('signature-input');

        form.addEventListener('submit', function (e) {
            if (!hasStroke) {
                e.preventDefault();
                alert('Tanda tangan wajib diisi sebelum menyimpan penilaian.');
                return;
            }
            signatureInput.value = canvas.toDataURL('image/png');
        });
    }
</script>

</body>
</html>
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

        /* ===== Legenda skala I/A/B/C/D ===== */
        .legenda {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 16px;
        }

        .legenda-item {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 6px 10px;
            background: #f5f5f5;
            border-radius: 6px;
            font-size: 12px;
        }

        .legenda-huruf {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: #111;
            color: white;
            font-weight: bold;
            font-size: 11px;
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

        table.komponen td.label .deskripsi {
            font-weight: normal;
            font-size: 11.5px;
            color: #888;
            margin-top: 3px;
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

        /* ===== Rekomendasi (checkbox) ===== */
        .rekomendasi-options {
            display: flex;
            flex-wrap: wrap;
            gap: 10px 20px;
        }

        .rekomendasi-options label {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-weight: normal;
            margin-bottom: 0;
        }

        .rekomendasi-options input[type="checkbox"] {
            width: 16px;
            height: 16px;
        }

        #kenaikan-gaji-wrap {
            display: none;
            max-width: 280px;
        }

        #kenaikan-gaji-wrap input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-family: Arial;
            font-size: 14px;
            box-sizing: border-box;
        }

        #kenaikan-gaji-wrap .hint {
            font-size: 12px;
            color: #999;
            margin-top: 4px;
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

        .alert-info {
            background: #dbeafe;
            color: #1e40af;
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

<a href="{{ route('official.dashboard') }}" class="back">&larr; Kembali</a>

<h1>Penilaian {{ $employee->name }}</h1>

@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif

@if(session('error'))
    <div class="alert alert-error">{{ session('error') }}</div>
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
    <h2>Data Kehadiran &amp; Kontrak</h2>

    <table class="komponen">
        <tr>
            <td class="label">Izin</td>
            <td>{{ $employee->jumlah_izin ?? 0 }}</td>
        </tr>
        <tr>
            <td class="label">Sakit</td>
            <td>{{ $employee->jumlah_sakit ?? 0 }}</td>
        </tr>
        <tr>
            <td class="label">Alpa</td>
            <td>{{ $employee->jumlah_alpa ?? 0 }}</td>
        </tr>
        <tr>
            <td class="label">Terlambat</td>
            <td>{{ $employee->jumlah_terlambat ?? 0 }}</td>
        </tr>
        <tr>
            <td class="label">Status Kontrak</td>
            <td>{{ $employee->contractStatusLabel() }}</td>
        </tr>
    </table>
</div>


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

    {{-- Legenda skala index penilaian --}}
    <div class="legenda">
        @foreach (\App\Models\Evaluation::SCALE as $huruf => $range)
            <div class="legenda-item">
                <span class="legenda-huruf">{{ $huruf }}</span>
                <span>{{ $range['min'] }}-{{ $range['max'] }} ({{ $range['label'] }})</span>
            </div>
        @endforeach
    </div>

    @php
        $isLocked = $myEvaluation && $evaluationLocked;
        $isEdit = $myEvaluation && ! $evaluationLocked;
    @endphp

    @if($isLocked)

        {{-- Sudah dinilai DAN atasan pejabat sudah menanggapi: terkunci, tidak bisa diedit --}}
        <div class="alert alert-info">
            Nilai ini sudah dikunci karena atasan pejabat sudah mengirim tanggapan
            untuk karyawan ini.
        </div>

        <table class="komponen">
            <tr><th>Komponen</th><th>Bobot</th><th>Nilai</th></tr>
            @foreach (\App\Models\Evaluation::WEIGHTS as $key => $bobot)
                <tr>
                    <td class="label">
                        {{ \App\Models\Evaluation::LABELS[$key] }}
                        <div class="deskripsi">{{ \App\Models\Evaluation::DESCRIPTIONS[$key] }}</div>
                    </td>
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
            @if($myEvaluation->kenaikan_gaji_amount)
                <p>Nominal Kenaikan Gaji: Rp {{ number_format($myEvaluation->kenaikan_gaji_amount, 0, ',', '.') }}</p>
            @endif
        </div>

        @if ($myEvaluation->signature)
            <div class="field">
                <label>Tanda Tangan Penilai</label>
                <img src="{{ Storage::disk('public')->url($myEvaluation->signature) }}" class="signature-saved">
            </div>
        @endif

        @if ($myEvaluation->employee_response)
            <div class="field">
                <label>Tanggapan Karyawan</label>
                <p>{{ $myEvaluation->employee_response }}</p>
            </div>
        @endif

    @else

        @if($isEdit)
            <div class="alert alert-info">
                Anda masih bisa mengubah nilai ini selama atasan pejabat belum
                mengirim tanggapan untuk karyawan ini.
            </div>
        @endif

        <form method="POST"
              action="{{ $isEdit ? route('official.evaluate.update', $employee->id) : route('official.evaluate', $employee->id) }}"
              id="form-penilaian">

            @csrf
            @if($isEdit)
                @method('PUT')
            @endif

            <table class="komponen">
                <tr>
                    <th>Komponen</th>
                    <th>Bobot</th>
                    <th>Nilai (0-100)</th>
                </tr>

                @foreach (\App\Models\Evaluation::WEIGHTS as $key => $bobot)
                    <tr>
                        <td class="label">
                            {{ \App\Models\Evaluation::LABELS[$key] }}
                            <div class="deskripsi">{{ \App\Models\Evaluation::DESCRIPTIONS[$key] }}</div>
                        </td>
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
                                value="{{ old($key, $isEdit ? $myEvaluation->$key : 0) }}"
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
                <textarea name="feedback" required minlength="10">{{ old('feedback', $isEdit ? $myEvaluation->feedback : '') }}</textarea>
                @error('feedback')
                    <div class="field-error">{{ $message }}</div>
                @enderror
            </div>

            @php
                $selectedRecommendations = old('recommendation', $isEdit ? $myEvaluation->recommendationList() : []);
            @endphp

            <div class="field">
                <label>Rekomendasi (bisa pilih lebih dari satu)</label>

                <div class="rekomendasi-options">
                    @foreach (\App\Models\Evaluation::RECOMMENDATIONS as $value => $recLabel)
                        <label>
                            <input
                                type="checkbox"
                                name="recommendation[]"
                                value="{{ $value }}"
                                class="rekomendasi-checkbox"
                                {{ in_array($value, $selectedRecommendations) ? 'checked' : '' }}
                            >
                            {{ $recLabel }}
                        </label>
                    @endforeach
                </div>

                @error('recommendation')
                    <div class="field-error">{{ $message }}</div>
                @enderror
                @error('recommendation.*')
                    <div class="field-error">{{ $message }}</div>
                @enderror

                <div class="field" id="kenaikan-gaji-wrap">
                    <label>Nominal Kenaikan Gaji</label>
                    <input
                        type="number"
                        name="kenaikan_gaji_amount"
                        min="1"
                        max="{{ \App\Models\Evaluation::KENAIKAN_GAJI_MAX }}"
                        value="{{ old('kenaikan_gaji_amount', $isEdit ? $myEvaluation->kenaikan_gaji_amount : '') }}"
                    >
                    <div class="hint">
                        Maksimal Rp{{ number_format(\App\Models\Evaluation::KENAIKAN_GAJI_MAX, 0, ',', '.') }}
                    </div>
                    @error('kenaikan_gaji_amount')
                        <div class="field-error">{{ $message }}</div>
                    @enderror
                </div>
            </div>

            <div class="field signature-wrap">
                <label>Tanda Tangan Penilai</label>
                <canvas id="signature-pad"></canvas>
                <div class="signature-actions">
                    <span class="signature-hint">
                        @if($isEdit)
                            Kosongkan jika tidak ingin mengganti tanda tangan sebelumnya
                        @else
                            Gambar tanda tangan di kotak di atas
                        @endif
                    </span>
                    <button type="button" class="signature-clear" id="btn-clear-signature">Hapus &amp; ulangi</button>
                </div>
                {{-- Diisi otomatis oleh JS sebelum form dikirim --}}
                <input type="hidden" name="signature" id="signature-input">
                @error('signature')
                    <div class="field-error">{{ $message }}</div>
                @enderror

                @if($isEdit && $myEvaluation->signature)
                    <img src="{{ Storage::disk('public')->url($myEvaluation->signature) }}" class="signature-saved" style="margin-top:10px;">
                @endif
            </div>

            <button type="submit" id="btn-submit">
                {{ $isEdit ? 'Perbarui Penilaian' : 'Simpan Penilaian' }}
            </button>

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

    // ---- Tampilkan input nominal kenaikan gaji hanya jika dicentang ----
    const kenaikanGajiCheckbox = document.querySelector('.rekomendasi-checkbox[value="kenaikan_gaji"]');
    const kenaikanGajiWrap = document.getElementById('kenaikan-gaji-wrap');
    const kenaikanGajiInput = kenaikanGajiWrap ? kenaikanGajiWrap.querySelector('input[name="kenaikan_gaji_amount"]') : null;

    function toggleKenaikanGaji() {
        if (!kenaikanGajiCheckbox || !kenaikanGajiWrap) return;

        if (kenaikanGajiCheckbox.checked) {
            kenaikanGajiWrap.style.display = 'block';
        } else {
            kenaikanGajiWrap.style.display = 'none';
            if (kenaikanGajiInput) {
                kenaikanGajiInput.value = '';
            }
        }
    }

    if (kenaikanGajiCheckbox) {
        kenaikanGajiCheckbox.addEventListener('change', toggleKenaikanGaji);
        toggleKenaikanGaji();
    }

    if (kenaikanGajiInput) {
        kenaikanGajiInput.addEventListener('input', function () {
            const max = parseInt(kenaikanGajiInput.max, 10);
            if (parseInt(kenaikanGajiInput.value, 10) > max) {
                kenaikanGajiInput.value = max;
            }
        });
    }

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
        const isEditForm = {{ $isEdit ? 'true' : 'false' }};

        form.addEventListener('submit', function (e) {
            if (!hasStroke && !isEditForm) {
                e.preventDefault();
                alert('Tanda tangan wajib diisi sebelum menyimpan penilaian.');
                return;
            }
            if (hasStroke) {
                signatureInput.value = canvas.toDataURL('image/png');
            }
        });
    }
</script>

</body>
</html>

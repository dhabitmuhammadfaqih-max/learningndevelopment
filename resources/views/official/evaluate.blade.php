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
            padding: 30px;
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
            padding: 8px;
            border-bottom: 2px solid #eee;
        }

        table.komponen td {
            padding: 10px 8px;
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

        textarea,
        select {
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

        .result-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            margin-bottom: 25px;
        }

        .result-header h2 {
            margin: 0 0 5px;
        }

        .result-header p {
            margin: 0;
            color: #777;
        }

        .re-evaluate {
            display: inline-block;
            padding: 10px 16px;
            background: #111;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 14px;
        }

        .re-evaluate:hover {
            background: #333;
        }

        .success {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 12px 16px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>

<a href="{{ route('official.dashboard') }}" class="back">
    &larr; Kembali
</a>

<h1>Penilaian {{ $employee->name }}</h1>

@if(session('success'))
    <div class="success">
        {{ session('success') }}
    </div>
@endif


{{-- TANGGAPAN KORELASI --}}
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


{{-- PENILAIAN --}}
<div class="card">

    {{-- 
        Kalau sudah punya nilai DAN tidak sedang edit,
        tampilkan hasil penilaian.
    --}}
    @if($myEvaluation && request()->query('edit') != 1)

        <div class="result-header">

            <div>
                <h2>Hasil Penilaian</h2>
                <p>Penilaian terhadap {{ $employee->name }}</p>
            </div>

            <a
                href="{{ route('official.employee', $employee->id) }}?edit=1"
                class="re-evaluate"
            >
                Penilaian Ulang
            </a>

        </div>


        <table class="komponen">

            <tr>
                <th>Komponen</th>
                <th>Bobot</th>
                <th>Nilai</th>
            </tr>

            @foreach(\App\Models\Evaluation::WEIGHTS as $key => $bobot)

                <tr>

                    <td class="label">
                        {{ \App\Models\Evaluation::LABELS[$key] }}
                    </td>

                    <td class="bobot">
                        {{ rtrim(rtrim(number_format($bobot, 1), '0'), '.') }}%
                    </td>

                    <td>
                        {{ $myEvaluation->$key }}
                    </td>

                </tr>

            @endforeach

        </table>


        <div class="total-box">

            <span>Nilai Akhir</span>

            <span class="angka">
                {{ $myEvaluation->score }}/100
            </span>

        </div>


        <div class="field">

            <label>Tanggapan</label>

            <p>
                {{ $myEvaluation->feedback }}
            </p>

        </div>


        <div class="field">

            <label>Rekomendasi</label>

            <p>
                {{ $myEvaluation->recommendationLabel() }}
            </p>

        </div>


    @else

        {{-- 
            Kalau belum pernah dinilai,
            ATAU sedang melakukan penilaian ulang,
            tampilkan form.
        --}}

        <h2>
            @if($myEvaluation)
                Penilaian Ulang
            @else
                Berikan Penilaian
            @endif
        </h2>


        <form
            method="POST"
            action="{{ route('official.evaluate', $employee->id) }}"
            id="form-penilaian"
        >

            @csrf


            <table class="komponen">

                <tr>
                    <th>Komponen</th>
                    <th>Bobot</th>
                    <th>Nilai (0-100)</th>
                </tr>


                @foreach(\App\Models\Evaluation::WEIGHTS as $key => $bobot)

                    <tr>

                        <td class="label">
                            {{ \App\Models\Evaluation::LABELS[$key] }}
                        </td>

                        <td class="bobot">
                            {{ rtrim(rtrim(number_format($bobot, 1), '0'), '.') }}%
                        </td>

                        <td>

                            <input
                                type="number"
                                name="{{ $key }}"
                                min="0"
                                max="100"
                                step="1"
                                class="komponen-input"
                                data-bobot="{{ $bobot }}"
                                value="{{ old($key, $myEvaluation?->$key ?? '') }}"
                                required
                            >

                        </td>

                    </tr>

                @endforeach

            </table>


            {{-- NILAI AKHIR --}}
            <div class="total-box">

                <span>Nilai Akhir</span>

                <span class="angka" id="nilai-akhir">
                    {{ $myEvaluation?->score ?? '0.00' }}
                </span>

            </div>


            {{-- FEEDBACK --}}
            <div class="field">

                <label>Tanggapan</label>

                <textarea
                    name="feedback"
                    required
                >{{ old('feedback', $myEvaluation?->feedback ?? '') }}</textarea>

            </div>


            {{-- REKOMENDASI --}}
            <div class="field">

                <label>Rekomendasi</label>

                <select name="recommendation" required>

                    <option value="">
                        -- Pilih Rekomendasi --
                    </option>

                    <option
                        value="perpanjang_kontrak"
                        @selected(old('recommendation', $myEvaluation?->recommendation ?? '') === 'perpanjang_kontrak')
                    >
                        Perpanjang Kontrak
                    </option>

                    <option
                        value="promosi"
                        @selected(old('recommendation', $myEvaluation?->recommendation ?? '') === 'promosi')
                    >
                        Promosi
                    </option>

                    <option
                        value="kenaikan_gaji"
                        @selected(old('recommendation', $myEvaluation?->recommendation ?? '') === 'kenaikan_gaji')
                    >
                        Kenaikan Gaji
                    </option>

                    <option
                        value="tidak_ada"
                        @selected(old('recommendation', $myEvaluation?->recommendation ?? '') === 'tidak_ada')
                    >
                        Tidak Ada
                    </option>

                </select>

            </div>


            <button type="submit">

                @if($myEvaluation)
                    Simpan Penilaian Ulang
                @else
                    Simpan Penilaian
                @endif

            </button>

        </form>

    @endif

</div>


<script>

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

</script>

</body>
</html>
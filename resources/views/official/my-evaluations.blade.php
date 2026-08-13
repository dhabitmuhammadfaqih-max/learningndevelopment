<!DOCTYPE html>
<html>
<head>
    <title>Nilai Saya</title>

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

        button {
            padding: 10px 20px;
            background: #111;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .card {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 10px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .empty {
            color: #999;
        }

        .eval-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 6px;
        }

        .eval-header h2 {
            margin: 0 0 4px 0;
        }

        .eval-header small {
            color: #888;
        }

        .skor-box {
            text-align: center;
            background: #f5f5f5;
            border-radius: 8px;
            padding: 10px 20px;
        }

        .skor-box .angka {
            font-size: 28px;
            font-weight: bold;
        }

        .skor-huruf {
            display: inline-block;
            margin-top: 4px;
            padding: 2px 10px;
            border-radius: 999px;
            background: #e0e7ff;
            color: #3730a3;
            font-size: 12px;
            font-weight: 600;
        }

        table.komponen {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
        }

        table.komponen th,
        table.komponen td {
            text-align: left;
            padding: 10px 8px;
            border-bottom: 1px solid #eee;
            vertical-align: top;
        }

        table.komponen th {
            font-size: 13px;
            color: #666;
        }

        .label {
            font-weight: 600;
        }

        .deskripsi {
            font-size: 12px;
            color: #888;
            font-weight: normal;
            margin-top: 2px;
        }

        .bobot {
            color: #666;
            white-space: nowrap;
        }

        .nilai-komponen {
            font-weight: bold;
        }

        .kontribusi {
            color: #888;
            font-size: 12px;
        }

        .feedback-box {
            margin-top: 16px;
            padding: 14px;
            background: #f9fafb;
            border-radius: 8px;
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

        .rekom-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 999px;
            font-size: 12px;
            background: #dcfce7;
            color: #166534;
            margin-top: 4px;
        }
    </style>
</head>

<body>

<a href="{{ route('official.dashboard') }}" class="back">&larr; Kembali ke Dashboard</a>

<div class="topbar">
    <h1>Nilai Saya</h1>
</div>

<p style="color:#555;">
    Berikut seluruh hasil penilaian yang telah diberikan atasan kepada
    <strong>{{ auth()->user()->name }}</strong>, lengkap per komponen penilaian.
</p>

@if($myOfficialEvaluations->isEmpty())

    <div class="card">
        <p class="empty">Belum ada penilaian yang diterima.</p>
    </div>

@else

    @foreach($myOfficialEvaluations as $evaluation)

        <div class="card">

            <div class="eval-header">
                <div>
                    <h2>Penilaian dari {{ $evaluation->supervisor->name ?? '-' }}</h2>
                    <small>{{ $evaluation->created_at->translatedFormat('d M Y H:i') }}</small>
                </div>

                <div class="skor-box">
                    <div class="angka">{{ $evaluation->score }}/100</div>
                    <span class="skor-huruf">
                        Indeks {{ \App\Models\OfficialEvaluation::scaleIndex((float) $evaluation->score) }}
                    </span>
                </div>
            </div>

            <table class="komponen">
                <tr>
                    <th>Komponen</th>
                    <th>Bobot</th>
                    <th>Nilai (0-100)</th>
                    <th>Kontribusi</th>
                </tr>

                @foreach(\App\Models\OfficialEvaluation::WEIGHTS as $key => $bobot)
                    <tr>
                        <td class="label">
                            {{ \App\Models\OfficialEvaluation::LABELS[$key] }}
                            <div class="deskripsi">{{ \App\Models\OfficialEvaluation::DESCRIPTIONS[$key] }}</div>
                        </td>
                        <td class="bobot">{{ rtrim(rtrim(number_format($bobot, 1), '0'), '.') }}%</td>
                        <td class="nilai-komponen">{{ $evaluation->$key }}</td>
                        <td class="kontribusi">
                            {{ number_format($evaluation->$key * ($bobot / 100), 2) }} poin
                        </td>
                    </tr>
                @endforeach
            </table>

            <div class="feedback-box">
                <strong>Tanggapan Atasan:</strong>
                <p>{{ $evaluation->feedback }}</p>

                <strong>Rekomendasi:</strong>
                <div>
                    <span class="rekom-badge">{{ $evaluation->recommendationLabel() }}</span>
                </div>

                @if($evaluation->kenaikan_gaji_amount)
                    <p style="margin-top:10px;">
                        <strong>Nominal Kenaikan Gaji:</strong>
                        Rp {{ number_format($evaluation->kenaikan_gaji_amount, 0, ',', '.') }}
                    </p>
                @endif

                @if($evaluation->signature)
                    <div>
                        <strong>Tanda Tangan Atasan:</strong>
                        <img src="{{ Storage::disk('public')->url($evaluation->signature) }}" class="signature-saved">
                    </div>
                @endif
            </div>

        </div>

    @endforeach

@endif

</body>
</html>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #000;
            margin: 0;
            padding: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            border: 1px solid #000;
            padding: 6px 8px;
            vertical-align: middle;
        }

        .header-logo {
            width: 20%;
            text-align: center;
            font-size: 9px;
            font-weight: bold;
        }

        .header-title {
            width: 60%;
            text-align: center;
            font-size: 13px;
            font-weight: bold;
            line-height: 1.3;
        }

        .header-meta {
            width: 20%;
            font-size: 9px;
        }

        .doc-title {
            text-align: center;
            font-weight: bold;
            font-size: 12px;
            margin: 12px 0 2px 0;
        }

        .subtitle {
            text-align: center;
            font-size: 10px;
            color: #444;
            margin-bottom: 12px;
        }

        .identitas-box {
            border: 1px solid #000;
            margin-bottom: 10px;
        }

        .identitas-title {
            text-align: center;
            font-weight: bold;
            background: #eee;
            border-bottom: 1px solid #000;
            padding: 4px;
            font-size: 10px;
        }

        .identitas-body {
            padding: 6px 10px;
        }

        .identitas-body table td {
            border: none;
            padding: 2px 0;
            font-size: 10px;
        }

        .identitas-body table td.key {
            width: 32%;
        }

        .identitas-body table td.sep {
            width: 3%;
        }

        table.komponen {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        table.komponen th,
        table.komponen td {
            border: 1px solid #000;
            padding: 5px 6px;
            font-size: 9.5px;
            vertical-align: top;
        }

        table.komponen th {
            background: #eee;
            text-align: center;
            font-weight: bold;
        }

        table.komponen td.nama {
            text-align: left;
        }

        table.komponen td.nama .deskripsi {
            font-size: 8.5px;
            color: #555;
            margin-top: 2px;
        }

        table.komponen td.center {
            text-align: center;
        }

        table.komponen tr.total td {
            font-weight: bold;
            background: #f5f5f5;
        }

        .skor-final {
            text-align: center;
            margin: 10px 0 14px 0;
        }

        .skor-final .angka {
            font-size: 22px;
            font-weight: bold;
        }

        .skor-final .indeks {
            display: inline-block;
            margin-top: 4px;
            padding: 2px 12px;
            border: 1px solid #000;
            border-radius: 3px;
            font-size: 10px;
            font-weight: bold;
        }

        .box {
            border: 1px solid #000;
            margin-bottom: 10px;
        }

        .box-title {
            font-weight: bold;
            background: #eee;
            border-bottom: 1px solid #000;
            padding: 4px 8px;
            font-size: 10px;
        }

        .box-body {
            padding: 8px 10px;
            font-size: 10px;
            line-height: 1.5;
        }

        .rekom-badge {
            display: inline-block;
            padding: 2px 10px;
            border: 1px solid #166534;
            border-radius: 3px;
            font-size: 9.5px;
            margin-top: 2px;
        }

        .ttd-table {
            width: 100%;
            margin-top: 20px;
        }

        .ttd-table td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            font-size: 10px;
            padding: 0 10px;
        }

        .ttd-image {
            height: 60px;
            margin: 6px 0;
        }

        .ttd-line {
            border-top: 1px solid #000;
            margin-top: 45px;
            padding-top: 4px;
            font-weight: bold;
        }

        .footer-note {
            margin-top: 16px;
            font-size: 8.5px;
            color: #666;
            text-align: center;
        }
    </style>
</head>
<body>

    <table class="header-table">
        <tr>
            <td class="header-logo">{{ config('app.name', 'Perusahaan') }}</td>
            <td class="header-title">FORMULIR PENILAIAN PEJABAT</td>
            <td class="header-meta">
                Tanggal: {{ $evaluation->created_at->translatedFormat('d M Y') }}<br>
                No. ID: {{ $evaluation->id }}
            </td>
        </tr>
    </table>

    <div class="doc-title">HASIL PENILAIAN KINERJA PEJABAT</div>
    <div class="subtitle">
        Diberikan oleh atasan pejabat kepada pejabat yang bersangkutan
    </div>

    <div class="identitas-box">
        <div class="identitas-title">IDENTITAS</div>
        <div class="identitas-body">
            <table>
                <tr>
                    <td class="key">Nama Pejabat Dinilai</td>
                    <td class="sep">:</td>
                    <td>{{ $evaluation->official->name ?? auth()->user()->name }}</td>
                </tr>
                <tr>
                    <td class="key">Jabatan</td>
                    <td class="sep">:</td>
                    <td>{{ $evaluation->official->jabatan ?? auth()->user()->jabatan ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="key">Unit Kerja</td>
                    <td class="sep">:</td>
                    <td>{{ $evaluation->official->unit_kerja ?? auth()->user()->unit_kerja ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="key">Dinilai Oleh (Atasan)</td>
                    <td class="sep">:</td>
                    <td>{{ $evaluation->supervisor->name ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="key">Tanggal Penilaian</td>
                    <td class="sep">:</td>
                    <td>{{ $evaluation->created_at->translatedFormat('d M Y H:i') }}</td>
                </tr>
            </table>
        </div>
    </div>

    <table class="komponen">
        <tr>
            <th style="width:40%;">Komponen Penilaian</th>
            <th style="width:12%;">Bobot</th>
            <th style="width:14%;">Nilai (0-100)</th>
            <th style="width:18%;">Kontribusi</th>
        </tr>

        @foreach(\App\Models\OfficialEvaluation::WEIGHTS as $key => $bobot)
            <tr>
                <td class="nama">
                    {{ \App\Models\OfficialEvaluation::LABELS[$key] }}
                    <div class="deskripsi">{{ \App\Models\OfficialEvaluation::DESCRIPTIONS[$key] }}</div>
                </td>
                <td class="center">{{ rtrim(rtrim(number_format($bobot, 1), '0'), '.') }}%</td>
                <td class="center">{{ $evaluation->$key }}</td>
                <td class="center">{{ number_format($evaluation->$key * ($bobot / 100), 0) }}</td>
            </tr>
        @endforeach

        <tr class="total">
            <td colspan="3" class="center">TOTAL NILAI AKHIR</td>
            <td class="center">{{ number_format((float) $evaluation->score, 0) }}</td>
        </tr>
    </table>

    <div class="skor-final">
        <div class="angka">{{ $evaluation->score }} / 100</div>
        <div class="indeks">
            Indeks {{ \App\Models\OfficialEvaluation::scaleIndex((float) $evaluation->score) }}
            &mdash;
            {{ \App\Models\OfficialEvaluation::SCALE[\App\Models\OfficialEvaluation::scaleIndex((float) $evaluation->score)]['label'] }}
        </div>
    </div>

    <div class="box">
        <div class="box-title">Tanggapan Atasan</div>
        <div class="box-body">
            {{ $evaluation->feedback }}
            <br><br>
            <strong>Rekomendasi:</strong>
            <span class="rekom-badge">{{ $evaluation->recommendationLabel() }}</span>

            @if($evaluation->kenaikan_gaji_amount)
                <br><br>
                <strong>Nominal Kenaikan Gaji:</strong>
                Rp {{ number_format($evaluation->kenaikan_gaji_amount, 0, ',', '.') }}
            @endif
        </div>
    </div>

    @if($evaluation->employee_response)
        <div class="box">
            <div class="box-title">Tanggapan Pejabat yang Dinilai</div>
            <div class="box-body">
                {{ $evaluation->employee_response }}
                <br>
                <span style="font-size:8.5px; color:#666;">
                    Dikirim {{ $evaluation->employee_response_at?->translatedFormat('d M Y H:i') }}
                </span>
            </div>
        </div>
    @endif

    <table class="ttd-table">
        <tr>
            <td>
                Pejabat yang Dinilai
                <br>
                <div class="ttd-line">{{ $evaluation->official->name ?? auth()->user()->name }}</div>
            </td>
            <td>
                @if($signature)
                    <img src="{{ $signature }}" class="ttd-image">
                @else
                    <div style="height:60px; margin:6px 0;"></div>
                @endif
                <div class="ttd-line">{{ $evaluation->supervisor->name ?? '-' }}</div>
                Atasan Penilai
            </td>
        </tr>
    </table>

    <div class="footer-note">
        Dokumen ini dibuat otomatis oleh sistem dan sah tanpa memerlukan tanda tangan basah.
    </div>

</body>
</html>

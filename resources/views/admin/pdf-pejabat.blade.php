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

        /* ===== Header Dokumen ===== */
        .header-table td {
            border: 1px solid #000;
            padding: 4px 6px;
            vertical-align: middle;
        }

        .header-logo {
            width: 30%;
            text-align: center;
            font-size: 9px;
            font-weight: bold;
        }

        .header-title {
            width: 50%;
            text-align: center;
            font-size: 13px;
            font-weight: bold;
            line-height: 1.3;
        }

        .header-meta {
            width: 30%;
            font-size: 10px;
            padding: 0 !important;
        }

        .header-meta table {
            width: 100%;
        }

        .header-meta table td {
            border: none;
            border-bottom: 1px solid #000;
            padding: 3px 6px;
        }

        .header-meta table td:first-child {
            width: 45%;
        }

        .header-meta table tr:last-child td {
            border-bottom: none;
        }

        .doc-title {
            text-align: center;
            font-weight: bold;
            font-size: 12px;
            margin: 10px 0 2px 0;
        }

        .periode {
            text-align: center;
            margin-bottom: 8px;
        }

        /* ===== Box Identitas ===== */
        .identitas-box {
            border: 1px solid #000;
            margin-bottom: -1px;
        }

        .identitas-title {
            text-align: center;
            font-weight: bold;
            border-bottom: 1px solid #000;
            padding: 3px;
            font-size: 10px;
        }

        .identitas-body {
            padding: 4px 8px;
        }

        .identitas-body table td {
            border: none;
            padding: 1px 0;
            font-size: 9.5px;
        }

        .identitas-body table td.label {
            width: 120px;
            font-weight: bold;
        }

        .identitas-body table td.colon {
            width: 10px;
        }

        /* ===== Tanda Tangan ===== */
        .ttd-container {
            margin-top: 20px;
            width: 100%;
        }

        .ttd-signature-img {
            height: 42px;
            max-width: 100%;
        }

        .ttd-name {
            display: inline-block;
            border-bottom: 1px solid #000;
            padding: 0 6px 1px 6px;
            margin: 0 auto;
            font-weight: normal;
        }

        /* ===== Highlight rekomendasi ===== */
        .rekomendasi-highlight {
            color: #ff0000;
            font-weight: bold;
        }

        /* ===== Halaman 2: Catatan ===== */
        .page-break {
            page-break-before: always;
        }

        .catatan-box {
            border: 1px solid #000;
        }

        .catatan-header {
            text-align: center;
            font-weight: bold;
            border-bottom: 1px solid #000;
            padding: 4px;
        }

        .catatan-item {
            border-bottom: 1px solid #000;
            padding: 8px;
        }

        .catatan-item:last-child {
            border-bottom: none;
        }

        .catatan-item h4 {
            margin: 0 0 6px 0;
            font-size: 10px;
        }

        .komentar {
            padding: 6px 8px;
            margin-bottom: 6px;
        }

        .komentar .nama {
            font-weight: bold;
            display: block;
            margin-top: 4px;
            text-align: right;
        }

        .empty {
            color: #888;
            font-style: italic;
        }

        .korelasi-grid {
            width: 100%;
            border-collapse: collapse;
        }

        .korelasi-cell {
            width: 50%;
            vertical-align: top;
            padding: 4px 8px;
            border: none;
        }

        .korelasi-text {
            font-size: 9px;
            line-height: 1.4;
        }

        .korelasi-ttd {
            text-align: right;
            margin-top: 4px;
        }

        .korelasi-ttd img {
            height: 45px;
        }

        .korelasi-ttd .nama {
            display: block;
            font-weight: bold;
            font-size: 9px;
        }
    </style>
</head>
<body>

<!-- HEADER DOKUMEN -->
<table class="header-table">
    <tr>
        <td class="header-logo">
            <img src="{{ public_path('images/logo-dagsap.png') }}" alt="Logo Dagsap" style="height:45px; margin-bottom:2px;"><br>
            PT. DAGSAP ENDURA EATORE
        </td>
        <td class="header-title">
            FORM<br>
            PENILAIAN KINERJA<br>
            PEJABAT
        </td>
        <td class="header-meta">
            <table>
                <tr><td>Nomor Dokumen</td><td>: FRM.HRD.03.06</td></tr>
                <tr><td>Revisi</td><td>: 0</td></tr>
                <tr><td>Tanggal Efektif</td><td>: 06 Mei 2013</td></tr>
                <tr><td>Halaman</td><td>: 1 dari 2</td></tr>
            </table>
        </td>
    </tr>
</table>

<div class="doc-title">FORMULIR PENILAIAN KINERJA PEJABAT</div>
<div class="periode">PERIODE : {{ now()->format('Y') }}</div>

<!-- IDENTITAS PEJABAT YANG DINILAI -->
<div class="identitas-box">
    <div class="identitas-title">PEJABAT YANG DINILAI</div>
    <div class="identitas-body">
        <table>
            <tr>
                <td class="label">NAMA</td>
                <td class="colon">:</td>
                <td>{{ $pejabat->name }}</td>
            </tr>
            <tr>
                <td class="label">NIK</td>
                <td class="colon">:</td>
                <td>{{ $pejabat->nik ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">JABATAN</td>
                <td class="colon">:</td>
                <td>{{ $pejabat->jabatan ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">UNIT KERJA</td>
                <td class="colon">:</td>
                <td>{{ $pejabat->unit_kerja ?? '-' }}</td>
            </tr>
        </table>
    </div>
</div>

<!-- IDENTITAS ATASAN PENILAI -->
<div class="identitas-box">
    <div class="identitas-title">ATASAN YANG MENILAI</div>
    <div class="identitas-body">
        <table>
            <tr>
                <td class="label">NAMA</td>
                <td class="colon">:</td>
                <td>{{ $evaluation?->supervisor?->name ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">NIK</td>
                <td class="colon">:</td>
                <td>{{ $evaluation?->supervisor?->nik ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">JABATAN</td>
                <td class="colon">:</td>
                <td>{{ $evaluation?->supervisor?->jabatan ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">UNIT KERJA</td>
                <td class="colon">:</td>
                <td>{{ $evaluation?->supervisor?->unit_kerja ?? '-' }}</td>
            </tr>
        </table>
    </div>
</div>

@php
    $komponen = \App\Models\OfficialEvaluation::LABELS;
    $bobot = \App\Models\OfficialEvaluation::WEIGHTS;

    $kolomIndex = function ($nilai) {
        if ($nilai >= 90) return 'I';
        if ($nilai >= 80) return 'A';
        if ($nilai >= 65) return 'B';
        if ($nilai >= 50) return 'C';
        return 'D';
    };

    $totalBobot = 0;

    $jumlahIzin           = (int) ($pejabat->jumlah_izin      ?? 0);
    $jumlahSakit          = (int) ($pejabat->jumlah_sakit      ?? 0);
    $jumlahAlpa           = (int) ($pejabat->jumlah_alpa       ?? 0);
    $jumlahTerlambat      = (int) ($pejabat->jumlah_terlambat  ?? 0);
    $jumlahKetidakhadiran = $jumlahIzin + $jumlahSakit + $jumlahAlpa + $jumlahTerlambat;
@endphp

{{--
    SATU TABEL BESAR dengan colgroup yang sama persis di semua baris,
    identik dengan struktur di admin/pdf.blade.php (versi pegawai):
    Col 1  : NO           3%
    Col 2  : FAKTOR       29%
    Col 3  : INDEX        7%
    Col 4  : I / Sakit    8%
    Col 5  : A / Ijin     8%
    Col 6  : B / Alpa     8%  (di ketidakhadiran, col 6+7 gabung = Alpa)
    Col 7  : C / —        8%
    Col 8  : D / Terlambat 8%
    Col 9  : NILAI/Jumlah  8%
    Col 10 : KETERANGAN   13%
--}}
<table style="width:100%; border-collapse:collapse; table-layout:auto; margin-top:10px;">

    {{-- Header baris 1 --}}
    <tr style="font-size:9px; text-align:center;">
        <th rowspan="2" style="border:1px solid #000; padding:3px 1px; font-weight:normal;">NO</th>
        <th rowspan="2" style="border:1px solid #000; padding:3px; text-align:left; font-weight:normal;">FAKTOR PENILAIAN</th>
        <th rowspan="2" style="border:1px solid #000; padding:3px; font-weight:normal;">INDEX</th>
        <th style="border:1px solid #000; padding:3px; font-weight:normal;">I</th>
        <th style="border:1px solid #000; padding:3px; font-weight:normal;">A</th>
        <th style="border:1px solid #000; padding:3px; font-weight:normal;">B</th>
        <th style="border:1px solid #000; padding:3px; font-weight:normal;">C</th>
        <th style="border:1px solid #000; padding:3px; font-weight:normal;">D</th>
        <th rowspan="2" style="border:1px solid #000; padding:3px; font-weight:normal;">NILAI</th>
        <th rowspan="2" style="border:1px solid #000; padding:3px; font-weight:normal;">KETERANGAN</th>
    </tr>

    {{-- Header baris 2 --}}
    <tr style="font-size:9px; text-align:center;">
        <th style="border:1px solid #000; padding:3px; font-weight:normal;">90 - 100</th>
        <th style="border:1px solid #000; padding:3px; font-weight:normal;">80 - 89</th>
        <th style="border:1px solid #000; padding:3px; font-weight:normal;">65 - 79</th>
        <th style="border:1px solid #000; padding:3px; font-weight:normal;">50 - 64</th>
        <th style="border:1px solid #000; padding:3px; font-weight:normal;">35 - 49</th>
    </tr>

    {{-- Baris data faktor penilaian --}}
    @foreach ($komponen as $key => $label)
        @php
            $nilaiMentah     = $evaluation?->$key ?? 0;
            $bobotItem       = $bobot[$key];
            $totalBobot     += $bobotItem;
            $kolom           = $kolomIndex($nilaiMentah);
            $nilaiTertimbang = round($nilaiMentah * ($bobotItem / 100));
        @endphp
        <tr style="font-size:9px; text-align:center;">
            <td style="border:1px solid #000; padding:3px 1px;">{{ $loop->iteration }}</td>
            <td style="border:1px solid #000; padding:3px; text-align:left;">{{ $label }}</td>
            <td style="border:1px solid #000; padding:3px;">{{ rtrim(rtrim(number_format($bobotItem, 1), '0'), '.') }}%</td>
            <td style="border:1px solid #000; padding:3px;">{{ $kolom === 'I' ? $nilaiMentah : '' }}</td>
            <td style="border:1px solid #000; padding:3px;">{{ $kolom === 'A' ? $nilaiMentah : '' }}</td>
            <td style="border:1px solid #000; padding:3px;">{{ $kolom === 'B' ? $nilaiMentah : '' }}</td>
            <td style="border:1px solid #000; padding:3px;">{{ $kolom === 'C' ? $nilaiMentah : '' }}</td>
            <td style="border:1px solid #000; padding:3px;">{{ $kolom === 'D' ? $nilaiMentah : '' }}</td>
            <td style="border:1px solid #000; padding:3px;">{{ $nilaiTertimbang }}</td>
            <td style="border:1px solid #000; padding:3px;"></td>
        </tr>
    @endforeach

    {{-- Baris TOTAL --}}
    <tr style="font-size:9px; text-align:center;">
        <td colspan="2" style="border:1px solid #000; padding:3px; text-align:left; padding-left:6px;">TOTAL</td>
        <td style="border:1px solid #000; padding:3px;">{{ rtrim(rtrim(number_format($totalBobot, 1), '0'), '.') }}%</td>
        <td style="border:1px solid #000; padding:3px;"></td>
        <td style="border:1px solid #000; padding:3px;"></td>
        <td style="border:1px solid #000; padding:3px;"></td>
        <td style="border:1px solid #000; padding:3px;"></td>
        <td style="border:1px solid #000; padding:3px;"></td>
        <td style="border:1px solid #000; padding:3px;">{{ $evaluation?->score ?? 0 }}</td>
        <td style="border:1px solid #000; padding:3px;"></td>
    </tr>

    {{-- Baris KETIDAK HADIRAN: header sub-kolom --}}
    <tr style="font-size:9px; text-align:center;">
        <td colspan="3" rowspan="2" style="border:1px solid #000; padding:3px 6px; text-align:center; vertical-align:middle;">KETIDAK HADIRAN</td>
        <td style="border:1px solid #000; padding:2px;">Sakit (S)</td>
        <td style="border:1px solid #000; padding:2px;">Ijin (I)</td>
        <td style="border:1px solid #000; padding:2px;">Alpa (A)</td>
        <td style="border:1px solid #000; padding:2px;">Terlambat (T)</td>
        <td colspan="2" style="border:1px solid #000; padding:2px;">Jumlah</td>
        <td rowspan="2" style="border:1px solid #000; padding:3px;"></td>
    </tr>
    {{-- Baris KETIDAK HADIRAN: nilai --}}
    <tr style="font-size:9px; text-align:center;">
        <td style="border:1px solid #000; padding:6px 2px;">{{ $jumlahSakit }}</td>
        <td style="border:1px solid #000; padding:6px 2px;">{{ $jumlahIzin }}</td>
        <td style="border:1px solid #000; padding:6px 2px;">{{ $jumlahAlpa }}</td>
        <td style="border:1px solid #000; padding:6px 2px;">{{ $jumlahTerlambat }}</td>
        <td colspan="2" style="border:1px solid #000; border-top:none; padding:6px 2px;">{{ $jumlahKetidakhadiran }}</td>
    </tr>

    {{-- Baris JUMLAH PENGURANG --}}
    <tr style="font-size:9px;">
        <td colspan="3" style="border:1px solid #000; padding:3px 6px; text-align:center; vertical-align:middle;">JUMLAH PENGURANG</td>
        <td style="border:1px solid #000; padding:8px 3px;"></td>
        <td style="border:1px solid #000; padding:8px 3px;"></td>
        <td style="border:1px solid #000; padding:8px 3px;"></td>
        <td style="border:1px solid #000; padding:8px 3px;"></td>
        <td colspan="2" style="border:1px solid #000; padding:8px 3px;"></td>
        <td style="border:1px solid #000; padding:8px 3px;"></td>
    </tr>

    {{-- Baris PERINGKAT PENILAIAN: header HASIL-PENILAIAN --}}
    <tr style="font-size:9px; text-align:center;">
        <td colspan="3" rowspan="3" style="border:1px solid #000; padding:3px 6px; text-align:center; vertical-align:middle;">PERINGKAT PENILAIAN</td>
        <td colspan="5" style="border:1px solid #000; border-bottom:none; padding:2px;">HASIL - PENILAIAN</td>
        <td colspan="2" rowspan="3" style="border:1px solid #000; padding:3px;"></td>
    </tr>
    {{-- Baris PERINGKAT PENILAIAN: label I A B C D --}}
    <tr style="font-size:9px; text-align:center;">
        <td style="border:1px solid #000; padding:2px;">I</td>
        <td style="border:1px solid #000; padding:2px;">A</td>
        <td style="border:1px solid #000; padding:2px;">B</td>
        <td style="border:1px solid #000; padding:2px;">C</td>
        <td style="border:1px solid #000; padding:2px;">D</td>
    </tr>
    {{-- Baris PERINGKAT PENILAIAN: nilai kosong --}}
    <tr style="font-size:9px; text-align:center;">
        <td style="border:1px solid #000; border-top:none; padding:8px 2px;"></td>
        <td style="border:1px solid #000; border-top:none; padding:8px 2px;"></td>
        <td style="border:1px solid #000; border-top:none; padding:8px 2px;"></td>
        <td style="border:1px solid #000; border-top:none; padding:8px 2px;"></td>
        <td style="border:1px solid #000; border-top:none; padding:8px 2px;"></td>
    </tr>

</table>

<!-- TANDA TANGAN -->
<div class="ttd-container">
    <table style="width:100%; border-collapse:collapse; table-layout:fixed;">

        {{-- Row 1: Label judul --}}
        <tr>
            <td style="width:33.33%; text-align:center; font-weight:bold; font-size:9px; padding:4px 8px 0 8px;">
                ATASAN PENILAI
            </td>
            <td style="width:33.33%; text-align:center; font-weight:bold; font-size:9px; padding:4px 8px 0 8px;">
                PEJABAT YANG DINILAI
            </td>
            <td style="width:33.33%; text-align:center; font-weight:bold; font-size:9px; padding:4px 8px 0 8px;">
                ATASAN YANG MENILAI
            </td>
        </tr>

        {{-- Row 2: Area tanda tangan --}}
        <tr>
            <td style="text-align:center; height:56px; vertical-align:bottom; padding:0 8px;">
                @if(!empty($signatures['atasan_penilai']))
                    <img src="{{ $signatures['atasan_penilai'] }}" class="ttd-signature-img">
                @endif
            </td>
            <td style="text-align:center; height:56px; vertical-align:bottom; padding:0 8px;">
                @if(!empty($signatures['pejabat']))
                    <img src="{{ $signatures['pejabat'] }}" class="ttd-signature-img">
                @endif
            </td>
            <td style="text-align:center; height:56px; vertical-align:bottom; padding:0 8px;">
                @if(!empty($signatures['atasan']))
                    <img src="{{ $signatures['atasan'] }}" class="ttd-signature-img">
                @endif
            </td>
        </tr>

        {{-- Row 3: Nama (underline) --}}
        <tr>
            <td style="text-align:center; padding:2px 8px;">
                <span class="ttd-name">
                    {{ $officialSupervisorFeedback->supervisor->name ?? '-' }}
                </span>
            </td>
            <td style="text-align:center; padding:2px 8px;">
                <span class="ttd-name">{{ $pejabat->name }}</span>
            </td>
            <td style="text-align:center; padding:2px 8px;">
                <span class="ttd-name">{{ $evaluation?->supervisor?->name ?? '-' }}</span>
            </td>
        </tr>

        {{-- Row 4: Keterangan jabatan --}}
        <tr>
            <td style="text-align:center; font-weight:bold; font-size:9px; padding:2px 8px 0 8px;">
                DIVISI HRD & GA
            </td>
            <td></td>
            <td style="text-align:center; font-weight:bold; font-size:9px; padding:2px 8px 0 8px;">
                KORELASI KERJA
            </td>
        </tr>

        {{-- Row 5: Spasi --}}
        <tr>
            <td colspan="3" style="height:34px;"></td>
        </tr>

        {{-- Row 6: Area tanda tangan bawah --}}
        <tr>
            <td style="text-align:center; height:46px; vertical-align:bottom; padding:0 8px;">
                @if(!empty($signatures['hrd']))
                    <img src="{{ $signatures['hrd'] }}" class="ttd-signature-img">
                @endif
            </td>
            <td></td>
            <td style="text-align:center; height:46px; vertical-align:bottom; padding:0 8px;">
            </td>
        </tr>

        {{-- Row 7: Nama penanda tangan bawah --}}
        <tr>
            <td style="text-align:center; padding:0 8px;">
                <span style="display:inline-block; border-bottom:1px solid #000; min-width:140px; padding-bottom:1px;">&nbsp;</span>
            </td>
            <td></td>
            <td style="text-align:center; padding:0 8px;">
                <span style="display:inline-block; border-bottom:1px solid #000; min-width:140px; padding-bottom:1px;">&nbsp;</span>
            </td>
        </tr>

    </table>
</div>

<!-- HALAMAN 2: CATATAN -->
<div class="page-break"></div>

<div class="catatan-box">
    <div class="catatan-header">CATATAN</div>

    {{-- 1. Keberatan dari pejabat yang dinilai --}}
    <div class="catatan-item">
        <h4>1. KEBERATAN YANG DINILAI</h4>
        @if ($evaluation && $evaluation->employee_response)
            <div class="komentar">
                {!! nl2br(e($evaluation->employee_response)) !!}
            </div>
        @endif
        <div class="korelasi-ttd">
            @if(!empty($signatures['pejabat']))
                <img src="{{ $signatures['pejabat'] }}" class="ttd-signature-img">
            @endif
            <span class="nama">( {{ $pejabat->name }} )</span>
        </div>
    </div>

    {{-- 2. Tanggapan dari Atasan Penilai (yang menilai langsung) --}}
    <div class="catatan-item">
        <h4>2. TANGGAPAN PENILAI</h4>
        @if ($evaluation && $evaluation->feedback)
            <div class="komentar">
                {!! nl2br(e($evaluation->feedback)) !!}
            </div>
        @elseif (! $evaluation && $pejabat->tanggapanPenilaiPejabatManual())
            <div class="komentar">&nbsp;</div>
        @endif
        @if ($evaluation && $evaluation->recommendationLabel() !== 'Tidak Ada')
            <p class="rekomendasi-highlight">{{ $evaluation->recommendationLabel() }}</p>
        @endif
        @if ($evaluation && $evaluation->kenaikan_gaji_amount)
            <p>Nominal Kenaikan Gaji: Rp {{ number_format($evaluation->kenaikan_gaji_amount, 0, ',', '.') }}</p>
        @endif
        <div class="korelasi-ttd">
            @if(!empty($signatures['atasan']))
                <img src="{{ $signatures['atasan'] }}" class="ttd-signature-img">
            @endif
            <span class="nama">( {{ $evaluation?->supervisor?->name ?? $pejabat->supervisor?->name ?? '-' }} )</span>
        </div>
    </div>

    {{-- 3. Tanggapan Atasan Penilai (satu tingkat di atas Atasan yang menilai) --}}
    <div class="catatan-item">
        <h4>3. TANGGAPAN ATASAN PENILAI</h4>
        @if ($officialSupervisorFeedback && $officialSupervisorFeedback->feedback)
            <div class="komentar">
                {!! nl2br(e($officialSupervisorFeedback->feedback)) !!}
            </div>
            @if ($officialSupervisorFeedback->recommendationLabel() !== 'Tidak Ada')
                <p class="rekomendasi-highlight">{{ $officialSupervisorFeedback->recommendationLabel() }}</p>
            @endif
            <div class="korelasi-ttd">
                @if(!empty($signatures['atasan_penilai']))
                    <img src="{{ $signatures['atasan_penilai'] }}" class="ttd-signature-img">
                @endif
                <span class="nama">( {{ $officialSupervisorFeedback->supervisor->name ?? '-' }} )</span>
            </div>
        @else
            <p class="empty">Belum ada tanggapan.</p>
        @endif
    </div>

    {{-- 4. Tanggapan Korelasi (2 kolom, tiap orang dengan tanda tangan) --}}
    <div class="catatan-item">
        <h4>4. TANGGAPAN KORELASI</h4>
        @if ($feedbacks->count())
            <table class="korelasi-grid">
                @foreach ($feedbacks->values()->chunk(2) as $row)
                    <tr>
                        @foreach ($row as $idx => $feedback)
                            @php
                                $korelasiSig = $signatures['korelasi'][$idx]['signature'] ?? null;
                            @endphp
                            <td class="korelasi-cell">
                                <div class="korelasi-text">{{ $feedback->feedback }}</div>
                                <div class="korelasi-ttd">
                                    @if(!empty($korelasiSig))
                                        <img src="{{ $korelasiSig }}">
                                    @endif
                                    <span class="nama">( {{ $feedback->reviewer->name ?? '-' }} )</span>
                                </div>
                            </td>
                        @endforeach
                        @if ($row->count() < 2)
                            <td class="korelasi-cell">&nbsp;</td>
                        @endif
                    </tr>
                @endforeach
            </table>
        @else
            <p class="empty">Belum ada tanggapan dari korelasi.</p>
        @endif
    </div>

    {{-- 5. Hukuman yang pernah diberikan --}}
    <div class="catatan-item">
        <h4>5. HUKUMAN YANG PERNAH DIBERIKAN</h4>
        <p>A. TEGURAN LISAN/TERTULIS : {{ $evaluation?->teguranRingkas() ?? '-' }}</p>
        <p>B. HUKUMAN ADMINISTRASI : -</p>
    </div>
</div>

</body>
</html>

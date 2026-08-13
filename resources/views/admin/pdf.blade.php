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
            width: 20%;
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
            font-size: 9px;
            padding: 0 !important;
        }

        .header-meta table td {
            border: none;
            border-bottom: 1px solid #000;
            padding: 2px 5px;
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
            font-weight: bold;
            margin-bottom: 8px;
        }

        /* ===== Box Identitas ===== */
        .identitas-box {
            border: 1px solid #000;
            margin-bottom: -1px; /* Overlap border agar tidak double */
        }

        .identitas-title {
            text-align: center;
            font-weight: bold;
            background: #eee;
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

        /* ===== Tabel Faktor Penilaian ===== */
        .faktor-table {
            margin-top: 10px;
        }

        .faktor-table th,
        .faktor-table td {
            border: 1px solid #000;
            padding: 3px 4px;
            text-align: center;
            font-size: 9px;
        }

        .faktor-table th {
            background: #eee;
            font-weight: bold;
        }

        .faktor-table td.nama-faktor {
            text-align: left;
        }

        .faktor-table tr.total-row td {
            font-weight: bold;
            background: #f5f5f5;
        }

        /* ===== Box Keterangan / Hasil Penilaian ===== */
        .hasil-box {
            margin-top: 10px;
            border: 1px solid #000;
            padding: 6px;
            font-size: 10px;
        }

        /* ===== 5 Tanda Tangan Table ===== */
        .ttd-container {
            margin-top: 20px;
            width: 100%;
        }

        .ttd-table td {
            width: 20%; /* Split rata 5 Kolom */
            border: 1px solid #000;
            text-align: center;
            vertical-align: top;
            padding: 4px 2px;
            font-size: 8.5px;
            font-weight: bold;
        }

        .ttd-space {
            height: 50px; /* Space area tanda tangan */
        }

        .ttd-signature-img {
            height: 46px;
            max-width: 100%;
        }

        /* Kolom korelasi bisa memuat lebih dari satu tanda tangan
           (satu per pemberi tanggapan korelasi). */
        .ttd-korelasi-list {
            height: 50px;
            overflow: hidden;
            display: flex;
            flex-direction: row;
            justify-content: center;
            align-items: flex-start;
            gap: 2px;
        }

        .ttd-korelasi-item {
            display: flex;
            flex-direction: column;
            align-items: center;
            width: 33%;
        }

        .ttd-korelasi-item img {
            height: 28px;
            max-width: 100%;
        }

        .ttd-korelasi-item span {
            font-size: 6.5px;
            font-weight: normal;
            line-height: 1.1;
            text-align: center;
        }

        .ttd-name {
            display: block;
            border-top: 1px solid #000;
            padding-top: 3px;
            margin: 0 4px;
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
            background: #eee;
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
            border: 1px solid #ddd;
            background: #fafafa;
            padding: 6px 8px;
            margin-bottom: 6px;
            border-radius: 3px;
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
    </style>
</head>
<body>

<!-- HEADER DOKUMEN -->
<table class="header-table">
    <tr>
        <td class="header-logo">
            PT. DAGSAP ENDURA EATORE
        </td>
        <td class="header-title">
            FORM<br>
            PENILAIAN KINERJA<br>
            PEJABAT DAN PEGAWAI
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

<div class="doc-title">FORMULIR PENILAIAN KINERJA PEGAWAI</div>
<div class="periode">PERIODE : {{ now()->format('Y') }}</div>

<!-- IDENTITAS PEGAWAI YANG DINILAI -->
<div class="identitas-box">
    <div class="identitas-title">PEGAWAI YANG DINILAI</div>
    <div class="identitas-body">
        <table>
            <tr>
                <td class="label">NAMA</td><td class="colon">:</td>
                <td>{{ $employee->name }}</td>
                <td class="label">NIK</td><td class="colon">:</td>
                <td>{{ $employee->nik ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">JABATAN</td><td class="colon">:</td>
                <td>{{ $employee->jabatan ?? '-' }}</td>
                <td class="label">UNIT KERJA</td><td class="colon">:</td>
                <td>{{ $employee->unit_kerja ?? '-' }}</td>
            </tr>
        </table>
    </div>
</div>

<!-- IDENTITAS PEJABAT YANG MENILAI -->
<div class="identitas-box">
    <div class="identitas-title">PEJABAT YANG MENILAI</div>
    <div class="identitas-body">
        <table>
            <tr>
                <td class="label">NAMA</td><td class="colon">:</td>
                <td>{{ $evaluation->official->name ?? '-' }}</td>
                <td class="label">NIK</td><td class="colon">:</td>
                <td>{{ $evaluation->official->nik ?? '-' }}</td>
            </tr>
            <tr>
                <td class="label">JABATAN</td><td class="colon">:</td>
                <td>{{ $evaluation->official->jabatan ?? '-' }}</td>
                <td class="label">UNIT KERJA</td><td class="colon">:</td>
                <td>{{ $evaluation->official->unit_kerja ?? '-' }}</td>
            </tr>
        </table>
    </div>
</div>

@php
    $komponen = [
        'pengetahuan_kerja'           => 'Pengetahuan Kerja',
        'penguasaan_peralatan'        => 'Penguasaan Peralatan / Perangkat Kerja',
        'volume_kerja'                => 'Volume Kerja',
        'mutu_tanggung_jawab'         => 'Mutu Tanggung Jawab Pekerjaan',
        'disiplin_dedikasi_loyalitas' => 'Disiplin, Dedikasi & Loyalitas',
        'prakarsa'                    => 'Prakarsa',
        'daya_serap'                  => 'Daya Serap',
        'kerajinan'                   => 'Kerajinan',
        'kerjasama'                   => 'Kerjasama',
    ];

    $bobot = [
        'pengetahuan_kerja'           => 15,
        'penguasaan_peralatan'        => 15,
        'volume_kerja'                => 10,
        'mutu_tanggung_jawab'         => 10,
        'disiplin_dedikasi_loyalitas' => 15,
        'prakarsa'                    => 7.5,
        'daya_serap'                  => 10,
        'kerajinan'                   => 10,
        'kerjasama'                   => 7.5,
    ];

    $kolomIndex = function ($nilai) {
        if ($nilai >= 90) return 'I';
        if ($nilai >= 80) return 'A';
        if ($nilai >= 65) return 'B';
        if ($nilai >= 50) return 'C';
        return 'D';
    };

    $totalBobot = 0;
@endphp

<!-- TABEL FAKTOR PENILAIAN -->
<table class="faktor-table">
    <tr>
        <th rowspan="2" style="width:3%;">NO</th>
        <th rowspan="2" style="width:32%;">FAKTOR PENILAIAN</th>
        <th rowspan="2" style="width:7%;">INDEX</th>
        <th colspan="5">SKOR</th>
        <th rowspan="2" style="width:8%;">NILAI</th>
    </tr>
    <tr>
        <th style="width:8%;">I<br>90-100</th>
        <th style="width:8%;">A<br>80-89</th>
        <th style="width:8%;">B<br>65-79</th>
        <th style="width:8%;">C<br>50-64</th>
        <th style="width:8%;">D<br>35-49</th>
    </tr>

    @foreach ($komponen as $key => $label)
        @php
            $nilaiMentah = $evaluation->$key ?? 0;
            $bobotItem = $bobot[$key];
            $totalBobot += $bobotItem;
            $kolom = $kolomIndex($nilaiMentah);
            $nilaiTertimbang = round($nilaiMentah * ($bobotItem / 100), 2);
        @endphp
        <tr>
            <td>{{ $loop->iteration }}</td>
            <td class="nama-faktor">{{ $label }}</td>
            <td>{{ rtrim(rtrim(number_format($bobotItem, 1), '0'), '.') }}%</td>
            <td>{{ $kolom === 'I' ? $nilaiMentah : '' }}</td>
            <td>{{ $kolom === 'A' ? $nilaiMentah : '' }}</td>
            <td>{{ $kolom === 'B' ? $nilaiMentah : '' }}</td>
            <td>{{ $kolom === 'C' ? $nilaiMentah : '' }}</td>
            <td>{{ $kolom === 'D' ? $nilaiMentah : '' }}</td>
            <td>{{ $nilaiTertimbang }}</td>
        </tr>
    @endforeach

    <tr class="total-row">
        <td colspan="2">TOTAL</td>
        <td>{{ rtrim(rtrim(number_format($totalBobot, 1), '0'), '.') }}%</td>
        <td colspan="5"></td>
        <td>{{ $evaluation->score ?? 0 }}</td>
    </tr>
</table>

<!-- REKOMENDASI & NILAI AKHIR -->
<table style="margin-top:10px;">
    <tr>
        <td style="width:60%; vertical-align: top; border:1px solid #000; padding:6px;">
            <strong>REKOMENDASI / HASIL PENILAIAN:</strong><br>
            {{ $evaluation->recommendationLabel() ?? 'Lulus Probation' }}
            @if($evaluation->kenaikan_gaji_amount)
                <br>Nominal Kenaikan Gaji: Rp {{ number_format($evaluation->kenaikan_gaji_amount, 0, ',', '.') }}
            @endif
        </td>
        <td style="width:40%; vertical-align: top; border:1px solid #000; padding:6px; border-left:none;">
            <strong>PERINGKAT / NILAI AKHIR:</strong><br>
            <span style="font-size: 14px; font-weight: bold;">{{ $evaluation->score ?? 0 }} / 100</span>
        </td>
    </tr>
</table>

<!-- 5 KOLOM TANDA TANGAN -->
<div class="ttd-container">
    <table class="ttd-table">
        <tr>
            <td>
                ATASAN PENILAI
                <div class="ttd-space">
                    @if(!empty($signatures['atasan']))
                        <img src="{{ $signatures['atasan'] }}" class="ttd-signature-img">
                    @endif
                </div>
                <span class="ttd-name">
                    {{ $supervisorFeedback->supervisor->name ?? 'Ishana Mahisa' }}
                </span>
            </td>
            <td>
                DIVISI HRD & GA
                <div class="ttd-space"></div>
                <span class="ttd-name">
                    {{ $hrd->name ?? '-' }}
                </span>
            </td>
            <td>
                KORELASI KERJA
                <div class="ttd-korelasi-list">
                    @forelse($signatures['korelasi'] as $ttd)
                        <div class="ttd-korelasi-item">
                            @if(!empty($ttd['signature']))
                                <img src="{{ $ttd['signature'] }}">
                            @endif
                            <span>{{ $ttd['nama'] }}</span>
                        </div>
                    @empty
                        &nbsp;
                    @endforelse
                </div>
            </td>
            <td>
                PEJABAT YANG MENILAI
                <div class="ttd-space">
                    @if(!empty($signatures['pejabat']))
                        <img src="{{ $signatures['pejabat'] }}" class="ttd-signature-img">
                    @endif
                </div>
                <span class="ttd-name">
                    {{ $evaluation->official->name ?? 'Irawati Tjaturini' }}
                </span>
            </td>
            <td>
                PEGAWAI YANG DINILAI
                <div class="ttd-space"></div>
                <span class="ttd-name">
                    {{ $employee->name }}
                </span>
            </td>
        </tr>
    </table>
</div>

<!-- HALAMAN 2: CATATAN -->
<div class="page-break"></div>

<div class="catatan-box">
    <div class="catatan-header">CATATAN</div>

    <div class="catatan-item">
        <h4>1. KEBERATAN YANG DINILAI</h4>
        <p class="empty">-</p>
    </div>

    <div class="catatan-item">
        <h4>2. TANGGAPAN PENILAI</h4>
        @if ($evaluation && $evaluation->feedback)
            <div class="komentar">
                {!! nl2br(e($evaluation->feedback)) !!}
                <span class="nama">( {{ $evaluation->official->name ?? '-' }} )</span>
            </div>
        @else
            <p class="empty">Belum ada tanggapan.</p>
        @endif
    </div>

    <div class="catatan-item">
        <h4>3. TANGGAPAN ATASAN PENILAI</h4>
        @if ($supervisorFeedback && $supervisorFeedback->feedback)
            <div class="komentar">
                {!! nl2br(e($supervisorFeedback->feedback)) !!}
                <span class="nama">( {{ $supervisorFeedback->supervisor->name ?? '-' }} )</span>
            </div>
        @else
            <p class="empty">Belum ada tanggapan.</p>
        @endif
    </div>

    <div class="catatan-item">
        <h4>4. TANGGAPAN KORELASI</h4>
        @forelse ($feedbacks as $feedback)
            <div class="komentar">
                {{ $feedback->feedback }}
                <span class="nama">( {{ $feedback->reviewer->name }} )</span>
            </div>
        @empty
            <p class="empty">Belum ada tanggapan dari korelasi.</p>
        @endforelse
    </div>

    <div class="catatan-item">
        <h4>5. HUKUMAN YANG PERNAH DIBERIKAN</h4>
        <p>A. TEGURAN LISAN/TERTULIS : -</p>
        <p>B. HUKUMAN ADMINISTRASI : -</p>
    </div>
</div>

</body>
</html>
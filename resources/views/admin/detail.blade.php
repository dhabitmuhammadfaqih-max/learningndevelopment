<x-dashboard-layout :title="(in_array($employee->role, ['pegawai', 'pejabat'], true) ? 'Penilaian' : 'Kehadiran') . ' ' . $employee->name">

<style>
    #hrd-detail {
        font-family: inherit;
        max-width: 920px;
        margin: 0 auto;
    }

    #hrd-detail .back-link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        margin-bottom: 18px;
        color: #475569;
        text-decoration: none;
        font-size: 13.5px;
        font-weight: 600;
    }

    #hrd-detail .back-link:hover {
        color: #2563eb;
    }

    #hrd-detail .profile-banner {
        background: linear-gradient(135deg, #dbeafe 0%, #eef2ff 60%, #f5f3ff 100%);
        border-radius: 24px;
        padding: 26px 28px;
        margin-bottom: 22px;
        display: flex;
        align-items: center;
        gap: 18px;
        flex-wrap: wrap;
    }

    #hrd-detail .avatar {
        width: 56px;
        height: 56px;
        border-radius: 999px;
        background: #2563eb;
        color: white;
        display: grid;
        place-items: center;
        font-weight: 800;
        font-size: 20px;
        flex-shrink: 0;
        box-shadow: 0 6px 16px -6px rgba(37, 99, 235, 0.55);
    }

    #hrd-detail .profile-banner h1 {
        margin: 0 0 4px;
        font-size: 20px;
        font-weight: 800;
        color: #1e293b;
    }

    #hrd-detail .meta-line {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 8px;
        color: #475569;
        font-size: 13px;
    }

    #hrd-detail .alert-success {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 10px 16px;
        border-radius: 999px;
        background: #dcfce7;
        color: #166534;
        font-size: 13px;
        font-weight: 700;
        margin-bottom: 18px;
    }

    #hrd-detail .card {
        background: white;
        padding: 22px 24px;
        margin-bottom: 18px;
        border-radius: 18px;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04), 0 10px 24px -16px rgba(15, 23, 42, 0.25);
        border: 1px solid #f1f5f9;
    }

    #hrd-detail .card h2 {
        margin: 0 0 16px;
        font-size: 15.5px;
        font-weight: 800;
        color: #1e293b;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    #hrd-detail .item {
        border: 1px solid #f1f5f9;
        padding: 14px 16px;
        margin: 10px 0;
        border-radius: 14px;
        background: #f8fafc;
    }

    #hrd-detail .item strong {
        display: block;
        margin-bottom: 6px;
        color: #1e293b;
        font-size: 13.5px;
    }

    #hrd-detail .item p {
        margin: 0;
        color: #475569;
        font-size: 13.5px;
        line-height: 1.5;
    }

    #hrd-detail .signature-saved {
        width: 160px;
        height: 70px;
        object-fit: contain;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        background: white;
        display: block;
        margin-top: 10px;
    }

    #hrd-detail .signature-wrap {
        margin-top: 8px;
        max-width: 500px;
    }

    #hrd-detail .signature-canvas {
        width: 100%;
        max-width: 400px;
        aspect-ratio: 400 / 150;
        border: 1px dashed #cbd5e1;
        border-radius: 12px;
        background: #f8fafc;
        touch-action: none;
        cursor: crosshair;
        display: block;
    }

    @media (min-width: 640px) {
        #hrd-detail .signature-canvas {
            width: 400px;
            height: 150px;
        }
    }

    #hrd-detail .signature-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-top: 8px;
    }

    #hrd-detail .signature-hint {
        font-size: 12px;
        color: #94a3b8;
    }

    #hrd-detail .signature-clear {
        background: none;
        border: none;
        color: #64748b;
        font-size: 12.5px;
        cursor: pointer;
        padding: 4px 8px;
        margin: 0;
        font-weight: 600;
        text-decoration: underline;
    }

    #hrd-detail .score {
        font-size: 34px;
        font-weight: 800;
        color: #1e293b;
        margin: 4px 0 10px;
    }

    #hrd-detail .score small {
        font-size: 15px;
        font-weight: 600;
        color: #94a3b8;
    }

    #hrd-detail .badge {
        display: inline-block;
        padding: 4px 14px;
        border-radius: 999px;
        background: #1e293b;
        color: white;
        font-size: 11.5px;
        font-weight: 700;
        letter-spacing: .02em;
    }

    #hrd-detail .badge-outline {
        background: white;
        color: #475569;
        border: 1px solid #e2e8f0;
    }

    #hrd-detail .badge-vendor {
        background: #fef3c7;
        color: #92400e;
        border: none;
    }

    #hrd-detail .badge-status {
        background: #e0f2fe;
        color: #075985;
        border: none;
    }

    #hrd-detail .empty {
        color: #94a3b8;
        font-size: 13.5px;
        margin: 0;
    }

    #hrd-detail .field-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        gap: 14px;
    }

    #hrd-detail .field-grid label {
        display: block;
        font-size: 12px;
        font-weight: 600;
        color: #64748b;
        margin-bottom: 6px;
    }

    #hrd-detail .field-grid input,
    #hrd-detail select {
        width: 100%;
        padding: 9px 12px;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        box-sizing: border-box;
        font-family: inherit;
        font-size: 14px;
        background: #f8fafc;
    }

    #hrd-detail .field-grid input:focus {
        outline: 2px solid #bfdbfe;
        border-color: #2563eb;
        background: white;
    }

    #hrd-detail button,
    #hrd-detail .btn {
        font-family: inherit;
        padding: 10px 20px;
        background: #2563eb;
        color: white;
        border: none;
        border-radius: 999px;
        cursor: pointer;
        font-size: 13.5px;
        font-weight: 700;
        box-shadow: 0 6px 16px -6px rgba(37, 99, 235, 0.55);
        transition: background .15s ease;
    }

    #hrd-detail button:hover,
    #hrd-detail .btn:hover {
        background: #1d4ed8;
    }

    #hrd-detail .warning {
        background: #fff7ed;
        border: 1px solid #fed7aa;
        color: #9a3412;
        padding: 14px 16px;
        border-radius: 14px;
        font-size: 13.5px;
        line-height: 1.5;
    }

    #hrd-detail .pdf-card {
        text-align: center;
        padding: 26px 24px;
    }
</style>

<div id="hrd-detail">

<div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; margin-bottom:18px;">
    <a href="{{ route($employee->role === 'pejabat' ? 'admin.officials' : 'admin.employees', ['tahun' => $tahun]) }}" class="back-link" style="margin-bottom:0;">
        &larr; Kembali ke {{ $employee->role === 'pejabat' ? 'Lihat Pejabat' : 'Lihat Pegawai' }}
    </a>

    <x-tahun-selector :tahun="$tahun" :options="$availableTahun" />
</div>

@if(session('success'))
    <div class="alert-success">&#10003; {{ session('success') }}</div>
@endif

<div class="profile-banner">
    <div class="avatar">
        {{ strtoupper(mb_substr($employee->name, 0, 1)) }}
    </div>

    <div>
        <h1>{{ $employee->name }}</h1>

        <div class="meta-line">
            <span>{{ $employee->username }}</span>
            <span class="badge">{{ strtoupper($employee->role) }}</span>
            @if($employee->unit_kerja)
                <span>&middot; {{ $employee->unit_kerja }}</span>
            @endif
            @if($employee->jabatan)
                <span>&middot; {{ $employee->jabatan }}</span>
            @endif
            @if($employee->vendor)
                <span class="badge badge-vendor">{{ $employee->vendor }}</span>
            @endif
            @if($employee->status)
                <span class="badge badge-status">{{ $employee->status }}</span>
            @endif
        </div>
    </div>
</div>

<div class="card">
    <h2>&#128203; Kehadiran</h2>

    <p style="margin:0 0 16px; font-size:13px; color:#64748b;">
        Vendor &amp; Status pegawai/pejabat diambil otomatis dari data akun
        (halaman Kelola Akun), tidak perlu diisi ulang di sini:
    </p>
    <p style="margin:-8px 0 16px;">
        @if($employee->vendor)
            <span class="badge badge-vendor">{{ $employee->vendor }}</span>
        @endif
        @if($employee->status)
            <span class="badge badge-status">{{ $employee->status }}</span>
        @endif
        @if(!$employee->vendor && !$employee->status)
            <span class="badge badge-outline">Belum diisi</span>
        @endif
    </p>

    <form method="POST" action="{{ route('admin.employee.attendance.update', $employee->id) }}">
        @csrf
        @method('PUT')

        <div class="field-grid">
            @foreach(\App\Models\User::ATTENDANCE_COUNTERS as $field => $label)
                <div>
                    <label for="{{ $field }}">Jumlah {{ $label }}</label>
                    <input
                        type="number"
                        min="0"
                        id="{{ $field }}"
                        name="{{ $field }}"
                        value="{{ old($field, $employee->{$field} ?? 0) }}"
                    >
                </div>
            @endforeach
        </div>

        <div style="margin-top:18px;">
            <button type="submit">Simpan Kehadiran</button>
        </div>
    </form>
</div>


@if($employee->role === 'pegawai')

<div class="card">
    <h2>&#128172; Tanggapan Korelasi</h2>

    @if($employee->is_spg)
        <p class="empty" style="margin-bottom:10px;">Korelasi bersifat opsional untuk akun SPG.</p>
    @endif

    @forelse($feedbacks as $feedback)
        <div class="item">
            <strong>{{ $feedback->reviewer->name }}</strong>
            <p>{{ $feedback->feedback }}</p>

            @if($feedback->signature)
                <img src="{{ Storage::disk('public')->url($feedback->signature) }}" class="signature-saved">
            @endif
        </div>
    @empty
        <p class="empty">Belum ada tanggapan.</p>
    @endforelse
</div>


<div class="card">
    <h2>&#11088; Penilaian Pejabat</h2>

    @if($evaluation)
        <p class="empty" style="margin-bottom:4px;">
            Pejabat: <strong style="color:#1e293b;">{{ $evaluation->official->name ?? '-' }}</strong>
        </p>

        <div class="score">{{ $evaluation->score }}<small>/100</small></div>

        <p style="color:#475569; font-size:13.5px; line-height:1.5;">{{ $evaluation->feedback }}</p>

        <span class="badge badge-outline">{{ $evaluation->recommendationLabel() }}</span>

        @if($evaluation->kenaikan_gaji_amount)
            <p style="margin-top:12px; font-size:13.5px;">
                <strong>Nominal Kenaikan Gaji:</strong>
                Rp {{ number_format($evaluation->kenaikan_gaji_amount, 0, ',', '.') }}
            </p>
        @endif
    @elseif($employee->penilaianUtamaManual())
        <p class="empty">Diisi manual oleh Penilai.</p>
    @else
        <p class="empty">Belum ada penilaian dari Penilai yang ditugaskan.</p>
    @endif
</div>


<div class="card">
    <h2>&#128221; Tanggapan Pegawai</h2>

    @if($evaluation && $evaluation->employee_response)
        <p style="color:#475569; font-size:13.5px; line-height:1.5; margin:0;">{{ $evaluation->employee_response }}</p>

        @if($evaluation->employee_response_at)
            <p class="empty" style="margin:8px 0 0;">
                Dikirim {{ $evaluation->employee_response_at?->translatedFormat('d M Y H:i') }}
            </p>
        @endif

        @if($evaluation->employee_signature)
            <img src="{{ Storage::disk('public')->url($evaluation->employee_signature) }}" class="signature-saved">
        @endif
    @else
        <p class="empty">Belum ada tanggapan dari pegawai.</p>
    @endif
</div>


<div class="card">
    <h2>&#128100; Tanggapan Atasan</h2>

    @if($atasanEvaluation)
        <p class="empty" style="margin-bottom:4px;">
            Atasan Penilai: <strong style="color:#1e293b;">{{ $atasanEvaluation->official->name ?? '-' }}</strong>
        </p>

        <div class="score">{{ $atasanEvaluation->score }}<small>/100</small></div>

        <p style="color:#475569; font-size:13.5px; line-height:1.5;">{{ $atasanEvaluation->feedback }}</p>

        <span class="badge badge-outline">{{ $atasanEvaluation->recommendationLabel() }}</span>

        @if($atasanEvaluation->kenaikan_gaji_amount)
            <p style="margin-top:12px; font-size:13.5px;">
                <strong>Nominal Kenaikan Gaji:</strong>
                Rp {{ number_format($atasanEvaluation->kenaikan_gaji_amount, 0, ',', '.') }}
            </p>
        @endif
    @elseif($atasanFeedback)
        <p class="empty" style="margin-bottom:4px;">
            Atasan Penilai: <strong style="color:#1e293b;">{{ $atasanFeedback->supervisor->name ?? '-' }}</strong>
        </p>

        <p style="color:#475569; font-size:13.5px; line-height:1.5;">{{ $atasanFeedback->feedback }}</p>

        @if($atasanFeedback->signature)
            <img src="{{ Storage::disk('public')->url($atasanFeedback->signature) }}" class="signature-saved">
        @endif
    @elseif($employee->tanggapanAtasanManual())
        <p class="empty">Diisi manual oleh atasan.</p>
    @else
        <p class="empty">Belum ada tanggapan atasan.</p>
    @endif
</div>


@if($evaluation)
<div class="card">
    <h2>&#9997;&#65039; Tanda Tangan HRD &amp; GA</h2>

    @if($evaluation->hrd_signature)
        <p style="margin:0 0 4px; font-size:13.5px;">
            Ditandatangani oleh <strong>{{ $evaluation->hrd->name ?? '-' }}</strong>
        </p>
        <p class="empty" style="margin:0 0 8px;">
            {{ $evaluation->hrd_signed_at?->translatedFormat('d M Y H:i') }}
        </p>
        <img src="{{ Storage::disk('public')->url($evaluation->hrd_signature) }}" class="signature-saved">
    @elseif(empty($evaluation->employee_signature) && ! $employee->penilaianUtamaManual() && ! $employee->tanggapanAtasanManual())
        <p class="empty">
            Belum bisa ditanda-tangani. Menunggu pegawai memberikan tanggapan &amp; tanda tangan atas penilaiannya sendiri.
        </p>
    @elseif(! $employee->checklistPertemuanLengkap($tahun))
        {{-- Syarat tambahan: checklist pertemuan (Pegawai & Penilai) harus
             lengkap dulu untuk tahun ini sebelum HRD boleh tanda tangan.
             Lihat User::checklistPertemuanLengkap() &
             HrdController::signAsHrd(). --}}
        <p class="empty">
            Belum bisa tanda tangan - checklist Pegawai & Penilai belum lengkap.
        </p>
    @else
        <form method="POST" action="{{ route('admin.employee.sign', $employee->id) }}" id="form-hrd-sign">
            @csrf
            <input type="hidden" name="tahun" value="{{ $tahun }}">

            <div class="signature-wrap">
                <canvas id="hrd-signature-pad" class="signature-canvas"></canvas>
                <div class="signature-actions">
                    <span class="signature-hint">Gambar tanda tangan di kotak di atas</span>
                    <button type="button" class="signature-clear" id="btn-clear-hrd-signature">Hapus &amp; ulangi</button>
                </div>
                <input type="hidden" name="hrd_signature" id="hrd-signature-input">
            </div>

            <div style="margin-top:14px;">
                <button type="submit">Simpan Tanda Tangan</button>
            </div>
        </form>
    @endif
</div>
@endif



<div class="card">
    <h2>&#9989; Checklist Pertemuan &amp; Evaluasi</h2>

    @if($employee->role === 'pejabat')
        <p class="empty" style="margin-bottom:12px;">
            Dicentang mandiri oleh pejabat (dashboard "Nilai Saya") dan atasan (halaman penilaian pejabat).
            Keduanya harus sudah dicentang sebelum PDF bisa dicetak.
        </p>

        <p style="margin:0 0 4px;">
            <span class="badge {{ $employee->pejabatSudahKonfirmasiPertemuan($tahun) ? 'badge-status' : 'badge-outline' }}">
                {{ $employee->pejabatSudahKonfirmasiPertemuan($tahun) ? '✓' : '—' }} Pejabat
            </span>
            <span class="badge {{ $employee->atasanSudahKonfirmasiPertemuan($tahun) ? 'badge-status' : 'badge-outline' }}">
                {{ $employee->atasanSudahKonfirmasiPertemuan($tahun) ? '✓' : '—' }} Atasan
            </span>
        </p>

        @if($employee->pejabat_konfirmasi_pertemuan_at)
            <p class="empty" style="margin:8px 0 0;">
                Pejabat mencentang {{ $employee->pejabat_konfirmasi_pertemuan_at->translatedFormat('d M Y H:i') }}
            </p>
            @if($employee->pejabat_konfirmasi_pertemuan_selfie)
                <p class="empty" style="margin:4px 0 0;">Bukti: {{ \App\Models\User::checklistEvidenceLabel($employee->pejabat_konfirmasi_pertemuan_evidence_type) }}@if (\App\Models\User::checklistMeetingMethodLabel($employee->pejabat_konfirmasi_pertemuan_metode)) ({{ \App\Models\User::checklistMeetingMethodLabel($employee->pejabat_konfirmasi_pertemuan_metode) }})@endif</p>
                <img src="{{ Storage::disk('public')->url($employee->pejabat_konfirmasi_pertemuan_selfie) }}"
                     alt="Bukti checklist Pejabat"
                     style="width:64px;height:64px;object-fit:cover;border-radius:12px;border:1px solid #e2e8f0;margin-top:6px;">
            @endif
        @endif
        @if($employee->atasan_konfirmasi_pertemuan_at)
            <p class="empty" style="margin:8px 0 0;">
                Atasan mencentang {{ $employee->atasan_konfirmasi_pertemuan_at->translatedFormat('d M Y H:i') }}
            </p>
            @if($employee->atasan_konfirmasi_pertemuan_selfie)
                <p class="empty" style="margin:4px 0 0;">Bukti: {{ \App\Models\User::checklistEvidenceLabel($employee->atasan_konfirmasi_pertemuan_evidence_type) }}@if (\App\Models\User::checklistMeetingMethodLabel($employee->atasan_konfirmasi_pertemuan_metode)) ({{ \App\Models\User::checklistMeetingMethodLabel($employee->atasan_konfirmasi_pertemuan_metode) }})@endif</p>
                <img src="{{ Storage::disk('public')->url($employee->atasan_konfirmasi_pertemuan_selfie) }}"
                     alt="Bukti checklist Atasan"
                     style="width:64px;height:64px;object-fit:cover;border-radius:12px;border:1px solid #e2e8f0;margin-top:6px;">
            @endif
        @endif
    @else
        <p class="empty" style="margin-bottom:12px;">
            Dicentang mandiri oleh pegawai (dashboard pegawai) dan penilai (halaman penilaian pejabat).
            Keduanya harus sudah dicentang sebelum PDF bisa dicetak.
        </p>

        <p style="margin:0 0 4px;">
            <span class="badge {{ $employee->pegawaiSudahKonfirmasiPertemuan($tahun) ? 'badge-status' : 'badge-outline' }}">
                {{ $employee->pegawaiSudahKonfirmasiPertemuan($tahun) ? '✓' : '—' }} Pegawai
            </span>
            <span class="badge {{ $employee->penilaiSudahKonfirmasiPertemuan($tahun) ? 'badge-status' : 'badge-outline' }}">
                {{ $employee->penilaiSudahKonfirmasiPertemuan($tahun) ? '✓' : '—' }} Penilai
            </span>
        </p>

        @if($employee->pegawai_konfirmasi_pertemuan_at)
            <p class="empty" style="margin:8px 0 0;">
                Pegawai mencentang {{ $employee->pegawai_konfirmasi_pertemuan_at->translatedFormat('d M Y H:i') }}
            </p>
            @if($employee->pegawai_konfirmasi_pertemuan_selfie)
                <p class="empty" style="margin:4px 0 0;">Bukti: {{ \App\Models\User::checklistEvidenceLabel($employee->pegawai_konfirmasi_pertemuan_evidence_type) }}@if (\App\Models\User::checklistMeetingMethodLabel($employee->pegawai_konfirmasi_pertemuan_metode)) ({{ \App\Models\User::checklistMeetingMethodLabel($employee->pegawai_konfirmasi_pertemuan_metode) }})@endif</p>
                <img src="{{ Storage::disk('public')->url($employee->pegawai_konfirmasi_pertemuan_selfie) }}"
                     alt="Bukti checklist Pegawai"
                     style="width:64px;height:64px;object-fit:cover;border-radius:12px;border:1px solid #e2e8f0;margin-top:6px;">
            @endif
        @endif
        @if($employee->penilai_konfirmasi_pertemuan_at)
            <p class="empty" style="margin:8px 0 0;">
                Penilai mencentang {{ $employee->penilai_konfirmasi_pertemuan_at->translatedFormat('d M Y H:i') }}
            </p>
            @if($employee->penilai_konfirmasi_pertemuan_selfie)
                <p class="empty" style="margin:4px 0 0;">Bukti: {{ \App\Models\User::checklistEvidenceLabel($employee->penilai_konfirmasi_pertemuan_evidence_type) }}@if (\App\Models\User::checklistMeetingMethodLabel($employee->penilai_konfirmasi_pertemuan_metode)) ({{ \App\Models\User::checklistMeetingMethodLabel($employee->penilai_konfirmasi_pertemuan_metode) }})@endif</p>
                <img src="{{ Storage::disk('public')->url($employee->penilai_konfirmasi_pertemuan_selfie) }}"
                     alt="Bukti checklist Penilai"
                     style="width:64px;height:64px;object-fit:cover;border-radius:12px;border:1px solid #e2e8f0;margin-top:6px;">
            @endif
        @endif
    @endif
</div>


@php
    // Minimal 3 korelasi TIDAK berlaku untuk akun dengan flag is_spg (opsional).
    $korelasiOk = $employee->is_spg || $feedbacks->count() >= 3;
    // Tanggapan Atasan TIDAK wajib kalau akun ini ditandai
    // tanggapan_atasan_manual - atasan akan mengisinya manual di luar
    // sistem (lihat User::tanggapanAtasanManual()).
    $atasanOk = $atasanEvaluation || $atasanFeedback || $employee->tanggapanAtasanManual();
    // Penilaian utama TIDAK wajib kalau Penilai (users.supervisor_id)
    // pegawai ini menilai secara manual - lihat User::penilaianUtamaManual()
    // & HrdController::pdf().
    $penilaianUtamaOk = $evaluation || $employee->penilaianUtamaManual();
    // Checklist pertemuan pegawai & penilai harus lengkap juga - lihat
    // User::checklistPertemuanLengkap() & HrdController::pdf().
    $checklistOk = $employee->checklistPertemuanLengkap($tahun);
    // Pegawai wajib mengisi tanggapan atas penilaiannya sendiri sebelum
    // PDF boleh dicetak - lihat Evaluation::employee_response &
    // HrdController::pdf(). Dilewati kalau $evaluation belum ada (mode
    // manual, sama seperti $penilaianUtamaOk di atas).
    $employeeResponseOk = ! $evaluation || ! empty($evaluation->employee_response);
    $pdfReady = $korelasiOk && $penilaianUtamaOk && $atasanOk && $checklistOk && $employeeResponseOk;
@endphp

<div class="card pdf-card">
    @if($pdfReady)
        <a href="{{ route('admin.pdf', ['id' => $employee->id, 'tahun' => $tahun]) }}">
            <button type="button">&#128196; Generate PDF Penilaian</button>
        </a>
    @else
        <div class="warning">
            PDF belum tersedia. Pastikan
            @if(! $employee->is_spg)
                minimal 3 tanggapan korelasi,
            @endif
            penilaian pejabat, tanggapan atasan,
            @if(! $employeeResponseOk)
                tanggapan pegawai atas penilaiannya sendiri,
            @endif
            @if(! $checklistOk)
                dan checklist pertemuan &amp; evaluasi (pegawai &amp; penilai)
            @endif
            sudah tersedia.
        </div>
    @endif
</div>

@elseif($employee->role === 'pejabat')

<div class="card">
    <h2>&#128172; Tanggapan Korelasi</h2>

    @forelse($feedbacks as $feedback)
        <div class="item">
            <strong>{{ $feedback->reviewer->name }}</strong>
            <p>{{ $feedback->feedback }}</p>

            @if($feedback->signature)
                <img src="{{ Storage::disk('public')->url($feedback->signature) }}" class="signature-saved">
            @endif
        </div>
    @empty
        <p class="empty">Belum ada tanggapan.</p>
    @endforelse
</div>


<div class="card">
    <h2>&#11088; Penilaian Atasan Pejabat</h2>

    @if($officialEvaluation)
        <p class="empty" style="margin-bottom:4px;">
            Atasan Penilai: <strong style="color:#1e293b;">{{ $officialEvaluation->supervisor->name ?? '-' }}</strong>
        </p>

        <div class="score">{{ $officialEvaluation->score }}<small>/100</small></div>

        <p style="color:#475569; font-size:13.5px; line-height:1.5;">{{ $officialEvaluation->feedback }}</p>

        <span class="badge badge-outline">{{ $officialEvaluation->recommendationLabel() }}</span>

        @if($officialEvaluation->kenaikan_gaji_amount)
            <p style="margin-top:12px; font-size:13.5px;">
                <strong>Nominal Kenaikan Gaji:</strong>
                Rp {{ number_format($officialEvaluation->kenaikan_gaji_amount, 0, ',', '.') }}
            </p>
        @endif

        @if($officialEvaluation->signature)
            <img src="{{ Storage::disk('public')->url($officialEvaluation->signature) }}" class="signature-saved">
        @endif
    @else
        <p class="empty">Belum ada penilaian dari Atasan Pejabat yang ditugaskan.</p>
    @endif
</div>


<div class="card">
    <h2>&#128221; Tanggapan Atasan Penilai</h2>

    @if($officialSupervisorFeedback)
        <p class="empty" style="margin-bottom:4px;">
            Atasan Penilai: <strong style="color:#1e293b;">{{ $officialSupervisorFeedback->supervisor->name ?? '-' }}</strong>
        </p>

        <p style="color:#475569; font-size:13.5px; line-height:1.5; margin:0;">{{ $officialSupervisorFeedback->feedback }}</p>

        @if($officialSupervisorFeedback->recommendation)
            <span class="badge badge-outline" style="margin-top:12px;">{{ $officialSupervisorFeedback->recommendationLabel() }}</span>
        @endif

        @if($officialSupervisorFeedback->kenaikan_gaji_amount)
            <p style="margin-top:12px; font-size:13.5px;">
                <strong>Nominal Kenaikan Gaji:</strong>
                Rp {{ number_format($officialSupervisorFeedback->kenaikan_gaji_amount, 0, ',', '.') }}
            </p>
        @endif

        @if($officialSupervisorFeedback->signature)
            <img src="{{ Storage::disk('public')->url($officialSupervisorFeedback->signature) }}" class="signature-saved">
        @endif
    @else
        <p class="empty">Belum ada tanggapan dari atasan penilai.</p>
    @endif
</div>

<div class="card">
    <h2>&#128221; Tanggapan Pejabat yang Dinilai</h2>

    @if($officialEvaluation && $officialEvaluation->employee_response)
        <p style="color:#475569; font-size:13.5px; line-height:1.5; margin:0;">{{ $officialEvaluation->employee_response }}</p>

        @if($officialEvaluation->employee_response_at)
            <p class="empty" style="margin:8px 0 0;">
                Dikirim {{ $officialEvaluation->employee_response_at?->translatedFormat('d M Y H:i') }}
            </p>
        @endif
    @else
        <p class="empty">Belum ada tanggapan dari pejabat yang dinilai.</p>
    @endif
</div>


@if($officialEvaluation)
<div class="card">
    <h2>&#9997;&#65039; Tanda Tangan HRD &amp; GA</h2>

    @if($officialEvaluation->hrd_signature)
        <p style="margin:0 0 4px; font-size:13.5px;">
            Ditandatangani oleh <strong>{{ $officialEvaluation->hrd->name ?? '-' }}</strong>
        </p>
        <p class="empty" style="margin:0 0 8px;">
            {{ $officialEvaluation->hrd_signed_at?->translatedFormat('d M Y H:i') }}
        </p>
        <img src="{{ Storage::disk('public')->url($officialEvaluation->hrd_signature) }}" class="signature-saved">
    @elseif(empty($officialEvaluation->employee_signature) && ! $employee->tanggapanPenilaiPejabatManual())
        <p class="empty">
            Belum bisa ditanda-tangani. Menunggu pejabat memberikan tanggapan &amp; tanda tangan atas penilaiannya sendiri.
        </p>
    @elseif(! $employee->checklistPertemuanPejabatLengkap($tahun))
        {{-- Syarat tambahan: checklist pertemuan (Pejabat & Atasan) harus
             lengkap dulu untuk tahun ini sebelum HRD boleh tanda tangan.
             Lihat User::checklistPertemuanPejabatLengkap() &
             HrdController::signAsHrdOfficial(). --}}
        <p class="empty">
            Belum bisa tanda tangan - checklist Pejabat & Atasan belum lengkap.
        </p>
    @else
        <form method="POST" action="{{ route('admin.official.sign', $employee->id) }}" id="form-hrd-sign-official">
            @csrf
            <input type="hidden" name="tahun" value="{{ $tahun }}">

            <div class="signature-wrap">
                <canvas id="hrd-signature-pad-official" class="signature-canvas"></canvas>
                <div class="signature-actions">
                    <span class="signature-hint">Gambar tanda tangan di kotak di atas</span>
                    <button type="button" class="signature-clear" id="btn-clear-hrd-signature-official">Hapus &amp; ulangi</button>
                </div>
                <input type="hidden" name="hrd_signature" id="hrd-signature-input-official">
            </div>

            <div style="margin-top:14px;">
                <button type="submit">Simpan Tanda Tangan</button>
            </div>
        </form>
    @endif
</div>
@endif

<div class="card">
    <h2>&#9989; Checklist Pertemuan &amp; Evaluasi</h2>

    <p class="empty" style="margin-bottom:12px;">
        Dicentang mandiri oleh pejabat (dashboard "Nilai Saya") dan atasan (halaman penilaian pejabat).
        Keduanya harus sudah dicentang sebelum PDF bisa dicetak.
    </p>

    <p style="margin:0 0 4px;">
        <span class="badge {{ $employee->pejabatSudahKonfirmasiPertemuan($tahun) ? 'badge-status' : 'badge-outline' }}">
            {{ $employee->pejabatSudahKonfirmasiPertemuan($tahun) ? '✓' : '—' }} Pejabat
        </span>
        <span class="badge {{ $employee->atasanSudahKonfirmasiPertemuan($tahun) ? 'badge-status' : 'badge-outline' }}">
            {{ $employee->atasanSudahKonfirmasiPertemuan($tahun) ? '✓' : '—' }} Atasan
        </span>
    </p>

    @if($employee->pejabat_konfirmasi_pertemuan_at)
        <p class="empty" style="margin:8px 0 0;">
            Pejabat mencentang {{ $employee->pejabat_konfirmasi_pertemuan_at->translatedFormat('d M Y H:i') }}
        </p>
        @if($employee->pejabat_konfirmasi_pertemuan_selfie)
            <p class="empty" style="margin:4px 0 0;">Bukti: {{ \App\Models\User::checklistEvidenceLabel($employee->pejabat_konfirmasi_pertemuan_evidence_type) }}@if (\App\Models\User::checklistMeetingMethodLabel($employee->pejabat_konfirmasi_pertemuan_metode)) ({{ \App\Models\User::checklistMeetingMethodLabel($employee->pejabat_konfirmasi_pertemuan_metode) }})@endif</p>
            <img src="{{ Storage::disk('public')->url($employee->pejabat_konfirmasi_pertemuan_selfie) }}"
                 alt="Bukti checklist Pejabat"
                 style="width:64px;height:64px;object-fit:cover;border-radius:12px;border:1px solid #e2e8f0;margin-top:6px;">
        @endif
    @endif
    @if($employee->atasan_konfirmasi_pertemuan_at)
        <p class="empty" style="margin:8px 0 0;">
            Atasan mencentang {{ $employee->atasan_konfirmasi_pertemuan_at->translatedFormat('d M Y H:i') }}
        </p>
        @if($employee->atasan_konfirmasi_pertemuan_selfie)
            <p class="empty" style="margin:4px 0 0;">Bukti: {{ \App\Models\User::checklistEvidenceLabel($employee->atasan_konfirmasi_pertemuan_evidence_type) }}@if (\App\Models\User::checklistMeetingMethodLabel($employee->atasan_konfirmasi_pertemuan_metode)) ({{ \App\Models\User::checklistMeetingMethodLabel($employee->atasan_konfirmasi_pertemuan_metode) }})@endif</p>
            <img src="{{ Storage::disk('public')->url($employee->atasan_konfirmasi_pertemuan_selfie) }}"
                 alt="Bukti checklist Atasan"
                 style="width:64px;height:64px;object-fit:cover;border-radius:12px;border:1px solid #e2e8f0;margin-top:6px;">
        @endif
    @endif
</div>

@php
    $korelasiOkPejabat = $feedbacks->count() >= \App\Models\User::MIN_TANGGAPAN_KORELASI;
    // Penilaian dari Atasan Pejabat (OfficialEvaluation) TIDAK wajib
    // kalau Penilai (users.supervisor_id) pejabat ini menilai secara
    // manual - sama seperti bypass di HrdController::officialPdf() lewat
    // User::tanggapanPenilaiPejabatManual(). Sebelumnya syarat ini
    // mewajibkan $officialEvaluation ada tanpa pengecualian, jadi tombol
    // PDF tidak pernah muncul untuk pejabat yang penilainya menilai
    // manual walau backend sebenarnya sudah mengizinkan.
    $penilaianAtasanPejabatOk = $officialEvaluation || $employee->tanggapanPenilaiPejabatManual();
    // Checklist pertemuan pejabat & atasan harus lengkap juga, dan HRD
    // harus sudah tanda tangan - syarat ini harus konsisten dengan
    // HrdController::officialPdf() supaya tombol di sini tidak menyesatkan
    // (tombol muncul tapi backend tetap menolak generate PDF-nya).
    $checklistOkPejabat = $employee->checklistPertemuanPejabatLengkap($tahun);
    // Kalau $officialEvaluation belum ada (mode manual, penilai akan
    // mengisi di luar aplikasi), belum ada baris untuk ditandatangani
    // HRD, jadi syarat tanda tangan ikut dilewati - sama seperti
    // pengecekan di HrdController::officialPdf(). SAMA HALNYA kalau
    // Penilai (users.supervisor_id) pejabat ini menilai secara manual
    // (User::tanggapanPenilaiPejabatManual()) walau baris
    // OfficialEvaluation-nya sudah ada.
    $hrdSignedPejabat = ! $officialEvaluation
        || (bool) ($officialEvaluation->hrd_signature ?? null)
        || $employee->tanggapanPenilaiPejabatManual();
    // Pejabat wajib mengisi tanggapan atas penilaiannya sendiri sebelum
    // PDF boleh dicetak - lihat OfficialEvaluation::employee_response &
    // HrdController::officialPdf(). Dilewati kalau $officialEvaluation
    // belum ada (mode manual, sama seperti $penilaianAtasanPejabatOk)
    // ATAU kalau Penilai/Atasan Penilai pejabat ini menilai secara manual
    // (User::tanggapanPenilaiPejabatManual()) - harus konsisten dengan
    // HrdController::officialPdf() supaya tombol di sini tidak
    // menyesatkan.
    $employeeResponseOkPejabat = ! $officialEvaluation
        || ! empty($officialEvaluation->employee_response)
        || $employee->tanggapanPenilaiPejabatManual();
    $pdfReadyPejabat = $korelasiOkPejabat && $penilaianAtasanPejabatOk && $checklistOkPejabat && $hrdSignedPejabat && $employeeResponseOkPejabat;
@endphp

<div class="card pdf-card">
    @if($pdfReadyPejabat)
        <a href="{{ route('admin.pdf', ['id' => $employee->id, 'tahun' => $tahun]) }}">
            <button type="button">&#128196; Generate PDF Penilaian</button>
        </a>
    @else
        <div class="warning">
            PDF belum tersedia. Pastikan minimal
            {{ \App\Models\User::MIN_TANGGAPAN_KORELASI }}
            tanggapan korelasi dan penilaian atasan pejabat sudah tersedia,
            @if(! $checklistOkPejabat)
                checklist pertemuan pejabat & atasan sudah dicentang
                ({{ $employee->pejabatSudahKonfirmasiPertemuan($tahun) ? 'pejabat sudah centang' : 'pejabat belum centang' }},
                {{ $employee->atasanSudahKonfirmasiPertemuan($tahun) ? 'atasan sudah centang' : 'atasan belum centang' }}),
            @endif
            @if(! $employeeResponseOkPejabat)
                tanggapan pejabat atas penilaiannya sendiri sudah diisi,
            @endif
            dan HRD sudah menandatangani penilaian ini
            ({{ $hrdSignedPejabat ? 'sudah' : 'belum' }} tanda tangan).
        </div>
    @endif
</div>

@else


<div class="card">
    <p class="empty">
        Halaman ini khusus mengelola kehadiran untuk akun
        ber-role {{ strtoupper($employee->role) }}. Penilaian kinerja, tanggapan
        korelasi, dan PDF hanya berlaku untuk akun pegawai dan pejabat.
    </p>
</div>

@endif

</div>

<script>
    const hrdCanvas = document.getElementById('hrd-signature-pad');

    if (hrdCanvas) {
        const ctx = hrdCanvas.getContext('2d');
        const ratio = window.devicePixelRatio || 1;

        function resizeHrdCanvas() {
            hrdCanvas.width = hrdCanvas.clientWidth * ratio;
            hrdCanvas.height = hrdCanvas.clientHeight * ratio;
            ctx.scale(ratio, ratio);
            ctx.lineCap = 'round';
            ctx.lineJoin = 'round';
            ctx.lineWidth = 2.2;
            ctx.strokeStyle = '#111';
        }
        resizeHrdCanvas();

        let drawing = false;
        let last = null;
        let hasStroke = false;

        function getPos(e) {
            const rect = hrdCanvas.getBoundingClientRect();
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

        hrdCanvas.addEventListener('mousedown', start);
        hrdCanvas.addEventListener('mousemove', move);
        hrdCanvas.addEventListener('mouseup', end);
        hrdCanvas.addEventListener('mouseleave', end);
        hrdCanvas.addEventListener('touchstart', start);
        hrdCanvas.addEventListener('touchmove', move);
        hrdCanvas.addEventListener('touchend', end);

        document.getElementById('btn-clear-hrd-signature').addEventListener('click', function () {
            ctx.clearRect(0, 0, hrdCanvas.clientWidth, hrdCanvas.clientHeight);
            hasStroke = false;
        });

        const hrdForm = document.getElementById('form-hrd-sign');
        const hrdSignatureInput = document.getElementById('hrd-signature-input');

        hrdForm.addEventListener('submit', function (e) {
            if (!hasStroke) {
                e.preventDefault();
                alert('Tanda tangan wajib diisi.');
                return;
            }
            hrdSignatureInput.value = hrdCanvas.toDataURL('image/png');
        });
    }

    // Signature pad untuk tanda tangan HRD pejabat
    const hrdCanvasOfficial = document.getElementById('hrd-signature-pad-official');

    if (hrdCanvasOfficial) {
        const ctxO = hrdCanvasOfficial.getContext('2d');
        const ratioO = window.devicePixelRatio || 1;

        function resizeHrdCanvasOfficial() {
            hrdCanvasOfficial.width = hrdCanvasOfficial.clientWidth * ratioO;
            hrdCanvasOfficial.height = hrdCanvasOfficial.clientHeight * ratioO;
            ctxO.scale(ratioO, ratioO);
            ctxO.lineCap = 'round';
            ctxO.lineJoin = 'round';
            ctxO.lineWidth = 2.2;
            ctxO.strokeStyle = '#111';
        }
        resizeHrdCanvasOfficial();

        let drawingO = false;
        let lastO = null;
        let hasStrokeO = false;

        function getPosO(e) {
            const rect = hrdCanvasOfficial.getBoundingClientRect();
            const point = e.touches ? e.touches[0] : e;
            return { x: point.clientX - rect.left, y: point.clientY - rect.top };
        }

        function startO(e) { e.preventDefault(); drawingO = true; hasStrokeO = true; lastO = getPosO(e); }
        function moveO(e) {
            if (!drawingO) return;
            e.preventDefault();
            const pos = getPosO(e);
            ctxO.beginPath();
            ctxO.moveTo(lastO.x, lastO.y);
            ctxO.lineTo(pos.x, pos.y);
            ctxO.stroke();
            lastO = pos;
        }
        function endO() { drawingO = false; }

        hrdCanvasOfficial.addEventListener('mousedown', startO);
        hrdCanvasOfficial.addEventListener('mousemove', moveO);
        hrdCanvasOfficial.addEventListener('mouseup', endO);
        hrdCanvasOfficial.addEventListener('mouseleave', endO);
        hrdCanvasOfficial.addEventListener('touchstart', startO);
        hrdCanvasOfficial.addEventListener('touchmove', moveO);
        hrdCanvasOfficial.addEventListener('touchend', endO);

        document.getElementById('btn-clear-hrd-signature-official').addEventListener('click', function () {
            ctxO.clearRect(0, 0, hrdCanvasOfficial.clientWidth, hrdCanvasOfficial.clientHeight);
            hasStrokeO = false;
        });

        const hrdFormOfficial = document.getElementById('form-hrd-sign-official');
        const hrdSignatureInputOfficial = document.getElementById('hrd-signature-input-official');

        hrdFormOfficial.addEventListener('submit', function (e) {
            if (!hasStrokeO) {
                e.preventDefault();
                alert('Tanda tangan wajib diisi.');
                return;
            }
            hrdSignatureInputOfficial.value = hrdCanvasOfficial.toDataURL('image/png');
        });
    }
</script>

</x-dashboard-layout>
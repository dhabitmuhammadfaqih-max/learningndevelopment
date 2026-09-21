{{--
    Ringkasan bukti checklist "sudah bertemu & evaluasi" untuk halaman
    HRD (resources/views/admin/detail.blade.php).

    Dibuat karena blok ini dulu disalin 8x di detail.blade.php (4 peran
    x 2 bagian halaman) dan semuanya hanya bisa menampilkan GAMBAR -
    begitu bukti Offline diganti kode pertemuan (lihat
    App\Models\MeetingCode), kedelapannya harus ikut berubah. Sekarang
    cukup satu tempat.

    Props:
    - $user   (App\Models\User) baris pegawai/pejabat yang dinilai -
      semua kolom checklist memang disimpan di baris pihak yang dinilai.
    - $prefix (string) 'pegawai' | 'penilai' | 'pejabat' | 'atasan'
    - $label  (string) nama peran untuk ditampilkan, mis. 'Penilai'
--}}
@php
    $kode         = $user->{$prefix . '_konfirmasi_pertemuan_kode'};
    $file         = $user->{$prefix . '_konfirmasi_pertemuan_selfie'};
    $evidenceType = $user->{$prefix . '_konfirmasi_pertemuan_evidence_type'};
    $metode       = \App\Models\User::checklistMeetingMethodLabel(
        $user->{$prefix . '_konfirmasi_pertemuan_metode'}
    );
    $adalahKode   = \App\Models\User::checklistEvidenceAdalahKode($evidenceType);
@endphp

@if($kode || $file)
    <p class="empty" style="margin:4px 0 0;">
        Bukti: {{ \App\Models\User::checklistEvidenceLabel($evidenceType) }}@if($adalahKode && $kode) (Kode {{ $kode }})@elseif($metode) ({{ $metode }})@endif
    </p>

    {{-- Bukti berupa kode tidak punya gambar apa pun - kodenya sendiri
         sudah tercetak di baris di atas. Gambar hanya untuk metode
         Online (upload) & data lama bermetode selfie. --}}
    @if(! $adalahKode && $file)
        <img src="{{ Storage::disk('public')->url($file) }}"
             alt="Bukti checklist {{ $label }}"
             style="width:64px;height:64px;object-fit:cover;border-radius:12px;border:1px solid #e2e8f0;margin-top:6px;">
    @endif
@endif

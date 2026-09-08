{{--
    Partial ringkasan kehadiran: Izin, Sakit, Alpa, Terlambat, Total Waktu
    Terlambat, dan Tanggal Masuk (+ masa kerja).

    Dipakai di:
    - employee/dashboard.blade.php  (pegawai lihat kehadirannya sendiri)
    - official/dashboard.blade.php  (pejabat lihat kehadirannya sendiri)
    - official/evaluate.blade.php   (pejabat/penilai menilai pegawai)
    - supervisor/evaluate_official.blade.php (atasan penilai menilai pejabat)

    Variabel yang WAJIB dikirim lewat @include:
    - $kehadiranUser : instance App\Models\User yang datanya mau ditampilkan
--}}
<div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
    <div class="rounded-2xl bg-slate-50 px-4 py-3 min-w-0">
        <p class="text-xl font-extrabold text-slate-800 break-words">{{ $kehadiranUser->jumlah_izin ?? 0 }}</p>
        <p class="text-[11px] text-slate-400 mt-0.5">Izin</p>
    </div>
    <div class="rounded-2xl bg-slate-50 px-4 py-3 min-w-0">
        <p class="text-xl font-extrabold text-slate-800 break-words">{{ $kehadiranUser->jumlah_sakit ?? 0 }}</p>
        <p class="text-[11px] text-slate-400 mt-0.5">Sakit</p>
    </div>
    <div class="rounded-2xl bg-slate-50 px-4 py-3 min-w-0">
        <p class="text-xl font-extrabold text-slate-800 break-words">{{ $kehadiranUser->jumlah_alpa ?? 0 }}</p>
        <p class="text-[11px] text-slate-400 mt-0.5">Alpa</p>
    </div>
    <div class="rounded-2xl bg-slate-50 px-4 py-3 min-w-0">
        <p class="text-xl font-extrabold text-slate-800 break-words">{{ $kehadiranUser->jumlah_terlambat ?? 0 }}</p>
        <p class="text-[11px] text-slate-400 mt-0.5">Terlambat</p>
    </div>
    <div class="rounded-2xl bg-slate-50 px-4 py-3 min-w-0">
        <p class="text-base sm:text-lg lg:text-xl font-extrabold text-slate-800 break-words leading-tight">{{ $kehadiranUser->menit_terlambat_formatted }}</p>
        <p class="text-[11px] text-slate-400 mt-0.5">Total Waktu Terlambat</p>
    </div>
    <div class="rounded-2xl bg-slate-50 px-4 py-3 min-w-0">
        <p class="text-base sm:text-lg lg:text-xl font-extrabold text-slate-800 break-words leading-tight">{{ optional($kehadiranUser->tanggal_masuk)->format('d/m/Y') ?? '-' }}</p>
        <p class="text-[11px] text-slate-400 mt-0.5">Tanggal Masuk</p>
    </div>
</div>

@if ($kehadiranUser->masa_kerja)
    <p class="text-[11px] text-slate-400 mt-3">Masa kerja: <span class="font-semibold text-slate-500">{{ $kehadiranUser->masa_kerja }}</span></p>
@endif

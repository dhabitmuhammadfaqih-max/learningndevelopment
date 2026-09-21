<x-dashboard-layout title="Permintaan Kode Pertemuan">

<div class="max-w-4xl mx-auto px-4 py-6">

    <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-xl font-bold text-slate-800">Permintaan Kode Pertemuan</h1>
            <p class="text-sm text-slate-500 mt-1 max-w-xl">
                Kode pertemuan (bukti checklist "sudah bertemu &amp; evaluasi" metode Offline) sekarang
                dibuat oleh HRD. Baris di bawah muncul begitu Penilai/Atasan menekan "Minta Kode"
                di dashboard-nya. Setelah kode dibuat, kedua pihak menekan "Sudah Bertemu" masing-masing.
            </p>
        </div>
        <x-tahun-selector :tahun="$tahun" :options="$availableTahun" />
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-xl border border-green-200 bg-green-50 text-green-700 text-sm px-4 py-3">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-4 rounded-xl border border-red-200 bg-red-50 text-red-700 text-sm px-4 py-3">
            {{ session('error') }}
        </div>
    @endif

    {{-- Siap di-generate --}}
    <div class="mb-3">
        <h2 class="text-sm font-bold text-slate-700">Menunggu Dibuatkan Kode ({{ $pendingRequests->count() }})</h2>
    </div>

    @if ($pendingRequests->isEmpty())
        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-8 text-center text-sm text-slate-400 mb-8">
            Tidak ada permintaan yang menunggu untuk tahun ini. 👍
        </div>
    @else
        <div class="rounded-xl border border-slate-200 divide-y divide-slate-100 overflow-hidden mb-8">
            @foreach ($pendingRequests as $row)
                <div class="flex flex-col sm:flex-row sm:items-center gap-3 px-4 py-3.5">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-slate-800 truncate">
                            {{ $row->subject->name ?? '—' }}
                            <span class="text-xs font-normal text-slate-400">
                                ({{ $row->context === \App\Models\MeetingCode::CONTEXT_PEJABAT ? 'Pejabat' : 'Pegawai' }})
                            </span>
                        </p>
                        <p class="text-xs text-slate-400 mt-0.5">
                            Dinilai oleh {{ $row->issuer->name ?? '—' }}
                        </p>
                        <p class="text-[11px] text-slate-400 mt-1">
                            Diminta {{ $row->issuer->name ?? 'Penilai/Atasan' }}
                            {{ $row->issuer_requested_at?->translatedFormat('d M Y H:i') }}
                        </p>
                    </div>

                    <form method="POST" action="{{ route('admin.meeting-codes.generate', $row->id) }}" class="shrink-0">
                        @csrf
                        <button type="submit"
                                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold px-4 py-2.5 transition">
                            Buat Kode
                        </button>
                    </form>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Riwayat --}}
    <div class="mb-3">
        <h2 class="text-sm font-bold text-slate-700">Kode Dibuat Terakhir</h2>
        <p class="text-xs text-slate-400 mt-0.5">Kode berlaku {{ \App\Models\MeetingCode::VALID_MINUTES }} menit sejak dibuat.</p>
    </div>

    @if ($recentlyGenerated->isEmpty())
        <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-8 text-center text-sm text-slate-400">
            Belum ada kode yang dibuat untuk tahun ini.
        </div>
    @else
        <div class="rounded-xl border border-slate-200 divide-y divide-slate-100 overflow-hidden">
            @foreach ($recentlyGenerated as $row)
                @php
                    $kedua = $row->sudahDipakai() && $row->issuerSudahKonfirmasi();
                    $salahSatu = $row->sudahDipakai() || $row->issuerSudahKonfirmasi();

                    if ($kedua) {
                        $status = ['label' => 'Kedua pihak selesai', 'class' => 'bg-emerald-50 text-emerald-600'];
                    } elseif (! $row->kodeHidup()) {
                        $status = ['label' => 'Kedaluwarsa', 'class' => 'bg-slate-100 text-slate-500'];
                    } elseif ($salahSatu) {
                        $status = ['label' => 'Menunggu satu pihak', 'class' => 'bg-blue-50 text-blue-600'];
                    } else {
                        $status = ['label' => 'Masih berlaku', 'class' => 'bg-amber-50 text-amber-600'];
                    }
                @endphp
                <div class="flex flex-col sm:flex-row sm:items-center gap-2 px-4 py-3 text-sm">
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-slate-800 truncate">
                            {{ $row->subject->name ?? '—' }}
                            <span class="text-xs font-normal text-slate-400">
                                ({{ $row->context === \App\Models\MeetingCode::CONTEXT_PEJABAT ? 'Pejabat' : 'Pegawai' }})
                                &middot; dinilai {{ $row->issuer->name ?? '—' }}
                            </span>
                        </p>
                        <p class="text-[11px] text-slate-400 mt-0.5">
                            Dibuat {{ $row->generated_at?->translatedFormat('d M Y H:i') }}
                            oleh {{ $row->generatedBy->name ?? '—' }}
                        </p>
                        <p class="text-[11px] text-slate-400 mt-0.5">
                            Dinilai: {{ $row->sudahDipakai() ? 'sudah bertemu' : 'belum' }}
                            &middot;
                            Penilai/Atasan: {{ $row->issuerSudahKonfirmasi() ? 'sudah bertemu' : 'belum' }}
                        </p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <span class="font-mono font-bold tracking-widest text-slate-600">{{ $row->code }}</span>
                        <span class="text-[11px] font-semibold px-2.5 py-1 rounded-full {{ $status['class'] }}">{{ $status['label'] }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

</div>

</x-dashboard-layout>

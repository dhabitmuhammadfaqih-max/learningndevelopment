<x-dashboard-layout title="Tanggapan Atasan Penilai">
    @php
        $initials = collect(explode(' ', trim($official->name)))
            ->map(fn ($part) => mb_substr($part, 0, 1))
            ->take(2)
            ->implode('');

        $selectedRecommendations = old('recommendation', $officialSupervisorFeedback?->recommendationList() ?? []);
        $kenaikanGajiValue = old('kenaikan_gaji_amount', $officialSupervisorFeedback?->kenaikan_gaji_amount ?? '');
        $promosiKeteranganValue = old('promosi_keterangan', $officialSupervisorFeedback?->promosi_keterangan ?? '');
        $demosiKeteranganValue = old('demosi_keterangan', $officialSupervisorFeedback?->demosi_keterangan ?? '');
        $mutasiKeteranganValue = old('mutasi_keterangan', $officialSupervisorFeedback?->mutasi_keterangan ?? '');
    @endphp

    <a href="{{ route('official.dashboard') }}"
       class="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-500 hover:text-slate-800 mb-5 transition">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="m15 18-6-6 6-6"/></svg>
        Kembali ke Dashboard
    </a>

    {{-- Flash messages --}}
    @if (session('success'))
        <div class="mb-6 rounded-2xl bg-emerald-50 text-emerald-700 px-5 py-4 text-sm font-medium">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-6 rounded-2xl bg-amber-50 text-amber-700 px-5 py-4 text-sm font-medium">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-6 rounded-2xl bg-red-50 text-red-700 px-5 py-4 text-sm">
            <ul class="list-disc pl-5 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Identity banner --}}
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-blue-600 to-indigo-500 px-6 sm:px-10 py-8 sm:py-10 mb-6">
        <div class="absolute -right-10 -top-10 w-48 h-48 rounded-full bg-white/10"></div>
        <div class="absolute right-24 bottom-[-3rem] w-32 h-32 rounded-full bg-white/10"></div>

        <div class="relative flex items-center gap-5">
            <div class="w-16 h-16 rounded-2xl shrink-0 grid place-items-center text-white font-bold text-lg bg-white/15 backdrop-blur">
                {{ $initials }}
            </div>
            <div class="min-w-0">
                <h2 class="text-2xl sm:text-3xl font-extrabold text-white truncate">{{ $official->name }}</h2>
                <p class="mt-1.5 text-blue-50/90 text-sm">
                    {{ $official->username }}
                    @if ($official->jabatan) &middot; {{ $official->jabatan }} @endif
                    @if ($official->unit_kerja) &middot; {{ $official->unit_kerja }} @endif
                </p>
                <div class="mt-3 flex flex-wrap gap-2">
                    @if ($official->vendor)
                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-400 text-amber-950 text-xs font-bold px-3 py-1.5 shadow-sm">
                            &#127970; {{ $official->vendor }}
                        </span>
                    @endif
                    @if ($official->status && auth()->user()->statusKontrakTerbuka())
                        <span class="inline-flex items-center gap-1 rounded-full bg-white text-blue-700 text-xs font-bold px-3 py-1.5 shadow-sm">
                            &#9989; {{ $official->status }}
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

        {{-- Main column --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Penilaian pejabat (read-only) --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 sm:p-7">
                <h3 class="text-lg font-bold text-slate-800 mb-5">Penilaian Pejabat</h3>

                @if ($officialEvaluation)
                    <p class="text-sm text-slate-500 mb-4">
                        <span class="font-semibold text-slate-700">Dinilai oleh:</span> {{ $officialEvaluation->supervisor->name ?? '-' }}
                    </p>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="text-left text-slate-400 text-xs">
                                    <th class="pb-2 font-medium">Komponen</th>
                                    <th class="pb-2 font-medium">Bobot</th>
                                    <th class="pb-2 font-medium">Nilai</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach (\App\Models\OfficialEvaluation::WEIGHTS as $key => $bobot)
                                    <tr>
                                        <td class="py-3 align-top">
                                            <p class="font-medium text-slate-700">{{ \App\Models\OfficialEvaluation::LABELS[$key] }}</p>
                                            <p class="text-xs text-slate-400 mt-0.5">{{ \App\Models\OfficialEvaluation::DESCRIPTIONS[$key] }}</p>
                                        </td>
                                        <td class="py-3 align-top text-slate-400 whitespace-nowrap">{{ rtrim(rtrim(number_format($bobot, 1), '0'), '.') }}%</td>
                                        <td class="py-3 align-top text-slate-700">{{ $officialEvaluation->$key }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 rounded-2xl bg-gradient-to-br from-slate-900 to-slate-700 text-white px-5 py-4 flex items-center justify-between">
                        <span class="text-sm font-medium">Nilai Akhir</span>
                        <span class="text-2xl font-extrabold">{{ $officialEvaluation->score }}/100</span>
                    </div>

                    <p class="mt-4 text-sm text-slate-600 leading-relaxed">{{ $officialEvaluation->feedback }}</p>

                    <span class="inline-block mt-3 rounded-full bg-slate-900 text-white text-xs font-semibold px-3 py-1.5">
                        {{ $officialEvaluation->recommendationLabel() }}
                    </span>

                    @if ($officialEvaluation->kenaikan_gaji_amount)
                        <p class="mt-3 text-sm text-slate-600">
                            <span class="font-semibold text-slate-700">Nominal Kenaikan Gaji:</span>
                            Rp {{ number_format($officialEvaluation->kenaikan_gaji_amount, 0, ',', '.') }}
                        </p>
                    @endif
                @else
                    <p class="text-sm text-slate-400">Belum ada penilaian pejabat.</p>
                @endif
            </div>

            {{-- Tanggapan Saya (Atasan Penilai) --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 sm:p-7">
                <h3 class="text-lg font-bold text-slate-800 mb-5">Tanggapan Saya (Atasan Penilai)</h3>

                @if ($tanggapanLocked)
                    <div class="rounded-2xl bg-blue-50 text-blue-700 px-5 py-4 text-sm font-medium">
                        Tanggapan ini sudah dikunci karena pejabat yang dinilai sudah menandatangani penilaiannya. Tidak bisa diisi/diubah lagi.
                    </div>

                    @if ($officialSupervisorFeedback)
                        <div class="mt-4 space-y-3">
                            <p class="text-sm text-slate-600 bg-slate-50 rounded-xl px-4 py-3">{{ $officialSupervisorFeedback->feedback }}</p>
                            <p class="text-sm text-slate-600">
                                <span class="font-semibold text-slate-700">Rekomendasi:</span> {{ $officialSupervisorFeedback->recommendationLabel() }}
                            </p>
                            @if ($officialSupervisorFeedback->kenaikan_gaji_amount)
                                <p class="text-sm text-slate-600">
                                    <span class="font-semibold text-slate-700">Nominal Kenaikan Gaji:</span>
                                    Rp {{ number_format($officialSupervisorFeedback->kenaikan_gaji_amount, 0, ',', '.') }}
                                </p>
                            @endif
                            @if ($officialSupervisorFeedback->signature)
                                <img src="{{ Storage::disk('public')->url($officialSupervisorFeedback->signature) }}"
                                     class="w-full max-w-[220px] h-24 object-contain border border-slate-200 rounded-xl bg-slate-50">
                            @endif
                        </div>
                    @endif
                @elseif (! $readyForTanggapan)
                    <div class="rounded-2xl bg-amber-50 text-amber-700 px-5 py-4 text-sm font-medium">
                        Tanggapan belum bisa diberikan karena penilai belum memberikan penilaian
                        untuk pejabat ini.
                    </div>
                @else
                    {{-- Pengingat keputusan akhir --}}
                    <div class="mb-5 rounded-2xl border border-indigo-200 bg-indigo-50 px-5 py-4 text-sm text-indigo-900">
                        <div class="flex items-start gap-3">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5 mt-0.5 shrink-0 text-indigo-600"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.8l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.7-3.2l-8-14a2 2 0 0 0-3.4 0Z"/></svg>
                            <div>
                                <p class="font-bold">Perhatian: Rekomendasi Anda adalah keputusan akhir</p>
                                <p class="mt-1 leading-relaxed text-indigo-700">Rekomendasi yang Anda pilih sebagai Atasan Penilai akan menjadi keputusan akhir yang diambil. Pastikan pilihan rekomendasi sudah dipertimbangkan dengan matang sebelum disimpan.</p>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('official.pejabat.tanggapan.store', $official->id) }}" id="form-tanggapan-atasan-pejabat" data-autosave>
                        @csrf

                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1.5">Tanggapan</label>
                            <textarea name="feedback" required minlength="10"
                                      class="w-full max-w-xl min-h-[120px] rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none resize-y">{{ old('feedback', $officialSupervisorFeedback?->feedback ?? '') }}</textarea>
                            @error('feedback')
                                <p class="text-xs text-red-600 mt-1.5">{{ $message }}</p>
                            @enderror
                        </div>

                        @include('partials.recommendation-fields', [
                            'selectedRecommendations' => $selectedRecommendations,
                            'kenaikanGajiValue' => $kenaikanGajiValue,
                            'promosiKeteranganValue' => $promosiKeteranganValue,
                            'demosiKeteranganValue' => $demosiKeteranganValue,
                            'mutasiKeteranganValue' => $mutasiKeteranganValue,
                            'recommendations' => \App\Models\OfficialEvaluation::RECOMMENDATIONS,
                            'recommendationDescriptions' => \App\Models\OfficialEvaluation::RECOMMENDATION_DESCRIPTIONS,
                            'subjectLabel' => 'pejabat',
                            'subjectStatus' => $official->status,
                            'subjectVendor' => $official->vendor,
                        ])

                        <div class="mt-5 max-w-md">
                            <label class="block text-sm font-bold text-slate-700 mb-1.5">Tanda Tangan</label>
                            @if ($officialSupervisorFeedback?->signature)
                                <img src="{{ Storage::disk('public')->url($officialSupervisorFeedback->signature) }}" alt="Tanda tangan Anda"
                                     class="rounded-xl border border-slate-200 bg-white block w-full max-w-[400px] h-[150px] object-contain">
                            @else
                                <img src="{{ auth()->user()->signature_url }}" alt="Tanda tangan Anda"
                                     class="rounded-xl border border-slate-200 bg-white block w-full max-w-[400px] h-[150px] object-contain">
                            @endif
                            <p class="text-xs text-slate-400 mt-2">
                                Tanda tangan akun Anda akan otomatis dipakai untuk tanggapan ini.
                            </p>
                        </div>

                        <button type="submit"
                                class="mt-6 inline-flex items-center gap-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-6 py-3 transition">
                            Simpan Tanggapan
                        </button>
                    </form>
                @endif
            </div>

        </div>
        {{-- /Main column --}}

        {{-- Sidebar --}}
        <div class="space-y-6 lg:sticky lg:top-6">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 sm:p-7">
                <h3 class="text-sm font-bold text-slate-500 uppercase tracking-wide mb-4">Sebelum Mengirim</h3>
                <ul class="space-y-2.5 text-xs text-slate-500 leading-relaxed">
                    <li class="flex items-start gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-3.5 h-3.5 mt-0.5 text-blue-500 shrink-0"><path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/></svg>
                        Tanggapan hanya bisa diberikan setelah penilai memberikan penilaian.
                    </li>
                    <li class="flex items-start gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-3.5 h-3.5 mt-0.5 text-blue-500 shrink-0"><path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/></svg>
                        Tanggapan minimal 10 karakter.
                    </li>
                    <li class="flex items-start gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-3.5 h-3.5 mt-0.5 text-blue-500 shrink-0"><path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/></svg>
                        Tanda tangan wajib digambar sebelum menyimpan tanggapan.
                    </li>
                </ul>
            </div>
        </div>
        {{-- /Sidebar --}}
    </div>

    <script>
        // AJAX polling: cek berkala apakah Penilai baru saja memberi nilai
        // ke pejabat ini (form ini ke-unlock), atau pejabat yang dinilai
        // baru saja tanda tangan (form ini ke-lock), lalu reload otomatis.
        // Lihat OfficialController::tanggapanPejabatStatusVersion().
        (function () {
            const POLL_INTERVAL_MS = 5000;
            const STATUS_URL = '{{ route('official.pejabat.tanggapan.status-version', $official->id) }}';
            let currentVersion = null;

            function isUserTyping() {
                const active = document.activeElement;
                if (!active) return false;
                const tag = active.tagName;
                return tag === 'TEXTAREA' || tag === 'INPUT' || tag === 'CANVAS';
            }

            function poll() {
                if (document.hidden) return;

                fetch(STATUS_URL + '?t=' + Date.now(), {
                    headers: {
                        'Accept': 'application/json',
                        'ngrok-skip-browser-warning': 'true',
                    },
                    cache: 'no-store',
                })
                    .then(res => res.ok ? res.json() : null)
                    .then(data => {
                        if (!data) return;
                        if (currentVersion === null) {
                            currentVersion = data.version;
                            return;
                        }
                        if (data.version !== currentVersion) {
                            if (isUserTyping()) return;
                            window.showReloadOverlay();
                        }
                    })
                    .catch((err) => console.error('status-version polling error:', err));
            }

            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'visible') poll();
            });

            setInterval(poll, POLL_INTERVAL_MS);
        })();
    </script>
</x-dashboard-layout>

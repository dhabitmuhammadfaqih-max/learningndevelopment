<x-dashboard-layout title="Tanggapan Atasan - {{ $employee->name }}">

    @php
        $initials = collect(explode(' ', trim($employee->name)))
            ->map(fn ($part) => mb_substr($part, 0, 1))
            ->take(2)
            ->implode('');

        $selectedRecommendations = old('recommendation', $supervisorFeedback?->recommendationList() ?? []);
        $kenaikanGajiValue = old('kenaikan_gaji_amount', $supervisorFeedback->kenaikan_gaji_amount ?? '');
        $promosiKeteranganValue = old('promosi_keterangan', $supervisorFeedback->promosi_keterangan ?? '');
        $demosiKeteranganValue = old('demosi_keterangan', $supervisorFeedback->demosi_keterangan ?? '');
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
                <h2 class="text-2xl sm:text-3xl font-extrabold text-white truncate">{{ $employee->name }}</h2>
                <p class="mt-1.5 text-blue-50/90 text-sm">
                    {{ $employee->username }}
                    @if ($employee->jabatan) &middot; {{ $employee->jabatan }} @endif
                    @if ($employee->unit_kerja) &middot; {{ $employee->unit_kerja }} @endif
                </p>
                <div class="mt-3 flex flex-wrap gap-2">
                    @if ($employee->vendor)
                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-400 text-amber-950 text-xs font-bold px-3 py-1.5 shadow-sm">
                            &#127970; {{ $employee->vendor }}
                        </span>
                    @endif
                    @if ($employee->status && auth()->user()->statusKontrakTerbuka())
                        <span class="inline-flex items-center gap-1 rounded-full bg-white text-blue-700 text-xs font-bold px-3 py-1.5 shadow-sm">
                            &#9989; {{ $employee->status }}
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

        {{-- Main column --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Penilaian Pejabat (read-only) --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 sm:p-7">
                <h3 class="text-lg font-bold text-slate-800 mb-5">Penilaian Pejabat</h3>

                @if ($evaluation)
                    <p class="text-sm text-slate-500 mb-4">
                        <span class="font-semibold text-slate-700">Pejabat:</span> {{ $evaluation->official->name }}
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
                                @foreach (\App\Models\Evaluation::WEIGHTS as $key => $bobot)
                                    <tr>
                                        <td class="py-3 align-top font-medium text-slate-700">{{ \App\Models\Evaluation::LABELS[$key] }}</td>
                                        <td class="py-3 align-top text-slate-400 whitespace-nowrap">{{ rtrim(rtrim(number_format($bobot, 1), '0'), '.') }}%</td>
                                        <td class="py-3 align-top text-slate-700">{{ $evaluation->$key }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 rounded-2xl bg-gradient-to-br from-slate-900 to-slate-700 text-white px-5 py-4 flex items-center justify-between">
                        <span class="text-sm font-medium">Nilai Akhir</span>
                        <span class="text-2xl font-extrabold">{{ $evaluation->score }}/100</span>
                    </div>

                    <p class="mt-4 text-sm text-slate-600 leading-relaxed">{{ $evaluation->feedback }}</p>

                    <span class="inline-block mt-3 rounded-full bg-slate-900 text-white text-xs font-semibold px-3 py-1.5">
                        {{ $evaluation->recommendationLabel() }}
                    </span>

                    @if ($evaluation->kenaikan_gaji_amount)
                        <p class="mt-3 text-sm text-slate-600">
                            <span class="font-semibold text-slate-700">Nominal Kenaikan Gaji:</span>
                            Rp {{ number_format($evaluation->kenaikan_gaji_amount, 0, ',', '.') }}
                        </p>
                    @endif
                @else
                    <p class="text-sm text-slate-400">Belum ada penilaian pejabat.</p>
                @endif
            </div>

            {{-- Tanggapan Korelasi --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 sm:p-7">
                <h3 class="text-lg font-bold text-slate-800 mb-5">Tanggapan Korelasi</h3>

                @forelse ($feedbacks as $feedback)
                    <div class="border-b border-slate-100 last:border-b-0 py-3 first:pt-0">
                        <p class="font-semibold text-slate-700 text-sm">{{ $feedback->reviewer->name }}</p>
                        <p class="text-sm text-slate-500 mt-0.5">{{ $feedback->feedback }}</p>
                    </div>
                @empty
                    <p class="text-sm text-slate-400">Belum ada tanggapan.</p>
                @endforelse
            </div>

            {{-- Tanggapan Saya (Atasan Penilai) --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 sm:p-7">
                <h3 class="text-lg font-bold text-slate-800 mb-5">Tanggapan Saya (Atasan Penilai)</h3>

                @if ($tanggapanLocked)
                    <div class="rounded-2xl bg-blue-50 text-blue-700 px-5 py-4 text-sm font-medium">
                        Tanggapan ini sudah dikunci karena pegawai yang dinilai sudah menandatangani penilaiannya. Tidak bisa diisi/diubah lagi.
                    </div>

                    @if ($supervisorFeedback)
                        <div class="mt-4 space-y-3">
                            <p class="text-sm text-slate-600 bg-slate-50 rounded-xl px-4 py-3">{{ $supervisorFeedback->feedback }}</p>
                            <p class="text-sm text-slate-600">
                                <span class="font-semibold text-slate-700">Rekomendasi:</span> {{ $supervisorFeedback->recommendationLabel() }}
                            </p>
                            @if ($supervisorFeedback->kenaikan_gaji_amount)
                                <p class="text-sm text-slate-600">
                                    <span class="font-semibold text-slate-700">Nominal Kenaikan Gaji:</span>
                                    Rp {{ number_format($supervisorFeedback->kenaikan_gaji_amount, 0, ',', '.') }}
                                </p>
                            @endif
                            @if ($supervisorFeedback->signature)
                                <img src="{{ Storage::disk('public')->url($supervisorFeedback->signature) }}"
                                     class="w-full max-w-[220px] h-24 object-contain border border-slate-200 rounded-xl bg-slate-50">
                            @endif
                        </div>
                    @endif
                @elseif (! $readyForTanggapan)
                    <div class="rounded-2xl bg-amber-50 text-amber-700 px-5 py-4 text-sm font-medium">
                        Tanggapan belum bisa diberikan karena penilai belum memberikan penilaian
                        untuk pegawai ini.
                    </div>
                @else
                    {{-- Pengingat keputusan akhir rekomendasi Atasan Penilai --}}
                    <div class="mb-5 rounded-2xl border-2 border-amber-300 bg-amber-50 px-5 py-4 text-sm text-amber-900 shadow-sm">
                        <div class="flex items-start gap-3">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="w-6 h-6 mt-0.5 shrink-0 text-amber-600"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.8l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.7-3.2l-8-14a2 2 0 0 0-3.4 0Z"/></svg>
                            <div>
                                <p class="font-extrabold text-base">⚠️ PERHATIAN: Rekomendasi Anda adalah keputusan akhir</p>
                                <p class="mt-1 leading-relaxed font-medium text-amber-800">Sebagai Atasan Penilai, rekomendasi yang Anda pilih akan menjadi keputusan akhir yang diambil. Pastikan pilihan rekomendasi sudah dipertimbangkan dengan matang sebelum menyimpan.</p>
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('official.employee.tanggapan.store', $employee->id) }}" id="form-tanggapan-atasan" data-autosave>

                        @csrf

                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1.5">Tanggapan</label>
                            <textarea name="feedback" required minlength="10"
                                      class="w-full max-w-xl min-h-[120px] rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none resize-y">{{ old('feedback', $supervisorFeedback->feedback ?? '') }}</textarea>
                            @error('feedback')
                                <p class="text-xs text-red-600 mt-1.5">{{ $message }}</p>
                            @enderror
                        </div>

                        @include('partials.recommendation-fields', [
                            'selectedRecommendations' => $selectedRecommendations,
                            'kenaikanGajiValue' => $kenaikanGajiValue,
                            'promosiKeteranganValue' => $promosiKeteranganValue,
                            'demosiKeteranganValue' => $demosiKeteranganValue,
                            'recommendations' => \App\Models\Evaluation::RECOMMENDATIONS,
                            'recommendationDescriptions' => \App\Models\Evaluation::RECOMMENDATION_DESCRIPTIONS,
                            'subjectLabel' => 'pegawai',
                            'subjectStatus' => $employee->status,
                            'subjectVendor' => $employee->vendor,
                        ])

                        <div class="mt-5 max-w-md">
                            <label class="block text-sm font-bold text-slate-700 mb-1.5">Tanda Tangan</label>
                            <canvas id="signature-pad" class="rounded-xl border border-slate-200 bg-white cursor-crosshair block w-full max-w-[400px] sm:w-[400px] sm:h-[150px]" style="touch-action:none; aspect-ratio: 400 / 150;"></canvas>
                            <div class="flex items-center justify-between mt-2">
                                <span class="text-xs text-slate-400">Gambar tanda tangan di kotak di atas</span>
                                <button type="button" id="btn-clear-signature" class="text-xs text-slate-500 hover:text-slate-800 underline">Hapus &amp; ulangi</button>
                            </div>
                            {{-- Diisi otomatis oleh JS sebelum form dikirim --}}
                            <input type="hidden" name="signature" id="signature-input">
                            @error('signature')
                                <p class="text-xs text-red-600 mt-1.5">{{ $message }}</p>
                            @enderror

                            @if ($supervisorFeedback && $supervisorFeedback->signature)
                                <img src="{{ Storage::disk('public')->url($supervisorFeedback->signature) }}"
                                     class="mt-3 w-full max-w-[220px] h-24 object-contain border border-slate-200 rounded-xl bg-slate-50">
                            @endif
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
        // ---- Signature pad (pointer events, matches app-wide pattern) ----
        function initSignaturePad(canvasId, clearBtnId) {
            const canvas = document.getElementById(canvasId);
            if (!canvas) return null;

            const ctx = canvas.getContext('2d');
            let drawing = false;
            let last = null;
            let hasStroke = false;
            let cssWidth = 0;
            let cssHeight = 0;
            let ratio = window.devicePixelRatio || 1;

            function setupCanvas(preserve) {
                const oldImage = preserve && hasStroke ? canvas.toDataURL() : null;

                cssWidth = canvas.clientWidth;
                cssHeight = canvas.clientHeight;
                ratio = window.devicePixelRatio || 1;

                canvas.width = cssWidth * ratio;
                canvas.height = cssHeight * ratio;

                ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
                ctx.lineCap = 'round';
                ctx.lineJoin = 'round';
                ctx.lineWidth = 2.2;
                ctx.strokeStyle = '#111';

                if (oldImage) {
                    const image = new Image();
                    image.onload = function () {
                        ctx.drawImage(image, 0, 0, cssWidth, cssHeight);
                    };
                    image.src = oldImage;
                }
            }

            requestAnimationFrame(() => setupCanvas(false));

            if ('ResizeObserver' in window) {
                const observer = new ResizeObserver(() => {
                    if (!drawing) setupCanvas(true);
                });
                observer.observe(canvas);
            }

            window.addEventListener('resize', () => {
                if (!drawing) setupCanvas(true);
            });

            function getPos(e) {
                const rect = canvas.getBoundingClientRect();
                return {
                    x: e.clientX - rect.left,
                    y: e.clientY - rect.top
                };
            }

            function start(e) {
                if (e.pointerType === 'mouse' && e.button !== 0) return;
                e.preventDefault();
                drawing = true;
                hasStroke = true;
                last = getPos(e);
                if (canvas.setPointerCapture) {
                    try { canvas.setPointerCapture(e.pointerId); } catch (_) {}
                }
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

            function end(e) {
                if (e) e.preventDefault();
                drawing = false;
                last = null;
            }

            canvas.style.touchAction = 'none';
            canvas.addEventListener('pointerdown', start);
            canvas.addEventListener('pointermove', move);
            canvas.addEventListener('pointerup', end);
            canvas.addEventListener('pointercancel', end);
            canvas.addEventListener('pointerleave', function (e) {
                if (drawing && e.pointerType === 'mouse') end(e);
            });

            const clearBtn = document.getElementById(clearBtnId);
            if (clearBtn) {
                clearBtn.addEventListener('click', function () {
                    ctx.save();
                    ctx.setTransform(1, 0, 0, 1, 0, 0);
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                    ctx.restore();

                    ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
                    ctx.lineCap = 'round';
                    ctx.lineJoin = 'round';
                    ctx.lineWidth = 2.2;
                    ctx.strokeStyle = '#111';

                    drawing = false;
                    last = null;
                    hasStroke = false;
                });
            }

            return {
                hasStroke: () => hasStroke,
                toDataURL: () => canvas.toDataURL('image/png'),
            };
        }

        const tanggapanPad = initSignaturePad('signature-pad', 'btn-clear-signature');
        const tanggapanForm = document.getElementById('form-tanggapan-atasan');
        const tanggapanSignatureInput = document.getElementById('signature-input');

        if (tanggapanForm && tanggapanPad) {
            tanggapanForm.addEventListener('submit', function (e) {
                if (!tanggapanPad.hasStroke()) {
                    e.preventDefault();
                    alert('Tanda tangan wajib diisi sebelum menyimpan tanggapan.');
                    return;
                }
                tanggapanSignatureInput.value = tanggapanPad.toDataURL();
            });
        }
    </script>

    <script>
        // AJAX polling: cek berkala apakah Penilai baru saja memberi nilai
        // (form ini ke-unlock), atau pegawai baru saja tanda tangan (form
        // ini ke-lock), lalu reload otomatis. Lihat
        // OfficialController::tanggapanPegawaiStatusVersion() untuk
        // pola/alasannya.
        (function () {
            const POLL_INTERVAL_MS = 5000;
            const STATUS_URL = '{{ route('official.employee.tanggapan.status-version', $employee->id) }}';
            let currentVersion = null;

            function isUserTyping() {
                const active = document.activeElement;
                if (!active) return false;
                const tag = active.tagName;
                return tag === 'TEXTAREA' || tag === 'INPUT' || tag === 'CANVAS';
            }

            function poll() {
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
                            window.location.reload();
                        }
                    })
                    .catch((err) => console.error('status-version polling error:', err));
            }

            setInterval(poll, POLL_INTERVAL_MS);
        })();
    </script>

</x-dashboard-layout>
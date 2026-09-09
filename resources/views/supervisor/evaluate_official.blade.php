<x-dashboard-layout title="Penilaian {{ $pejabat->name }}">

    @php
        // "Kembali" harus mengarah ke dashboard sesuai role akun yang login.
        // Role "atasan_pejabat" sudah digabung ke "pejabat", jadi halaman ini
        // sekarang diakses oleh pejabat (baik yang menilai pejabat lain
        // maupun HRD).
        $backRoute = match (auth()->user()->role) {
            'hrd'   => route('admin.dashboard'),
            default => route('official.dashboard'),
        };

        $isEdit = (bool) $myEvaluation;

        // Begitu pejabat yang dinilai sudah tanda tangan (menanggapi &
        // menandatangani penilaiannya sendiri), penilaian ini tidak boleh
        // diubah lagi - lihat SupervisorController::updateOfficialEvaluation().
        $isLocked = $isEdit && (bool) $myEvaluation->employee_signature;

        $initials = collect(explode(' ', trim($pejabat->name)))
            ->map(fn ($part) => mb_substr($part, 0, 1))
            ->take(2)
            ->implode('');
    @endphp

    <a href="{{ $backRoute }}"
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
                <h2 class="text-2xl sm:text-3xl font-extrabold text-white truncate">{{ $pejabat->name }}</h2>
                <p class="mt-1.5 text-blue-50/90 text-sm">
                    {{ $pejabat->username }}
                    @if ($pejabat->jabatan) &middot; {{ $pejabat->jabatan }} @endif
                    @if ($pejabat->unit_kerja) &middot; {{ $pejabat->unit_kerja }} @endif
                </p>
                <div class="mt-3 flex flex-wrap gap-2">
                    @if ($pejabat->vendor)
                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-400 text-amber-950 text-xs font-bold px-3 py-1.5 shadow-sm">
                            &#127970; {{ $pejabat->vendor }}
                        </span>
                    @endif
                    @if ($pejabat->status && auth()->user()->statusKontrakTerbuka())
                        <span class="inline-flex items-center gap-1 rounded-full bg-white text-blue-700 text-xs font-bold px-3 py-1.5 shadow-sm">
                            &#9989; {{ $pejabat->status }}
                        </span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

        {{-- Main column --}}
        <div class="lg:col-span-2 space-y-6">

        {{-- Data Kehadiran & Kontrak --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 sm:p-7">
            <h3 class="text-lg font-bold text-slate-800 mb-5">Data Kehadiran &amp; Kontrak</h3>

            @include('partials.kehadiran-fields', ['kehadiranUser' => $pejabat])

            <div class="flex flex-wrap gap-2 mt-4">
                @if ($pejabat->vendor)
                    <span class="inline-flex items-center text-[11px] font-semibold px-2.5 py-1 rounded-full bg-amber-50 text-amber-700">Vendor: {{ $pejabat->vendor }}</span>
                @endif
                @if ($pejabat->status && auth()->user()->statusKontrakTerbuka())
                    <span class="inline-flex items-center text-[11px] font-semibold px-2.5 py-1 rounded-full bg-sky-50 text-sky-700">Status: {{ $pejabat->status }}</span>
                @endif
            </div>
        </div>

        {{-- Tanggapan Korelasi --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 sm:p-7">
            <div class="flex items-center justify-between mb-5">
                <h3 class="text-lg font-bold text-slate-800">Tanggapan Korelasi</h3>
                <span @class([
                    'text-xs font-bold px-2.5 py-1 rounded-full',
                    'bg-emerald-50 text-emerald-700' => $readyToEvaluate,
                    'bg-red-50 text-red-700' => ! $readyToEvaluate,
                ])>
                    {{ $peerFeedbacks->count() }} / {{ \App\Models\User::MIN_TANGGAPAN_KORELASI_PEJABAT }}
                </span>
            </div>

            @forelse ($peerFeedbacks as $feedback)
                <div class="rounded-2xl border border-slate-100 bg-slate-50/60 p-4 mb-3 last:mb-0">
                    <p class="font-semibold text-slate-800 text-sm">{{ $feedback->reviewer->name }}</p>
                    <p class="text-sm text-slate-600 mt-1.5 leading-relaxed">{{ $feedback->feedback }}</p>

                    @if ($feedback->signature)
                        <img src="{{ Storage::disk('public')->url($feedback->signature) }}"
                             class="mt-3 w-full max-w-[220px] h-24 object-contain border border-slate-200 rounded-xl bg-white">
                    @endif
                </div>
            @empty
                <p class="text-sm text-slate-400">Belum ada tanggapan.</p>
            @endforelse
        </div>

        {{-- Berikan Penilaian --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 sm:p-7">
            <h3 class="text-lg font-bold text-slate-800 mb-5">Berikan Penilaian</h3>

            @if ($isLocked)

                {{-- Pejabat yang dinilai sudah tanda tangan: terkunci --}}
                <div class="mb-5 rounded-2xl bg-blue-50 text-blue-700 px-5 py-4 text-sm">
                    Nilai ini sudah dikunci karena pejabat yang dinilai sudah menandatangani penilaian ini.
                </div>

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
                                    <td class="py-3 align-top text-slate-700">{{ $myEvaluation->$key }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-5 rounded-2xl bg-slate-900 text-white px-5 py-4 flex items-center justify-between">
                    <span class="text-sm text-slate-300">Nilai Akhir</span>
                    <span class="text-2xl font-extrabold">{{ $myEvaluation->score }}<span class="text-sm font-medium text-slate-400">/100</span></span>
                </div>

                <div class="mt-5">
                    <p class="text-sm font-bold text-slate-700 mb-1.5">Tanggapan</p>
                    <p class="text-sm text-slate-600 leading-relaxed">{{ $myEvaluation->feedback }}</p>
                </div>

                <div class="mt-5">
                    <p class="text-sm font-bold text-slate-700 mb-1.5">Rekomendasi</p>
                    <p class="text-sm text-slate-600">{{ $myEvaluation->recommendationLabel() }}</p>
                    @if ($myEvaluation->kenaikan_gaji_amount)
                        <p class="text-sm text-slate-600 mt-1">Nominal Kenaikan Gaji: Rp {{ number_format($myEvaluation->kenaikan_gaji_amount, 0, ',', '.') }}</p>
                    @endif
                </div>

                @if ($myEvaluation->signature)
                    <div class="mt-5">
                        <p class="text-sm font-bold text-slate-700 mb-1.5">Tanda Tangan Penilai</p>
                        <img src="{{ Storage::disk('public')->url($myEvaluation->signature) }}"
                             class="w-full max-w-[260px] h-28 object-contain border border-slate-200 rounded-xl bg-slate-50">
                    </div>
                @endif

                @if ($myEvaluation->employee_response)
                    <div class="mt-5">
                        <p class="text-sm font-bold text-slate-700 mb-1.5">Tanggapan Pejabat</p>
                        <p class="text-sm text-slate-600 leading-relaxed">{{ $myEvaluation->employee_response }}</p>
                    </div>
                @endif

                @if ($myEvaluation->employee_signature)
                    <div class="mt-5">
                        <p class="text-sm font-bold text-slate-700 mb-1.5">Tanda Tangan Pejabat</p>
                        <img src="{{ Storage::disk('public')->url($myEvaluation->employee_signature) }}"
                             class="w-full max-w-[260px] h-28 object-contain border border-slate-200 rounded-xl bg-slate-50">
                    </div>
                @endif

            @elseif (! $isEdit && ! $readyToEvaluate)
                <div class="rounded-2xl bg-red-50 text-red-700 px-5 py-4 text-sm">
                    Pejabat ini belum bisa dinilai. Pastikan sudah menerima minimal {{ \App\Models\User::MIN_TANGGAPAN_KORELASI_PEJABAT }} tanggapan korelasi dari pejabat lain dan HRD sudah mengisi data kehadiran terlebih dahulu.
                </div>
            @else

            @php
                $selectedRecommendations = old('recommendation', $isEdit ? $myEvaluation->recommendationList() : []);
                $kenaikanGajiValue = old('kenaikan_gaji_amount', $isEdit ? $myEvaluation->kenaikan_gaji_amount : '');
                $promosiKeteranganValue = old('promosi_keterangan', $isEdit ? $myEvaluation->promosi_keterangan : '');
                $demosiKeteranganValue = old('demosi_keterangan', $isEdit ? $myEvaluation->demosi_keterangan : '');
        $mutasiKeteranganValue = old('mutasi_keterangan', $isEdit ? $myEvaluation->mutasi_keterangan : '');
                $teguranPernahValue = old('teguran_pernah', $isEdit && $myEvaluation->teguran ? 'ya' : 'tidak');
                $teguranKeteranganValue = old('teguran', $isEdit ? $myEvaluation->teguran : '');
            @endphp

            <form method="POST"
                  action="{{ $isEdit ? route('supervisor.official.evaluate.update', $pejabat->id) : route('supervisor.official.evaluate', $pejabat->id) }}"
                  id="form-penilaian"
                  data-autosave>

                @csrf
                @if ($isEdit)
                    @method('PUT')
                @endif

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-slate-400 text-xs">
                                <th class="pb-2 font-medium">Komponen</th>
                                <th class="pb-2 font-medium">Bobot</th>
                                <th class="pb-2 font-medium">Nilai (0-100)</th>
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
                                    <td class="py-3 align-top">
                                        <input
                                            type="number"
                                            name="{{ $key }}"
                                            min="0"
                                            max="100"
                                            step="1"
                                            class="komponen-input w-20 rounded-xl border border-slate-200 px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none"
                                            data-bobot="{{ $bobot }}"
                                            value="{{ old($key, $isEdit ? $myEvaluation->$key : 0) }}"
                                            required
                                        >
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <p class="mt-3 text-xs text-slate-400 lg:hidden">
                    Nilai akhir otomatis: <span class="font-bold text-slate-600" id="nilai-akhir-mobile">0</span>/100
                </p>

                <div class="mt-5">
                    <label class="block text-sm font-bold text-slate-700 mb-1.5">Tanggapan</label>
                    <textarea name="feedback" required minlength="10"
                              class="w-full max-w-xl min-h-[120px] rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none resize-y">{{ old('feedback', $isEdit ? $myEvaluation->feedback : '') }}</textarea>
                    @error('feedback')
                        <p class="text-xs text-red-600 mt-1.5">{{ $message }}</p>
                    @enderror
                </div>

                @include('partials.teguran-fields', [
                    'teguranPernahValue' => $teguranPernahValue,
                    'teguranKeteranganValue' => $teguranKeteranganValue,
                    'subjectLabel' => 'pejabat',
                ])

                @include('partials.recommendation-fields', [
                    'selectedRecommendations' => $selectedRecommendations,
                    'kenaikanGajiValue' => $kenaikanGajiValue,
                    'promosiKeteranganValue' => $promosiKeteranganValue,
                    'demosiKeteranganValue' => $demosiKeteranganValue,
                    'mutasiKeteranganValue' => $mutasiKeteranganValue,
                    'recommendations' => \App\Models\OfficialEvaluation::RECOMMENDATIONS,
                    'recommendationDescriptions' => \App\Models\OfficialEvaluation::RECOMMENDATION_DESCRIPTIONS,
                    'subjectLabel' => 'pejabat',
                    'subjectStatus' => $pejabat->status,
                    'subjectVendor' => $pejabat->vendor,
                ])

                <div class="mt-5 max-w-md">
                    <label class="block text-sm font-bold text-slate-700 mb-1.5">Tanda Tangan Penilai</label>
                    <canvas id="signature-pad" class="rounded-xl border border-slate-200 bg-white cursor-crosshair block w-full max-w-[400px] sm:w-[400px] sm:h-[150px]" style="touch-action:none; aspect-ratio: 400 / 150;"></canvas>
                    <div class="flex items-center justify-between mt-2">
                        <span class="text-xs text-slate-400">
                            @if ($isEdit)
                                Kosongkan jika tidak ingin mengganti tanda tangan sebelumnya
                            @else
                                Gambar tanda tangan di kotak di atas
                            @endif
                        </span>
                        <button type="button" id="btn-clear-official-signature" class="text-xs text-slate-500 hover:text-slate-800 underline">Hapus &amp; ulangi</button>
                    </div>
                    <input type="hidden" name="signature" id="signature-input">
                    @error('signature')
                        <p class="text-xs text-red-600 mt-1.5">{{ $message }}</p>
                    @enderror

                    @if ($isEdit && $myEvaluation->signature)
                        <img src="{{ Storage::disk('public')->url($myEvaluation->signature) }}"
                             class="mt-3 w-full max-w-[220px] h-24 object-contain border border-slate-200 rounded-xl bg-slate-50">
                    @endif
                </div>

                <button type="submit" id="btn-submit"
                        class="mt-6 inline-flex items-center gap-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-6 py-3 transition">
                    {{ $isEdit ? 'Perbarui Penilaian' : 'Simpan Penilaian' }}
                </button>

            </form>
            @endif
        </div>

        </div>
        {{-- /Main column --}}

        {{-- Sidebar --}}
        <div class="space-y-6 lg:sticky lg:top-6">

            {{-- Nilai akhir live --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 sm:p-7">
                <h3 class="text-sm font-bold text-slate-500 uppercase tracking-wide mb-4">Nilai Akhir</h3>
                <div class="rounded-2xl bg-gradient-to-br from-slate-900 to-slate-700 text-white px-5 py-6 text-center">
                    <p class="text-4xl font-extrabold" id="nilai-akhir">0</p>
                    <p class="text-xs text-slate-400 mt-1">dari 100 (otomatis)</p>
                </div>
                <p class="text-xs text-slate-400 mt-4 leading-relaxed">
                    Dihitung otomatis dari nilai &times; bobot tiap komponen di sebelah kiri. Isi semua kolom untuk hasil akhir yang akurat.
                </p>
            </div>

            {{-- Checklist Pertemuan & Evaluasi dipindahkan ke Dashboard (lihat
                 resources/views/official/dashboard.blade.php, bagian
                 "Pejabat yang Harus Anda Nilai") supaya semua checklist
                 pejabat ada di luar/Dashboard, bukan di halaman Evaluate. --}}

            {{-- Skala penilaian --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 sm:p-7">
                <h3 class="text-sm font-bold text-slate-500 uppercase tracking-wide mb-4">Skala Penilaian</h3>
                <div class="space-y-2">
                    @foreach (\App\Models\OfficialEvaluation::SCALE as $huruf => $range)
                        <div class="flex items-center gap-3 rounded-xl bg-slate-50 px-3 py-2.5">
                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-slate-800 text-white font-bold text-[11px] shrink-0">{{ $huruf }}</span>
                            <span class="text-xs text-slate-600">{{ $range['min'] }}-{{ $range['max'] }} &middot; {{ $range['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Tips pengisian --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 sm:p-7">
                <h3 class="text-sm font-bold text-slate-500 uppercase tracking-wide mb-4">Sebelum Mengirim</h3>
                <ul class="space-y-2.5 text-xs text-slate-500 leading-relaxed">
                    <li class="flex items-start gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-3.5 h-3.5 mt-0.5 text-blue-500 shrink-0"><path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/></svg>
                        Pastikan semua komponen sudah diisi nilainya (0-100).
                    </li>
                    <li class="flex items-start gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-3.5 h-3.5 mt-0.5 text-blue-500 shrink-0"><path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/></svg>
                        Tanggapan minimal 10 karakter.
                    </li>
                    <li class="flex items-start gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-3.5 h-3.5 mt-0.5 text-blue-500 shrink-0"><path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/></svg>
                        Tanda tangan wajib digambar sebelum menyimpan penilaian baru.
                    </li>
                </ul>
            </div>

        </div>
        {{-- /Sidebar --}}

    </div>

    <script>
        // Hitung nilai akhir otomatis saat input komponen diubah
        const inputs = document.querySelectorAll('.komponen-input');
        const hasil = document.getElementById('nilai-akhir');
        const hasilMobile = document.getElementById('nilai-akhir-mobile');

        // Batasi nilai komponen supaya tidak bisa lebih dari 100 (atau
        // kurang dari 0) langsung saat diketik, bukan cuma divalidasi
        // setelah form dikirim.
        function batasiNilai(input) {
            if (input.value === '') {
                return;
            }

            const nilai = parseFloat(input.value);

            if (isNaN(nilai)) {
                return;
            }

            if (nilai > 100) {
                input.value = 100;
            } else if (nilai < 0) {
                input.value = 0;
            }
        }

        function hitungTotal() {
            let total = 0;

            inputs.forEach(function (input) {
                const nilai = parseFloat(input.value) || 0;
                const bobot = parseFloat(input.dataset.bobot) || 0;
                total += nilai * (bobot / 100);
            });

            if (hasil) {
                hasil.textContent = Math.round(total);
            }
            if (hasilMobile) {
                hasilMobile.textContent = Math.round(total);
            }
        }

        inputs.forEach(function (input) {
            input.addEventListener('input', function () {
                batasiNilai(input);
                hitungTotal();
            });
        });

        hitungTotal();

        // ---- Signature pad (pointer events, matches original supervisor page) ----
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

        const officialPad = initSignaturePad('signature-pad', 'btn-clear-official-signature');
        const officialForm = document.getElementById('form-penilaian');
        const officialSignatureInput = document.getElementById('signature-input');
        const isEditForm = {{ $isEdit ? 'true' : 'false' }};

        if (officialForm && officialPad) {
            officialForm.addEventListener('submit', function (e) {
                if (!officialPad.hasStroke() && !isEditForm) {
                    e.preventDefault();
                    alert('Tanda tangan wajib diisi sebelum menyimpan penilaian.');
                    return;
                }
                if (officialPad.hasStroke()) {
                    officialSignatureInput.value = officialPad.toDataURL();
                }
            });
        }
    </script>

    <script>
        // AJAX polling: cek berkala apakah ada perubahan (mis. tanggapan
        // korelasi baru masuk untuk pejabat ini, atau atasan sudah
        // menyelesaikan menilainya), lalu reload otomatis. Lihat
        // SupervisorController::statusVersion() untuk pola/alasannya.
        (function () {
            const POLL_INTERVAL_MS = 5000;
            const STATUS_URL = '{{ route('supervisor.official.status-version', $pejabat->id) }}';
            let currentVersion = null;

            function isUserTyping() {
                const active = document.activeElement;
                if (!active) return false;
                const tag = active.tagName;
                return tag === 'TEXTAREA' || tag === 'INPUT' || tag === 'CANVAS';
            }

            function poll() {
                fetch(STATUS_URL + '?t=' + Date.now(), { headers: { 'Accept': 'application/json', 'ngrok-skip-browser-warning': 'true' }, cache: 'no-store' })
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

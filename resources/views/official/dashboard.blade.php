<x-dashboard-layout title="Dashboard Pejabat">

    @php
        $hour = now()->hour;
        $greeting = $hour < 11 ? 'Selamat Pagi' : ($hour < 15 ? 'Selamat Siang' : ($hour < 19 ? 'Selamat Sore' : 'Selamat Malam'));
        $firstName = explode(' ', auth()->user()->name)[0];

        $totalPegawai = $employees->count();
        $sudahDinilai = $employees->filter(fn ($e) => $e->evaluations->isNotEmpty())->count();
        $siapDinilai = $employees->filter(fn ($e) => $e->evaluations->isEmpty() && $e->siapDinilaiPenilai())->count();
        $belumSiap = $totalPegawai - $sudahDinilai - $siapDinilai;
        $tanggapanDiterima = $employees->sum('feedbacks_received_count');

        $palettes = [
            'bg-blue-500', 'bg-indigo-500', 'bg-rose-500', 'bg-amber-500',
            'bg-emerald-500', 'bg-violet-500', 'bg-cyan-500', 'bg-pink-500',
        ];

        $initialsOf = function ($name) {
            return collect(explode(' ', trim($name)))
                ->map(fn ($part) => mb_substr($part, 0, 1))
                ->take(2)
                ->implode('');
        };
    @endphp

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

    {{-- Greeting banner --}}
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-blue-600 to-indigo-500 px-6 sm:px-10 py-8 sm:py-10 mb-6">
        <div class="absolute -right-10 -top-10 w-48 h-48 rounded-full bg-white/10"></div>
        <div class="absolute right-24 bottom-[-3rem] w-32 h-32 rounded-full bg-white/10"></div>

        <div class="relative flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6">
            <div class="max-w-xl">
                <h2 class="text-2xl sm:text-3xl font-extrabold text-white">{{ $greeting }}, {{ $firstName }}!</h2>
                <p class="mt-3 text-blue-50/90 text-sm sm:text-base leading-relaxed">
                    Kelola penilaian kinerja pegawai binaan Anda, berikan tanggapan untuk pejabat lain, dan pantau nilai yang Anda terima — semua dalam satu halaman.
                </p>
                <div class="mt-3 flex flex-wrap gap-2">
                    @if (auth()->user()->vendor)
                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-400 text-amber-950 text-xs font-bold px-3 py-1.5 shadow-sm">
                            &#127970; {{ auth()->user()->vendor }}
                        </span>
                    @endif
                    {{-- Badge Status disembunyikan kalau HRD sudah "menutup"
                         tampilannya lewat halaman Semua Akun - lihat
                         User::statusKontrakTerbuka() &
                         HrdController::toggleStatusKontrak(). --}}
                    @if (auth()->user()->status && auth()->user()->statusKontrakTerbuka())
                        <span class="inline-flex items-center gap-1 rounded-full bg-white text-blue-700 text-xs font-bold px-3 py-1.5 shadow-sm">
                            &#9989; {{ auth()->user()->status }}
                        </span>
                    @endif
                </div>

                <div class="mt-5">
                    <a href="{{ route('official.my-evaluations') }}"
                       class="inline-flex items-center gap-1.5 rounded-xl bg-white text-blue-600 text-sm font-semibold px-4 py-2.5 hover:bg-blue-50 transition">
                        Lihat Nilai Saya
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-6-6 6 6-6 6"/></svg>
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 shrink-0">
                <div class="rounded-2xl bg-white/15 backdrop-blur px-4 py-3 min-w-[110px]">
                    <p class="text-2xl font-extrabold text-white">{{ $totalPegawai }}</p>
                    <p class="text-[11px] text-blue-50/80 mt-0.5">Pegawai Binaan</p>
                </div>
                <div class="rounded-2xl bg-white/15 backdrop-blur px-4 py-3 min-w-[110px]">
                    <p class="text-2xl font-extrabold text-white">{{ $sudahDinilai }}</p>
                    <p class="text-[11px] text-blue-50/80 mt-0.5">Sudah Dinilai</p>
                </div>
                <div class="rounded-2xl bg-white/15 backdrop-blur px-4 py-3 min-w-[110px]">
                    <p class="text-2xl font-extrabold text-white">{{ $siapDinilai }}</p>
                    <p class="text-[11px] text-blue-50/80 mt-0.5">Siap Dinilai</p>
                </div>
                <div class="rounded-2xl bg-white/15 backdrop-blur px-4 py-3 min-w-[110px]">
                    <p class="text-2xl font-extrabold text-white">{{ $tanggapanDiterima }}</p>
                    <p class="text-[11px] text-blue-50/80 mt-0.5">Tanggapan Korelasi</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Quick nav --}}
    <div class="flex items-center gap-2 overflow-x-auto pb-2 mb-6 -mx-1 px-1">
        @if ($pejabatBinaan->isNotEmpty())
            <a href="#pejabat-binaan" class="shrink-0 text-xs font-semibold text-blue-600 bg-white border border-slate-100 shadow-sm rounded-full px-4 py-2 hover:bg-blue-50 transition">Pejabat yang Harus Dinilai</a>
        @endif
        <a href="#pegawai" class="shrink-0 text-xs font-semibold text-blue-600 bg-white border border-slate-100 shadow-sm rounded-full px-4 py-2 hover:bg-blue-50 transition">Pegawai</a>
        @if ($atasanPenilaiEmployees->isNotEmpty())
            <a href="#atasan-penilai" class="shrink-0 text-xs font-semibold text-blue-600 bg-white border border-slate-100 shadow-sm rounded-full px-4 py-2 hover:bg-blue-50 transition">Perlu Tanggapan Anda</a>
        @endif
        @if ($atasanPenilaiPejabatList->isNotEmpty())
            <a href="#atasan-penilai-pejabat" class="shrink-0 text-xs font-semibold text-blue-600 bg-white border border-slate-100 shadow-sm rounded-full px-4 py-2 hover:bg-blue-50 transition">Perlu Tanggapan Anda (Pejabat)</a>
        @endif
        <a href="#beri-tanggapan" class="shrink-0 text-xs font-semibold text-blue-600 bg-white border border-slate-100 shadow-sm rounded-full px-4 py-2 hover:bg-blue-50 transition">Beri Tanggapan</a>
        <a href="#tanggapan-untuk-saya" class="shrink-0 text-xs font-semibold text-blue-600 bg-white border border-slate-100 shadow-sm rounded-full px-4 py-2 hover:bg-blue-50 transition">Tanggapan Untuk Saya</a>
        <a href="#pegawai-sudah-dinilai" class="shrink-0 text-xs font-semibold text-emerald-600 bg-white border border-slate-100 shadow-sm rounded-full px-4 py-2 hover:bg-emerald-50 transition">Pegawai Sudah Saya Nilai</a>
        <a href="#pejabat-sudah-ditanggapi" class="shrink-0 text-xs font-semibold text-indigo-600 bg-white border border-slate-100 shadow-sm rounded-full px-4 py-2 hover:bg-indigo-50 transition">Pejabat Sudah Saya Tanggapi</a>
    </div>

    {{-- Pencarian nama pegawai & pejabat --}}
    <div class="mb-6">
        <div class="relative">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4 absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                <circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m21 21-4.35-4.35"/>
            </svg>
            <input
                type="text"
                id="dashboard-search"
                placeholder="Cari nama pegawai atau pejabat..."
                autocomplete="off"
                class="w-full rounded-2xl border border-slate-100 shadow-sm bg-white pl-11 pr-4 py-3 text-sm focus:border-blue-500 focus:ring-blue-500"
            >
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Main column --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Pejabat yang Harus Anda Nilai --}}
            @if ($pejabatBinaan->isNotEmpty())
                <div id="pejabat-binaan" class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 sm:p-7">
                    <h3 class="text-lg font-bold text-slate-800 mb-5">Pejabat yang Harus Anda Nilai</h3>

                    <div class="space-y-3" id="list-pejabat-binaan">
                        @foreach ($pejabatBinaan as $i => $pejabat)
                            @php
                                $pejabatAlreadyEvaluated = $pejabat->evaluated_count > 0;
                                $pejabatKorelasiSelesai = $pejabat->korelasiPejabatSudahMemberiTanggapan();
                                $pejabatReadyToEvaluate = $pejabat->siapDinilaiPenilaiPejabat();

                                // Terkunci begitu pejabat yang dinilai sudah tanda
                                // tangan (menanggapi & menandatangani penilaiannya
                                // sendiri) - sama pola-nya seperti $employeeTanggapanLocked
                                // / $pejabatTanggapanLocked di bagian "Perlu Tanggapan
                                // Anda" di bawah. Lihat juga evaluate_official.blade.php
                                // ($isLocked) & SupervisorController::updateOfficialEvaluation().
                                $pejabatBinaanEvalTahunIni = $pejabat->officialEvaluations->first();
                                $pejabatBinaanLocked = (bool) ($pejabatBinaanEvalTahunIni?->employee_signature);
                            @endphp

                            <div class="dashboard-searchable flex flex-col sm:flex-row sm:items-center gap-4 p-4 rounded-2xl border border-slate-100 hover:border-blue-100 hover:bg-blue-50/30 transition" data-name="{{ mb_strtolower($pejabat->name) }}">
                                <div class="flex items-center gap-4 flex-1 min-w-0">
                                    <div class="w-12 h-12 rounded-2xl shrink-0 grid place-items-center text-white font-bold text-sm {{ $palettes[$i % count($palettes)] }}">
                                        {{ $initialsOf($pejabat->name) }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-semibold text-slate-800 truncate">{{ $pejabat->name }}</p>
                                        <p class="text-xs text-slate-400 truncate">
                                            {{ $pejabat->username }}
                                            @if ($pejabat->jabatan) &middot; {{ $pejabat->jabatan }} @endif
                                            @if ($pejabat->unit_kerja) &middot; {{ $pejabat->unit_kerja }} @endif
                                        </p>
                                        <div class="flex flex-wrap gap-1.5 mt-2">
                                            @if ($pejabat->vendor)
                                                <span class="inline-flex items-center text-[11px] font-semibold px-2.5 py-1 rounded-full bg-amber-50 text-amber-700">{{ $pejabat->vendor }}</span>
                                            @endif
                                            @if ($pejabat->status && auth()->user()->statusKontrakTerbuka())
                                                <span class="inline-flex items-center text-[11px] font-semibold px-2.5 py-1 rounded-full bg-sky-50 text-sky-700">{{ $pejabat->status }}</span>
                                            @endif
                                            <span class="text-[11px] font-semibold px-2.5 py-1 rounded-full {{ $pejabatKorelasiSelesai ? 'bg-indigo-50 text-indigo-600' : 'bg-rose-50 text-rose-500' }}">
                                                {{ $pejabat->feedbacks_received_count }} / {{ \App\Models\User::MIN_TANGGAPAN_KORELASI_PEJABAT }} tanggapan korelasi
                                            </span>

                                            <span class="text-[11px] font-semibold px-2.5 py-1 rounded-full {{ $pejabat->kehadiranSudahDiisiHrd() ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-500' }}">
                                                @if ($pejabat->kehadiranSudahDiisiHrd())
                                                    &#10003; Kehadiran diisi HRD
                                                @else
                                                    Kehadiran belum diisi HRD
                                                @endif
                                            </span>
                                            @if ($pejabatAlreadyEvaluated)
                                                <span class="inline-flex items-center gap-1 text-[11px] font-semibold px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-600">&#10003; Sudah Dinilai</span>
                                            @elseif (! $pejabatReadyToEvaluate)
                                                <span class="inline-flex items-center gap-1 text-[11px] font-semibold px-2.5 py-1 rounded-full bg-slate-100 text-slate-500">Belum Bisa Dinilai</span>
                                            @else
                                                <span class="inline-flex items-center gap-1 text-[11px] font-semibold px-2.5 py-1 rounded-full bg-amber-50 text-amber-600">Siap Dinilai</span>
                                            @endif

                                            @if ($pejabatBinaanLocked)
                                                <span class="inline-flex items-center gap-1 text-[11px] font-semibold px-2.5 py-1 rounded-full bg-slate-100 text-slate-500">&#128274; Terkunci (sudah tanda tangan)</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                @php
                                    $atasanSudahCentang    = $pejabat->atasanSudahKonfirmasiPertemuan();
                                    $atasanBolehCentang    = $pejabat->checklistPertemuanPejabatBolehDiisi();
                                    // Terkunci begitu HRD sudah tanda tangan penilaian pejabat ini -
                                    // lihat User::hrdSudahMenandatanganiPenilaianPejabat().
                                    $atasanHrdLocked       = $pejabat->hrdSudahMenandatanganiPenilaianPejabat();
                                @endphp

                                <div class="shrink-0 flex flex-col gap-2 items-stretch">
                                    {{-- Tombol Checklist Pertemuan --}}
                                    @include('partials.checklist-selfie-toggle', [
                                        'action' => route('supervisor.official.checklist-pertemuan.toggle', $pejabat->id),
                                        'checked' => $atasanSudahCentang,
                                        'checkedAt' => $pejabat->atasan_konfirmasi_pertemuan_at,
                                        'selfieUrl' => $pejabat->atasan_konfirmasi_pertemuan_selfie ? Storage::disk('public')->url($pejabat->atasan_konfirmasi_pertemuan_selfie) : null,
                                        'evidenceType' => $pejabat->atasan_konfirmasi_pertemuan_evidence_type,
                                        'meetingMethod' => $pejabat->atasan_konfirmasi_pertemuan_metode,
                                        'checkedLabel' => 'Sudah Bertemu',
                                        'uncheckedLabel' => 'Tandai Pertemuan',
                                        'boleh' => $atasanBolehCentang,
                                        'bolehMessage' => 'Belum bisa memberikan bukti evaluasi. Menunggu siap dinilai & tanggapan Atasan Penilai.',
                                        'hrdLocked' => $atasanHrdLocked,
                                        'hrdLockedMessage' => 'Terkunci - penilaian sudah ditanda-tangani HRD.',
                                    ])

                                    {{-- Tombol Nilai Pejabat --}}
                                    @if ($pejabatBinaanLocked)
                                        <a href="{{ route('supervisor.official', $pejabat->id) }}"
                                           title="Pejabat sudah menandatangani penilaiannya - nilai tidak bisa diubah lagi"
                                           class="text-center rounded-xl bg-slate-100 text-slate-500 text-sm font-semibold px-4 py-2.5 hover:bg-slate-200 transition">
                                            Lihat Penilaian
                                        </a>
                                    @elseif (! $pejabatAlreadyEvaluated && ! $pejabatReadyToEvaluate)
                                        <button type="button" disabled
                                                title="Menunggu minimal {{ \App\Models\User::MIN_TANGGAPAN_KORELASI_PEJABAT }} tanggapan korelasi dari pejabat lain dan data kehadiran dari HRD"
                                                class="text-center rounded-xl bg-slate-100 text-slate-400 text-sm font-semibold px-4 py-2.5 cursor-not-allowed">
                                            Belum Bisa Dinilai
                                        </button>
                                    @else
                                        <a href="{{ route('supervisor.official', $pejabat->id) }}"
                                           class="text-center rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2.5 transition">
                                            Nilai Pejabat
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endforeach

                        <p class="hidden text-sm text-slate-400 py-2" data-role="no-result">Tidak ada pejabat yang cocok dengan pencarian.</p>
                    </div>
                </div>
            @endif

            {{-- Pegawai --}}
            <div id="pegawai" class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 sm:p-7">
                <h3 class="text-lg font-bold text-slate-800 mb-5">Pegawai</h3>

                @if ($employees->isEmpty())
                    <p class="text-sm text-slate-400">Belum ada data pegawai.</p>
                @else
                    <div class="space-y-3" id="list-pegawai">
                        @foreach ($employees as $i => $employee)
                            @php
                                $alreadyEvaluated = $employee->evaluations->isNotEmpty();
                                $readyToEvaluate = $employee->siapDinilaiPenilai();
                            @endphp

                            <div class="dashboard-searchable flex flex-col sm:flex-row sm:items-center gap-4 p-4 rounded-2xl border border-slate-100 hover:border-blue-100 hover:bg-blue-50/30 transition" data-name="{{ mb_strtolower($employee->name) }}">
                                <div class="flex items-center gap-4 flex-1 min-w-0">
                                    <div class="w-12 h-12 rounded-2xl shrink-0 grid place-items-center text-white font-bold text-sm {{ $palettes[$i % count($palettes)] }}">
                                        {{ $initialsOf($employee->name) }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-semibold text-slate-800 truncate">{{ $employee->name }}</p>
                                        <p class="text-xs text-slate-400 truncate">
                                            {{ $employee->username }}
                                            @if ($employee->jabatan) &middot; {{ $employee->jabatan }} @endif
                                            @if ($employee->unit_kerja) &middot; {{ $employee->unit_kerja }} @endif
                                        </p>

                                        <div class="flex flex-wrap gap-1.5 mt-2">
                                            @if ($employee->vendor)
                                                <span class="inline-flex items-center text-[11px] font-semibold px-2.5 py-1 rounded-full bg-amber-50 text-amber-700">{{ $employee->vendor }}</span>
                                            @endif
                                            @if ($employee->status && auth()->user()->statusKontrakTerbuka())
                                                <span class="inline-flex items-center text-[11px] font-semibold px-2.5 py-1 rounded-full bg-sky-50 text-sky-700">{{ $employee->status }}</span>
                                            @endif
                                            @unless ($employee->is_spg)
                                                <span class="text-[11px] font-semibold px-2.5 py-1 rounded-full {{ $employee->korelasiSudahMemberiTanggapan() ? 'bg-indigo-50 text-indigo-600' : 'bg-rose-50 text-rose-500' }}">
                                                    {{ $employee->feedbacks_received_count }} / {{ \App\Models\User::MIN_TANGGAPAN_KORELASI }} tanggapan korelasi
                                                </span>
                                            @endunless

                                            <span class="text-[11px] font-semibold px-2.5 py-1 rounded-full {{ $employee->kehadiranSudahDiisiHrd() ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-500' }}">
                                                @if ($employee->kehadiranSudahDiisiHrd())
                                                    &#10003; Kehadiran diisi HRD
                                                @else
                                                    Kehadiran belum diisi HRD
                                                @endif
                                            </span>

                                            @if ($alreadyEvaluated)
                                                <span class="text-[11px] font-semibold px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-600">&#10003; Sudah dinilai</span>
                                            @elseif (! $readyToEvaluate)
                                                <span class="text-[11px] font-semibold px-2.5 py-1 rounded-full bg-slate-100 text-slate-500">Belum bisa dinilai</span>
                                            @else
                                                <span class="text-[11px] font-semibold px-2.5 py-1 rounded-full bg-amber-50 text-amber-600">Siap dinilai</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="flex flex-col items-stretch sm:items-end gap-2 shrink-0 w-full sm:w-auto">
                                    {{-- Checklist Pertemuan & Evaluasi (khusus Penilai yang ditugaskan
                                         langsung). Dipindahkan ke Dashboard supaya semua checklist
                                         pejabat ada di luar/Dashboard, bukan di halaman Evaluate. --}}
                                    @if ($employee->supervisor_id === auth()->id())
                                        @php
                                            // Baru boleh MULAI dicentang setelah Atasan Penilai
                                            // (SupervisorFeedback) sudah mengisi tanggapannya untuk
                                            // pegawai ini. Membatalkan checklist yang sudah tercentang
                                            // tetap boleh kapan saja, wajib selfie setiap dicentang.
                                            $penilaiSudahCentang = $employee->penilaiSudahKonfirmasiPertemuan();
                                            $penilaiBolehCentang = $employee->checklistPertemuanPenilaiBolehDiisi();
                                            // Terkunci begitu HRD sudah tanda tangan penilaian pegawai ini -
                                            // lihat User::hrdSudahMenandatanganiPenilaian().
                                            $penilaiHrdLocked    = $employee->hrdSudahMenandatanganiPenilaian();
                                        @endphp
                                        @include('partials.checklist-selfie-toggle', [
                                            'action' => route('official.employee.checklist-pertemuan.toggle', $employee->id),
                                            'checked' => $penilaiSudahCentang,
                                            'checkedAt' => $employee->penilai_konfirmasi_pertemuan_at,
                                            'selfieUrl' => $employee->penilai_konfirmasi_pertemuan_selfie ? Storage::disk('public')->url($employee->penilai_konfirmasi_pertemuan_selfie) : null,
                                            'evidenceType' => $employee->penilai_konfirmasi_pertemuan_evidence_type,
                                            'meetingMethod' => $employee->penilai_konfirmasi_pertemuan_metode,
                                            'checkedLabel' => 'Sudah Bertemu & Evaluasi',
                                            'uncheckedLabel' => 'Tandai Sudah Bertemu & Evaluasi',
                                            'boleh' => $penilaiBolehCentang,
                                            'bolehMessage' => 'Belum bisa memberikan bukti evaluasi. Menunggu siap dinilai & tanggapan Atasan Penilai.',
                                            'hrdLocked' => $penilaiHrdLocked,
                                            'hrdLockedMessage' => 'Terkunci - penilaian sudah ditanda-tangani HRD.',
                                        ])
                                    @endif

                                    @if ($alreadyEvaluated)
                                        <button type="button" disabled
                                                class="text-center rounded-xl bg-slate-100 text-slate-400 text-sm font-semibold px-4 py-2.5 cursor-not-allowed">
                                            Berikan Penilaian
                                        </button>
                                        <button type="button"
                                                class="text-center rounded-xl border border-blue-200 text-blue-600 text-sm font-semibold px-4 py-2.5 hover:bg-blue-50 transition"
                                                data-url="{{ route('official.employee', $employee->id) }}"
                                                data-locked="{{ $employee->supervisor_feedbacks_count > 0 ? '1' : '0' }}"
                                                onclick="handleEditClick(this)">
                                            Edit Penilaian
                                        </button>
                                    @elseif (! $readyToEvaluate)
                                        <button type="button" disabled
                                                title="Menunggu tanggapan korelasi dan/atau kehadiran dari HRD"
                                                class="text-center rounded-xl bg-slate-100 text-slate-400 text-sm font-semibold px-4 py-2.5 cursor-not-allowed">
                                            Belum Bisa Dinilai
                                        </button>
                                    @else
                                        <a href="{{ route('official.employee', $employee->id) }}"
                                           class="text-center rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2.5 transition">
                                            Berikan Penilaian
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endforeach

                        <p class="hidden text-sm text-slate-400 py-2" data-role="no-result">Tidak ada pegawai yang cocok dengan pencarian.</p>
                    </div>
                @endif
            </div>

            {{-- Pegawai yang Perlu Tanggapan Anda (Atasan Penilai) --}}
            @if ($atasanPenilaiEmployees->isNotEmpty())
                <div id="atasan-penilai" class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 sm:p-7">
                    <h3 class="text-lg font-bold text-slate-800 mb-1">Pegawai yang Perlu Tanggapan Anda (Atasan Penilai)</h3>
                    <p class="text-xs text-slate-400 mb-5">
                        Anda ditugaskan sebagai Atasan Penilai untuk pegawai di bawah ini. Anda hanya bisa memberi tanggapan, bukan nilai.
                    </p>

                    <div class="space-y-3" id="list-atasan-penilai">
                        @foreach ($atasanPenilaiEmployees as $i => $employee)
                            <div class="dashboard-searchable flex flex-col sm:flex-row sm:items-center gap-4 p-4 rounded-2xl border border-slate-100 hover:border-blue-100 hover:bg-blue-50/30 transition" data-name="{{ mb_strtolower($employee->name) }}">
                                <div class="flex items-center gap-4 flex-1 min-w-0">
                                    <div class="w-12 h-12 rounded-2xl shrink-0 grid place-items-center text-white font-bold text-sm {{ $palettes[$i % count($palettes)] }}">
                                        {{ $initialsOf($employee->name) }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-semibold text-slate-800 truncate">{{ $employee->name }}</p>
                                        <p class="text-xs text-slate-400 truncate">
                                            {{ $employee->username }}
                                            @if ($employee->jabatan) &middot; {{ $employee->jabatan }} @endif
                                            @if ($employee->unit_kerja) &middot; {{ $employee->unit_kerja }} @endif
                                        </p>

                                @php
                                    // Terkunci begitu pegawai yang dinilai sudah tanda
                                    // tangan penilaiannya sendiri (Evaluation::
                                    // employee_signature) - lihat OfficialController::
                                    // giveTanggapanPegawai(). $employee->evaluations
                                    // sudah dibatasi ke tahun aktif dari query index().
                                    $employeeEvalTahunIni = $employee->evaluations->first();
                                    $employeeTanggapanLocked = (bool) ($employeeEvalTahunIni?->employee_signature);
                                @endphp

                                <div class="flex flex-wrap gap-1.5 mt-2">
                                    @if ($employee->evaluations_count > 0)
                                        <span class="text-[11px] font-semibold px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-600">&#10003; Sudah dinilai penilai</span>
                                    @else
                                        <span class="text-[11px] font-semibold px-2.5 py-1 rounded-full bg-rose-50 text-rose-500">Menunggu penilaian</span>
                                    @endif

                                    @if ($employee->supervisor_feedbacks_count > 0)
                                        <span class="text-[11px] font-semibold px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-600">&#10003; Sudah ditanggapi</span>
                                    @else
                                        <span class="text-[11px] font-semibold px-2.5 py-1 rounded-full bg-amber-50 text-amber-600">Belum ditanggapi</span>
                                    @endif

                                    @if ($employeeTanggapanLocked)
                                        <span class="text-[11px] font-semibold px-2.5 py-1 rounded-full bg-slate-100 text-slate-500">&#128274; Terkunci (sudah tanda tangan)</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        @if ($employeeTanggapanLocked)
                            <a href="{{ route('official.employee.tanggapan', $employee->id) }}"
                               title="Pegawai sudah menandatangani penilaiannya - tanggapan tidak bisa diubah lagi"
                               class="shrink-0 text-center rounded-xl bg-slate-100 text-slate-500 text-sm font-semibold px-4 py-2.5 hover:bg-slate-200 transition">
                                Lihat Tanggapan
                            </a>
                        @elseif ($employee->evaluations_count > 0)
                            <a href="{{ route('official.employee.tanggapan', $employee->id) }}"
                               class="shrink-0 text-center rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2.5 transition">
                                Beri Tanggapan
                            </a>
                        @else
                            <button type="button" disabled
                                    title="Penilai belum memberikan penilaian untuk pegawai ini"
                                    class="shrink-0 text-center rounded-xl bg-slate-100 text-slate-400 text-sm font-semibold px-4 py-2.5 cursor-not-allowed">
                                Beri Tanggapan
                            </button>
                        @endif
                            </div>
                        @endforeach

                        <p class="hidden text-sm text-slate-400 py-2" data-role="no-result">Tidak ada pegawai yang cocok dengan pencarian.</p>
                    </div>
                </div>
            @endif

            {{-- Pejabat yang Perlu Tanggapan Anda (Atasan Penilai) --}}
            @if ($atasanPenilaiPejabatList->isNotEmpty())
                <div id="atasan-penilai-pejabat" class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 sm:p-7">
                    <h3 class="text-lg font-bold text-slate-800 mb-1">Pejabat yang Perlu Tanggapan Anda (Atasan Penilai)</h3>
                    <p class="text-xs text-slate-400 mb-3">
                        Anda ditugaskan sebagai Atasan Penilai untuk pejabat di bawah ini. Anda hanya bisa memberi tanggapan &amp; rekomendasi, bukan nilai.
                    </p>
                    <div class="mb-5 rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-3 text-xs text-indigo-800 leading-relaxed">
                        <span class="font-bold">Perhatian:</span> Rekomendasi yang Anda berikan sebagai Atasan Penilai merupakan <span class="font-bold">keputusan akhir yang akan diambil</span>. Harap pertimbangkan rekomendasi dengan matang sebelum menyimpan.
                    </div>

                    <div class="space-y-3" id="list-atasan-penilai-pejabat">
                        @foreach ($atasanPenilaiPejabatList as $i => $pejabat)
                            <div class="dashboard-searchable flex flex-col sm:flex-row sm:items-center gap-4 p-4 rounded-2xl border border-slate-100 hover:border-blue-100 hover:bg-blue-50/30 transition" data-name="{{ mb_strtolower($pejabat->name) }}">
                                <div class="flex items-center gap-4 flex-1 min-w-0">
                                    <div class="w-12 h-12 rounded-2xl shrink-0 grid place-items-center text-white font-bold text-sm {{ $palettes[$i % count($palettes)] }}">
                                        {{ $initialsOf($pejabat->name) }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-semibold text-slate-800 truncate">{{ $pejabat->name }}</p>
                                        <p class="text-xs text-slate-400 truncate">
                                            {{ $pejabat->username }}
                                            @if ($pejabat->jabatan) &middot; {{ $pejabat->jabatan }} @endif
                                            @if ($pejabat->unit_kerja) &middot; {{ $pejabat->unit_kerja }} @endif
                                        </p>

                                @php
                                    // Sama pola-nya seperti bagian pegawai di atas:
                                    // terkunci begitu pejabat yang dinilai sudah tanda
                                    // tangan (OfficialEvaluation::employee_signature).
                                    $pejabatEvalTahunIni = $pejabat->officialEvaluations->first();
                                    $pejabatTanggapanLocked = (bool) ($pejabatEvalTahunIni?->employee_signature);
                                @endphp

                                <div class="flex flex-wrap gap-1.5 mt-2">
                                    @if ($pejabat->official_evaluations_count > 0)
                                        <span class="text-[11px] font-semibold px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-600">&#10003; Sudah dinilai</span>
                                    @else
                                        <span class="text-[11px] font-semibold px-2.5 py-1 rounded-full bg-rose-50 text-rose-500">Menunggu penilaian</span>
                                    @endif

                                    @if ($pejabat->official_supervisor_feedbacks_count > 0)
                                        <span class="text-[11px] font-semibold px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-600">&#10003; Sudah ditanggapi</span>
                                    @else
                                        <span class="text-[11px] font-semibold px-2.5 py-1 rounded-full bg-amber-50 text-amber-600">Belum ditanggapi</span>
                                    @endif

                                    @if ($pejabatTanggapanLocked)
                                        <span class="text-[11px] font-semibold px-2.5 py-1 rounded-full bg-slate-100 text-slate-500">&#128274; Terkunci (sudah tanda tangan)</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        @if ($pejabatTanggapanLocked)
                            <a href="{{ route('official.pejabat.tanggapan', $pejabat->id) }}"
                               title="Pejabat sudah menandatangani penilaiannya - tanggapan tidak bisa diubah lagi"
                               class="shrink-0 text-center rounded-xl bg-slate-100 text-slate-500 text-sm font-semibold px-4 py-2.5 hover:bg-slate-200 transition">
                                Lihat Tanggapan
                            </a>
                        @elseif ($pejabat->official_evaluations_count > 0)
                            <a href="{{ route('official.pejabat.tanggapan', $pejabat->id) }}"
                               class="shrink-0 text-center rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2.5 transition">
                                Beri Tanggapan
                            </a>
                        @else
                            <button type="button" disabled
                                    title="Penilai belum memberikan penilaian untuk pejabat ini"
                                    class="shrink-0 text-center rounded-xl bg-slate-100 text-slate-400 text-sm font-semibold px-4 py-2.5 cursor-not-allowed">
                                Beri Tanggapan
                            </button>
                        @endif
                            </div>
                        @endforeach

                        <p class="hidden text-sm text-slate-400 py-2" data-role="no-result">Tidak ada pejabat yang cocok dengan pencarian.</p>
                    </div>
                </div>
            @endif

            {{-- Beri Tanggapan ke Pejabat Lain --}}
            <div id="beri-tanggapan" class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 sm:p-7">
                <h3 class="text-lg font-bold text-slate-800 mb-1">Beri Tanggapan ke Pejabat Lain</h3>
                <p class="text-xs text-slate-400 mb-5">
                    Anda bisa memberi tanggapan untuk pejabat lain dari unit kerja manapun.
                </p>

                @if ($peerOfficials->isEmpty())
                    <p class="text-sm text-slate-400">Belum ada pejabat lain yang terdaftar.</p>
                @else
                    <form method="POST" action="{{ route('official.feedback') }}" id="form-official-feedback" class="space-y-4" data-autosave>
                        @csrf

                        <div>
                            <label class="block text-xs font-semibold text-slate-500 mb-2">Pilih Pejabat</label>

                            <div class="employee-picker" id="official-picker">
                                <input
                                    type="text"
                                    id="official-search"
                                    placeholder="Cari nama pejabat..."
                                    autocomplete="off"
                                    class="w-full rounded-xl border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500"
                                >

                                <div class="employee-list mt-2 max-h-56 overflow-y-auto rounded-xl border border-slate-200 divide-y divide-slate-50" id="official-list">
                                    @foreach ($peerOfficials as $peer)
                                        <div
                                            @class([
                                                'employee-option flex items-center justify-between gap-2 px-4 py-2.5 text-sm cursor-pointer hover:bg-slate-50',
                                                'employee-option-selected !bg-blue-600 !text-white' => old('official_id') == $peer->id,
                                            ])
                                            data-id="{{ $peer->id }}"
                                            data-name="{{ mb_strtolower($peer->name) }}"
                                        >
                                            <span>{{ $peer->name }}</span>
                                            <span class="employee-unit-badge text-[11px] px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 whitespace-nowrap">
                                                {{ $peer->jabatan ?: $peer->unit_kerja }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>

                                <p class="employee-empty px-1 py-2 text-sm text-slate-400" id="official-no-result" style="display:none;">
                                    Pejabat tidak ditemukan.
                                </p>

                                <input type="hidden" name="official_id" id="official-id-input" value="{{ old('official_id') }}" required>
                            </div>
                        </div>

                        <textarea
                            name="feedback"
                            placeholder="Tulis tanggapan..."
                            minlength="10"
                            required
                            class="w-full min-h-[100px] rounded-xl border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500"
                        >{{ old('feedback') }}</textarea>

                        <div>
                            <label class="block text-xs font-semibold text-slate-500 mb-2">Tanda Tangan</label>
                            <canvas id="official-signature-pad" class="signature-canvas rounded-xl border border-slate-200 bg-white touch-none cursor-crosshair block w-full max-w-[400px] sm:w-[400px] sm:h-[150px]" style="aspect-ratio: 400 / 150;"></canvas>
                            <div class="flex items-center justify-between mt-2">
                                <span class="text-xs text-slate-400">Gambar tanda tangan di kotak di atas</span>
                                <button type="button" id="btn-clear-official-signature" class="text-xs font-medium text-slate-500 underline hover:text-slate-800">Hapus &amp; ulangi</button>
                            </div>
                            <input type="hidden" name="signature" id="official-signature-input">
                        </div>

                        <button type="submit"
                                class="inline-flex items-center justify-center rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-5 py-2.5 transition">
                            Kirim Tanggapan
                        </button>
                    </form>
                @endif
            </div>
        </div>

        {{-- Right column --}}
        <div class="space-y-6">

            {{-- Kehadiran Saya --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                <h3 class="text-sm font-bold text-slate-800 mb-4">Kehadiran Saya</h3>

                @include('partials.kehadiran-fields', ['kehadiranUser' => auth()->user()])
            </div>

            {{-- Ringkasan --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                <h3 class="text-sm font-bold text-slate-800 mb-4">Ringkasan Pegawai Binaan</h3>

                <div class="space-y-3">
                    <div class="flex items-center gap-3">
                        <span class="shrink-0 w-10 h-8 rounded-lg grid place-items-center text-xs font-bold bg-blue-50 text-blue-600">{{ $totalPegawai }}</span>
                        <span class="text-sm text-slate-600">Total Pegawai</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="shrink-0 w-10 h-8 rounded-lg grid place-items-center text-xs font-bold bg-emerald-50 text-emerald-600">{{ $sudahDinilai }}</span>
                        <span class="text-sm text-slate-600">Sudah Dinilai</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="shrink-0 w-10 h-8 rounded-lg grid place-items-center text-xs font-bold bg-amber-50 text-amber-600">{{ $siapDinilai }}</span>
                        <span class="text-sm text-slate-600">Siap Dinilai</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="shrink-0 w-10 h-8 rounded-lg grid place-items-center text-xs font-bold bg-slate-100 text-slate-500">{{ $belumSiap }}</span>
                        <span class="text-sm text-slate-600">Belum Bisa Dinilai</span>
                    </div>
                </div>
            </div>

            {{-- Tanggapan Untuk Saya --}}
            <div id="tanggapan-untuk-saya" class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                <h3 class="text-sm font-bold text-slate-800 mb-4">Tanggapan Untuk Saya (dari Pejabat Lain)</h3>

                <div class="space-y-4 max-h-[420px] overflow-y-auto pr-1" id="list-tanggapan-untuk-saya">
                    @forelse ($myPeerFeedbacks as $feedback)
                        <div class="dashboard-searchable pb-4 border-b border-slate-50 last:border-b-0 last:pb-0" data-name="{{ mb_strtolower($feedback->reviewer->name) }}">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="w-7 h-7 rounded-full bg-blue-50 text-blue-600 grid place-items-center text-[11px] font-bold shrink-0">
                                    {{ mb_substr($feedback->reviewer->name, 0, 1) }}
                                </span>
                                <span class="text-sm font-semibold text-slate-700">{{ $feedback->reviewer->name }}</span>
                            </div>
                            <p class="text-sm text-slate-500 pl-9">{{ $feedback->feedback }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-slate-400">Belum ada tanggapan.</p>
                    @endforelse

                    <p class="hidden text-sm text-slate-400 py-2" data-role="no-result">Tidak ada tanggapan dari pejabat yang cocok dengan pencarian.</p>
                </div>
            </div>

            {{-- Pegawai yang sudah saya nilai --}}
            <div id="pegawai-sudah-dinilai" class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-bold text-slate-800">Pegawai yang Sudah Saya Nilai</h3>
                    <span class="shrink-0 text-xs font-bold px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-600">{{ $sudahDinilaiEmployees->count() }}</span>
                </div>

                <div class="space-y-3 max-h-[420px] overflow-y-auto pr-1" id="list-pegawai-sudah-dinilai">
                    @forelse ($sudahDinilaiEmployees as $employee)
                        <div class="dashboard-searchable flex items-center gap-2 pb-3 border-b border-slate-50 last:border-b-0 last:pb-0" data-name="{{ mb_strtolower($employee->name) }}">
                            <span class="w-7 h-7 rounded-full bg-emerald-50 text-emerald-600 grid place-items-center text-[11px] font-bold shrink-0">
                                {{ mb_substr($employee->name, 0, 1) }}
                            </span>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-slate-700 truncate">{{ $employee->name }}</p>
                                <p class="text-xs text-slate-400 truncate">{{ $employee->jabatan ?: $employee->unit_kerja }}</p>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-400">Anda belum menilai pegawai manapun.</p>
                    @endforelse

                    <p class="hidden text-sm text-slate-400 py-2" data-role="no-result">Tidak ada pegawai yang cocok dengan pencarian.</p>
                </div>
            </div>

            {{-- Pejabat yang sudah saya beri tanggapan --}}
            <div id="pejabat-sudah-ditanggapi" class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-bold text-slate-800">Pejabat yang Sudah Saya Tanggapi</h3>
                    <span class="shrink-0 text-xs font-bold px-2.5 py-1 rounded-full bg-indigo-50 text-indigo-600">{{ $myGivenPeerFeedbacks->count() }}</span>
                </div>

                <div class="space-y-4 max-h-[420px] overflow-y-auto pr-1" id="list-pejabat-sudah-ditanggapi">
                    @forelse ($myGivenPeerFeedbacks as $feedback)
                        <div class="dashboard-searchable pb-4 border-b border-slate-50 last:border-b-0 last:pb-0" data-name="{{ mb_strtolower($feedback->employee->name) }}">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="w-7 h-7 rounded-full bg-indigo-50 text-indigo-600 grid place-items-center text-[11px] font-bold shrink-0">
                                    {{ mb_substr($feedback->employee->name, 0, 1) }}
                                </span>
                                <span class="text-sm font-semibold text-slate-700">{{ $feedback->employee->name }}</span>
                            </div>
                            <p class="text-sm text-slate-500 pl-9">{{ $feedback->feedback }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-slate-400">Anda belum memberi tanggapan ke pejabat lain.</p>
                    @endforelse

                    <p class="hidden text-sm text-slate-400 py-2" data-role="no-result">Tidak ada pejabat yang cocok dengan pencarian.</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Pencarian global nama pegawai & pejabat di seluruh dashboard.
        // Bekerja di semua daftar yang ditandai id="list-..." dan item
        // bertanda class "dashboard-searchable" + data-name (lowercase).
        const dashboardSearch = document.getElementById('dashboard-search');
        if (dashboardSearch) {
            const dashboardLists = document.querySelectorAll('[id^="list-"]');

            dashboardSearch.addEventListener('input', function () {
                const query = this.value.trim().toLowerCase();

                dashboardLists.forEach(function (list) {
                    const items = list.querySelectorAll('.dashboard-searchable');
                    let visibleCount = 0;

                    items.forEach(function (item) {
                        const match = !query || (item.dataset.name || '').includes(query);
                        item.classList.toggle('hidden', !match);
                        if (match) visibleCount++;
                    });

                    const noResult = list.querySelector('[data-role="no-result"]');
                    if (noResult) {
                        noResult.classList.toggle('hidden', !(query && items.length > 0 && visibleCount === 0));
                    }
                });
            });
        }
    </script>

    <script>
        function handleEditClick(btn) {
            if (btn.dataset.locked === '1') {
                alert('Penilaian ini sudah tidak bisa di edit');
                return;
            }
            window.location.href = btn.dataset.url;
        }
    </script>

    <script>
        // Fungsi reusable untuk pad tanda tangan (sama seperti dashboard pegawai).
        function initSignaturePad(canvasId, clearBtnId) {
            const canvas = document.getElementById(canvasId);
            if (!canvas) return null;

            const ctx = canvas.getContext('2d', { alpha: true });
            let drawing = false;
            let last = null;
            let hasStroke = false;
            let ratio = Math.max(window.devicePixelRatio || 1, 1);

            function setupCanvas(keepDrawing = false) {
                const rect = canvas.getBoundingClientRect();
                const cssWidth = Math.max(Math.round(rect.width), 1);
                const cssHeight = Math.max(Math.round(rect.height), 1);

                ratio = Math.max(window.devicePixelRatio || 1, 1);

                const oldImage = keepDrawing && canvas.width && canvas.height
                    ? canvas.toDataURL('image/png')
                    : null;

                canvas.width = Math.round(cssWidth * ratio);
                canvas.height = Math.round(cssHeight * ratio);

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

        const officialPad = initSignaturePad('official-signature-pad', 'btn-clear-official-signature');
        const officialForm = document.getElementById('form-official-feedback');
        const officialSignatureInput = document.getElementById('official-signature-input');
        const officialIdInput = document.getElementById('official-id-input');

        if (officialForm && officialPad) {
            officialForm.addEventListener('submit', function (e) {
                if (!officialIdInput.value) {
                    e.preventDefault();
                    alert('Silakan pilih pejabat terlebih dahulu.');
                    return;
                }

                if (!officialPad.hasStroke()) {
                    e.preventDefault();
                    alert('Tanda tangan wajib diisi sebelum mengirim tanggapan.');
                    return;
                }
                officialSignatureInput.value = officialPad.toDataURL();
            });
        }

        // Pencarian pejabat pada pemilih "Beri Tanggapan ke Pejabat Lain"
        const officialSearch = document.getElementById('official-search');
        const officialList = document.getElementById('official-list');

        if (officialSearch && officialList) {
            const officialOptions = Array.from(officialList.querySelectorAll('.employee-option'));
            const officialNoResult = document.getElementById('official-no-result');

            function applyOfficialFilter() {
                const query = officialSearch.value.trim().toLowerCase();
                let visibleCount = 0;

                officialOptions.forEach(function (opt) {
                    const visible = !query || opt.dataset.name.includes(query);
                    opt.classList.toggle('hidden', !visible);
                    if (visible) visibleCount++;
                });

                if (officialNoResult) {
                    officialNoResult.style.display = visibleCount === 0 ? 'block' : 'none';
                }
            }

            officialSearch.addEventListener('input', applyOfficialFilter);

            officialOptions.forEach(function (opt) {
                opt.addEventListener('click', function () {
                    officialOptions.forEach(o => o.classList.remove('employee-option-selected', '!bg-blue-600', '!text-white'));
                    opt.classList.add('employee-option-selected', '!bg-blue-600', '!text-white');
                    officialIdInput.value = opt.dataset.id;
                });
            });
        }
    </script>

    <script>
        // AJAX polling: cek berkala apakah ada perubahan (mis. tanggapan
        // korelasi baru masuk, atau penilaian baru selesai), lalu reload
        // otomatis. Lihat OfficialController::statusVersion() &
        // EmployeeController::statusVersion() untuk pola/alasannya.
        (function () {
            const POLL_INTERVAL_MS = 5000;
            const STATUS_URL = '{{ route('official.dashboard.status-version') }}';
            let currentVersion = null;

            function isUserTyping() {
                const active = document.activeElement;
                if (!active) return false;
                const tag = active.tagName;
                return tag === 'TEXTAREA' || tag === 'INPUT' || tag === 'CANVAS';
            }

            function poll() {
                if (document.hidden) return;

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
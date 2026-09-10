<x-dashboard-layout title="Dashboard Pegawai">

    <?php
        $hour = now()->hour;
        $greeting = $hour < 11 ? 'Selamat Pagi' : ($hour < 15 ? 'Selamat Siang' : ($hour < 19 ? 'Selamat Sore' : 'Selamat Malam'));
        $percent = $myEvaluation ? max(0, min(100, (float) $myEvaluation->score)) : 0;
    ?>

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
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-blue-600 to-blue-500 px-6 sm:px-10 py-8 sm:py-10 mb-8">
        <div class="absolute -right-10 -top-10 w-48 h-48 rounded-full bg-white/10"></div>
        <div class="absolute right-16 bottom-[-3rem] w-32 h-32 rounded-full bg-white/10"></div>
        <div class="relative max-w-xl">
            <h2 class="text-2xl sm:text-3xl font-extrabold text-white">{{ $greeting }}, {{ explode(' ', auth()->user()->name)[0] }}!</h2>
            <p class="mt-3 text-blue-50/90 text-sm sm:text-base leading-relaxed">
                Pantau hasil penilaian kinerja, berikan tanggapan untuk rekan kerja, dan lihat masukan yang kamu terima — semua dalam satu halaman.
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
                <a href="#tanggapan-saya-berikan"
                   class="inline-flex items-center gap-1.5 rounded-xl bg-white text-blue-600 text-sm font-semibold px-4 py-2.5 hover:bg-blue-50 transition">
                    Lihat Tanggapan yang Sudah Saya Berikan
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="M5 12h14m-6-6 6 6-6 6"/></svg>
                </a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Main column --}}
        <div class="lg:col-span-2 space-y-6">

            {{-- Nilai Saya --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 sm:p-7">
                <h3 class="text-lg font-bold text-slate-800 mb-5">Nilai Saya</h3>

                @if ($myEvaluation)
                    <div class="flex flex-col sm:flex-row items-center sm:items-start gap-6">
                        <div class="relative w-32 h-32 shrink-0 rounded-full"
                             style="background: conic-gradient(#2563eb {{ $percent }}%, #e5edf7 {{ $percent }}% 100%)">
                            <div class="absolute inset-2 bg-white rounded-full flex flex-col items-center justify-center">
                                <span class="text-2xl font-extrabold text-blue-600">{{ $myEvaluation->score }}</span>
                                <span class="text-[11px] text-slate-400">dari 100</span>
                            </div>
                        </div>

                        <div class="flex-1 w-full space-y-2.5 text-sm">
                            <div class="flex justify-between border-b border-slate-50 pb-2">
                                <span class="text-slate-400">Pejabat Penilai</span>
                                <span class="font-semibold text-slate-700">{{ $myEvaluation->official->name }}</span>
                            </div>
                            <div class="flex justify-between border-b border-slate-50 pb-2">
                                <span class="text-slate-400">Rekomendasi</span>
                                <span class="font-semibold text-slate-700">{{ $myEvaluation->recommendationLabel() }}</span>
                            </div>
                            @if ($myEvaluation->kenaikan_gaji_amount)
                                <div class="flex justify-between border-b border-slate-50 pb-2">
                                    <span class="text-slate-400">Nominal Kenaikan Gaji</span>
                                    <span class="font-semibold text-slate-700">Rp {{ number_format($myEvaluation->kenaikan_gaji_amount, 0, ',', '.') }}</span>
                                </div>
                            @endif
                            @if ($myEvaluation->feedback)
                                <p class="text-slate-500 pt-1">{{ $myEvaluation->feedback }}</p>
                            @endif
                        </div>
                    </div>

                    <button type="button"
                            id="btn-toggle-detail-penilaian"
                            onclick="toggleDetailPenilaian()"
                            class="mt-5 inline-flex items-center gap-1.5 text-sm font-semibold text-blue-600 hover:text-blue-700">
                        <span>Lihat Detail Penilaian</span>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg>
                    </button>

                    <div class="detail-penilaian mt-4" id="detail-penilaian" style="display:none;">
                        <div class="overflow-x-auto rounded-xl border border-slate-100">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wide">
                                        <th class="text-left px-4 py-3 font-semibold">Poin Penilaian</th>
                                        <th class="text-left px-4 py-3 font-semibold">Nilai</th>
                                        <th class="text-left px-4 py-3 font-semibold">Bobot</th>
                                        <th class="text-left px-4 py-3 font-semibold">Kontribusi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50">
                                    @foreach (\App\Models\Evaluation::WEIGHTS as $key => $weight)
                                        <?php
                                            $value = (float) ($myEvaluation->{$key} ?? 0);
                                            $contribution = round($value * ($weight / 100));
                                        ?>
                                        <tr>
                                            <td class="px-4 py-3 align-top">
                                                <p class="font-medium text-slate-700">{{ \App\Models\Evaluation::LABELS[$key] ?? $key }}</p>
                                                <p class="text-xs text-slate-400 mt-0.5">{{ \App\Models\Evaluation::DESCRIPTIONS[$key] ?? '' }}</p>
                                            </td>
                                            <td class="px-4 py-3 align-top text-slate-600">{{ $value }}</td>
                                            <td class="px-4 py-3 align-top text-slate-600">{{ $weight }}%</td>
                                            <td class="px-4 py-3 align-top text-slate-600">{{ $contribution }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="bg-slate-50">
                                        <td colspan="3" class="px-4 py-3 font-bold text-slate-700">Total Nilai Akhir</td>
                                        <td class="px-4 py-3 font-bold text-blue-600">{{ $myEvaluation->score }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <div class="mt-6 pt-6 border-t border-slate-100">
                        <h4 class="text-sm font-bold text-slate-800 mb-3">Tanggapan Atasan Penilai</h4>

                        @if ($mySupervisorFeedback)
                            <div class="space-y-2.5 text-sm">
                                <div class="flex justify-between border-b border-slate-50 pb-2">
                                    <span class="text-slate-400">Atasan Penilai</span>
                                    <span class="font-semibold text-slate-700">{{ $mySupervisorFeedback->supervisor->name ?? '-' }}</span>
                                </div>
                                <div class="flex justify-between border-b border-slate-50 pb-2">
                                    <span class="text-slate-400">Rekomendasi</span>
                                    <span class="font-semibold text-slate-700">{{ $mySupervisorFeedback->recommendationLabel() }}</span>
                                </div>
                                @if ($mySupervisorFeedback->kenaikan_gaji_amount)
                                    <div class="flex justify-between border-b border-slate-50 pb-2">
                                        <span class="text-slate-400">Nominal Kenaikan Gaji</span>
                                        <span class="font-semibold text-slate-700">Rp {{ number_format($mySupervisorFeedback->kenaikan_gaji_amount, 0, ',', '.') }}</span>
                                    </div>
                                @endif
                                @if ($mySupervisorFeedback->feedback)
                                    <p class="text-slate-500 pt-1">{{ $mySupervisorFeedback->feedback }}</p>
                                @endif
                            </div>

                            @if ($mySupervisorFeedback->signature)
                                <img src="{{ Storage::disk('public')->url($mySupervisorFeedback->signature) }}"
                                     class="w-40 h-[70px] object-contain border border-slate-100 rounded-lg bg-slate-50 mt-3">
                            @endif
                        @else
                            <p class="text-sm text-slate-400">Tanggapan dari Atasan Penilai belum tersedia.</p>
                        @endif
                    </div>

                    <div class="mt-6 pt-6 border-t border-slate-100" x-data="{ editing: false }">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="text-sm font-bold text-slate-800">Tanggapan Saya Atas Penilaian Ini</h4>
                            @if ($myEvaluation->employee_response)
                                <button type="button"
                                        x-show="! editing"
                                        x-on:click="editing = true; $nextTick(() => window.dispatchEvent(new Event('eval-edit-shown')))"
                                        class="text-xs font-semibold text-blue-600 hover:text-blue-700 underline">
                                    Edit Tanggapan
                                </button>
                                <button type="button"
                                        x-show="editing"
                                        x-on:click="editing = false"
                                        class="text-xs font-semibold text-slate-500 hover:text-slate-700 underline">
                                    Batal
                                </button>
                            @endif
                        </div>

                        @if ($myEvaluation->employee_response)
                            <div x-show="! editing">
                                <p class="text-sm text-slate-600 bg-slate-50 rounded-xl px-4 py-3">{{ $myEvaluation->employee_response }}</p>
                                <p class="text-xs text-slate-400 mt-2">
                                    Dikirim {{ $myEvaluation->employee_response_at?->translatedFormat('d M Y H:i') }}
                                </p>

                                @if ($myEvaluation->employee_signature)
                                    <img src="{{ Storage::disk('public')->url($myEvaluation->employee_signature) }}"
                                         class="w-40 h-[70px] object-contain border border-slate-100 rounded-lg bg-slate-50 mt-2">
                                @endif
                            </div>
                        @endif

                        @if (! $myEvaluation->employee_response)
                            @if (!$canRespondEvaluation)
                                <p class="text-sm text-slate-400 bg-slate-50 rounded-xl px-4 py-3">
                                    Tanggapan Anda belum dapat diisi. Penilaian dari Atasan Pejabat untuk Anda belum diselesaikan. Silakan cek kembali nanti.
                                </p>
                            @else
                                <form method="POST" action="{{ route('employee.evaluation.respond', $myEvaluation->id) }}" id="form-eval-response" class="space-y-4" data-autosave>
                                    @csrf

                                    <textarea
                                        name="employee_response"
                                        placeholder="Tulis tanggapan Anda atas penilaian ini..."
                                        minlength="5"
                                        required
                                        class="w-full min-h-[100px] rounded-xl border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500"
                                    >{{ old('employee_response') }}</textarea>

                                    <div>
                                        <label class="block text-xs font-semibold text-slate-500 mb-2">Tanda Tangan</label>
                                        <canvas id="eval-signature-pad" class="signature-canvas rounded-xl border border-slate-200 bg-white touch-none cursor-crosshair block w-full max-w-[400px] sm:w-[400px] sm:h-[150px]" style="aspect-ratio: 400 / 150;"></canvas>
                                        <div class="flex items-center justify-between mt-2">
                                            <span class="text-xs text-slate-400">Gambar tanda tangan di kotak di atas</span>
                                            <button type="button" id="btn-clear-eval-signature" class="text-xs font-medium text-slate-500 underline hover:text-slate-800">Hapus &amp; ulangi</button>
                                        </div>
                                        <input type="hidden" name="employee_signature" id="eval-signature-input">
                                    </div>

                                    <button type="submit"
                                            class="inline-flex items-center justify-center rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-5 py-2.5 transition">
                                        Kirim Tanggapan
                                    </button>
                                </form>
                            @endif
                        @else
                            <div x-show="editing" x-cloak>
                                <form method="POST" action="{{ route('employee.evaluation.respond', $myEvaluation->id) }}" id="form-eval-response-edit" class="space-y-4" data-autosave>
                                    @csrf

                                    <textarea
                                        name="employee_response"
                                        placeholder="Tulis tanggapan Anda atas penilaian ini..."
                                        minlength="5"
                                        required
                                        class="w-full min-h-[100px] rounded-xl border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500"
                                    >{{ old('employee_response', $myEvaluation->employee_response) }}</textarea>

                                    <div>
                                        <label class="block text-xs font-semibold text-slate-500 mb-2">Tanda Tangan</label>
                                        <canvas id="eval-signature-pad-edit" class="signature-canvas rounded-xl border border-slate-200 bg-white touch-none cursor-crosshair block w-full max-w-[400px] sm:w-[400px] sm:h-[150px]" style="aspect-ratio: 400 / 150;"></canvas>
                                        <div class="flex items-center justify-between mt-2">
                                            <span class="text-xs text-slate-400">Gambar ulang tanda tangan di kotak di atas</span>
                                            <button type="button" id="btn-clear-eval-signature-edit" class="text-xs font-medium text-slate-500 underline hover:text-slate-800">Hapus &amp; ulangi</button>
                                        </div>
                                        <input type="hidden" name="employee_signature" id="eval-signature-input-edit">
                                    </div>

                                    <button type="submit"
                                            class="inline-flex items-center justify-center rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-5 py-2.5 transition">
                                        Simpan Perubahan
                                    </button>
                                </form>
                            </div>
                        @endif
                    </div>

                @else
                    <p class="text-sm text-slate-400">Penilaian belum tersedia.</p>
                @endif
            </div>

            {{-- Penilaian Pegawai (khusus akun yang ditugaskan HRD sebagai
                 Penilai untuk pegawai lain lewat users.supervisor_id - lihat
                 EmployeeController::index() & OfficialController::canEvaluate()).
                 Section ini otomatis kosong/tersembunyi untuk akun pegawai
                 biasa yang tidak ditugaskan menilai siapa pun. --}}
            @if ($pegawaiYangDinilai->isNotEmpty())
                <div id="penilaian-pegawai" class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 sm:p-7">
                    <h3 class="text-lg font-bold text-slate-800 mb-1">Penilaian Pegawai</h3>
                    <p class="text-xs text-slate-400 mb-5">
                        Anda ditugaskan HRD sebagai Penilai untuk pegawai di bawah ini.
                    </p>

                    <div class="space-y-3">
                        @foreach ($pegawaiYangDinilai as $employee)
                            @php
                                $alreadyEvaluated = $employee->evaluations->isNotEmpty();
                                $readyToEvaluate = $employee->siapDinilaiPenilai();
                            @endphp

                            <div class="flex flex-col sm:flex-row sm:items-center gap-4 p-4 rounded-2xl border border-slate-100 hover:border-blue-100 hover:bg-blue-50/30 transition">
                                <div class="flex-1 min-w-0">
                                    <p class="font-semibold text-slate-800 truncate">{{ $employee->name }}</p>
                                    <p class="text-xs text-slate-400 truncate">
                                        {{ $employee->username }}
                                        @if ($employee->jabatan) &middot; {{ $employee->jabatan }} @endif
                                        @if ($employee->unit_kerja) &middot; {{ $employee->unit_kerja }} @endif
                                    </p>

                                    <div class="flex flex-wrap gap-1.5 mt-2">
                                        @if ($alreadyEvaluated)
                                            <span class="text-[11px] font-semibold px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-600">&#10003; Sudah dinilai</span>
                                        @elseif (! $readyToEvaluate)
                                            <span class="text-[11px] font-semibold px-2.5 py-1 rounded-full bg-slate-100 text-slate-500">Belum bisa dinilai</span>
                                        @else
                                            <span class="text-[11px] font-semibold px-2.5 py-1 rounded-full bg-amber-50 text-amber-600">Siap dinilai</span>
                                        @endif
                                    </div>
                                </div>

                                <div class="flex flex-col items-stretch sm:items-end gap-2 shrink-0 w-full sm:w-auto">
                                    @if ($alreadyEvaluated)
                                        <a href="{{ route('official.employee', $employee->id) }}"
                                           class="text-center rounded-xl border border-blue-200 text-blue-600 text-sm font-semibold px-4 py-2.5 hover:bg-blue-50 transition">
                                            Edit Penilaian
                                        </a>
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
                    </div>
                </div>
            @endif

            {{-- Berikan Tanggapan Teman --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 sm:p-7">
                <h3 class="text-lg font-bold text-slate-800 mb-5">Berikan Tanggapan Teman</h3>

                <form method="POST" action="{{ route('employee.feedback') }}" id="form-feedback" class="space-y-4" data-autosave>
                    @csrf

                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-2">Pilih Pegawai</label>

                        <div class="employee-picker" id="employee-picker">
                            <input
                                type="text"
                                id="employee-search"
                                placeholder="Cari nama pegawai..."
                                autocomplete="off"
                                class="w-full rounded-xl border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500"
                            >

                            <select id="employee-unit-filter" class="w-full mt-2 rounded-xl border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="all">Semua Unit</option>
                                @foreach ($employeeUnits as $unit)
                                    <option value="{{ $unit }}">{{ $unit }}</option>
                                @endforeach
                            </select>

                            <div class="employee-list mt-2 max-h-56 overflow-y-auto rounded-xl border border-slate-200 divide-y divide-slate-50" id="employee-list">
                                @forelse ($employees as $employee)
                                    <div
                                        @class([
                                            'employee-option flex items-center justify-between gap-2 px-4 py-2.5 text-sm cursor-pointer hover:bg-slate-50',
                                            'employee-option-selected !bg-blue-600 !text-white' => old('employee_id') == $employee->id,
                                        ])
                                        data-id="{{ $employee->id }}"
                                        data-name="{{ mb_strtolower($employee->name) }}"
                                        data-unit="{{ $employee->unit_kerja ?: 'Tanpa Unit' }}"
                                    >
                                        <span>{{ $employee->name }}</span>
                                        <span class="employee-unit-badge text-[11px] px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 whitespace-nowrap">
                                            {{ $employee->unit_kerja ?: 'Tanpa Unit' }}
                                        </span>
                                    </div>
                                @empty
                                    <p class="employee-empty px-4 py-3 text-sm text-slate-400">Tidak ada pegawai lain.</p>
                                @endforelse
                            </div>

                            <p class="employee-empty px-1 py-2 text-sm text-slate-400" id="employee-no-result" style="display:none;">
                                Pegawai tidak ditemukan.
                            </p>

                            <input type="hidden" name="employee_id" id="employee-id-input" value="{{ old('employee_id') }}" required>
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
                        <canvas id="signature-pad" class="signature-canvas rounded-xl border border-slate-200 bg-white touch-none cursor-crosshair block w-full max-w-[400px] sm:w-[400px] sm:h-[150px]" style="aspect-ratio: 400 / 150;"></canvas>
                        <div class="flex items-center justify-between mt-2">
                            <span class="text-xs text-slate-400">Gambar tanda tangan di kotak di atas</span>
                            <button type="button" id="btn-clear-signature" class="text-xs font-medium text-slate-500 underline hover:text-slate-800">Hapus &amp; ulangi</button>
                        </div>
                        <input type="hidden" name="signature" id="signature-input">
                    </div>

                    <button type="submit"
                            class="inline-flex items-center justify-center rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-5 py-2.5 transition">
                        Kirim Tanggapan
                    </button>
                </form>
            </div>
        </div>

        {{-- Right column --}}
        <div class="space-y-6">

            {{-- Kehadiran Saya --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                <h3 class="text-sm font-bold text-slate-800 mb-4">Kehadiran Saya</h3>

                @include('partials.kehadiran-fields', ['kehadiranUser' => auth()->user()])
            </div>

            {{-- Ringkasan nilai --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-bold text-slate-800">Ringkasan Nilai</h3>
                </div>

                @if ($myEvaluation)
                    <div class="space-y-3">
                        @foreach (\App\Models\Evaluation::WEIGHTS as $key => $weight)
                            <?php $value = (float) ($myEvaluation->{$key} ?? 0); ?>
                            <div class="flex items-center gap-3">
                                <span class="shrink-0 w-10 h-8 rounded-lg grid place-items-center text-xs font-bold
                                    {{ $value >= 80 ? 'bg-emerald-50 text-emerald-600' : ($value >= 60 ? 'bg-blue-50 text-blue-600' : 'bg-red-50 text-red-500') }}">
                                    {{ $value }}
                                </span>
                                <span class="text-sm text-slate-600 truncate">{{ \App\Models\Evaluation::LABELS[$key] ?? $key }}</span>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-slate-400">Belum ada nilai.</p>
                @endif
            </div>

            {{-- Checklist Pertemuan & Evaluasi --}}
            @php
                // Baru boleh MULAI dicentang setelah Atasan Penilai
                // (SupervisorFeedback) sudah mengisi tanggapannya untuk
                // pegawai ini - lihat User::checklistPertemuanBolehDiisi().
                // Membatalkan checklist yang sudah tercentang tetap boleh
                // kapan saja. Setiap kali DICENTANG wajib disertai selfie
                // langsung dari kamera perangkat.
                $pegawaiSudahCentang = auth()->user()->pegawaiSudahKonfirmasiPertemuan();
                $pegawaiBolehCentang = auth()->user()->checklistPertemuanBolehDiisi();
                // Terkunci begitu HRD sudah tanda tangan penilaian ini -
                // lihat User::hrdSudahMenandatanganiPenilaian().
                $pegawaiHrdLocked = auth()->user()->hrdSudahMenandatanganiPenilaian();
            @endphp
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                <h3 class="text-sm font-bold text-slate-800 mb-2">Checklist Pertemuan &amp; Evaluasi</h3>
                <p class="text-xs text-slate-400 mb-4">
                    Centang setelah Anda bertemu langsung dan mendiskusikan hasil evaluasi dengan Penilai Anda. Selfie diperlukan sebagai bukti.
                    HRD tidak dapat mencetak PDF penilaian Anda sebelum checklist ini dicentang.
                </p>

                @include('partials.checklist-selfie-toggle', [
                    'action' => route('employee.checklist-pertemuan.toggle'),
                    'checked' => $pegawaiSudahCentang,
                    'checkedAt' => auth()->user()->pegawai_konfirmasi_pertemuan_at,
                    'selfieUrl' => auth()->user()->pegawai_konfirmasi_pertemuan_selfie ? Storage::disk('public')->url(auth()->user()->pegawai_konfirmasi_pertemuan_selfie) : null,
                    'evidenceType' => auth()->user()->pegawai_konfirmasi_pertemuan_evidence_type,
                    'meetingMethod' => auth()->user()->pegawai_konfirmasi_pertemuan_metode,
                    'checkedLabel' => 'Sudah Bertemu & Evaluasi (klik untuk batalkan)',
                    'uncheckedLabel' => 'Ambil Selfie & Tandai Sudah Bertemu',
                    'boleh' => $pegawaiBolehCentang,
                    'bolehMessage' => 'Belum bisa memberikan bukti evaluasi. Menunggu siap dinilai & tanggapan Atasan Penilai.',
                    'hrdLocked' => $pegawaiHrdLocked,
                    'hrdLockedMessage' => 'Terkunci - penilaian sudah ditanda-tangani HRD.',
                ])
            </div>

            {{-- Info / reminder --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                <h3 class="text-sm font-bold text-slate-800 mb-4">Info</h3>

                <div class="space-y-3">
                    <div class="flex items-start gap-3">
                        <span class="mt-0.5 w-7 h-7 rounded-full grid place-items-center shrink-0
                            {{ $myEvaluation ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-500' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-3.5 h-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v5m0 3h.01M10.29 3.86 1.82 18a1.5 1.5 0 0 0 1.29 2.25h17.78A1.5 1.5 0 0 0 22.18 18L13.71 3.86a1.5 1.5 0 0 0-2.42 0Z"/></svg>
                        </span>
                        <p class="text-sm text-slate-600">
                            {{ $myEvaluation ? 'Penilaian kinerja Anda sudah tersedia.' : 'Penilaian kinerja Anda belum tersedia dari pejabat.' }}
                        </p>
                    </div>

                    <div class="flex items-start gap-3">
                        <span class="mt-0.5 w-7 h-7 rounded-full grid place-items-center shrink-0
                            {{ $canRespondEvaluation ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-500' }}">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-3.5 h-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v5m0 3h.01M10.29 3.86 1.82 18a1.5 1.5 0 0 0 1.29 2.25h17.78A1.5 1.5 0 0 0 22.18 18L13.71 3.86a1.5 1.5 0 0 0-2.42 0Z"/></svg>
                        </span>
                        <p class="text-sm text-slate-600">
                            {{ $canRespondEvaluation ? 'Anda sudah dapat mengisi tanggapan atas penilaian.' : 'Tanggapan Anda menunggu penilaian dari Atasan Pejabat.' }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- Tanggapan terhadap saya --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                <h3 class="text-sm font-bold text-slate-800 mb-4">Tanggapan Terhadap Saya</h3>

                <div class="space-y-4 max-h-[420px] overflow-y-auto pr-1">
                    @forelse ($myFeedbacks as $feedback)
                        <div class="pb-4 border-b border-slate-50 last:border-b-0 last:pb-0">
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
                </div>
            </div>

            {{-- Tanggapan yang sudah saya berikan --}}
            <div id="tanggapan-saya-berikan" class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-bold text-slate-800">Sudah Saya Tanggapi</h3>
                    <span class="shrink-0 text-xs font-bold px-2.5 py-1 rounded-full bg-indigo-50 text-indigo-600">{{ $myGivenFeedbacks->count() }}</span>
                </div>

                <div class="space-y-4 max-h-[420px] overflow-y-auto pr-1">
                    @forelse ($myGivenFeedbacks as $feedback)
                        <div class="pb-4 border-b border-slate-50 last:border-b-0 last:pb-0">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="w-7 h-7 rounded-full bg-indigo-50 text-indigo-600 grid place-items-center text-[11px] font-bold shrink-0">
                                    {{ mb_substr($feedback->employee->name, 0, 1) }}
                                </span>
                                <span class="text-sm font-semibold text-slate-700">{{ $feedback->employee->name }}</span>
                            </div>
                            <p class="text-sm text-slate-500 pl-9">{{ $feedback->feedback }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-slate-400">Anda belum memberi tanggapan ke rekan kerja.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <script>
        // Fungsi reusable untuk pad tanda tangan (dipakai di form tanggapan
        // teman & form tanggapan atas penilaian).
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

                // Canvas bitmap mengikuti ukuran CSS agar koordinat sentuhan
                // tetap 1:1 dengan posisi yang terlihat di layar.
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

            // Jalankan setelah layout benar-benar selesai. Ini penting jika
            // canvas berada di modal/container yang awalnya tersembunyi.
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

                // PointerEvent menggunakan koordinat viewport yang sama dengan
                // getBoundingClientRect(), jadi tidak perlu membagi lagi dengan DPR.
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

            // Pointer Events menangani mouse, touch, dan stylus dengan
            // koordinat yang konsisten di desktop maupun mobile.
            canvas.style.touchAction = 'none';
            canvas.addEventListener('pointerdown', start);
            canvas.addEventListener('pointermove', move);
            canvas.addEventListener('pointerup', end);
            canvas.addEventListener('pointercancel', end);
            canvas.addEventListener('pointerleave', function (e) {
                // Jangan langsung memutus gambar saat jari/stylus masih
                // aktif; pointer capture akan menjaga event tetap masuk.
                if (drawing && e.pointerType === 'mouse') end(e);
            });

            const clearBtn = document.getElementById(clearBtnId);
            if (clearBtn) {
                clearBtn.addEventListener('click', function () {
                    // clearRect harus memakai koordinat bitmap, bukan koordinat
                    // CSS yang sedang terkena transform DPR.
                    ctx.save();
                    ctx.setTransform(1, 0, 0, 1, 0, 0);
                    ctx.clearRect(0, 0, canvas.width, canvas.height);
                    ctx.restore();

                    // Kembalikan transform dan konfigurasi drawing.
                    const rect = canvas.getBoundingClientRect();
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

        const feedbackPad = initSignaturePad('signature-pad', 'btn-clear-signature');
        const evalPad = initSignaturePad('eval-signature-pad', 'btn-clear-eval-signature');
        // Pad edit tanggapan ada di dalam elemen x-show (disembunyikan
        // via display:none saat awal render), jadi ukurannya baru pas
        // dihitung ulang saat form edit ditampilkan - lihat listener
        // 'eval-edit-shown' yang di-dispatch dari tombol "Edit Tanggapan".
        let evalEditPad = initSignaturePad('eval-signature-pad-edit', 'btn-clear-eval-signature-edit');
        window.addEventListener('eval-edit-shown', function () {
            evalEditPad = initSignaturePad('eval-signature-pad-edit', 'btn-clear-eval-signature-edit');
        });

        const form = document.getElementById('form-feedback');
        const signatureInput = document.getElementById('signature-input');

        if (form && feedbackPad) {
            form.addEventListener('submit', function (e) {
                if (!employeeIdInput.value) {
                    e.preventDefault();
                    alert('Silakan pilih pegawai terlebih dahulu.');
                    return;
                }

                if (!feedbackPad.hasStroke()) {
                    e.preventDefault();
                    alert('Tanda tangan wajib diisi sebelum mengirim tanggapan.');
                    return;
                }
                signatureInput.value = feedbackPad.toDataURL();
            });
        }

        const evalForm = document.getElementById('form-eval-response');
        const evalSignatureInput = document.getElementById('eval-signature-input');

        if (evalForm && evalPad) {
            evalForm.addEventListener('submit', function (e) {
                if (!evalPad.hasStroke()) {
                    e.preventDefault();
                    alert('Tanda tangan wajib diisi sebelum mengirim tanggapan.');
                    return;
                }
                evalSignatureInput.value = evalPad.toDataURL();
            });
        }

        const evalEditForm = document.getElementById('form-eval-response-edit');
        const evalEditSignatureInput = document.getElementById('eval-signature-input-edit');

        if (evalEditForm) {
            evalEditForm.addEventListener('submit', function (e) {
                if (!evalEditPad || !evalEditPad.hasStroke()) {
                    e.preventDefault();
                    alert('Tanda tangan wajib diisi sebelum menyimpan perubahan.');
                    return;
                }
                evalEditSignatureInput.value = evalEditPad.toDataURL();
            });
        }
    </script>

    <script>
        // Fitur pencarian & filter unit kerja untuk memilih pegawai
        const employeeSearch = document.getElementById('employee-search');
        const employeeList = document.getElementById('employee-list');
        const employeeOptions = Array.from(employeeList.querySelectorAll('.employee-option'));
        const employeeIdInput = document.getElementById('employee-id-input');
        const employeeNoResult = document.getElementById('employee-no-result');
        const unitFilterSelect = document.getElementById('employee-unit-filter');

        function applyFilters() {
            const query = employeeSearch.value.trim().toLowerCase();
            const activeUnit = unitFilterSelect.value;
            let visibleCount = 0;

            employeeOptions.forEach(function (opt) {
                const matchesSearch = !query || opt.dataset.name.includes(query);
                const matchesUnit = activeUnit === 'all' || opt.dataset.unit === activeUnit;
                const visible = matchesSearch && matchesUnit;

                opt.classList.toggle('hidden', !visible);
                if (visible) visibleCount++;
            });

            employeeNoResult.style.display = visibleCount === 0 ? 'block' : 'none';
        }

        employeeSearch.addEventListener('input', applyFilters);
        unitFilterSelect.addEventListener('change', applyFilters);

        employeeOptions.forEach(function (opt) {
            opt.addEventListener('click', function () {
                employeeOptions.forEach(o => o.classList.remove('employee-option-selected', '!bg-blue-600', '!text-white'));
                opt.classList.add('employee-option-selected', '!bg-blue-600', '!text-white');
                employeeIdInput.value = opt.dataset.id;
            });
        });
    </script>

    <script>
        // Toggle tampil/sembunyi rincian tiap poin penilaian yang diterima
        // pegawai, supaya tidak langsung memenuhi layar saat dashboard dibuka.
        function toggleDetailPenilaian() {
            const detail = document.getElementById('detail-penilaian');
            const btn = document.getElementById('btn-toggle-detail-penilaian');

            if (!detail || !btn) {
                return;
            }

            const isHidden = detail.style.display === 'none';

            detail.style.display = isHidden ? 'block' : 'none';
            const label = btn.querySelector('span');
            if (label) {
                label.textContent = isHidden ? 'Sembunyikan Detail Penilaian' : 'Lihat Detail Penilaian';
            }
        }
    </script>

    <script>
        // AJAX polling: cek berkala apakah status penilaian / tanggapan
        // atasan berubah (misal pejabat baru saja memberi nilai), lalu
        // muat ulang dashboard secara otomatis TANPA pegawai perlu klik
        // refresh manual. Lihat EmployeeController::statusVersion().
        (function () {
            const POLL_INTERVAL_MS = 5000;
            const STATUS_URL = '{{ route('employee.dashboard.status-version') }}';
            let currentVersion = null;

            function isUserTyping() {
                // Jangan reload di tengah-tengah pegawai mengisi tanggapan
                // atau tanda tangan - tunggu sampai dia selesai/pindah fokus.
                const active = document.activeElement;
                if (!active) return false;
                const tag = active.tagName;
                return tag === 'TEXTAREA' || tag === 'INPUT' || tag === 'CANVAS';
            }

            function poll() {
                if (document.hidden) return;

                // cache: 'no-store' + query anti-cache karena browser bisa
                // saja menyajikan hasil fetch() sebelumnya dari cache HTTP
                // biasa - bikin polling ini kelihatan "jalan" (200 OK di
                // Network tab) padahal server tidak pernah dicek ulang.
                fetch(STATUS_URL + '?t=' + Date.now(), {
                    // 'ngrok-skip-browser-warning' wajib kalau diakses
                    // lewat ngrok free tier - tanpa ini, ngrok balikin
                    // halaman HTML interstitial (bukan JSON) meski
                    // statusnya tetap 200 OK, jadi polling kelihatan
                    // "jalan" tapi gak pernah dapat data asli.
                    headers: {
                        'Accept': 'application/json',
                        'ngrok-skip-browser-warning': 'true',
                    },
                    cache: 'no-store',
                })
                    .then(res => res.ok ? res.json() : null)
                    .then(data => {
                        if (!data) return;

                        // Poll pertama cuma merekam titik awal, tidak
                        // membandingkan apa pun - menghindari perlu
                        // menghitung ulang hash yang identik di Blade
                        // (rawan salah format tanggal).
                        if (currentVersion === null) {
                            currentVersion = data.version;
                            return;
                        }

                        if (data.version !== currentVersion) {
                            if (isUserTyping()) {
                                // Coba lagi di siklus polling berikutnya,
                                // jangan ganggu pegawai yang sedang mengetik.
                                return;
                            }
                            window.showReloadOverlay();
                        }
                    })
                    .catch((err) => {
                        // Dicetak ke console (bukan didiamkan total) supaya
                        // masalah kayak respons non-JSON (mis. halaman
                        // interstitial ngrok, atau error server) kelihatan
                        // saat debug, bukan gagal senyap.
                        console.error('status-version polling error:', err);
                    });
            }

            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'visible') poll();
            });

            setInterval(poll, POLL_INTERVAL_MS);
        })();
    </script>

</x-dashboard-layout>

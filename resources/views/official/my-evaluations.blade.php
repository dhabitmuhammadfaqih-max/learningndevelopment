<x-dashboard-layout title="Nilai Saya">

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

    @if ($errors->any())
        <div class="mb-6 rounded-2xl bg-red-50 text-red-700 px-5 py-4 text-sm">
            <ul class="list-disc pl-5 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Intro banner --}}
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-blue-600 to-indigo-500 px-6 sm:px-10 py-8 sm:py-10 mb-8">
        <div class="absolute -right-10 -top-10 w-48 h-48 rounded-full bg-white/10"></div>
        <div class="absolute right-24 bottom-[-3rem] w-32 h-32 rounded-full bg-white/10"></div>
        <div class="relative max-w-xl">
            <h2 class="text-2xl sm:text-3xl font-extrabold text-white">Nilai Saya</h2>
            <p class="mt-3 text-blue-50/90 text-sm sm:text-base leading-relaxed">
                Berikut seluruh hasil penilaian yang telah diberikan atasan kepada
                <strong class="text-white">{{ auth()->user()->name }}</strong>, lengkap per komponen penilaian.
            </p>
        </div>
    </div>

    {{-- Checklist Pertemuan & Evaluasi --}}
    @php
        // Baru boleh MULAI dicentang setelah Atasan Penilai
        // (OfficialSupervisorFeedback) sudah mengisi tanggapannya untuk
        // tahun berjalan - lihat User::checklistPertemuanPejabatBolehDiisi().
        // Membatalkan checklist yang sudah tercentang tetap boleh kapan
        // saja. Setiap kali DICENTANG wajib disertai selfie langsung dari
        // kamera perangkat.
        $pejabatSudahCentang = auth()->user()->pejabatSudahKonfirmasiPertemuan();
        $pejabatBolehCentang = auth()->user()->checklistPertemuanPejabatBolehDiisi();
        // Terkunci begitu HRD sudah tanda tangan penilaian ini - lihat
        // User::hrdSudahMenandatanganiPenilaianPejabat().
        $pejabatHrdLocked = auth()->user()->hrdSudahMenandatanganiPenilaianPejabat();
        // Baris permintaan/kode yang masih terbuka untuk penilaian diri
        // sendiri - lihat App\Models\MeetingCode::openFor().
        $pejabatMeetingRow = (! $pejabatSudahCentang && $pejabatBolehCentang)
            ? \App\Models\MeetingCode::openFor(auth()->id(), \App\Models\MeetingCode::CONTEXT_PEJABAT, \App\Support\ActivePeriod::year())
            : null;
    @endphp
    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 sm:p-7 mb-8">
        <h3 class="text-lg font-bold text-slate-800 mb-2">Checklist Pertemuan &amp; Evaluasi</h3>
        <p class="text-sm text-slate-400 mb-4">
            Centang setelah Anda bertemu dan mendiskusikan hasil evaluasi dengan Atasan Anda. Untuk pertemuan offline, Penilai meminta kode ke HRD, lalu kalian berdua menekan "Sudah Bertemu".
            HRD tidak dapat mencetak PDF penilaian Anda sebelum checklist ini dicentang.
        </p>

        @include('partials.checklist-pertemuan-toggle', [
            'action' => route('official.checklist-pertemuan-saya.toggle'),
            'mode' => 'subject',
            'meetingRow' => $pejabatMeetingRow,
            'counterpartLabel' => 'Atasan Anda',
            'checked' => $pejabatSudahCentang,
            'checkedAt' => auth()->user()->pejabat_konfirmasi_pertemuan_at,
            'selfieUrl' => auth()->user()->pejabat_konfirmasi_pertemuan_selfie ? Storage::disk('public')->url(auth()->user()->pejabat_konfirmasi_pertemuan_selfie) : null,
            'evidenceType' => auth()->user()->pejabat_konfirmasi_pertemuan_evidence_type,
            'meetingMethod' => auth()->user()->pejabat_konfirmasi_pertemuan_metode,
            'meetingCode' => auth()->user()->pejabat_konfirmasi_pertemuan_kode,
            'checkedLabel' => 'Sudah Bertemu & Evaluasi (klik untuk batalkan)',
            'uncheckedLabel' => 'Tandai Sudah Bertemu & Evaluasi',
            'boleh' => $pejabatBolehCentang,
            'bolehMessage' => 'Belum bisa memberikan bukti evaluasi. Menunggu siap dinilai & tanggapan Atasan Penilai.',
            'hrdLocked' => $pejabatHrdLocked,
            'hrdLockedMessage' => 'Terkunci - penilaian sudah ditanda-tangani HRD.',
        ])
    </div>

    @if ($myOfficialEvaluations->isEmpty())

        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 sm:p-7 text-center">
            <div class="w-14 h-14 rounded-2xl bg-slate-50 text-slate-300 grid place-items-center mx-auto mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-7 h-7"><path stroke-linecap="round" stroke-linejoin="round" d="m12 3 2.6 5.9 6.4.6-4.8 4.3 1.4 6.3L12 17l-5.6 3.1 1.4-6.3-4.8-4.3 6.4-.6L12 3Z"/></svg>
            </div>
            <p class="text-sm text-slate-400">Belum ada penilaian yang diterima.</p>
        </div>

    @else

        <div class="space-y-6">
            @foreach ($myOfficialEvaluations as $i => $evaluation)
                @php
                    $percent = max(0, min(100, (float) $evaluation->score));
                    $detailId = 'detail-nilai-' . $evaluation->id;
                    $toggleBtnId = 'btn-toggle-' . $evaluation->id;
                @endphp

                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 sm:p-7">

                    {{-- Header: pemberi nilai + skor --}}
                    <div class="flex flex-col sm:flex-row items-center sm:items-start gap-6">
                        <div class="relative w-32 h-32 shrink-0 rounded-full"
                             style="background: conic-gradient(#2563eb {{ $percent }}%, #e5edf7 {{ $percent }}% 100%)">
                            <div class="absolute inset-2 bg-white rounded-full flex flex-col items-center justify-center">
                                <span class="text-2xl font-extrabold text-blue-600">{{ $evaluation->score }}</span>
                                <span class="text-[11px] text-slate-400">dari 100</span>
                            </div>
                        </div>

                        <div class="flex-1 w-full">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                <div>
                                    <p class="text-lg font-bold text-slate-800">Penilaian dari {{ $evaluation->supervisor->name ?? '-' }}</p>
                                    <p class="text-xs text-slate-400 mt-0.5">{{ $evaluation->created_at->translatedFormat('d M Y H:i') }}</p>
                                </div>
                                <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-full bg-indigo-50 text-indigo-600 w-fit">
                                    Indeks {{ \App\Models\OfficialEvaluation::scaleIndex((float) $evaluation->score) }}
                                </span>
                            </div>

                            <div class="mt-4 space-y-2.5 text-sm">
                                <div class="flex justify-between border-b border-slate-50 pb-2">
                                    <span class="text-slate-400">Rekomendasi</span>
                                    <span class="font-semibold text-slate-700">{{ $evaluation->recommendationLabel() }}</span>
                                </div>
                                @if ($evaluation->kenaikan_gaji_amount)
                                    <div class="flex justify-between border-b border-slate-50 pb-2">
                                        <span class="text-slate-400">Nominal Kenaikan Gaji</span>
                                        <span class="font-semibold text-slate-700">Rp {{ number_format($evaluation->kenaikan_gaji_amount, 0, ',', '.') }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <button type="button"
                            id="{{ $toggleBtnId }}"
                            onclick="toggleDetailNilai('{{ $detailId }}', '{{ $toggleBtnId }}')"
                            class="mt-5 inline-flex items-center gap-1.5 text-sm font-semibold text-blue-600 hover:text-blue-700">
                        <span>Lihat Detail Penilaian</span>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6"/></svg>
                    </button>

                    <div class="mt-4" id="{{ $detailId }}" style="display:none;">
                        <div class="overflow-x-auto rounded-xl border border-slate-100">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="bg-slate-50 text-slate-500 text-xs uppercase tracking-wide">
                                        <th class="text-left px-4 py-3 font-semibold">Komponen</th>
                                        <th class="text-left px-4 py-3 font-semibold">Bobot</th>
                                        <th class="text-left px-4 py-3 font-semibold">Nilai</th>
                                        <th class="text-left px-4 py-3 font-semibold">Kontribusi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50">
                                    @foreach (\App\Models\OfficialEvaluation::WEIGHTS as $key => $bobot)
                                        <tr>
                                            <td class="px-4 py-3 align-top">
                                                <p class="font-medium text-slate-700">{{ \App\Models\OfficialEvaluation::LABELS[$key] }}</p>
                                                <p class="text-xs text-slate-400 mt-0.5">{{ \App\Models\OfficialEvaluation::DESCRIPTIONS[$key] }}</p>
                                            </td>
                                            <td class="px-4 py-3 align-top text-slate-600 whitespace-nowrap">{{ rtrim(rtrim(number_format($bobot, 1), '0'), '.') }}%</td>
                                            <td class="px-4 py-3 align-top font-semibold text-slate-700">{{ $evaluation->$key }}</td>
                                            <td class="px-4 py-3 align-top text-slate-500">{{ number_format($evaluation->$key * ($bobot / 100), 0) }} poin</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="bg-slate-50">
                                        <td colspan="3" class="px-4 py-3 font-bold text-slate-700">Total Nilai Akhir</td>
                                        <td class="px-4 py-3 font-bold text-blue-600">{{ $evaluation->score }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    {{-- Tanggapan Atasan --}}
                    <div class="mt-6 pt-6 border-t border-slate-100">
                        <h4 class="text-sm font-bold text-slate-800 mb-2">Tanggapan Atasan</h4>
                        <p class="text-sm text-slate-600 bg-slate-50 rounded-xl px-4 py-3">{{ $evaluation->feedback }}</p>

                        @if ($evaluation->signature)
                            <p class="text-xs font-semibold text-slate-500 mt-4 mb-2">Tanda Tangan Atasan</p>
                            <img src="{{ Storage::disk('public')->url($evaluation->signature) }}"
                                 class="w-40 h-[70px] object-contain border border-slate-100 rounded-lg bg-slate-50">
                        @endif
                    </div>

                    {{-- Tanggapan Atasan Penilai --}}
                    @php
                        $atasanFeedbackTahunIni = $myOfficialSupervisorFeedbacks->get($evaluation->tahun);
                    @endphp
                    <div class="mt-6 pt-6 border-t border-slate-100">
                        <h4 class="text-sm font-bold text-slate-800 mb-3">Tanggapan Atasan Penilai</h4>

                        @if ($atasanFeedbackTahunIni)
                            <div class="space-y-2.5 text-sm">
                                <div class="flex justify-between border-b border-slate-50 pb-2">
                                    <span class="text-slate-400">Atasan Penilai</span>
                                    <span class="font-semibold text-slate-700">{{ $atasanFeedbackTahunIni->supervisor->name ?? '-' }}</span>
                                </div>
                                <div class="flex justify-between border-b border-slate-50 pb-2">
                                    <span class="text-slate-400">Rekomendasi</span>
                                    <span class="font-semibold text-slate-700">{{ $atasanFeedbackTahunIni->recommendationLabel() }}</span>
                                </div>
                                @if ($atasanFeedbackTahunIni->kenaikan_gaji_amount)
                                    <div class="flex justify-between border-b border-slate-50 pb-2">
                                        <span class="text-slate-400">Nominal Kenaikan Gaji</span>
                                        <span class="font-semibold text-slate-700">Rp {{ number_format($atasanFeedbackTahunIni->kenaikan_gaji_amount, 0, ',', '.') }}</span>
                                    </div>
                                @endif
                                @if ($atasanFeedbackTahunIni->feedback)
                                    <p class="text-slate-500 pt-1">{{ $atasanFeedbackTahunIni->feedback }}</p>
                                @endif
                            </div>

                            @if ($atasanFeedbackTahunIni->signature)
                                <img src="{{ Storage::disk('public')->url($atasanFeedbackTahunIni->signature) }}"
                                     class="w-40 h-[70px] object-contain border border-slate-100 rounded-lg bg-slate-50 mt-3">
                            @endif
                        @else
                            <p class="text-sm text-slate-400">Tanggapan dari Atasan Penilai belum tersedia.</p>
                        @endif
                    </div>

                    {{-- Tanggapan saya --}}
                    <div class="mt-6 pt-6 border-t border-slate-100" x-data="{ editing: false }">
                        <div class="flex items-center justify-between mb-3">
                            <h4 class="text-sm font-bold text-slate-800">Tanggapan Saya Atas Penilaian Ini</h4>
                            @if ($evaluation->employee_response)
                                <button type="button"
                                        x-show="! editing"
                                        x-on:click="editing = true; $nextTick(() => window.dispatchEvent(new CustomEvent('eval-edit-shown-{{ $evaluation->id }}')))"
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

                        @if ($evaluation->employee_response)
                            <div x-show="! editing">
                                <p class="text-sm text-slate-600 bg-slate-50 rounded-xl px-4 py-3">{{ $evaluation->employee_response }}</p>
                                <p class="text-xs text-slate-400 mt-2">
                                    Dikirim {{ $evaluation->employee_response_at?->translatedFormat('d M Y H:i') }}
                                </p>

                                @if ($evaluation->employee_signature)
                                    <img src="{{ Storage::disk('public')->url($evaluation->employee_signature) }}"
                                         class="w-40 h-[70px] object-contain border border-slate-100 rounded-lg bg-slate-50 mt-2">
                                @endif
                            </div>

                            <div x-show="editing" x-cloak>
                                @php $evalEditFormId = 'form-eval-response-edit-' . $evaluation->id; @endphp
                                <form method="POST" action="{{ route('official.evaluation.respond', $evaluation->id) }}" id="{{ $evalEditFormId }}" class="space-y-4" data-autosave>
                                    @csrf

                                    <textarea
                                        name="employee_response"
                                        placeholder="Tulis tanggapan Anda atas penilaian ini..."
                                        minlength="5"
                                        required
                                        class="w-full min-h-[100px] rounded-xl border-slate-200 text-sm focus:border-blue-500 focus:ring-blue-500"
                                    >{{ old('employee_response', $evaluation->employee_response) }}</textarea>

                                    <div>
                                        <label class="block text-xs font-semibold text-slate-500 mb-2">Tanda Tangan</label>
                                        <img src="{{ auth()->user()->signature_url }}" alt="Tanda tangan Anda"
                                             class="rounded-xl border border-slate-200 bg-white block w-full max-w-[400px] h-[150px] object-contain">
                                        <p class="text-xs text-slate-400 mt-2">
                                            Tanda tangan akun Anda akan otomatis dipakai untuk tanggapan ini.
                                        </p>
                                    </div>

                                    <button type="submit"
                                            class="inline-flex items-center justify-center rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-5 py-2.5 transition">
                                        Simpan Perubahan
                                    </button>
                                </form>
                            </div>
                        @else
                            @php
                                $evalFormId = 'form-eval-response-' . $evaluation->id;
                                $canRespondEvaluation = \App\Models\OfficialSupervisorFeedback::where('official_id', auth()->id())
                                    ->where('tahun', $evaluation->tahun)
                                    ->exists();
                            @endphp

                            @if (! $canRespondEvaluation)
                                <div class="rounded-2xl bg-amber-50 text-amber-700 px-4 py-3 text-sm font-medium">
                                    Tanggapan belum bisa diberikan karena Atasan Penilai belum menyelesaikan tanggapannya untuk penilaian ini.
                                </div>
                            @else
                                <form method="POST" action="{{ route('official.evaluation.respond', $evaluation->id) }}" id="{{ $evalFormId }}" class="space-y-4" data-autosave>
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
                                        <img src="{{ auth()->user()->signature_url }}" alt="Tanda tangan Anda"
                                             class="rounded-xl border border-slate-200 bg-white block w-full max-w-[400px] h-[150px] object-contain">
                                        <p class="text-xs text-slate-400 mt-2">
                                            Tanda tangan akun Anda akan otomatis dipakai untuk tanggapan ini.
                                        </p>
                                    </div>

                                    <button type="submit"
                                            class="inline-flex items-center justify-center rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-5 py-2.5 transition">
                                        Kirim Tanggapan
                                    </button>
                                </form>
                            @endif
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

    @endif

    <script>
        function toggleDetailNilai(detailId, btnId) {
            const detail = document.getElementById(detailId);
            const btn = document.getElementById(btnId);
            if (!detail || !btn) return;

            const isHidden = detail.style.display === 'none';
            detail.style.display = isHidden ? 'block' : 'none';

            const label = btn.querySelector('span');
            if (label) {
                label.textContent = isHidden ? 'Sembunyikan Detail Penilaian' : 'Lihat Detail Penilaian';
            }
        }
    </script>

</x-dashboard-layout>
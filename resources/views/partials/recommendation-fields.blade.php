@php
    $selectedRecommendations = $selectedRecommendations ?? [];
    $kenaikanGajiValue = $kenaikanGajiValue ?? '';
    $promosiKeteranganValue = $promosiKeteranganValue ?? '';
    $demosiKeteranganValue = $demosiKeteranganValue ?? '';
    $recommendations = $recommendations ?? [];
    $recommendationDescriptions = $recommendationDescriptions ?? [];
    $subjectLabel = $subjectLabel ?? 'pegawai';

    // "Kontrak Dagsap ke Tetap" cuma masuk akal kalau status kontrak
    // orang yang dinilai SAAT INI memang sudah "Kontrak Dagsap" (status
    // Kontrak + vendor Dagsap - lihat User::EMPLOYMENT_STATUSES,
    // User::VENDORS, User::isEligibleForDagsapTetap()). Kalau statusnya
    // masih PHL atau Kontrak OS (vendor "OS ABM"/"OS Bantul"/"OS Cakra"),
    // rekomendasi ini TIDAK boleh diajukan - harus lewat "PHL ke Kontrak"
    // / "Kontrak OS ke Kontrak Dagsap" dulu. Checkbox-nya dinonaktifkan
    // di sini (client-side) dan tetap ditolak di server-side (lihat
    // masing-masing validate*Input() di controller) sebagai jaring
    // pengaman kalau ada yang mengakali atribut disabled.
    $subjectStatus = $subjectStatus ?? null;
    $subjectVendor = $subjectVendor ?? null;
    $eligibleForDagsapTetap = $subjectStatus === 'Kontrak' && $subjectVendor === 'Dagsap';
@endphp

<div class="mt-5" x-data="{
        selected: {{ Illuminate\Support\Js::from(array_values($selectedRecommendations)) }},
        kenaikanGaji: {{ Illuminate\Support\Js::from((string) $kenaikanGajiValue) }},
        promosiKeterangan: {{ Illuminate\Support\Js::from((string) $promosiKeteranganValue) }},
        demosiKeterangan: {{ Illuminate\Support\Js::from((string) $demosiKeteranganValue) }},
        eligibleDagsapTetap: {{ Illuminate\Support\Js::from($eligibleForDagsapTetap) }},
    }"
    x-effect="if (!selected.includes('kenaikan_gaji')) kenaikanGaji = ''; if (!selected.includes('promosi')) promosiKeterangan = ''; if (!selected.includes('demosi')) demosiKeterangan = ''"
    x-init="if (!eligibleDagsapTetap) selected = selected.filter(v => v !== 'kontrak_dagsap_ke_tetap')"
    @change="if ($event.target.value === 'promosi' && $event.target.checked) { selected = selected.filter(v => v !== 'demosi'); } if ($event.target.value === 'demosi' && $event.target.checked) { selected = selected.filter(v => v !== 'promosi'); } if ($event.target.value === 'kontrak_dagsap_ke_tetap' && $event.target.checked && !eligibleDagsapTetap) { selected = selected.filter(v => v !== 'kontrak_dagsap_ke_tetap'); $event.target.checked = false; }"
>
    {{-- Promosi dan Demosi tidak boleh dipilih bersamaan (rekomendasi yang saling bertolak belakang) --}}
    <label class="block text-sm font-bold text-slate-700 mb-1.5">Rekomendasi (bisa pilih lebih dari satu)</label>

    <div class="grid grid-cols-1 gap-2.5">
        @foreach ($recommendations as $value => $recLabel)
            @php
                $isDagsapTetapOption = $value === 'kontrak_dagsap_ke_tetap';
                $optionDisabled = $isDagsapTetapOption && ! $eligibleForDagsapTetap;
            @endphp
            <label
                class="flex items-start gap-2.5 rounded-xl border px-3.5 py-3 transition"
                :class="{{ $optionDisabled ? 'true' : 'false' }} ? 'opacity-50 cursor-not-allowed border-slate-200 bg-slate-50' : (selected.includes('{{ $value }}') ? 'border-blue-500 bg-blue-50/60 ring-1 ring-blue-500 cursor-pointer' : 'border-slate-200 hover:border-slate-300 hover:bg-slate-50 cursor-pointer')"
            >
                <input
                    type="checkbox"
                    name="recommendation[]"
                    value="{{ $value }}"
                    x-model="selected"
                    @if ($optionDisabled) disabled @endif
                    class="mt-0.5 w-4 h-4 rounded shrink-0 {{ $optionDisabled ? 'border-slate-300 text-slate-400 focus:ring-slate-300 disabled:cursor-not-allowed disabled:opacity-70' : 'border-slate-300 text-blue-600 focus:ring-blue-500' }}"
                >
                <span class="flex flex-col gap-0.5">
                    <span class="text-xs font-semibold {{ $optionDisabled ? 'text-slate-400' : 'text-slate-800' }}">{{ $recLabel }}</span>
                    <span class="text-[11px] text-slate-400 leading-snug">{{ $recommendationDescriptions[$value] ?? '' }}</span>
                    @if ($isDagsapTetapOption && $optionDisabled)
                        <span class="text-[11px] text-amber-700 font-semibold leading-snug mt-0.5">
                            &#9888; Tidak tersedia - status {{ $subjectLabel }} ini saat ini
                            {{ $subjectStatus ? "{$subjectStatus}" . ($subjectVendor ? " ({$subjectVendor})" : '') : 'belum diketahui' }},
                            bukan Kontrak Dagsap. Rekomendasi ini hanya bisa diajukan kalau status {{ $subjectLabel }}
                            sudah Kontrak Dagsap (bukan PHL atau Kontrak OS).
                            @if (in_array($value, $selectedRecommendations, true))
                                Rekomendasi ini sudah tersimpan sebelumnya, tapi otomatis tidak akan ikut disimpan lagi
                                sampai status {{ $subjectLabel }} berubah menjadi Kontrak Dagsap.
                            @endif
                        </span>
                    @endif
                </span>
            </label>
        @endforeach
    </div>

    @error('recommendation')
        <p class="text-xs text-red-600 mt-1.5">{{ $message }}</p>
    @enderror
    @error('recommendation.*')
        <p class="text-xs text-red-600 mt-1.5">{{ $message }}</p>
    @enderror

    <div class="mt-4 max-w-[280px]" x-show="selected.includes('kenaikan_gaji')" x-cloak>
        <label class="block text-sm font-bold text-slate-700 mb-1.5">Nominal Kenaikan Gaji</label>
        <input
            type="text"
            inputmode="numeric"
            pattern="[0-9]*"
            name="kenaikan_gaji_amount"
            min="1"
            x-model="kenaikanGaji"
            @keydown="if (!/^[0-9]$/.test($event.key) && !['Backspace','Delete','ArrowLeft','ArrowRight','Tab','Home','End'].includes($event.key) && !$event.metaKey && !$event.ctrlKey) $event.preventDefault()"
            @input="kenaikanGaji = kenaikanGaji.replace(/[^0-9]/g, '')"
            @paste="setTimeout(() => kenaikanGaji = kenaikanGaji.replace(/[^0-9]/g, ''))"
            class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none"
        >
        @error('kenaikan_gaji_amount')
            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div class="mt-4 max-w-md" x-show="selected.includes('promosi')" x-cloak>
        <label class="block text-sm font-bold text-slate-700 mb-1.5">Jabatan yang Direkomendasikan</label>
        <input
            type="text"
            name="promosi_keterangan"
            x-model="promosiKeterangan"
            maxlength="255"
            placeholder="Contoh: Kepala Divisi Operasional"
            class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none"
        >
        <p class="text-xs text-slate-400 mt-1">
            Jelaskan jabatan/posisi tujuan promosi {{ $subjectLabel }} ini.
        </p>
        @error('promosi_keterangan')
            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
        @enderror
    </div>

    <div class="mt-4 max-w-md" x-show="selected.includes('demosi')" x-cloak>
        <label class="block text-sm font-bold text-slate-700 mb-1.5">Jabatan yang Direkomendasikan</label>
        <input
            type="text"
            name="demosi_keterangan"
            x-model="demosiKeterangan"
            maxlength="255"
            placeholder="Contoh: Koordinator Operasional"
            class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none"
        >
        <p class="text-xs text-slate-400 mt-1">
            Jelaskan jabatan/posisi tujuan demosi {{ $subjectLabel }} ini.
        </p>
        @error('demosi_keterangan')
            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
        @enderror
    </div>
</div>

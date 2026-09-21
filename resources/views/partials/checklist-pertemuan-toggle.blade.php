{{--
    Partial checklist "sudah bertemu & evaluasi".

    MENGGANTIKAN partials/checklist-selfie-toggle.blade.php. Bukti
    metode OFFLINE yang dulu berupa selfie kamera sekarang berupa KODE
    PERTEMUAN (lihat App\Models\MeetingCode):

    - Pihak PENILAI/ATASAN ($mode = 'issuer') menekan "Buat Kode
      Pertemuan". Kodenya tampil di layar dia saja, berlaku
      MeetingCode::VALID_MINUTES menit.
    - Pihak PEGAWAI/PEJABAT ($mode = 'subject') mengetik kode itu di
      perangkatnya sendiri. Begitu cocok, checklist KEDUA pihak
      tercentang sekaligus & HRD langsung diberi tahu - tidak ada
      lagi dua bukti terpisah seperti waktu masih pakai selfie.

    Metode ONLINE (upload bukti Zoom/Telpon/Chat) TIDAK berubah sama
    sekali dan masih tersedia untuk kedua mode.

    Props:
    - $action        (string)  route submit toggle checklist
    - $mode          (string)  'subject' (yang dinilai, memasukkan kode)
                               | 'issuer' (yang menilai, membuat kode)
    - $checked       (bool)
    - $checkedAt     (Carbon|null)
    - $selfieUrl     (string|null) URL file bukti - hanya terisi untuk
      metode Online (upload) & data LAMA bermetode selfie.
    - $evidenceType  (string|null) 'upload' (Online) | 'kode' (Offline)
      | 'selfie' (Offline, data lama) | null (data lama)
    - $meetingMethod (string|null) 'zoom' | 'telpon' | 'chat' - hanya
      relevan saat $evidenceType 'upload'.
    - $meetingCode   (string|null) kode yang dipakai - hanya relevan
      saat $evidenceType 'kode'.
    - $codeAction    (string|null) route "buat kode", WAJIB saat
      $mode = 'issuer'.
    - $activeCode    (App\Models\MeetingCode|null) kode yang masih
      berlaku, supaya tetap tampil setelah halaman di-refresh. Hanya
      dipakai saat $mode = 'issuer'.
    - $checkedLabel / $uncheckedLabel (string) label tombol
    - $boleh / $bolehMessage       — sama seperti partial lama
    - $hrdLocked / $hrdLockedMessage — sama seperti partial lama
--}}
@php
    $mode = $mode ?? 'subject';
    $boleh = $boleh ?? true;
    $bolehMessage = $bolehMessage ?? 'Belum bisa memberikan bukti evaluasi.';
    $hrdLocked = $hrdLocked ?? false;
    $hrdLockedMessage = $hrdLockedMessage ?? 'Terkunci - penilaian sudah ditanda-tangani HRD.';
    $codeAction = $codeAction ?? null;
    $activeCode = $activeCode ?? null;
    $meetingCode = $meetingCode ?? null;
@endphp
@if ($hrdLocked)
    <div class="relative isolate z-10 w-full sm:max-w-xs rounded-xl bg-slate-50 border border-slate-200 px-4 py-2.5 text-xs text-slate-500">
        {{ $hrdLockedMessage }}
    </div>
    @if($checkedAt)
        <p class="text-xs text-slate-400 mt-2">
            Dicentang {{ $checkedAt->translatedFormat('d M Y H:i') }}
        </p>
    @endif
    {{-- Bukti (kode maupun foto) SENGAJA tidak ditampilkan lagi begitu
         HRD sudah tanda tangan - lihat catatan $hrdLocked di atas. --}}
@elseif (! $checked && ! $boleh)
    <div class="relative isolate z-10 w-full sm:max-w-xs break-words rounded-xl bg-slate-50 border border-slate-200 px-4 py-2.5 text-xs text-slate-500">
        {{ $bolehMessage }}
    </div>
    @if($checkedAt)
        <p class="text-xs text-slate-400 mt-2">
            Dicentang {{ $checkedAt->translatedFormat('d M Y H:i') }}
        </p>
    @endif
@else
{{--
    Catatan soal `method` di bawah: setelah tombol "Buat Kode" ditekan
    (atau kode salah diketik), halaman dirender ulang oleh redirect
    back() dan seluruh state Alpine hilang. Tanpa inisialisasi itu,
    panel Offline menutup sendiri dan kode yang barusan dibuat tidak
    pernah kelihatan oleh penilai.
--}}
<div
    class="relative isolate z-10 w-full sm:max-w-xs rounded-2xl bg-white border border-slate-200 p-3"
    x-data="{
        rootEl: null,
        method: {!! ($mode === 'issuer' && $activeCode && $activeCode->masihBerlaku()) || ($mode === 'subject' && $errors->has('meeting_code')) ? "'kode'" : 'null' !!},
        meetingMethod: null,
        uploadFile: null,
        uploadPreview: null,
        // Sisa detik kode yang masih berlaku (mode issuer). Dihitung
        // ulang di browser tiap detik supaya penilai tahu kapan harus
        // generate ulang, tanpa perlu refresh halaman.
        sisaDetik: {{ $mode === 'issuer' && $activeCode ? $activeCode->sisaDetik() : 0 }},
        get sisaLabel() {
            const m = Math.floor(this.sisaDetik / 60);
            const s = this.sisaDetik % 60;
            return m + ':' + String(s).padStart(2, '0');
        },
        startCountdown() {
            if (this.sisaDetik <= 0) { return; }
            this._tick = setInterval(() => {
                this.sisaDetik = Math.max(0, this.sisaDetik - 1);
                if (this.sisaDetik === 0) { clearInterval(this._tick); }
            }, 1000);
        },
        chooseUpload() { this.method = 'upload'; },
        chooseOffline() { this.method = 'kode'; },
        resetMethod() {
            this.method = null;
            this.meetingMethod = null;
            this.uploadFile = null;
            this.uploadPreview = null;
            // Sengaja querySelector lewat rootEl, bukan $refs: input
            // file-nya berada di dalam <template x-if>, dan pola
            // rootEl inilah yang sudah terbukti jalan di partial
            // sebelumnya untuk elemen di dalam template.
            const fileInput = this.rootEl.querySelector('[x-ref=fileInput]');
            if (fileInput) { fileInput.value = ''; }
        },
        onFileChange(event) {
            const file = event.target.files && event.target.files[0] ? event.target.files[0] : null;
            this.uploadFile = file;
            this.uploadPreview = file ? URL.createObjectURL(file) : null;
        },
        // Kode selalu huruf besar & tanpa karakter selain huruf/angka -
        // orang sering mengetik dengan spasi atau huruf kecil karena
        // kodenya dibacakan lisan. Server juga menormalkan hal yang
        // sama (lihat MeetingCode::redeem()), ini cuma supaya yang
        // terlihat di layar sudah rapi sejak awal.
        normalizeCode(event) {
            event.target.value = event.target.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
        }
    }"
    x-init="rootEl = $el; startCountdown()"
>
    {{--
        `relative isolate z-10` + `bg-white border` di atas SENGAJA
        ditambahkan: widget ini duduk di kolom kanan kartu pegawai/
        pejabat yang tingginya ikut membesar begitu kotak kode (dengan
        countdown & tombol "Buat Kode Baru") sedang tampil. Tanpa latar
        solid & isolasi stacking-context sendiri, konten kolom kiri
        (nama, badge status) bisa "keteban" transparan di balik widget
        ini saat kartu jadi lebih tinggi dari kolom kiri - lihat
        laporan bug tampilan checklist offline (kode) yang numpuk.
    --}}
    @if($checked)
        {{-- Sudah dicentang: submit langsung membatalkan, tanpa bukti baru --}}
        <form method="POST" action="{{ $action }}">
            @csrf
            <button type="submit"
                    class="w-full inline-flex items-center justify-center gap-2 rounded-xl text-sm font-semibold px-4 py-2.5 transition bg-emerald-50 text-emerald-600 hover:bg-emerald-100">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/></svg>
                {{ $checkedLabel }}
            </button>
        </form>
    @else
        {{-- Langkah 1: pilih metode --}}
        <template x-if="! method">
            <div>
                <p class="text-xs font-semibold text-slate-500 mb-2">Disampaikan Secara Langsung</p>
                <div class="grid grid-cols-2 gap-2">
                    <button type="button"
                            x-on:click="chooseOffline()"
                            class="flex flex-col items-center justify-center gap-1 rounded-xl text-sm font-semibold px-3 py-3 transition bg-amber-50 text-amber-600 hover:bg-amber-100 border border-amber-200">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M8 14h8"/></svg>
                        <span>Offline</span>
                    </button>
                    <button type="button"
                            x-on:click="chooseUpload()"
                            class="flex flex-col items-center justify-center gap-1 rounded-xl text-sm font-semibold px-3 py-3 transition bg-slate-50 text-slate-600 hover:bg-slate-100 border border-slate-200">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 16V4m0 0L7 9m5-5 5 5M5 20h14"/></svg>
                        <span>Online</span>
                    </button>
                </div>
            </div>
        </template>

        {{-- Langkah 2a: Online (upload file + metode pertemuan) --}}
        <template x-if="method === 'upload'">
            <form method="POST" action="{{ $action }}" enctype="multipart/form-data"
                  x-on:submit="if (! uploadFile || ! meetingMethod) { $event.preventDefault(); }">
                @csrf
                <input type="hidden" name="evidence_type" value="upload">
                <input type="hidden" name="meeting_method" x-bind:value="meetingMethod">

                <div class="flex items-center justify-between mb-2">
                    <p class="text-xs font-semibold text-slate-500">Bukti: Online</p>
                    <button type="button" x-on:click="resetMethod()" class="text-xs font-semibold text-blue-600 hover:underline">Ganti Metode</button>
                </div>

                <label class="block text-xs font-semibold text-slate-500 mb-1">Metode Pertemuan</label>
                <select x-model="meetingMethod"
                        class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm mb-3 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 outline-none">
                    <option value="" disabled>Pilih metode pertemuan</option>
                    @foreach (\App\Models\User::CHECKLIST_MEETING_METHODS as $methodValue => $methodLabel)
                        <option value="{{ $methodValue }}">{{ $methodLabel }}</option>
                    @endforeach
                </select>

                <label class="flex flex-col items-center justify-center gap-1 rounded-xl border-2 border-dashed border-slate-300 px-3 py-4 cursor-pointer hover:bg-slate-50 transition">
                    <template x-if="! uploadPreview">
                        <span class="text-xs text-slate-500 text-center">Klik untuk pilih gambar dari perangkat</span>
                    </template>
                    <template x-if="uploadPreview">
                        <img :src="uploadPreview" class="w-16 h-16 object-cover rounded-xl border border-slate-200">
                    </template>
                    <input type="file"
                           name="evidence_file"
                           x-ref="fileInput"
                           accept="image/*"
                           class="hidden"
                           x-on:change="onFileChange($event)">
                </label>

                <button type="submit"
                        :disabled="! uploadFile || ! meetingMethod"
                        class="mt-2 w-full inline-flex items-center justify-center gap-2 rounded-xl text-sm font-semibold px-4 py-2.5 transition bg-blue-600 hover:bg-blue-700 text-white disabled:opacity-40 disabled:cursor-not-allowed">
                    {{ $uncheckedLabel }}
                </button>
            </form>
        </template>

        {{-- Langkah 2b: Offline --}}
        @if ($mode === 'issuer')
            {{-- Sisi PENILAI/ATASAN: membuat kode, lalu menunjukkannya
                 langsung ke lawan bicaranya. Checklist sisi ini akan
                 tercentang otomatis begitu kodenya dimasukkan. --}}
            <template x-if="method === 'kode'">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-xs font-semibold text-slate-500">Bukti: Offline (Kode)</p>
                        <button type="button" x-on:click="resetMethod()" class="text-xs font-semibold text-blue-600 hover:underline">Ganti Metode</button>
                    </div>

                    @if ($activeCode && $activeCode->masihBerlaku())
                        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-center">
                            <p class="text-[11px] font-semibold text-amber-600 mb-1">Tunjukkan kode ini</p>
                            <p class="text-2xl font-bold tracking-[0.3em] text-amber-700">{{ $activeCode->code }}</p>
                            <p class="text-[11px] text-amber-600 mt-1">
                                <span x-show="sisaDetik > 0">Berlaku <span x-text="sisaLabel"></span> lagi</span>
                                <span x-show="sisaDetik === 0" x-cloak>Kode sudah kedaluwarsa, buat yang baru.</span>
                            </p>
                        </div>
                    @endif

                    <form method="POST" action="{{ $codeAction }}" class="mt-2">
                        @csrf
                        <button type="submit"
                                class="w-full inline-flex items-center justify-center gap-2 rounded-xl text-sm font-semibold px-4 py-2.5 transition bg-amber-500 hover:bg-amber-600 text-white">
                            {{ $activeCode && $activeCode->masihBerlaku() ? 'Buat Kode Baru' : 'Buat Kode Pertemuan' }}
                        </button>
                    </form>

                    <p class="text-[11px] text-slate-400 mt-2 leading-relaxed">
                        Kode berlaku {{ \App\Models\MeetingCode::VALID_MINUTES }} menit dan hanya tampil di layar Anda.
                        Bacakan langsung ke orang yang Anda temui - checklist kalian berdua tercentang otomatis begitu kodenya dimasukkan.
                    </p>
                </div>
            </template>
        @else
            {{-- Sisi PEGAWAI/PEJABAT: memasukkan kode yang ditunjukkan
                 penilai. Satu submit ini mencentang checklist kedua
                 belah pihak sekaligus. --}}
            <template x-if="method === 'kode'">
                <form method="POST" action="{{ $action }}">
                    @csrf
                    <input type="hidden" name="evidence_type" value="kode">

                    <div class="flex items-center justify-between mb-2">
                        <p class="text-xs font-semibold text-slate-500">Bukti: Offline (Kode)</p>
                        <button type="button" x-on:click="resetMethod()" class="text-xs font-semibold text-blue-600 hover:underline">Ganti Metode</button>
                    </div>

                    <label class="block text-xs font-semibold text-slate-500 mb-1">Kode Pertemuan</label>
                    <input type="text"
                           name="meeting_code"
                           inputmode="latin"
                           autocomplete="off"
                           autocapitalize="characters"
                           spellcheck="false"
                           maxlength="{{ \App\Models\MeetingCode::LENGTH }}"
                           placeholder="{{ str_repeat('X', \App\Models\MeetingCode::LENGTH) }}"
                           x-on:input="normalizeCode($event)"
                           class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-center text-lg font-bold tracking-[0.3em] uppercase focus:border-amber-500 focus:ring-1 focus:ring-amber-500 outline-none">

                    @error('meeting_code')
                        <p class="text-xs text-red-600 mt-1.5">{{ $message }}</p>
                    @enderror

                    <button type="submit"
                            class="mt-2 w-full inline-flex items-center justify-center gap-2 rounded-xl text-sm font-semibold px-4 py-2.5 transition bg-amber-500 hover:bg-amber-600 text-white">
                        Verifikasi &amp; Tandai Sudah Bertemu
                    </button>

                    <p class="text-[11px] text-slate-400 mt-2 leading-relaxed">
                        Minta kodenya langsung ke Penilai Anda saat pertemuan. Kode hanya berlaku {{ \App\Models\MeetingCode::VALID_MINUTES }} menit.
                    </p>
                </form>
            </template>
        @endif
    @endif

    @if($checkedAt)
        <p class="text-xs text-slate-400 mt-2">
            Dicentang {{ $checkedAt->translatedFormat('d M Y H:i') }}
        </p>
    @endif

    {{-- Ringkasan bukti yang tersimpan --}}
    @if($checked && ($selfieUrl || $meetingCode))
        @php
            $meetingMethodLabel = \App\Models\User::checklistMeetingMethodLabel($meetingMethod ?? null);
            $adalahKode = \App\Models\User::checklistEvidenceAdalahKode($evidenceType ?? null);
        @endphp
        <p class="text-xs text-slate-400 mt-2 mb-1">
            Bukti: {{ \App\Models\User::checklistEvidenceLabel($evidenceType ?? null) }}
            @if($adalahKode)
                (Kode {{ $meetingCode }})
            @elseif($meetingMethodLabel)
                ({{ $meetingMethodLabel }})
            @endif
        </p>
        @if(! $adalahKode && $selfieUrl)
            <div class="mt-1">
                <img src="{{ $selfieUrl }}" alt="Bukti checklist" class="w-16 h-16 object-cover rounded-xl border border-slate-200">
            </div>
        @endif
    @endif
</div>
@endif

{{--
    Partial checklist "sudah bertemu & evaluasi" dengan bukti berupa
    salah satu dari 2 metode:
    - Upload File (dipilih dari galeri/file manager perangkat)
    - Ambil Selfie (langsung dari kamera perangkat via getUserMedia)

    User WAJIB memilih salah satu metode sebelum submit - lihat
    method di x-data. Kedua metode menyimpan path ke kolom yang SAMA
    (*_konfirmasi_pertemuan_selfie, existing) - hanya kolom
    *_konfirmasi_pertemuan_evidence_type ('upload'/'selfie') yang
    baru, dipakai untuk label di HRD - lihat
    User::checklistEvidenceLabel() & migration
    add_evidence_type_to_checklist_pertemuan_columns.

    Props:
    - $action        (string)  route untuk submit toggle
    - $checked       (bool)    apakah checklist ini sudah dicentang
    - $checkedAt     (Carbon|null)
    - $selfieUrl     (string|null) URL bukti tersimpan (kalau sudah dicentang)
    - $evidenceType  (string|null) 'upload' (Online) | 'selfie' (Offline) | null (data lama)
    - $meetingMethod (string|null) 'zoom' | 'telpon' | 'chat' | null - hanya
      relevan saat $evidenceType 'upload' (Online), lihat
      User::CHECKLIST_MEETING_METHODS.
    - $checkedLabel   (string) label tombol saat sudah dicentang
    - $uncheckedLabel (string) label tombol saat belum dicentang
    - $boleh          (bool, optional, default true) apakah checklist ini
      sudah boleh MULAI dicentang. Kalau false DAN belum tercentang,
      tombol/form diganti pesan $bolehMessage - lihat
      User::checklistPertemuanPejabatBolehDiisi(). Tidak mempengaruhi
      tombol batal (checklist yang sudah tercentang selalu bisa
      dibatalkan kapan saja).
    - $bolehMessage   (string, optional) pesan yang ditampilkan saat
      $boleh false.
    - $hrdLocked      (bool, optional, default false) apakah HRD sudah
      menandatangani penilaian yang terkait checklist ini - lihat
      User::hrdSudahMenandatanganiPenilaian() &
      User::hrdSudahMenandatanganiPenilaianPejabat(). Kalau true:
      tombol batal disembunyikan (checklist terkunci, tidak bisa
      dicentang/dibatalkan lagi lewat sini) DAN foto bukti/selfie tidak
      ditampilkan lagi. Alurnya: selfie/checklist dulu, baru HRD tanda
      tangan; setelah itu checklist & selfie tidak bisa diakses/diubah.
    - $hrdLockedMessage (string, optional) pesan yang ditampilkan saat
      $hrdLocked true.
--}}
@php
    $boleh = $boleh ?? true;
    $bolehMessage = $bolehMessage ?? 'Belum bisa memberikan bukti evaluasi.';
    $hrdLocked = $hrdLocked ?? false;
    $hrdLockedMessage = $hrdLockedMessage ?? 'Terkunci - penilaian sudah ditanda-tangani HRD.';
@endphp
@if ($hrdLocked)
    <div class="rounded-xl bg-slate-50 border border-slate-200 px-4 py-2.5 text-xs text-slate-500">
        {{ $hrdLockedMessage }}
    </div>
    @if($checkedAt)
        <p class="text-xs text-slate-400 mt-2">
            Dicentang {{ $checkedAt->translatedFormat('d M Y H:i') }}
        </p>
    @endif
    {{-- Foto bukti/selfie SENGAJA tidak ditampilkan lagi begitu HRD sudah
         tanda tangan - lihat catatan $hrdLocked di atas. --}}
@elseif (! $checked && ! $boleh)
    <div class="w-full max-w-full sm:max-w-[220px] break-words rounded-xl bg-slate-50 border border-slate-200 px-4 py-2.5 text-xs text-slate-500">
        {{ $bolehMessage }}
    </div>
    @if($checkedAt)
        <p class="text-xs text-slate-400 mt-2">
            Dicentang {{ $checkedAt->translatedFormat('d M Y H:i') }}
        </p>
    @endif
@else
<div
    x-data="{
        rootEl: null,
        method: null,
        meetingMethod: null,
        uploadFile: null,
        uploadPreview: null,
        open: false,
        stream: null,
        photo: null,
        error: '',
        starting: false,
        ready: false,
        devices: [],
        deviceIndex: 0,
        draftRestored: false,
        autosaveKey() {
            return 'autosave:checklist-selfie:' + (this.rootEl ? this.rootEl.dataset.storageKey : '');
        },
        // Simpan hasil selfie (base64) ke localStorage tiap kali berhasil
        // ambil foto - supaya kalau tiba-tiba web ketutup/ke-refresh/
        // internet putus SEBELUM sempat klik 'Gunakan Foto' & submit,
        // fotonya tidak perlu diambil ulang dari kamera. Metode Upload
        // File SENGAJA TIDAK disimpan ke draft (lihat catatan di
        // onFileChange) karena keterbatasan browser: File asli tidak
        // bisa disimpan ulang lewat localStorage, cuma path/nama
        // filenya saja yang bisa - jadi tidak berguna buat auto-restore.
        persistDraft() {
            if (this.method !== 'selfie' || ! this.photo) { return; }
            try {
                localStorage.setItem(this.autosaveKey(), JSON.stringify({ photo: this.photo, savedAt: Date.now() }));
            } catch (e) {}
        },
        clearDraft() {
            try { localStorage.removeItem(this.autosaveKey()); } catch (e) {}
        },
        restoreDraft() {
            let raw = null;
            try { raw = localStorage.getItem(this.autosaveKey()); } catch (e) { raw = null; }
            if (! raw) { return; }
            try {
                const parsed = JSON.parse(raw);
                if (parsed && parsed.photo) {
                    this.method = 'selfie';
                    this.photo = parsed.photo;
                    this.open = true;
                    this.draftRestored = true;
                }
            } catch (e) {
                this.clearDraft();
            }
        },
        chooseUpload() {
            this.method = 'upload';
        },
        chooseSelfie() {
            this.method = 'selfie';
            this.startCamera();
        },
        resetMethod() {
            this.method = null;
            this.meetingMethod = null;
            this.uploadFile = null;
            this.uploadPreview = null;
            this.photo = null;
            this.draftRestored = false;
            this.clearDraft();
            const fileInput = this.rootEl.querySelector('[x-ref=fileInput]');
            if (fileInput) {
                fileInput.value = '';
            }
        },
        // CATATAN: hasil pilihan 'Upload File' SENGAJA tidak ikut
        // disimpan ke draft autosave. File asli (event.target.files[0])
        // adalah objek browser yang tidak bisa diserialize/disimpan ke
        // localStorage - beda dari selfie kamera yang hasilnya sudah
        // berupa string base64 biasa (lihat capture()/persistDraft()).
        // Kalau web ketutup/refresh sebelum submit, metode Upload File
        // harus dipilih ulang oleh user - ini keterbatasan browser, bukan
        // bug.
        onFileChange(event) {
            const file = event.target.files && event.target.files[0] ? event.target.files[0] : null;
            this.uploadFile = file;
            this.uploadPreview = file ? URL.createObjectURL(file) : null;
        },
        async startCamera() {
            this.error = '';
            this.photo = null;
            this.ready = false;
            this.open = true;
            this.starting = true;
            try {
                // Sebelumnya facingMode:'user' dipaksa (hard constraint).
                // Di laptop yang punya 2 kamera (mis. kamera biasa +
                // kamera inframerah buat Windows Hello), constraint yang
                // dipaksa begini kadang bikin Chrome malah memilih
                // kamera inframerah (framenya gelap tanpa lampu IR aktif
                // dari proses Windows Hello) alih-alih kamera biasa.
                // Sekarang dibikin lebih longgar ('ideal', bukan wajib),
                // dan kalau user sudah pernah pilih device tertentu lewat
                // 'Ganti Kamera', device itu yang dipakai.
                const chosenDeviceId = this.devices[this.deviceIndex] ? this.devices[this.deviceIndex].deviceId : null;
                const videoConstraints = chosenDeviceId
                    ? { deviceId: { exact: chosenDeviceId } }
                    : { facingMode: { ideal: 'user' } };

                this.stream = await navigator.mediaDevices.getUserMedia({ video: videoConstraints, audio: false });

                // Ambil daftar kamera yang tersedia (baru bisa dapat
                // label lengkap SETELAH izin kamera diberikan), supaya
                // tombol 'Ganti Kamera' bisa muncul kalau devicenya
                // lebih dari satu.
                try {
                    const allDevices = await navigator.mediaDevices.enumerateDevices();
                    this.devices = allDevices.filter((d) => d.kind === 'videoinput');
                } catch (e) {
                    // Diamkan - enumerateDevices gagal tidak fatal,
                    // cuma berarti tombol ganti kamera tidak muncul.
                }

                this.$nextTick(() => {
                    const video = this.rootEl.querySelector('[x-ref=video]');
                    if (! video) { return; }
                    video.srcObject = this.stream;

                    // srcObject yang di-assign lewat JS tidak selalu
                    // otomatis diputar oleh atribut autoplay (terutama
                    // Chrome/Edge di Windows setelah kamera dibuka-tutup
                    // beberapa kali) - videonya tersambung tapi layarnya
                    // tetap hitam sampai .play() dipanggil manual.
                    video.play().catch(() => {
                        this.error = 'Gagal menampilkan kamera, coba lagi.';
                    });

                    clearInterval(this._cameraReadyPoll);
                    clearTimeout(this._cameraReadyTimeout);

                    const blockedMessage = 'Kamera diblokir setelah tersambung (indikator \'Camera on\' menyala tapi gambarnya tidak pernah muncul). Ini bukan masalah izin di browser, tapi biasanya diblokir di level OS/driver. Cek: (1) Settings > Privacy & security > Camera di Windows - aktifkan \'Let desktop apps access your camera\', (2) tutup aplikasi lain yang mungkin memegang kamera (Zoom/Teams/OBS), (3) kalau ini sesi Remote Desktop/VM, kamera fisik biasanya tidak diteruskan ke sesi remote. Kalau sudah dicek dan masih gagal, pakai metode \'Upload File\' saja sebagai gantinya.';

                    // Track video punya properti `muted` yang di-set true
                    // oleh browser saat OS memblokir pengiriman frame
                    // SETELAH stream berhasil dinegosiasikan (ini beda
                    // dari gagal izin - izin sudah diberikan, tapi
                    // framenya sendiri ditahan di level OS/driver).
                    // Ini sinyal resmi dari browser, jauh lebih pasti
                    // dibanding menebak dari videoWidth/canvas yang
                    // bisa saja tetap kosong tanpa penjelasan.
                    const track = this.stream.getVideoTracks()[0];
                    if (track) {
                        if (track.muted) {
                            this.error = blockedMessage;
                        }
                        track.addEventListener('mute', () => {
                            if (this.open && ! this.photo) {
                                this.ready = false;
                                this.error = blockedMessage;
                            }
                        });
                        track.addEventListener('unmute', () => {
                            if (this.open && ! this.photo) {
                                this.error = '';
                            }
                        });
                    }

                    // videoWidth SAJA tidak bisa dipercaya sebagai tanda
                    // 'video sudah benar-benar tampil' - videoWidth bisa
                    // sudah terisi dari metadata track walau frame
                    // gambarnya sendiri tidak pernah benar-benar
                    // dikirim/didekode. Makanya dicek berkala (poll)
                    // apakah video benar-benar sudah bisa digambar ke
                    // canvas (readyState cukup + tidak error saat
                    // drawImage), sebagai pelengkap dari deteksi
                    // track.muted di atas.
                    const probe = document.createElement('canvas');
                    const probeCtx = probe.getContext('2d');
                    const canDrawFrame = () => {
                        if (video.readyState < 2 || ! video.videoWidth) { return false; }
                        try {
                            probe.width = 2;
                            probe.height = 2;
                            probeCtx.drawImage(video, 0, 0, 2, 2);
                            return true;
                        } catch (e) {
                            return false;
                        }
                    };

                    this._cameraReadyPoll = setInterval(() => {
                        if (! this.open || this.photo) {
                            clearInterval(this._cameraReadyPoll);
                            return;
                        }
                        if (canDrawFrame()) {
                            this.ready = true;
                            this.error = '';
                            clearInterval(this._cameraReadyPoll);
                            clearTimeout(this._cameraReadyTimeout);
                        }
                    }, 200);

                    // Kalau setelah beberapa detik video tetap belum
                    // benar-benar siap dan track.muted juga tidak
                    // pernah menyala (jadi bukan kasus blokir OS di
                    // atas), kasih tahu user dengan jelas alih-alih
                    // dibiarkan macet tanpa keterangan.
                    this._cameraReadyTimeout = setTimeout(() => {
                        clearInterval(this._cameraReadyPoll);
                        if (this.open && ! this.photo && ! this.ready && ! this.error) {
                            this.error = blockedMessage;
                        }
                    }, 5000);
                });
            } catch (e) {
                this.error = 'Izin kamera diperlukan untuk mengambil selfie.';
            } finally {
                this.starting = false;
            }
        },
        stopStream() {
            clearInterval(this._cameraReadyPoll);
            if (this.stream) {
                this.stream.getTracks().forEach(t => t.stop());
                this.stream = null;
            }
            // Elemen <video> harus benar-benar dilepas dari stream lama,
            // bukan cuma track-nya yang dihentikan - kalau tidak, saat
            // kamera dibuka lagi (retake / buka-tutup berkali-kali),
            // <video> masih 'nyangkut' ke stream lama yang sudah mati
            // dan stream baru gagal tampil (layar tetap hitam walau
            // .play() sudah dipanggil manual) di banyak browser Chrome/
            // Edge di Windows.
            const video = this.rootEl.querySelector('[x-ref=video]');
            if (video) {
                video.pause();
                video.srcObject = null;
                video.load();
            }
        },
        frameBrightness(video) {
            // Sampel kecil (32x32) supaya murah dihitung - cukup untuk
            // mendeteksi frame yang benar-benar hitam total.
            const w = 32, h = 32;
            const sample = document.createElement('canvas');
            sample.width = w;
            sample.height = h;
            const ctx = sample.getContext('2d');
            ctx.drawImage(video, 0, 0, w, h);
            const { data } = ctx.getImageData(0, 0, w, h);
            let total = 0;
            for (let i = 0; i < data.length; i += 4) {
                total += (data[i] + data[i + 1] + data[i + 2]) / 3;
            }
            return total / (data.length / 4);
        },
        capture() {
            const video = this.rootEl.querySelector('[x-ref=video]');
            const canvas = this.rootEl.querySelector('[x-ref=canvas]');
            if (! video || ! video.videoWidth) { return; }
            clearTimeout(this._cameraReadyTimeout);
            clearInterval(this._cameraReadyPoll);

            // Kamera kadang berhasil konek (videoWidth > 0, tidak ada
            // error) tapi framenya hitam total - paling sering karena
            // pengaturan privasi kamera di level OS (mis. Windows:
            // Settings > Privacy & security > Camera) memblokir akses
            // browser meski izin di browser sudah diberikan, atau
            // lensa kamera memang tertutup. videoWidth saja tidak
            // cukup buat mendeteksi ini, jadi foto hasil capture
            // dicek juga kecerahannya sebelum dipakai - supaya user
            // tidak keburu submit selfie yang isinya hitam polos.
            if (this.frameBrightness(video) < 4) {
                this.error = 'Kamera menyala tapi gambarnya gelap total. Kemungkinan pengaturan privasi kamera di OS memblokir browser (Windows: Settings > Privacy & security > Camera - aktifkan akses kamera untuk browser/aplikasi desktop), atau lensa kamera tertutup. Periksa lalu coba lagi.';
                return;
            }

            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            const ctx = canvas.getContext('2d');
            ctx.save();
            ctx.translate(canvas.width, 0);
            ctx.scale(-1, 1);
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
            ctx.restore();

            // PENTING: sebelumnya toDataURL('image/png') langsung dipakai
            // di resolusi ASLI kamera (bisa 1920x1080+) - satu foto begini
            // bisa 3-8MB base64, padahal localStorage cuma dijatah sekitar
            // 5-10MB TOTAL per situs. Draft selfie sendirian bisa
            // menghabiskan (hampir) seluruh kuota, dan persistDraft() bisa
            // diam-diam gagal (lihat catch-nya) tanpa kelihatan di mana
            // pun. Server sudah kompres ke JPEG 75% & max 1000px (lihat
            // HandlesChecklistEvidence::compressChecklistImage()), tapi
            // itu baru terjadi SETELAH submit - draft yang tersimpan di
            // browser tetap file mentahnya. Di sini kita resize + kompres
            // ke ukuran serupa DULU, sebelum disimpan sebagai draft
            // maupun dikirim ke server.
            const MAX_DIMENSION = 1000;
            let outCanvas = canvas;
            if (canvas.width > MAX_DIMENSION || canvas.height > MAX_DIMENSION) {
                const scale = MAX_DIMENSION / Math.max(canvas.width, canvas.height);
                outCanvas = document.createElement('canvas');
                outCanvas.width = Math.round(canvas.width * scale);
                outCanvas.height = Math.round(canvas.height * scale);
                outCanvas.getContext('2d').drawImage(canvas, 0, 0, outCanvas.width, outCanvas.height);
            }
            this.photo = outCanvas.toDataURL('image/jpeg', 0.8);

            this.stopStream();
            this.persistDraft();
        },
        retake() {
            this.draftRestored = false;
            this.clearDraft();
            this.startCamera();
        },
        switchCamera() {
            if (this.devices.length < 2) { return; }
            this.deviceIndex = (this.deviceIndex + 1) % this.devices.length;
            this.stopStream();
            this.startCamera();
        },
        usePhoto() {
            this.rootEl.querySelector('[x-ref=selfieInput]').value = this.photo;
            this.open = false;
            this.stopStream();
            this.clearDraft();
            this.$nextTick(() => this.rootEl.querySelector('[x-ref=toggleForm]').submit());
        },
        cancel() {
            clearTimeout(this._cameraReadyTimeout);
            clearInterval(this._cameraReadyPoll);
            this.stopStream();
            this.open = false;
            this.photo = null;
            this.ready = false;
            this.method = null;
            this.draftRestored = false;
            this.clearDraft();
        }
    }"
    data-storage-key="{{ $action }}"
    x-init="rootEl = $el; restoreDraft()"
    x-on:keydown.escape.window="open && cancel()"
    x-on:beforeunload.window="stopStream()"
>
    <form
        method="POST"
        action="{{ $action }}"
        enctype="multipart/form-data"
        x-ref="toggleForm"
        @if($checked) onsubmit="return true;" @else x-on:submit="if (method === 'upload' && (! uploadFile || ! meetingMethod)) { $event.preventDefault(); }" @endif
    >
        @csrf
        <input type="hidden" name="selfie" x-ref="selfieInput">
        <input type="hidden" name="evidence_type" x-bind:value="method">
        <input type="hidden" name="meeting_method" x-bind:value="meetingMethod">

        @if($checked)
            {{-- Sudah dicentang: submit langsung membatalkan, tanpa bukti baru --}}
            <button type="submit"
                    class="w-full inline-flex items-center justify-center gap-2 rounded-xl text-sm font-semibold px-4 py-2.5 transition bg-emerald-50 text-emerald-600 hover:bg-emerald-100">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="m5 13 4 4L19 7"/></svg>
                {{ $checkedLabel }}
            </button>
        @else
            {{-- Belum dicentang: WAJIB pilih salah satu metode bukti dulu --}}

            {{-- Langkah 1: pilih metode (tampil selama belum memilih) --}}
            <template x-if="! method">
                <div>
                    <p class="text-xs font-semibold text-slate-500 mb-2">Disampaikan Secara Langsung</p>
                    <div class="grid grid-cols-2 gap-2">
                        <button type="button"
                                x-on:click="chooseSelfie()"
                                class="flex flex-col items-center justify-center gap-1 rounded-xl text-sm font-semibold px-3 py-3 transition bg-amber-50 text-amber-600 hover:bg-amber-100 border border-amber-200">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 8a2 2 0 0 1 2-2h1.5l.9-1.5a1 1 0 0 1 .86-.5h5.48a1 1 0 0 1 .86.5L16.5 6H18a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8Z"/><circle cx="12" cy="13" r="3.5"/></svg>
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

            {{-- Langkah 2a: Online dipilih (upload file + metode pertemuan) --}}
            <template x-if="method === 'upload'">
                <div>
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
                </div>
            </template>

            {{-- Langkah 2b: Offline dipilih (kamera dibuka via modal) --}}
            <template x-if="method === 'selfie' && ! open">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-xs font-semibold text-slate-500">Bukti: Offline</p>
                        <button type="button" x-on:click="resetMethod()" class="text-xs font-semibold text-blue-600 hover:underline">Ganti Metode</button>
                    </div>
                    <button type="button"
                            x-on:click="startCamera()"
                            class="w-full inline-flex items-center justify-center gap-2 rounded-xl text-sm font-semibold px-4 py-2.5 transition bg-amber-50 text-amber-600 hover:bg-amber-100">
                        Buka Kamera
                    </button>
                </div>
            </template>
        @endif
    </form>

    @if($checkedAt)
        <p class="text-xs text-slate-400 mt-2">
            Dicentang {{ $checkedAt->translatedFormat('d M Y H:i') }}
        </p>
    @endif

    @if($checked && $selfieUrl)
        @php
            $meetingMethodLabel = \App\Models\User::checklistMeetingMethodLabel($meetingMethod ?? null);
        @endphp
        <p class="text-xs text-slate-400 mt-2 mb-1">
            Bukti: {{ \App\Models\User::checklistEvidenceLabel($evidenceType ?? null) }}
            @if($meetingMethodLabel)
                ({{ $meetingMethodLabel }})
            @endif
        </p>
        <div class="mt-1">
            <img src="{{ $selfieUrl }}" alt="Bukti checklist" class="w-16 h-16 object-cover rounded-xl border border-slate-200">
        </div>
    @endif

    {{-- Modal kamera --}}
    <div x-show="open"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4"
         style="display:none;">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-5">
            <h4 class="text-sm font-bold text-slate-800 mb-3">Ambil Selfie</h4>

            <div x-show="error" x-cloak>
                <div>
                    <p class="text-sm text-red-600 bg-red-50 rounded-xl px-4 py-3" x-text="error"></p>
                    <button type="button"
                            x-on:click="startCamera()"
                            class="mt-3 w-full rounded-xl bg-slate-800 text-white text-sm font-semibold px-4 py-2.5">
                        Coba Lagi
                    </button>
                    <button type="button"
                            x-on:click="cancel()"
                            class="mt-2 w-full rounded-xl bg-slate-100 text-slate-600 text-sm font-semibold px-4 py-2.5">
                        Batal
                    </button>
                </div>
            </div>

            <div x-show="! error">
                <div>
                    <div class="relative rounded-xl overflow-hidden bg-slate-900 aspect-square">
                        <video x-ref="video"
                               x-show="! photo"
                               playsinline
                               muted
                               class="w-full h-full object-cover"
                               style="transform: scaleX(-1); width: 100%; height: 100%; display: block;"></video>
                        <img x-show="photo" :src="photo" class="w-full h-full object-cover">
                    </div>
                    <canvas x-ref="canvas" class="hidden"></canvas>

                    <div class="mt-4 space-y-2">
                        <template x-if="! photo">
                            <div>
                                <p x-show="! ready" x-cloak class="text-xs text-slate-400 text-center mb-2">Menyiapkan kamera...</p>
                                <button type="button"
                                        x-on:click="capture()"
                                        :disabled="starting || ! ready"
                                        class="w-full rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold px-4 py-2.5 disabled:opacity-40 disabled:cursor-not-allowed">
                                    Ambil
                                </button>
                                <button type="button"
                                        x-show="! ready && ! starting"
                                        x-cloak
                                        x-on:click="capture()"
                                        class="mt-2 w-full text-xs font-semibold text-slate-400 hover:text-slate-600 underline">
                                    Kamera kelihatan sudah nyala? Coba ambil manual
                                </button>
                                <button type="button"
                                        x-show="devices.length > 1"
                                        x-cloak
                                        x-on:click="switchCamera()"
                                        class="mt-2 w-full inline-flex items-center justify-center gap-1.5 rounded-xl text-xs font-semibold px-3 py-2 bg-slate-50 text-slate-500 hover:bg-slate-100 border border-slate-200">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-3.5 h-3.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h5M20 20v-5h-5M4 9a8 8 0 0 1 14.93-3M20 15a8 8 0 0 1-14.93 3"/></svg>
                                    Ganti Kamera
                                </button>
                            </div>
                        </template>
                        <template x-if="photo">
                            <div>
                                <p x-show="draftRestored" x-cloak class="text-xs text-amber-600 bg-amber-50 rounded-lg px-3 py-2 mb-2">
                                    Foto ini dipulihkan dari draf otomatis (belum sempat terkirim sebelumnya). Langsung "Gunakan Foto" untuk lanjut, atau ambil ulang.
                                </p>
                                <div class="flex gap-2">
                                    <button type="button"
                                            x-on:click="retake()"
                                            class="flex-1 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-semibold px-4 py-2.5">
                                        Ambil Ulang
                                    </button>
                                    <button type="button"
                                            x-on:click="usePhoto()"
                                            class="flex-1 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold px-4 py-2.5">
                                        Gunakan Foto
                                    </button>
                                </div>
                            </div>
                        </template>
                        <button type="button"
                                x-on:click="cancel()"
                                class="w-full rounded-xl bg-white border border-slate-200 text-slate-500 text-sm font-medium px-4 py-2">
                            Batal
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
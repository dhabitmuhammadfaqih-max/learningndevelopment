{{--
    Overlay "memuat ulang" yang dipakai bareng oleh semua halaman yang
    polling status-version (lihat admin/employee/official dashboard,
    tanggapan-pegawai, tanggapan-pejabat, supervisor/evaluate_official).

    Sebelumnya, saat polling mendeteksi data berubah, halaman langsung
    window.location.reload() detik itu juga - kerasa "kedip" mendadak
    dari sudut pandang user. Sekarang dipanggil lewat window.showReloadOverlay()
    yang menampilkan overlay skeleton sebentar dulu (kasih sinyal visual
    "ada pembaruan, halaman lagi disegarkan") baru reload beneran.
--}}
<div id="reload-overlay"
     class="fixed inset-0 z-[999] bg-white/80 backdrop-blur-sm hidden items-center justify-center">
    <div class="w-full max-w-sm px-6">
        <div class="flex items-center gap-3 justify-center mb-6">
            <svg class="w-5 h-5 animate-spin text-blue-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <p class="text-sm font-semibold text-slate-600">Memuat pembaruan&hellip;</p>
        </div>

        {{-- Skeleton shimmer - meniru bentuk kasar konten dashboard --}}
        <div class="space-y-3 animate-pulse">
            <div class="h-24 rounded-2xl bg-slate-200/70"></div>
            <div class="h-4 w-2/3 rounded bg-slate-200/70"></div>
            <div class="h-4 w-1/2 rounded bg-slate-200/70"></div>
            <div class="h-16 rounded-xl bg-slate-200/70"></div>
        </div>
    </div>
</div>

<script>
    // Dipanggil dari poll() di halaman manapun yang butuh reload otomatis
    // saat data server berubah - lihat masing-masing <script> di bagian
    // bawah halaman dashboard/tanggapan.
    window.showReloadOverlay = function () {
        const overlay = document.getElementById('reload-overlay');
        if (!overlay) {
            window.location.reload();
            return;
        }

        overlay.classList.remove('hidden');
        overlay.classList.add('flex');

        // Beri jeda singkat supaya transisi kerasa halus, bukan reload
        // instan - cukup lama untuk kelihatan sebagai "transisi yang
        // disengaja", cukup singkat supaya tidak kerasa lambat.
        setTimeout(() => window.location.reload(), 450);
    };
</script>

{{--
    Modal wajib diisi SEKALI SAJA per akun, muncul otomatis di halaman
    dashboard manapun (lihat components/dashboard-layout.blade.php) selama
    auth()->user() belum punya signature_path tersimpan.

    Setelah disimpan lewat AccountSignatureController@store, tanda tangan
    ini yang dipakai ulang otomatis oleh semua alur tanda tangan
    (tanggapan, penilaian, pengesahan HRD, dsb - lihat
    App\Support\AccountSignature) di device manapun user ini login,
    karena disimpan di kolom users.signature_path (database), bukan di
    localStorage/session milik satu browser.
--}}
@if (auth()->check() && ! auth()->user()->hasSavedSignature())
    <div id="signature-setup-modal"
         class="fixed inset-0 z-[999] bg-slate-900/60 backdrop-blur-sm flex items-center justify-center px-4">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6">
            <div class="w-11 h-11 rounded-full bg-blue-50 grid place-items-center mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5 text-blue-600">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536M9 11l6.586-6.586a2 2 0 1 1 2.828 2.828L11.828 13.828a4 4 0 0 1-1.414.943l-3.114 1.2 1.2-3.114A4 4 0 0 1 9 11ZM5 19h14" />
                </svg>
            </div>

            <p class="text-base font-bold text-slate-800 mb-1">Simpan Tanda Tangan Anda</p>
            <p class="text-sm text-slate-500 leading-relaxed mb-4">
                Karena ini pertama kalinya Anda login, mohon gambar tanda tangan sekali di bawah ini.
                Setelah disimpan, tanda tangan ini akan dipakai otomatis setiap kali Anda perlu
                menandatangani sesuatu (tanggapan, penilaian, dsb) - di perangkat manapun Anda login -
                tanpa perlu menggambar ulang.
            </p>

            <canvas id="account-signature-pad"
                    class="signature-canvas rounded-xl border border-slate-200 bg-white touch-none cursor-crosshair block w-full"
                    style="aspect-ratio: 400 / 150;"></canvas>

            <div class="flex items-center justify-between mt-2 mb-4">
                <button type="button" id="btn-clear-account-signature"
                        class="text-xs font-medium text-slate-500 underline hover:text-slate-800">
                    Hapus &amp; ulangi
                </button>
                <p id="account-signature-error" class="text-xs text-red-600 hidden"></p>
            </div>

            <button type="button" id="btn-save-account-signature"
                    class="w-full px-4 py-2.5 rounded-xl text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                Simpan Tanda Tangan
            </button>
        </div>
    </div>

    <script>
        (function () {
            const canvas = document.getElementById('account-signature-pad');
            if (!canvas) return;

            const ctx = canvas.getContext('2d', { alpha: true });
            let drawing = false;
            let last = null;
            let hasStroke = false;
            let ratio = Math.max(window.devicePixelRatio || 1, 1);

            function setupCanvas() {
                const rect = canvas.getBoundingClientRect();
                const cssWidth = Math.max(Math.round(rect.width), 1);
                const cssHeight = Math.max(Math.round(rect.height), 1);
                ratio = Math.max(window.devicePixelRatio || 1, 1);
                canvas.width = Math.round(cssWidth * ratio);
                canvas.height = Math.round(cssHeight * ratio);
                ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
                ctx.lineCap = 'round';
                ctx.lineJoin = 'round';
                ctx.lineWidth = 2.2;
                ctx.strokeStyle = '#111';
            }

            function pointFromEvent(e) {
                const rect = canvas.getBoundingClientRect();
                const point = e.touches ? e.touches[0] : e;
                return { x: point.clientX - rect.left, y: point.clientY - rect.top };
            }

            function start(e) {
                e.preventDefault();
                drawing = true;
                last = pointFromEvent(e);
            }

            function move(e) {
                if (!drawing) return;
                e.preventDefault();
                const point = pointFromEvent(e);
                ctx.beginPath();
                ctx.moveTo(last.x, last.y);
                ctx.lineTo(point.x, point.y);
                ctx.stroke();
                last = point;
                hasStroke = true;
            }

            function end() {
                drawing = false;
                last = null;
            }

            setupCanvas();
            window.addEventListener('resize', setupCanvas);

            // ResizeObserver dipakai (bukan cuma window "resize") supaya
            // canvas tetap sinkron kalau ukurannya berubah karena hal lain
            // selain resize window - misalnya modal baru pertama kali
            // dirender, browser di-zoom, atau devicePixelRatio berubah
            // (pindah ke monitor lain). Kalau ini tidak disinkronkan,
            // titik yang dihitung dari posisi kursor (pointFromEvent) jadi
            // geser dari garis yang benar-benar digambar di canvas.
            if (window.ResizeObserver) {
                new ResizeObserver(function () { setupCanvas(); }).observe(canvas);
            }

            canvas.addEventListener('mousedown', start);
            canvas.addEventListener('mousemove', move);
            window.addEventListener('mouseup', end);
            canvas.addEventListener('touchstart', start, { passive: false });
            canvas.addEventListener('touchmove', move, { passive: false });
            canvas.addEventListener('touchend', end);

            document.getElementById('btn-clear-account-signature').addEventListener('click', function () {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                hasStroke = false;
            });

            const errorEl = document.getElementById('account-signature-error');
            const saveBtn = document.getElementById('btn-save-account-signature');

            saveBtn.addEventListener('click', function () {
                errorEl.classList.add('hidden');

                if (!hasStroke) {
                    errorEl.textContent = 'Silakan gambar tanda tangan terlebih dahulu.';
                    errorEl.classList.remove('hidden');
                    return;
                }

                saveBtn.disabled = true;
                saveBtn.textContent = 'Menyimpan...';

                fetch('{{ route('account-signature.store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ signature: canvas.toDataURL('image/png') }),
                })
                    .then(function (res) {
                        if (!res.ok) throw new Error('Gagal menyimpan tanda tangan.');
                        return res.json();
                    })
                    .then(function () {
                        window.location.reload();
                    })
                    .catch(function () {
                        saveBtn.disabled = false;
                        saveBtn.textContent = 'Simpan Tanda Tangan';
                        errorEl.textContent = 'Gagal menyimpan tanda tangan. Silakan coba lagi.';
                        errorEl.classList.remove('hidden');
                    });
            });
        })();
    </script>
@endif

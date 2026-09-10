{{--
    Modal konfirmasi custom, pengganti confirm() bawaan browser yang
    tampilannya tidak bisa di-styling (lihat screenshot user: muncul
    sebagai "127.0.0.1:8000 says" polos ala sistem operasi).

    Dipakai lewat helper window.confirmDialog(event, 'pesan') di form/button
    yang sebelumnya pakai onsubmit="return confirm('...')" atau
    onclick="return confirm('...')" - tinggal ganti confirm(...) jadi
    confirmDialog(event, ...), sisanya sama persis.
--}}
<div id="confirm-modal" class="fixed inset-0 z-[998] bg-slate-900/40 backdrop-blur-sm hidden items-center justify-center px-4">
    <div class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6">
        <div class="w-11 h-11 rounded-full bg-amber-50 grid place-items-center mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="w-5 h-5 text-amber-500">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/>
            </svg>
        </div>

        <p id="confirm-modal-message" class="text-sm text-slate-700 leading-relaxed mb-6"></p>

        <div class="flex items-center gap-3 justify-end">
            <button type="button" id="confirm-modal-cancel"
                    class="px-4 py-2 rounded-xl text-sm font-semibold text-slate-600 hover:bg-slate-100 transition">
                Batal
            </button>
            <button type="button" id="confirm-modal-ok"
                    class="px-4 py-2 rounded-xl text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 transition">
                Ya, Lanjutkan
            </button>
        </div>
    </div>
</div>

<script>
    // Tampilkan modal, kembalikan Promise<boolean> (true = user klik "Ya,
    // Lanjutkan", false = "Batal" atau klik di luar modal).
    window.showConfirmModal = function (message) {
        return new Promise((resolve) => {
            const overlay = document.getElementById('confirm-modal');
            const msgEl = document.getElementById('confirm-modal-message');
            const btnOk = document.getElementById('confirm-modal-ok');
            const btnCancel = document.getElementById('confirm-modal-cancel');

            // Fallback ke confirm() bawaan kalau partial ini entah kenapa
            // tidak ikut ter-render (mis. dipanggil dari halaman yang
            // tidak pakai dashboard-layout) - supaya form tetap bisa
            // jalan, bukan macet diam-diam.
            if (!overlay || !msgEl || !btnOk || !btnCancel) {
                resolve(window.confirm(message));
                return;
            }

            msgEl.textContent = message;
            overlay.classList.remove('hidden');
            overlay.classList.add('flex');

            function cleanup(result) {
                overlay.classList.add('hidden');
                overlay.classList.remove('flex');
                btnOk.removeEventListener('click', onOk);
                btnCancel.removeEventListener('click', onCancel);
                overlay.removeEventListener('click', onBackdrop);
                resolve(result);
            }

            function onOk() { cleanup(true); }
            function onCancel() { cleanup(false); }
            function onBackdrop(e) { if (e.target === overlay) cleanup(false); }

            btnOk.addEventListener('click', onOk);
            btnCancel.addEventListener('click', onCancel);
            overlay.addEventListener('click', onBackdrop);
        });
    };

    // Helper siap-pakai untuk onsubmit="return confirmDialog(event, '...')"
    // (di <form>) maupun onclick="return confirmDialog(event, '...')"
    // (di <button type="submit"> di dalam form) - keduanya bekerja sama
    // persis seperti confirm() lama, cuma tampilannya custom.
    window.confirmDialog = function (event, message) {
        event.preventDefault();

        const form = event.target.form || event.target.closest('form');

        window.showConfirmModal(message).then((ok) => {
            if (ok && form) form.submit();
        });

        return false;
    };
</script>

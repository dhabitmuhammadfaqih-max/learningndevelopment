{{--
    Banner "Aktifkan Notifikasi".

    Kenapa perlu tombol terpisah (bukan otomatis saat load)? Safari iOS
    HANYA mengizinkan Notification.requestPermission() dipanggil sebagai
    respons LANGSUNG dari tap/klik user. Kalau dipanggil otomatis, Safari
    menolak diam-diam tanpa menampilkan popup izin sama sekali.

    Di iPhone, banner juga dipakai untuk memberi instruksi memasang aplikasi
    ke Home Screen. Notification API memang tidak tersedia di tab Safari.
--}}
<div
    id="fcm-permission-banner"
    class="hidden fixed bottom-4 inset-x-4 sm:inset-x-auto sm:right-4 sm:max-w-sm z-50 rounded-lg shadow-lg border border-blue-200 bg-white dark:bg-gray-800 dark:border-gray-700 p-4"
>
    <div class="flex items-start gap-3">
        <div class="flex-shrink-0 text-blue-600 dark:text-blue-400">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
            </svg>
        </div>
        <div class="flex-1">
            <p id="fcm-permission-title" class="text-sm font-medium text-gray-900 dark:text-gray-100">Aktifkan Notifikasi</p>
            <p id="fcm-permission-message" class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Dapatkan info penilaian &amp; feedback terbaru secara langsung.</p>
            <div id="fcm-permission-actions" class="mt-3 flex gap-2">
                <button
                    type="button"
                    id="fcm-permission-enable-btn"
                    class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-md bg-blue-600 text-white hover:bg-blue-700 transition"
                >
                    Aktifkan
                </button>
                <button
                    type="button"
                    id="fcm-permission-dismiss-btn"
                    class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-md text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                >
                    Nanti saja
                </button>
            </div>
        </div>
    </div>
</div>

{{--
    Baris diagnostik kecil di pojok kiri bawah. SENGAJA selalu di-render
    (bukan cuma pas ada masalah) supaya kalau banner "Aktifkan Notifikasi"
    di atas tidak muncul, penyebabnya bisa langsung dibaca di layar HP
    tanpa perlu sambungin device ke komputer / buka console. Tap baris ini
    untuk sembunyikan.
--}}
<div
    id="fcm-debug-status"
    class="hidden fixed top-4 left-4 z-[9999] max-w-[85vw] rounded-md bg-red-600 text-white text-[11px] leading-snug px-3 py-2 shadow-lg border-2 border-yellow-300"
    onclick="this.classList.add('hidden')"
></div>

<script>
    (function () {
        const debugEl = document.getElementById('fcm-debug-status');

        function showDebug(message) {
            if (!debugEl) return;
            debugEl.textContent = '[FCM] ' + message + ' (tap untuk tutup)';
            debugEl.classList.remove('hidden');
        }

        try {
            const isIos = /iphone|ipad|ipod/i.test(navigator.userAgent);
            const isStandalone = window.navigator.standalone === true
                || window.matchMedia('(display-mode: standalone)').matches;
            const banner = document.getElementById('fcm-permission-banner');
            const title = document.getElementById('fcm-permission-title');
            const message = document.getElementById('fcm-permission-message');
            const actions = document.getElementById('fcm-permission-actions');

            if (isIos && !isStandalone) {
                title.textContent = 'Pasang aplikasi untuk notifikasi';
                message.innerHTML = 'Di Safari, ketuk <strong>Bagikan</strong> lalu <strong>Tambahkan ke Layar Utama</strong>. Setelah itu buka aplikasi dari ikon baru tersebut dan aktifkan notifikasi.';
                actions.classList.add('hidden');
                banner.classList.remove('hidden');
                return;
            }

            if (!('Notification' in window)) {
                showDebug('Notifikasi membutuhkan iOS 16.4 atau lebih baru dan aplikasi yang dibuka dari Home Screen.');
                return;
            }

            const enableBtn = document.getElementById('fcm-permission-enable-btn');
            const dismissBtn = document.getElementById('fcm-permission-dismiss-btn');
            const dismissedKey = 'fcm-banner-dismissed';

            let dismissedInSession = false;
            try {
                dismissedInSession = !!sessionStorage.getItem(dismissedKey);
            } catch (storageError) {
                // Private browsing di Safari bisa bikin sessionStorage
                // melempar error - jangan sampai itu menghentikan seluruh
                // script, anggap saja belum pernah di-dismiss.
                dismissedInSession = false;
            }

            const currentPermission = Notification.permission;

            if (currentPermission === 'default' && !dismissedInSession) {
                banner.classList.remove('hidden');
            } else if (currentPermission === 'denied') {
                title.textContent = 'Notifikasi diblokir';
                message.textContent = 'Buka Settings > Notifications, pilih aplikasi ini, lalu aktifkan Allow Notifications. Setelah itu buka ulang aplikasi.';
                actions.classList.add('hidden');
                banner.classList.remove('hidden');
                showDebug('Izin notifikasi sudah ditolak sebelumnya. Aktifkan kembali lewat Settings > Notifications.');
            } else if (currentPermission === 'granted') {
                showDebug('Izin notifikasi sudah GRANTED - popup memang tidak akan muncul lagi karena sudah diizinkan. Notifikasi harusnya sudah aktif.');
            } else if (dismissedInSession) {
                showDebug('Banner sempat ditutup manual ("Nanti saja") di sesi ini. Tutup app sepenuhnya (swipe di App Switcher) lalu buka ulang dari icon Home Screen untuk memunculkannya lagi.');
            }

            enableBtn.addEventListener('click', function () {
                // Panggil langsung di dalam handler klik - JANGAN di-wrap
                // dengan await/setTimeout apapun sebelum ini, supaya Safari
                // iOS masih menganggap ini bagian dari user gesture.
                if (typeof window.initFcm === 'function') {
                    window.initFcm({ requestPermission: true });
                } else {
                    showDebug('initFcm() belum ke-load (fcm-client.js gagal dimuat atau config Firebase belum lengkap).');
                }
                banner.classList.add('hidden');
            });

            dismissBtn.addEventListener('click', function () {
                try {
                    sessionStorage.setItem(dismissedKey, '1');
                } catch (storageError) {
                    // Abaikan - private browsing, tidak fatal.
                }
                banner.classList.add('hidden');
            });

            // Kalau user allow/deny lewat prompt native, sembunyikan banner.
            window.addEventListener('fcm:ready', () => banner.classList.add('hidden'));
            window.addEventListener('fcm:permission-denied', () => {
                banner.classList.add('hidden');
                showDebug('User menolak popup izin notifikasi barusan (status jadi denied).');
            });
            window.addEventListener('fcm:token-failed', () => {
                showDebug('Izin sudah diberikan, tapi FCM gagal generate token (cek VAPID key / config Firebase).');
            });
            window.addEventListener('fcm:error', (e) => {
                const msg = e?.detail?.error?.message || 'unknown error';
                showDebug('Error saat inisialisasi push notification: ' + msg);
            });
        } catch (fatalError) {
            showDebug('Script notifikasi error: ' + (fatalError?.message || fatalError));
        }
    })();
</script>
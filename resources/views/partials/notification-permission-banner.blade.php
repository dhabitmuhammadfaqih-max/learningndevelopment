{{--
    Banner "Aktifkan Notifikasi".

    Kenapa perlu tombol terpisah (bukan otomatis saat load)? Safari iOS
    HANYA mengizinkan Notification.requestPermission() dipanggil sebagai
    respons LANGSUNG dari tap/klik user. Kalau dipanggil otomatis, Safari
    menolak diam-diam tanpa menampilkan popup izin sama sekali.

    Banner ini disembunyikan secara default, dan cuma dimunculkan lewat JS
    kalau browser mendukung Notification API DAN user belum pernah
    memberi/menolak izin (Notification.permission === 'default'). Kalau
    sudah 'granted' atau 'denied', banner tidak pernah muncul.
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
            <p class="text-sm font-medium text-gray-900 dark:text-gray-100">Aktifkan Notifikasi</p>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">Dapatkan info penilaian &amp; feedback terbaru secara langsung.</p>
            <div class="mt-3 flex gap-2">
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

<script>
    (function () {
        if (!('Notification' in window)) {
            return;
        }

        const banner = document.getElementById('fcm-permission-banner');
        const enableBtn = document.getElementById('fcm-permission-enable-btn');
        const dismissBtn = document.getElementById('fcm-permission-dismiss-btn');
        const dismissedKey = 'fcm-banner-dismissed';

        // Tampilkan banner hanya kalau izin belum pernah diputuskan sama
        // sekali, dan user belum pernah menutupnya di sesi ini.
        if (Notification.permission === 'default' && !sessionStorage.getItem(dismissedKey)) {
            banner.classList.remove('hidden');
        }

        enableBtn.addEventListener('click', function () {
            // Panggil langsung di dalam handler klik - JANGAN di-wrap
            // dengan await/setTimeout apapun sebelum ini, supaya Safari
            // iOS masih menganggap ini bagian dari user gesture.
            if (typeof window.initFcm === 'function') {
                window.initFcm();
            }
            banner.classList.add('hidden');
        });

        dismissBtn.addEventListener('click', function () {
            sessionStorage.setItem(dismissedKey, '1');
            banner.classList.add('hidden');
        });

        // Kalau user allow/deny lewat prompt native, sembunyikan banner.
        window.addEventListener('fcm:ready', () => banner.classList.add('hidden'));
        window.addEventListener('fcm:permission-denied', () => banner.classList.add('hidden'));
    })();
</script>

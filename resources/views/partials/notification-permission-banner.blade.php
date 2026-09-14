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
    class="hidden fixed inset-x-4 sm:inset-x-auto sm:right-4 sm:max-w-sm z-50 rounded-lg shadow-lg border border-blue-200 bg-white dark:bg-gray-800 dark:border-gray-700 p-4"
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

<script>
    (function () {
        // PAKSA posisi banner lewat JS, bypass CSS "bottom" yang di sebagian
        // device/browser (khususnya PWA standalone di iOS Safari) bisa
        // dihitung salah oleh engine. Kita hitung posisi "top" secara manual
        // dari tinggi viewport yang sebenarnya (termasuk visualViewport,
        // lebih akurat saat address bar collapse/expand), lalu paksa dengan
        // !important supaya tidak bisa ketimpa apapun.
        function forceBannerPosition() {
            const banner = document.getElementById('fcm-permission-banner');
            if (!banner || banner.classList.contains('hidden')) return;

            const vv = window.visualViewport;
            const viewportHeight = vv ? vv.height : window.innerHeight;
            const viewportOffsetTop = vv ? vv.offsetTop : 0;
            const margin = 16; // sama dengan "bottom-4" Tailwind (1rem)

            const bannerHeight = banner.offsetHeight || 140;
            const top = viewportOffsetTop + viewportHeight - bannerHeight - margin;

            banner.style.setProperty('position', 'fixed', 'important');
            banner.style.setProperty('bottom', 'auto', 'important');
            banner.style.setProperty('top', top + 'px', 'important');
        }

        window.addEventListener('resize', forceBannerPosition);
        window.addEventListener('scroll', forceBannerPosition, { passive: true });
        window.addEventListener('orientationchange', forceBannerPosition);
        if (window.visualViewport) {
            window.visualViewport.addEventListener('resize', forceBannerPosition);
            window.visualViewport.addEventListener('scroll', forceBannerPosition);
        }
        // Jaga-jaga: layout bisa berubah beberapa saat setelah banner
        // ditampilkan (font load, dsb), jadi dicoba ulang beberapa kali.
        [0, 100, 300, 800, 1500, 3000].forEach((delay) => setTimeout(forceBannerPosition, delay));

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
                forceBannerPosition();
                return;
            }

            if (!('Notification' in window)) {
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

            if (currentPermission === 'default' && (!dismissedInSession || isIos)) {
                banner.classList.remove('hidden');
                forceBannerPosition();
            } else if (currentPermission === 'denied') {
                title.textContent = 'Notifikasi diblokir';
                message.textContent = 'Buka Settings > Notifications, pilih aplikasi ini, lalu aktifkan Allow Notifications. Setelah itu buka ulang aplikasi.';
                actions.classList.add('hidden');
                banner.classList.remove('hidden');
                forceBannerPosition();
            }

            enableBtn.addEventListener('click', function () {
                // Safari iOS membutuhkan requestPermission() tepat di dalam
                // handler tap. Registrasi FCM dilakukan setelah izin selesai.
                if (!('Notification' in window) || typeof window.initFcm !== 'function') {
                    return;
                }

                Notification.requestPermission().then((permission) => {
                    banner.classList.add('hidden');
                    if (permission === 'granted') {
                        window.initFcm();
                    }
                }).catch(() => {
                    // Popup izin gagal dibuka - biarkan banner tetap tampil
                    // supaya user bisa coba tap lagi.
                });
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
            window.addEventListener('fcm:permission-denied', () => banner.classList.add('hidden'));
        } catch (fatalError) {
            console.error('[FCM] Notification banner error:', fatalError);
        }
    })();
</script>
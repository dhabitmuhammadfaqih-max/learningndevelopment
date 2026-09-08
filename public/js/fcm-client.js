/*
 * Client-side FCM Web Push.
 *
 * File statis (tidak lewat Vite) supaya bisa dipakai langsung dari
 * dashboard-layout.blade.php tanpa perlu build step tambahan - konsisten
 * dengan public/firebase-messaging-sw.js.
 *
 * Membutuhkan window.__FCM_CONFIG__ (diisi inline oleh Blade, lihat
 * resources/views/partials/fcm-scripts.blade.php) dan Firebase compat SDK
 * sudah dimuat lewat <script> CDN sebelum file ini.
 */
(function () {
    const config = window.__FCM_CONFIG__;

    if (!config || !config.apiKey || !config.projectId) {
        console.warn('[FCM] Config Firebase belum lengkap - FCM Web Push tidak diaktifkan.');
        return;
    }

    if (!('serviceWorker' in navigator) || !('Notification' in window)) {
        console.warn('[FCM] Browser ini tidak mendukung service worker / Notification API.');
        return;
    }

    firebase.initializeApp(config);
    const messaging = firebase.messaging();

    function registerServiceWorker() {
        // Kirim config Firebase lewat query string ke service worker
        // (lihat public/firebase-messaging-sw.js).
        const params = new URLSearchParams({
            apiKey: config.apiKey,
            authDomain: config.authDomain || '',
            projectId: config.projectId,
            storageBucket: config.storageBucket || '',
            messagingSenderId: config.messagingSenderId || '',
            appId: config.appId || '',
        });

        return navigator.serviceWorker.register(`/firebase-messaging-sw.js?${params.toString()}`);
    }

    function sendTokenToBackend(token) {
        return fetch(config.storeTokenUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': config.csrfToken,
            },
            body: JSON.stringify({ token }),
        }).then((res) => {
            if (!res.ok) {
                throw new Error(`Gagal menyimpan token ke server (HTTP ${res.status})`);
            }
            return res.json();
        });
    }

    async function initFcm() {
        try {
            const registration = await registerServiceWorker();

            const permission = await Notification.requestPermission();

            if (permission !== 'granted') {
                console.warn('[FCM] Izin notifikasi ditolak/belum diberikan oleh user.');
                window.dispatchEvent(new CustomEvent('fcm:permission-denied'));
                return;
            }

            const token = await messaging.getToken({
                vapidKey: config.vapidKey,
                serviceWorkerRegistration: registration,
            });

            if (!token) {
                console.error('[FCM] getToken() tidak mengembalikan token.');
                window.dispatchEvent(new CustomEvent('fcm:token-failed'));
                return;
            }

            await sendTokenToBackend(token);

            window.__FCM_TOKEN__ = token;
            window.dispatchEvent(new CustomEvent('fcm:ready', { detail: { token } }));
        } catch (error) {
            console.error('[FCM] Gagal inisialisasi push notification:', error);
            window.dispatchEvent(new CustomEvent('fcm:error', { detail: { error } }));
        }
    }

    // Notifikasi saat TAB SEDANG AKTIF (foreground) - tidak lewat service
    // worker, jadi ditampilkan manual lewat Notification API.
    //
    // PENTING: server mengirim data-only message (tanpa field
    // `notification`) supaya tidak dobel dengan showNotification() di
    // firebase-messaging-sw.js saat background - lihat catatan di
    // FirebaseCloudMessagingService::sendToToken(). Karena itu title/body
    // dibaca dari payload.data, BUKAN payload.notification.
    messaging.onMessage((payload) => {
        const title = payload.data?.title || 'Notifikasi';
        const body = payload.data?.body || '';
        const url = payload.fcmOptions?.link || payload.data?.url;

        if (Notification.permission === 'granted') {
            const notif = new Notification(title, {
                body,
                icon: '/images/logo-dagsap.png',
            });

            if (url) {
                notif.onclick = () => {
                    window.focus();
                    window.location.href = url;
                };
            }
        }
    });

    window.initFcm = initFcm;

    // Registrasi token otomatis saat halaman dashboard dimuat (dulu ini
    // hanya terjadi lewat tombol "Test Notifikasi" yang sudah dihapus).
    // Tanpa ini, tidak ada token FCM yang pernah tersimpan ke database,
    // sehingga notification "Penilaian Baru Tersedia" tidak akan pernah
    // sampai ke siapapun.
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFcm);
    } else {
        initFcm();
    }
})();
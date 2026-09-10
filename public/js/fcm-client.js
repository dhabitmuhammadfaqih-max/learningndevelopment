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
 *
 * CATATAN PENTING (Safari iOS):
 * Safari di iOS HANYA mengizinkan Notification.requestPermission()
 * dipanggil sebagai respons LANGSUNG dari user gesture (tap/klik).
 * Kalau dipanggil otomatis saat halaman load, atau dipanggil setelah
 * `await` lain (mis. registrasi service worker), Safari akan menolak
 * diam-diam TANPA menampilkan popup izin sama sekali. Karena itu:
 *   - initFcm() TIDAK dipanggil otomatis saat page load lagi.
 *   - initFcm() dipanggil dari onclick tombol "Aktifkan Notifikasi"
 *     (lihat notification-permission-button.blade.php), dan
 *     requestPermission() dipanggil PALING AWAL di initFcm(), sebelum
 *     `await` apapun, supaya masih dianggap "dalam" user gesture oleh
 *     Safari.
 *   - Kalau izin sudah granted sebelumnya (dari kunjungan lalu), token
 *     tetap di-refresh otomatis saat page load - itu tidak butuh popup
 *     baru jadi aman dipanggil otomatis.
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

    // Dipakai ulang oleh foreground handler (onMessage) supaya bisa pakai
    // registration.showNotification() - BUKAN `new Notification()`, karena
    // constructor itu tidak didukung sama sekali di Safari iOS (baik tab
    // biasa maupun PWA standalone).
    let swRegistration = null;

    function registerServiceWorker() {
        if (swRegistration) {
            return Promise.resolve(swRegistration);
        }

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

        return navigator.serviceWorker.register(`/firebase-messaging-sw.js?${params.toString()}`)
            .then((registration) => {
                swRegistration = registration;
                return registration;
            });
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
            // WAJIB paling awal, sebelum `await` apapun - lihat catatan di
            // atas soal user gesture requirement Safari iOS.
            const permission = await Notification.requestPermission();

            if (permission !== 'granted') {
                console.warn('[FCM] Izin notifikasi ditolak/belum diberikan oleh user.');
                window.dispatchEvent(new CustomEvent('fcm:permission-denied'));
                return;
            }

            const registration = await registerServiceWorker();

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

    // Notifikasi saat TAB SEDANG AKTIF (foreground) - tidak lewat event
    // background service worker, jadi ditampilkan manual di sini.
    //
    // PENTING: pakai registration.showNotification(), BUKAN
    // `new Notification()`. Constructor Notification() TIDAK didukung di
    // Safari iOS sama sekali (selalu throw), sedangkan
    // ServiceWorkerRegistration.showNotification() didukung di semua
    // browser modern termasuk Safari iOS 16.4+.
    //
    // Server mengirim data-only message (tanpa field `notification`)
    // supaya tidak dobel dengan handler background di
    // firebase-messaging-sw.js - lihat catatan di
    // FirebaseCloudMessagingService::sendToToken(). Karena itu title/body
    // dibaca dari payload.data, BUKAN payload.notification.
    messaging.onMessage((payload) => {
        if (Notification.permission !== 'granted' || !swRegistration) {
            return;
        }

        const title = payload.data?.title || 'Notifikasi';
        const body = payload.data?.body || '';
        const url = payload.fcmOptions?.link || payload.data?.url || '/';

        swRegistration.showNotification(title, {
            body,
            icon: '/images/logo-dagsap.png',
            data: { url },
        });
    });

    window.initFcm = initFcm;

    // Kalau izin SUDAH granted dari kunjungan sebelumnya, refresh token
    // secara otomatis saat halaman dimuat - ini AMAN dipanggil otomatis
    // karena tidak akan memunculkan popup baru (izin sudah ada).
    // Kalau izin masih 'default' (belum pernah ditanya) atau 'denied',
    // JANGAN dipanggil otomatis - tunggu user tap tombol "Aktifkan
    // Notifikasi" supaya popup izin bisa muncul di Safari iOS.
    function autoInitIfAlreadyGranted() {
        if (Notification.permission === 'granted') {
            initFcm();
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', autoInitIfAlreadyGranted);
    } else {
        autoInitIfAlreadyGranted();
    }
})();

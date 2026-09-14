/*
 * Service Worker Firebase Cloud Messaging.
 *
 * File ini HARUS berada di root domain (public/firebase-messaging-sw.js)
 * supaya scope-nya mencakup seluruh halaman aplikasi.
 *
 * Karena file ini statis (tidak diproses Vite/Blade), config Firebase
 * (yang memang aman untuk client-side) dikirim lewat query string saat
 * registrasi - lihat public/js/fcm-client.js -> registerServiceWorker().
 * Ini pola standar untuk pasang FCM Web Push dengan config dinamis per
 * environment (local/staging/production) tanpa hardcode.
 *
 * Pakai Firebase SDK versi "compat" (bukan modular) karena importScripts()
 * di service worker tidak mendukung ES module secara luas di semua
 * browser Android/Chrome saat ini - compat build paling stabil untuk SW.
 */

importScripts('https://www.gstatic.com/firebasejs/10.14.1/firebase-app-compat.js');
importScripts('https://www.gstatic.com/firebasejs/10.14.1/firebase-messaging-compat.js');

const params = new URLSearchParams(self.location.search);

const firebaseConfig = {
    apiKey: params.get('apiKey'),
    authDomain: params.get('authDomain'),
    projectId: params.get('projectId'),
    storageBucket: params.get('storageBucket'),
    messagingSenderId: params.get('messagingSenderId'),
    appId: params.get('appId'),
};

// Kalau config belum lengkap (mis. .env belum diisi), jangan crash -
// cukup hentikan inisialisasi Firebase di service worker ini.
if (firebaseConfig.apiKey && firebaseConfig.projectId) {
    firebase.initializeApp(firebaseConfig);

    const messaging = firebase.messaging();

    // Notifikasi saat TAB TIDAK AKTIF / browser di background.
    //
    // Server mengirim data-only payload (lihat
    // FirebaseCloudMessagingService::sendToToken()) - sengaja BUKAN
    // notification+data, supaya browser/OS tidak ikut auto-display
    // sendiri di level platform. Ini satu-satunya tempat yang menampilkan
    // notifikasi untuk kondisi background, persis sekali per push.
    self.addEventListener('push', (event) => {
        if (!event.data) {
            return;
        }

        let payload;
        try {
            payload = event.data.json();
        } catch (e) {
            return;
        }

        // Jaga-jaga kalau suatu saat ada pengirim lain (bukan
        // FirebaseCloudMessagingService di atas) yang masih mengirim
        // notification payload ke token yang sama - jangan tampilkan
        // dobel dengan auto-display bawaan FCM untuk notification message.
        if (payload.notification) {
            return;
        }

        const data = payload.data || {};
        const title = data.title || 'Notifikasi';
        const body = data.body || '';
        const url = data.url || '/';

        event.waitUntil(
            self.registration.showNotification(title, {
                body,
                icon: '/images/logo-dagsap.png',
                data: { url },
            })
        );
    });

    // Klik notifikasi -> fokuskan tab yang sudah terbuka, atau buka tab baru
    // ke dashboard aplikasi.
    self.addEventListener('notificationclick', (event) => {
        event.notification.close();

        const targetUrl = event.notification.data?.url || '/';

        event.waitUntil(
            clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windowClients) => {
                for (const client of windowClients) {
                    if (client.url === targetUrl && 'focus' in client) {
                        return client.focus();
                    }
                }

                if (clients.openWindow) {
                    return clients.openWindow(targetUrl);
                }
            })
        );
    });
} else {
    console.warn('[FCM SW] Firebase config tidak lengkap - service worker tidak diinisialisasi.');
}
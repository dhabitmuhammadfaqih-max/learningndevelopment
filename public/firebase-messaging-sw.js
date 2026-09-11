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
    // PENTING: server SENGAJA mengirim data-only message (tidak ada
    // field `notification`) - lihat catatan di
    // FirebaseCloudMessagingService::sendToToken(). Kalau payload
    // punya field `notification`, browser otomatis menampilkan
    // notifikasi sendiri SELAIN showNotification() manual di bawah ini,
    // sehingga satu push muncul sebagai DUA notifikasi (bug "double
    // kirim"). Selama server tetap kirim data-only, baca title/body
    // dari payload.data, BUKAN payload.notification.
    //
    // CATATAN (iOS fix): onBackgroundMessage() terindikasi TIDAK selalu
    // ke-trigger di Safari iOS untuk data-only message (walau jalan
    // normal di Chrome/Android). Karena itu ditambahkan juga listener
    // 'push' manual di bawah sebagai jalur alternatif supaya notifikasi
    // tetap muncul di iOS. Kalau dua-duanya ke-trigger di platform yang
    // sama, ada risiko notifikasi tampil dobel - lihat catatan di bawah
    // listener 'push'.
    messaging.onBackgroundMessage((payload) => {
        const title = payload.data?.title || 'Notifikasi';
        const body = payload.data?.body || '';
        const url = payload.fcmOptions?.link || payload.data?.url || '/';

        self.registration.showNotification(title, {
            body,
            icon: '/images/logo-dagsap.png',
            data: { url },
        });
    });

    // FALLBACK untuk Safari iOS: listener 'push' manual, bypass parsing
    // internal Firebase SDK yang terindikasi tidak reliable di iOS untuk
    // data-only message. Payload push dari FCM diparse langsung dari
    // event.data.
    //
    // SEMENTARA dipasang BARENGAN dengan onBackgroundMessage() di atas
    // untuk keperluan testing/perbandingan platform:
    // - Kalau di iPhone notifikasi jadi MUNCUL setelah ini ditambahkan,
    //   berarti terbukti onBackgroundMessage() yang bermasalah di iOS.
    // - Kalau di Android/desktop notifikasi jadi MUNCUL DOBEL (2x),
    //   berarti kedua listener ini sama-sama ke-trigger di Chrome, dan
    //   blok messaging.onBackgroundMessage() di atas HARUS dihapus,
    //   cukup pakai listener 'push' manual ini saja untuk semua platform.
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
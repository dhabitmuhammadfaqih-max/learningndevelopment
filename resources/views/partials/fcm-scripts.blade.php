{{-- FCM Web Push: dimuat lewat CDN (compat build) supaya tidak perlu
     tambahan dependency npm/Vite. Hanya dimuat untuk user yang sudah
     login (dashboard-layout selalu untuk halaman auth). --}}
<script src="https://www.gstatic.com/firebasejs/10.14.1/firebase-app-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/10.14.1/firebase-messaging-compat.js"></script>

<script>
    // Config Firebase Web (aman untuk client-side, BUKAN credential rahasia).
    window.__FCM_CONFIG__ = {
        apiKey: @json(config('firebase.web.api_key')),
        authDomain: @json(config('firebase.web.auth_domain')),
        projectId: @json(config('firebase.web.project_id')),
        storageBucket: @json(config('firebase.web.storage_bucket')),
        messagingSenderId: @json(config('firebase.web.messaging_sender_id')),
        appId: @json(config('firebase.web.app_id')),
        vapidKey: @json(config('firebase.web.vapid_key')),
        csrfToken: @json(csrf_token()),
        storeTokenUrl: @json(route('fcm.token.store')),
    };
</script>
<script src="{{ asset('js/fcm-client.js') }}"></script>

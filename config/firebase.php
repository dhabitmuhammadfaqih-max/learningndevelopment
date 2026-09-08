<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Firebase Server Credential (RAHASIA - untuk backend Laravel saja)
    |--------------------------------------------------------------------------
    | Diambil dari Service Account JSON di Firebase Console:
    | Project Settings > Service Accounts > Generate new private key.
    | JANGAN commit nilai asli ke Git - isi hanya lewat .env.
    */
    'project_id' => env('FIREBASE_PROJECT_ID'),
    'client_email' => env('FIREBASE_CLIENT_EMAIL'),

    // Private key sering mengandung "\n" literal saat disimpan di .env,
    // jadi perlu di-convert ke newline asli sebelum dipakai sign JWT.
    'private_key' => str_replace('\\n', "\n", (string) env('FIREBASE_PRIVATE_KEY')),

    /*
    |--------------------------------------------------------------------------
    | Firebase Web App Config (AMAN untuk client-side/browser)
    |--------------------------------------------------------------------------
    | Diambil dari Firebase Console: Project Settings > General > Your apps
    | > Web app > SDK setup and configuration. Nilai-nilai ini memang
    | didesain Firebase untuk terlihat oleh publik/browser - BUKAN secret.
    */
    'web' => [
        'api_key' => env('FIREBASE_WEB_API_KEY'),
        'auth_domain' => env('FIREBASE_WEB_AUTH_DOMAIN'),
        'project_id' => env('FIREBASE_PROJECT_ID'),
        'storage_bucket' => env('FIREBASE_WEB_STORAGE_BUCKET'),
        'messaging_sender_id' => env('FIREBASE_WEB_MESSAGING_SENDER_ID'),
        'app_id' => env('FIREBASE_WEB_APP_ID'),

        // VAPID key = "Web Push certificates" key pair di Firebase Console
        // > Project Settings > Cloud Messaging > Web configuration.
        // Dipakai browser saat generate registration token (getToken()).
        'vapid_key' => env('FIREBASE_VAPID_KEY'),
    ],

];

<?php

namespace App\Services;

use App\Models\FcmToken;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Kirim push notification lewat Firebase Cloud Messaging HTTP v1 API.
 *
 * Sengaja TIDAK memakai package pihak ketiga (mis. kreait/laravel-firebase)
 * supaya tidak ada risiko konflik dependency dengan Laravel 13 / PHP 8.3
 * yang masih baru. Implementasi ini hanya memakai:
 * - openssl (bawaan PHP) untuk sign JWT (OAuth2 service account flow)
 * - Illuminate\Support\Facades\Http (Guzzle, sudah ada di Laravel)
 *
 * Alur:
 * 1) Buat JWT ditandatangani private key service account (RS256).
 * 2) Tukar JWT itu dengan access token OAuth2 dari Google
 *    (grant_type=urn:ietf:params:oauth:grant-type:jwt-bearer).
 * 3) Access token dipakai untuk memanggil
 *    https://fcm.googleapis.com/v1/projects/{project_id}/messages:send
 *
 * Access token di-cache (default expired ~1 jam dari Google) supaya tidak
 * generate token baru di setiap request.
 */
class FirebaseCloudMessagingService
{
    private const TOKEN_CACHE_KEY = 'firebase_fcm_access_token';

    private const OAUTH_SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    private const OAUTH_TOKEN_URL = 'https://oauth2.googleapis.com/token';

    /**
     * Kirim notifikasi ke SEMUA device/browser (FcmToken) milik satu user.
     * Token yang sudah tidak valid (UNREGISTERED / NOT_FOUND) otomatis
     * dihapus dari database.
     *
     * @param  array<string,string>  $data  Payload tambahan (opsional), mis. ['url' => route(...)]
     * @return array{sent:int, failed:int, no_token:bool}
     */
    public function sendToUser(User $user, string $title, string $body, array $data = []): array
    {
        // Simpan juga ke inbox notifikasi in-app terlepas dari ada/tidaknya
        // token push terdaftar - supaya user yang belum mengizinkan
        // notifikasi browser (atau device-nya tidak dapat token, mis. Mi
        // Browser tanpa Google Play Services / Safari) tetap kebagian
        // notifikasi ini saat membuka halaman "Notifikasi" di web.
        try {
            \App\Models\Notification::create([
                'user_id' => $user->id,
                'title'   => $title,
                'body'    => $body,
                'url'     => $data['url'] ?? null,
            ]);
        } catch (\Throwable $e) {
            Log::error('FCM: gagal menyimpan notifikasi in-app.', [
                'user_id' => $user->id,
                'error'   => $e->getMessage(),
            ]);
        }

        $tokens = $user->fcmTokens()->pluck('token', 'id');

        if ($tokens->isEmpty()) {
            Log::warning('FCM: user tidak punya token terdaftar.', ['user_id' => $user->id]);

            return ['sent' => 0, 'failed' => 0, 'no_token' => true];
        }

        $sent = 0;
        $failed = 0;

        foreach ($tokens as $fcmTokenId => $token) {
            $result = $this->sendToToken($token, $title, $body, $data);

            if ($result === true) {
                $sent++;
                FcmToken::whereKey($fcmTokenId)->update(['last_used_at' => now()]);
            } else {
                $failed++;

                // Token sudah tidak valid di sisi Google -> bersihkan dari DB
                // supaya tidak terus dicoba kirim ke token mati.
                if ($result === 'invalid_token') {
                    FcmToken::whereKey($fcmTokenId)->delete();

                    Log::info('FCM: token tidak valid, dihapus dari database.', [
                        'user_id' => $user->id,
                        'fcm_token_id' => $fcmTokenId,
                    ]);
                }
            }
        }

        return ['sent' => $sent, 'failed' => $failed, 'no_token' => false];
    }

    /**
     * Kirim notifikasi ke satu registration token.
     *
     * @return true|'invalid_token'|'error'
     */
    public function sendToToken(string $token, string $title, string $body, array $data = []): true|string
    {
        $accessToken = $this->getAccessToken();

        if ($accessToken === null) {
            return 'error';
        }

        $projectId = config('firebase.project_id');

        // PHP array kosong di-encode json_encode() sebagai `[]` (list),
        // padahal FCM HTTP v1 API mewajibkan field `data` dan
        // `webpush.fcm_options` berupa objek `{}` (map) - walau isinya
        // kosong (mis. saat $data tidak berisi 'url'). Kalau dibiarkan
        // array kosong, Google menolak dengan 400 INVALID_ARGUMENT
        // ("Cannot bind a list to map"), dan sebelumnya error ini malah
        // membuat token yang sebenarnya valid ikut terhapus dari
        // database (lihat pengecekan invalid_token di bawah). Cast ke
        // stdClass kosong supaya selalu ke-encode sebagai objek.
        //
        // SENGAJA data-only (TIDAK ada key `message.notification` /
        // `webpush.notification`) - lihat PENTING di bawah.
        $dataPayload = array_map('strval', array_merge($data, [
            'title' => $title,
            'body' => $body,
        ]));
        $fcmOptions = array_filter(['link' => $data['url'] ?? null]);

        // PENTING - JANGAN tambahkan kembali key `notification` di sini
        // (baik `message.notification` maupun `webpush.notification`).
        //
        // Sebelumnya title/body dikirim lewat `message.notification`.
        // Begitu payload FCM punya field `notification`, browser
        // (lewat service worker) OTOMATIS menampilkan notifikasi
        // sendiri di background - TAPI firebase-messaging-sw.js di
        // project ini JUGA memanggil showNotification() secara manual
        // di onBackgroundMessage(). Kombinasi keduanya menyebabkan SATU
        // push dari server muncul sebagai DUA notifikasi di device
        // (notifikasi "double kirim"/dobel).
        //
        // Fix: kirim data-only message. title/body dilewatkan lewat
        // `data` saja, lalu ditampilkan manual SEKALI oleh
        // onBackgroundMessage() di service worker (lihat
        // public/firebase-messaging-sw.js) maupun onMessage() di
        // public/js/fcm-client.js saat tab aktif.
        $payload = [
            'message' => [
                'token' => $token,
                'data' => empty($dataPayload) ? new \stdClass() : $dataPayload,
                'webpush' => [
                    'fcm_options' => empty($fcmOptions) ? new \stdClass() : $fcmOptions,
                ],
            ],
        ];

        try {
            $response = Http::withToken($accessToken)
                ->timeout(10)
                ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", $payload);

            if ($response->successful()) {
                return true;
            }

            $errorStatus = data_get($response->json(), 'error.status');

            Log::error('FCM: gagal mengirim notifikasi.', [
                'status_code' => $response->status(),
                'error_status' => $errorStatus,
                'body' => $response->json(),
            ]);

            // INVALID_ARGUMENT sengaja DIKELUARKAN dari daftar ini -
            // status ini berarti PAYLOAD yang salah (bug di sisi kita,
            // seperti kasus di atas), BUKAN berarti registration token-nya
            // tidak valid. Menghapus token karena INVALID_ARGUMENT bisa
            // menghapus token yang sebenarnya sehat hanya karena ada bug
            // payload yang tidak berkaitan sama sekali dengan token itu.
            // Token yang BENAR-BENAR sudah tidak terdaftar di Google akan
            // selalu pulang sebagai UNREGISTERED atau NOT_FOUND.
            if (in_array($errorStatus, ['UNREGISTERED', 'NOT_FOUND'], true)) {
                return 'invalid_token';
            }

            return 'error';
        } catch (\Throwable $e) {
            Log::error('FCM: exception saat mengirim notifikasi.', [
                'message' => $e->getMessage(),
            ]);

            return 'error';
        }
    }

    /**
     * Ambil OAuth2 access token (di-cache) untuk otentikasi ke FCM HTTP v1.
     * Return null kalau credential belum lengkap di .env atau gagal ambil
     * token - caller wajib menangani null ini tanpa membuat app crash.
     */
    private function getAccessToken(): ?string
    {
        return Cache::remember(self::TOKEN_CACHE_KEY, now()->addMinutes(50), function () {
            $projectId = config('firebase.project_id');
            $clientEmail = config('firebase.client_email');
            $privateKey = config('firebase.private_key');

            if (! $projectId || ! $clientEmail || ! $privateKey) {
                Log::error('FCM: credential Firebase belum lengkap di .env (FIREBASE_PROJECT_ID / FIREBASE_CLIENT_EMAIL / FIREBASE_PRIVATE_KEY).');

                // Jangan cache null - biar dicoba lagi tiap request sampai
                // credential diisi.
                Cache::forget(self::TOKEN_CACHE_KEY);

                return null;
            }

            $jwt = $this->buildSignedJwt($clientEmail, $privateKey);

            if ($jwt === null) {
                Cache::forget(self::TOKEN_CACHE_KEY);

                return null;
            }

            try {
                $response = Http::asForm()->timeout(10)->post(self::OAUTH_TOKEN_URL, [
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $jwt,
                ]);

                if (! $response->successful()) {
                    Log::error('FCM: gagal menukar JWT dengan access token OAuth2.', [
                        'status' => $response->status(),
                        'body' => $response->json(),
                    ]);

                    Cache::forget(self::TOKEN_CACHE_KEY);

                    return null;
                }

                return $response->json('access_token');
            } catch (\Throwable $e) {
                Log::error('FCM: exception saat mengambil access token.', ['message' => $e->getMessage()]);

                Cache::forget(self::TOKEN_CACHE_KEY);

                return null;
            }
        });
    }

    /**
     * Buat & tandatangani JWT (RS256) untuk service account OAuth2 flow.
     * Return null kalau private key gagal di-parse (mis. format .env salah).
     */
    private function buildSignedJwt(string $clientEmail, string $privateKey): ?string
    {
        $now = time();

        $header = [
            'alg' => 'RS256',
            'typ' => 'JWT',
        ];

        $claims = [
            'iss' => $clientEmail,
            'scope' => self::OAUTH_SCOPE,
            'aud' => self::OAUTH_TOKEN_URL,
            'iat' => $now,
            'exp' => $now + 3600,
        ];

        $segments = [
            $this->base64UrlEncode(json_encode($header)),
            $this->base64UrlEncode(json_encode($claims)),
        ];

        $signingInput = implode('.', $segments);

        $privateKeyResource = openssl_pkey_get_private($privateKey);

        if ($privateKeyResource === false) {
            Log::error('FCM: FIREBASE_PRIVATE_KEY tidak valid / gagal di-parse openssl.');

            return null;
        }

        $signature = '';
        $signed = openssl_sign($signingInput, $signature, $privateKeyResource, OPENSSL_ALGO_SHA256);

        if (! $signed) {
            Log::error('FCM: gagal menandatangani JWT dengan private key.');

            return null;
        }

        $segments[] = $this->base64UrlEncode($signature);

        return implode('.', $segments);
    }

    private function base64UrlEncode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
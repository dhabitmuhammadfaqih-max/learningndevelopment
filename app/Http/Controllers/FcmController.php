<?php

namespace App\Http\Controllers;

use App\Models\FcmToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Endpoint FCM Web Push:
 * - simpan/registrasi token milik user yang sedang login
 * - hapus token
 *
 * Semua route di sini WAJIB lewat middleware 'auth' (didaftarkan di
 * routes/web.php), jadi tidak perlu cek auth manual di sini.
 */
class FcmController extends Controller
{
    /**
     * Simpan/registrasi FCM token dari browser/HP untuk user yang login.
     * Dipanggil otomatis oleh JS setelah permission notifikasi diizinkan
     * dan token berhasil didapat dari Firebase Messaging SDK.
     */
    public function storeToken(Request $request)
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'max:255'],
        ]);

        try {
            $token = FcmToken::updateOrCreate(
                ['token' => $validated['token']],
                [
                    'user_id' => $request->user()->id,
                    'user_agent' => substr((string) $request->userAgent(), 0, 255),
                    'device_label' => $this->guessDeviceLabel((string) $request->userAgent()),
                    'last_used_at' => now(),
                ]
            );

            return response()->json(['message' => 'Token tersimpan.', 'id' => $token->id]);
        } catch (\Throwable $e) {
            Log::error('FCM: gagal menyimpan token ke database.', [
                'user_id' => $request->user()->id,
                'message' => $e->getMessage(),
            ]);

            return response()->json(['message' => 'Gagal menyimpan token.'], 500);
        }
    }

    /**
     * Hapus token tertentu (mis. dipanggil browser saat token dianggap
     * kadaluarsa di sisi client, atau user logout dari device ini).
     */
    public function destroyToken(Request $request)
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'max:255'],
        ]);

        FcmToken::where('user_id', $request->user()->id)
            ->where('token', $validated['token'])
            ->delete();

        return response()->json(['message' => 'Token dihapus.']);
    }

    private function guessDeviceLabel(string $userAgent): string
    {
        $browser = match (true) {
            str_contains($userAgent, 'Edg/') => 'Edge',
            str_contains($userAgent, 'Chrome/') => 'Chrome',
            str_contains($userAgent, 'Firefox/') => 'Firefox',
            str_contains($userAgent, 'Safari/') => 'Safari',
            default => 'Browser',
        };

        $platform = match (true) {
            str_contains($userAgent, 'Android') => 'Android',
            str_contains($userAgent, 'iPhone') || str_contains($userAgent, 'iPad') => 'iOS',
            str_contains($userAgent, 'Windows') => 'Windows',
            str_contains($userAgent, 'Macintosh') => 'Mac',
            str_contains($userAgent, 'Linux') => 'Linux',
            default => '',
        };

        return trim("{$browser} - {$platform}", ' -') ?: 'Device tidak dikenali';
    }
}

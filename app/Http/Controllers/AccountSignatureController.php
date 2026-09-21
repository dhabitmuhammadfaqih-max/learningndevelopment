<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * Tanda tangan "akun" - disimpan SEKALI per user (lihat
 * User::signature_path), lalu dipakai ulang otomatis oleh semua alur
 * tanda tangan (tanggapan pegawai, penilaian pejabat/atasan, pengesahan
 * HRD, dsb - lihat App\Support\AccountSignature). Karena disimpan di
 * kolom akun (database), tanda tangan ini ikut kemanapun user login,
 * bukan tersimpan di browser/device tertentu.
 */
class AccountSignatureController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'signature' => ['required', 'string'],
        ]);

        if (! preg_match('/^data:image\/png;base64,/', $validated['signature'])) {
            return response()->json(['message' => 'Format tanda tangan tidak valid.'], 422);
        }

        $imageContent = base64_decode(substr($validated['signature'], strpos($validated['signature'], ',') + 1));

        $user = Auth::user();

        // Hapus file lama kalau user memang sedang memperbarui tanda
        // tangannya (bukan mengisi untuk pertama kali).
        if ($user->signature_path && Storage::disk('public')->exists($user->signature_path)) {
            Storage::disk('public')->delete($user->signature_path);
        }

        $path = 'signatures/account/user_' . $user->id . '_' . time() . '.png';
        Storage::disk('public')->put($path, $imageContent);

        $user->update([
            'signature_path' => $path,
            'signature_saved_at' => now(),
        ]);

        return response()->json([
            'message' => 'Tanda tangan berhasil disimpan.',
            'signature_url' => Storage::disk('public')->url($path),
        ]);
    }
}

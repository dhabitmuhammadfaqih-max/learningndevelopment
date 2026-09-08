<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menyimpan FCM registration token per user, per device/browser.
     * Satu user bisa punya banyak token (banyak device/browser login
     * bersamaan) - lihat FcmToken::class & FcmTokenController.
     *
     * PENTING: tabel ini HANYA menyimpan token publik dari browser/HP
     * (hasil getToken() Firebase Messaging JS). Firebase service account
     * credential (private key) TIDAK PERNAH disimpan di database - itu
     * hanya ada di .env (lihat config/firebase.php).
     */
    public function up(): void
    {
        Schema::create('fcm_tokens', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            // Token registrasi FCM dari browser/HP. Unique supaya satu
            // token fisik tidak dobel tersimpan untuk user yang sama
            // (mis. reload halaman berkali-kali).
            $table->string('token', 255)->unique();

            // Label bebas untuk membantu user/HRD mengenali device ini,
            // mis. "Chrome - Android". Diisi otomatis dari User-Agent.
            $table->string('device_label')->nullable();

            $table->string('user_agent')->nullable();

            // Kapan token ini terakhir kali berhasil dipakai mengirim
            // notifikasi / terakhir diregistrasi ulang oleh browser.
            $table->timestamp('last_used_at')->nullable();

            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fcm_tokens');
    }
};

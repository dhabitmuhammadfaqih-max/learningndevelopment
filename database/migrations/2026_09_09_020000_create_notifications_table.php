<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel inbox notifikasi in-app.
 *
 * BEDA dengan fcm_notification_logs: tabel itu cuma untuk mencegah
 * duplicate PUSH notification (tidak simpan judul/isi, unique per
 * kombinasi employee+supervisor+type). Tabel ini adalah inbox
 * sesungguhnya yang ditampilkan di halaman "Notifikasi" & dropdown
 * lonceng - satu baris = satu notifikasi yang pernah diterima user
 * tertentu, lengkap dengan judul/isi/link, dan status sudah/belum dibaca.
 *
 * Diisi dari titik yang sama dengan pengiriman push (lihat
 * FirebaseCloudMessagingService::sendToUser()), supaya user tetap
 * kebagian notifikasi di dalam web walau device-nya tidak punya token
 * FCM terdaftar / permission notifikasi belum diizinkan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('title', 255);
            $table->text('body');

            // Link tujuan saat notifikasi di-klik (opsional).
            $table->string('url', 255)->nullable();

            $table->timestamp('read_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};

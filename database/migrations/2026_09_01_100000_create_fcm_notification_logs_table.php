<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Log notifikasi FCM yang sudah dikirim, untuk mencegah duplicate.
     *
     * Setiap baris merepresentasikan SATU notifikasi yang sudah pernah
     * dikirim ke supervisor tertentu untuk employee tertentu dengan jenis
     * notifikasi tertentu. Unique constraint di (employee_id, supervisor_id,
     * notification_type) memastikan notifikasi yang sama tidak dikirim
     * dua kali selama data ini masih ada.
     *
     * Jika siklus penilaian direset (mis. tahun baru), HRD bisa menghapus
     * baris-baris lama agar notifikasi bisa dikirim lagi untuk siklus baru.
     */
    public function up(): void
    {
        Schema::create('fcm_notification_logs', function (Blueprint $table) {
            $table->id();

            // Pegawai yang siap dinilai
            $table->foreignId('employee_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Supervisor / penilai yang menerima notifikasi
            $table->foreignId('supervisor_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Jenis notifikasi, mis. 'siap_dinilai_penilai'
            // Dibuat string agar bisa diperluas ke jenis notifikasi lain
            // tanpa harus migrasi ulang.
            $table->string('notification_type', 100);

            // Kapan notifikasi ini dikirim (bisa null jika belum dikirim
            // tapi slot sudah dipesan untuk mencegah race condition)
            $table->timestamp('sent_at')->useCurrent();

            $table->timestamps();

            // Satu kombinasi employee + supervisor + type hanya boleh
            // punya SATU baris - ini yang mencegah duplicate notification
            // untuk kondisi yang sama.
            $table->unique(
                ['employee_id', 'supervisor_id', 'notification_type'],
                'fcm_notif_log_unique'
            );

            $table->index('supervisor_id');
            $table->index('employee_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fcm_notification_logs');
    }
};

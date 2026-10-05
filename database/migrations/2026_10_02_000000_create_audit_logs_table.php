<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel Audit Log - catatan siapa melakukan apa, kapan, dari mana.
 *
 * Sengaja TIDAK memakai foreign key ke users: log harus tetap ada walau
 * akun pelakunya (atau akun yang diubah) sudah dihapus. Karena itu nama &
 * role pelaku disalin (snapshot) ke kolom actor_name/actor_role, dan
 * objeknya dirujuk lewat auditable_type (nama tabel) + auditable_id +
 * auditable_label (teks yang sudah jadi saat kejadian).
 *
 * Tidak ada kolom updated_at: baris log tidak pernah diubah.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            // Pelaku
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('actor_name')->nullable();
            $table->string('actor_role', 30)->nullable();

            // Kejadian
            $table->string('event', 40);
            $table->text('description');

            // Objek yang terkena (opsional - login/ekspor dsb. bisa tanpa objek)
            $table->string('auditable_type', 100)->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->string('auditable_label')->nullable();

            // Perubahan data & info tambahan
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->json('meta')->nullable();

            // Konteks request
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('method', 10)->nullable();
            $table->string('url')->nullable();

            $table->timestamp('created_at')->nullable();

            $table->index('created_at');
            $table->index('event');
            $table->index(['actor_id', 'created_at']);
            $table->index(['auditable_type', 'auditable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};

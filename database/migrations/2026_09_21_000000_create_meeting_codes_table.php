<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kode pertemuan sekali-pakai, PENGGANTI bukti selfie kamera untuk
     * checklist "sudah bertemu & evaluasi" metode OFFLINE (lihat
     * migration add_evidence_type_to_checklist_pertemuan_columns:
     * evidence_type 'selfie' -> sekarang 'kode').
     *
     * Alurnya (offline/tatap muka):
     * 1. PENILAI/ATASAN menekan "Generate Kode" di dashboard-nya -
     *    baris baru dibuat di sini dengan masa berlaku singkat
     *    (MeetingCode::VALID_MINUTES).
     * 2. Kode itu ditunjukkan langsung ke pegawai/pejabat yang sedang
     *    duduk bersamanya - inilah yang membuktikan mereka benar-benar
     *    bertemu, karena kode tidak pernah dikirim lewat sistem ke
     *    pihak yang memasukkannya.
     * 3. PEGAWAI/PEJABAT mengetik kode itu di dashboard-nya. Kalau
     *    cocok & belum kedaluwarsa, checklist KEDUA belah pihak
     *    langsung tercentang sekaligus (lihat
     *    EmployeeController::toggleChecklistPertemuan()) dan HRD
     *    diberi tahu lewat NotificationTriggerService.
     *
     * Kenapa tabel sendiri, bukan kolom di users: satu pasangan bisa
     * generate berkali-kali (kode kedaluwarsa, salah ketik, dibatalkan
     * lalu diulang), dan riwayatnya berguna untuk audit HRD. Kolom di
     * users hanya menyimpan kode FINAL yang berhasil dipakai
     * (*_konfirmasi_pertemuan_kode - lihat migration
     * add_kode_to_checklist_pertemuan_columns).
     */
    public function up(): void
    {
        Schema::create('meeting_codes', function (Blueprint $table) {
            $table->id();

            // Kode yang diketik manual oleh pegawai/pejabat. Sengaja
            // pendek (6 karakter) & tanpa karakter ambigu (0/O, 1/I/L)
            // supaya enak dibacakan langsung saat tatap muka - lihat
            // MeetingCode::CHARSET.
            $table->string('code', 8);

            // 'pegawai' = siklus penilaian pegawai (Evaluation),
            // 'pejabat' = siklus penilaian pejabat (OfficialEvaluation).
            // Dua siklus ini punya kolom checklist yang berbeda di
            // users, jadi kode-nya juga tidak boleh tertukar.
            $table->string('context', 20);

            // Pihak yang DINILAI - baris users tempat kolom checklist
            // disimpan (pola yang sama seperti
            // penilai_konfirmasi_pertemuan_at yang disimpan di baris
            // pegawai, bukan baris penilai).
            $table->foreignId('subject_id')->constrained('users')->cascadeOnDelete();

            // Pihak yang MENILAI & yang menekan tombol generate.
            $table->foreignId('issuer_id')->constrained('users')->cascadeOnDelete();

            // Tahun penilaian saat kode dibuat - checklist memang
            // per-tahun, lihat migration
            // add_tahun_to_checklist_pertemuan_columns.
            $table->unsignedSmallInteger('tahun');

            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->foreignId('used_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // Kode hanya perlu unik di antara kode yang MASIH hidup,
            // tapi unique index parsial tidak portabel antar database
            // (MySQL tidak punya). Dibikin unique penuh saja -
            // MeetingCode::issue() tinggal mengulang undian kalau
            // bentrok, dan ruang kodenya (32^6 ≈ 1 miliar) jauh lebih
            // dari cukup.
            $table->unique('code');

            // Dipakai MeetingCode::activeFor() untuk menampilkan
            // kembali kode yang masih berlaku saat halaman penilai
            // di-refresh (supaya tidak perlu generate ulang).
            $table->index(['subject_id', 'context', 'tahun']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meeting_codes');
    }
};

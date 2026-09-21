<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ubah alur MeetingCode dari "Penilai/Atasan generate sendiri &
     * tunjukkan tatap muka" menjadi "kedua pihak minta -> HRD yang
     * generate -> kode tampil di dashboard KEDUA pihak".
     *
     * Kenapa masih 1 baris per siklus (bukan tabel request terpisah):
     * baris yang sama dipakai dari awal minta sampai kode dipakai,
     * supaya riwayat & query activeFor()/redeem() yang sudah ada tetap
     * jalan tanpa join tambahan - lihat App\Models\MeetingCode.
     *
     * - subject_requested_at: PEGAWAI/PEJABAT (yang dinilai) menekan
     *   "Minta Kode".
     * - issuer_requested_at : PENILAI/ATASAN (yang menilai) menekan
     *   "Minta Kode".
     * - Baris baru dibuat begitu SALAH SATU pihak menekan duluan (kolom
     *   sisi yang belum minta tetap null). HRD hanya boleh generate
     *   setelah KEDUA kolom terisi - lihat MeetingCode::siapDigenerate()
     *   & HrdController::meetingCodeRequests().
     * - code/expires_at sekarang boleh null: baris "baru diminta, belum
     *   di-generate HRD" belum py kode sama sekali.
     * - generated_by/generated_at: HRD yang menekan "Buat Kode" & kapan -
     *   dipakai untuk riwayat/audit, sama seperti alasan tabel ini
     *   dibuat terpisah dari kolom users (lihat migration
     *   create_meeting_codes_table).
     */
    public function up(): void
    {
        Schema::table('meeting_codes', function (Blueprint $table) {
            $table->timestamp('subject_requested_at')->nullable()->after('tahun');
            $table->timestamp('issuer_requested_at')->nullable()->after('subject_requested_at');
            $table->foreignId('generated_by')->nullable()->after('expires_at')
                ->constrained('users')->nullOnDelete();
            $table->timestamp('generated_at')->nullable()->after('generated_by');
        });

        Schema::table('meeting_codes', function (Blueprint $table) {
            $table->string('code', 8)->nullable()->change();
            $table->timestamp('expires_at')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('meeting_codes', function (Blueprint $table) {
            $table->dropConstrainedForeignId('generated_by');
            $table->dropColumn(['subject_requested_at', 'issuer_requested_at', 'generated_at']);
        });

        Schema::table('meeting_codes', function (Blueprint $table) {
            $table->string('code', 8)->nullable(false)->change();
            $table->timestamp('expires_at')->nullable(false)->change();
        });
    }
};

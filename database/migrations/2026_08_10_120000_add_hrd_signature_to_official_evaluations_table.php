<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan tanda tangan HRD pada tabel official_evaluations,
     * sama seperti pola hrd_signature di tabel evaluations (lihat migrasi
     * add_employee_hrd_signature_to_evaluations_table). Dipakai untuk
     * menyimpan tanda tangan HRD pada penilaian pejabat, analog dengan
     * alur tanda tangan HRD pada penilaian pegawai.
     */
    public function up(): void
    {
        Schema::table('official_evaluations', function (Blueprint $table) {
            if (! Schema::hasColumn('official_evaluations', 'hrd_id')) {
                $table->foreignId('hrd_id')->nullable()->after('employee_signature')
                    ->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('official_evaluations', 'hrd_signature')) {
                $table->string('hrd_signature')->nullable()->after('hrd_id');
            }

            if (! Schema::hasColumn('official_evaluations', 'hrd_signed_at')) {
                $table->timestamp('hrd_signed_at')->nullable()->after('hrd_signature');
            }
        });
    }

    public function down(): void
    {
        Schema::table('official_evaluations', function (Blueprint $table) {
            if (Schema::hasColumn('official_evaluations', 'hrd_signed_at')) {
                $table->dropColumn('hrd_signed_at');
            }

            if (Schema::hasColumn('official_evaluations', 'hrd_signature')) {
                $table->dropColumn('hrd_signature');
            }

            if (Schema::hasColumn('official_evaluations', 'hrd_id')) {
                $table->dropConstrainedForeignId('hrd_id');
            }
        });
    }
};
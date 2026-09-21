<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom "metode pertemuan" (zoom/telpon/chat) yang HANYA relevan
     * saat checklist "sudah bertemu & evaluasi" dicentang dengan bukti
     * Online (evidence_type = 'upload' - lihat migration
     * add_evidence_type_to_checklist_pertemuan_columns).
     *
     * Sekadar keterangan/label tambahan - TIDAK punya file upload
     * sendiri-sendiri per metode, tetap memakai file yang sama di
     * kolom *_konfirmasi_pertemuan_selfie (existing). Dipakai untuk
     * menampilkan label "Bukti: Online (Zoom)" dsb ke HRD - lihat
     * User::checklistMeetingMethodLabel() &
     * resources/views/partials/checklist-selfie-toggle.blade.php.
     */
    public function up(): void
    {
        foreach (['pegawai', 'penilai', 'pejabat', 'atasan'] as $prefix) {
            Schema::table('users', function (Blueprint $table) use ($prefix) {
                $column = "{$prefix}_konfirmasi_pertemuan_metode";

                if (! Schema::hasColumn('users', $column)) {
                    $table->string($column)
                        ->nullable()
                        ->after("{$prefix}_konfirmasi_pertemuan_evidence_type");
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['pegawai', 'penilai', 'pejabat', 'atasan'] as $prefix) {
            Schema::table('users', function (Blueprint $table) use ($prefix) {
                $column = "{$prefix}_konfirmasi_pertemuan_metode";

                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            });
        }
    }
};

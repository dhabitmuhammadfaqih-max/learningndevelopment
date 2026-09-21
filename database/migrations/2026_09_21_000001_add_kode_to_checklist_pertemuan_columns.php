<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kode pertemuan yang BERHASIL dipakai untuk mencentang checklist
     * "sudah bertemu & evaluasi" metode Offline - pengganti kolom
     * *_konfirmasi_pertemuan_selfie untuk metode tersebut (lihat
     * migration create_meeting_codes_table).
     *
     * Kolom *_konfirmasi_pertemuan_selfie SENGAJA TIDAK DIHAPUS: data
     * lama (evidence_type 'selfie' & 'upload') masih memakainya, dan
     * metode ONLINE (upload bukti Zoom/Telpon/Chat) tetap menyimpan
     * path file-nya di sana. Jadi:
     * - evidence_type 'upload' -> path file ada di *_selfie, kode null
     * - evidence_type 'kode'   -> kode ada di sini, *_selfie null
     * - evidence_type 'selfie' -> data LAMA saja, path foto di *_selfie
     *
     * Lihat User::checklistEvidenceLabel() &
     * resources/views/partials/checklist-pertemuan-toggle.blade.php.
     */
    public function up(): void
    {
        foreach (['pegawai', 'penilai', 'pejabat', 'atasan'] as $prefix) {
            Schema::table('users', function (Blueprint $table) use ($prefix) {
                $column = "{$prefix}_konfirmasi_pertemuan_kode";

                if (! Schema::hasColumn('users', $column)) {
                    $table->string($column, 8)
                        ->nullable()
                        ->after("{$prefix}_konfirmasi_pertemuan_metode");
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['pegawai', 'penilai', 'pejabat', 'atasan'] as $prefix) {
            Schema::table('users', function (Blueprint $table) use ($prefix) {
                $column = "{$prefix}_konfirmasi_pertemuan_kode";

                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            });
        }
    }
};

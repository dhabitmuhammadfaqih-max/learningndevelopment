<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sebelum migration ini, checklist "sudah bertemu & evaluasi"
     * (pegawai_konfirmasi_pertemuan_at, dst - lihat migration
     * add_checklist_pertemuan_to_users_table) TIDAK terikat ke tahun
     * penilaian manapun - cuma satu nilai aktif per user yang terus
     * kebawa dari tahun ke tahun. Akibatnya begitu pindah ke tahun
     * penilaian baru, checklist otomatis "sudah tercentang" padahal
     * belum ada pertemuan sama sekali untuk tahun itu (kepake data
     * checklist tahun lalu).
     *
     * Kolom *_konfirmasi_pertemuan_tahun di sini menyimpan tahun milik
     * konfirmasi yang sedang tersimpan di *_konfirmasi_pertemuan_at.
     * User::pegawaiSudahKonfirmasiPertemuan() dkk (lihat App\Models\User)
     * sekarang mensyaratkan tahun ini SAMA DENGAN tahun yang sedang aktif
     * sebelum dianggap "sudah centang" - kalau beda (mis. sisa dari tahun
     * lalu), otomatis dianggap belum centang untuk tahun berjalan, tanpa
     * perlu job/command reset manual apa pun.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach ([
                'pegawai_konfirmasi_pertemuan_tahun',
                'penilai_konfirmasi_pertemuan_tahun',
                'pejabat_konfirmasi_pertemuan_tahun',
                'atasan_konfirmasi_pertemuan_tahun',
            ] as $column) {
                if (! Schema::hasColumn('users', $column)) {
                    $table->unsignedSmallInteger($column)->nullable()->after(str_replace('_tahun', '_evidence_type', $column));
                }
            }
        });

        // Backfill: checklist yang SUDAH tercentang sebelum migration ini
        // ada dianggap milik tahun berjalan saat migration dijalankan -
        // ini konsisten dengan perilaku lama (checklist tidak pernah
        // dibedakan per tahun, jadi "tahun aktif sekarang" adalah asumsi
        // paling masuk akal untuk data existing).
        $tahunSekarang = now()->year;

        foreach ([
            'pegawai' => 'pegawai_konfirmasi_pertemuan',
            'penilai' => 'penilai_konfirmasi_pertemuan',
            'pejabat' => 'pejabat_konfirmasi_pertemuan',
            'atasan'  => 'atasan_konfirmasi_pertemuan',
        ] as $prefix) {
            DB::table('users')
                ->whereNotNull($prefix . '_at')
                ->update([$prefix . '_tahun' => $tahunSekarang]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach ([
                'pegawai_konfirmasi_pertemuan_tahun',
                'penilai_konfirmasi_pertemuan_tahun',
                'pejabat_konfirmasi_pertemuan_tahun',
                'atasan_konfirmasi_pertemuan_tahun',
            ] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

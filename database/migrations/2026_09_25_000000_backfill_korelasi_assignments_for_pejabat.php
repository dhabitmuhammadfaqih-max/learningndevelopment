<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Fitur Atur Korelasi diperluas ke PEJABAT (Atasan menentukan pejabat
     * lain yang boleh ditanggapi). Tanggapan pejabat -> pejabat yang
     * SUDAH terkirim (Feedback) dianggap sebagai korelasi yang sudah
     * ditentukan, sama seperti yang dilakukan migration
     * create_korelasi_assignments_table untuk pegawai. insertOrIgnore
     * supaya aman dijalankan walau baris yang sama sudah ada.
     */
    public function up(): void
    {
        $now = now();

        DB::table('feedbacks')
            ->join('users as reviewer', 'reviewer.id', '=', 'feedbacks.reviewer_id')
            ->join('users as target', 'target.id', '=', 'feedbacks.employee_id')
            ->where('reviewer.role', 'pejabat')
            ->where('target.role', 'pejabat')
            ->select('feedbacks.reviewer_id', 'feedbacks.employee_id')
            ->distinct()
            ->get()
            ->chunk(200)
            ->each(function ($chunk) use ($now) {
                DB::table('korelasi_assignments')->insertOrIgnore(
                    $chunk->map(fn ($row) => [
                        'reviewer_id' => $row->reviewer_id,
                        'target_id'   => $row->employee_id,
                        'assigned_by' => null,
                        'created_at'  => $now,
                        'updated_at'  => $now,
                    ])->all()
                );
            });
    }

    public function down(): void
    {
        // Data hasil backfill tidak dihapus - tidak bisa dibedakan dari
        // penugasan yang dibuat manual oleh Atasan.
    }
};

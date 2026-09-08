<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Satu akun (reviewer_id) hanya boleh memberi SATU tanggapan (Feedback -
 * dipakai untuk tanggapan korelasi pegawai<->pegawai maupun
 * pejabat<->pejabat, lihat EmployeeController::feedback() dan
 * OfficialController::feedback()) ke orang yang sama (employee_id).
 * Pengecekan ini sudah ada di level controller, tapi ditambahkan juga
 * unique index di sini supaya tetap konsisten walau ada request yang
 * lolos dari validasi controller (mis. race condition dua submit
 * bersamaan).
 *
 * CATATAN: kalau migrasi ini gagal karena sudah ada data duplikat
 * (reviewer_id + employee_id yang sama, lebih dari satu baris) di
 * tabel feedbacks, migrasi TIDAK menghapus data apapun secara
 * otomatis - bersihkan/gabungkan dulu baris duplikat itu secara
 * manual, baru jalankan ulang migrasinya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('feedbacks', function (Blueprint $table) {
            if (! $this->indexExists('feedbacks', 'feedbacks_reviewer_id_employee_id_unique')) {
                $table->unique(['reviewer_id', 'employee_id'], 'feedbacks_reviewer_id_employee_id_unique');
            }
        });
    }

    public function down(): void
    {
        Schema::table('feedbacks', function (Blueprint $table) {
            if ($this->indexExists('feedbacks', 'feedbacks_reviewer_id_employee_id_unique')) {
                $table->dropUnique('feedbacks_reviewer_id_employee_id_unique');
            }
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();

        if ($driver === 'sqlite') {
            $indexes = collect($connection->select("PRAGMA index_list({$table})"));

            return $indexes->contains(fn ($index) => $index->name === $indexName);
        }

        // MySQL/MariaDB
        $result = $connection->select(
            'SHOW INDEX FROM ' . $table . ' WHERE Key_name = ?',
            [$indexName]
        );

        return count($result) > 0;
    }
};

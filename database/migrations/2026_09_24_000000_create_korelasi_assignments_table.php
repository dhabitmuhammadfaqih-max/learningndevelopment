<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Daftar "korelasi" yang ditentukan PENILAI/ATASAN (users.
     * supervisor_id) untuk akun binaannya: akun-akun yang ditunjuk
     * sebagai korelasi MEMBERI tanggapan (Feedback) kepada akun binaan
     * itu. Lihat App\Models\KorelasiAssignment,
     * OfficialController::korelasi() & EmployeeController::feedback().
     *
     * - reviewer_id : akun yang ditunjuk = PEMBERI tanggapan.
     * - target_id   : akun binaan (yang dinilai) = PENERIMA tanggapan.
     * - assigned_by : akun penilai yang menentukan (audit).
     */
    public function up(): void
    {
        Schema::create('korelasi_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reviewer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('target_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['reviewer_id', 'target_id'], 'korelasi_assignments_reviewer_target_unique');
        });

        // Tanggapan korelasi (Feedback) pegawai -> pegawai yang SUDAH
        // terkirim sebelum fitur ini dijadikan penugasan supaya tetap
        // konsisten: pasangan itu langsung dianggap "sudah ditentukan"
        // (dan tampil ter-checklist di dashboard pegawai). Penilai
        // tetap bisa menambah pilihan lain lewat "Atur Korelasi".
        $now = now();

        DB::table('feedbacks')
            ->join('users as reviewer', 'reviewer.id', '=', 'feedbacks.reviewer_id')
            ->join('users as target', 'target.id', '=', 'feedbacks.employee_id')
            ->where('reviewer.role', 'pegawai')
            ->where('target.role', 'pegawai')
            ->select('feedbacks.reviewer_id', 'feedbacks.employee_id')
            ->distinct()
            ->get()
            ->chunk(200)
            ->each(function ($chunk) use ($now) {
                DB::table('korelasi_assignments')->insert(
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
        Schema::dropIfExists('korelasi_assignments');
    }
};

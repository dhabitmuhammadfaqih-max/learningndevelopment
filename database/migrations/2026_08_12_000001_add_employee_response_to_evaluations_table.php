<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('evaluations', 'employee_response')) {
            Schema::table('evaluations', function (Blueprint $table) {
                // Tanggapan karyawan atas penilaian yang diberikan pejabat.
                $table->text('employee_response')->nullable()->after('signature');
                $table->timestamp('employee_response_at')->nullable()->after('employee_response');
            });
        }
    }

    public function down(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            if (Schema::hasColumn('evaluations', 'employee_response_at')) {
                $table->dropColumn('employee_response_at');
            }
            if (Schema::hasColumn('evaluations', 'employee_response')) {
                $table->dropColumn('employee_response');
            }
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            if (! Schema::hasColumn('evaluations', 'employee_signature')) {
                $table->string('employee_signature')->nullable()->after('employee_response_at');
            }

            if (! Schema::hasColumn('evaluations', 'hrd_id')) {
                $table->foreignId('hrd_id')->nullable()->after('employee_signature')
                    ->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('evaluations', 'hrd_signature')) {
                $table->string('hrd_signature')->nullable()->after('hrd_id');
            }

            if (! Schema::hasColumn('evaluations', 'hrd_signed_at')) {
                $table->timestamp('hrd_signed_at')->nullable()->after('hrd_signature');
            }
        });
    }

    public function down(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            if (Schema::hasColumn('evaluations', 'hrd_signed_at')) {
                $table->dropColumn('hrd_signed_at');
            }

            if (Schema::hasColumn('evaluations', 'hrd_signature')) {
                $table->dropColumn('hrd_signature');
            }

            if (Schema::hasColumn('evaluations', 'hrd_id')) {
                $table->dropConstrainedForeignId('hrd_id');
            }

            if (Schema::hasColumn('evaluations', 'employee_signature')) {
                $table->dropColumn('employee_signature');
            }
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            if (!Schema::hasColumn('evaluations', 'teguran')) {
                $table->text('teguran')->nullable()->after('feedback');
            }
        });

        Schema::table('official_evaluations', function (Blueprint $table) {
            if (!Schema::hasColumn('official_evaluations', 'teguran')) {
                $table->text('teguran')->nullable()->after('feedback');
            }
        });

        if (Schema::hasColumn('supervisor_feedbacks', 'teguran')) {
            Schema::table('supervisor_feedbacks', function (Blueprint $table) {
                $table->dropColumn('teguran');
            });
        }

        if (Schema::hasColumn('official_supervisor_feedbacks', 'teguran')) {
            Schema::table('official_supervisor_feedbacks', function (Blueprint $table) {
                $table->dropColumn('teguran');
            });
        }
    }

    public function down(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            if (Schema::hasColumn('evaluations', 'teguran')) $table->dropColumn('teguran');
        });
        Schema::table('official_evaluations', function (Blueprint $table) {
            if (Schema::hasColumn('official_evaluations', 'teguran')) $table->dropColumn('teguran');
        });
    }
};

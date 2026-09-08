<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supervisor_feedbacks', function (Blueprint $table) {
            $table->text('teguran')->nullable()->after('feedback');
        });

        Schema::table('official_supervisor_feedbacks', function (Blueprint $table) {
            $table->text('teguran')->nullable()->after('feedback');
        });
    }

    public function down(): void
    {
        Schema::table('supervisor_feedbacks', function (Blueprint $table) {
            $table->dropColumn('teguran');
        });

        Schema::table('official_supervisor_feedbacks', function (Blueprint $table) {
            $table->dropColumn('teguran');
        });
    }
};

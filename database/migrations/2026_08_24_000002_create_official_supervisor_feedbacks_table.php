<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * official_supervisor_feedbacks adalah versi supervisor_feedbacks
     * untuk pejabat yang ditugaskan sebagai Atasan Penilai.
     */
    public function up(): void
    {
        if (! Schema::hasTable('official_supervisor_feedbacks')) {
            Schema::create('official_supervisor_feedbacks', function (Blueprint $table) {
                $table->id();

                $table->foreignId('official_id')
                    ->constrained('users')
                    ->cascadeOnDelete();

                $table->foreignId('supervisor_id')
                    ->constrained('users')
                    ->cascadeOnDelete();

                $table->unsignedSmallInteger('tahun')->default(date('Y'));

                $table->text('feedback');

                $table->string('recommendation', 500)
                    ->default('tidak_ada');

                $table->unsignedInteger('kenaikan_gaji_amount')
                    ->nullable();

                $table->string('signature')
                    ->nullable();

                $table->timestamps();

                $table->unique(
                    ['official_id', 'supervisor_id', 'tahun'],
                    'off_sup_fb_unique'
                );
            });

            return;
        }

        $hasUniqueIndex = collect(DB::select('SHOW INDEX FROM official_supervisor_feedbacks'))
            ->contains(fn (object $index): bool => $index->Key_name === 'off_sup_fb_unique');

        if (! $hasUniqueIndex) {
            Schema::table('official_supervisor_feedbacks', function (Blueprint $table) {
                $table->unique(
                    ['official_id', 'supervisor_id', 'tahun'],
                    'off_sup_fb_unique'
                );
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('official_supervisor_feedbacks');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supervisor_feedbacks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('employee_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('supervisor_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->text('feedback');

            $table->timestamps();

            $table->unique(['employee_id', 'supervisor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supervisor_feedbacks');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menambahkan kolom `tahun` ke tabel evaluations, official_evaluations, dan
 * supervisor_feedbacks, supaya penilaian bisa diisi ulang setiap tahun tanpa
 * menghapus/menimpa histori tahun-tahun sebelumnya.
 *
 * Sebelumnya, unique constraint hanya (employee_id, official_id) dsb, yang
 * artinya satu pejabat cuma boleh menilai satu pegawai SEKALI SELAMANYA.
 * Setelah migration ini, constraint-nya jadi per tahun.
 *
 * CATATAN: unique index lama dipakai MySQL untuk menopang foreign key
 * (employee_id/official_id/dst mengarah ke tabel users), jadi foreign key-nya
 * harus dilepas dulu sebelum index unique lama bisa dihapus, baru dipasang
 * lagi setelah unique index yang baru (dengan tahun) dibuat.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── evaluations ─────────────────────────────────────────────
        Schema::table('evaluations', function (Blueprint $table) {
            if (! Schema::hasColumn('evaluations', 'tahun')) {
                $table->unsignedSmallInteger('tahun')
                    ->default(date('Y'))
                    ->after('official_id');
            }
        });

        Schema::table('evaluations', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            $table->dropForeign(['official_id']);
        });

        Schema::table('evaluations', function (Blueprint $table) {
            $table->dropUnique(['employee_id', 'official_id']);
            $table->unique(['employee_id', 'official_id', 'tahun']);
        });

        Schema::table('evaluations', function (Blueprint $table) {
            $table->foreign('employee_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('official_id')->references('id')->on('users')->cascadeOnDelete();
        });

        // ── official_evaluations ────────────────────────────────────
        Schema::table('official_evaluations', function (Blueprint $table) {
            if (! Schema::hasColumn('official_evaluations', 'tahun')) {
                $table->unsignedSmallInteger('tahun')
                    ->default(date('Y'))
                    ->after('supervisor_id');
            }
        });

        Schema::table('official_evaluations', function (Blueprint $table) {
            $table->dropForeign(['official_id']);
            $table->dropForeign(['supervisor_id']);
        });

        Schema::table('official_evaluations', function (Blueprint $table) {
            $table->dropUnique(['official_id', 'supervisor_id']);
            $table->unique(['official_id', 'supervisor_id', 'tahun']);
        });

        Schema::table('official_evaluations', function (Blueprint $table) {
            $table->foreign('official_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('supervisor_id')->references('id')->on('users')->cascadeOnDelete();
        });

        // ── supervisor_feedbacks ─────────────────────────────────────
        Schema::table('supervisor_feedbacks', function (Blueprint $table) {
            if (! Schema::hasColumn('supervisor_feedbacks', 'tahun')) {
                $table->unsignedSmallInteger('tahun')
                    ->default(date('Y'))
                    ->after('supervisor_id');
            }
        });

        Schema::table('supervisor_feedbacks', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            $table->dropForeign(['supervisor_id']);
        });

        Schema::table('supervisor_feedbacks', function (Blueprint $table) {
            $table->dropUnique(['employee_id', 'supervisor_id']);
            $table->unique(['employee_id', 'supervisor_id', 'tahun']);
        });

        Schema::table('supervisor_feedbacks', function (Blueprint $table) {
            $table->foreign('employee_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('supervisor_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        foreach ([
            'evaluations'          => ['employee_id', 'official_id'],
            'official_evaluations' => ['official_id', 'supervisor_id'],
            'supervisor_feedbacks' => ['employee_id', 'supervisor_id'],
        ] as $tableName => $columns) {
            Schema::table($tableName, function (Blueprint $table) use ($columns) {
                $table->dropForeign([$columns[0]]);
                $table->dropForeign([$columns[1]]);
            });

            Schema::table($tableName, function (Blueprint $table) use ($columns) {
                $table->dropUnique([$columns[0], $columns[1], 'tahun']);
                $table->unique([$columns[0], $columns[1]]);
            });

            Schema::table($tableName, function (Blueprint $table) use ($columns) {
                $table->foreign($columns[0])->references('id')->on('users')->cascadeOnDelete();
                $table->foreign($columns[1])->references('id')->on('users')->cascadeOnDelete();
            });

            Schema::table($tableName, function (Blueprint $table) {
                $table->dropColumn('tahun');
            });
        }
    }
};
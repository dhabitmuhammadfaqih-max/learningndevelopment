<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Koreksi satu kali untuk timestamp yang tersimpan SEBELUM config/app.php
 * 'timezone' diganti dari 'UTC' ke 'Asia/Jakarta' (2026-08-27).
 *
 * Sebelum perubahan itu, semua now()/created_at/updated_at dkk tersimpan
 * sebagai jam UTC (mis. jam 8 pagi WIB tersimpan sebagai "01:00:00").
 * Setelah timezone app diganti ke WIB, Laravel akan membaca nilai lama itu
 * APA ADANYA sebagai jam WIB (bukan dikonversi), sehingga tampilannya jadi
 * mundur 7 jam dari yang seharusnya. Command ini menambah 7 jam ke semua
 * kolom datetime/timestamp yang sudah terlanjur tersimpan sebelum
 * perbaikan, supaya nilainya benar saat ditampilkan.
 *
 * PENTING: jalankan HANYA SEKALI. Command ini otomatis membuat file
 * penanda (storage/app/legacy_timezone_fix.lock) supaya tidak sengaja
 * dijalankan dua kali dan menambah 7 jam lagi. Pakai --force kalau memang
 * sengaja mau menjalankan ulang (mis. ada data baru yang diimpor manual
 * langsung ke database dengan jam UTC).
 *
 * Cara pakai:
 *   php artisan app:fix-legacy-timezone-timestamps            (dry-run, cuma preview)
 *   php artisan app:fix-legacy-timezone-timestamps --apply    (benar-benar mengubah data)
 *   php artisan app:fix-legacy-timezone-timestamps --apply --force   (paksa jalan lagi walau sudah pernah)
 */
class FixLegacyTimezoneTimestamps extends Command
{
    protected $signature = 'app:fix-legacy-timezone-timestamps
                            {--apply : Benar-benar menyimpan perubahan. Tanpa opsi ini, command hanya preview (dry-run).}
                            {--hours=7 : Jumlah jam yang ditambahkan (default 7, selisih WIB dari UTC).}
                            {--force : Tetap jalankan walau sudah pernah dijalankan sebelumnya.}';

    protected $description = 'Koreksi +7 jam untuk timestamp lama yang tersimpan sebelum timezone app diganti dari UTC ke Asia/Jakarta.';

    /**
     * Daftar tabel & kolom datetime/timestamp yang perlu dikoreksi.
     * Kolom "sessions.last_activity" & tabel jobs/cache SENGAJA tidak
     * disertakan karena disimpan sebagai unix timestamp (epoch, tidak
     * terpengaruh timezone) atau memang tidak relevan untuk dikoreksi.
     */
    private const TARGETS = [
        'users' => [
            'created_at',
            'updated_at',
            'email_verified_at',
            'kehadiran_diisi_at',
            'pegawai_konfirmasi_pertemuan_at',
            'penilai_konfirmasi_pertemuan_at',
        ],
        'evaluations' => [
            'created_at',
            'updated_at',
            'employee_response_at',
            'hrd_signed_at',
        ],
        'feedbacks' => [
            'created_at',
            'updated_at',
        ],
        'supervisor_feedbacks' => [
            'created_at',
            'updated_at',
        ],
        'signature_documents' => [
            'created_at',
            'updated_at',
            'karyawan_signed_at',
            'pejabat_signed_at',
            'atasan_signed_at',
        ],
        'official_evaluations' => [
            'created_at',
            'updated_at',
            'employee_response_at',
        ],
        'official_supervisor_feedbacks' => [
            'created_at',
            'updated_at',
        ],
    ];

    private const LOCK_FILE = 'legacy_timezone_fix.lock';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $force = (bool) $this->option('force');
        $hours = (int) $this->option('hours');

        $lockPath = storage_path('app/' . self::LOCK_FILE);

        if ($apply && file_exists($lockPath) && ! $force) {
            $this->error(
                'Command ini sepertinya sudah pernah dijalankan sebelumnya (' .
                trim(@file_get_contents($lockPath)) .
                ").\nKalau memang sengaja mau menjalankan ulang, tambahkan --force."
            );

            return self::FAILURE;
        }

        $this->info($apply
            ? "Menjalankan koreksi (+{$hours} jam) - perubahan akan DISIMPAN ke database."
            : "Mode PREVIEW (dry-run) - tidak ada perubahan yang disimpan. Tambahkan --apply untuk benar-benar menyimpan.");
        $this->newLine();

        $totalRows = 0;

        foreach (self::TARGETS as $table => $columns) {
            if (! Schema::hasTable($table)) {
                $this->line("- Lewati tabel \"{$table}\": tabel tidak ditemukan.");
                continue;
            }

            $existingColumns = array_values(array_filter(
                $columns,
                fn ($column) => Schema::hasColumn($table, $column)
            ));

            if (empty($existingColumns)) {
                continue;
            }

            foreach ($existingColumns as $column) {
                $query = DB::table($table)->whereNotNull($column);
                $count = (clone $query)->count();

                if ($count === 0) {
                    continue;
                }

                $totalRows += $count;

                $this->line("- {$table}.{$column}: {$count} baris" . ($apply ? ' (mengubah...)' : ' (akan diubah)'));

                if (! $apply) {
                    continue;
                }

                $query->orderBy('id')->chunkById(500, function ($rows) use ($table, $column, $hours) {
                    foreach ($rows as $row) {
                        $newValue = Carbon::parse($row->{$column})->addHours($hours);

                        DB::table($table)
                            ->where('id', $row->id)
                            ->update([$column => $newValue]);
                    }
                });
            }
        }

        $this->newLine();

        if ($totalRows === 0) {
            $this->info('Tidak ada data yang perlu dikoreksi.');

            return self::SUCCESS;
        }

        if (! $apply) {
            $this->info("Total {$totalRows} baris AKAN dikoreksi. Jalankan lagi dengan --apply untuk benar-benar menyimpan.");

            return self::SUCCESS;
        }

        @file_put_contents($lockPath, 'Dijalankan pada ' . now()->toDateTimeString() . " (+{$hours} jam, {$totalRows} baris)");

        $this->info("Selesai. Total {$totalRows} baris berhasil dikoreksi (+{$hours} jam).");

        return self::SUCCESS;
    }
}

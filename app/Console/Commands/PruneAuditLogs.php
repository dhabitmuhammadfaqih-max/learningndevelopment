<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Support\AuditLogger;
use Illuminate\Console\Command;

/**
 * Hapus audit log yang sudah lebih tua dari masa simpan.
 *
 * Satu-satunya jalur penghapusan audit log (model AuditLog menolak
 * hapus/ubah per baris). Memakai query builder, jadi tidak memicu guard
 * model - sengaja.
 */
class PruneAuditLogs extends Command
{
    protected $signature = 'audit:prune {--days= : Hapus log lebih tua dari N hari (default: config audit.retention_days)}';

    protected $description = 'Hapus audit log yang lebih tua dari masa simpan';

    /** Batas bawah, supaya salah ketik (--days=0) tidak menghapus semuanya. */
    private const MIN_DAYS = 30;

    public function handle(): int
    {
        $days = $this->option('days') !== null
            ? (int) $this->option('days')
            : (int) config('audit.retention_days', 365);

        if ($days <= 0) {
            $this->info('Masa simpan tidak dibatasi (retention_days = 0). Tidak ada yang dihapus.');

            return self::SUCCESS;
        }

        if ($days < self::MIN_DAYS) {
            $this->error('Masa simpan minimal ' . self::MIN_DAYS . ' hari.');

            return self::FAILURE;
        }

        $cutoff = now()->subDays($days);

        $deleted = AuditLog::query()->where('created_at', '<', $cutoff)->delete();

        if ($deleted > 0) {
            AuditLogger::log(
                AuditLog::EVENT_PRUNED,
                "Membersihkan {$deleted} audit log yang lebih tua dari {$days} hari",
                ['meta' => ['jumlah' => $deleted, 'lebih_tua_dari' => $cutoff->toDateTimeString()]]
            );
        }

        $this->info("{$deleted} audit log dihapus (lebih tua dari {$days} hari).");

        return self::SUCCESS;
    }
}

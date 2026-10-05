<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Hapus audit log yang lebih tua dari AUDIT_RETENTION_DAYS (config/audit.php).
// Butuh cron `* * * * * php artisan schedule:run` aktif di server.
Schedule::command('audit:prune')->dailyAt('02:00');

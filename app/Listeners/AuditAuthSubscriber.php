<?php

namespace App\Listeners;

use App\Models\AuditLog;
use App\Models\User;
use App\Support\AuditLogger;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;

/**
 * Mencatat kejadian autentikasi ke Audit Log: login, logout, gagal login,
 * dan login diblokir (rate limit).
 *
 * Didaftarkan eksplisit di AppServiceProvider (Event::subscribe) - nama
 * method sengaja onXxx (bukan handle*) supaya tidak terdeteksi ganda oleh
 * auto-discovery listener Laravel.
 *
 * PRIVASI: password TIDAK PERNAH dicatat. Untuk login gagal, username yang
 * diketik hanya dicatat kalau cocok dengan akun yang ada (supaya terlihat
 * akun mana yang diserang). Username yang tidak dikenal TIDAK disimpan,
 * karena orang sering salah mengetik password di kolom username.
 */
class AuditAuthSubscriber
{
    public function subscribe(): array
    {
        return [
            Login::class   => 'onLogin',
            Logout::class  => 'onLogout',
            Failed::class  => 'onFailed',
            Lockout::class => 'onLockout',
        ];
    }

    public function onLogin(Login $event): void
    {
        AuditLogger::log(AuditLog::EVENT_LOGIN, 'Berhasil login', [
            'actor'   => $event->user,
            'subject' => $event->user,
            'meta'    => ['ingat_saya' => (bool) $event->remember],
        ]);
    }

    public function onLogout(Logout $event): void
    {
        // $event->user bisa null kalau sesi sudah kedaluwarsa.
        AuditLogger::log(AuditLog::EVENT_LOGOUT, 'Logout', [
            'actor'   => $event->user,
            'subject' => $event->user,
        ]);
    }

    public function onFailed(Failed $event): void
    {
        $known = $event->user instanceof User ? $event->user : null;

        AuditLogger::log(
            AuditLog::EVENT_LOGIN_FAILED,
            $known ? 'Gagal login (password salah)' : 'Gagal login (username tidak dikenal)',
            [
                'actor'   => $known,
                'subject' => $known,
            ]
        );
    }

    public function onLockout(Lockout $event): void
    {
        $username = (string) $event->request->input('username', '');
        $known = $username !== '' ? User::query()->where('username', $username)->first() : null;

        AuditLogger::log(
            AuditLog::EVENT_LOCKOUT,
            'Login diblokir sementara (terlalu banyak percobaan gagal)',
            [
                'actor'   => $known,
                'subject' => $known,
            ]
        );
    }
}

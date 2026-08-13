<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Evaluation;
use App\Models\Feedback;
use App\Models\SupervisorFeedback;

class User extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name',
        'username',
        'nik',
        'unit_kerja',
        'jabatan',
        'email',
        'password',
        'role',
        'is_spg',
        'jumlah_izin',
        'jumlah_sakit',
        'jumlah_alpa',
        'jumlah_terlambat',
        'contract_status',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_spg' => 'boolean',
    ];

    // Kolom jumlah kehadiran yang bisa diisi admin (masing-masing kategori
    // dihitung terpisah, bukan satu status tunggal).
    public const ATTENDANCE_COUNTERS = [
        'jumlah_izin'      => 'Izin',
        'jumlah_sakit'     => 'Sakit',
        'jumlah_alpa'      => 'Alpa',
        'jumlah_terlambat' => 'Terlambat',
    ];

    // Pilihan status kontrak yang bisa diberikan admin.
    public const CONTRACT_STATUSES = [
        'harian'  => 'Harian',
        'bulanan' => 'Bulanan',
        'tahunan' => 'Tahunan',
        'tetap'   => 'Tetap',
    ];

    public function contractStatusLabel(): string
    {
        return self::CONTRACT_STATUSES[$this->contract_status] ?? '-';
    }

    public function feedbacksGiven()
    {
        return $this->hasMany(
            Feedback::class,
            'reviewer_id'
        );
    }

    public function feedbacksReceived()
    {
        return $this->hasMany(
            Feedback::class,
            'employee_id'
        );
    }

    public function evaluations()
    {
        return $this->hasMany(
            Evaluation::class,
            'employee_id'
        );
    }

    public function evaluationsGiven()
    {
        return $this->hasMany(
            Evaluation::class,
            'official_id'
        );
    }

    public function supervisorFeedbacks()
    {
        return $this->hasMany(
            SupervisorFeedback::class,
            'employee_id'
        );
    }
}
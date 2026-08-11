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
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

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
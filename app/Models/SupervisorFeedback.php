<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupervisorFeedback extends Model
{
    protected $table = 'supervisor_feedbacks';
    protected $fillable = [
        'employee_id',
        'supervisor_id',
        'feedback',
    ];

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }
}
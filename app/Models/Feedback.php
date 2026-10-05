<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    use Auditable;

    protected $table = 'feedbacks';

    protected $fillable = [
        'employee_id',
        'reviewer_id',
        'feedback',
        'signature',
    ];

    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    /**
     * Teks objek yang tampil di halaman Audit Log (lihat trait Auditable).
     */
    public function auditLabel(): string
    {
        return 'Tanggapan korelasi ' . \App\Support\AuditLogger::userName($this->reviewer_id)
            . ' atas ' . \App\Support\AuditLogger::userName($this->employee_id);
    }
}
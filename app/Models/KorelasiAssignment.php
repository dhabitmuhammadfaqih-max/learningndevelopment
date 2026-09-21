<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu baris = "$reviewer ditunjuk sebagai KORELASI $target", artinya
 * $reviewer yang MEMBERI tanggapan ke $target. Ditentukan oleh Penilai/
 * Atasan $target (users.supervisor_id) lewat halaman "Atur Korelasi" -
 * lihat OfficialController::korelasi() & SupervisorController::korelasi().
 * Di dashboard $reviewer, $target muncul di "Berikan Tanggapan kepada
 * Rekan Kerja"; akun yang tidak pernah ditunjuk melihat daftar kosong.
 */
class KorelasiAssignment extends Model
{
    protected $fillable = [
        'reviewer_id',
        'target_id',
        'assigned_by',
    ];

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    public function target(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}

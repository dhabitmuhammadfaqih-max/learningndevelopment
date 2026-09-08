<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OfficialSupervisorFeedback extends Model
{
    protected $table = 'official_supervisor_feedbacks';

    protected $fillable = [
        'official_id',
        'supervisor_id',
        'tahun',
        'feedback',
        'recommendation',
        'kenaikan_gaji_amount',
        'promosi_keterangan',
        'demosi_keterangan',
        'signature',
    ];

    public function official()
    {
        return $this->belongsTo(User::class, 'official_id');
    }

    public function supervisor()
    {
        return $this->belongsTo(User::class, 'supervisor_id');
    }

    /**
     * Scope: batasi query ke satu tahun tertentu (default tahun berjalan
     * kalau $tahun tidak diisi). Lihat catatan yang sama di
     * App\Models\Evaluation::scopeTahunAktif().
     */
    public function scopeTahunAktif($query, ?int $tahun = null)
    {
        return $query->where('tahun', $tahun ?? now()->year);
    }

    /**
     * Rekomendasi disimpan sebagai string dipisah koma, sama seperti
     * OfficialEvaluation::recommendationList().
     */
    public function recommendationList(): array
    {
        if (! $this->recommendation || $this->recommendation === 'tidak_ada') {
            return [];
        }

        return array_values(array_filter(explode(',', $this->recommendation)));
    }

    public function recommendationLabel(): string
    {
        $list = $this->recommendationList();

        if (empty($list)) {
            return 'Tidak Ada';
        }

        $labels = array_map(function ($value) {
            $label = OfficialEvaluation::RECOMMENDATIONS[$value] ?? $value;

            if ($value === 'promosi' && $this->promosi_keterangan) {
                $label .= ' (ke ' . $this->promosi_keterangan . ')';
            }

            if ($value === 'demosi' && $this->demosi_keterangan) {
                $label .= ' (ke ' . $this->demosi_keterangan . ')';
            }

            return $label;
        }, $list);

        return implode(', ', $labels);
    }
}

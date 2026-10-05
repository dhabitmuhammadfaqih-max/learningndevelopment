<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Illuminate\Database\Eloquent\Model;

class OfficialSupervisorFeedback extends Model
{
    use Auditable;

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
        'mutasi_keterangan',
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
        return $query->where('tahun', $tahun ?? \App\Support\ActivePeriod::year());
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

            if ($value === 'mutasi' && $this->mutasi_keterangan) {
                $label .= ' (ke ' . $this->mutasi_keterangan . ')';
            }

            return $label;
        }, $list);

        return implode(', ', $labels);
    }

    /**
     * Teks objek yang tampil di halaman Audit Log (lihat trait Auditable).
     */
    public function auditLabel(): string
    {
        return 'Tanggapan atasan penilai ' . \App\Support\AuditLogger::userName($this->supervisor_id)
            . ' atas pejabat ' . \App\Support\AuditLogger::userName($this->official_id)
            . ($this->tahun ? " tahun {$this->tahun}" : '');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SignatureDocument extends Model
{
    protected $fillable = [
        'nomor_dokumen',
        'judul',
        'pegawai_nama',
        'pegawai_jabatan',
        'pegawai_signature',
        'pegawai_signed_at',
        'pejabat_nama',
        'pejabat_jabatan',
        'pejabat_signature',
        'pejabat_signed_at',
        'atasan_nama',
        'atasan_jabatan',
        'atasan_signature',
        'atasan_signed_at',
        'status',
    ];

    protected $casts = [
        'pegawai_signed_at' => 'datetime',
        'pejabat_signed_at' => 'datetime',
        'atasan_signed_at' => 'datetime',
    ];

    /**
     * Urutan resmi penanda tangan. Urutan ini menentukan giliran
     * siapa yang boleh menandatangani berikutnya.
     */
    public const ROLES = ['pegawai', 'pejabat', 'atasan'];

    /**
     * Role berikutnya yang berhak menandatangani, atau null jika
     * semua sudah menandatangani.
     */
    public function nextRole(): ?string
    {
        foreach (self::ROLES as $role) {
            if (is_null($this->{"{$role}_signature"})) {
                return $role;
            }
        }
        return null;
    }

    public function isComplete(): bool
    {
        return $this->nextRole() === null;
    }

    public function isRoleSigned(string $role): bool
    {
        return ! is_null($this->{"{$role}_signature"});
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ResidentProfile extends Model
{
    protected $fillable = [
        'user_id',
        'phone',
        'global_role',
        'is_verified',
        'nik_encrypted',
        'kk_encrypted',
        'is_ownership_doc_verified',
        'vehicles',
        'privacy_settings',
    ];

    protected function casts(): array
    {
        return [
            'is_verified' => 'boolean',
            'is_ownership_doc_verified' => 'boolean',
            'nik_encrypted' => 'encrypted', // Enkripsi AES-256 otomatis
            'kk_encrypted' => 'encrypted',  // Enkripsi AES-256 otomatis
            'vehicles' => 'array',
            'privacy_settings' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getReputationBadgeAttribute(): array
    {
        if (! $this->is_verified) {
            return ['level' => 0, 'label' => 'Menunggu Verifikasi', 'color' => 'gray'];
        }

        if ($this->is_ownership_doc_verified) {
            return ['level' => 4, 'label' => 'Pemilik Tervalidasi Dokumen', 'color' => 'amber'];
        }

        $hasOperational = ! empty($this->vehicles);
        $hasIdentity = ! empty($this->nik_encrypted) && ! empty($this->kk_encrypted);

        if ($hasIdentity && $hasOperational) {
            return ['level' => 3, 'label' => 'Warga Terpercaya', 'color' => 'emerald'];
        }

        if ($hasOperational) {
            return ['level' => 2, 'label' => 'Warga Aktif', 'color' => 'blue'];
        }

        return ['level' => 1, 'label' => 'Warga Terdaftar', 'color' => 'teal'];
    }
}
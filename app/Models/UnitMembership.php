<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UnitMembership extends Model
{
    protected $fillable = [
        'unit_id',
        'user_id',
        'guarantor_user_id',
        'is_owner',
        'owner_role',
        'is_occupant',
        'resident_role',
        'ownership_verification_status',
        'nib_shm_encrypted',
        'ownership_verified_at',
        'ownership_verified_by_user_id',
        'entity_type',
        'sub_role',
        'is_primary_contact',
        'is_approved',
        'valid_until',
    ];

    protected $hidden = [
        'nib_shm_encrypted',
    ];

    protected $appends = [
        'has_nib_shm',
        'ownership_status_label',
        'endorsed_by_sentence',
    ];

    protected function casts(): array
    {
        return [
            'is_owner' => 'boolean',
            'is_occupant' => 'boolean',
            'nib_shm_encrypted' => 'encrypted',
            'ownership_verified_at' => 'datetime',
            'is_primary_contact' => 'boolean',
            'is_approved' => 'boolean',
            'valid_until' => 'date',
        ];
    }

    public function getHasNibShmAttribute(): bool
    {
        return ! empty($this->nib_shm_encrypted);
    }

    public function getOwnershipStatusLabelAttribute(): string
    {
        if (! $this->is_owner) {
            return 'Bukan Pemilik';
        }

        return match ($this->ownership_verification_status) {
            'verified_shm' => $this->endorsed_by_sentence ?: 'Klaim Terverifikasi (Bukti NIB SHM Sah)',
            default => 'Klaim Sepihak Tanpa Bukti NIB SHM (Belum Terverifikasi)',
        };
    }

    public function getEndorsedBySentenceAttribute(): ?string
    {
        if (! $this->is_owner) {
            return null;
        }

        $verifications = $this->relationLoaded('ownershipVerifications')
            ? $this->ownershipVerifications
            : $this->ownershipVerifications()->with('verifier:id,name')->get();

        if ($verifications->isEmpty()) {
            return null;
        }

        $labels = $verifications->map(function (OwnershipVerification $v) {
            $name = $v->verifier?->name ?? 'Pengurus';
            return $v->role_label ? "{$name} ({$v->role_label})" : $name;
        })->values()->all();

        $count = count($labels);
        if ($count === 1) {
            return 'Disahkan oleh ' . $labels[0];
        }
        if ($count === 2) {
            return 'Disahkan oleh ' . $labels[0] . ' dan ' . $labels[1];
        }

        $last = array_pop($labels);
        return 'Disahkan oleh ' . implode(', ', $labels) . ', dan ' . $last;
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function guarantor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guarantor_user_id');
    }

    public function ownershipVerifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ownership_verified_by_user_id');
    }

    public function ownershipVerifications(): HasMany
    {
        return $this->hasMany(OwnershipVerification::class)->orderBy('verified_at');
    }
}
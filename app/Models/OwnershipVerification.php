<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OwnershipVerification extends Model
{
    protected $fillable = [
        'unit_membership_id',
        'user_id',
        'role_code',
        'role_label',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
        ];
    }

    public function membership(): BelongsTo
    {
        return $this->belongsTo(UnitMembership::class, 'unit_membership_id');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
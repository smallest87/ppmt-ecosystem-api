<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnitMembership extends Model
{
    protected $fillable = [
        'unit_id',
        'user_id',
        'entity_type',
        'sub_role',
        'is_primary_contact',
        'is_approved',
        'valid_until',
    ];

    protected function casts(): array
    {
        return [
            'is_primary_contact' => 'boolean',
            'is_approved' => 'boolean',
            'valid_until' => 'date',
        ];
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
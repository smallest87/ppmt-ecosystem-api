<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnitInvite extends Model
{
    protected $fillable = [
        'unit_id',
        'created_by_user_id',
        'token',
        'entity_type',
        'sub_role',
        'member_valid_until',
        'expires_at',
        'used_by_user_id',
        'used_at',
    ];

    protected function casts(): array
    {
        return [
            'member_valid_until' => 'date',
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
        ];
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function isUsable(): bool
    {
        return is_null($this->used_at) && $this->expires_at->isFuture();
    }
}
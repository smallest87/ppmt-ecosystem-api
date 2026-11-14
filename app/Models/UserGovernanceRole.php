<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserGovernanceRole extends Model
{
    protected $fillable = [
        'user_id',
        'role_code',
        'scope_type',
        'scope_value',
        'assigned_by_user_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_user_id');
    }

    public function getDisplayLabelAttribute(): string
    {
        $names = [
            'operator_aplikasi' => 'Operator Aplikasi',
            'ketua_paguyuban' => 'Ketua Paguyuban',
            'sekretaris_paguyuban' => 'Sekretaris Paguyuban',
            'bendahara_paguyuban' => 'Bendahara Paguyuban',
            'koordinator_zona' => 'Koordinator Zona',
            'koordinator_blok' => 'Koordinator Blok',
            'keamanan_lingkungan' => 'Keamanan Lingkungan',
        ];

        $label = $names[$this->role_code] ?? ucwords(str_replace('_', ' ', $this->role_code));

        if ($this->scope_type !== 'global' && $this->scope_value) {
            return "{$label} ({$this->scope_value})";
        }

        return $label;
    }
}
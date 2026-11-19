<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserGovernanceRole extends Model
{
    public const ROLES = [
        'admin' => [
            'label' => 'Admin Sistem',
            'scope' => 'global',
            'group' => 'system',
        ],
        'operator_aplikasi' => [
            'label' => 'Operator Aplikasi',
            'scope' => 'global',
            'group' => 'system',
        ],
        'ketua_paguyuban' => [
            'label' => 'Ketua Paguyuban',
            'scope' => 'global',
            'group' => 'executive',
        ],
        'sekretaris_paguyuban' => [
            'label' => 'Sekretaris Paguyuban',
            'scope' => 'global',
            'group' => 'executive',
        ],
        'bendahara_paguyuban' => [
            'label' => 'Bendahara Paguyuban',
            'scope' => 'global',
            'group' => 'executive',
        ],
        'seksi_ekonomi' => [
            'label' => 'Seksi Ekonomi & UMKM',
            'scope' => 'global',
            'group' => 'ministry',
        ],
        'keamanan_lingkungan' => [
            'label' => 'Keamanan Lingkungan',
            'scope' => 'global',
            'group' => 'ministry',
        ],
        'koordinator_zona' => [
            'label' => 'Koordinator Zona',
            'scope' => 'zone',
            'group' => 'territorial',
        ],
        'koordinator_blok' => [
            'label' => 'Koordinator Blok',
            'scope' => 'block',
            'group' => 'territorial',
        ],
    ];

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

    public static function validRoleCodes(): array
    {
        return array_keys(self::ROLES);
    }

    public static function requiredScopeFor(string $roleCode): string
    {
        return self::ROLES[$roleCode]['scope'] ?? 'global';
    }

    public static function labelFor(string $roleCode): string
    {
        return self::ROLES[$roleCode]['label']
            ?? ucwords(str_replace('_', ' ', $roleCode));
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
        $label = self::labelFor($this->role_code);

        if ($this->scope_type !== 'global' && $this->scope_value) {
            return "{$label} ({$this->scope_value})";
        }

        return $label;
    }
}
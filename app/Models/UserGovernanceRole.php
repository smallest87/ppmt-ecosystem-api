<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserGovernanceRole extends Model
{
    public const ROLES = [
        'admin' => ['label' => 'Admin Sistem', 'domain' => 'system', 'domain_label' => 'Teknis & Infrastruktur Sistem', 'scope' => 'global', 'appointed_by' => 'Admin Sistem Aktif / Genesis', 'permissions' => [], 'restrictions' => []],
        'operator_aplikasi' => ['label' => 'Operator Aplikasi', 'domain' => 'system', 'domain_label' => 'Teknis & Infrastruktur Sistem', 'scope' => 'global', 'appointed_by' => 'Admin Sistem', 'permissions' => [], 'restrictions' => []],
        'ketua_paguyuban' => ['label' => 'Ketua Paguyuban', 'domain' => 'organization', 'domain_label' => 'Pimpinan Inti Organisasi', 'scope' => 'global', 'appointed_by' => 'Admin Sistem / Operator Aplikasi', 'permissions' => [], 'restrictions' => []],
        'sekretaris_paguyuban' => ['label' => 'Sekretaris Paguyuban', 'domain' => 'organization', 'domain_label' => 'Pimpinan Inti Organisasi', 'scope' => 'global', 'appointed_by' => 'Ketua Paguyuban', 'permissions' => [], 'restrictions' => []],
        'bendahara_paguyuban' => ['label' => 'Bendahara Paguyuban', 'domain' => 'organization', 'domain_label' => 'Pimpinan Inti Organisasi', 'scope' => 'global', 'appointed_by' => 'Ketua Paguyuban', 'permissions' => [], 'restrictions' => []],
        'seksi_ekonomi' => ['label' => 'Seksi Ekonomi & UMKM', 'domain' => 'organization', 'domain_label' => 'Kabinet / Bidang Operasional', 'scope' => 'global', 'appointed_by' => 'Ketua Paguyuban', 'permissions' => [], 'restrictions' => []],
        'keamanan_lingkungan' => ['label' => 'Keamanan Lingkungan', 'domain' => 'organization', 'domain_label' => 'Kabinet / Bidang Operasional', 'scope' => 'global', 'appointed_by' => 'Ketua Paguyuban', 'permissions' => [], 'restrictions' => []],
        'koordinator_zona' => ['label' => 'Koordinator Zona', 'domain' => 'organization', 'domain_label' => 'Koordinator Wilayah', 'scope' => 'zone', 'appointed_by' => 'Ketua Paguyuban', 'permissions' => [], 'restrictions' => []],
        'koordinator_blok' => ['label' => 'Koordinator Blok', 'domain' => 'organization', 'domain_label' => 'Koordinator Wilayah', 'scope' => 'block', 'appointed_by' => 'Ketua Paguyuban', 'permissions' => [], 'restrictions' => []],
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
        $dbDefs = GovernanceRoleDefinition::allActiveCached();
        if ($dbDefs->isNotEmpty()) {
            return $dbDefs->keys()->all();
        }

        return array_keys(self::ROLES);
    }

    public static function requiredScopeFor(string $roleCode): string
    {
        $dbDef = GovernanceRoleDefinition::allActiveCached()->get($roleCode);
        if ($dbDef) {
            return $dbDef->scope;
        }

        return self::ROLES[$roleCode]['scope'] ?? 'global';
    }

    public static function labelFor(string $roleCode): string
    {
        $dbDef = GovernanceRoleDefinition::allActiveCached()->get($roleCode);
        if ($dbDef) {
            return $dbDef->label;
        }

        return self::ROLES[$roleCode]['label']
            ?? ucwords(str_replace('_', ' ', $roleCode));
    }

    public static function domainFor(string $roleCode): string
    {
        $dbDef = GovernanceRoleDefinition::allActiveCached()->get($roleCode);
        if ($dbDef) {
            return $dbDef->domain;
        }

        return self::ROLES[$roleCode]['domain'] ?? 'organization';
    }

    public static function permissionMatrix(): array
    {
        $dbDefs = GovernanceRoleDefinition::allActiveCached();
        if ($dbDefs->isNotEmpty()) {
            return $dbDefs->map(fn (GovernanceRoleDefinition $def) => [
                'id' => $def->id,
                'code' => $def->role_code,
                'label' => $def->label,
                'domain' => $def->domain,
                'domain_label' => $def->domain_label,
                'scope' => $def->scope,
                'appointed_by' => $def->appointed_by,
                'permissions' => $def->permissions ?? [],
                'restrictions' => $def->restrictions ?? [],
                'is_core' => (bool) $def->is_core,
            ])->values()->all();
        }

        return collect(self::ROLES)->map(fn (array $meta, string $code) => [
            'id' => null,
            'code' => $code,
            'label' => $meta['label'],
            'domain' => $meta['domain'],
            'domain_label' => $meta['domain_label'],
            'scope' => $meta['scope'],
            'appointed_by' => $meta['appointed_by'],
            'permissions' => $meta['permissions'],
            'restrictions' => $meta['restrictions'],
            'is_core' => true,
        ])->values()->all();
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
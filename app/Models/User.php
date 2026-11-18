<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function profile(): HasOne
    {
        return $this->hasOne(ResidentProfile::class);
    }

    public function unitMemberships(): HasMany
    {
        return $this->hasMany(UnitMembership::class);
    }

    public function governanceRoles(): HasMany
    {
        return $this->hasMany(UserGovernanceRole::class)->where('is_active', true);
    }

    public function hasGovernanceRole(string $roleCode, ?string $scopeType = null, ?string $scopeValue = null): bool
    {
        if (in_array($roleCode, ['admin', 'operator_aplikasi'], true) && $this->profile?->global_role === 'admin') {
            return true;
        }

        return $this->governanceRoles()
            ->where('role_code', $roleCode)
            ->when($scopeType, fn ($q) => $q->where('scope_type', $scopeType))
            ->when($scopeValue, fn ($q) => $q->where('scope_value', $scopeValue))
            ->exists();
    }

    public function isSystemAdmin(): bool
    {
        return $this->hasGovernanceRole('admin') || $this->hasGovernanceRole('operator_aplikasi');
    }

    public function isKetuaPaguyuban(): bool
    {
        return $this->hasGovernanceRole('ketua_paguyuban');
    }

    public function isSekretarisPaguyuban(): bool
    {
        return $this->hasGovernanceRole('sekretaris_paguyuban');
    }

    public function isBendaharaPaguyuban(): bool
    {
        return $this->hasGovernanceRole('bendahara_paguyuban');
    }

    public function canManageAnnouncements(): bool
    {
        return $this->isSekretarisPaguyuban() || $this->isKetuaPaguyuban();
    }

    public function canManageTreasury(): bool
    {
        return $this->isBendaharaPaguyuban();
    }

    public function canViewTreasury(): bool
    {
        return $this->isBendaharaPaguyuban() || $this->isKetuaPaguyuban();
    }

    /**
     * Visibilitas Level 2 (Angka Penuh NIB SHM):
     * Tertutup penuh (false) bagi seluruh pengurus paguyuban & admin sistem.
     * Hanya terbuka jika kelak jabatan otoritas legal (developer / bpn) ditambahkan.
     */
    public function canViewPlainNibShm(): bool
    {
        return $this->governanceRoles()
            ->whereIn('role_code', ['developer', 'bpn'])
            ->exists();
    }

    /**
     * Menentukan jabatan aktif paling relevan yang dipakai pengurus saat mengesahkan kavling.
     */
    public function resolveVerifierRoleForUnit(Unit $unit): array
    {
        $roles = $this->governanceRoles()->get();

        $priorityOrder = [
            'developer',
            'bpn',
            'ketua_paguyuban',
            'koordinator_blok',
            'koordinator_zona',
            'sekretaris_paguyuban',
            'bendahara_paguyuban',
            'keamanan_lingkungan',
            'operator_aplikasi',
            'admin',
        ];

        foreach ($priorityOrder as $code) {
            $matched = $roles->first(function (UserGovernanceRole $r) use ($code, $unit) {
                if ($r->role_code !== $code) {
                    return false;
                }
                if ($r->scope_type === 'block') {
                    return strtoupper((string) $r->scope_value) === strtoupper((string) $unit->block);
                }
                if ($r->scope_type === 'zone') {
                    return strtoupper((string) $r->scope_value) === strtoupper((string) $unit->zone);
                }
                return true;
            });

            if ($matched) {
                return [
                    'role_code' => $matched->role_code,
                    'role_label' => $matched->display_label,
                ];
            }
        }

        if ($this->profile?->global_role === 'admin') {
            return ['role_code' => 'admin', 'role_label' => 'Admin Sistem'];
        }

        if ($this->profile?->global_role === 'koordinator_blok') {
            return ['role_code' => 'koordinator_blok', 'role_label' => 'Koordinator Blok ' . $unit->block];
        }

        return ['role_code' => 'pengurus', 'role_label' => 'Pengurus PPMT'];
    }

    public function hasCentralOccupancyAccess(): bool
    {
        if ($this->isGlobalGovernor()) {
            return true;
        }

        return $this->governanceRoles()
            ->whereIn('role_code', [
                'sekretaris_paguyuban',
                'bendahara_paguyuban',
                'keamanan_lingkungan',
            ])
            ->exists();
    }

    public function canViewOccupancyRegistry(): bool
    {
        return $this->hasAdminAccess();
    }

    public function accessibleUnitsQuery(): Builder
    {
        $query = Unit::query()->where('unit_type', '!=', 'fasum');

        if ($this->hasCentralOccupancyAccess()) {
            return $query;
        }

        $zones = $this->governanceRoles()
            ->where('role_code', 'koordinator_zona')
            ->where('scope_type', 'zone')
            ->pluck('scope_value')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $blocks = $this->governanceRoles()
            ->where('role_code', 'koordinator_blok')
            ->where('scope_type', 'block')
            ->pluck('scope_value')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($this->profile?->global_role === 'koordinator_blok') {
            $legacyZones = $this->unitMemberships()
                ->where('is_approved', true)
                ->with('unit:id,zone')
                ->get()
                ->pluck('unit.zone')
                ->filter()
                ->unique()
                ->all();
            $zones = array_values(array_unique(array_merge($zones, $legacyZones)));
        }

        if (empty($zones) && empty($blocks)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $q) use ($zones, $blocks) {
            if (! empty($zones)) {
                $q->orWhereIn('zone', $zones);
            }
            if (! empty($blocks)) {
                $q->orWhereIn('block', $blocks);
            }
        });
    }

    public function assignableGovernanceRoles(): array
    {
        $roles = [];

        if ($this->isSystemAdmin()) {
            $roles = array_merge($roles, [
                'admin',
                'operator_aplikasi',
                'ketua_paguyuban',
            ]);
        }

        if ($this->isKetuaPaguyuban()) {
            $roles = array_merge($roles, [
                'sekretaris_paguyuban',
                'bendahara_paguyuban',
                'koordinator_zona',
                'koordinator_blok',
                'keamanan_lingkungan',
            ]);
        }

        return array_values(array_unique($roles));
    }

    public function canAssignGovernanceRole(string $roleCode): bool
    {
        return in_array($roleCode, $this->assignableGovernanceRoles(), true);
    }

    public function canRevokeGovernanceRole(UserGovernanceRole $role): bool
    {
        if ((int) $role->user_id === (int) $this->id) {
            return false;
        }

        return $this->canAssignGovernanceRole($role->role_code);
    }

    public function isGlobalGovernor(): bool
    {
        return $this->isSystemAdmin() || $this->isKetuaPaguyuban();
    }

    public function hasAdminAccess(): bool
    {
        if (in_array($this->profile?->global_role, ['admin', 'koordinator_blok'], true)) {
            return true;
        }

        return $this->governanceRoles()->exists();
    }

    public function canVouchUnit(Unit $unit): bool
    {
        if (! $this->profile || ! $this->profile->is_verified) {
            return false;
        }

        if ($this->isGlobalGovernor()) {
            return true;
        }

        if ($this->hasGovernanceRole('koordinator_zona', 'zone', $unit->zone)) {
            return true;
        }

        if ($this->hasGovernanceRole('koordinator_blok', 'block', $unit->block)) {
            return true;
        }

        if ($this->profile->global_role === 'koordinator_blok') {
            $isCoordinatorZone = $this->unitMemberships()
                ->where('is_approved', true)
                ->whereHas('unit', fn ($q) => $q->where('zone', $unit->zone))
                ->exists();

            if ($isCoordinatorZone) {
                return true;
            }
        }

        $isPrimaryOwner = $this->unitMemberships()
            ->where('unit_id', $unit->id)
            ->where('entity_type', 'owner')
            ->where('sub_role', 'primary_owner')
            ->where('is_approved', true)
            ->exists();

        if ($isPrimaryOwner) {
            return true;
        }

        // Izinkan penjamin yang ditunjuk pada pengajuan pending di unit ini
        return UnitMembership::where('unit_id', $unit->id)
            ->where('guarantor_user_id', $this->id)
            ->where('is_approved', false)
            ->exists();
    }
}
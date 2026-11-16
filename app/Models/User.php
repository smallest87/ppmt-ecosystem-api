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

    public function canManageAnnouncements(): bool
    {
        return $this->isSekretarisPaguyuban() || $this->isKetuaPaguyuban();
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

    /**
     * Query unit hunian (non-fasum) yang berada dalam wewenang pengurus ini.
     */
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

    /**
     * Daftar kode jabatan yang sah disematkan oleh user ini sesuai hierarki tata kelola.
     */
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

        return $this->unitMemberships()
            ->where('unit_id', $unit->id)
            ->where('entity_type', 'owner')
            ->where('sub_role', 'primary_owner')
            ->where('is_approved', true)
            ->exists();
    }
}
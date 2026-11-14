<?php

namespace App\Models;

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

    public function isGlobalGovernor(): bool
    {
        if ($this->profile?->global_role === 'admin') {
            return true;
        }

        return $this->governanceRoles()
            ->whereIn('role_code', ['admin', 'operator_aplikasi', 'ketua_paguyuban'])
            ->exists();
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
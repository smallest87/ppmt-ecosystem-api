<?php

namespace App\Policies;

use App\Models\Unit;
use App\Models\User;

class UnitPolicy
{
    public function inviteMember(User $user, Unit $unit): bool
    {
        if (! $user->profile?->is_verified) {
            return false;
        }

        return $user->unitMemberships()
            ->where('unit_id', $unit->id)
            ->whereIn('sub_role', ['primary_owner', 'primary_resident'])
            ->where('is_approved', true)
            ->where(function ($query) {
                $query->whereNull('valid_until')
                      ->orWhereDate('valid_until', '>=', now()->toDateString());
            })
            ->exists();
    }
}
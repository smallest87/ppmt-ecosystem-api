<?php

namespace App\Policies;

use App\Models\Unit;
use App\Models\User;

class VerificationPolicy
{
    public function vouchUser(User $voucher, Unit $unit): bool
    {
        $profile = $voucher->profile;

        if (! $profile || ! $profile->is_verified) {
            return false;
        }

        if ($profile->global_role === 'admin') {
            return true;
        }

        // 1. Cek apakah Koordinator Blok pada zona unit terkait
        if ($profile->global_role === 'koordinator_blok') {
            $isSameZone = $voucher->unitMemberships()
                ->where('is_approved', true)
                ->whereHas('unit', fn ($q) => $q->where('zone', $unit->zone))
                ->exists();

            if ($isSameZone) {
                return true;
            }
        }

        // 2. Cek apakah Pemilik Utama (primary_owner) dari unit yang diklaim
        return $voucher->unitMemberships()
            ->where('unit_id', $unit->id)
            ->where('entity_type', 'owner')
            ->where('sub_role', 'primary_owner')
            ->where('is_approved', true)
            ->exists();
    }
}
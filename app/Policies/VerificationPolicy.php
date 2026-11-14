<?php

namespace App\Policies;

use App\Models\Unit;
use App\Models\User;

class VerificationPolicy
{
    public function vouchUser(User $voucher, Unit $unit): bool
    {
        return $voucher->canVouchUnit($unit);
    }
}
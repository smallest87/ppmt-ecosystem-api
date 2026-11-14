<?php

namespace App\Providers;

use App\Models\Unit;
use App\Models\VerificationVoucher;
use App\Policies\UnitPolicy;
use App\Policies\VerificationPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(Unit::class, UnitPolicy::class);
        Gate::policy(VerificationVoucher::class, VerificationPolicy::class);
    }
}
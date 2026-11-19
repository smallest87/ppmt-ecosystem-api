<?php

namespace App\Providers;

use App\Models\Unit;
use App\Models\VerificationVoucher;
use App\Policies\UnitPolicy;
use App\Policies\VerificationPolicy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
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

        if ($this->app->environment(['local', 'testing']) && env('PPMT_BENCHMARK', false)) {
            $startTime = microtime(true);
            $queries = [];

            DB::listen(function ($query) use (&$queries) {
                $queries[] = [
                    'sql' => $query->sql,
                    'time_ms' => $query->time,
                ];
            });

            app()->terminating(function () use ($startTime, &$queries) {
                if (request()->is('api/*')) {
                    $durationMs = round((microtime(true) - $startTime) * 1000, 2);
                    $peakRamMb = round(memory_get_peak_usage(true) / 1024 / 1024, 2);
                    $dbTimeMs = round(array_sum(array_column($queries, 'time_ms')), 2);

                    Log::build([
                        'driver' => 'single',
                        'path' => storage_path('logs/benchmark.log'),
                    ])->info('API_METRIC', [
                        'endpoint' => request()->method() . ' /' . request()->path(),
                        'status' => http_response_code() ?: 200,
                        'duration_ms' => $durationMs,
                        'db_time_ms' => $dbTimeMs,
                        'php_time_ms' => round(max(0, $durationMs - $dbTimeMs), 2),
                        'query_count' => count($queries),
                        'peak_ram_mb' => $peakRamMb,
                    ]);
                }
            });
        }
    }
}
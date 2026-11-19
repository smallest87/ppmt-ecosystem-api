<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PpmtBenchmarkSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ResourceBenchmarkTest extends TestCase
{
    use RefreshDatabase;

    public function test_measure_resource_consumption_across_all_scenarios(): void
    {
        $this->seed(PpmtBenchmarkSeeder::class);

        $admin = User::whereHas('governanceRoles', fn ($q) => $q->where('role_code', 'admin'))->firstOrFail();
        $bendahara = User::whereHas('governanceRoles', fn ($q) => $q->where('role_code', 'bendahara_paguyuban'))->firstOrFail();
        $wargaBiasa = User::whereDoesntHave('governanceRoles')->firstOrFail();

        $scenarios = [
            [
                'name' => '1. Login & Hashing Verifikasi',
                'actor' => null,
                'method' => 'POST',
                'uri' => '/api/v1/auth/login',
                'payload' => ['login_id' => $wargaBiasa->profile->phone, 'password' => 'password123'],
            ],
            [
                'name' => '2. Profil & Hak Akses (/me)',
                'actor' => $admin,
                'method' => 'GET',
                'uri' => '/api/v1/me',
                'payload' => [],
            ],
            [
                'name' => '3. Direktori 180 Warga (Privacy Masking)',
                'actor' => $wargaBiasa,
                'method' => 'GET',
                'uri' => '/api/v1/residents/directory',
                'payload' => [],
            ],
            [
                'name' => '4. Pasar Tetangga (30 Lapak / 180 Produk)',
                'actor' => $wargaBiasa,
                'method' => 'GET',
                'uri' => '/api/v1/umkm/stores',
                'payload' => [],
            ],
            [
                'name' => '5. Laporan Kas Warga (500 Mutasi)',
                'actor' => $wargaBiasa,
                'method' => 'GET',
                'uri' => '/api/v1/treasury/public-report',
                'payload' => [],
            ],
            [
                'name' => '6. Pembukuan Bendahara (90 Kavling + Kas)',
                'actor' => $bendahara,
                'method' => 'GET',
                'uri' => '/api/v1/treasury/overview',
                'payload' => [],
            ],
            [
                'name' => '7. Buku Induk Pusat (90 Unit + SHM Status)',
                'actor' => $admin,
                'method' => 'GET',
                'uri' => '/api/v1/governance/occupancy-registry',
                'payload' => [],
            ],
            [
                'name' => '8. Role Management & Audit Matriks RBAC',
                'actor' => $admin,
                'method' => 'GET',
                'uri' => '/api/v1/governance/access-matrix',
                'payload' => [],
            ],
        ];

        $results = [];

        foreach ($scenarios as $sc) {
            DB::flushQueryLog();
            DB::enableQueryLog();

            $ramBefore = memory_get_usage(true);
            $start = microtime(true);

            $req = $sc['actor'] ? $this->actingAs($sc['actor'], 'sanctum') : $this;
            $response = $sc['method'] === 'POST'
                ? $req->postJson($sc['uri'], $sc['payload'])
                : $req->getJson($sc['uri']);

            $durationMs = round((microtime(true) - $start) * 1000, 2);
            $queries = DB::getQueryLog();
            $dbTimeMs = round(array_sum(array_column($queries, 'time')), 2);
            $ramDeltaMb = round(max(0, memory_get_usage(true) - $ramBefore) / 1024 / 1024, 2);
            $peakRamMb = round(memory_get_peak_usage(true) / 1024 / 1024, 2);

            $response->assertStatus(200);

            $results[] = [
                'scenario' => $sc['name'],
                'total_ms' => $durationMs,
                'db_ms' => $dbTimeMs,
                'queries' => count($queries),
                'delta_ram_mb' => $ramDeltaMb,
                'peak_ram_mb' => $peakRamMb,
            ];
        }

        fwrite(STDOUT, "\n\n=== HASIL BENCHMARK SUMBER DAYA PPMT (SKALA PENUH 90 UNIT / 180 WARGA) ===\n");
        fwrite(STDOUT, str_pad('Skenario', 44) . str_pad('Total(ms)', 12) . str_pad('DB(ms)', 10) . str_pad('Queries', 10) . str_pad('+RAM(MB)', 11) . "Peak(MB)\n");
        fwrite(STDOUT, str_repeat('-', 96) . "\n");

        foreach ($results as $r) {
            fwrite(
                STDOUT,
                str_pad($r['scenario'], 44) .
                str_pad((string) $r['total_ms'], 12) .
                str_pad((string) $r['db_ms'], 10) .
                str_pad((string) $r['queries'], 10) .
                str_pad((string) $r['delta_ram_mb'], 11) .
                $r['peak_ram_mb'] . "\n"
            );
        }
        fwrite(STDOUT, str_repeat('-', 96) . "\n\n");

        $this->assertTrue(true);
    }
}
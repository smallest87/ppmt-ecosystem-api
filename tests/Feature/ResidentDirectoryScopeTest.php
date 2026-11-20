<?php

namespace Tests\Feature;

use App\Models\ResidentProfile;
use App\Models\Unit;
use App\Models\UnitMembership;
use App\Models\User;
use App\Models\UserGovernanceRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ResidentDirectoryScopeTest extends TestCase
{
    use RefreshDatabase;

    private function createUnit(string $code, string $block, string $zone, int $sortOrder): Unit
    {
        $number = preg_replace('/[^0-9A-Z]/', '', explode('-', $code)[1] ?? '1');

        return Unit::create([
            'code' => $code,
            'block' => $block,
            'number' => (string) $number,
            'zone' => $zone,
            'unit_type' => 'rumah',
            'sort_order' => $sortOrder,
        ]);
    }

    private function createVerifiedResident(
        string $name,
        string $phone,
        Unit $unit,
        array $privacy = [],
        string $globalRole = 'resident'
    ): User {
        $user = User::factory()->create([
            'name' => $name,
            'email' => $phone . '@ppmt.local',
        ]);

        ResidentProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'display_name' => null,
                'phone' => $phone,
                'global_role' => $globalRole,
                'is_verified' => true,
                'privacy_settings' => array_merge([
                    'show_phone' => false,
                    'show_ownership' => false,
                    'show_full_name' => false,
                ], $privacy),
            ]
        );

        UnitMembership::create([
            'user_id' => $user->id,
            'unit_id' => $unit->id,
            'entity_type' => 'owner',
            'sub_role' => 'primary_owner',
            'is_owner' => true,
            'owner_role' => 'primary_owner',
            'is_occupant' => true,
            'resident_role' => 'primary_resident',
            'is_approved' => true,
            'is_primary_contact' => true,
        ]);

        return $user;
    }

    public function test_koordinator_blok_sees_all_blocks_publicly_and_private_details_only_in_assigned_block(): void
    {
        $unitA1 = $this->createUnit('A-1', 'A', 'ZONA-1', 1);
        $unitC1 = $this->createUnit('C-1', 'C', 'ZONA-3', 10);
        $unitC2 = $this->createUnit('C-2', 'C', 'ZONA-3', 11);
        $unitD1 = $this->createUnit('D-1', 'D', 'ZONA-4', 20);

        // Imam sebagai Koordinator Blok C
        $imam = $this->createVerifiedResident('Imam Santoso', '081200000001', $unitC1);
        UserGovernanceRole::create([
            'user_id' => $imam->id,
            'role_code' => 'koordinator_blok',
            'scope_type' => 'block',
            'scope_value' => 'C',
            'is_active' => true,
        ]);

        // Warga di Blok C (dengan privasi tertutup)
        $wargaBlokC = $this->createVerifiedResident('Budi Hartono', '081200000002', $unitC2, [
            'show_phone' => false,
            'show_full_name' => false,
            'show_ownership' => false,
        ]);

        // Warga di Blok A & Blok D (di luar wilayah binaan Imam, privasi tertutup)
        $wargaBlokA = $this->createVerifiedResident('Agus Pratama', '081200000003', $unitA1, [
            'show_phone' => false,
            'show_full_name' => false,
            'show_ownership' => false,
        ]);
        $wargaBlokD = $this->createVerifiedResident('Dewi Lestari', '081200000004', $unitD1, [
            'show_phone' => false,
            'show_full_name' => false,
            'show_ownership' => false,
        ]);

        Sanctum::actingAs($imam);

        $response = $this->getJson('/api/v1/residents/directory');

        $response->assertOk();

        $returnedIds = collect($response->json('data'))->pluck('id')->all();

        // Imam melihat semua warga lintas blok layaknya warga terverifikasi
        $this->assertEqualsCanonicalizing(
            [$imam->id, $wargaBlokC->id, $wargaBlokA->id, $wargaBlokD->id],
            $returnedIds
        );

        // Imam berhak melihat data privat warga Blok C binaannya
        $budiData = collect($response->json('data'))->firstWhere('id', $wargaBlokC->id);
        $this->assertSame('Budi Hartono', $budiData['name']);
        $this->assertSame('081200000002', $budiData['phone']);
        $this->assertTrue($budiData['units'][0]['is_owner']);

        // Untuk warga di luar Blok C (Blok A & D), data privat tetap tersamarkan
        $agusData = collect($response->json('data'))->firstWhere('id', $wargaBlokA->id);
        $this->assertSame('Agus', $agusData['name']);
        $this->assertNull($agusData['phone']);
        $this->assertFalse($agusData['units'][0]['is_owner']);

        $dewiData = collect($response->json('data'))->firstWhere('id', $wargaBlokD->id);
        $this->assertSame('Dewi', $dewiData['name']);
        $this->assertNull($dewiData['phone']);
        $this->assertFalse($dewiData['units'][0]['is_owner']);
    }

    public function test_koordinator_blok_sees_cross_block_secondary_units_with_privacy_enforced(): void
    {
        $unitB5 = $this->createUnit('B-5', 'B', 'ZONA-2', 5);
        $unitC1 = $this->createUnit('C-1', 'C', 'ZONA-3', 10);
        $unitC4 = $this->createUnit('C-4', 'C', 'ZONA-3', 13);

        $imam = $this->createVerifiedResident('Imam Santoso', '081200000011', $unitC1);
        UserGovernanceRole::create([
            'user_id' => $imam->id,
            'role_code' => 'koordinator_blok',
            'scope_type' => 'block',
            'scope_value' => 'C',
            'is_active' => true,
        ]);

        // Warga punya rumah di Blok C-4 dan rumah kedua di Blok B-5 (privasi kepemilikan tertutup)
        $multiUnitResident = $this->createVerifiedResident('Rudi Wijaya', '081200000012', $unitC4, [
            'show_ownership' => false,
        ]);
        UnitMembership::create([
            'user_id' => $multiUnitResident->id,
            'unit_id' => $unitB5->id,
            'entity_type' => 'owner',
            'sub_role' => 'primary_owner',
            'is_owner' => true,
            'owner_role' => 'primary_owner',
            'is_occupant' => false,
            'is_approved' => true,
        ]);

        Sanctum::actingAs($imam);

        $response = $this->getJson('/api/v1/residents/directory')->assertOk();

        $rudiData = collect($response->json('data'))->firstWhere('id', $multiUnitResident->id);
        $unitsByCode = collect($rudiData['units'])->keyBy('unit_code');

        // Kedua unit tampil, namun status pemilik (is_owner) hanya terbuka di C-4 dan tertutup di B-5
        $this->assertEqualsCanonicalizing(['C-4', 'B-5'], $unitsByCode->keys()->all());
        $this->assertTrue($unitsByCode['C-4']['is_owner']);
        $this->assertFalse($unitsByCode['B-5']['is_owner']);
    }

    public function test_regular_resident_sees_all_blocks_with_privacy_settings_enforced(): void
    {
        $unitA2 = $this->createUnit('A-2', 'A', 'ZONA-1', 2);
        $unitC3 = $this->createUnit('C-3', 'C', 'ZONA-3', 12);

        $viewer = $this->createVerifiedResident('Andi Saputra', '081200000021', $unitA2);

        // Warga dengan privasi tertutup
        $privateNeighbor = $this->createVerifiedResident('Siti Aminah', '081200000022', $unitC3, [
            'show_phone' => false,
            'show_full_name' => false,
            'show_ownership' => false,
        ]);

        Sanctum::actingAs($viewer);

        $response = $this->getJson('/api/v1/residents/directory')->assertOk();

        $sitiData = collect($response->json('data'))->firstWhere('id', $privateNeighbor->id);

        $this->assertNotNull($sitiData);
        $this->assertSame('Siti', $sitiData['name']);
        $this->assertNull($sitiData['phone']);
        $this->assertFalse($sitiData['units'][0]['is_owner']);
    }

    public function test_central_governor_sees_all_blocks_and_private_details(): void
    {
        $unitA2 = $this->createUnit('A-2', 'A', 'ZONA-1', 2);
        $unitC3 = $this->createUnit('C-3', 'C', 'ZONA-3', 12);

        $ketua = $this->createVerifiedResident('Hendra Kusuma', '081200000031', $unitA2);
        UserGovernanceRole::create([
            'user_id' => $ketua->id,
            'role_code' => 'ketua_paguyuban',
            'scope_type' => 'global',
            'scope_value' => null,
            'is_active' => true,
        ]);

        $privateNeighbor = $this->createVerifiedResident('Siti Aminah', '081200000032', $unitC3, [
            'show_phone' => false,
            'show_full_name' => false,
            'show_ownership' => false,
        ]);

        Sanctum::actingAs($ketua);

        $response = $this->getJson('/api/v1/residents/directory')->assertOk();

        $sitiData = collect($response->json('data'))->firstWhere('id', $privateNeighbor->id);

        $this->assertSame('Siti Aminah', $sitiData['name']);
        $this->assertSame('081200000032', $sitiData['phone']);
        $this->assertTrue($sitiData['units'][0]['is_owner']);
    }
}
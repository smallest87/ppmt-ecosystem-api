<?php

namespace Tests\Feature;

use App\Models\GovernanceRoleDefinition;
use App\Models\ResidentProfile;
use App\Models\Unit;
use App\Models\User;
use App\Models\UserGovernanceRole;
use Database\Seeders\PpmtUnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CabinetDomainIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PpmtUnitSeeder::class);
        GovernanceRoleDefinition::flushCache();
    }

    /**
     * CAB-01: Isolasi Keuangan (Bendahara tulis, Ketua baca, Seksi Ekonomi & Admin ditolak)
     */
    public function test_cab_01_treasury_domain_isolation(): void
    {
        $bendahara = $this->createResidentWithRole('Bu Bendahara', '081400000001', 'B-4', 'bendahara_paguyuban');
        $ketua = $this->createResidentWithRole('Pak Ketua', '081400000002', 'B-5', 'ketua_paguyuban');
        $seksiEkonomi = $this->createResidentWithRole('Pak Ekonomi', '081400000003', 'B-6', 'seksi_ekonomi');
        $admin = $this->createResidentWithRole('Admin Sistem', '081400000004', 'B-7', 'admin');

        $ledgerPayload = [
            'entry_date' => now()->toDateString(),
            'type' => 'expense',
            'category' => 'pengangkutan_sampah',
            'amount' => 150000,
            'payment_method' => 'cash',
            'description' => 'Bayar petugas angkut sampah minggu ini',
        ];

        // Seksi Ekonomi & Admin Sistem DITOLAK melihat overview pembukuan maupun mencatat mutasi kas
        $this->actingAs($seksiEkonomi, 'sanctum')->getJson('/api/v1/treasury/overview')->assertStatus(403);
        $this->actingAs($seksiEkonomi, 'sanctum')->postJson('/api/v1/treasury/ledger', $ledgerPayload)->assertStatus(403);
        $this->actingAs($admin, 'sanctum')->postJson('/api/v1/treasury/ledger', $ledgerPayload)->assertStatus(403);

        // Ketua Paguyuban BOLEH memantau overview pembukuan (view-only), tapi DITOLAK menulis mutasi kas
        $this->actingAs($ketua, 'sanctum')->getJson('/api/v1/treasury/overview')->assertStatus(200);
        $this->actingAs($ketua, 'sanctum')->postJson('/api/v1/treasury/ledger', $ledgerPayload)->assertStatus(403);

        // Bendahara Paguyuban BERHASIL mencatat mutasi kas
        $this->actingAs($bendahara, 'sanctum')
            ->postJson('/api/v1/treasury/ledger', $ledgerPayload)
            ->assertStatus(201);
    }

    /**
     * CAB-02: Isolasi Publikasi & Pengumuman (Sekretaris & Ketua boleh, Bendahara & Seksi Ekonomi ditolak)
     */
    public function test_cab_02_announcement_domain_isolation(): void
    {
        $sekretaris = $this->createResidentWithRole('Pak Sekretaris', '081400000010', 'C-1', 'sekretaris_paguyuban');
        $bendahara = $this->createResidentWithRole('Bu Bendahara', '081400000011', 'C-2', 'bendahara_paguyuban');
        $seksiEkonomi = $this->createResidentWithRole('Pak Ekonomi', '081400000012', 'C-3', 'seksi_ekonomi');

        $announcementPayload = [
            'title' => 'Jadwal Kerja Bakti Minggu Pagi',
            'category' => 'kerja_bakti',
            'body' => 'Dimohon seluruh warga berkumpul di pos utama pukul 07.00 WIB.',
            'is_pinned' => true,
            'is_published' => true,
        ];

        // Bendahara & Seksi Ekonomi DITOLAK menerbitkan pengumuman resmi paguyuban
        $this->actingAs($bendahara, 'sanctum')->postJson('/api/v1/announcements', $announcementPayload)->assertStatus(403);
        $this->actingAs($seksiEkonomi, 'sanctum')->postJson('/api/v1/announcements', $announcementPayload)->assertStatus(403);

        // Sekretaris Paguyuban BERHASIL menerbitkan pengumuman
        $this->actingAs($sekretaris, 'sanctum')
            ->postJson('/api/v1/announcements', $announcementPayload)
            ->assertStatus(201);
    }

    /**
     * CAB-03: Otoritas Wilayah Koordinator Blok/Zona & Kerahasiaan Angka Mentah NIB SHM
     */
    public function test_cab_03_territorial_coordinator_scope_and_nib_shm_secrecy(): void
    {
        // Koordinator Blok khusus Blok A
        $koorBlokA = $this->createVerifiedResident('Koor Blok A', '081400000020', 'A-10');
        UserGovernanceRole::create([
            'user_id' => $koorBlokA->id,
            'role_code' => 'koordinator_blok',
            'scope_type' => 'block',
            'scope_value' => 'A',
            'is_active' => true,
        ]);

        // Pemilik di Blok A dan Pemilik di Blok D yang menginput NIB SHM
        $ownerBlokA = $this->createVerifiedResident('Pemilik A-11', '081400000021', 'A-11');
        $membershipA = $ownerBlokA->unitMemberships()->first();
        $membershipA->update([
            'is_owner' => true,
            'owner_role' => 'primary_owner',
            'nib_shm_encrypted' => '12.05.04.01.1.00123',
        ]);

        $ownerBlokD = $this->createVerifiedResident('Pemilik D-5', '081400000022', 'D-5');
        $membershipD = $ownerBlokD->unitMemberships()->first();
        $membershipD->update([
            'is_owner' => true,
            'owner_role' => 'primary_owner',
            'nib_shm_encrypted' => '12.05.04.01.1.00999',
        ]);

        // Koordinator Blok A DITOLAK mengesahkan SHM milik warga di Blok D
        $this->actingAs($koorBlokA, 'sanctum')
            ->postJson("/api/v1/verifications/ownership/{$membershipD->id}", [
                'action' => 'approve_shm',
            ])->assertStatus(403);

        // Koordinator Blok A BERHASIL mengesahkan SHM warga di Blok A, dan angka mentah NIB SHM tetap tertutup (null)
        $pendingRes = $this->actingAs($koorBlokA, 'sanctum')
            ->getJson('/api/v1/verifications/ownership-pending')
            ->assertStatus(200);

        $itemA = collect($pendingRes->json('data'))->firstWhere('membership_id', $membershipA->id);
        $this->assertNotNull($itemA);
        $this->assertFalse($itemA['can_see_raw_nib']);
        $this->assertNull($itemA['nib_shm_plain']);

        $this->actingAs($koorBlokA, 'sanctum')
            ->postJson("/api/v1/verifications/ownership/{$membershipA->id}", [
                'action' => 'approve_shm',
            ])->assertStatus(200);
    }

    private function createVerifiedResident(string $name, string $phone, string $unitCode): User
    {
        $unit = Unit::where('code', $unitCode)->firstOrFail();

        $user = User::create([
            'name' => $name,
            'email' => $phone . '@ppmt.local',
            'password' => Hash::make('password123'),
        ]);

        ResidentProfile::create([
            'user_id' => $user->id,
            'phone' => $phone,
            'global_role' => 'warga_ppmt',
            'is_verified' => true,
            'privacy_settings' => ['show_phone' => false, 'show_ownership' => false],
        ]);

        $user->unitMemberships()->create([
            'unit_id' => $unit->id,
            'entity_type' => 'owner',
            'sub_role' => 'primary_owner',
            'is_approved' => true,
        ]);

        return $user;
    }

    private function createResidentWithRole(string $name, string $phone, string $unitCode, string $roleCode): User
    {
        $user = $this->createVerifiedResident($name, $phone, $unitCode);

        UserGovernanceRole::create([
            'user_id' => $user->id,
            'role_code' => $roleCode,
            'scope_type' => 'global',
            'scope_value' => null,
            'is_active' => true,
        ]);

        return $user;
    }
}
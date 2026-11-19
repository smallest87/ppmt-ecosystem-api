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

class GovernanceRoleAndSeparationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PpmtUnitSeeder::class);
        GovernanceRoleDefinition::flushCache();
    }

    /**
     * GOV-01: Pemisahan tegas wewenang Admin Sistem vs Operator Aplikasi pada Role Builder
     */
    public function test_gov_01_only_pure_system_admin_can_manage_role_definitions(): void
    {
        $pureAdmin = $this->createResidentWithRole('Admin Utama', '081300000001', 'A-1', 'admin');
        $operator = $this->createResidentWithRole('Operator Harian', '081300000002', 'A-2', 'operator_aplikasi');
        $ketua = $this->createResidentWithRole('Ketua Paguyuban', '081300000003', 'A-3', 'ketua_paguyuban');

        $payload = [
            'role_code' => 'seksi_kebersihan',
            'label' => 'Seksi Kebersihan & Lingkungan',
            'domain' => 'organization',
            'domain_label' => 'Kabinet / Bidang Operasional',
            'scope' => 'global',
            'appointed_by' => 'Ketua Paguyuban',
            'permissions' => ['Memantau jadwal pengangkutan sampah'],
            'restrictions' => ['Dilarang mengubah jurnal kas Bendahara'],
        ];

        // Operator Aplikasi boleh melihat matriks audit (read-only), tapi DITOLAK saat membuat role baru
        $this->actingAs($operator, 'sanctum')
            ->getJson('/api/v1/governance/access-matrix')
            ->assertStatus(200);

        $this->actingAs($operator, 'sanctum')
            ->postJson('/api/v1/governance/role-definitions', $payload)
            ->assertStatus(403);

        // Ketua Paguyuban DITOLAK mengakses Role Builder teknis
        $this->actingAs($ketua, 'sanctum')
            ->postJson('/api/v1/governance/role-definitions', $payload)
            ->assertStatus(403);

        // Admin Sistem Murni BERHASIL membuat role kustom baru
        $createRes = $this->actingAs($pureAdmin, 'sanctum')
            ->postJson('/api/v1/governance/role-definitions', $payload)
            ->assertStatus(201);

        $definitionId = $createRes->json('data.id');

        // Admin Sistem Murni BERHASIL memperbarui matriks role tersebut
        $this->actingAs($pureAdmin, 'sanctum')
            ->patchJson("/api/v1/governance/role-definitions/{$definitionId}", [
                'label' => 'Seksi Kebersihan & Pertamanan',
                'domain_label' => 'Kabinet / Bidang Operasional',
                'scope' => 'global',
                'appointed_by' => 'Ketua Paguyuban',
                'permissions' => ['Memantau jadwal pengangkutan sampah & taman'],
                'restrictions' => ['Dilarang mengubah jurnal kas Bendahara'],
            ])->assertStatus(200);
    }

    /**
     * GOV-02: Proteksi role inti (is_core) & larangan menghapus role kustom yang masih dipegang warga
     */
    public function test_gov_02_core_role_protection_and_active_holder_guard(): void
    {
        $pureAdmin = $this->createResidentWithRole('Admin Utama', '081300000010', 'A-4', 'admin');
        $ketua = $this->createResidentWithRole('Ketua Paguyuban', '081300000011', 'A-5', 'ketua_paguyuban');
        $warga = $this->createVerifiedResident('Warga Kandidat', '081300000012', 'A-6');

        // 1. Role inti bawaan sistem (seksi_ekonomi) tidak boleh dihapus
        $coreEkonomi = GovernanceRoleDefinition::where('role_code', 'seksi_ekonomi')->firstOrFail();

        $this->actingAs($pureAdmin, 'sanctum')
            ->deleteJson("/api/v1/governance/role-definitions/{$coreEkonomi->id}")
            ->assertStatus(422);

        // 2. Buat role kustom baru lalu angkat warga ke jabatan tersebut
        $customRes = $this->actingAs($pureAdmin, 'sanctum')
            ->postJson('/api/v1/governance/role-definitions', [
                'role_code' => 'seksi_olahraga',
                'label' => 'Seksi Olahraga & Pemuda',
                'domain' => 'organization',
                'scope' => 'global',
                'permissions' => ['Mengelola jadwal lapangan & turnamen'],
                'restrictions' => ['Tidak mengakses kas paguyuban'],
            ])->assertStatus(201);

        $customId = $customRes->json('data.id');

        // Ketua Paguyuban langsung otomatis bisa mengangkat warga ke role kustom domain 'organization'
        $assignRes = $this->actingAs($ketua, 'sanctum')
            ->postJson('/api/v1/governance/assign', [
                'target_user_id' => $warga->id,
                'role_code' => 'seksi_olahraga',
                'scope_type' => 'global',
            ])->assertStatus(201);

        $assignmentId = $assignRes->json('data.id');

        // Admin Sistem ditolak menghapus definisi role selama masih ada pemegang aktif
        $this->actingAs($pureAdmin, 'sanctum')
            ->deleteJson("/api/v1/governance/role-definitions/{$customId}")
            ->assertStatus(422);

        // Setelah Ketua mencabut jabatan warga tersebut, Admin Sistem bisa menghapus definisi role kustom
        $this->actingAs($ketua, 'sanctum')
            ->deleteJson("/api/v1/governance/roles/{$assignmentId}")
            ->assertStatus(200);

        $this->actingAs($pureAdmin, 'sanctum')
            ->deleteJson("/api/v1/governance/role-definitions/{$customId}")
            ->assertStatus(200);
    }

    /**
     * GOV-03: Separation of Duties antara Teknis (Admin/Operator) vs Organisasi (Ketua Paguyuban)
     */
    public function test_gov_03_separation_of_duties_between_technical_and_organization_cabinets(): void
    {
        $pureAdmin = $this->createResidentWithRole('Admin Utama', '081300000020', 'B-1', 'admin');
        $ketua = $this->createResidentWithRole('Ketua Paguyuban', '081300000021', 'B-2', 'ketua_paguyuban');
        $warga = $this->createVerifiedResident('Warga Biasa', '081300000022', 'B-3');

        // Admin Sistem DITOLAK menunjuk kabinet organisasi (seksi_ekonomi / bendahara_paguyuban)
        $this->actingAs($pureAdmin, 'sanctum')
            ->postJson('/api/v1/governance/assign', [
                'target_user_id' => $warga->id,
                'role_code' => 'seksi_ekonomi',
                'scope_type' => 'global',
            ])->assertStatus(403);

        // Ketua Paguyuban DITOLAK menunjuk role teknis (operator_aplikasi / admin)
        $this->actingAs($ketua, 'sanctum')
            ->postJson('/api/v1/governance/assign', [
                'target_user_id' => $warga->id,
                'role_code' => 'operator_aplikasi',
                'scope_type' => 'global',
            ])->assertStatus(403);

        // Ketua Paguyuban BERHASIL menunjuk Seksi Ekonomi & UMKM
        $this->actingAs($ketua, 'sanctum')
            ->postJson('/api/v1/governance/assign', [
                'target_user_id' => $warga->id,
                'role_code' => 'seksi_ekonomi',
                'scope_type' => 'global',
            ])->assertStatus(201);

        // Cek payload /api/v1/me milik warga yang baru diangkat sebagai Seksi Ekonomi
        $this->actingAs($warga->fresh(), 'sanctum')
            ->getJson('/api/v1/me')
            ->assertStatus(200)
            ->assertJsonPath('data.is_seksi_ekonomi', true)
            ->assertJsonPath('data.can_manage_marketplace', true)
            ->assertJsonPath('data.can_view_treasury', false)
            ->assertJsonPath('data.can_manage_announcements', false)
            ->assertJsonPath('data.is_pure_system_admin', false);
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
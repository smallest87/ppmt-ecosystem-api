<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\GovernanceRoleDefinition;
use App\Models\ResidentProfile;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\PpmtUnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ResidentAccessMatrixTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PpmtUnitSeeder::class);
        GovernanceRoleDefinition::flushCache();
    }

    /**
     * RES-01: Daftar lengkap fitur yang DAPAT diakses oleh Warga Terverifikasi biasa
     */
    public function test_res_01_verified_resident_can_access_all_citizen_endpoints(): void
    {
        $warga = $this->createVerifiedResident('Warga Terverifikasi', '081600000001', 'B-8');
        $otherResident = $this->createVerifiedResident('Warga Lain', '081600000002', 'B-9');

        // Buat 1 pengumuman published dan 1 draf (unpublished)
        Announcement::create([
            'title' => 'Info Resmi Warga',
            'category' => 'info',
            'body' => 'Isi pengumuman publik.',
            'is_published' => true,
            'created_by_user_id' => $otherResident->id,
        ]);
        Announcement::create([
            'title' => 'Draf Internal Pengurus',
            'category' => 'info',
            'body' => 'Belum dipublikasikan.',
            'is_published' => false,
            'created_by_user_id' => $otherResident->id,
        ]);

        // 1. Cek /me -> semua flag pengurus harus false
        $this->actingAs($warga, 'sanctum')
            ->getJson('/api/v1/me')
            ->assertStatus(200)
            ->assertJsonPath('data.is_verified', true)
            ->assertJsonPath('data.is_pure_system_admin', false)
            ->assertJsonPath('data.can_view_treasury', false)
            ->assertJsonPath('data.can_manage_announcements', false)
            ->assertJsonPath('data.can_manage_marketplace', false);

        // 2. Pembaruan profil mandiri
        $this->actingAs($warga, 'sanctum')
            ->patchJson('/api/v1/profile', [
                'display_name' => 'Keluarga B-8',
                'privacy_settings' => [
                    'show_phone' => true,
                    'show_ownership' => false,
                    'show_full_name' => true,
                ],
            ])->assertStatus(200);

        // 3. Direktori Warga
        $this->actingAs($warga, 'sanctum')
            ->getJson('/api/v1/residents/directory')
            ->assertStatus(200);

        // 4. Laporan Transparansi Kas Warga (bukan pembukuan bendahara)
        $this->actingAs($warga, 'sanctum')
            ->getJson('/api/v1/treasury/public-report')
            ->assertStatus(200);

        // 5. Pengumuman Warga (hanya yang is_published = true yang muncul, draf tersaring)
        $annRes = $this->actingAs($warga, 'sanctum')
            ->getJson('/api/v1/announcements?all=1')
            ->assertStatus(200);

        $titles = collect($annRes->json('data'))->pluck('title');
        $this->assertTrue($titles->contains('Info Resmi Warga'));
        $this->assertFalse($titles->contains('Draf Internal Pengurus'));

        // 6. Pasar Tetangga: lihat katalog, buka lapak di kavling sendiri, & ubah status lapak
        $this->actingAs($warga, 'sanctum')
            ->getJson('/api/v1/umkm/stores')
            ->assertStatus(200);

        $storeRes = $this->actingAs($warga, 'sanctum')
            ->postJson('/api/v1/umkm/stores', [
                'unit_code' => 'B-8',
                'store_name' => 'Warung B-8',
                'category' => 'sembako',
                'status' => 'open',
            ])->assertStatus(201);

        $storeId = $storeRes->json('data.id');

        $this->actingAs($warga, 'sanctum')
            ->getJson('/api/v1/umkm/my-store')
            ->assertStatus(200)
            ->assertJsonPath('data.id', $storeId);

        $this->actingAs($warga, 'sanctum')
            ->patchJson("/api/v1/umkm/stores/{$storeId}/status", ['status' => 'open_po'])
            ->assertStatus(200);

        // 7. Pemilik Utama dapat membuat undangan keluarga pada kavlingnya sendiri
        $this->actingAs($warga, 'sanctum')
            ->postJson('/api/v1/units/B-8/invites', [
                'entity_type' => 'resident',
                'sub_role' => 'family_member',
            ])->assertStatus(201);
    }

    /**
     * RES-02: Daftar rute pengurus/admin yang WAJIB DITOLAK (403) bagi Warga Terverifikasi biasa
     */
    public function test_res_02_verified_resident_is_blocked_from_all_governance_and_cabinet_endpoints(): void
    {
        $warga = $this->createVerifiedResident('Warga Biasa', '081600000010', 'B-10');
        $wargaLain = $this->createVerifiedResident('Warga Tetangga', '081600000011', 'B-11');

        // 1. Blokir akses Bendahara
        $this->actingAs($warga, 'sanctum')->getJson('/api/v1/treasury/overview')->assertStatus(403);
        $this->actingAs($warga, 'sanctum')->postJson('/api/v1/treasury/ledger', [
            'entry_date' => now()->toDateString(),
            'type' => 'income',
            'category' => 'sumbangan',
            'amount' => 50000,
            'payment_method' => 'cash',
            'description' => 'Tes akses ilegal',
        ])->assertStatus(403);

        // 2. Blokir akses Sekretaris / Ketua (publikasi pengumuman)
        $this->actingAs($warga, 'sanctum')->postJson('/api/v1/announcements', [
            'title' => 'Pengumuman Ilegal',
            'category' => 'info',
            'body' => 'Tidak boleh lolos.',
        ])->assertStatus(403);

        // 3. Blokir akses Buku Induk, Audit RBAC, Penunjukan Role, & Role Builder
        $this->actingAs($warga, 'sanctum')->getJson('/api/v1/governance/occupancy-registry')->assertStatus(403);
        $this->actingAs($warga, 'sanctum')->getJson('/api/v1/governance/access-matrix')->assertStatus(403);
        $this->actingAs($warga, 'sanctum')->postJson('/api/v1/governance/assign', [
            'target_user_id' => $warga->id,
            'role_code' => 'seksi_ekonomi',
            'scope_type' => 'global',
        ])->assertStatus(403);
        $this->actingAs($warga, 'sanctum')->postJson('/api/v1/governance/role-definitions', [
            'role_code' => 'role_palsu',
            'label' => 'Role Palsu',
            'domain' => 'organization',
            'scope' => 'global',
            'permissions' => ['Tes'],
            'restrictions' => ['Tes'],
        ])->assertStatus(403);

        // 4. Blokir verifikasi warga di kavling orang lain
        $this->actingAs($warga, 'sanctum')->postJson('/api/v1/verifications/approve', [
            'target_user_id' => $wargaLain->id,
            'unit_code' => 'B-11',
        ])->assertStatus(403);
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
            'privacy_settings' => [
                'show_phone' => false,
                'show_ownership' => false,
                'show_full_name' => false,
            ],
        ]);

        $user->unitMemberships()->create([
            'unit_id' => $unit->id,
            'entity_type' => 'owner',
            'sub_role' => 'primary_owner',
            'is_approved' => true,
        ]);

        return $user;
    }
}
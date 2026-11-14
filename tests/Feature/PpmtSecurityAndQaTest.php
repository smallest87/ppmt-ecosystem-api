<?php

namespace Tests\Feature;

use App\Models\ResidentProfile;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\PpmtUnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PpmtSecurityAndQaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PpmtUnitSeeder::class);
    }

    /**
     * QA-UNIT-01: Integritas 91 kavling & aturan anti-angka 13 (12A/12B)
     */
    public function test_qa_unit_01_public_units_integrity_and_anti_thirteen_rule(): void
    {
        $response = $this->getJson('/api/v1/public/units');

        $response->assertStatus(200)
            ->assertJsonPath('total', 91);

        $codes = collect($response->json('data'))->pluck('code');

        // Pastikan angka 13 tidak ada di Blok B, C, D
        $this->assertFalse($codes->contains('B-13'));
        $this->assertFalse($codes->contains('C-13'));
        $this->assertFalse($codes->contains('D-13'));

        // Pastikan 12A dan 12B ada di Blok B, C, D, namun tidak di Blok A
        $this->assertTrue($codes->contains('B-12A'));
        $this->assertTrue($codes->contains('B-12B'));
        $this->assertFalse($codes->contains('A-12A'));
    }

    /**
     * QA-AUTH-01 & QA-AUTH-02: Pendaftaran bottom-up, blokir akun Pending, & persetujuan Koordinator Blok
     */
    public function test_qa_auth_01_and_02_claim_workflow_and_coordinator_verification(): void
    {
        // QA-AUTH-01: Tolak klaim unit fiktif B-13
        $this->postJson('/api/v1/auth/register-claim', [
            'name' => 'Penyusup',
            'phone' => '081200000001',
            'password' => 'password123',
            'unit_code' => 'B-13',
            'entity_type' => 'owner',
            'sub_role' => 'primary_owner',
        ])->assertStatus(422);

        // Buat akun Koordinator Blok ZONA-4 (Unit D-1)
        $koordinator = $this->createVerifiedResident('Pak Koordinator', '081200000002', 'D-1', 'owner', 'primary_owner', 'koordinator_blok');

        // Warga baru mendaftar mandiri di unit C-21 (ZONA-4)
        $registerResponse = $this->postJson('/api/v1/auth/register-claim', [
            'name' => 'Warga C-21',
            'phone' => '081200000003',
            'password' => 'password123',
            'unit_code' => 'C-21',
            'entity_type' => 'owner',
            'sub_role' => 'primary_owner',
        ])->assertStatus(201);

        $wargaToken = $registerResponse->json('token');
        $wargaId = $registerResponse->json('data.user_id');

        // QA-AUTH-02: Akun Pending ditolak saat mengakses Pasar Tetangga UMKM
        $this->withToken($wargaToken)
            ->getJson('/api/v1/umkm/stores')
            ->assertStatus(403)
            ->assertJsonPath('code', 'UNVERIFIED_WARGA_PPMT');

        // Koordinator Blok ZONA-4 menyetujui pendaftaran Warga C-21
        $this->actingAs($koordinator, 'sanctum')
            ->postJson('/api/v1/verifications/approve', [
                'target_user_id' => $wargaId,
                'unit_code' => 'C-21',
            ])->assertStatus(200);

        // Setelah diverifikasi, Warga C-21 bebas mengakses Pasar Tetangga
        $this->withToken($wargaToken)
            ->getJson('/api/v1/umkm/stores')
            ->assertStatus(200);
    }

    /**
     * QA-SEC-01 & QA-SEC-03: Anti-manipulasi hak undangan & kedaluwarsa sewa tenant
     */
    public function test_qa_sec_01_and_03_invite_authorization_and_tenant_expiration(): void
    {
        $owner = $this->createVerifiedResident('Pemilik A-5', '081200000010', 'A-5', 'owner', 'primary_owner');
        $coResident = $this->createVerifiedResident('Anggota A-5', '081200000011', 'A-5', 'resident', 'co_resident');

        // QA-SEC-01: co_resident ditolak saat membuat undangan unit meski profilnya lengkap
        $coResident->profile->update([
            'nik_encrypted' => '3507010101870001',
            'kk_encrypted' => '3507010101870002',
            'vehicles' => [['type' => 'mobil', 'plate' => 'N 1234 AB']],
        ]);

        $this->actingAs($coResident, 'sanctum')
            ->postJson('/api/v1/units/A-5/invites', [
                'entity_type' => 'resident',
                'sub_role' => 'family_member',
            ])->assertStatus(403);

        // Pemilik utama berhasil membuat undangan penyewa (tenant)
        $inviteResponse = $this->actingAs($owner, 'sanctum')
            ->postJson('/api/v1/units/A-5/invites', [
                'entity_type' => 'resident',
                'sub_role' => 'tenant',
                'member_valid_until' => now()->addDays(30)->toDateString(),
            ])->assertStatus(201);

        $inviteToken = $inviteResponse->json('data.invite_token');

        // Penyewa mendaftar menggunakan invite_token -> langsung terverifikasi
        $tenantClaim = $this->postJson('/api/v1/auth/register-claim', [
            'name' => 'Penyewa A-5',
            'phone' => '081200000012',
            'password' => 'password123',
            'unit_code' => 'A-5',
            'entity_type' => 'resident',
            'sub_role' => 'tenant',
            'invite_token' => $inviteToken,
        ])->assertStatus(201)
          ->assertJsonPath('data.is_verified', true);

        $tenantUser = User::find($tenantClaim->json('data.user_id'));

        // QA-SEC-03: Simulasikan masa sewa habis kemarin -> middleware langsung memblokir
        $tenantUser->unitMemberships()->update(['valid_until' => now()->subDay()->toDateString()]);

        $this->actingAs($tenantUser, 'sanctum')
            ->getJson('/api/v1/umkm/stores')
            ->assertStatus(403)
            ->assertJsonPath('code', 'MEMBERSHIP_EXPIRED');
    }

    /**
     * QA-SEC-02 & QA-DB-01: Privacy Masking di JSON & Enkripsi AES-256 di Database
     */
    public function test_qa_sec_02_and_db_01_privacy_masking_and_aes_encryption(): void
    {
        $wargaA = $this->createVerifiedResident('Warga Privat', '081200000020', 'B-2', 'owner', 'primary_owner');
        $wargaB = $this->createVerifiedResident('Warga Tetangga', '081200000021', 'B-3', 'owner', 'primary_owner');

        // Warga A mengisi NIK/KK dan menyetel show_phone = false
        $this->actingAs($wargaA, 'sanctum')
            ->patchJson('/api/v1/profile', [
                'nik' => '3507123456780001',
                'kk' => '3507123456780002',
                'privacy_settings' => [
                    'show_phone' => false,
                    'show_ownership' => false,
                ],
            ])->assertStatus(200);

        // QA-DB-01: Cek langsung ke database mentah bahwa NIK tidak terbaca sebagai plaintext
        $rawProfile = DB::table('resident_profiles')->where('user_id', $wargaA->id)->first();
        $this->assertNotEquals('3507123456780001', $rawProfile->nik_encrypted);
        $this->assertEquals('3507123456780001', $wargaA->profile->fresh()->nik_encrypted);

        // QA-SEC-02: Warga B memeriksa direktori warga -> nomor HP Warga A wajib null
        $directoryResponse = $this->actingAs($wargaB, 'sanctum')
            ->getJson('/api/v1/residents/directory')
            ->assertStatus(200);

        $wargaAData = collect($directoryResponse->json('data'))->firstWhere('id', $wargaA->id);
        $this->assertNull($wargaAData['phone']);
    }

    private function createVerifiedResident(
        string $name,
        string $phone,
        string $unitCode,
        string $entityType,
        string $subRole,
        string $globalRole = 'warga_ppmt'
    ): User {
        $unit = Unit::where('code', $unitCode)->firstOrFail();

        $user = User::create([
            'name' => $name,
            'email' => $phone . '@ppmt.local',
            'password' => Hash::make('password123'),
        ]);

        ResidentProfile::create([
            'user_id' => $user->id,
            'phone' => $phone,
            'global_role' => $globalRole,
            'is_verified' => true,
            'privacy_settings' => ['show_phone' => false, 'show_ownership' => false],
        ]);

        $user->unitMemberships()->create([
            'unit_id' => $unit->id,
            'entity_type' => $entityType,
            'sub_role' => $subRole,
            'is_approved' => true,
        ]);

        return $user;
    }
}
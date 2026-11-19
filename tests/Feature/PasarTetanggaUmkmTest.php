<?php

namespace Tests\Feature;

use App\Models\ResidentProfile;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\PpmtUnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PasarTetanggaUmkmTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PpmtUnitSeeder::class);
    }

    /**
     * UMKM-01: Warga hanya boleh membuka lapak di unitnya sendiri & tidak boleh edit lapak tetangga
     */
    public function test_umkm_01_resident_store_ownership_and_privacy_masking(): void
    {
        $seller = $this->createVerifiedResident('Raden Julian', '081500000001', 'D-10', [
            'show_full_name' => false,
        ]);
        $neighbor = $this->createVerifiedResident('Tetangga Sebelah', '081500000002', 'D-11');

        // 1. Ditolak membuka lapak pada unit yang bukan miliknya/huniannya
        $this->actingAs($seller, 'sanctum')
            ->postJson('/api/v1/umkm/stores', [
                'unit_code' => 'D-11',
                'store_name' => 'Warung Palsu',
                'category' => 'kuliner',
            ])->assertStatus(403);

        // 2. Berhasil membuka lapak pada unit hunian sendiri (D-10)
        $storeRes = $this->actingAs($seller, 'sanctum')
            ->postJson('/api/v1/umkm/stores', [
                'unit_code' => 'D-10',
                'store_name' => 'Dapur Bu Julian',
                'category' => 'kuliner',
                'description' => 'Sarapan pagi & camilan rumahan',
                'status' => 'open',
                'catalog_items' => [
                    [
                        'id' => 'item-1',
                        'name' => 'Nasi Uduk Komplit',
                        'category' => 'kuliner',
                        'badge_type' => 'ready',
                        'price' => 12000,
                        'is_available' => true,
                    ],
                ],
            ])->assertStatus(201);

        $storeId = $storeRes->json('data.id');

        // 3. Ditolak membuka lapak kedua dengan akun yang sama
        $this->actingAs($seller, 'sanctum')
            ->postJson('/api/v1/umkm/stores', [
                'unit_code' => 'D-10',
                'store_name' => 'Lapak Cabang',
                'category' => 'sembako',
            ])->assertStatus(422);

        // 4. Tetangga sebelah DITOLAK mengubah katalog maupun status buka/tutup lapak D-10
        $this->actingAs($neighbor, 'sanctum')
            ->patchJson("/api/v1/umkm/stores/{$storeId}", [
                'store_name' => 'Diubah Tetangga',
            ])->assertStatus(403);

        $this->actingAs($neighbor, 'sanctum')
            ->patchJson("/api/v1/umkm/stores/{$storeId}/status", [
                'status' => 'closed',
            ])->assertStatus(403);

        // 5. Etalase publik menghormati privacy_settings.show_full_name = false (hanya nama depan)
        $indexRes = $this->actingAs($neighbor, 'sanctum')
            ->getJson('/api/v1/umkm/stores')
            ->assertStatus(200);

        $storeItem = collect($indexRes->json('data'))->firstWhere('id', $storeId);
        $this->assertEquals('Raden', $storeItem['owner']['name']);
    }

    private function createVerifiedResident(string $name, string $phone, string $unitCode, array $privacy = []): User
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
            'privacy_settings' => array_merge([
                'show_phone' => false,
                'show_ownership' => false,
                'show_full_name' => false,
            ], $privacy),
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
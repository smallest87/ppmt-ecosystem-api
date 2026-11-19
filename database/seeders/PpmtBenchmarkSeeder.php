<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\CashLedgerEntry;
use App\Models\DuesPayment;
use App\Models\ResidentProfile;
use App\Models\UmkmStore;
use App\Models\Unit;
use App\Models\User;
use App\Models\UserGovernanceRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class PpmtBenchmarkSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(PpmtUnitSeeder::class);

        $units = Unit::where('unit_type', '!=', 'fasum')->orderBy('id')->get();
        $passwordHash = Hash::make('password123');
        $createdUsers = [];

        // 1. Isi 90 kavling dengan 180 warga (Kepala Keluarga + Anggota Keluarga) lengkap dengan AES-256 NIK/KK & SHM
        foreach ($units as $idx => $unit) {
            $phonePrimary = '0819000' . str_pad((string) ($idx + 1), 4, '0', STR_PAD_LEFT);
            $primary = User::create([
                'name' => "Warga Utama {$unit->code}",
                'email' => "{$phonePrimary}@ppmt.local",
                'password' => $passwordHash,
            ]);

            ResidentProfile::create([
                'user_id' => $primary->id,
                'display_name' => "Keluarga {$unit->code}",
                'phone' => $phonePrimary,
                'gender' => $idx % 2 === 0 ? 'male' : 'female',
                'global_role' => 'warga_ppmt',
                'is_verified' => true,
                'is_ownership_doc_verified' => $idx % 3 === 0,
                'nik_encrypted' => '350701010187' . str_pad((string) ($idx + 1), 4, '0', STR_PAD_LEFT),
                'kk_encrypted' => '350702020287' . str_pad((string) ($idx + 1), 4, '0', STR_PAD_LEFT),
                'vehicles' => [['type' => 'mobil', 'plate' => 'N ' . (1000 + $idx) . ' AB']],
                'privacy_settings' => [
                    'show_phone' => $idx % 2 === 0,
                    'show_ownership' => $idx % 3 === 0,
                    'show_full_name' => $idx % 4 === 0,
                ],
            ]);

            $primary->unitMemberships()->create([
                'unit_id' => $unit->id,
                'entity_type' => 'owner',
                'sub_role' => 'primary_owner',
                'is_owner' => true,
                'owner_role' => 'primary_owner',
                'nib_shm_encrypted' => '12.05.04.01.1.' . str_pad((string) ($idx + 100), 5, '0', STR_PAD_LEFT),
                'ownership_verification_status' => $idx % 3 === 0 ? 'verified_shm' : 'unverified_claim',
                'is_primary_contact' => true,
                'is_approved' => true,
            ]);

            // Anggota keluarga kedua per unit
            $phoneFamily = '0819100' . str_pad((string) ($idx + 1), 4, '0', STR_PAD_LEFT);
            $family = User::create([
                'name' => "Anggota {$unit->code}",
                'email' => "{$phoneFamily}@ppmt.local",
                'password' => $passwordHash,
            ]);

            ResidentProfile::create([
                'user_id' => $family->id,
                'phone' => $phoneFamily,
                'gender' => $idx % 2 === 0 ? 'female' : 'male',
                'global_role' => 'warga_ppmt',
                'is_verified' => true,
                'nik_encrypted' => '350703030390' . str_pad((string) ($idx + 1), 4, '0', STR_PAD_LEFT),
                'privacy_settings' => ['show_phone' => false, 'show_ownership' => false, 'show_full_name' => false],
            ]);

            $family->unitMemberships()->create([
                'unit_id' => $unit->id,
                'entity_type' => 'resident',
                'sub_role' => 'family_member',
                'is_approved' => true,
            ]);

            $unit->update(['occupancy_status' => 'occupied']);
            $createdUsers[] = $primary;
        }

        // 2. Tetapkan pemegang jabatan lengkap (Admin, Ketua, Sekretaris, Bendahara, Seksi Ekonomi, Koordinator)
        $rolesMap = [
            0 => 'admin',
            1 => 'ketua_paguyuban',
            2 => 'sekretaris_paguyuban',
            3 => 'bendahara_paguyuban',
            4 => 'seksi_ekonomi',
        ];
        foreach ($rolesMap as $uIdx => $roleCode) {
            UserGovernanceRole::create([
                'user_id' => $createdUsers[$uIdx]->id,
                'role_code' => $roleCode,
                'scope_type' => 'global',
                'is_active' => true,
            ]);
        }

        // 3. Buat 30 lapak Pasar Tetangga masing-masing dengan 6 item katalog (180 item total)
        $categories = ['kuliner', 'sembako', 'jasa', 'pinjam_alat', 'lainnya'];
        for ($i = 0; $i < 30; $i++) {
            $owner = $createdUsers[$i + 5];
            $unit = $units[$i + 5];
            $items = [];
            for ($k = 1; $k <= 6; $k++) {
                $items[] = [
                    'id' => "item-{$i}-{$k}",
                    'name' => "Produk Unggulan {$k} Lapak {$unit->code}",
                    'category' => $categories[$i % count($categories)],
                    'badge_type' => $k % 2 === 0 ? 'po' : 'ready',
                    'price' => 10000 + ($k * 2500),
                    'unit_note' => 'per porsi',
                    'is_available' => true,
                ];
            }

            UmkmStore::create([
                'unit_id' => $unit->id,
                'owner_user_id' => $owner->id,
                'store_name' => "Lapak Warga {$unit->code}",
                'category' => $categories[$i % count($categories)],
                'description' => 'Melayani pesanan tetangga PPMT setiap hari.',
                'status' => $i % 5 === 0 ? 'open_po' : 'open',
                'whatsapp_number' => $owner->profile->phone,
                'catalog_items' => $items,
            ]);
        }

        // 4. Simulasi 4 bulan iuran sampah (360 transaksi) + 140 mutasi operasional kas = 500 baris buku kas
        $bendahara = $createdUsers[3];
        $months = [
            now()->subMonths(3)->format('Y-m'),
            now()->subMonths(2)->format('Y-m'),
            now()->subMonth()->format('Y-m'),
            now()->format('Y-m'),
        ];

        foreach ($months as $m) {
            foreach ($units as $uIdx => $unit) {
                if ($uIdx % 10 === 0) {
                    continue; // sisakan 10% belum bayar
                }
                $ledger = CashLedgerEntry::create([
                    'entry_date' => "{$m}-10",
                    'type' => 'income',
                    'category' => 'iuran_sampah',
                    'amount' => 20000,
                    'payment_method' => $uIdx % 2 === 0 ? 'transfer' : 'cash',
                    'description' => "Iuran Sampah {$unit->code} ({$m})",
                    'unit_id' => $unit->id,
                    'recorded_by_user_id' => $bendahara->id,
                ]);

                DuesPayment::create([
                    'unit_id' => $unit->id,
                    'dues_type' => 'iuran_sampah',
                    'period_month' => $m,
                    'amount' => 20000,
                    'status' => 'paid',
                    'payment_method' => $uIdx % 2 === 0 ? 'transfer' : 'cash',
                    'paid_at' => "{$m}-10",
                    'cash_ledger_entry_id' => $ledger->id,
                    'recorded_by_user_id' => $bendahara->id,
                ]);
            }
        }

        for ($e = 1; $e <= 140; $e++) {
            CashLedgerEntry::create([
                'entry_date' => now()->subDays($e % 90)->toDateString(),
                'type' => $e % 4 === 0 ? 'income' : 'expense',
                'category' => $e % 4 === 0 ? 'sumbangan' : 'pengangkutan_sampah',
                'amount' => 50000 + (($e % 10) * 15000),
                'payment_method' => 'transfer',
                'description' => "Transaksi Operasional Paguyuban #{$e}",
                'recorded_by_user_id' => $bendahara->id,
            ]);
        }

        // 5. Simulasi 50 Pengumuman Warga
        $sekretaris = $createdUsers[2];
        for ($a = 1; $a <= 50; $a++) {
            Announcement::create([
                'title' => "Informasi & Pengumuman Warga PPMT #{$a}",
                'category' => $a % 3 === 0 ? 'kerja_bakti' : 'info',
                'body' => str_repeat('Pemberitahuan resmi dari pengurus paguyuban untuk seluruh warga perumahan. ', 6),
                'is_pinned' => $a <= 2,
                'is_published' => true,
                'created_by_user_id' => $sekretaris->id,
            ]);
        }
    }
}
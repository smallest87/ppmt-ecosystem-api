<?php

namespace Database\Seeders;

use App\Models\ResidentProfile;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class GenesisAdminSeeder extends Seeder
{
    public function run(): void
    {
        $unit = Unit::where('code', 'C-21')->first();
        if (! $unit) {
            return;
        }

        $admin = User::updateOrCreate(
            ['email' => '081200000000@ppmt.local'],
            [
                'name' => 'Admin Paguyuban PPMT',
                'password' => Hash::make('password123'),
            ]
        );

        ResidentProfile::updateOrCreate(
            ['user_id' => $admin->id],
            [
                'phone' => '081200000000',
                'global_role' => 'admin',
                'is_verified' => true,
                'privacy_settings' => ['show_phone' => true, 'show_ownership' => true],
            ]
        );

        $admin->unitMemberships()->updateOrCreate(
            ['unit_id' => $unit->id, 'entity_type' => 'owner'],
            [
                'sub_role' => 'primary_owner',
                'is_primary_contact' => true,
                'is_approved' => true,
            ]
        );

        $unit->update(['occupancy_status' => 'occupied']);
    }
}
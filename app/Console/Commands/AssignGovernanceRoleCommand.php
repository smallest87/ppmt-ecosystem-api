<?php

namespace App\Console\Commands;

use App\Models\ResidentProfile;
use App\Models\UserGovernanceRole;
use Illuminate\Console\Command;

class AssignGovernanceRoleCommand extends Command
{
    protected $signature = 'ppmt:assign-governance
                            {phone : Nomor WhatsApp warga terdaftar}
                            {role=operator_aplikasi : Kode jabatan (operator_aplikasi, ketua_paguyuban, koordinator_zona, koordinator_blok)}
                            {--scope-type=global : global, zone, atau block}
                            {--scope-value= : Nilai cakupan (misal ZONA-4 atau C)}';

    protected $description = 'Menyematkan jabatan tata kelola PPMT secara prosedural kepada warga terdaftar';

    public function handle(): int
    {
        $phone = $this->argument('phone');
        $role = $this->argument('role');
        $scopeType = $this->option('scope-type');
        $scopeValue = $scopeType === 'global' ? null : strtoupper((string) $this->option('scope-value'));

        $profile = ResidentProfile::where('phone', $phone)->with('user')->first();

        if (! $profile || ! $profile->user) {
            $this->error("Warga dengan nomor WhatsApp {$phone} tidak ditemukan.");
            return self::FAILURE;
        }

        // Pastikan akun terverifikasi & unitnya disahkan saat penetapan Genesis Node
        if (! $profile->is_verified) {
            $profile->update(['is_verified' => true]);
            $profile->user->unitMemberships()->update(['is_approved' => true]);
            $profile->user->unitMemberships()->each(function ($m) {
                $m->unit()->where('occupancy_status', 'pending')->update(['occupancy_status' => 'occupied']);
            });
            $this->info("Status kewargaan {$profile->user->name} otomatis diaktifkan sebagai Genesis Node.");
        }

        $assignment = UserGovernanceRole::updateOrCreate(
            [
                'user_id' => $profile->user->id,
                'role_code' => $role,
                'scope_type' => $scopeType,
                'scope_value' => $scopeValue,
            ],
            [
                'assigned_by_user_id' => null,
                'is_active' => true,
            ]
        );

        $this->info("Berhasil menyematkan jabatan [{$assignment->display_label}] kepada {$profile->user->name} ({$phone}).");
        return self::SUCCESS;
    }
}
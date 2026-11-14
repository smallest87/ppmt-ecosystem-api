<?php

namespace App\Console\Commands;

use App\Models\ResidentProfile;
use App\Models\UserGovernanceRole;
use App\Models\VerificationVoucher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AssignGovernanceRoleCommand extends Command
{
    protected $signature = 'ppmt:assign-governance
                            {phone : Nomor WhatsApp warga terdaftar}
                            {role=admin : Kode jabatan (admin, operator_aplikasi, ketua_paguyuban, sekretaris_paguyuban, bendahara_paguyuban, koordinator_zona, koordinator_blok, keamanan_lingkungan)}
                            {--scope-type=global : global, zone, atau block}
                            {--scope-value= : Nilai cakupan (misal ZONA-4 atau C)}';

    protected $description = 'Menyematkan jabatan tata kelola PPMT secara prosedural kepada warga terdaftar';

    public function handle(): int
    {
        $phone = trim((string) $this->argument('phone'));
        $role = trim((string) $this->argument('role'));
        $scopeType = trim((string) $this->option('scope-type'));
        $scopeValue = $scopeType === 'global' ? null : strtoupper((string) $this->option('scope-value'));

        $allowedRoles = [
            'admin',
            'operator_aplikasi',
            'ketua_paguyuban',
            'sekretaris_paguyuban',
            'bendahara_paguyuban',
            'koordinator_zona',
            'koordinator_blok',
            'keamanan_lingkungan',
        ];

        if (! in_array($role, $allowedRoles, true)) {
            $this->error("Kode jabatan '{$role}' tidak dikenal. Pilihan: " . implode(', ', $allowedRoles));
            return self::FAILURE;
        }

        $profile = ResidentProfile::where('phone', $phone)->with(['user.unitMemberships.unit'])->first();

        if (! $profile || ! $profile->user) {
            $this->error("Warga dengan nomor WhatsApp {$phone} tidak ditemukan. Pastikan warga sudah mendaftar mandiri terlebih dahulu.");
            return self::FAILURE;
        }

        $assignment = DB::transaction(function () use ($profile, $role, $scopeType, $scopeValue) {
            $user = $profile->user;

            // Pastikan status kewargaan murni 'warga_ppmt' & terverifikasi saat penetapan Genesis Node
            if (! $profile->is_verified || $profile->global_role !== 'warga_ppmt') {
                $profile->update([
                    'global_role' => 'warga_ppmt',
                    'is_verified' => true,
                ]);

                $user->unitMemberships()->update(['is_approved' => true]);

                $user->unitMemberships->each(function ($m) {
                    $m->unit()->where('occupancy_status', 'pending')->update([
                        'occupancy_status' => $m->sub_role === 'tenant' ? 'rented' : 'occupied',
                    ]);

                    VerificationVoucher::firstOrCreate(
                        [
                            'target_user_id' => $m->user_id,
                            'unit_id' => $m->unit_id,
                        ],
                        [
                            'voucher_user_id' => $m->user_id,
                            'method' => 'genesis_seed',
                        ]
                    );
                });

                $this->info("Status kewargaan {$user->name} otomatis diaktifkan sebagai Warga PPMT Terverifikasi.");
            }

            return UserGovernanceRole::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'role_code' => $role,
                    'scope_type' => $scopeType,
                    'scope_value' => $scopeValue,
                ],
                [
                    'assigned_by_user_id' => null,
                    'is_active' => true,
                ]
            );
        });

        $this->info("Berhasil menyematkan jabatan [{$assignment->display_label}] kepada {$profile->user->name} ({$phone}).");
        return self::SUCCESS;
    }
}
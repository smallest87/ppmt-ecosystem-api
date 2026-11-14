<?php

namespace App\Console\Commands;

use App\Models\ResidentProfile;
use App\Models\UserGovernanceRole;
use Illuminate\Console\Command;

class RevokeGovernanceRoleCommand extends Command
{
    protected $signature = 'ppmt:revoke-governance
                            {phone : Nomor WhatsApp warga terdaftar}
                            {role=admin : Kode jabatan (admin, operator_aplikasi, ketua_paguyuban, sekretaris_paguyuban, bendahara_paguyuban, koordinator_zona, koordinator_blok, keamanan_lingkungan)}
                            {--scope-type=global : global, zone, atau block}
                            {--scope-value= : Nilai cakupan (misal ZONA-4 atau C)}
                            {--force : Paksa pencabutan meskipun merupakan Admin terakhir}';

    protected $description = 'Mencabut jabatan tata kelola PPMT secara prosedural dari warga terdaftar';

    public function handle(): int
    {
        $phone = trim((string) $this->argument('phone'));
        $role = trim((string) $this->argument('role'));
        $scopeType = trim((string) $this->option('scope-type'));
        $scopeValue = $scopeType === 'global' ? null : strtoupper((string) $this->option('scope-value'));

        $profile = ResidentProfile::where('phone', $phone)->with('user')->first();

        if (! $profile || ! $profile->user) {
            $this->error("Warga dengan nomor WhatsApp '{$phone}' tidak ditemukan.");
            return self::FAILURE;
        }

        $query = UserGovernanceRole::where('user_id', $profile->user->id)
            ->where('role_code', $role)
            ->where('scope_type', $scopeType)
            ->where('is_active', true);

        if ($scopeValue !== null && $scopeValue !== '') {
            $query->where('scope_value', $scopeValue);
        }

        $assignment = $query->first();

        if (! $assignment) {
            $this->warn("Warga {$profile->user->name} ({$phone}) tidak memiliki jabatan aktif '{$role}' pada cakupan tersebut.");
            return self::FAILURE;
        }

        // Kunci pengaman Admin terakhir (kecuali disertai flag --force)
        if (in_array($role, ['admin', 'operator_aplikasi'], true) && ! $this->option('force')) {
            $activeAdminCount = UserGovernanceRole::whereIn('role_code', ['admin', 'operator_aplikasi'])
                ->where('is_active', true)
                ->count();

            if ($activeAdminCount <= 1) {
                $this->error("Ditolak: {$profile->user->name} adalah satu-satunya Admin aktif. Delegasikan jabatan Admin ke warga lain terlebih dahulu (atau gunakan --force jika benar-benar sengaja).");
                return self::FAILURE;
            }
        }

        $label = $assignment->display_label;
        $assignment->update(['is_active' => false]);

        $this->info("Berhasil mencabut jabatan [{$label}] dari {$profile->user->name} ({$phone}). Status kewargaannya tetap aktif sebagai Warga PPMT.");

        return self::SUCCESS;
    }
}
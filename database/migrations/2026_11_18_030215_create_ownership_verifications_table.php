<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ownership_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_membership_id')->constrained('unit_memberships')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role_code', 50)->nullable();
            $table->string('role_label', 120)->nullable();
            $table->timestamp('verified_at')->useCurrent();
            $table->timestamps();

            $table->unique(['unit_membership_id', 'user_id'], 'ov_membership_user_unique');
        });

        // Migrasi data pengesahan lama yang sudah ada di unit_memberships ke tabel kolektif
        $existingVerified = DB::table('unit_memberships')
            ->where('ownership_verification_status', 'verified_shm')
            ->whereNotNull('ownership_verified_by_user_id')
            ->get();

        foreach ($existingVerified as $row) {
            $govRole = DB::table('user_governance_roles')
                ->where('user_id', $row->ownership_verified_by_user_id)
                ->where('is_active', true)
                ->first();

            $roleLabels = [
                'admin' => 'Admin Sistem',
                'operator_aplikasi' => 'Operator Aplikasi',
                'ketua_paguyuban' => 'Ketua Paguyuban',
                'sekretaris_paguyuban' => 'Sekretaris Paguyuban',
                'bendahara_paguyuban' => 'Bendahara Paguyuban',
                'koordinator_zona' => 'Koordinator Zona',
                'koordinator_blok' => 'Koordinator Blok',
                'keamanan_lingkungan' => 'Keamanan Lingkungan',
            ];

            $roleCode = $govRole->role_code ?? 'pengurus';
            $baseLabel = $roleLabels[$roleCode] ?? 'Pengurus PPMT';
            if (! empty($govRole?->scope_value)) {
                $baseLabel .= ' ' . $govRole->scope_value;
            }

            DB::table('ownership_verifications')->updateOrInsert(
                [
                    'unit_membership_id' => $row->id,
                    'user_id' => $row->ownership_verified_by_user_id,
                ],
                [
                    'role_code' => $roleCode,
                    'role_label' => $baseLabel,
                    'verified_at' => $row->ownership_verified_at ?? now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ownership_verifications');
    }
};
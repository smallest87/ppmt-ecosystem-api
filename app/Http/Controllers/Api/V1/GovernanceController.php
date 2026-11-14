<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserGovernanceRole;
use App\Models\Unit;
use App\Models\VerificationVoucher;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GovernanceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (! $request->user()->isGlobalGovernor()) {
            return response()->json(['message' => 'Akses ditolak. Hanya Operator Aplikasi atau Ketua Paguyuban.'], 403);
        }

        $assignments = UserGovernanceRole::query()
            ->where('is_active', true)
            ->with(['user:id,name', 'user.unitMemberships.unit:id,code', 'assignedBy:id,name'])
            ->latest()
            ->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'user_id' => $r->user_id,
                'user_name' => $r->user->name,
                'unit_codes' => $r->user->unitMemberships->pluck('unit.code')->join(', '),
                'role_code' => $r->role_code,
                'display_label' => $r->display_label,
                'scope_type' => $r->scope_type,
                'scope_value' => $r->scope_value,
                'assigned_by' => $r->assignedBy?->name ?? 'Sistem Genesis',
            ]);

        return response()->json(['data' => $assignments]);
    }

    public function assign(Request $request): JsonResponse
    {
        $actor = $request->user();

        if (! $actor->hasGovernanceRole('operator_aplikasi') && ! $actor->hasGovernanceRole('ketua_paguyuban')) {
            return response()->json([
                'message' => 'Akses ditolak. Anda tidak memiliki wewenang Operator Aplikasi untuk menyematkan jabatan.',
            ], 403);
        }

        $validated = $request->validate([
            'target_user_id' => ['required', 'integer', 'exists:users,id'],
            'role_code' => ['required', Rule::in([
                'operator_aplikasi',
                'ketua_paguyuban',
                'sekretaris_paguyuban',
                'bendahara_paguyuban',
                'koordinator_zona',
                'koordinator_blok',
                'keamanan_lingkungan',
            ])],
            'scope_type' => ['required', Rule::in(['global', 'zone', 'block'])],
            'scope_value' => ['nullable', 'string', 'max:30'],
        ]);

        // Kunci pengaman: Hanya operator_aplikasi yang boleh mengangkat operator_aplikasi baru
        if ($validated['role_code'] === 'operator_aplikasi' && ! $actor->hasGovernanceRole('operator_aplikasi')) {
            return response()->json([
                'message' => 'Hanya Operator Aplikasi aktif yang dapat menetapkan Operator Aplikasi baru.',
            ], 403);
        }

        $targetUser = User::with('profile')->findOrFail($validated['target_user_id']);

        if (! $targetUser->profile?->is_verified) {
            return response()->json([
                'message' => 'Jabatan pengurus hanya dapat disematkan kepada warga PPMT yang sudah terverifikasi.',
            ], 422);
        }

        $scopeValue = $validated['scope_type'] === 'global' ? null : strtoupper($validated['scope_value'] ?? '');

        $assignment = UserGovernanceRole::updateOrCreate(
            [
                'user_id' => $targetUser->id,
                'role_code' => $validated['role_code'],
                'scope_type' => $validated['scope_type'],
                'scope_value' => $scopeValue,
            ],
            [
                'assigned_by_user_id' => $actor->id,
                'is_active' => true,
            ]
        );

        return response()->json([
            'message' => "Jabatan '{$assignment->display_label}' berhasil disematkan kepada {$targetUser->name}.",
            'data' => $assignment,
        ], 201);
    }

    public function revoke(Request $request, UserGovernanceRole $role): JsonResponse
    {
        $actor = $request->user();

        if (! $actor->hasGovernanceRole('operator_aplikasi') && ! $actor->hasGovernanceRole('ketua_paguyuban')) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        if ($role->role_code === 'operator_aplikasi' && $role->user_id === $actor->id) {
            return response()->json([
                'message' => 'Demi mencegah penguncian sistem, Anda tidak dapat mencabut peran Operator Aplikasi dari diri sendiri.',
            ], 422);
        }

        $role->update(['is_active' => false]);

        return response()->json([
            'message' => "Jabatan '{$role->display_label}' telah dicabut.",
        ]);
    }

    public function assistedRegister(Request $request): JsonResponse
    {
        $actor = $request->user();

        if (! $actor->isGlobalGovernor()) {
            return response()->json([
                'message' => 'Akses ditolak. Hanya Operator Aplikasi atau Ketua Paguyuban yang dapat mendaftarkan warga secara langsung.',
            ], 403);
        }

        $validated = $request->validate([
            'unit_code' => ['required', 'string', 'exists:units,code'],
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['required', 'string', 'max:20', 'unique:resident_profiles,phone'],
            'password' => ['required', 'string', 'min:8'],
            'entity_type' => ['required', Rule::in(['owner', 'resident'])],
            'sub_role' => ['required', Rule::in([
                'primary_owner', 'co_owner',
                'primary_resident', 'family_member', 'co_resident', 'tenant', 'caretaker',
            ])],
        ]);

        $unit = Unit::where('code', $validated['unit_code'])->firstOrFail();

        if ($unit->unit_type === 'fasum') {
            return response()->json(['message' => 'Unit fasilitas umum tidak dapat didaftarkan sebagai hunian warga.'], 422);
        }

        $newUser = DB::transaction(function () use ($validated, $unit, $actor) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['phone'] . '@ppmt.local',
                'password' => Hash::make($validated['password']),
            ]);

            $user->profile()->create([
                'phone' => $validated['phone'],
                'global_role' => 'warga_ppmt',
                'is_verified' => true,
                'privacy_settings' => [
                    'show_phone' => false,
                    'show_ownership' => false,
                ],
            ]);

            $user->unitMemberships()->create([
                'unit_id' => $unit->id,
                'entity_type' => $validated['entity_type'],
                'sub_role' => $validated['sub_role'],
                'is_primary_contact' => in_array($validated['sub_role'], ['primary_owner', 'primary_resident']),
                'is_approved' => true,
            ]);

            // Tetap tercatat sah dalam Verification Ledger (Web of Trust)
            VerificationVoucher::create([
                'target_user_id' => $user->id,
                'unit_id' => $unit->id,
                'voucher_user_id' => $actor->id,
                'method' => 'in_person_vouch',
            ]);

            if ($unit->occupancy_status === 'unclaimed' || $unit->occupancy_status === 'pending') {
                $unit->update([
                    'occupancy_status' => $validated['sub_role'] === 'tenant' ? 'rented' : 'occupied',
                ]);
            }

            return $user;
        });

        return response()->json([
            'message' => "Warga {$newUser->name} berhasil didaftarkan dan langsung aktif pada Kavling {$unit->code}.",
            'data' => [
                'user_id' => $newUser->id,
                'name' => $newUser->name,
                'unit_code' => $unit->code,
            ],
        ], 201);
    }
}
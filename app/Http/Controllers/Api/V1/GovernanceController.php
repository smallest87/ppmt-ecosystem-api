<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserGovernanceRole;
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
}
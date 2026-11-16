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
            return response()->json(['message' => 'Akses ditolak. Hanya Admin, Operator Aplikasi, atau Ketua Paguyuban.'], 403);
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

        if (! $actor->isGlobalGovernor()) {
            return response()->json([
                'message' => 'Akses ditolak. Anda tidak memiliki wewenang pengurus pusat untuk menyematkan jabatan.',
            ], 403);
        }

        $validated = $request->validate([
            'target_user_id' => ['required', 'integer', 'exists:users,id'],
            'role_code' => ['required', Rule::in([
                'admin',
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

        $isActorSystemAdmin = $actor->hasGovernanceRole('admin') || $actor->hasGovernanceRole('operator_aplikasi');

        // Kunci pengaman: Hanya Admin / Operator Aplikasi aktif yang boleh mengangkat Admin / Operator Aplikasi baru
        if (in_array($validated['role_code'], ['admin', 'operator_aplikasi'], true) && ! $isActorSystemAdmin) {
            return response()->json([
                'message' => 'Hanya Admin atau Operator Aplikasi aktif yang dapat menetapkan jabatan Admin baru.',
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

        if (! $actor->isGlobalGovernor()) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        // Kunci pengaman: Mencegah sistem kehilangan seluruh Admin / Operator Aplikasi aktif
        if (in_array($role->role_code, ['admin', 'operator_aplikasi'], true)) {
            $activeAdminCount = UserGovernanceRole::whereIn('role_code', ['admin', 'operator_aplikasi'])
                ->where('is_active', true)
                ->count();

            if ($activeAdminCount <= 1) {
                return response()->json([
                    'message' => 'Sistem mewajibkan minimal 1 Admin aktif. Delegasikan jabatan Admin ke warga lain terlebih dahulu sebelum mencabut jabatan ini.',
                ], 422);
            }
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
                'message' => 'Akses ditolak. Hanya Admin, Operator Aplikasi, atau Ketua Paguyuban yang dapat mendaftarkan warga secara langsung.',
            ], 403);
        }

        $request->merge([
            'phone' => $request->filled('phone') ? trim((string) $request->input('phone')) : null,
            'username' => $request->filled('username') ? strtolower(trim((string) $request->input('username'))) : null,
        ]);

        $validated = $request->validate([
            'unit_code' => ['required', 'string', 'exists:units,code'],
            'name' => ['required', 'string', 'max:150'],
            'phone' => [
                'nullable',
                'required_without:username',
                'string',
                'max:20',
                'regex:/^[0-9+]{8,20}$/',
                'unique:resident_profiles,phone',
            ],
            'username' => [
                'nullable',
                'required_without:phone',
                'string',
                'max:40',
                'regex:/^(?=.*[a-z])[a-z0-9._]{3,40}$/',
                'unique:resident_profiles,username',
            ],
            'password' => ['required', 'string', 'min:8'],
            'entity_type' => ['required', Rule::in(['owner', 'resident'])],
            'sub_role' => ['required', Rule::in([
                'primary_owner', 'co_owner',
                'primary_resident', 'family_member', 'co_resident', 'tenant', 'caretaker',
            ])],
        ], [
            'phone.required_without' => 'Isi minimal Nomor WhatsApp atau Username untuk warga tersebut.',
            'username.required_without' => 'Isi minimal Username atau Nomor WhatsApp untuk warga tersebut.',
            'phone.regex' => 'Nomor WhatsApp harus berupa angka 8–20 digit.',
            'username.regex' => 'Username minimal 3 karakter, wajib mengandung huruf, dan hanya boleh huruf kecil, angka, titik (.), atau garis bawah (_).',
            'phone.unique' => 'Nomor WhatsApp ini sudah terdaftar di akun warga lain.',
            'username.unique' => 'Username ini sudah digunakan warga lain.',
        ]);

        $unit = Unit::where('code', $validated['unit_code'])->firstOrFail();

        if ($unit->unit_type === 'fasum') {
            return response()->json(['message' => 'Unit fasilitas umum tidak dapat didaftarkan sebagai hunian warga.'], 422);
        }

        $newUser = DB::transaction(function () use ($validated, $unit, $actor) {
            $syntheticIdentifier = $validated['phone'] ?: $validated['username'];

            $user = User::create([
                'name' => $validated['name'],
                'email' => $syntheticIdentifier . '@ppmt.local',
                'password' => Hash::make($validated['password']),
            ]);

            $user->profile()->create([
                'phone' => $validated['phone'],
                'username' => $validated['username'],
                'global_role' => 'warga_ppmt',
                'is_verified' => true,
                'privacy_settings' => [
                    'show_phone' => false,
                    'show_ownership' => false,
                    'show_full_name' => false,
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
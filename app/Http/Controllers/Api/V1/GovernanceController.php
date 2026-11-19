<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\MeetingMinute;
use App\Models\OwnershipVerification;
use App\Models\UnitMembership;
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
    private function isImpersonationToken($token): bool
    {
        if (! $token) {
            return false;
        }

        return str_starts_with((string) $token->name, 'impersonated-by:')
            || in_array('impersonated', $token->abilities ?? [], true);
    }

    public function pendingOwnershipVerifications(Request $request): JsonResponse
    {
        $actor = $request->user();

        if (! $actor->hasAdminAccess()) {
            return response()->json(['data' => []]);
        }

        $canSeeRawNib = $actor->canViewPlainNibShm();

        $items = UnitMembership::query()
            ->where('is_owner', true)
            ->with([
                'unit:id,code,block,zone',
                'user:id,name',
                'user.profile:id,user_id,display_name,phone,username',
                'ownershipVerifications.verifier:id,name',
            ])
            ->latest('updated_at')
            ->get()
            ->filter(function (UnitMembership $m) use ($actor) {
                if (empty($m->nib_shm_encrypted)) {
                    return false;
                }

                if (! $m->unit || ! $actor->canVouchUnit($m->unit)) {
                    return false;
                }

                // Jangan tampilkan lagi di antrean pribadi jika pengurus ini sudah ikut mengesahkan
                $alreadyEndorsedByMe = $m->ownershipVerifications->contains(
                    fn (OwnershipVerification $v) => (int) $v->user_id === (int) $actor->id
                );

                return ! $alreadyEndorsedByMe;
            })
            ->map(function (UnitMembership $m) use ($canSeeRawNib) {
                $fullName = $m->user?->name ?? 'Warga';
                $displayName = $m->user?->profile?->display_name;

                return [
                    'membership_id' => $m->id,
                    'user_id' => $m->user_id,
                    'name' => $displayName && $displayName !== $fullName
                        ? "{$fullName} ({$displayName})"
                        : $fullName,
                    'contact' => $m->user?->profile?->phone ?: ($m->user?->profile?->username ? '@' . $m->user->profile->username : '-'),
                    'unit_code' => $m->unit->code,
                    'zone' => $m->unit->zone,
                    'owner_role_label' => ($m->owner_role === 'co_owner') ? 'Pemilik Bersama' : 'Pemilik Utama',
                    'can_see_raw_nib' => $canSeeRawNib,
                    'nib_shm_plain' => $canSeeRawNib ? $m->nib_shm_encrypted : null,
                    'nib_shm_masked' => '•••••••••••••••• (Nomor NIB SHM Terisi & Terenkripsi)',
                    'endorsement_count' => $m->ownershipVerifications->count(),
                    'endorsed_by_sentence' => $m->endorsed_by_sentence,
                    'submitted_at' => $m->updated_at?->diffForHumans(),
                ];
            })
            ->values();

        return response()->json([
            'data' => $items,
        ]);
    }

    public function occupancyRegistry(Request $request): JsonResponse
    {
        $actor = $request->user();

        if (! $actor->canViewOccupancyRegistry()) {
            return response()->json([
                'message' => 'Akses ditolak. Hanya pengurus aktif PPMT yang dapat mengakses buku induk hunian.',
            ], 403);
        }

        $units = $actor->accessibleUnitsQuery()
            ->with([
                'memberships' => fn ($q) => $q->orderByDesc('is_approved')->orderByDesc('is_primary_contact'),
                'memberships.user:id,name',
                'memberships.user.profile:id,user_id,display_name,phone,username,is_verified',
                'memberships.ownershipVerifications.verifier:id,name',
            ])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $subRoleLabels = [
            'primary_owner' => 'Pemilik Utama',
            'co_owner' => 'Pemilik Bersama',
            'primary_resident' => 'Penghuni Utama',
            'family_member' => 'Anggota Keluarga',
            'co_resident' => 'Penghuni Bersama',
            'tenant' => 'Penyewa / Kontrak',
            'caretaker' => 'Penjaga Rumah',
        ];

        $totalUnits = $units->count();
        $occupiedCount = $units->where('occupancy_status', 'occupied')->count();
        $rentedCount = $units->where('occupancy_status', 'rented')->count();
        $pendingCount = $units->where('occupancy_status', 'pending')->count();
        $unclaimedCount = $units->where('occupancy_status', 'unclaimed')->count();

        $verifiedResidentIds = $units->flatMap(function (Unit $unit) {
            return $unit->memberships
                ->filter(fn ($m) => $m->is_approved && $m->user?->profile?->is_verified)
                ->pluck('user_id');
        })->unique()->count();

        $canSeeRawNib = $actor->canViewPlainNibShm();

        $formattedUnits = $units->map(function (Unit $unit) use ($subRoleLabels, $actor, $canSeeRawNib) {
            $canVerifyUnit = $actor->canVouchUnit($unit);

            $members = $unit->memberships->map(function ($m) use ($subRoleLabels, $canVerifyUnit, $actor, $canSeeRawNib) {
                $fullName = $m->user?->name ?? 'Warga';
                $displayName = $m->user?->profile?->display_name;
                $isOwner = (bool) ($m->is_owner ?? ($m->entity_type === 'owner'));
                $isOccupant = (bool) ($m->is_occupant ?? true);
                $hasNib = ! empty($m->nib_shm_encrypted);

                $roleParts = [];
                if ($isOwner) {
                    $roleParts[] = $subRoleLabels[$m->owner_role ?: 'primary_owner'] ?? 'Pemilik';
                }
                if ($isOccupant) {
                    $roleParts[] = $subRoleLabels[$m->resident_role ?: 'primary_resident'] ?? 'Penghuni';
                } else {
                    $roleParts[] = 'Tidak Menghuni';
                }

                $isEndorsedByMe = $m->ownershipVerifications->contains(
                    fn (OwnershipVerification $v) => (int) $v->user_id === (int) $actor->id
                );

                return [
                    'membership_id' => $m->id,
                    'user_id' => $m->user_id,
                    'name' => $displayName && $displayName !== $fullName
                        ? "{$fullName} ({$displayName})"
                        : $fullName,
                    'contact' => $m->user?->profile?->phone ?: ($m->user?->profile?->username ? '@' . $m->user->profile->username : '-'),
                    'is_owner' => $isOwner,
                    'is_occupant' => $isOccupant,
                    'ownership_verification_status' => $m->ownership_verification_status ?: ($isOwner ? 'unverified_claim' : 'none'),
                    'has_nib_shm' => $hasNib,
                    'can_see_raw_nib' => $canSeeRawNib,
                    'nib_shm_plain' => ($canVerifyUnit && $canSeeRawNib) ? $m->nib_shm_encrypted : null,
                    'nib_shm_masked' => $hasNib ? '•••••••••••••••• (Terenkripsi & Tertutup)' : null,
                    'endorsement_count' => $m->ownershipVerifications->count(),
                    'endorsed_by_sentence' => $m->endorsed_by_sentence,
                    'is_endorsed_by_me' => $isEndorsedByMe,
                    'can_verify_ownership' => $canVerifyUnit,
                    'entity_type' => $m->entity_type,
                    'sub_role' => $m->sub_role,
                    'sub_role_label' => implode(' • ', $roleParts),
                    'is_approved' => (bool) $m->is_approved,
                    'is_primary_contact' => (bool) $m->is_primary_contact,
                    'valid_until' => $m->valid_until?->format('d M Y'),
                ];
            })->values();

            return [
                'id' => $unit->id,
                'code' => $unit->code,
                'block' => $unit->block,
                'zone' => $unit->zone,
                'unit_type' => $unit->unit_type,
                'occupancy_status' => $unit->occupancy_status,
                'members' => $members,
            ];
        })->values();

        return response()->json([
            'data' => [
                'scope_is_central' => $actor->hasCentralOccupancyAccess(),
                'stats' => [
                    'total_units' => $totalUnits,
                    'filled_units' => $occupiedCount + $rentedCount,
                    'occupied_units' => $occupiedCount,
                    'rented_units' => $rentedCount,
                    'pending_units' => $pendingCount,
                    'unclaimed_units' => $unclaimedCount,
                    'verified_residents' => $verifiedResidentIds,
                    'published_announcements' => Announcement::where('is_published', true)->count(),
                    'total_minutes' => MeetingMinute::count(),
                ],
                'units' => $formattedUnits,
            ],
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $actor = $request->user();

        if (! $actor->isGlobalGovernor()) {
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
                'can_revoke' => $actor->canRevokeGovernanceRole($r),
            ]);

        return response()->json(['data' => $assignments]);
    }

    public function assign(Request $request): JsonResponse
    {
        $actor = $request->user();

        if ($this->isImpersonationToken($actor->currentAccessToken())) {
            return response()->json([
                'message' => 'Penetapan jabatan pengurus dilarang dilakukan menggunakan sesi pinjam akun.',
            ], 403);
        }

        if (! $actor->isGlobalGovernor()) {
            return response()->json([
                'message' => 'Akses ditolak. Anda tidak memiliki wewenang pengurus pusat untuk menyematkan jabatan.',
            ], 403);
        }

        $validated = $request->validate([
            'target_user_id' => ['required', 'integer', 'exists:users,id'],
            'role_code' => ['required', Rule::in(UserGovernanceRole::validRoleCodes())],
            'scope_type' => ['required', Rule::in(['global', 'zone', 'block'])],
            'scope_value' => ['nullable', 'string', 'max:30'],
        ]);

        if (! $actor->canAssignGovernanceRole($validated['role_code'])) {
            $reason = $validated['role_code'] === 'ketua_paguyuban'
                ? 'Jabatan Ketua Paguyuban hanya dapat ditetapkan oleh Admin Sistem / Operator Aplikasi berdasarkan hasil musyawarah warga.'
                : 'Mandat jabatan Anda saat ini tidak memiliki wewenang untuk menyematkan jabatan tersebut.';

            return response()->json(['message' => $reason], 403);
        }

        $targetUser = User::with('profile')->findOrFail($validated['target_user_id']);

        if (! $targetUser->profile?->is_verified) {
            return response()->json([
                'message' => 'Jabatan pengurus hanya dapat disematkan kepada warga PPMT yang sudah terverifikasi.',
            ], 422);
        }

        $enforcedScopeType = UserGovernanceRole::requiredScopeFor($validated['role_code']);
        $scopeValue = $enforcedScopeType === 'global'
            ? null
            : strtoupper(trim((string) ($validated['scope_value'] ?? '')));

        if ($enforcedScopeType !== 'global' && $scopeValue === '') {
            return response()->json([
                'message' => 'Pilih cakupan Zona atau Blok untuk jabatan koordinator ini.',
            ], 422);
        }

        $assignment = UserGovernanceRole::updateOrCreate(
            [
                'user_id' => $targetUser->id,
                'role_code' => $validated['role_code'],
                'scope_type' => $enforcedScopeType,
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

        if ($this->isImpersonationToken($actor->currentAccessToken())) {
            return response()->json([
                'message' => 'Pencabutan jabatan pengurus dilarang dilakukan menggunakan sesi pinjam akun.',
            ], 403);
        }

        if (! $actor->isGlobalGovernor()) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        if ((int) $role->user_id === (int) $actor->id) {
            return response()->json([
                'message' => 'Anda tidak dapat mencabut jabatan aktif milik Anda sendiri.',
            ], 422);
        }

        if (! $actor->canRevokeGovernanceRole($role)) {
            return response()->json([
                'message' => "Jabatan '{$role->display_label}' berada di luar batas kewenangan pencabutan Anda.",
            ], 403);
        }

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
            'is_owner' => ['required', 'boolean'],
            'owner_role' => ['nullable', Rule::in(['primary_owner', 'co_owner'])],
            'is_occupant' => ['required', 'boolean'],
            'resident_role' => ['nullable', Rule::in([
                'primary_resident', 'family_member', 'co_resident', 'tenant', 'caretaker',
            ])],
            'nib_shm' => ['nullable', 'string', 'max:60'],
        ], [
            'phone.required_without' => 'Isi minimal Nomor WhatsApp atau Username untuk warga tersebut.',
            'username.required_without' => 'Isi minimal Username atau Nomor WhatsApp untuk warga tersebut.',
            'phone.regex' => 'Nomor WhatsApp harus berupa angka 8–20 digit.',
            'username.regex' => 'Username minimal 3 karakter, wajib mengandung huruf, dan hanya boleh huruf kecil, angka, titik (.), atau garis bawah (_).',
            'phone.unique' => 'Nomor WhatsApp ini sudah terdaftar di akun warga lain.',
            'username.unique' => 'Username ini sudah digunakan warga lain.',
        ]);

        if (! $validated['is_owner'] && ! $validated['is_occupant']) {
            return response()->json([
                'message' => 'Centang minimal satu peran: Pemilik Kavling, Penghuni Kavling, atau keduanya.',
            ], 422);
        }

        $unit = Unit::where('code', $validated['unit_code'])->firstOrFail();

        if ($unit->unit_type === 'fasum') {
            return response()->json(['message' => 'Unit fasilitas umum tidak dapat didaftarkan sebagai hunian warga.'], 422);
        }

        $newUser = DB::transaction(function () use ($validated, $unit, $actor) {
            $syntheticIdentifier = $validated['phone'] ?: $validated['username'];
            $isOwner = (bool) $validated['is_owner'];
            $isOccupant = (bool) $validated['is_occupant'];
            $ownerRole = $isOwner ? ($validated['owner_role'] ?? 'primary_owner') : null;
            $residentRole = $isOccupant ? ($validated['resident_role'] ?? 'primary_resident') : null;
            $nibShm = $isOwner && ! empty($validated['nib_shm']) ? strtoupper(trim($validated['nib_shm'])) : null;
            $ownershipStatus = ! $isOwner ? 'none' : ($nibShm ? 'verified_shm' : 'unverified_claim');

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
                'is_ownership_doc_verified' => $ownershipStatus === 'verified_shm',
                'privacy_settings' => [
                    'show_phone' => false,
                    'show_ownership' => false,
                    'show_full_name' => false,
                ],
            ]);

            $membership = $user->unitMemberships()->create([
                'unit_id' => $unit->id,
                'is_owner' => $isOwner,
                'owner_role' => $ownerRole,
                'is_occupant' => $isOccupant,
                'resident_role' => $residentRole,
                'ownership_verification_status' => $ownershipStatus,
                'nib_shm_encrypted' => $nibShm,
                'ownership_verified_at' => $ownershipStatus === 'verified_shm' ? now() : null,
                'ownership_verified_by_user_id' => $ownershipStatus === 'verified_shm' ? $actor->id : null,
                'entity_type' => $isOwner ? 'owner' : 'resident',
                'sub_role' => $isOwner ? $ownerRole : $residentRole,
                'is_primary_contact' => $ownerRole === 'primary_owner' || $residentRole === 'primary_resident',
                'is_approved' => true,
            ]);

            if ($ownershipStatus === 'verified_shm') {
                $roleInfo = $actor->resolveVerifierRoleForUnit($unit);
                OwnershipVerification::create([
                    'unit_membership_id' => $membership->id,
                    'user_id' => $actor->id,
                    'role_code' => $roleInfo['role_code'],
                    'role_label' => $roleInfo['role_label'],
                    'verified_at' => now(),
                ]);
            }

            VerificationVoucher::create([
                'target_user_id' => $user->id,
                'unit_id' => $unit->id,
                'voucher_user_id' => $actor->id,
                'method' => 'in_person_vouch',
            ]);

            if ($isOccupant && ($unit->occupancy_status === 'unclaimed' || $unit->occupancy_status === 'pending')) {
                $unit->update([
                    'occupancy_status' => $residentRole === 'tenant' ? 'rented' : 'occupied',
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

    public function accessMatrix(Request $request): JsonResponse
    {
        $actor = $request->user();

        if (! $actor->isSystemAdmin()) {
            return response()->json([
                'message' => 'Akses ditolak. Halaman Role Management & Audit Hak Akses hanya untuk Admin Sistem.',
            ], 403);
        }

        $activeAssignments = UserGovernanceRole::query()
            ->where('is_active', true)
            ->with(['user:id,name', 'user.profile:user_id,display_name,phone,username', 'user.unitMemberships.unit:id,code', 'assignedBy:id,name'])
            ->latest()
            ->get();

        // Kelompokkan per warga yang menjabat (Subjek -> Gabungan Role Aktif)
        $holders = $activeAssignments
            ->groupBy('user_id')
            ->map(function ($roles) use ($actor) {
                $first = $roles->first();
                $u = $first->user;

                return [
                    'user_id' => $u->id,
                    'name' => $u->name,
                    'display_name' => $u->profile?->display_name,
                    'contact' => $u->profile?->phone ?: ($u->profile?->username ? '@' . $u->profile->username : '-'),
                    'unit_codes' => $u->unitMemberships->pluck('unit.code')->filter()->unique()->join(', ') ?: '-',
                    'roles' => $roles->map(fn (UserGovernanceRole $r) => [
                        'id' => $r->id,
                        'role_code' => $r->role_code,
                        'display_label' => $r->display_label,
                        'domain' => UserGovernanceRole::ROLES[$r->role_code]['domain'] ?? 'organization',
                        'scope_type' => $r->scope_type,
                        'scope_value' => $r->scope_value,
                        'assigned_by' => $r->assignedBy?->name ?? 'Sistem Genesis',
                        'can_revoke' => $actor->canRevokeGovernanceRole($r),
                    ])->values(),
                ];
            })
            ->values();

        return response()->json([
            'data' => [
                'matrix' => UserGovernanceRole::permissionMatrix(),
                'holders' => $holders,
            ],
        ]);
    }

    public function storeRoleDefinition(Request $request): JsonResponse
    {
        if (! $request->user()->isPureSystemAdmin()) {
            return response()->json([
                'message' => 'Akses ditolak. Hanya Admin Sistem (bukan Operator) yang berhak menciptakan definisi role baru.',
            ], 403);
        }

        $request->merge([
            'role_code' => strtolower(trim((string) $request->input('role_code'))),
        ]);

        $validated = $request->validate([
            'role_code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-z][a-z0-9_]{2,49}$/',
                'unique:governance_role_definitions,role_code',
            ],
            'label' => ['required', 'string', 'max:100'],
            'domain' => ['required', Rule::in(['system', 'organization'])],
            'domain_label' => ['nullable', 'string', 'max:100'],
            'scope' => ['required', Rule::in(['global', 'zone', 'block'])],
            'appointed_by' => ['nullable', 'string', 'max:150'],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['required', 'string', 'max:300'],
            'restrictions' => ['required', 'array', 'min:1'],
            'restrictions.*' => ['required', 'string', 'max:300'],
        ], [
            'role_code.regex' => 'Kode role harus diawali huruf kecil dan hanya boleh berisi huruf kecil, angka, serta garis bawah (_).',
            'role_code.unique' => 'Kode role tersebut sudah terdaftar di sistem.',
        ]);

        $defaultDomainLabel = $validated['domain'] === 'system'
            ? 'Teknis & Infrastruktur Sistem'
            : 'Kabinet / Bidang Operasional';

        $defaultAppointedBy = $validated['domain'] === 'system'
            ? 'Admin Sistem'
            : 'Ketua Paguyuban';

        $def = \App\Models\GovernanceRoleDefinition::create([
            'role_code' => $validated['role_code'],
            'label' => trim($validated['label']),
            'domain' => $validated['domain'],
            'domain_label' => trim($validated['domain_label'] ?? '') ?: $defaultDomainLabel,
            'scope' => $validated['scope'],
            'appointed_by' => trim($validated['appointed_by'] ?? '') ?: $defaultAppointedBy,
            'permissions' => array_values(array_filter(array_map('trim', $validated['permissions']))),
            'restrictions' => array_values(array_filter(array_map('trim', $validated['restrictions']))),
            'is_core' => false,
            'is_active' => true,
        ]);

        \App\Models\GovernanceRoleDefinition::flushCache();

        return response()->json([
            'message' => "Role baru '{$def->label}' ({$def->role_code}) berhasil dibuat dan masuk ke matriks sistem.",
            'data' => $def,
        ], 201);
    }

    public function updateRoleDefinition(Request $request, \App\Models\GovernanceRoleDefinition $definition): JsonResponse
    {
        if (! $request->user()->isPureSystemAdmin()) {
            return response()->json([
                'message' => 'Akses ditolak. Hanya Admin Sistem yang berhak mengubah matriks role.',
            ], 403);
        }

        $validated = $request->validate([
            'label' => ['required', 'string', 'max:100'],
            'domain_label' => ['required', 'string', 'max:100'],
            'scope' => ['sometimes', Rule::in(['global', 'zone', 'block'])],
            'appointed_by' => ['required', 'string', 'max:150'],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['required', 'string', 'max:300'],
            'restrictions' => ['required', 'array', 'min:1'],
            'restrictions.*' => ['required', 'string', 'max:300'],
        ]);

        $definition->update([
            'label' => trim($validated['label']),
            'domain_label' => trim($validated['domain_label']),
            // Role inti sistem tidak boleh diubah scope-nya agar logika zona/blok tidak rusak
            'scope' => $definition->is_core ? $definition->scope : ($validated['scope'] ?? $definition->scope),
            'appointed_by' => trim($validated['appointed_by']),
            'permissions' => array_values(array_filter(array_map('trim', $validated['permissions']))),
            'restrictions' => array_values(array_filter(array_map('trim', $validated['restrictions']))),
        ]);

        \App\Models\GovernanceRoleDefinition::flushCache();

        return response()->json([
            'message' => "Matriks role '{$definition->label}' berhasil diperbarui.",
            'data' => $definition,
        ]);
    }

    public function destroyRoleDefinition(Request $request, \App\Models\GovernanceRoleDefinition $definition): JsonResponse
    {
        if (! $request->user()->isPureSystemAdmin()) {
            return response()->json([
                'message' => 'Akses ditolak. Hanya Admin Sistem yang berhak menghapus role.',
            ], 403);
        }

        if ($definition->is_core) {
            return response()->json([
                'message' => "Role '{$definition->label}' adalah role inti bawaan arsitektur sistem sehingga tidak dapat dihapus.",
            ], 422);
        }

        $activeHoldersCount = UserGovernanceRole::where('role_code', $definition->role_code)
            ->where('is_active', true)
            ->count();

        if ($activeHoldersCount > 0) {
            return response()->json([
                'message' => "Tidak dapat menghapus role '{$definition->label}' karena masih dipegang oleh {$activeHoldersCount} warga aktif. Cabut jabatan warga terkait terlebih dahulu.",
            ], 422);
        }

        $label = $definition->label;
        $definition->delete();
        \App\Models\GovernanceRoleDefinition::flushCache();

        return response()->json([
            'message' => "Definisi role kustom '{$label}' telah dihapus dari sistem.",
        ]);
    }
}
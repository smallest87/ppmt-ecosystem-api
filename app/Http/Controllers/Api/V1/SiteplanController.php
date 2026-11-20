<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SiteplanController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $viewer = $request->user();

        if (! $viewer || ! $viewer->profile?->is_verified) {
            return response()->json([
                'message' => 'Akses ditolak. Siteplan internal hanya dapat diakses oleh warga PPMT yang telah terverifikasi.',
            ], 403);
        }

        $isCentralAdmin = $viewer->hasCentralOccupancyAccess();
        $canViewTreasury = $viewer->canViewTreasury() || $viewer->isSystemAdmin();

        $accessibleUnitIds = $isCentralAdmin
            ? []
            : $viewer->accessibleUnitsQuery()->pluck('id')->all();

        // Deteksi apakah pengguna adalah koordinator wilayah terbatas (mis. Koordinator Blok A / Koordinator Zona)
        $isScopedCoordinator = ! $isCentralAdmin && count($accessibleUnitIds) > 0;

        $activeScopes = [];
        if ($isScopedCoordinator && method_exists($viewer, 'governanceRoles')) {
            $activeScopes = $viewer->governanceRoles()
                ->whereIn('role_code', ['koordinator_blok', 'koordinator_zona'])
                ->get()
                ->map(fn ($r) => $r->scope_type === 'block' ? 'Blok ' . $r->scope_value : $r->scope_value)
                ->unique()
                ->values()
                ->all();
        }

        $myUnitIds = $viewer->unitMemberships()
            ->where('is_approved', true)
            ->pluck('unit_id')
            ->all();

        // Ambil status iuran sampah bulan berjalan jika pengguna berwenang melihat kas
        $currentMonth = now()->format('Y-m');
        $duesByUnitId = [];
        if ($canViewTreasury && Schema::hasTable('trash_dues')) {
            $duesByUnitId = DB::table('trash_dues')
                ->where('period_month', $currentMonth)
                ->pluck('status', 'unit_id')
                ->all();
        }

        $units = Unit::query()
            ->with([
                'memberships' => fn ($q) => $q->orderByDesc('is_approved')->orderByDesc('is_primary_contact'),
                'memberships.user:id,name',
                'memberships.user.profile:id,user_id,display_name,phone,username,is_verified,privacy_settings',
                'memberships.ownershipVerifications.verifier:id,name',
                'umkmStores' => fn ($q) => $q->whereIn('status', ['open', 'open_po']),
            ])
            ->orderByRaw("
                CASE block
                    WHEN 'R' THEN 1
                    WHEN 'A' THEN 2
                    WHEN 'B' THEN 3
                    WHEN 'C' THEN 4
                    WHEN 'D' THEN 5
                    WHEN 'FASUM' THEN 6
                    ELSE 7
                END
            ")
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

        $formattedUnits = $units->map(function (Unit $unit) use (
            $viewer,
            $isCentralAdmin,
            $accessibleUnitIds,
            $myUnitIds,
            $canViewTreasury,
            $duesByUnitId,
            $subRoleLabels
        ) {
            $isMyUnit = in_array($unit->id, $myUnitIds, true);
            $hasGovernanceScopeOnUnit = $isCentralAdmin || in_array($unit->id, $accessibleUnitIds, true);
            $canApproveUnit = $viewer->canVouchUnit($unit);

            // Semua warga terverifikasi (termasuk koordinator wilayah) dapat menginspeksi seluruh kavling;
            // hak buka privasi & aksi verifikasi tetap terkunci hanya pada wilayah tugasnya.
            $isInteractive = true;

            $visibleMembers = $unit->memberships
                ->filter(function ($m) use ($hasGovernanceScopeOnUnit, $canApproveUnit, $isMyUnit) {
                    if ($m->is_approved) {
                        return true;
                    }

                    return $hasGovernanceScopeOnUnit || $canApproveUnit || $isMyUnit;
                })
                ->map(function ($m) use ($viewer, $hasGovernanceScopeOnUnit, $isMyUnit, $subRoleLabels) {
                    $user = $m->user;
                    $profile = $user?->profile;
                    $privacy = $profile?->privacy_settings ?? [];

                    $bypassPrivacy = $hasGovernanceScopeOnUnit
                        || $isMyUnit
                        || ($user && (int) $user->id === (int) $viewer->id);

                    $canSeeFullName = $bypassPrivacy || ($privacy['show_full_name'] ?? false);
                    $canSeePhone = $bypassPrivacy || ($privacy['show_phone'] ?? false);
                    $canSeeOwnership = $bypassPrivacy || ($privacy['show_ownership'] ?? false);

                    $fullName = $user?->name ?? 'Warga';
                    $publicName = $profile?->display_name ?: explode(' ', trim($fullName))[0];

                    $isOwner = (bool) ($m->is_owner ?? ($m->entity_type === 'owner'));
                    $isOccupant = (bool) ($m->is_occupant ?? true);
                    $visibleOwner = $canSeeOwnership && $isOwner;

                    $ownershipStatus = $m->ownership_verification_status ?: ($isOwner ? 'unverified_claim' : 'none');
                    $isVerifiedShm = $visibleOwner && $ownershipStatus === 'verified_shm';

                    $roleParts = [];
                    if ($visibleOwner) {
                        $roleParts[] = $subRoleLabels[$m->owner_role ?: 'primary_owner'] ?? 'Pemilik';
                    }
                    if ($isOccupant) {
                        $roleParts[] = $subRoleLabels[$m->resident_role ?: 'primary_resident'] ?? 'Penghuni';
                    } elseif ($visibleOwner) {
                        $roleParts[] = 'Tidak Menghuni';
                    } else {
                        $roleParts[] = 'Warga Terhubung (Kepemilikan Diprivasi)';
                    }

                    return [
                        'membership_id' => $m->id,
                        'user_id' => $m->user_id,
                        'name' => $canSeeFullName ? $fullName : $publicName,
                        'phone' => $canSeePhone ? $profile?->phone : null,
                        'is_owner' => $visibleOwner,
                        'is_occupant' => $isOccupant,
                        'ownership_verification_status' => $visibleOwner ? $ownershipStatus : 'none',
                        'is_verified_shm' => $isVerifiedShm,
                        'endorsement_count' => $isVerifiedShm ? $m->ownershipVerifications->count() : 0,
                        'endorsed_by_sentence' => $isVerifiedShm ? $m->endorsed_by_sentence : null,
                        'entity_type' => $visibleOwner ? 'owner' : ($isOccupant ? 'resident' : 'member'),
                        'sub_role' => $visibleOwner ? ($m->owner_role ?: 'primary_owner') : ($isOccupant ? ($m->resident_role ?: 'primary_resident') : null),
                        'sub_role_label' => implode(' • ', $roleParts),
                        'is_approved' => (bool) $m->is_approved,
                        'is_primary_contact' => (bool) $m->is_primary_contact,
                    ];
                })
                ->values();

            $publicApprovedOwners = $unit->memberships->filter(function ($m) use ($viewer, $hasGovernanceScopeOnUnit, $isMyUnit) {
                if (! $m->is_approved || ! (bool) ($m->is_owner ?? ($m->entity_type === 'owner'))) {
                    return false;
                }
                $user = $m->user;
                $privacy = $user?->profile?->privacy_settings ?? [];
                $bypassPrivacy = $hasGovernanceScopeOnUnit
                    || $isMyUnit
                    || ($user && (int) $user->id === (int) $viewer->id);

                return $bypassPrivacy || ($privacy['show_ownership'] ?? false);
            });

            $hasVerifiedShm = $publicApprovedOwners->contains(
                fn ($m) => ($m->ownership_verification_status ?: 'unverified_claim') === 'verified_shm'
            );
            $hasUnverifiedClaim = ! $hasVerifiedShm && $publicApprovedOwners->isNotEmpty();

            $ownersSummary = $publicApprovedOwners->map(function ($m) use ($viewer, $hasGovernanceScopeOnUnit, $isMyUnit) {
                $user = $m->user;
                $profile = $user?->profile;
                $privacy = $profile?->privacy_settings ?? [];

                $bypassPrivacy = $hasGovernanceScopeOnUnit
                    || $isMyUnit
                    || ($user && (int) $user->id === (int) $viewer->id);

                $canSeeFullName = $bypassPrivacy || ($privacy['show_full_name'] ?? false);

                $fullName = $user?->name ?? 'Warga';
                $publicName = $profile?->display_name ?: explode(' ', trim($fullName))[0];
                $isVerifiedShm = ($m->ownership_verification_status ?: 'unverified_claim') === 'verified_shm';

                return [
                    'name' => $canSeeFullName ? $fullName : $publicName,
                    'is_verified_shm' => $isVerifiedShm,
                    'endorsement_count' => $isVerifiedShm ? $m->ownershipVerifications->count() : 0,
                    'endorsed_by_sentence' => $isVerifiedShm ? $m->endorsed_by_sentence : null,
                    'owner_role' => $m->owner_role ?: 'primary_owner',
                ];
            })->values();

            $activeStores = $unit->umkmStores->map(fn ($store) => [
                'id' => $store->id,
                'store_name' => $store->store_name,
                'category' => $store->category,
                'status' => $store->status,
            ])->values();

            $payload = [
                'id' => $unit->id,
                'code' => $unit->code,
                'block' => $unit->block,
                'number' => $unit->number,
                'unit_type' => $unit->unit_type,
                'zone' => $unit->zone,
                'occupancy_status' => $unit->occupancy_status,
                'map_coordinates' => $unit->map_coordinates,
                'is_my_unit' => $isMyUnit,
                'is_interactive' => $isInteractive,
                'can_manage_unit' => $hasGovernanceScopeOnUnit || $canApproveUnit,
                'has_owner_claim' => $publicApprovedOwners->isNotEmpty(),
                'has_verified_shm' => $hasVerifiedShm,
                'has_unverified_claim' => $hasUnverifiedClaim,
                'owners' => $ownersSummary,
                'pending_count' => $unit->memberships->where('is_approved', false)->count(),
                'active_stores_count' => $activeStores->count(),
                'active_stores' => $activeStores,
                'members' => $visibleMembers,
            ];

            if ($canViewTreasury) {
                $payload['dues_status'] = $unit->unit_type === 'fasum'
                    ? 'exempt'
                    : ($duesByUnitId[$unit->id] ?? 'unpaid');
            }

            return $payload;
        })->values();

        return response()->json([
            'viewer_context' => [
                'is_central_admin' => $isCentralAdmin,
                'is_global_governor' => $viewer->isGlobalGovernor(),
                'is_scoped_coordinator' => $isScopedCoordinator,
                'active_scopes' => $activeScopes,
                'can_view_treasury' => $canViewTreasury,
                'current_month' => $currentMonth,
                'accessible_unit_ids' => $accessibleUnitIds,
            ],
            'stats' => [
                'total_units' => $units->count(),
                'occupied_units' => $units->whereIn('occupancy_status', ['occupied', 'rented'])->count(),
                'pending_units' => $units->where('occupancy_status', 'pending')->count(),
                'unclaimed_units' => $units->where('occupancy_status', 'unclaimed')->where('unit_type', '!=', 'fasum')->count(),
                'active_umkm_units' => $formattedUnits->where('active_stores_count', '>', 0)->count(),
            ],
            'data' => $formattedUnits,
        ]);
    }
}
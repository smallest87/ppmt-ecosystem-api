<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ResidentDirectoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $viewer = $request->user();
        $profile = $this->profile;
        $privacy = $profile?->privacy_settings ?? [];

        $scopeInfo = ['is_central' => false, 'unit_ids' => []];
        if ($viewer) {
            $cacheKey = 'directory_viewer_scope_' . $viewer->id;
            if (! $request->attributes->has($cacheKey)) {
                $isCentral = (method_exists($viewer, 'hasCentralOccupancyAccess') && $viewer->hasCentralOccupancyAccess())
                    || $viewer->profile?->global_role === 'admin';

                $isTerritorial = ! $isCentral && (
                    $viewer->profile?->global_role === 'koordinator_blok'
                    || $viewer->governanceRoles()
                        ->whereIn('role_code', ['koordinator_blok', 'koordinator_zona'])
                        ->exists()
                );

                $request->attributes->set($cacheKey, [
                    'is_central' => $isCentral,
                    'unit_ids' => $isTerritorial
                        ? $viewer->accessibleUnitsQuery()->pluck('id')->all()
                        : [],
                ]);
            }
            $scopeInfo = $request->attributes->get($cacheKey);
        }

        $approvedMemberships = $this->unitMemberships->where('is_approved', true);
        $managedUnitIds = $scopeInfo['unit_ids'];

        // Koordinator berwenang melihat profil lengkap warga jika warga tersebut memiliki minimal satu unit di wilayah binaannya
        $hasManagedUnit = $scopeInfo['is_central']
            || (! empty($managedUnitIds) && $approvedMemberships->whereIn('unit_id', $managedUnitIds)->isNotEmpty());

        $isSelf = $viewer && (int) $viewer->id === (int) $this->id;
        $isSelfOrAuthorized = $isSelf || $hasManagedUnit;

        $canSeeFullName = $isSelfOrAuthorized || ($privacy['show_full_name'] ?? false);
        $canSeePhone = $isSelfOrAuthorized || ($privacy['show_phone'] ?? false);

        $publicName = $profile?->display_name ?: explode(' ', trim($this->name))[0];

        $subRoleLabels = [
            'primary_owner' => 'Pemilik Utama',
            'co_owner' => 'Pemilik Bersama',
            'primary_resident' => 'Penghuni Utama',
            'family_member' => 'Anggota Keluarga',
            'co_resident' => 'Penghuni Bersama',
            'tenant' => 'Penyewa / Kontrak',
            'caretaker' => 'Penjaga Rumah',
        ];

        return [
            'id' => $this->id,
            'name' => $canSeeFullName ? $this->name : $publicName,
            'display_name' => $publicName,
            'phone' => $canSeePhone ? $profile?->phone : null,
            'reputation_badge' => $profile?->reputation_badge,
            'units' => $approvedMemberships
                ->map(function ($membership) use ($isSelf, $scopeInfo, $managedUnitIds, $privacy, $subRoleLabels) {
                    $isOwner = (bool) ($membership->is_owner ?? ($membership->entity_type === 'owner'));
                    $isOccupant = (bool) ($membership->is_occupant ?? true);

                    // Privasi kepemilikan dibuka hanya jika pemilik akun, pengurus pusat, koordinator unit terkait, atau diizinkan publik
                    $canSeeUnitOwnership = $isSelf
                        || $scopeInfo['is_central']
                        || in_array($membership->unit_id, $managedUnitIds, true)
                        || ($privacy['show_ownership'] ?? false);

                    $visibleOwner = $canSeeUnitOwnership && $isOwner;

                    $roleParts = [];
                    if ($visibleOwner) {
                        $roleParts[] = $subRoleLabels[$membership->owner_role ?: 'primary_owner'] ?? 'Pemilik';
                    }
                    if ($isOccupant) {
                        $roleParts[] = $subRoleLabels[$membership->resident_role ?: 'primary_resident'] ?? 'Penghuni';
                    } elseif ($visibleOwner) {
                        $roleParts[] = 'Tidak Menghuni';
                    } else {
                        $roleParts[] = 'Warga Terhubung';
                    }

                    return [
                        'unit_code' => $membership->unit->code,
                        'zone' => $membership->unit->zone,
                        'is_owner' => $visibleOwner,
                        'is_occupant' => $isOccupant,
                        'entity_type' => $visibleOwner ? 'owner' : ($isOccupant ? 'resident' : 'member'),
                        'sub_role' => implode(' • ', $roleParts),
                    ];
                })->values(),
        ];
    }
}
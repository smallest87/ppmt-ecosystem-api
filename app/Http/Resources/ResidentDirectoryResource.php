<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ResidentDirectoryResource extends JsonResource
{
    protected static array $viewerAdminCache = [];

    public function toArray(Request $request): array
    {
        $viewer = $request->user();
        $profile = $this->profile;
        $privacy = $profile?->privacy_settings ?? [];

        $isViewerAdmin = false;
        if ($viewer) {
            if (! array_key_exists($viewer->id, self::$viewerAdminCache)) {
                self::$viewerAdminCache[$viewer->id] =
                    (method_exists($viewer, 'isGlobalGovernor') && $viewer->isGlobalGovernor()) ||
                    in_array($viewer->profile?->global_role, ['admin', 'koordinator_blok'], true);
            }
            $isViewerAdmin = self::$viewerAdminCache[$viewer->id];
        }

        $isSelfOrAdmin = ($viewer && $viewer->id === $this->id) || $isViewerAdmin;

        $canSeeFullName = $isSelfOrAdmin || ($privacy['show_full_name'] ?? false);
        $canSeePhone = $isSelfOrAdmin || ($privacy['show_phone'] ?? false);
        $canSeeOwnership = $isSelfOrAdmin || ($privacy['show_ownership'] ?? false);

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
            'units' => $this->unitMemberships
                ->where('is_approved', true)
                ->map(function ($membership) use ($canSeeOwnership, $subRoleLabels) {
                    $isOwner = (bool) ($membership->is_owner ?? ($membership->entity_type === 'owner'));
                    $isOccupant = (bool) ($membership->is_occupant ?? true);

                    $visibleOwner = $canSeeOwnership && $isOwner;

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
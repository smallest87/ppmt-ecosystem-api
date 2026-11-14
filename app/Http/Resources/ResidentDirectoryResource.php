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

        $isSelfOrAdmin = $viewer && (
            $viewer->id === $this->id ||
            (method_exists($viewer, 'isGlobalGovernor') && $viewer->isGlobalGovernor()) ||
            in_array($viewer->profile?->global_role, ['admin', 'koordinator_blok'])
        );

        $canSeeFullName = $isSelfOrAdmin || ($privacy['show_full_name'] ?? false);
        $canSeePhone = $isSelfOrAdmin || ($privacy['show_phone'] ?? false);
        $canSeeOwnership = $isSelfOrAdmin || ($privacy['show_ownership'] ?? false);

        $publicName = $profile?->display_name ?: explode(' ', trim($this->name))[0];

        return [
            'id' => $this->id,
            'name' => $canSeeFullName ? $this->name : $publicName,
            'display_name' => $publicName,
            'phone' => $canSeePhone ? $profile?->phone : null,
            'reputation_badge' => $profile?->reputation_badge,
            'units' => $this->unitMemberships
                ->where('is_approved', true)
                ->map(function ($membership) use ($canSeeOwnership) {
                    return [
                        'unit_code' => $membership->unit->code,
                        'zone' => $membership->unit->zone,
                        'entity_type' => $canSeeOwnership ? $membership->entity_type : 'member',
                        'sub_role' => $canSeeOwnership ? $membership->sub_role : null,
                    ];
                })->values(),
        ];
    }
}
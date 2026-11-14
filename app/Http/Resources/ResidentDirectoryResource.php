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
            in_array($viewer->profile?->global_role, ['admin', 'koordinator_blok'])
        );

        $canSeePhone = $isSelfOrAdmin || ($privacy['show_phone'] ?? false);
        $canSeeOwnership = $isSelfOrAdmin || ($privacy['show_ownership'] ?? false);

        return [
            'id' => $this->id,
            'name' => $this->name,
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
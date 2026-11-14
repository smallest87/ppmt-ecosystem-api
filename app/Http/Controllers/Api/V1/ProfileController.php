<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ResidentDirectoryResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProfileController extends Controller
{
    public function updateProgressiveProfile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nik' => ['nullable', 'digits:16'],
            'kk' => ['nullable', 'digits:16'],
            'vehicles' => ['nullable', 'array'],
            'vehicles.*.type' => ['required_with:vehicles', 'in:mobil,motor,sepeda'],
            'vehicles.*.plate' => ['required_with:vehicles', 'string', 'max:15'],
            'privacy_settings' => ['nullable', 'array'],
            'privacy_settings.show_phone' => ['boolean'],
            'privacy_settings.show_ownership' => ['boolean'],
        ]);

        $profile = $request->user()->profile;
        $updates = [];

        if (isset($validated['nik'])) {
            $updates['nik_encrypted'] = $validated['nik']; // Otomatis terenkripsi AES-256 oleh cast model
        }
        if (isset($validated['kk'])) {
            $updates['kk_encrypted'] = $validated['kk'];
        }
        if (array_key_exists('vehicles', $validated)) {
            $updates['vehicles'] = $validated['vehicles'];
        }
        if (isset($validated['privacy_settings'])) {
            $updates['privacy_settings'] = array_merge(
                $profile->privacy_settings ?? [],
                $validated['privacy_settings']
            );
        }

        $profile->update($updates);
        $profile->refresh();

        return response()->json([
            'message' => 'Profil dan pengaturan privasi berhasil diperbarui.',
            'data' => [
                'reputation_badge' => $profile->reputation_badge,
                'privacy_settings' => $profile->privacy_settings,
                'has_identity_docs' => ! empty($profile->nik_encrypted) && ! empty($profile->kk_encrypted),
                'vehicles_count' => count($profile->vehicles ?? []),
            ],
        ]);
    }

    public function directory(): AnonymousResourceCollection
    {
        $residents = User::query()
            ->whereHas('profile', fn ($q) => $q->where('is_verified', true))
            ->with(['profile', 'unitMemberships.unit'])
            ->get();

        return ResidentDirectoryResource::collection($residents);
    }
}
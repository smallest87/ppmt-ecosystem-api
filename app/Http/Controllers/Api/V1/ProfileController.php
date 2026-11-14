<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ResidentDirectoryResource;
use App\Models\UnitMembership;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function updateProgressiveProfile(Request $request): JsonResponse
    {
        $user = $request->user();
        $profile = $user->profile;

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'display_name' => ['nullable', 'string', 'max:100'],
            'phone' => [
                'sometimes',
                'required',
                'string',
                'max:20',
                Rule::unique('resident_profiles', 'phone')->ignore($profile->id),
            ],
            'current_password' => ['nullable', 'required_with:new_password', 'string'],
            'new_password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'nik' => ['nullable', 'digits:16'],
            'kk' => ['nullable', 'digits:16'],
            'vehicles' => ['nullable', 'array'],
            'vehicles.*.type' => ['required_with:vehicles', 'in:mobil,motor,sepeda'],
            'vehicles.*.plate' => ['required_with:vehicles', 'string', 'max:15'],
            'privacy_settings' => ['nullable', 'array'],
            'privacy_settings.show_phone' => ['sometimes', 'boolean'],
            'privacy_settings.show_ownership' => ['sometimes', 'boolean'],
            'privacy_settings.show_full_name' => ['sometimes', 'boolean'],
        ]);

        if (! empty($validated['new_password'])) {
            if (! Hash::check($validated['current_password'], $user->password)) {
                return response()->json([
                    'message' => 'Kata sandi lama Anda tidak sesuai.',
                    'errors' => ['current_password' => ['Kata sandi lama tidak sesuai.']],
                ], 422);
            }
            $user->password = Hash::make($validated['new_password']);
        }

        if (isset($validated['name'])) {
            $user->name = $validated['name'];
        }

        if (isset($validated['phone']) && $validated['phone'] !== $profile->phone) {
            $profile->phone = $validated['phone'];
            $user->email = $validated['phone'] . '@ppmt.local';
        }

        $user->save();

        $updates = [];

        if (array_key_exists('display_name', $validated)) {
            $updates['display_name'] = $validated['display_name'] ?: null;
        }
        if (isset($validated['nik'])) {
            $updates['nik_encrypted'] = $validated['nik'];
        }
        if (isset($validated['kk'])) {
            $updates['kk_encrypted'] = $validated['kk'];
        }
        if (array_key_exists('vehicles', $validated)) {
            $updates['vehicles'] = $validated['vehicles'];
        }
        if (isset($validated['privacy_settings'])) {
            $updates['privacy_settings'] = array_merge(
                $profile->privacy_settings ?? [
                    'show_phone' => false,
                    'show_ownership' => false,
                    'show_full_name' => false,
                ],
                $validated['privacy_settings']
            );
        }

        $profile->update($updates);
        $profile->refresh();

        return response()->json([
            'message' => 'Profil dan pengaturan privasi berhasil diperbarui.',
            'data' => [
                'name' => $user->name,
                'display_name' => $profile->display_name,
                'phone' => $profile->phone,
                'reputation_badge' => $profile->reputation_badge,
                'privacy_settings' => $profile->privacy_settings,
                'has_identity_docs' => ! empty($profile->nik_encrypted) && ! empty($profile->kk_encrypted),
                'vehicles_count' => count($profile->vehicles ?? []),
            ],
        ]);
    }

    public function updateMembershipRole(Request $request, UnitMembership $membership): JsonResponse
    {
        $actor = $request->user();
        $isSelf = $membership->user_id === $actor->id;
        $isGovernor = method_exists($actor, 'isGlobalGovernor') && $actor->isGlobalGovernor();

        $isPrimaryOwnerOfUnit = $actor->unitMemberships()
            ->where('unit_id', $membership->unit_id)
            ->where('entity_type', 'owner')
            ->where('sub_role', 'primary_owner')
            ->where('is_approved', true)
            ->exists();

        if (! $isSelf && ! $isGovernor && ! $isPrimaryOwnerOfUnit) {
            return response()->json([
                'message' => 'Anda tidak memiliki hak untuk mengubah status entitas/peran pada keanggotaan kavling ini.',
            ], 403);
        }

        $validated = $request->validate([
            'entity_type' => ['required', Rule::in(['owner', 'resident'])],
            'sub_role' => ['required', Rule::in([
                'primary_owner', 'co_owner',
                'primary_resident', 'family_member', 'co_resident', 'tenant', 'caretaker',
            ])],
            'valid_until' => ['nullable', 'date', 'after:today'],
        ]);

        // Validasi kecocokan pasangan Entity Type <-> Sub Role
        $validSubRoles = $validated['entity_type'] === 'owner'
            ? ['primary_owner', 'co_owner']
            : ['primary_resident', 'family_member', 'co_resident', 'tenant', 'caretaker'];

        if (! in_array($validated['sub_role'], $validSubRoles, true)) {
            return response()->json([
                'message' => 'Peran yang dipilih tidak sesuai dengan jenis Entitas (Pemilik vs Penghuni).',
            ], 422);
        }

        // Jika warga biasa (bukan pengurus/pemilik utama kavling) menaikkan statusnya menjadi owner,
        // wajib melalui persetujuan sosial ulang agar aman dari klaim kepemilikan sepihak.
        $elevatingToOwner = $validated['entity_type'] === 'owner' && $membership->entity_type !== 'owner';
        $requiresReapproval = $isSelf && ! $isGovernor && ! $isPrimaryOwnerOfUnit && $elevatingToOwner;

        DB::transaction(function () use ($membership, $validated, $requiresReapproval) {
            $membership->update([
                'entity_type' => $validated['entity_type'],
                'sub_role' => $validated['sub_role'],
                'is_primary_contact' => in_array($validated['sub_role'], ['primary_owner', 'primary_resident'], true),
                'valid_until' => $validated['sub_role'] === 'tenant' ? ($validated['valid_until'] ?? $membership->valid_until) : null,
                'is_approved' => $requiresReapproval ? false : $membership->is_approved,
            ]);

            // Sinkronkan status hunian kavling jika sudah disetujui
            if (! $requiresReapproval && $membership->is_approved) {
                $membership->unit()->update([
                    'occupancy_status' => $validated['sub_role'] === 'tenant' ? 'rented' : 'occupied',
                ]);
            }
        });

        $message = $requiresReapproval
            ? "Perubahan ke status Pemilik pada Kavling {$membership->unit->code} disimpan dan menunggu pengesahan Koordinator Zona/Blok."
            : "Status Entitas & Peran pada Kavling {$membership->unit->code} berhasil diperbarui.";

        return response()->json([
            'message' => $message,
            'requires_reapproval' => $requiresReapproval,
            'data' => $membership->fresh('unit'),
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
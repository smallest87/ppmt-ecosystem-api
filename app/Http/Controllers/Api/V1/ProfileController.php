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

        if ($user->currentAccessToken()?->can('impersonated')) {
            if ($request->filled('new_password') || $request->filled('nik') || $request->filled('kk')) {
                return response()->json([
                    'message' => 'Demi keamanan privasi warga, kata sandi, NIK, dan Nomor KK tidak dapat diubah selama sesi bantuan teknis Admin.',
                ], 403);
            }
        }

        $profile = $user->profile;

        // Normalisasi nilai kosong menjadi null sebelum validasi
        $mergeData = [];
        if ($request->has('phone')) {
            $mergeData['phone'] = $request->filled('phone') ? trim((string) $request->input('phone')) : null;
        }
        if ($request->has('username')) {
            $mergeData['username'] = $request->filled('username') ? strtolower(trim((string) $request->input('username'))) : null;
        }
        if (! empty($mergeData)) {
            $request->merge($mergeData);
        }

        // Tentukan nilai efektif setelah perubahan untuk memastikan minimal salah satu terisi
        $effectivePhone = $request->has('phone') ? $request->input('phone') : $profile->phone;
        $effectiveUsername = $request->has('username') ? $request->input('username') : $profile->username;

        if (empty($effectivePhone) && empty($effectiveUsername)) {
            return response()->json([
                'message' => 'Minimal Nomor WhatsApp atau Username harus tetap terisi agar Anda dapat masuk ke akun.',
            ], 422);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'display_name' => ['nullable', 'string', 'max:100'],
            'phone' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^[0-9+]{8,20}$/',
                Rule::unique('resident_profiles', 'phone')->ignore($profile->id),
            ],
            'username' => [
                'nullable',
                'string',
                'max:40',
                'regex:/^(?=.*[a-z])[a-z0-9._]{3,40}$/',
                Rule::unique('resident_profiles', 'username')->ignore($profile->id),
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
        ], [
            'phone.regex' => 'Nomor WhatsApp harus berupa angka 8–20 digit.',
            'phone.unique' => 'Nomor WhatsApp ini sudah digunakan akun warga lain.',
            'username.regex' => 'Username minimal 3 karakter, wajib mengandung huruf, dan hanya boleh huruf kecil, angka, titik (.), atau garis bawah (_).',
            'username.unique' => 'Username ini sudah digunakan warga lain, silakan pilih yang berbeda.',
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

        if (array_key_exists('phone', $validated)) {
            $profile->phone = $validated['phone'];
        }

        if (array_key_exists('username', $validated)) {
            $profile->username = $validated['username'];
        }

        // Sinkronkan email sintetis dengan identitas utama yang aktif
        $syntheticIdentifier = $profile->phone ?: $profile->username;
        if ($syntheticIdentifier) {
            $user->email = $syntheticIdentifier . '@ppmt.local';
        }

        $user->save();

        $updates = [
            'phone' => $profile->phone,
            'username' => $profile->username,
        ];

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
                'username' => $profile->username,
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

        if ($actor->currentAccessToken()?->can('impersonated')) {
            if ($request->filled('nib_shm') || $request->boolean('clear_nib_shm')) {
                return response()->json([
                    'message' => 'Nomor NIB SHM warga tidak dapat diubah atau dihapus selama sesi bantuan teknis Admin.',
                ], 403);
            }
        }

        $isSelf = $membership->user_id === $actor->id;
        $isGovernor = method_exists($actor, 'isGlobalGovernor') && $actor->isGlobalGovernor();

        $isVerifiedPrimaryOwnerOfUnit = $actor->unitMemberships()
            ->where('unit_id', $membership->unit_id)
            ->where('is_owner', true)
            ->where('owner_role', 'primary_owner')
            ->where('ownership_verification_status', 'verified_shm')
            ->where('is_approved', true)
            ->exists();

        if (! $isSelf && ! $isGovernor && ! $isVerifiedPrimaryOwnerOfUnit) {
            return response()->json([
                'message' => 'Anda tidak memiliki hak untuk mengubah status entitas/peran pada keanggotaan kavling ini.',
            ], 403);
        }

        $validated = $request->validate([
            'is_owner' => ['required', 'boolean'],
            'owner_role' => ['nullable', Rule::in(['primary_owner', 'co_owner'])],
            'is_occupant' => ['required', 'boolean'],
            'resident_role' => ['nullable', Rule::in([
                'primary_resident', 'family_member', 'co_resident', 'tenant', 'caretaker',
            ])],
            'nib_shm' => ['nullable', 'string', 'max:60'],
            'clear_nib_shm' => ['sometimes', 'boolean'],
            'valid_until' => ['nullable', 'date', 'after:today'],
        ]);

        if (! $validated['is_owner'] && ! $validated['is_occupant']) {
            return response()->json([
                'message' => 'Pilih minimal satu peran: sebagai Pemilik Kavling, Penghuni Kavling, atau keduanya.',
            ], 422);
        }

        $isOwner = (bool) $validated['is_owner'];
        $isOccupant = (bool) $validated['is_occupant'];
        $ownerRole = $isOwner ? ($validated['owner_role'] ?? 'primary_owner') : null;
        $residentRole = $isOccupant ? ($validated['resident_role'] ?? 'primary_resident') : null;

        // Kolom warisan (legacy compatibility)
        $legacyEntityType = $isOwner ? 'owner' : 'resident';
        $legacySubRole = $isOwner ? $ownerRole : $residentRole;

        // Penentuan status verifikasi kepemilikan NIB SHM
        $newNibShm = isset($validated['nib_shm']) && trim($validated['nib_shm']) !== ''
            ? strtoupper(trim($validated['nib_shm']))
            : null;

        $ownershipStatus = 'none';
        $nibToSave = $membership->nib_shm_encrypted;
        $verifiedAt = $membership->ownership_verified_at;
        $verifiedBy = $membership->ownership_verified_by_user_id;

        if (! $isOwner) {
            $ownershipStatus = 'none';
            $nibToSave = null;
            $verifiedAt = null;
            $verifiedBy = null;
        } else {
            if (! empty($validated['clear_nib_shm'])) {
                $nibToSave = null;
                $ownershipStatus = 'unverified_claim';
                $verifiedAt = null;
                $verifiedBy = null;
            } elseif ($newNibShm !== null) {
                $nibToSave = $newNibShm;
                // Jika pengurus pusat yang menginputkan langsung, bisa langsung verified; jika warga biasa, statusnya menunggu verifikasi NIB SHM
                $ownershipStatus = $isGovernor ? 'verified_shm' : 'unverified_claim';
                $verifiedAt = $isGovernor ? now() : null;
                $verifiedBy = $isGovernor ? $actor->id : null;
            } else {
                // Pertahankan status verified_shm jika sebelumnya sudah sah dan tidak diubah NIB-nya, selain itu unverified_claim
                $ownershipStatus = ($membership->is_owner && $membership->ownership_verification_status === 'verified_shm')
                    ? 'verified_shm'
                    : 'unverified_claim';
            }
        }

        DB::transaction(function () use (
            $membership,
            $isOwner,
            $ownerRole,
            $isOccupant,
            $residentRole,
            $legacyEntityType,
            $legacySubRole,
            $ownershipStatus,
            $nibToSave,
            $verifiedAt,
            $verifiedBy,
            $validated
        ) {
            $membership->update([
                'is_owner' => $isOwner,
                'owner_role' => $ownerRole,
                'is_occupant' => $isOccupant,
                'resident_role' => $residentRole,
                'ownership_verification_status' => $ownershipStatus,
                'nib_shm_encrypted' => $nibToSave,
                'ownership_verified_at' => $verifiedAt,
                'ownership_verified_by_user_id' => $verifiedBy,
                'entity_type' => $legacyEntityType,
                'sub_role' => $legacySubRole,
                'is_primary_contact' => $ownerRole === 'primary_owner' || $residentRole === 'primary_resident',
                'valid_until' => $residentRole === 'tenant' ? ($validated['valid_until'] ?? $membership->valid_until) : null,
            ]);

            // Sinkronkan badge reputasi level 4 pada profil warga
            $hasAnyVerifiedShm = UnitMembership::where('user_id', $membership->user_id)
                ->where('is_owner', true)
                ->where('ownership_verification_status', 'verified_shm')
                ->exists();

            $membership->user->profile()->update([
                'is_ownership_doc_verified' => $hasAnyVerifiedShm,
            ]);

            // Sinkronkan status hunian kavling jika keanggotaan sudah disetujui
            if ($membership->is_approved && $isOccupant) {
                $membership->unit()->update([
                    'occupancy_status' => $residentRole === 'tenant' ? 'rented' : 'occupied',
                ]);
            }
        });

        $message = $isOwner && $ownershipStatus === 'unverified_claim'
            ? "Peran pada Kavling {$membership->unit->code} diperbarui. Status kepemilikan tercatat sebagai 'Klaim Sepihak Tanpa Bukti NIB SHM (Belum Terverifikasi)' hingga disahkan pengurus."
            : "Status kepemilikan & hunian pada Kavling {$membership->unit->code} berhasil diperbarui.";

        return response()->json([
            'message' => $message,
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
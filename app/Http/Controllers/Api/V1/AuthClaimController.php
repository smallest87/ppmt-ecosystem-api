<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use App\Models\User;
use App\Models\UnitInvite;
use App\Models\VerificationVoucher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AuthClaimController extends Controller
{
    public function registerClaim(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['required', 'string', 'max:20', 'unique:resident_profiles,phone'],
            'password' => ['required', 'string', 'min:8'],
            'unit_code' => ['required', 'string', 'exists:units,code'],
            'entity_type' => ['required', Rule::in(['owner', 'resident'])],
            'sub_role' => ['required', Rule::in([
                'primary_owner', 'co_owner',
                'primary_resident', 'family_member', 'co_resident', 'tenant', 'caretaker',
            ])],
            'invite_token' => ['nullable', 'string'],
        ]);

        $unit = Unit::where('code', $validated['unit_code'])->firstOrFail();
        $invite = null;

        if (! empty($validated['invite_token'])) {
            $invite = UnitInvite::where('token', $validated['invite_token'])
                ->where('unit_id', $unit->id)
                ->first();

            if (! $invite || ! $invite->isUsable()) {
                return response()->json([
                    'message' => 'Tautan undangan tidak valid atau sudah kedaluwarsa.',
                ], 422);
            }
        }

        $user = DB::transaction(function () use ($validated, $unit, $invite) {
            $isAutoVerified = ! is_null($invite);

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['phone'] . '@ppmt.local',
                'password' => Hash::make($validated['password']),
            ]);

            $user->profile()->create([
                'phone' => $validated['phone'],
                'global_role' => 'warga_ppmt',
                'is_verified' => $isAutoVerified,
                'privacy_settings' => [
                    'show_phone' => false,
                    'show_ownership' => false,
                ],
            ]);

            $user->unitMemberships()->create([
                'unit_id' => $unit->id,
                'entity_type' => $invite ? $invite->entity_type : $validated['entity_type'],
                'sub_role' => $invite ? $invite->sub_role : $validated['sub_role'],
                'is_primary_contact' => in_array($validated['sub_role'], ['primary_owner', 'primary_resident']),
                'is_approved' => $isAutoVerified,
                'valid_until' => $invite?->member_valid_until,
            ]);

            if ($invite) {
                $invite->update([
                    'used_by_user_id' => $user->id,
                    'used_at' => now(),
                ]);

                VerificationVoucher::create([
                    'target_user_id' => $user->id,
                    'unit_id' => $unit->id,
                    'voucher_user_id' => $invite->created_by_user_id,
                    'method' => 'invite_link',
                ]);

                $unit->update(['occupancy_status' => $invite->sub_role === 'tenant' ? 'rented' : 'occupied']);
            } elseif ($unit->occupancy_status === 'unclaimed') {
                $unit->update(['occupancy_status' => 'pending']);
            }

            return $user;
        });

        $token = $user->createToken('ppmt-client')->plainTextToken;

        return response()->json([
            'message' => $user->profile->is_verified
                ? "Selamat datang di PPMT! Keanggotaan unit {$unit->code} langsung aktif melalui undangan sah."
                : "Pendaftaran klaim unit berhasil. Menunggu verifikasi sosial dari Koordinator Zona ({$unit->zone}) atau Pemilik Utama.",
            'token' => $token,
            'data' => [
                'user_id' => $user->id,
                'name' => $user->name,
                'unit_code' => $unit->code,
                'zone' => $unit->zone,
                'is_verified' => $user->profile->is_verified,
            ],
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $user = User::whereHas('profile', fn ($q) => $q->where('phone', $validated['phone']))->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => 'Nomor WhatsApp atau kata sandi salah.'], 401);
        }

        $token = $user->createToken('ppmt-client')->plainTextToken;

        return response()->json([
            'token' => $token,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'is_verified' => $user->profile->is_verified,
                'reputation_badge' => $user->profile->reputation_badge,
            ],
        ]);
    }
}
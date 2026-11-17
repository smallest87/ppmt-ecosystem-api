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
        // Normalisasi kode kavling (contoh: "c-05" -> "C-5", "b-12a" -> "B-12A")
        if ($request->filled('unit_code')) {
            $normalizedCode = strtoupper(trim((string) $request->input('unit_code')));
            $normalizedCode = preg_replace('/^([A-D])-0+([1-9][0-9]*[A-B]?)$/', '$1-$2', $normalizedCode);
            $request->merge(['unit_code' => $normalizedCode]);
        }

        $request->merge([
            'phone' => $request->filled('phone') ? trim((string) $request->input('phone')) : null,
            'username' => $request->filled('username') ? strtolower(trim((string) $request->input('username'))) : null,
            'guarantor_identifier' => $request->filled('guarantor_identifier')
                ? trim((string) $request->input('guarantor_identifier'))
                : null,
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'gender' => ['required', Rule::in(['male', 'female'])],
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
            'guarantor_identifier' => [
                'nullable',
                'required_without:phone',
                'string',
                'max:60',
            ],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'unit_code' => [
                'required',
                'string',
                Rule::exists('units', 'code')->where(fn ($q) => $q->where('unit_type', '!=', 'fasum')),
            ],
            'entity_type' => ['required', Rule::in(['owner', 'resident'])],
            'sub_role' => ['required', Rule::in([
                'primary_owner', 'co_owner',
                'primary_resident', 'family_member', 'co_resident', 'tenant', 'caretaker',
            ])],
            'invite_token' => ['nullable', 'string'],
        ], [
            'gender.required' => 'Pilih jenis kelamin (Laki-laki atau Perempuan).',
            'gender.in' => 'Pilihan jenis kelamin tidak valid.',
            'phone.required_without' => 'Isi minimal Nomor WhatsApp atau Username untuk masuk ke akun.',
            'username.required_without' => 'Isi minimal Username atau Nomor WhatsApp untuk masuk ke akun.',
            'guarantor_identifier.required_without' => 'Pendaftaran dengan Username wajib menyertakan Nomor WA atau Username penjamin.',
            'phone.regex' => 'Nomor WhatsApp harus berupa angka 8–20 digit.',
            'username.regex' => 'Username minimal 3 karakter, wajib mengandung huruf, dan hanya boleh huruf kecil, angka, titik (.), atau garis bawah (_).',
            'phone.unique' => 'Nomor WhatsApp ini sudah terdaftar pada akun warga lain.',
            'username.unique' => 'Username ini sudah digunakan warga lain, silakan pilih yang berbeda.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak cocok.',
            'unit_code.exists' => 'Nomor kavling tidak ditemukan. Periksa kembali pilihan Blok dan Nomor Rumah Anda.',
        ]);

        $unit = Unit::where('code', $validated['unit_code'])->firstOrFail();
        $invite = null;
        $guarantor = null;

        // Validasi penjamin wajib jika mendaftar menggunakan Username (tanpa Nomor WA)
        if (empty($validated['phone'])) {
            $rawGuarantor = $validated['guarantor_identifier'];
            $lowerGuarantor = strtolower($rawGuarantor);

            if ($lowerGuarantor === $validated['username']) {
                return response()->json([
                    'message' => 'Anda tidak dapat menjamin akun Anda sendiri.',
                ], 422);
            }

            $guarantor = User::whereHas('profile', function ($q) use ($rawGuarantor, $lowerGuarantor) {
                $q->where('is_verified', true)
                  ->where(function ($sub) use ($rawGuarantor, $lowerGuarantor) {
                      $sub->where('phone', $rawGuarantor)
                          ->orWhere('username', $lowerGuarantor);
                  });
            })->first();

            if (! $guarantor) {
                return response()->json([
                    'message' => 'Penjamin tidak ditemukan atau akun penjamin belum terverifikasi di PPMT.',
                ], 422);
            }

            // Syarat OR: penghuni terverifikasi di kavling yang sama ATAU pengurus aktif
            $isSameUnitVerifiedMember = $guarantor->unitMemberships()
                ->where('unit_id', $unit->id)
                ->where('is_approved', true)
                ->exists();

            $isActivePengurus = $guarantor->hasAdminAccess();

            if (! $isSameUnitVerifiedMember && ! $isActivePengurus) {
                return response()->json([
                    'message' => "Penjamin ({$guarantor->name}) bukan penghuni terverifikasi di kavling {$unit->code} maupun pengurus paguyuban.",
                ], 422);
            }
        }

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

        $user = DB::transaction(function () use ($validated, $unit, $invite, $guarantor) {
            $isAutoVerified = ! is_null($invite);
            $syntheticIdentifier = $validated['phone'] ?: $validated['username'];

            $user = User::create([
                'name' => $validated['name'],
                'email' => $syntheticIdentifier . '@ppmt.local',
                'password' => Hash::make($validated['password']),
            ]);

            $user->profile()->create([
                'phone' => $validated['phone'],
                'username' => $validated['username'],
                'gender' => $validated['gender'],
                'global_role' => 'warga_ppmt',
                'is_verified' => $isAutoVerified,
                'privacy_settings' => [
                    'show_phone' => false,
                    'show_ownership' => false,
                ],
            ]);

            $user->unitMemberships()->create([
                'unit_id' => $unit->id,
                'guarantor_user_id' => $guarantor?->id,
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

        $pendingMsg = $guarantor
            ? "Pendaftaran berhasil dengan penjamin {$guarantor->name}. Menunggu pengesahan dari penjamin atau pengurus wilayah."
            : "Pendaftaran klaim unit berhasil. Menunggu verifikasi sosial dari Koordinator Zona ({$unit->zone}) atau Pemilik Utama.";

        return response()->json([
            'message' => $user->profile->is_verified
                ? "Selamat datang di PPMT! Keanggotaan unit {$unit->code} langsung aktif melalui undangan sah."
                : $pendingMsg,
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
        $rawIdentifier = $request->input('login_id', $request->input('phone'));
        $request->merge([
            'login_id' => is_string($rawIdentifier) ? trim($rawIdentifier) : '',
        ]);

        $validated = $request->validate([
            'login_id' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'login_id.required' => 'Masukkan Nomor WhatsApp atau Username Anda.',
        ]);

        $identifier = $validated['login_id'];
        $lowerIdentifier = strtolower($identifier);

        $user = User::whereHas('profile', function ($q) use ($identifier, $lowerIdentifier) {
            $q->where('phone', $identifier)
              ->orWhere('username', $lowerIdentifier);
        })->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return response()->json(['message' => 'Nomor WhatsApp / Username atau kata sandi salah.'], 401);
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
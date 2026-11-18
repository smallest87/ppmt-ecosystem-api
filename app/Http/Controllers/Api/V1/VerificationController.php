<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use App\Models\User;
use App\Models\VerificationVoucher;
use App\Models\UnitMembership;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class VerificationController extends Controller
{
    public function approve(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'target_user_id' => ['required', 'exists:users,id'],
            'unit_code' => ['required', 'exists:units,code'],
        ]);

        $unit = Unit::where('code', $validated['unit_code'])->firstOrFail();

        // Pengecekan mutlak di Backend via VerificationPolicy
        Gate::authorize('vouchUser', [VerificationVoucher::class, $unit]);

        $targetUser = User::with('profile')->findOrFail($validated['target_user_id']);
        $voucher = $request->user();

        DB::transaction(function () use ($targetUser, $unit, $voucher) {
            $targetUser->profile()->update(['is_verified' => true]);

            $targetUser->unitMemberships()
                ->where('unit_id', $unit->id)
                ->update(['is_approved' => true]);

            $membership = $targetUser->unitMemberships()
                ->where('unit_id', $unit->id)
                ->first();

            $method = match ($voucher->profile->global_role) {
                'admin' => 'genesis_seed',
                'koordinator_blok' => 'coordinator_approval',
                default => 'owner_approval',
            };

            VerificationVoucher::create([
                'target_user_id' => $targetUser->id,
                'unit_id' => $unit->id,
                'voucher_user_id' => $voucher->id,
                'method' => $method,
            ]);

            $unit->update([
                'occupancy_status' => $membership?->sub_role === 'tenant' ? 'rented' : 'occupied',
            ]);
        });

        return response()->json([
            'message' => "Warga {$targetUser->name} untuk unit {$unit->code} berhasil diverifikasi secara sah.",
        ]);
    }

    public function pending(Request $request): JsonResponse
    {
        $voucher = $request->user();
        $profile = $voucher->profile;

        if (! $profile || ! $profile->is_verified) {
            return response()->json(['data' => []]);
        }

        $query = UnitMembership::query()
            ->where('is_approved', false)
            ->with([
                'user:id,name',
                'user.profile:user_id,phone,username,gender',
                'guarantor:id,name',
                'unit:id,code,block,zone',
            ]);

        if (! $voucher->isGlobalGovernor()) {
            $managedZones = $voucher->governanceRoles()
                ->where('role_code', 'koordinator_zona')
                ->pluck('scope_value')
                ->filter();

            $managedBlocks = $voucher->governanceRoles()
                ->where('role_code', 'koordinator_blok')
                ->pluck('scope_value')
                ->filter();

            if ($profile->global_role === 'koordinator_blok') {
                $legacyZones = $voucher->unitMemberships()
                    ->where('is_approved', true)
                    ->with('unit:id,zone')
                    ->get()
                    ->pluck('unit.zone');
                $managedZones = $managedZones->merge($legacyZones)->unique();
            }

            $ownedUnitIds = $voucher->unitMemberships()
                ->where('entity_type', 'owner')
                ->where('sub_role', 'primary_owner')
                ->where('is_approved', true)
                ->pluck('unit_id');

            $query->where(function ($q) use ($voucher, $managedZones, $managedBlocks, $ownedUnitIds) {
                // Pengajuan yang menunjuk user ini langsung sebagai penjamin
                $q->orWhere('guarantor_user_id', $voucher->id);

                if ($managedZones->isNotEmpty()) {
                    $q->orWhereHas('unit', fn ($uq) => $uq->whereIn('zone', $managedZones));
                }
                if ($managedBlocks->isNotEmpty()) {
                    $q->orWhereHas('unit', fn ($uq) => $uq->whereIn('block', $managedBlocks));
                }
                if ($ownedUnitIds->isNotEmpty()) {
                    $q->orWhereIn('unit_id', $ownedUnitIds);
                }
            });
        }

        $pendingList = $query->latest()->get()->map(fn ($m) => [
            'membership_id' => $m->id,
            'target_user_id' => $m->user_id,
            'name' => $m->user->name,
            'gender' => $m->user->profile?->gender,
            'phone' => $m->user->profile?->phone,
            'username' => $m->user->profile?->username,
            'guarantor_name' => $m->guarantor?->name,
            'unit_code' => $m->unit->code,
            'zone' => $m->unit->zone,
            'entity_type' => $m->entity_type,
            'sub_role' => $m->sub_role,
            'created_at' => $m->created_at->diffForHumans(),
        ]);

        return response()->json(['data' => $pendingList]);
    }

    public function verifyOwnership(Request $request, UnitMembership $membership): JsonResponse
    {
        $unit = $membership->unit;
        Gate::authorize('vouchUser', [VerificationVoucher::class, $unit]);

        $validated = $request->validate([
            'action' => ['required', Rule::in(['approve_shm', 'revoke_shm'])],
            'nib_shm' => ['nullable', 'string', 'max:60'],
        ]);

        if (! $membership->is_owner) {
            return response()->json([
                'message' => 'Warga ini tidak mencentang status sebagai pemilik pada kavling tersebut.',
            ], 422);
        }

        $actor = $request->user();

        DB::transaction(function () use ($membership, $validated, $actor) {
            if ($validated['action'] === 'approve_shm') {
                $nib = ! empty($validated['nib_shm'])
                    ? strtoupper(trim($validated['nib_shm']))
                    : $membership->nib_shm_encrypted;

                $membership->update([
                    'nib_shm_encrypted' => $nib,
                    'ownership_verification_status' => 'verified_shm',
                    'ownership_verified_at' => now(),
                    'ownership_verified_by_user_id' => $actor->id,
                ]);
            } else {
                $membership->update([
                    'ownership_verification_status' => 'unverified_claim',
                    'ownership_verified_at' => null,
                    'ownership_verified_by_user_id' => null,
                ]);
            }

            $hasAnyVerifiedShm = UnitMembership::where('user_id', $membership->user_id)
                ->where('is_owner', true)
                ->where('ownership_verification_status', 'verified_shm')
                ->exists();

            $membership->user->profile()->update([
                'is_ownership_doc_verified' => $hasAnyVerifiedShm,
            ]);
        });

        $msg = $validated['action'] === 'approve_shm'
            ? "Bukti NIB SHM {$membership->user->name} pada Kavling {$unit->code} telah disahkan."
            : "Status NIB SHM {$membership->user->name} dikembalikan menjadi klaim belum terverifikasi.";

        return response()->json([
            'message' => $msg,
            'data' => $membership->fresh(),
        ]);
    }
}
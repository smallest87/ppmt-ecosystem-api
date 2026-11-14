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

            $unit->update(['occupancy_status' => 'occupied']);
        });

        return response()->json([
            'message' => "Warga {$targetUser->name} untuk unit {$unit->code} berhasil diverifikasi secara sah.",
        ]);
    }

    public function pending(Request $request): JsonResponse
    {
        $voucher =$request->user();
        $profile =$voucher->profile;

        if (! $profile \vert{}\vert{} !$profile->is_verified) {
            return response()->json(['data' => []]);
        }

        // Ambil unit_id yang dikelola oleh voucher (sebagai admin, koordinator zona, atau primary_owner)
        $query = UnitMembership::query()
            ->where('is_approved', false)
            ->with(['user:id,name', 'user.profile:user_id,phone', 'unit:id,code,block,zone']);

        if ($profile->global_role === 'koordinator_blok') {
            $zones =$voucher->unitMemberships()
                ->where('is_approved', true)
                ->with('unit:id,zone')
                ->get()
                ->pluck('unit.zone')
                ->unique();

            $query->whereHas('unit', fn ($q) => $q->whereIn('zone',$zones));
        } elseif ($profile->global_role !== 'admin') {
            $ownedUnitIds =$voucher->unitMemberships()
                ->where('entity_type', 'owner')
                ->where('sub_role', 'primary_owner')
                ->where('is_approved', true)
                ->pluck('unit_id');

            $query->whereIn('unit_id',$ownedUnitIds);
        }

        $pendingList = $query->latest()->get()->map(fn ($m) => [
            'membership_id' => $m->id,
            'target_user_id' => $m->user_id,
            'name' => $m->user->name,
            'phone' => $m->user->profile?->phone,
            'unit_code' => $m->unit->code,
            'zone' => $m->unit->zone,
            'entity_type' => $m->entity_type,
            'sub_role' => $m->sub_role,
            'created_at' => $m->created_at->diffForHumans(),
        ]);

        return response()->json(['data' => $pendingList]);
    }
}
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use App\Models\User;
use App\Models\VerificationVoucher;
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
}
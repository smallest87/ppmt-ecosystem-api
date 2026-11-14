<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use App\Models\UnitInvite;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UnitInviteController extends Controller
{
    public function store(Request $request, string $unitCode): JsonResponse
    {
        $unit = Unit::where('code', $unitCode)->firstOrFail();

        // Terkunci mutlak oleh UnitPolicy (bukan dari badge reputasi)
        Gate::authorize('inviteMember', $unit);

        $validated = $request->validate([
            'entity_type' => ['required', Rule::in(['owner', 'resident'])],
            'sub_role' => ['required', Rule::in([
                'co_owner', 'primary_resident', 'family_member', 'co_resident', 'tenant', 'caretaker',
            ])],
            'member_valid_until' => [
                Rule::requiredIf(fn () => $request->input('sub_role') === 'tenant'),
                'nullable',
                'date',
                'after:today',
            ],
        ]);

        $invite = UnitInvite::create([
            'unit_id' => $unit->id,
            'created_by_user_id' => $request->user()->id,
            'token' => Str::random(40),
            'entity_type' => $validated['entity_type'],
            'sub_role' => $validated['sub_role'],
            'member_valid_until' => $validated['member_valid_until'] ?? null,
            'expires_at' => now()->addHours(48),
        ]);

        return response()->json([
            'message' => "Tautan undangan untuk unit {$unit->code} berhasil dibuat (berlaku 48 jam).",
            'data' => [
                'invite_token' => $invite->token,
                'unit_code' => $unit->code,
                'entity_type' => $invite->entity_type,
                'sub_role' => $invite->sub_role,
                'member_valid_until' => $invite->member_valid_until?->toDateString(),
                'expires_at' => $invite->expires_at->toIso8601String(),
            ],
        ], 201);
    }
}
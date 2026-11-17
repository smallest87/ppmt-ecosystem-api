<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Aspiration;
use App\Models\AspirationSignature;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AspirationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $isAdmin = $user->hasAdminAccess();

        $myUnitIds = $user->unitMemberships()
            ->where('is_approved', true)
            ->pluck('unit_id')
            ->unique()
            ->values()
            ->all();

        $query = Aspiration::with([
            'author.profile',
            'unit:id,code',
            'signatures' => function ($q) {
                $q->with('unit:id,code')->orderByDesc('created_at');
            },
        ])->orderByRaw("CASE status
                WHEN 'open' THEN 1
                WHEN 'submitted' THEN 2
                WHEN 'pending' THEN 3
                WHEN 'resolved' THEN 4
                WHEN 'rejected' THEN 5
                ELSE 6 END")
          ->orderByDesc('created_at');

        if (! $isAdmin) {
            $query->where(function ($q) use ($user) {
                $q->whereIn('status', ['open', 'submitted', 'resolved'])
                  ->orWhere('user_id', $user->id);
            });
        }

        $aspirations = $query->get()->map(function (Aspiration $item) use ($myUnitIds) {
            $signedUnitCodes = $item->signatures
                ->map(fn ($s) => $s->unit?->code)
                ->filter()
                ->unique()
                ->values()
                ->all();

            $signedByMyUnits = $item->signatures
                ->whereIn('unit_id', $myUnitIds)
                ->pluck('unit_id')
                ->unique()
                ->values()
                ->all();

            $testimonials = $item->signatures
                ->filter(fn ($s) => ! empty($s->note))
                ->map(fn ($s) => [
                    'unit_code' => $s->unit?->code ?? '-',
                    'note' => $s->note,
                    'created_at' => $s->created_at?->format('d M Y'),
                ])
                ->values()
                ->all();

            return [
                'id' => $item->id,
                'category' => $item->category,
                'title' => $item->title,
                'description' => $item->description,
                'location_scope' => $item->location_scope,
                'status' => $item->status,
                'target_signatures' => $item->target_signatures,
                'signatures_count' => count($signedUnitCodes),
                'signed_unit_codes' => $signedUnitCodes,
                'signed_by_my_unit_ids' => $signedByMyUnits,
                'is_signed_by_me' => count($signedByMyUnits) > 0,
                'testimonials' => $testimonials,
                'developer_response' => $item->developer_response,
                'author_name' => $item->author?->profile?->display_name ?: ($item->author?->name ?? 'Warga PPMT'),
                'author_unit' => $item->unit?->code,
                'created_at' => $item->created_at?->format('d M Y'),
            ];
        });

        return response()->json([
            'data' => $aspirations,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'category' => ['required', 'string', 'max:50'],
            'title' => ['required', 'string', 'min:8', 'max:180'],
            'description' => ['required', 'string', 'min:20', 'max:3000'],
            'location_scope' => ['nullable', 'string', 'max:120'],
            'unit_id' => ['nullable', 'integer', 'exists:units,id'],
            'target_signatures' => ['nullable', 'integer', 'min:5', 'max:91'],
        ]);

        $approvedUnitIds = $user->unitMemberships()
            ->where('is_approved', true)
            ->pluck('unit_id')
            ->all();

        $chosenUnitId = ! empty($validated['unit_id']) && in_array($validated['unit_id'], $approvedUnitIds)
            ? $validated['unit_id']
            : ($approvedUnitIds[0] ?? null);

        $isAdmin = $user->hasAdminAccess();

        $aspiration = Aspiration::create([
            'user_id' => $user->id,
            'unit_id' => $chosenUnitId,
            'category' => $validated['category'],
            'title' => $validated['title'],
            'description' => $validated['description'],
            'location_scope' => $validated['location_scope'] ?? null,
            'status' => $isAdmin ? 'open' : 'pending',
            'target_signatures' => $validated['target_signatures'] ?? 30,
            'reviewed_at' => $isAdmin ? now() : null,
        ]);

        if ($chosenUnitId) {
            AspirationSignature::firstOrCreate(
                [
                    'aspiration_id' => $aspiration->id,
                    'unit_id' => $chosenUnitId,
                ],
                [
                    'user_id' => $user->id,
                    'note' => null,
                ]
            );
        }

        return response()->json([
            'message' => $isAdmin
                ? 'Aspirasi berhasil diterbitkan dan dibuka untuk dukungan warga.'
                : 'Aspirasi berhasil dikirim! Menunggu kurasi singkat pengurus sebelum dibuka untuk dukungan kolektif.',
            'data' => $aspiration,
        ], 201);
    }

    public function sign(Request $request, Aspiration $aspiration): JsonResponse
    {
        $user = $request->user();

        if (! in_array($aspiration->status, ['open', 'submitted'])) {
            return response()->json([
                'message' => 'Penggalangan dukungan untuk aspirasi ini sedang tidak dibuka.',
            ], 422);
        }

        $validated = $request->validate([
            'unit_id' => ['nullable', 'integer', 'exists:units,id'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $approvedUnitIds = $user->unitMemberships()
            ->where('is_approved', true)
            ->pluck('unit_id')
            ->all();

        if (empty($approvedUnitIds)) {
            return response()->json([
                'message' => 'Anda belum memiliki keanggotaan kavling yang disahkan.',
            ], 403);
        }

        $unitId = ! empty($validated['unit_id']) && in_array($validated['unit_id'], $approvedUnitIds)
            ? $validated['unit_id']
            : $approvedUnitIds[0];

        $existing = AspirationSignature::where('aspiration_id', $aspiration->id)
            ->where('unit_id', $unitId)
            ->first();

        if ($existing) {
            if (! empty($validated['note'])) {
                $existing->update(['note' => $validated['note']]);
                return response()->json([
                    'message' => 'Catatan kesaksian kavling Anda berhasil diperbarui.',
                ]);
            }

            return response()->json([
                'message' => 'Kavling Anda sudah tercatat mendukung aspirasi ini.',
            ], 422);
        }

        AspirationSignature::create([
            'aspiration_id' => $aspiration->id,
            'unit_id' => $unitId,
            'user_id' => $user->id,
            'note' => $validated['note'] ?? null,
        ]);

        return response()->json([
            'message' => 'Dukungan resmi atas nama kavling Anda berhasil dicatat!',
        ], 201);
    }

    public function updateStatus(Request $request, Aspiration $aspiration): JsonResponse
    {
        $user = $request->user();

        if (! $user->hasAdminAccess()) {
            return response()->json([
                'message' => 'Hanya pengurus yang dapat memperbarui status aspirasi.',
            ], 403);
        }

        $validated = $request->validate([
            'status' => ['required', 'in:pending,open,submitted,resolved,rejected'],
            'target_signatures' => ['nullable', 'integer', 'min:5', 'max:91'],
            'developer_response' => ['nullable', 'string', 'max:2000'],
        ]);

        $aspiration->update([
            'status' => $validated['status'],
            'target_signatures' => $validated['target_signatures'] ?? $aspiration->target_signatures,
            'developer_response' => $validated['developer_response'] ?? $aspiration->developer_response,
            'reviewed_at' => now(),
        ]);

        return response()->json([
            'message' => 'Status dan catatan tindak lanjut aspirasi berhasil diperbarui.',
            'data' => $aspiration,
        ]);
    }
}
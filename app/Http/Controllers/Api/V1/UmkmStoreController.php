<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\UmkmStore;
use App\Models\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UmkmStoreController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $stores = UmkmStore::query()
            ->with(['unit:id,code,block,zone', 'owner:id,name'])
            ->when($request->query('category'), fn ($q, $cat) => $q->where('category', $cat))
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->orderByRaw("CASE status WHEN 'open' THEN 1 WHEN 'open_po' THEN 2 ELSE 3 END")
            ->latest()
            ->get();

        return response()->json([
            'total' => $stores->count(),
            'data' => $stores,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'unit_code' => ['required', 'string', 'exists:units,code'],
            'store_name' => ['required', 'string', 'max:120'],
            'category' => ['required', Rule::in(['kuliner', 'sembako', 'jasa', 'pinjam_alat', 'lainnya'])],
            'description' => ['nullable', 'string', 'max:500'],
            'status' => ['nullable', Rule::in(['open', 'open_po', 'closed'])],
            'whatsapp_number' => ['nullable', 'string', 'max:20'],
            'catalog_items' => ['nullable', 'array'],
            'catalog_items.*.name' => ['required_with:catalog_items', 'string', 'max:100'],
            'catalog_items.*.price' => ['required_with:catalog_items', 'numeric', 'min:0'],
        ]);

        $user = $request->user();
        $unit = Unit::where('code', $validated['unit_code'])->firstOrFail();

        // Pastikan warga tersebut benar-benar terdaftar & disetujui pada unit yang diklaim
        $isMemberOfUnit = $user->unitMemberships()
            ->where('unit_id', $unit->id)
            ->where('is_approved', true)
            ->exists();

        if (! $isMemberOfUnit) {
            return response()->json([
                'message' => "Anda hanya dapat membuka lapak usaha pada unit hunian Anda sendiri ({$unit->code} tidak terdaftar atas nama Anda).",
            ], 403);
        }

        $store = UmkmStore::create([
            'unit_id' => $unit->id,
            'owner_user_id' => $user->id,
            'store_name' => $validated['store_name'],
            'category' => $validated['category'],
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'] ?? 'open',
            'whatsapp_number' => $validated['whatsapp_number'] ?? $user->profile->phone,
            'catalog_items' => $validated['catalog_items'] ?? [],
        ]);

        return response()->json([
            'message' => "Lapak '{$store->store_name}' di unit {$unit->code} berhasil dibuka di Pasar Tetangga PPMT.",
            'data' => $store->load('unit:id,code,zone'),
        ], 201);
    }

    public function updateStatus(Request $request, UmkmStore $store): JsonResponse
    {
        if ($store->owner_user_id !== $request->user()->id) {
            return response()->json(['message' => 'Anda tidak berhak mengubah status lapak milik warga lain.'], 403);
        }

        $validated = $request->validate([
            'status' => ['required', Rule::in(['open', 'open_po', 'closed'])],
        ]);

        $store->update(['status' => $validated['status']]);

        return response()->json([
            'message' => "Status lapak '{$store->store_name}' diperbarui menjadi: {$store->status}.",
            'data' => $store,
        ]);
    }
}
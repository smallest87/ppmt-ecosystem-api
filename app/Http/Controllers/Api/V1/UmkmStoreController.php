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
    public function myStore(Request $request): JsonResponse
    {
        $store = UmkmStore::query()
            ->with('unit:id,code,block,zone')
            ->where('owner_user_id', $request->user()->id)
            ->first();

        return response()->json([
            'data' => $store,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $stores = UmkmStore::query()
            ->with([
                'unit:id,code,block,zone',
                'owner:id,name',
                'owner.profile:user_id,display_name,privacy_settings',
            ])
            ->when($request->query('category'), fn ($q, $cat) => $q->where('category', $cat))
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->orderByRaw("CASE status WHEN 'open' THEN 1 WHEN 'open_po' THEN 2 ELSE 3 END")
            ->latest()
            ->get()
            ->map(function (UmkmStore $store) {
                $ownerProfile = $store->owner?->profile;
                $privacy = $ownerProfile?->privacy_settings ?? [];
                $showFullName = (bool) ($privacy['show_full_name'] ?? false);

                $publicOwnerName = $ownerProfile?->display_name
                    ?: ($showFullName ? $store->owner?->name : explode(' ', trim((string) $store->owner?->name))[0]);

                $data = $store->toArray();
                $data['owner'] = [
                    'id' => $store->owner?->id,
                    'name' => $publicOwnerName,
                ];

                return $data;
            });

        return response()->json([
            'total' => $stores->count(),
            'data' => $stores,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if (UmkmStore::where('owner_user_id', $user->id)->exists()) {
            return response()->json([
                'message' => 'Akun Anda sudah memiliki lapak terdaftar. Silakan kelola profil dan produk lapak Anda.',
            ], 422);
        }

        $validated = $request->validate([
            'unit_code' => ['required', 'string', 'exists:units,code'],
            'store_name' => ['required', 'string', 'max:120'],
            'category' => ['required', Rule::in(['kuliner', 'sembako', 'jasa', 'pinjam_alat', 'lainnya'])],
            'description' => ['nullable', 'string', 'max:500'],
            'status' => ['nullable', Rule::in(['open', 'open_po', 'closed'])],
            'whatsapp_number' => ['nullable', 'string', 'max:20'],
            'catalog_items' => ['nullable', 'array'],
        ]);

        $unit = Unit::where('code', $validated['unit_code'])->firstOrFail();

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
            'store_name' => trim($validated['store_name']),
            'category' => $validated['category'],
            'description' => isset($validated['description']) ? trim($validated['description']) : null,
            'status' => $validated['status'] ?? 'open',
            'whatsapp_number' => trim($validated['whatsapp_number'] ?? $user->profile?->phone ?? ''),
            'catalog_items' => $validated['catalog_items'] ?? [],
        ]);

        return response()->json([
            'message' => "Lapak '{$store->store_name}' di unit {$unit->code} berhasil dibuka!",
            'data' => $store->load('unit:id,code,block,zone'),
        ], 201);
    }

    public function update(Request $request, UmkmStore $store): JsonResponse
    {
        if ($store->owner_user_id !== $request->user()->id) {
            return response()->json(['message' => 'Anda tidak berhak mengubah lapak milik warga lain.'], 403);
        }

        $validated = $request->validate([
            'store_name' => ['sometimes', 'required', 'string', 'max:120'],
            'category' => ['sometimes', 'required', Rule::in(['kuliner', 'sembako', 'jasa', 'pinjam_alat', 'lainnya'])],
            'description' => ['nullable', 'string', 'max:500'],
            'status' => ['sometimes', 'required', Rule::in(['open', 'open_po', 'closed'])],
            'whatsapp_number' => ['sometimes', 'required', 'string', 'max:20'],
            'catalog_items' => ['sometimes', 'array'],
            'catalog_items.*.id' => ['required_with:catalog_items'],
            'catalog_items.*.name' => ['required_with:catalog_items', 'string', 'max:120'],
            'catalog_items.*.category' => ['required_with:catalog_items', Rule::in(['kuliner', 'sembako', 'jasa', 'pinjam_alat', 'lainnya'])],
            'catalog_items.*.badge_type' => ['required_with:catalog_items', Rule::in(['ready', 'po', 'service', 'loan'])],
            'catalog_items.*.price' => ['required_with:catalog_items', 'numeric', 'min:0'],
            'catalog_items.*.unit_note' => ['nullable', 'string', 'max:100'],
            'catalog_items.*.delivery_tag' => ['nullable', 'string', 'max:100'],
            'catalog_items.*.description' => ['nullable', 'string', 'max:500'],
            'catalog_items.*.is_available' => ['sometimes', 'boolean'],
        ]);

        $store->update($validated);

        return response()->json([
            'message' => 'Perubahan lapak & etalase produk berhasil disimpan.',
            'data' => $store->fresh()->load('unit:id,code,block,zone'),
        ]);
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
            'message' => "Status lapak '{$store->store_name}' diperbarui.",
            'data' => $store,
        ]);
    }
}
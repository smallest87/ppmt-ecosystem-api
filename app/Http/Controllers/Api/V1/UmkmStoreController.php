<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\UmkmStore;
use App\Models\Unit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
        $hourlyWindow = now()->format('YmdH');

        $stores = UmkmStore::query()
            ->where('is_suspended', false)
            ->with([
                'unit:id,code,block,zone',
                'owner:id,name',
                'owner.profile:user_id,display_name,privacy_settings',
            ])
            ->when($request->query('category'), fn ($q, $cat) => $q->where('category', $cat))
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->get()
            ->sortBy(function (UmkmStore $store) use ($hourlyWindow) {
                $statusPriority = match ($store->status) {
                    'open' => 1,
                    'open_po' => 2,
                    default => 3,
                };

                $fairOrder = sprintf('%08u', crc32("ppmt-fair-{$hourlyWindow}-store-{$store->id}") % 100000000);

                return "{$statusPriority}-{$fairOrder}";
            })
            ->values()
            ->map(function (UmkmStore $store) use ($hourlyWindow) {
                $ownerProfile = $store->owner?->profile;
                $privacy = $ownerProfile?->privacy_settings ?? [];
                $showFullName = (bool) ($privacy['show_full_name'] ?? false);

                $publicOwnerName = $ownerProfile?->display_name
                    ?: ($showFullName ? $store->owner?->name : explode(' ', trim((string) $store->owner?->name))[0]);

                $data = $store->toArray();
                $data['is_currently_featured'] = $store->is_currently_featured;
                $data['owner'] = [
                    'id' => $store->owner?->id,
                    'name' => $publicOwnerName,
                ];
                $data['rotation_seed'] = (int) (crc32("ppmt-items-{$hourlyWindow}-store-{$store->id}") % 100000);

                return $data;
            });

        return response()->json([
            'total' => $stores->count(),
            'rotation_window' => $hourlyWindow,
            'data' => $stores,
        ]);
    }

    public function governanceIndex(Request $request): JsonResponse
    {
        if (! $request->user()->canManageMarketplace()) {
            return response()->json(['message' => 'Akses ditolak. Khusus Seksi Ekonomi atau Ketua Paguyuban.'], 403);
        }

        $stores = UmkmStore::query()
            ->with([
                'unit:id,code,block,zone',
                'owner:id,name',
                'owner.profile:user_id,display_name,phone',
                'curator:id,name',
            ])
            ->orderByDesc('is_featured')
            ->orderBy('is_suspended')
            ->latest()
            ->get()
            ->map(function (UmkmStore $store) {
                $data = $store->toArray();
                $data['is_currently_featured'] = $store->is_currently_featured;
                $data['items_count'] = is_array($store->catalog_items) ? count($store->catalog_items) : 0;

                return $data;
            });

        $stats = [
            'total_stores' => $stores->count(),
            'active_stores' => $stores->where('is_suspended', false)->count(),
            'featured_stores' => $stores->where('is_currently_featured', true)->count(),
            'suspended_stores' => $stores->where('is_suspended', true)->count(),
            'total_items' => $stores->sum('items_count'),
            'by_category' => $stores->groupBy('category')->map->count(),
            'by_zone' => $stores->groupBy(fn ($s) => $s['unit']['zone'] ?? 'Lainnya')->map->count(),
        ];

        return response()->json([
            'stats' => $stats,
            'data' => $stores->values(),
        ]);
    }

    public function curate(Request $request, UmkmStore $store): JsonResponse
    {
        if (! $request->user()->canManageMarketplace()) {
            return response()->json(['message' => 'Akses ditolak. Khusus Seksi Ekonomi atau Ketua Paguyuban.'], 403);
        }

        $validated = $request->validate([
            'is_featured' => ['sometimes', 'boolean'],
            'featured_days' => ['nullable', 'integer', 'min:1', 'max:30'],
            'is_suspended' => ['sometimes', 'boolean'],
            'moderation_note' => ['nullable', 'string', 'max:500'],
        ]);

        $isSuspended = array_key_exists('is_suspended', $validated)
            ? (bool) $validated['is_suspended']
            : $store->is_suspended;

        $isFeatured = array_key_exists('is_featured', $validated)
            ? (bool) $validated['is_featured']
            : $store->is_featured;

        if ($isSuspended) {
            $isFeatured = false;
        }

        if ($isFeatured && ! $store->is_currently_featured) {
            $activeFeaturedCount = UmkmStore::query()
                ->where('id', '!=', $store->id)
                ->where('is_featured', true)
                ->where('is_suspended', false)
                ->where(function ($q) {
                    $q->whereNull('featured_until')->orWhere('featured_until', '>', now());
                })
                ->count();

            if ($activeFeaturedCount >= 3) {
                return response()->json([
                    'message' => 'Kuota Sorotan Pekan Ini sudah penuh (maksimal 3 lapak). Nonaktifkan sorotan pada salah satu lapak terlebih dahulu.',
                ], 422);
            }
        }

        $featuredUntil = $store->featured_until;
        if (! $isFeatured) {
            $featuredUntil = null;
        } elseif (! empty($validated['featured_days'])) {
            $featuredUntil = now()->addDays((int) $validated['featured_days']);
        } elseif (! $store->featured_until || $store->featured_until->isPast()) {
            $featuredUntil = now()->addDays(7);
        }

        $store->update([
            'is_featured' => $isFeatured,
            'featured_until' => $featuredUntil,
            'is_suspended' => $isSuspended,
            'moderation_note' => array_key_exists('moderation_note', $validated)
                ? (trim((string) $validated['moderation_note']) ?: null)
                : $store->moderation_note,
            'curated_by_user_id' => $request->user()->id,
        ]);

        return response()->json([
            'message' => "Pengaturan kurasi untuk lapak '{$store->store_name}' berhasil diperbarui.",
            'data' => $store->fresh()->load(['unit:id,code,block,zone', 'owner:id,name', 'curator:id,name']),
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
            'catalog_items' => ['nullable', 'array', 'max:30'],
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
            'catalog_items' => ['sometimes', 'array', 'max:30'],
            'catalog_items.*.id' => ['required_with:catalog_items'],
            'catalog_items.*.name' => ['required_with:catalog_items', 'string', 'max:120'],
            'catalog_items.*.category' => ['required_with:catalog_items', Rule::in(['kuliner', 'sembako', 'jasa', 'pinjam_alat', 'lainnya'])],
            'catalog_items.*.badge_type' => ['required_with:catalog_items', Rule::in(['ready', 'po', 'service', 'loan'])],
            'catalog_items.*.price' => ['required_with:catalog_items', 'numeric', 'min:0'],
            'catalog_items.*.unit_note' => ['nullable', 'string', 'max:100'],
            'catalog_items.*.delivery_tag' => ['nullable', 'string', 'max:100'],
            'catalog_items.*.description' => ['nullable', 'string', 'max:500'],
            'catalog_items.*.image_url' => ['nullable', 'string', 'max:255'],
            'catalog_items.*.is_available' => ['sometimes', 'boolean'],
        ]);

        // Bersihkan file foto item yang sudah tidak dipakai (dihapus atau diganti foto baru)
        if (array_key_exists('catalog_items', $validated)) {
            $oldUrls = collect($store->catalog_items ?? [])->pluck('image_url')->filter()->all();
            $newUrls = collect($validated['catalog_items'])->pluck('image_url')->filter()->all();
            $removedUrls = array_diff($oldUrls, $newUrls);
            $storeFolderPrefix = "umkm/stores/{$store->id}/items/";

            foreach ($removedUrls as $url) {
                $relativePath = ltrim((string) preg_replace('#^/storage/#', '', (string) $url), '/');
                if (str_starts_with($relativePath, $storeFolderPrefix)) {
                    Storage::disk('public')->delete($relativePath);
                }
            }
        }

        $store->update($validated);

        return response()->json([
            'message' => 'Perubahan lapak & etalase produk berhasil disimpan.',
            'data' => $store->fresh()->load('unit:id,code,block,zone'),
        ]);
    }

    public function uploadItemImage(Request $request, UmkmStore $store): JsonResponse
    {
        if ($store->owner_user_id !== $request->user()->id) {
            return response()->json(['message' => 'Anda tidak berhak mengunggah foto untuk lapak warga lain.'], 403);
        }

        $request->validate([
            'image' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'image.max' => 'Ukuran foto maksimal 5 MB.',
            'image.mimes' => 'Format foto harus JPG, PNG, atau WebP.',
        ]);

        $file = $request->file('image');
        $rawContent = @file_get_contents($file->getRealPath());
        $srcImage = $rawContent ? @imagecreatefromstring($rawContent) : false;

        if (! $srcImage) {
            return response()->json(['message' => 'File gambar tidak valid atau rusak.'], 422);
        }

        $srcW = imagesx($srcImage);
        $srcH = imagesy($srcImage);

        // Center-crop ke rasio 1:1 dan perkecil ke maksimal 800x800 px agar ringan & tajam di kartu etalase
        $cropSize = min($srcW, $srcH);
        $srcX = (int) floor(($srcW - $cropSize) / 2);
        $srcY = (int) floor(($srcH - $cropSize) / 2);
        $targetSize = min(800, $cropSize);

        $canvas = imagecreatetruecolor($targetSize, $targetSize);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);

        imagecopyresampled(
            $canvas,
            $srcImage,
            0,
            0,
            $srcX,
            $srcY,
            $targetSize,
            $targetSize,
            $cropSize,
            $cropSize
        );

        ob_start();
        imagewebp($canvas, null, 82);
        $webpBinary = ob_get_clean();

        imagedestroy($srcImage);
        imagedestroy($canvas);

        if (! $webpBinary) {
            return response()->json(['message' => 'Gagal memproses kompresi gambar di server.'], 500);
        }

        $filename = 'item-' . now()->format('YmdHis') . '-' . Str::lower(Str::random(8)) . '.webp';
        $relativePath = "umkm/stores/{$store->id}/items/{$filename}";

        Storage::disk('public')->put($relativePath, $webpBinary);

        return response()->json([
            'message' => 'Foto item berhasil diunggah.',
            'image_url' => Storage::url($relativePath),
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
            'message' => "Status lapak '{$store->store_name}' diperbarui.",
            'data' => $store,
        ]);
    }
}
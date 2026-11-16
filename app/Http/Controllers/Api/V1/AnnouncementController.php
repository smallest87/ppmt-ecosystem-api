<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AnnouncementController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $includeUnpublished = $request->boolean('all') && $user->canManageAnnouncements();

        $query = Announcement::query()
            ->with(['author.profile', 'author.governanceRoles'])
            ->orderByDesc('is_pinned')
            ->latest();

        if (! $includeUnpublished) {
            $query->where('is_published', true);
        }

        $items = $query->get()->map(function (Announcement $item) {
            $authorName = 'Pengurus Paguyuban PPMT';
            if ($item->author) {
                $displayName = $item->author->profile?->display_name ?: $item->author->name;
                $roleLabel = $item->author->governanceRoles->first()?->display_label;
                $authorName = $roleLabel ? "{$displayName} ({$roleLabel})" : $displayName;
            }

            return [
                'id' => $item->id,
                'title' => $item->title,
                'category_code' => $item->category,
                'category' => $item->category_label,
                'badgeClass' => $item->badge_class,
                'excerpt' => Str::limit(preg_replace('/\s+/', ' ', trim($item->body)), 165),
                'body' => $item->body,
                'is_pinned' => $item->is_pinned,
                'is_published' => $item->is_published,
                'date' => $item->created_at?->translatedFormat('d M Y'),
                'author' => $authorName,
            ];
        });

        return response()->json(['data' => $items]);
    }

    public function store(Request $request): JsonResponse
    {
        $actor = $request->user();

        if (! $actor->canManageAnnouncements()) {
            return response()->json([
                'message' => 'Akses ditolak. Hanya Sekretaris Paguyuban atau Ketua Paguyuban yang dapat menerbitkan pengumuman.',
            ], 403);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'category' => ['required', Rule::in(['penting', 'kerja_bakti', 'musyawarah', 'info'])],
            'body' => ['required', 'string', 'max:5000'],
            'is_pinned' => ['sometimes', 'boolean'],
            'is_published' => ['sometimes', 'boolean'],
        ]);

        $announcement = Announcement::create([
            'title' => trim($validated['title']),
            'category' => $validated['category'],
            'body' => trim($validated['body']),
            'is_pinned' => (bool) ($validated['is_pinned'] ?? false),
            'is_published' => (bool) ($validated['is_published'] ?? true),
            'created_by_user_id' => $actor->id,
        ]);

        return response()->json([
            'message' => $announcement->is_published
                ? 'Pengumuman resmi berhasil diterbitkan ke Beranda Warga.'
                : 'Draf pengumuman berhasil disimpan.',
            'data' => $announcement,
        ], 201);
    }

    public function update(Request $request, Announcement $announcement): JsonResponse
    {
        $actor = $request->user();

        if (! $actor->canManageAnnouncements()) {
            return response()->json([
                'message' => 'Akses ditolak. Anda tidak memiliki wewenang mengelola pengumuman paguyuban.',
            ], 403);
        }

        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:180'],
            'category' => ['sometimes', 'required', Rule::in(['penting', 'kerja_bakti', 'musyawarah', 'info'])],
            'body' => ['sometimes', 'required', 'string', 'max:5000'],
            'is_pinned' => ['sometimes', 'boolean'],
            'is_published' => ['sometimes', 'boolean'],
        ]);

        if (isset($validated['title'])) {
            $validated['title'] = trim($validated['title']);
        }
        if (isset($validated['body'])) {
            $validated['body'] = trim($validated['body']);
        }

        $announcement->update($validated);

        return response()->json([
            'message' => 'Pengumuman paguyuban berhasil diperbarui.',
            'data' => $announcement->fresh(),
        ]);
    }

    public function destroy(Request $request, Announcement $announcement): JsonResponse
    {
        $actor = $request->user();

        if (! $actor->canManageAnnouncements()) {
            return response()->json([
                'message' => 'Akses ditolak.',
            ], 403);
        }

        $announcement->delete();

        return response()->json([
            'message' => 'Pengumuman telah dihapus.',
        ]);
    }
}
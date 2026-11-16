<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\MeetingMinute;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class MeetingMinuteController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $canSeeInternal = $user->hasAdminAccess();

        $query = MeetingMinute::query()
            ->with(['author.profile', 'author.governanceRoles'])
            ->orderByDesc('meeting_date')
            ->latest();

        if (! $canSeeInternal) {
            $query->where('visibility', 'public_residents');
        }

        $items = $query->get()->map(function (MeetingMinute $item) {
            $authorName = 'Sekretariat Paguyuban PPMT';
            if ($item->author) {
                $displayName = $item->author->profile?->display_name ?: $item->author->name;
                $roleLabel = $item->author->governanceRoles->first()?->display_label;
                $authorName = $roleLabel ? "{$displayName} ({$roleLabel})" : $displayName;
            }

            return [
                'id' => $item->id,
                'title' => $item->title,
                'meeting_date' => $item->meeting_date?->format('Y-m-d'),
                'meeting_date_formatted' => $item->meeting_date?->translatedFormat('d M Y'),
                'location' => $item->location,
                'category_code' => $item->category,
                'category' => $item->category_label,
                'badgeClass' => $item->badge_class,
                'excerpt' => Str::limit(preg_replace('/\s+/', ' ', trim($item->decisions)), 170),
                'decisions' => $item->decisions,
                'visibility' => $item->visibility,
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
                'message' => 'Akses ditolak. Hanya Sekretaris Paguyuban atau Ketua Paguyuban yang dapat mencatat notulensi & tata tertib.',
            ], 403);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'meeting_date' => ['required', 'date'],
            'location' => ['nullable', 'string', 'max:120'],
            'category' => ['required', Rule::in(['musyawarah_warga', 'rapat_pengurus', 'tata_tertib'])],
            'decisions' => ['required', 'string', 'max:10000'],
            'visibility' => ['required', Rule::in(['public_residents', 'internal_pengurus'])],
        ]);

        $minute = MeetingMinute::create([
            'title' => trim($validated['title']),
            'meeting_date' => $validated['meeting_date'],
            'location' => ! empty($validated['location']) ? trim($validated['location']) : null,
            'category' => $validated['category'],
            'decisions' => trim($validated['decisions']),
            'visibility' => $validated['visibility'],
            'created_by_user_id' => $actor->id,
        ]);

        return response()->json([
            'message' => 'Notulensi rapat / tata tertib berhasil disimpan ke arsip paguyuban.',
            'data' => $minute,
        ], 201);
    }

    public function update(Request $request, MeetingMinute $meetingMinute): JsonResponse
    {
        $actor = $request->user();

        if (! $actor->canManageAnnouncements()) {
            return response()->json([
                'message' => 'Akses ditolak. Anda tidak memiliki wewenang mengubah arsip notulensi.',
            ], 403);
        }

        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:180'],
            'meeting_date' => ['sometimes', 'required', 'date'],
            'location' => ['nullable', 'string', 'max:120'],
            'category' => ['sometimes', 'required', Rule::in(['musyawarah_warga', 'rapat_pengurus', 'tata_tertib'])],
            'decisions' => ['sometimes', 'required', 'string', 'max:10000'],
            'visibility' => ['sometimes', 'required', Rule::in(['public_residents', 'internal_pengurus'])],
        ]);

        if (isset($validated['title'])) {
            $validated['title'] = trim($validated['title']);
        }
        if (array_key_exists('location', $validated)) {
            $validated['location'] = ! empty($validated['location']) ? trim($validated['location']) : null;
        }
        if (isset($validated['decisions'])) {
            $validated['decisions'] = trim($validated['decisions']);
        }

        $meetingMinute->update($validated);

        return response()->json([
            'message' => 'Arsip notulensi / tata tertib berhasil diperbarui.',
            'data' => $meetingMinute->fresh(),
        ]);
    }

    public function destroy(Request $request, MeetingMinute $meetingMinute): JsonResponse
    {
        $actor = $request->user();

        if (! $actor->canManageAnnouncements()) {
            return response()->json([
                'message' => 'Akses ditolak.',
            ], 403);
        }

        $meetingMinute->delete();

        return response()->json([
            'message' => 'Arsip notulensi telah dihapus.',
        ]);
    }
}
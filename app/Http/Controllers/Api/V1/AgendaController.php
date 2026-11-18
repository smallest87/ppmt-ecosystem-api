<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\EnvironmentAgenda;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AgendaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $includePast = $request->boolean('all') && $user->canManageAnnouncements();

        $query = EnvironmentAgenda::query()
            ->visibleTo($user, $includePast)
            ->with([
                'author:id,name',
                'author.profile:id,user_id,display_name',
                'invitedUsers:id,name',
                'invitedUsers.profile:id,user_id,display_name',
            ]);

        if ($includePast) {
            $query->orderByDesc('event_date')->latest();
        } else {
            $query->whereDate('event_date', '>=', now()->toDateString())
                ->orderBy('event_date')
                ->orderBy('id');
        }

        $dayMap = [
            'Sun' => 'MIN',
            'Mon' => 'SEN',
            'Tue' => 'SEL',
            'Wed' => 'RAB',
            'Thu' => 'KAM',
            'Fri' => 'JUM',
            'Sat' => 'SAB',
        ];

        $agendas = $query->limit(50)->get()->map(function (EnvironmentAgenda $agenda) use ($dayMap) {
            $engDay = $agenda->event_date?->format('D') ?? 'MIN';

            return [
                'id' => $agenda->id,
                'title' => $agenda->title,
                'category_code' => $agenda->category,
                'category' => $agenda->category_label,
                'badgeClass' => $agenda->badge_class,
                'event_date' => $agenda->event_date?->format('Y-m-d'),
                'event_date_formatted' => $agenda->event_date?->translatedFormat('d M Y'),
                'dayShort' => $dayMap[$engDay] ?? strtoupper($engDay),
                'dateNum' => $agenda->event_date?->format('d'),
                'monthShort' => $agenda->event_date?->translatedFormat('M'),
                'time' => $agenda->time_label,
                'location' => $agenda->location,
                'notes' => $agenda->notes,
                'visibility' => $agenda->visibility ?? 'public',
                'visibility_label' => $agenda->visibility_label,
                'target_blocks' => $agenda->target_blocks ?? [],
                'invited_user_ids' => $agenda->invitedUsers->pluck('id')->values()->all(),
                'invited_users' => $agenda->invitedUsers->map(fn ($u) => [
                    'id' => $u->id,
                    'name' => $u->profile?->display_name ?: $u->name,
                ])->values()->all(),
                'is_upcoming' => $agenda->event_date?->gte(now()->startOfDay()) ?? false,
                'author' => $agenda->author?->profile?->display_name ?: ($agenda->author?->name ?? 'Sekretariat PPMT'),
            ];
        });

        return response()->json(['data' => $agendas]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->canManageAnnouncements()) {
            return response()->json([
                'message' => 'Akses ditolak. Hanya Sekretaris Paguyuban atau Ketua Paguyuban yang dapat mengatur agenda lingkungan.',
            ], 403);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:180'],
            'category' => ['required', Rule::in(['kegiatan', 'kebersihan', 'keamanan', 'musyawarah', 'pemeliharaan'])],
            'event_date' => ['required', 'date'],
            'time_label' => ['required', 'string', 'max:80'],
            'location' => ['required', 'string', 'max:140'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'visibility' => ['sometimes', Rule::in(['public', 'block', 'invite_only'])],
            'target_blocks' => ['required_if:visibility,block', 'array'],
            'target_blocks.*' => ['string', Rule::in(['R', 'A', 'B', 'C', 'D', 'FASUM'])],
            'invited_user_ids' => ['required_if:visibility,invite_only', 'array'],
            'invited_user_ids.*' => ['integer', 'exists:users,id'],
        ], [
            'target_blocks.required_if' => 'Pilih minimal satu blok untuk agenda khusus blok.',
            'invited_user_ids.required_if' => 'Pilih minimal satu warga untuk agenda undangan khusus.',
        ]);

        $visibility = $validated['visibility'] ?? 'public';

        $agenda = EnvironmentAgenda::create([
            'title' => trim($validated['title']),
            'category' => $validated['category'],
            'event_date' => $validated['event_date'],
            'time_label' => trim($validated['time_label']),
            'location' => trim($validated['location']),
            'notes' => isset($validated['notes']) ? trim($validated['notes']) : null,
            'visibility' => $visibility,
            'target_blocks' => $visibility === 'block'
                ? array_values(array_unique($validated['target_blocks'] ?? []))
                : null,
            'created_by_user_id' => $user->id,
        ]);

        if ($visibility === 'invite_only') {
            $agenda->invitedUsers()->sync(array_values(array_unique($validated['invited_user_ids'] ?? [])));
        }

        return response()->json([
            'message' => 'Agenda & jadwal lingkungan berhasil diterbitkan.',
            'data' => $agenda,
        ], 201);
    }

    public function update(Request $request, EnvironmentAgenda $agenda): JsonResponse
    {
        $user = $request->user();

        if (! $user->canManageAnnouncements()) {
            return response()->json([
                'message' => 'Akses ditolak. Hanya Sekretaris Paguyuban atau Ketua Paguyuban yang dapat mengubah agenda.',
            ], 403);
        }

        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:180'],
            'category' => ['sometimes', 'required', Rule::in(['kegiatan', 'kebersihan', 'keamanan', 'musyawarah', 'pemeliharaan'])],
            'event_date' => ['sometimes', 'required', 'date'],
            'time_label' => ['sometimes', 'required', 'string', 'max:80'],
            'location' => ['sometimes', 'required', 'string', 'max:140'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'visibility' => ['sometimes', Rule::in(['public', 'block', 'invite_only'])],
            'target_blocks' => ['required_if:visibility,block', 'array'],
            'target_blocks.*' => ['string', Rule::in(['R', 'A', 'B', 'C', 'D', 'FASUM'])],
            'invited_user_ids' => ['required_if:visibility,invite_only', 'array'],
            'invited_user_ids.*' => ['integer', 'exists:users,id'],
        ], [
            'target_blocks.required_if' => 'Pilih minimal satu blok untuk agenda khusus blok.',
            'invited_user_ids.required_if' => 'Pilih minimal satu warga untuk agenda undangan khusus.',
        ]);

        $visibility = $validated['visibility'] ?? $agenda->visibility ?? 'public';

        if (array_key_exists('visibility', $validated)) {
            $validated['target_blocks'] = $visibility === 'block'
                ? array_values(array_unique($validated['target_blocks'] ?? []))
                : null;
        }

        $agenda->update($validated);

        if ($visibility === 'invite_only' && array_key_exists('invited_user_ids', $validated)) {
            $agenda->invitedUsers()->sync(array_values(array_unique($validated['invited_user_ids'] ?? [])));
        } elseif ($visibility !== 'invite_only') {
            $agenda->invitedUsers()->detach();
        }

        return response()->json([
            'message' => 'Agenda lingkungan berhasil diperbarui.',
        ]);
    }

    public function destroy(Request $request, EnvironmentAgenda $agenda): JsonResponse
    {
        $user = $request->user();

        if (! $user->canManageAnnouncements()) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $agenda->delete();

        return response()->json([
            'message' => 'Agenda lingkungan telah dihapus.',
        ]);
    }
}
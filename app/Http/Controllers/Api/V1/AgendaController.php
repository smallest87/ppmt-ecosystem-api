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
            ->with(['author:id,name', 'author.profile:id,user_id,display_name']);

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
        ]);

        $agenda = EnvironmentAgenda::create([
            'title' => trim($validated['title']),
            'category' => $validated['category'],
            'event_date' => $validated['event_date'],
            'time_label' => trim($validated['time_label']),
            'location' => trim($validated['location']),
            'notes' => isset($validated['notes']) ? trim($validated['notes']) : null,
            'created_by_user_id' => $user->id,
        ]);

        return response()->json([
            'message' => 'Agenda & jadwal lingkungan berhasil diterbitkan ke Beranda Warga.',
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
        ]);

        $agenda->update($validated);

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
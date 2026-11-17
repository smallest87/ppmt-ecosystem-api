<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ResidentProfile;
use App\Models\Unit;
use App\Models\WhatsappGroupContact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use SplFileObject;

class WhatsappContactReconciliationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        if (! $request->user()?->isSystemAdmin()) {
            return response()->json(['message' => 'Akses khusus Admin Sistem.'], 403);
        }

        $contacts = WhatsappGroupContact::with([
            'matchedUser:id,name',
            'matchedUser.profile:user_id,phone,username,is_verified',
            'matchedUnit:id,code,zone',
        ])
            ->orderByRaw("CASE reconciliation_status
                WHEN 'unmatched' THEN 1
                WHEN 'matched_auto' THEN 2
                WHEN 'matched_manual' THEN 3
                ELSE 4 END")
            ->orderBy('wa_display_name')
            ->get()
            ->map(fn (WhatsappGroupContact $c) => $this->transformContact($c));

        $stats = [
            'total' => $contacts->count(),
            'both_groups' => $contacts->filter(fn ($c) => count($c['groups']) >= 2)->count(),
            'matched' => $contacts->filter(fn ($c) => in_array($c['reconciliation_status'], ['matched_auto', 'matched_manual'], true))->count(),
            'unmatched' => $contacts->where('reconciliation_status', 'unmatched')->count(),
            'ignored' => $contacts->where('reconciliation_status', 'ignored')->count(),
        ];

        return response()->json([
            'data' => [
                'stats' => $stats,
                'contacts' => $contacts->values(),
            ],
        ]);
    }

    public function importCsv(Request $request): JsonResponse
    {
        if (! $request->user()?->isSystemAdmin()) {
            return response()->json(['message' => 'Akses khusus Admin Sistem.'], 403);
        }

        $validated = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
            'group_name' => ['required', 'string', 'max:80'],
        ]);

        $defaultGroup = trim($validated['group_name']);
        $csv = new SplFileObject($request->file('file')->getRealPath(), 'r');
        $csv->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::READ_AHEAD);

        $headers = $csv->fgetcsv();
        if (! is_array($headers) || empty($headers)) {
            return response()->json(['message' => 'File CSV kosong atau format tidak valid.'], 422);
        }

        $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $headers[0]);
        $headers = array_map(fn ($h) => trim((string) $h), $headers);
        $headerMap = array_flip($headers);

        $phoneValueIndexes = [];
        foreach ($headers as $idx => $colName) {
            if (preg_match('/^Phone\s+\d+\s+-\s+Value$/i', $colName)) {
                $phoneValueIndexes[] = $idx;
            }
        }

        if (empty($phoneValueIndexes)) {
            return response()->json([
                'message' => 'Kolom nomor telepon Google Contacts (Phone 1 - Value) tidak ditemukan pada CSV.',
            ], 422);
        }

        $parsedItems = [];
        while (! $csv->eof()) {
            $row = $csv->fgetcsv();
            if (! is_array($row) || (count($row) === 1 && $row[0] === null)) {
                continue;
            }

            $firstName = trim((string) ($row[$headerMap['First Name'] ?? -1] ?? ''));
            $middleName = trim((string) ($row[$headerMap['Middle Name'] ?? -1] ?? ''));
            $lastName = trim((string) ($row[$headerMap['Last Name'] ?? -1] ?? ''));
            $rawLabels = trim((string) ($row[$headerMap['Labels'] ?? -1] ?? ''));

            $displayName = preg_replace('/\s+/', ' ', trim("{$firstName} {$middleName} {$lastName}"));

            $detectedGroups = [$defaultGroup];
            if (stripos($rawLabels, 'GRUP WARGA') !== false) {
                $detectedGroups[] = 'Grup Warga PPMT';
            }
            if (stripos($rawLabels, 'GRUP PESONA') !== false) {
                $detectedGroups[] = 'Grup Pesona Mandiri';
            }
            $detectedGroups = array_values(array_unique($detectedGroups));

            foreach ($phoneValueIndexes as $pIdx) {
                $rawPhoneCell = (string) ($row[$pIdx] ?? '');
                foreach (explode(':::', $rawPhoneCell) as $part) {
                    $norm = $this->normalizePhone($part);
                    if (! $norm) {
                        continue;
                    }

                    if (! isset($parsedItems[$norm])) {
                        $parsedItems[$norm] = [
                            'wa_display_name' => $displayName,
                            'groups' => $detectedGroups,
                        ];
                    } else {
                        $parsedItems[$norm]['groups'] = array_values(array_unique(
                            array_merge($parsedItems[$norm]['groups'], $detectedGroups)
                        ));
                        $parsedItems[$norm]['wa_display_name'] = (string) $this->pickBetterDisplayName(
                            $parsedItems[$norm]['wa_display_name'],
                            $displayName
                        );
                    }
                }
            }
        }

        $importedCount = 0;
        $mergedCount = 0;

        DB::transaction(function () use ($parsedItems, &$importedCount, &$mergedCount) {
            $existingByPhone = WhatsappGroupContact::whereIn('phone', array_keys($parsedItems))
                ->get()
                ->keyBy('phone');

            foreach ($parsedItems as $phone => $item) {
                $existing = $existingByPhone->get($phone);

                if ($existing) {
                    $mergedGroups = array_values(array_unique(array_merge($existing->groups ?? [], $item['groups'])));
                    $bestName = $this->pickBetterDisplayName($existing->wa_display_name, $item['wa_display_name']);

                    $existing->update([
                        'wa_display_name' => $bestName,
                        'groups' => $mergedGroups,
                        'source_note' => 'data dari grup whatsapp',
                    ]);
                    $mergedCount++;
                } else {
                    WhatsappGroupContact::create([
                        'phone' => $phone,
                        'wa_display_name' => $item['wa_display_name'] ?: null,
                        'groups' => $item['groups'],
                        'source_note' => 'data dari grup whatsapp',
                        'reconciliation_status' => 'unmatched',
                    ]);
                    $importedCount++;
                }
            }
        });

        $this->runAutoReconciliation();

        return response()->json([
            'message' => "Impor selesai: {$importedCount} nomor baru ditambahkan, {$mergedCount} nomor digabungkan & dipadupadankan otomatis.",
        ]);
    }

    public function reconcileAll(Request $request): JsonResponse
    {
        if (! $request->user()?->isSystemAdmin()) {
            return response()->json(['message' => 'Akses khusus Admin Sistem.'], 403);
        }

        $matchedTotal = $this->runAutoReconciliation();

        return response()->json([
            'message' => "Pemadupadanan otomatis selesai. {$matchedTotal} kontak terhubung dengan akun warga PPMT.",
        ]);
    }

    public function update(Request $request, WhatsappGroupContact $contact): JsonResponse
    {
        if (! $request->user()?->isSystemAdmin()) {
            return response()->json(['message' => 'Akses khusus Admin Sistem.'], 403);
        }

        $validated = $request->validate([
            'wa_display_name' => ['sometimes', 'nullable', 'string', 'max:180'],
            'manual_labels' => ['sometimes', 'array'],
            'manual_labels.*' => ['string', 'max:50'],
            'matched_user_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'matched_unit_id' => ['sometimes', 'nullable', 'integer', 'exists:units,id'],
            'reconciliation_status' => ['sometimes', 'in:unmatched,matched_auto,matched_manual,ignored'],
            'admin_notes' => ['sometimes', 'nullable', 'string', 'max:500'],
        ]);

        if (array_key_exists('manual_labels', $validated)) {
            $validated['manual_labels'] = array_values(array_unique(array_filter(
                array_map(fn ($lbl) => trim((string) $lbl), $validated['manual_labels'])
            )));
        }

        if ((! empty($validated['matched_user_id']) || ! empty($validated['matched_unit_id']))
            && ! isset($validated['reconciliation_status'])) {
            $validated['reconciliation_status'] = 'matched_manual';
        }

        $contact->update($validated);
        $this->refreshSingleAutoLabels($contact->fresh());

        return response()->json([
            'message' => 'Data & label kontak WhatsApp berhasil diperbarui.',
            'data' => $this->transformContact($contact->fresh(['matchedUser.profile', 'matchedUnit'])),
        ]);
    }

    private function runAutoReconciliation(): int
    {
        $profiles = ResidentProfile::with(['user.unitMemberships' => fn ($q) => $q->orderByDesc('is_approved')])
            ->whereNotNull('phone')
            ->get();

        $profileByNormPhone = [];
        foreach ($profiles as $prof) {
            $norm = $this->normalizePhone($prof->phone);
            if ($norm) {
                $profileByNormPhone[$norm] = $prof;
            }
        }

        $unitsByCode = Unit::all()->keyBy('code');
        $matchedCounter = 0;

        DB::transaction(function () use ($profileByNormPhone, $unitsByCode, &$matchedCounter) {
            $contacts = WhatsappGroupContact::all();
            foreach ($contacts as $contact) {
                $detectedCode = $this->extractKavlingCode($contact->wa_display_name);
                $matchedProfile = $profileByNormPhone[$contact->phone] ?? null;

                if ($matchedProfile && $matchedProfile->user) {
                    $membership = $matchedProfile->user->unitMemberships->first();
                    $contact->matched_user_id = $matchedProfile->user_id;
                    $contact->matched_unit_id = $membership?->unit_id
                        ?? ($detectedCode && isset($unitsByCode[$detectedCode]) ? $unitsByCode[$detectedCode]->id : $contact->matched_unit_id);

                    if ($contact->reconciliation_status !== 'ignored') {
                        $contact->reconciliation_status = 'matched_auto';
                    }
                    $matchedCounter++;
                } elseif (! $contact->matched_unit_id && $detectedCode && isset($unitsByCode[$detectedCode])) {
                    $contact->matched_unit_id = $unitsByCode[$detectedCode]->id;
                }

                $contact->detected_unit_code = $detectedCode;
                $contact->auto_labels = $this->buildAutoLabels($contact, $matchedProfile, $detectedCode);
                $contact->save();
            }
        });

        return $matchedCounter;
    }

    private function refreshSingleAutoLabels(WhatsappGroupContact $contact): void
    {
        $matchedProfile = $contact->matched_user_id
            ? ResidentProfile::where('user_id', $contact->matched_user_id)->first()
            : null;
        $detectedCode = $this->extractKavlingCode($contact->wa_display_name);

        $contact->detected_unit_code = $detectedCode;
        $contact->auto_labels = $this->buildAutoLabels($contact, $matchedProfile, $detectedCode);
        $contact->save();
    }

    private function buildAutoLabels(WhatsappGroupContact $contact, ?ResidentProfile $profile, ?string $detectedCode): array
    {
        $labels = [];
        $groups = $contact->groups ?? [];

        if (count($groups) >= 2) {
            $labels[] = 'Ada di 2 Grup WA';
        } elseif (count($groups) === 1) {
            $labels[] = 'Hanya ' . $groups[0];
        }

        if ($profile) {
            $labels[] = $profile->is_verified
                ? 'Akun Portal Terverifikasi'
                : 'Akun Portal (Menunggu Verifikasi)';
        } else {
            $labels[] = 'Belum Terdaftar di Portal';
        }

        if ($detectedCode) {
            $labels[] = 'Saran Kavling: ' . $detectedCode;
        }

        $upperName = strtoupper((string) $contact->wa_display_name);
        if (preg_match('/\b(DEVELOPER|VENDOR|MARKETING|PESONA MANDIRI BERSAMA|PDAM)\b/', $upperName)) {
            $labels[] = 'Terindikasi Non-Penghuni / Vendor';
        }

        return $labels;
    }

    private function normalizePhone(?string $raw): ?string
    {
        if (! $raw) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $raw);
        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '62')) {
            $digits = '0' . substr($digits, 2);
        } elseif (str_starts_with($digits, '8')) {
            $digits = '0' . $digits;
        }

        return strlen($digits) >= 8 ? $digits : null;
    }

    private function extractKavlingCode(?string $name): ?string
    {
        if (! $name) {
            return null;
        }

        if (preg_match('/\b([A-D])-?0*([1-9][0-9]*[A-B]?)\b/i', $name, $m)) {
            return strtoupper($m[1]) . '-' . strtoupper($m[2]);
        }

        return null;
    }

    private function pickBetterDisplayName(?string $current, string $incoming): ?string
    {
        if ($incoming === '') {
            return $current;
        }
        if (! $current) {
            return $incoming;
        }

        $currentIsGeneric = (bool) preg_match('/^PPMT\s+\d+$/i', $current);
        $incomingIsGeneric = (bool) preg_match('/^PPMT\s+\d+$/i', $incoming);

        if ($currentIsGeneric && ! $incomingIsGeneric) {
            return $incoming;
        }

        return mb_strlen($incoming) > mb_strlen($current) ? $incoming : $current;
    }

    private function transformContact(WhatsappGroupContact $c): array
    {
        return [
            'id' => $c->id,
            'phone' => $c->phone,
            'wa_display_name' => $c->wa_display_name,
            'groups' => $c->groups ?? [],
            'auto_labels' => $c->auto_labels ?? [],
            'manual_labels' => $c->manual_labels ?? [],
            'source_note' => $c->source_note,
            'detected_unit_code' => $c->detected_unit_code,
            'reconciliation_status' => $c->reconciliation_status,
            'admin_notes' => $c->admin_notes,
            'matched_user' => $c->matchedUser ? [
                'id' => $c->matchedUser->id,
                'name' => $c->matchedUser->name,
                'is_verified' => (bool) $c->matchedUser->profile?->is_verified,
            ] : null,
            'matched_unit' => $c->matchedUnit ? [
                'id' => $c->matchedUnit->id,
                'code' => $c->matchedUnit->code,
                'zone' => $c->matchedUnit->zone,
            ] : null,
        ];
    }
}
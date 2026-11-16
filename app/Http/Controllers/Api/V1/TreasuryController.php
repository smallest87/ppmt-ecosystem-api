<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\CashLedgerEntry;
use App\Models\DuesPayment;
use App\Models\Unit;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TreasuryController extends Controller
{
    public const GARBAGE_DUES_AMOUNT = 20000;

    public function publicReport(Request $request): JsonResponse
    {
        $user = $request->user()->load('unitMemberships.unit');

        $periodMonth = $request->query('month', now()->format('Y-m'));
        if (! preg_match('/^\d{4}-\d{2}$/', $periodMonth)) {
            $periodMonth = now()->format('Y-m');
        }

        $periodDate = Carbon::createFromFormat('Y-m-d', $periodMonth . '-01');
        $periodLabel = $periodDate->translatedFormat('F Y');
        $dueDateFormatted = $periodDate->translatedFormat('01 M Y');

        // 1. Saldo Kas Keseluruhan (All-Time)
        $totalIncomeAll = (int) CashLedgerEntry::where('type', 'income')->sum('amount');
        $totalExpenseAll = (int) CashLedgerEntry::where('type', 'expense')->sum('amount');
        $currentBalance = $totalIncomeAll - $totalExpenseAll;

        // 2. Arus Kas Bulan Terpilih
        $monthStart = $periodDate->copy()->startOfMonth()->toDateString();
        $monthEnd = $periodDate->copy()->endOfMonth()->toDateString();

        $monthIncome = (int) CashLedgerEntry::where('type', 'income')
            ->whereBetween('entry_date', [$monthStart, $monthEnd])
            ->sum('amount');

        $monthExpense = (int) CashLedgerEntry::where('type', 'expense')
            ->whereBetween('entry_date', [$monthStart, $monthEnd])
            ->sum('amount');

        // 3. Rekapitulasi Pengeluaran & Pemasukan Bulan Terpilih per Pos Kategori
        $categoryLabels = [
            'iuran_sampah' => 'Iuran Sampah Warga',
            'pengangkutan_sampah' => 'Operasional Angkut Sampah',
            'keamanan' => 'Keamanan & Ronda',
            'listrik_pju' => 'Listrik PJU & Pos',
            'perawatan_fasum' => 'Perawatan Fasum & Drainase',
            'sumbangan' => 'Sumbangan / Dana Kegiatan',
            'lainnya' => 'Operasional Lainnya',
        ];

        $breakdownRows = CashLedgerEntry::query()
            ->select('type', 'category', DB::raw('SUM(amount) as total'))
            ->whereBetween('entry_date', [$monthStart, $monthEnd])
            ->groupBy('type', 'category')
            ->get();

        $incomeByCategory = $breakdownRows
            ->where('type', 'income')
            ->map(fn ($r) => [
                'category' => $r->category,
                'label' => $categoryLabels[$r->category] ?? $r->category,
                'total' => (int) $r->total,
            ])
            ->values();

        $expenseByCategory = $breakdownRows
            ->where('type', 'expense')
            ->map(fn ($r) => [
                'category' => $r->category,
                'label' => $categoryLabels[$r->category] ?? $r->category,
                'total' => (int) $r->total,
            ])
            ->values();

        // 4. Progres Agregat Iuran Sampah Lingkungan (Tanpa Mengekspos Tunggakan Tetangga)
        $billableUnitsCount = Unit::query()
            ->where('unit_type', '!=', 'fasum')
            ->whereIn('occupancy_status', ['occupied', 'rented'])
            ->count();

        $paidUnitsCount = DuesPayment::query()
            ->where('dues_type', 'iuran_sampah')
            ->where('period_month', $periodMonth)
            ->where('status', 'paid')
            ->count();

        // 5. Status Iuran Sampah Kavling Milik/Hunian Warga yang Login (Bulan Terpilih + 3 Bulan Terakhir)
        $recentMonths = collect([0, 1, 2])->map(function (int $offset) use ($periodDate) {
            $d = $periodDate->copy()->subMonths($offset);
            return [
                'month' => $d->format('Y-m'),
                'label' => $d->translatedFormat('F Y'),
                'due_date' => $d->translatedFormat('01 M Y'),
            ];
        });

        $approvedMemberships = $user->unitMemberships
            ->where('is_approved', true)
            ->unique('unit_id')
            ->values();

        $userUnitIds = $approvedMemberships->pluck('unit_id')->all();
        $userPayments = DuesPayment::query()
            ->whereIn('unit_id', $userUnitIds)
            ->where('dues_type', 'iuran_sampah')
            ->whereIn('period_month', $recentMonths->pluck('month')->all())
            ->get()
            ->groupBy(fn ($p) => $p->unit_id . '_' . $p->period_month);

        $myUnitsDues = $approvedMemberships->map(function ($membership) use ($recentMonths, $userPayments, $periodMonth) {
            $unit = $membership->unit;
            $history = $recentMonths->map(function ($m) use ($unit, $userPayments) {
                $payment = $userPayments->get($unit->id . '_' . $m['month'])?->first();
                $status = $payment ? $payment->status : 'unpaid';

                return [
                    'period_month' => $m['month'],
                    'period_label' => $m['label'],
                    'due_date' => $m['due_date'],
                    'status' => $status, // paid | exempt | unpaid
                    'amount' => $payment ? (int) $payment->amount : self::GARBAGE_DUES_AMOUNT,
                    'payment_method' => $payment?->payment_method,
                    'paid_at' => $payment?->paid_at?->format('d M Y'),
                ];
            })->values();

            $currentMonthStatus = $history->firstWhere('period_month', $periodMonth) ?? $history->first();

            return [
                'unit_id' => $unit->id,
                'unit_code' => $unit->code,
                'zone' => $unit->zone,
                'current' => $currentMonthStatus,
                'history' => $history,
            ];
        })->values();

        // 6. 20 Mutasi Kas Terbaru pada Bulan Terpilih untuk Transparansi Warga
        $recentTransactions = CashLedgerEntry::query()
            ->whereBetween('entry_date', [$monthStart, $monthEnd])
            ->orderByDesc('entry_date')
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn (CashLedgerEntry $entry) => [
                'id' => $entry->id,
                'entry_date_formatted' => $entry->entry_date?->translatedFormat('d M Y'),
                'type' => $entry->type,
                'category_label' => $entry->category_label,
                'amount' => (int) $entry->amount,
                'payment_method' => $entry->payment_method,
                'description' => $entry->description,
            ]);

        return response()->json([
            'data' => [
                'period_month' => $periodMonth,
                'period_label' => $periodLabel,
                'due_date_formatted' => $dueDateFormatted,
                'standard_amount' => self::GARBAGE_DUES_AMOUNT,
                'summary' => [
                    'current_balance' => $currentBalance,
                    'month_income' => $monthIncome,
                    'month_expense' => $monthExpense,
                    'billable_units' => max($billableUnitsCount, $paidUnitsCount),
                    'paid_units' => $paidUnitsCount,
                ],
                'income_by_category' => $incomeByCategory,
                'expense_by_category' => $expenseByCategory,
                'my_units_dues' => $myUnitsDues,
                'recent_transactions' => $recentTransactions,
            ],
        ]);
    }

    public function overview(Request $request): JsonResponse
    {
        $actor = $request->user();

        if (! $actor->canViewTreasury()) {
            return response()->json([
                'message' => 'Akses ditolak. Hanya Bendahara Paguyuban atau Ketua Paguyuban yang dapat mengakses pembukuan kas.',
            ], 403);
        }

        $periodMonth = $request->query('month', now()->format('Y-m'));
        if (! preg_match('/^\d{4}-\d{2}$/', $periodMonth)) {
            $periodMonth = now()->format('Y-m');
        }

        $periodDate = Carbon::createFromFormat('Y-m-d', $periodMonth . '-01');
        $periodLabel = $periodDate->translatedFormat('F Y');
        $dueDateFormatted = $periodDate->translatedFormat('01 M Y');

        $totalIncomeAll = (int) CashLedgerEntry::where('type', 'income')->sum('amount');
        $totalExpenseAll = (int) CashLedgerEntry::where('type', 'expense')->sum('amount');
        $currentBalance = $totalIncomeAll - $totalExpenseAll;

        $monthStart = $periodDate->copy()->startOfMonth()->toDateString();
        $monthEnd = $periodDate->copy()->endOfMonth()->toDateString();

        $monthIncome = (int) CashLedgerEntry::where('type', 'income')
            ->whereBetween('entry_date', [$monthStart, $monthEnd])
            ->sum('amount');

        $monthExpense = (int) CashLedgerEntry::where('type', 'expense')
            ->whereBetween('entry_date', [$monthStart, $monthEnd])
            ->sum('amount');

        $units = Unit::query()
            ->where('unit_type', '!=', 'fasum')
            ->with([
                'memberships' => fn ($q) => $q->where('is_approved', true)->orderByDesc('is_primary_contact'),
                'memberships.user:id,name',
                'memberships.user.profile:id,user_id,display_name',
            ])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $paymentsByUnit = DuesPayment::query()
            ->where('dues_type', 'iuran_sampah')
            ->where('period_month', $periodMonth)
            ->get()
            ->keyBy('unit_id');

        $billableUnitsCount = 0;
        $paidUnitsCount = 0;
        $exemptUnitsCount = 0;
        $unpaidUnitsCount = 0;
        $collectedDuesAmount = 0;

        $duesRows = $units->map(function (Unit $unit) use (
            $paymentsByUnit,
            &$billableUnitsCount,
            &$paidUnitsCount,
            &$exemptUnitsCount,
            &$unpaidUnitsCount,
            &$collectedDuesAmount
        ) {
            $payment = $paymentsByUnit->get($unit->id);
            $isOccupied = in_array($unit->occupancy_status, ['occupied', 'rented'], true);

            $primaryMember = $unit->memberships->first();
            $residentName = $primaryMember
                ? ($primaryMember->user?->profile?->display_name ?: $primaryMember->user?->name)
                : null;

            if ($payment) {
                $status = $payment->status;
            } elseif ($isOccupied) {
                $status = 'unpaid';
            } else {
                $status = 'vacant';
            }

            if ($status === 'paid') {
                $billableUnitsCount++;
                $paidUnitsCount++;
                $collectedDuesAmount += (int) $payment->amount;
            } elseif ($status === 'unpaid') {
                $billableUnitsCount++;
                $unpaidUnitsCount++;
            } elseif ($status === 'exempt') {
                $exemptUnitsCount++;
            }

            return [
                'unit_id' => $unit->id,
                'code' => $unit->code,
                'block' => $unit->block,
                'zone' => $unit->zone,
                'occupancy_status' => $unit->occupancy_status,
                'resident_name' => $residentName,
                'status' => $status,
                'amount' => $payment ? (int) $payment->amount : self::GARBAGE_DUES_AMOUNT,
                'payment_method' => $payment?->payment_method,
                'paid_at' => $payment?->paid_at?->format('d M Y'),
                'notes' => $payment?->notes,
            ];
        })->values();

        $ledgerEntries = CashLedgerEntry::query()
            ->with(['unit:id,code', 'recorder:id,name'])
            ->orderByDesc('entry_date')
            ->latest()
            ->limit(40)
            ->get()
            ->map(fn (CashLedgerEntry $entry) => [
                'id' => $entry->id,
                'entry_date' => $entry->entry_date?->format('Y-m-d'),
                'entry_date_formatted' => $entry->entry_date?->translatedFormat('d M Y'),
                'type' => $entry->type,
                'category' => $entry->category,
                'category_label' => $entry->category_label,
                'amount' => (int) $entry->amount,
                'payment_method' => $entry->payment_method,
                'description' => $entry->description,
                'unit_code' => $entry->unit?->code,
                'recorded_by' => $entry->recorder?->name ?? 'Bendahara',
                'is_auto_dues' => $entry->category === 'iuran_sampah' && $entry->unit_id !== null,
            ]);

        return response()->json([
            'data' => [
                'period_month' => $periodMonth,
                'period_label' => $periodLabel,
                'due_date_formatted' => $dueDateFormatted,
                'standard_amount' => self::GARBAGE_DUES_AMOUNT,
                'summary' => [
                    'current_balance' => $currentBalance,
                    'month_income' => $monthIncome,
                    'month_expense' => $monthExpense,
                    'billable_units' => $billableUnitsCount,
                    'paid_units' => $paidUnitsCount,
                    'unpaid_units' => $unpaidUnitsCount,
                    'exempt_units' => $exemptUnitsCount,
                    'collected_dues_amount' => $collectedDuesAmount,
                    'expected_dues_amount' => $billableUnitsCount * self::GARBAGE_DUES_AMOUNT,
                ],
                'dues_units' => $duesRows,
                'ledger_entries' => $ledgerEntries,
            ],
        ]);
    }

    public function setDuesStatus(Request $request): JsonResponse
    {
        $actor = $request->user();

        if (! $actor->canManageTreasury()) {
            return response()->json([
                'message' => 'Akses ditolak. Hanya Bendahara Paguyuban yang berwenang mencatat pelunasan iuran.',
            ], 403);
        }

        $validated = $request->validate([
            'unit_id' => ['required', 'integer', 'exists:units,id'],
            'period_month' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'action' => ['required', Rule::in(['paid', 'exempt', 'unpaid'])],
            'payment_method' => ['nullable', Rule::in(['cash', 'transfer'])],
            'notes' => ['nullable', 'string', 'max:200'],
        ]);

        $unit = Unit::findOrFail($validated['unit_id']);
        $periodLabel = Carbon::createFromFormat('Y-m-d', $validated['period_month'] . '-01')->translatedFormat('F Y');

        DB::transaction(function () use ($validated, $unit, $actor, $periodLabel) {
            $existing = DuesPayment::where('unit_id', $unit->id)
                ->where('dues_type', 'iuran_sampah')
                ->where('period_month', $validated['period_month'])
                ->first();

            if ($validated['action'] === 'unpaid') {
                if ($existing) {
                    if ($existing->cash_ledger_entry_id) {
                        CashLedgerEntry::where('id', $existing->cash_ledger_entry_id)->delete();
                    }
                    $existing->delete();
                }
                return;
            }

            if ($validated['action'] === 'exempt') {
                if ($existing && $existing->cash_ledger_entry_id) {
                    CashLedgerEntry::where('id', $existing->cash_ledger_entry_id)->delete();
                }

                DuesPayment::updateOrCreate(
                    [
                        'unit_id' => $unit->id,
                        'dues_type' => 'iuran_sampah',
                        'period_month' => $validated['period_month'],
                    ],
                    [
                        'amount' => 0,
                        'status' => 'exempt',
                        'payment_method' => null,
                        'paid_at' => null,
                        'notes' => $validated['notes'] ?? 'Dibebaskan / Rumah Belum Dihuni',
                        'cash_ledger_entry_id' => null,
                        'recorded_by_user_id' => $actor->id,
                    ]
                );
                return;
            }

            $method = $validated['payment_method'] ?? 'cash';
            $methodText = $method === 'transfer' ? 'Transfer' : 'Tunai';

            $ledgerEntry = $existing?->ledgerEntry;
            if ($ledgerEntry) {
                $ledgerEntry->update([
                    'entry_date' => now()->toDateString(),
                    'amount' => self::GARBAGE_DUES_AMOUNT,
                    'payment_method' => $method,
                    'description' => "Iuran Sampah Kavling {$unit->code} — {$periodLabel} ({$methodText})",
                    'recorded_by_user_id' => $actor->id,
                ]);
            } else {
                $ledgerEntry = CashLedgerEntry::create([
                    'entry_date' => now()->toDateString(),
                    'type' => 'income',
                    'category' => 'iuran_sampah',
                    'amount' => self::GARBAGE_DUES_AMOUNT,
                    'payment_method' => $method,
                    'description' => "Iuran Sampah Kavling {$unit->code} — {$periodLabel} ({$methodText})",
                    'unit_id' => $unit->id,
                    'recorded_by_user_id' => $actor->id,
                ]);
            }

            DuesPayment::updateOrCreate(
                [
                    'unit_id' => $unit->id,
                    'dues_type' => 'iuran_sampah',
                    'period_month' => $validated['period_month'],
                ],
                [
                    'amount' => self::GARBAGE_DUES_AMOUNT,
                    'status' => 'paid',
                    'payment_method' => $method,
                    'paid_at' => now()->toDateString(),
                    'notes' => $validated['notes'] ?? null,
                    'cash_ledger_entry_id' => $ledgerEntry->id,
                    'recorded_by_user_id' => $actor->id,
                ]
            );
        });

        $msg = match ($validated['action']) {
            'paid' => "Iuran sampah Kavling {$unit->code} ({$periodLabel}) dicatat LUNAS & otomatis masuk Buku Kas.",
            'exempt' => "Kavling {$unit->code} ditandai bebas iuran sampah untuk {$periodLabel}.",
            default => "Status lunas Kavling {$unit->code} ({$periodLabel}) dibatalkan.",
        };

        return response()->json(['message' => $msg]);
    }

    public function storeLedger(Request $request): JsonResponse
    {
        $actor = $request->user();

        if (! $actor->canManageTreasury()) {
            return response()->json([
                'message' => 'Akses ditolak. Hanya Bendahara Paguyuban yang dapat mencatat mutasi kas.',
            ], 403);
        }

        $validated = $request->validate([
            'entry_date' => ['required', 'date'],
            'type' => ['required', Rule::in(['income', 'expense'])],
            'category' => ['required', Rule::in([
                'pengangkutan_sampah',
                'keamanan',
                'listrik_pju',
                'perawatan_fasum',
                'sumbangan',
                'lainnya',
            ])],
            'amount' => ['required', 'integer', 'min:500', 'max:500000000'],
            'payment_method' => ['required', Rule::in(['cash', 'transfer'])],
            'description' => ['required', 'string', 'max:255'],
        ]);

        $entry = CashLedgerEntry::create([
            'entry_date' => $validated['entry_date'],
            'type' => $validated['type'],
            'category' => $validated['category'],
            'amount' => $validated['amount'],
            'payment_method' => $validated['payment_method'],
            'description' => trim($validated['description']),
            'recorded_by_user_id' => $actor->id,
        ]);

        return response()->json([
            'message' => ($validated['type'] === 'income' ? 'Pemasukan kas' : 'Pengeluaran kas') . ' berhasil dicatat ke Buku Kas Umum.',
            'data' => $entry,
        ], 201);
    }

    public function destroyLedger(Request $request, CashLedgerEntry $entry): JsonResponse
    {
        $actor = $request->user();

        if (! $actor->canManageTreasury()) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        if ($entry->category === 'iuran_sampah' && $entry->unit_id !== null) {
            return response()->json([
                'message' => 'Catatan kas otomatis dari iuran sampah hanya dapat dibatalkan melalui Kartu Iuran Sampah.',
            ], 422);
        }

        $entry->delete();

        return response()->json([
            'message' => 'Catatan mutasi kas telah dihapus.',
        ]);
    }
}
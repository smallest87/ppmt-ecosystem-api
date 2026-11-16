<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DuesPayment extends Model
{
    protected $fillable = [
        'unit_id',
        'dues_type',
        'period_month',
        'amount',
        'status',
        'payment_method',
        'paid_at',
        'notes',
        'cash_ledger_entry_id',
        'recorded_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'paid_at' => 'date',
        ];
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function ledgerEntry(): BelongsTo
    {
        return $this->belongsTo(CashLedgerEntry::class, 'cash_ledger_entry_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }
}
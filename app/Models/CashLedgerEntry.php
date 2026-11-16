<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashLedgerEntry extends Model
{
    protected $fillable = [
        'entry_date',
        'type',
        'category',
        'amount',
        'payment_method',
        'description',
        'unit_id',
        'recorded_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'amount' => 'integer',
        ];
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by_user_id');
    }

    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'iuran_sampah' => 'Iuran Sampah Warga',
            'pengangkutan_sampah' => 'Operasional Angkut Sampah',
            'keamanan' => 'Keamanan & Ronda',
            'listrik_pju' => 'Listrik PJU & Pos',
            'perawatan_fasum' => 'Perawatan Fasum & Drainase',
            'sumbangan' => 'Sumbangan / Dana Kegiatan',
            default => 'Operasional Lainnya',
        };
    }
}
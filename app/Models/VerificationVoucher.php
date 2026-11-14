<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VerificationVoucher extends Model
{
    public $timestamps = false; // Immutable ledger: hanya mencatat created_at

    protected $fillable = [
        'target_user_id',
        'unit_id',
        'voucher_user_id',
        'method',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'target_user_id');
    }

    public function voucherUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voucher_user_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
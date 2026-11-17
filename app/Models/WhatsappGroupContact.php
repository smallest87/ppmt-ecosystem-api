<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappGroupContact extends Model
{
    protected $fillable = [
        'phone',
        'wa_display_name',
        'groups',
        'auto_labels',
        'manual_labels',
        'source_note',
        'detected_unit_code',
        'matched_user_id',
        'matched_unit_id',
        'reconciliation_status',
        'admin_notes',
    ];

    protected function casts(): array
    {
        return [
            'groups' => 'array',
            'auto_labels' => 'array',
            'manual_labels' => 'array',
        ];
    }

    public function matchedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'matched_user_id');
    }

    public function matchedUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'matched_unit_id');
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AspirationSignature extends Model
{
    protected $fillable = [
        'aspiration_id',
        'unit_id',
        'user_id',
        'note',
    ];

    public function aspiration(): BelongsTo
    {
        return $this->belongsTo(Aspiration::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
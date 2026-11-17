<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Aspiration extends Model
{
    protected $fillable = [
        'user_id',
        'unit_id',
        'category',
        'title',
        'description',
        'location_scope',
        'status',
        'target_signatures',
        'developer_response',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'target_signatures' => 'integer',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function signatures(): HasMany
    {
        return $this->hasMany(AspirationSignature::class);
    }
}
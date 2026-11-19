<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UmkmStore extends Model
{
    protected $fillable = [
        'unit_id',
        'owner_user_id',
        'store_name',
        'category',
        'description',
        'status',
        'is_featured',
        'featured_until',
        'is_suspended',
        'moderation_note',
        'curated_by_user_id',
        'whatsapp_number',
        'catalog_items',
    ];

    protected $appends = [
        'is_currently_featured',
    ];

    protected function casts(): array
    {
        return [
            'catalog_items' => 'array',
            'is_featured' => 'boolean',
            'featured_until' => 'datetime',
            'is_suspended' => 'boolean',
        ];
    }

    public function getIsCurrentlyFeaturedAttribute(): bool
    {
        if (! $this->is_featured || $this->is_suspended) {
            return false;
        }

        return $this->featured_until === null || $this->featured_until->isFuture();
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }

    public function curator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'curated_by_user_id');
    }
}
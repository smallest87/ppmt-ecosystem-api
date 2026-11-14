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
        'whatsapp_number',
        'catalog_items',
    ];

    protected function casts(): array
    {
        return [
            'catalog_items' => 'array',
        ];
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_user_id');
    }
}
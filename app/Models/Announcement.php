<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Announcement extends Model
{
    protected $fillable = [
        'title',
        'category',
        'body',
        'is_pinned',
        'is_published',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'is_pinned' => 'boolean',
            'is_published' => 'boolean',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'penting' => 'Penting',
            'kerja_bakti' => 'Kegiatan Warga',
            'musyawarah' => 'Musyawarah',
            default => 'Info Lingkungan',
        };
    }

    public function getBadgeClassAttribute(): string
    {
        return match ($this->category) {
            'penting' => 'bg-rose-50 text-rose-700 border-rose-200',
            'kerja_bakti' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
            'musyawarah' => 'bg-amber-50 text-amber-800 border-amber-200',
            default => 'bg-indigo-50 text-indigo-700 border-indigo-200',
        };
    }
}
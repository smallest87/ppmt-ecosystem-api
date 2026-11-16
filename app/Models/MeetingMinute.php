<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeetingMinute extends Model
{
    protected $fillable = [
        'title',
        'meeting_date',
        'location',
        'category',
        'decisions',
        'visibility',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'meeting_date' => 'date',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'tata_tertib' => 'Tata Tertib Lingkungan',
            'rapat_pengurus' => 'Rapat Pengurus',
            default => 'Musyawarah Warga',
        };
    }

    public function getBadgeClassAttribute(): string
    {
        return match ($this->category) {
            'tata_tertib' => 'bg-emerald-50 text-emerald-800 border-emerald-200',
            'rapat_pengurus' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
            default => 'bg-amber-50 text-amber-800 border-amber-200',
        };
    }
}
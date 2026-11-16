<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnvironmentAgenda extends Model
{
    protected $fillable = [
        'title',
        'category',
        'event_date',
        'time_label',
        'location',
        'notes',
        'created_by_user_id',
    ];

    protected $casts = [
        'event_date' => 'date',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'kebersihan' => 'Kebersihan & Sampah',
            'keamanan' => 'Keamanan & Ronda',
            'musyawarah' => 'Musyawarah / Rapat',
            'pemeliharaan' => 'Pemeliharaan Fasum',
            default => 'Kegiatan Warga',
        };
    }

    public function getBadgeClassAttribute(): string
    {
        return match ($this->category) {
            'kebersihan' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'keamanan' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
            'musyawarah' => 'bg-amber-50 text-amber-800 border-amber-200',
            'pemeliharaan' => 'bg-sky-50 text-sky-700 border-sky-200',
            default => 'bg-slate-100 text-slate-700 border-slate-200',
        };
    }
}
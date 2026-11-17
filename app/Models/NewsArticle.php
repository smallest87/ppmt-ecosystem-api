<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class NewsArticle extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'unit_id',
        'reviewed_by',
        'title',
        'slug',
        'category',
        'layout',
        'excerpt',
        'body',
        'thumbnail_path',
        'status',
        'rejection_reason',
        'published_at',
        'published_date',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'published_date' => 'date',
    ];

    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        if (! $this->thumbnail_path) {
            return null;
        }

        if (Str::startsWith($this->thumbnail_path, ['http://', 'https://'])) {
            return $this->thumbnail_path;
        }

        return asset('storage/' . ltrim($this->thumbnail_path, '/'));
    }

    public function getDatePathAttribute(): string
    {
        $date = $this->published_date ?? $this->created_at;

        return $date->format('Y/m/d');
    }

    /**
     * Hasilkan slug unik per tanggal terbit agar permalink tidak pernah bertabrakan.
     */
    public static function generateUniqueSlug(string $title, string $dateString, ?int $ignoreId = null): string
{
    $baseSlug = Str::slug($title) ?: 'kabar-warga';
    $slug = $baseSlug;
    $counter = 2;

    while (
        static::whereDate('published_date', $dateString)
            ->where('slug', $slug)
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists()
    ) {
        $slug = "{$baseSlug}-{$counter}";
        $counter++;
    }

    return $slug;
}
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class EnvironmentAgenda extends Model
{
    protected $fillable = [
        'title',
        'category',
        'event_date',
        'time_label',
        'location',
        'notes',
        'visibility',
        'target_blocks',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'target_blocks' => 'array',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function invitedUsers(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'environment_agenda_invitations',
            'environment_agenda_id',
            'user_id'
        )->withTimestamps();
    }

    public function scopeVisibleTo(Builder $query, User $user, bool $asManager = false): Builder
    {
        if ($asManager && $user->canManageAnnouncements()) {
            return $query;
        }

        $userBlocks = $user->unitMemberships()
            ->with('unit:id,block')
            ->get()
            ->pluck('unit.block')
            ->filter()
            ->unique()
            ->values()
            ->all();

        return $query->where(function (Builder $q) use ($user, $userBlocks) {
            // 1. Agenda terbuka untuk seluruh warga
            $q->where('visibility', 'public')
              ->orWhereNull('visibility');

            // 2. Agenda khusus blok yang sama dengan kavling warga
            if (! empty($userBlocks)) {
                $q->orWhere(function (Builder $bq) use ($userBlocks) {
                    $bq->where('visibility', 'block')
                       ->where(function (Builder $jsonQ) use ($userBlocks) {
                           foreach ($userBlocks as $block) {
                               $jsonQ->orWhereJsonContains('target_blocks', $block);
                           }
                       });
                });
            }

            // 3. Agenda khusus undangan tertentu
            $q->orWhere(function (Builder $iq) use ($user) {
                $iq->where('visibility', 'invite_only')
                   ->whereHas('invitedUsers', fn (Builder $uq) => $uq->where('users.id', $user->id));
            });

            // Pembuat agenda selalu bisa melihat agendanya sendiri
            $q->orWhere('created_by_user_id', $user->id);
        });
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

    public function getVisibilityLabelAttribute(): string
    {
        return match ($this->visibility) {
            'block' => 'Blok ' . implode(', ', $this->target_blocks ?? []),
            'invite_only' => 'Undangan Khusus',
            default => 'Seluruh Warga',
        };
    }
}
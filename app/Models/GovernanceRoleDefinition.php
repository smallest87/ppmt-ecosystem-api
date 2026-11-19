<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class GovernanceRoleDefinition extends Model
{
    protected static ?Collection $cachedAll = null;

    protected $fillable = [
        'role_code',
        'label',
        'domain',
        'domain_label',
        'scope',
        'appointed_by',
        'permissions',
        'restrictions',
        'is_core',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'permissions' => 'array',
            'restrictions' => 'array',
            'is_core' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public static function flushCache(): void
    {
        self::$cachedAll = null;
    }

    public static function allActiveCached(): Collection
    {
        if (self::$cachedAll !== null) {
            return self::$cachedAll;
        }

        if (! Schema::hasTable('governance_role_definitions')) {
            return collect();
        }

        self::$cachedAll = self::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get()
            ->keyBy('role_code');

        return self::$cachedAll;
    }
}
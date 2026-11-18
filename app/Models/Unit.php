<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    protected $fillable = [
        'code',
        'block',
        'number',
        'sort_order',
        'unit_type',
        'zone',
        'occupancy_status',
        'map_coordinates',
    ];

    protected function casts(): array
    {
        return [
            'map_coordinates' => 'array',
        ];
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(UnitMembership::class);
    }

    public function umkmStores(): HasMany
    {
        return $this->hasMany(UmkmStore::class);
    }
}
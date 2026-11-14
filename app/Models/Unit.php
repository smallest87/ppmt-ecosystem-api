<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
}
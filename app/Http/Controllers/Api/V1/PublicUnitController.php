<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use Illuminate\Http\JsonResponse;

class PublicUnitController extends Controller
{
    public function index(): JsonResponse
    {
        $units = Unit::query()
            ->orderByRaw("
                CASE block
                    WHEN 'R' THEN 1
                    WHEN 'A' THEN 2
                    WHEN 'B' THEN 3
                    WHEN 'C' THEN 4
                    WHEN 'D' THEN 5
                    WHEN 'FASUM' THEN 6
                    ELSE 7
                END
            ")
            ->orderBy('sort_order')
            ->get([
                'id',
                'code',
                'block',
                'number',
                'unit_type',
                'zone',
                'occupancy_status',
                'map_coordinates',
            ]);

        return response()->json([
            'total' => $units->count(),
            'data' => $units,
        ]);
    }
}
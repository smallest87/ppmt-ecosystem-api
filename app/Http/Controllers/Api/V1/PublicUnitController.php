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
            ->where('unit_type', '!=', 'fasum')
            ->orderByRaw("
                CASE block
                    WHEN 'R' THEN 1
                    WHEN 'A' THEN 2
                    WHEN 'B' THEN 3
                    WHEN 'C' THEN 4
                    WHEN 'D' THEN 5
                    ELSE 6
                END
            ")
            ->orderBy('sort_order')
            ->get([
                'id',
                'code',
                'block',
                'zone',
                'occupancy_status',
            ]);

        return response()->json([
            'data' => $units,
        ]);
    }
}
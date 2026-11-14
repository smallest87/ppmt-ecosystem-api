<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class PpmtUnitSeeder extends Seeder
{
    public function run(): void
    {
        // Aturan khusus PPMT: Indeks fisik ke-12 dan ke-13 menggunakan 12A dan 12B
        $antiThirteenRule = [
            12 => '12A',
            13 => '12B',
        ];

        // 1. Ruko Depan (R1 - R2) -> ZONA-1
        $this->seedBlock('R', 1, 2, 'ruko', [], fn ($i) => 'ZONA-1');

        // 2. Blok A (A-1 s/d A-25, tanpa 12A/12B) -> A-1..A-16 ZONA-1, A-17..A-25 ZONA-3
        $this->seedBlock('A', 1, 25, 'rumah', $antiThirteenRule, fn ($i) => $i <= 16 ? 'ZONA-1' : 'ZONA-3');

        // 3. Blok B (B-1 s/d B-24, pakai 12A/12B) -> B-1..B-10 ZONA-2, B-11..B-24 ZONA-3
        $this->seedBlock('B', 1, 24, 'rumah', $antiThirteenRule, fn ($i) => $i <= 10 ? 'ZONA-2' : 'ZONA-3');

        // 4. Blok C (C-1 s/d C-22, pakai 12A/12B) -> C-1..C-14 ZONA-3, C-15..C-22 ZONA-4
        $this->seedBlock('C', 1, 22, 'rumah', $antiThirteenRule, fn ($i) => $i <= 14 ? 'ZONA-3' : 'ZONA-4');

        // 5. Blok D (D-1 s/d D-17, pakai 12A/12B) -> Seluruhnya ZONA-4
        $this->seedBlock('D', 1, 17, 'rumah', $antiThirteenRule, fn ($i) => 'ZONA-4');

        // 6. Fasilitas Umum Tempat Ibadah -> ZONA-4
        Unit::updateOrCreate(
            ['code' => 'FASUM-IBADAH'],
            [
                'block' => 'FASUM',
                'number' => 'IBADAH',
                'sort_order' => 1,
                'unit_type' => 'fasum',
                'zone' => 'ZONA-4',
                'occupancy_status' => 'occupied',
            ]
        );
    }

    private function seedBlock(
        string $block,
        int $start,
        int $end,
        string $type,
        array $replacements,
        callable $zoneResolver
    ): void {
        for ($i = $start; $i <= $end; $i++) {
            $label = $replacements[$i] ?? (string) $i;
            $code = $type === 'ruko' ? "{$block}{$label}" : "{$block}-{$label}";

            Unit::updateOrCreate(
                ['code' => $code],
                [
                    'block' => $block,
                    'number' => $label,
                    'sort_order' => $i,
                    'unit_type' => $type,
                    'zone' => $zoneResolver($i),
                    'occupancy_status' => 'unclaimed',
                ]
            );
        }
    }
}
<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class PpmtMapCoordinatesSeeder extends Seeder
{
    /**
     * Sinkronisasi koordinat piksel 1:1 dari PPMT_v3.bmp (kanvas 36 x 70 piksel)
     * beserta koreksi presisi Blok B-1..B-4, B-11..B-24, dan Fasum Taman.
     */
    public function run(): void
    {
        $coordinates = self::getMatrixCoordinates();

        $updated = 0;
        foreach ($coordinates as $code => $rect) {
            $affected = Unit::where('code', $code)->update([
                'map_coordinates' => json_encode([
                    'grid_cols' => 36,
                    'grid_rows' => 70,
                    'x' => $rect['x'],
                    'y' => $rect['y'],
                    'w' => $rect['w'],
                    'h' => $rect['h'],
                    'orientation' => $rect['orientation'],
                ]),
            ]);

            $updated += $affected;
        }

        $this->command?->info("Berhasil menyinkronkan koordinat matriks 1:1 terkoreksi untuk {$updated} dari " . count($coordinates) . ' unit.');
    }

    /**
     * Peta koordinat bounding-box eksak 1:1 setiap kavling (36 x 70).
     *
     * @return array<string, array{x: int, y: int, w: int, h: int, orientation: string}>
     */
    public static function getMatrixCoordinates(): array
    {
        $map = [];

        // 1. Ruko Depan (R1 - R2) -> Masing-masing 1px x 4px (di sisi kanannya x=18 adalah Pos Satpam & Taman)
        $map['R1'] = ['x' => 16, 'y' => 3, 'w' => 1, 'h' => 4, 'orientation' => 'south'];
        $map['R2'] = ['x' => 17, 'y' => 3, 'w' => 1, 'h' => 4, 'orientation' => 'south'];

        // 2. Blok A Vertikal (A-1 s/d A-16) -> 3px x 2px, memanjang dari y=7 s/d y=38
        $blockAVertical = [
            'A-1', 'A-2', 'A-3', 'A-4', 'A-5', 'A-6', 'A-7', 'A-8',
            'A-9', 'A-10', 'A-11', 'A-12A', 'A-12B', 'A-14', 'A-15', 'A-16',
        ];
        foreach ($blockAVertical as $idx => $code) {
            $map[$code] = [
                'x' => 16,
                'y' => 7 + ($idx * 2),
                'w' => 3,
                'h' => 2,
                'orientation' => 'east',
            ];
        }

        // 3. Blok A Horizontal (A-17 s/d A-25) -> 2px x 3px, membelok ke Barat (y=39..41)
        $blockAHorizontalX = [
            'A-17' => 17,
            'A-18' => 15,
            'A-19' => 13,
            'A-20' => 11,
            'A-21' => 9,
            'A-22' => 7,
            'A-23' => 5,
            'A-24' => 3,
            'A-25' => 1,
        ];
        foreach ($blockAHorizontalX as $code => $x) {
            $map[$code] = [
                'x' => $x,
                'y' => 39,
                'w' => 2,
                'h' => 3,
                'orientation' => 'south',
            ];
        }

        // 4. Blok B Klaster Timur Atas (B-1 turun 1px ke y=28; B-2..B-3 2x3, B-4 hook 3x3)
        $map['B-1'] = ['x' => 22, 'y' => 27, 'w' => 7, 'h' => 3, 'orientation' => 'west'];
        $map['B-2'] = ['x' => 22, 'y' => 30, 'w' => 2, 'h' => 3, 'orientation' => 'south'];
        $map['B-3'] = ['x' => 24, 'y' => 30, 'w' => 2, 'h' => 3, 'orientation' => 'south'];
        $map['B-4'] = ['x' => 26, 'y' => 30, 'w' => 3, 'h' => 3, 'orientation' => 'south'];

        // 5. Blok B Klaster Timur Tengah (B-6..B-9 2x3, B-5 & B-10 hook 3x3)
        $map['B-7'] = ['x' => 22, 'y' => 36, 'w' => 2, 'h' => 3, 'orientation' => 'north'];
        $map['B-6'] = ['x' => 24, 'y' => 36, 'w' => 2, 'h' => 3, 'orientation' => 'north'];
        $map['B-5'] = ['x' => 26, 'y' => 36, 'w' => 3, 'h' => 3, 'orientation' => 'north'];

        $map['B-8'] = ['x' => 22, 'y' => 39, 'w' => 2, 'h' => 3, 'orientation' => 'south'];
        $map['B-9'] = ['x' => 24, 'y' => 39, 'w' => 2, 'h' => 3, 'orientation' => 'south'];
        $map['B-10'] = ['x' => 26, 'y' => 39, 'w' => 3, 'h' => 3, 'orientation' => 'south'];

        // 6. Pulau Tengah:
        //    - Blok B (B-24..B-11) digeser 1px ke kiri (x = 4..30) agar dinding B-24 sejajar taman x=4
        //    - Blok C (C-1..C-14) tetap pada posisi x = 5..31
        $islandColumns = [
            ['B-24', 4,  'C-1',   5],
            ['B-23', 6,  'C-2',   7],
            ['B-22', 8,  'C-3',   9],
            ['B-21', 10, 'C-4',   11],
            ['B-20', 12, 'C-5',   13],
            ['B-19', 14, 'C-6',   15],
            ['B-18', 16, 'C-7',   17],
            ['B-17', 18, 'C-8',   19],
            ['B-16', 20, 'C-9',   21],
            ['B-15', 22, 'C-10',  23],
            ['B-14', 24, 'C-11',  25],
            ['B-12B', 26, 'C-12A', 27],
            ['B-12A', 28, 'C-12B', 29],
            ['B-11', 30, 'C-14',  31],
        ];
        foreach ($islandColumns as [$bCode, $bx, $cCode, $cx]) {
            $map[$bCode] = [
                'x' => $bx,
                'y' => 45,
                'w' => 2,
                'h' => 3,
                'orientation' => 'north',
            ];
            $map[$cCode] = [
                'x' => $cx,
                'y' => 48,
                'w' => 2,
                'h' => 3,
                'orientation' => 'south',
            ];
        }

        // 7. Blok C Klaster Tenggara (C-15 s/d C-18) & Blok D (D-6 s/d D-9) -> masing-masing 2x3
        $southeastPairs = [
            ['C-18', 'D-6', 22],
            ['C-17', 'D-7', 24],
            ['C-16', 'D-8', 26],
            ['C-15', 'D-9', 28],
        ];
        foreach ($southeastPairs as [$cCode, $dCode, $x]) {
            $map[$cCode] = [
                'x' => $x,
                'y' => 54,
                'w' => 2,
                'h' => 3,
                'orientation' => 'north',
            ];
            $map[$dCode] = [
                'x' => $x,
                'y' => 57,
                'w' => 2,
                'h' => 3,
                'orientation' => 'south',
            ];
        }

        // 8. Blok C Klaster Barat Daya (C-19 s/d C-22) & Blok D (D-4, D-5) -> masing-masing 2x3
        $map['C-22'] = ['x' => 11, 'y' => 54, 'w' => 2, 'h' => 3, 'orientation' => 'north'];
        $map['C-21'] = ['x' => 13, 'y' => 54, 'w' => 2, 'h' => 3, 'orientation' => 'north'];
        $map['C-20'] = ['x' => 15, 'y' => 54, 'w' => 2, 'h' => 3, 'orientation' => 'north'];
        $map['C-19'] = ['x' => 17, 'y' => 54, 'w' => 2, 'h' => 3, 'orientation' => 'north'];

        $map['D-4'] = ['x' => 15, 'y' => 57, 'w' => 2, 'h' => 3, 'orientation' => 'south'];
        $map['D-5'] = ['x' => 17, 'y' => 57, 'w' => 2, 'h' => 3, 'orientation' => 'south'];

        // 9. Blok D Ujung Barat Daya (D-1 s/d D-3) -> masing-masing 3px x 2px (berakhir di y=60)
        $map['D-1'] = ['x' => 0, 'y' => 55, 'w' => 3, 'h' => 2, 'orientation' => 'east'];
        $map['D-2'] = ['x' => 0, 'y' => 57, 'w' => 3, 'h' => 2, 'orientation' => 'east'];
        $map['D-3'] = ['x' => 0, 'y' => 59, 'w' => 3, 'h' => 2, 'orientation' => 'east'];

        // 10. Blok D Deret Selatan (D-10 s/d D-12B) -> masing-masing 2x3
        $map['D-12B'] = ['x' => 22, 'y' => 63, 'w' => 2, 'h' => 3, 'orientation' => 'north'];
        $map['D-12A'] = ['x' => 24, 'y' => 63, 'w' => 2, 'h' => 3, 'orientation' => 'north'];
        $map['D-11'] = ['x' => 26, 'y' => 63, 'w' => 2, 'h' => 3, 'orientation' => 'north'];
        $map['D-10'] = ['x' => 28, 'y' => 63, 'w' => 2, 'h' => 3, 'orientation' => 'north'];

        // 11. Blok D Pojok Bawah Barat (D-14, D-15 -> 3x2) & Pojok Bawah Timur (D-16, D-17 -> 5x2)
        $map['D-14'] = ['x' => 13, 'y' => 66, 'w' => 3, 'h' => 2, 'orientation' => 'east'];
        $map['D-15'] = ['x' => 13, 'y' => 68, 'w' => 3, 'h' => 2, 'orientation' => 'east'];

        $map['D-16'] = ['x' => 25, 'y' => 66, 'w' => 5, 'h' => 2, 'orientation' => 'west'];
        $map['D-17'] = ['x' => 25, 'y' => 68, 'w' => 5, 'h' => 2, 'orientation' => 'west'];

        // 12. Fasilitas Umum Tempat Ibadah (Blok Kuning 5x3)
        $map['FASUM-IBADAH'] = ['x' => 6, 'y' => 54, 'w' => 5, 'h' => 3, 'orientation' => 'north'];

        return $map;
    }
}
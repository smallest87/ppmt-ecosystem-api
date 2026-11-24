<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class PpmtCarportSideSeeder extends Seeder
{
    public function run(): void
    {
        // Isi manual pengecualian / kavling yang sudah dipastikan di lapangan:
        // 'left'  = carport di sisi kiri (dilihat dari dalam rumah menghadap keluar)
        // 'right' = carport di sisi kanan (dilihat dari dalam rumah menghadap keluar)
        $manualSides = [
            'A-1' => 'right',
            'A-2' => 'left',
            'A-3' => 'right',
            'A-4' => 'left',
            'A-5' => 'right',
            'A-6' => 'left',
            'A-7' => 'right',
            'A-8' => 'left',
            'A-9' => 'right',
            'A-10' => 'left',
            'A-11' => 'right',
            'A-12A' => 'left',
            'A-12B' => 'right',
            'A-14' => 'left',
            'A-15' => 'right',
            'A-16' => 'left',
            'A-17' => 'right',
            'A-18' => 'left',
            'A-19' => 'right',
            'A-20' => 'left',
            'A-21' => 'right',
            'A-22' => 'left',
            'A-23' => 'right',
            'A-24' => 'left',
            'A-25' => 'right',
            'B-1' => 'left',
            'B-2' => 'left',
            'B-3' => 'right',
            'B-4' => 'left',
            'B-5' => 'right',
            'B-6' => 'left',
            'B-7' => 'right',
            'B-8' => 'left',
            'B-9' => 'right',
            'B-10' => 'left',
            'B-11' => 'right',
            'B-12A' => 'left',
            'B-12B' => 'right',
            'B-14' => 'left',
            'B-15' => 'right',
            'B-16' => 'left',
            'B-17' => 'right',
            'B-18' => 'left',
            'B-19' => 'right',
            'B-20' => 'left',
            'B-21' => 'right',
            'B-22' => 'left',
            'B-23' => 'right',
            'B-24' => 'left',
            'C-1' => 'right',
            'C-2' => 'left',
            'C-3' => 'right',
            'C-4' => 'left',
            'C-5' => 'right',
            'C-6' => 'left',
            'C-7' => 'right',
            'C-8' => 'left',
            'C-9' => 'right',
            'C-10' => 'left',
            'C-11' => 'right',
            'C-12A' => 'left',
            'C-12B' => 'right',
            'C-14' => 'left',
            'C-15' => 'left',
            'C-16' => 'right',
            'C-17' => 'left',
            'C-18' => 'right',
            'C-19' => 'left',
            'C-20' => 'right',
            'C-21' => 'left',
            'C-22' => 'right',
            'D-1' => 'left',
            'D-2' => 'right',
            'D-3' => 'left',
            'D-4' => 'left',
            'D-5' => 'right',
            'D-6' => 'left',
            'D-7' => 'right',
            'D-8' => 'left',
            'D-9' => 'right',
            'D-10' => 'left',
            'D-11' => 'right',
            'D-12A' => 'left',
            'D-12B' => 'right',
            'D-14' => 'left',
            'D-15' => 'right',
            'D-16' => 'right',
            'D-17' => 'left',
        ];

        $updated = 0;
        foreach ($manualSides as $code => $side) {
            $updated += Unit::where('code', $code)->update(['carport_side' => $side]);
        }

        $this->command?->info("Berhasil memperbarui carport_side untuk {$updated} unit.");
    }
}
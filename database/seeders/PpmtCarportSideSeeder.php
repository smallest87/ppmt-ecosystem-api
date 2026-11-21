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
            'C-1' => 'left',
            'C-2' => 'right',
            'C-3' => 'left',
            'C-4' => 'right',
            'C-5' => 'left',
            'C-6' => 'right',
            'C-7' => 'left',
            'C-8' => 'right',
            'C-9' => 'left',
            'C-10' => 'right',
            'C-11' => 'left',
            'C-12A' => 'right',
            'C-12B' => 'left',
            'C-14' => 'right',
        ];

        $updated = 0;
        foreach ($manualSides as $code => $side) {
            $updated += Unit::where('code', $code)->update(['carport_side' => $side]);
        }

        $this->command?->info("Berhasil memperbarui carport_side untuk {$updated} unit.");
    }
}
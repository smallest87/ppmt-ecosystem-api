<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class GenesisAdminSeeder extends Seeder
{
    public function run(): void
    {
        // Sengaja dikosongkan: Sistem PPMT tidak membuat entitas akun "Admin" statis.
        // Warga mendaftar mandiri terlebih dahulu, lalu diberikan mandat jabatan Admin
        // secara prosedural melalui perintah: php artisan ppmt:grant-admin {nomor_wa}
    }
}
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('governance_role_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('role_code', 50)->unique();
            $table->string('label', 100);
            $table->enum('domain', ['system', 'organization'])->default('organization');
            $table->string('domain_label', 100)->default('Kabinet / Bidang Operasional');
            $table->enum('scope', ['global', 'zone', 'block'])->default('global');
            $table->string('appointed_by', 150)->default('Ketua Paguyuban');
            $table->json('permissions');
            $table->json('restrictions');
            $table->boolean('is_core')->default(false);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        $now = now();
        $coreRoles = [
            [
                'role_code' => 'admin',
                'label' => 'Admin Sistem',
                'domain' => 'system',
                'domain_label' => 'Teknis & Infrastruktur Sistem',
                'scope' => 'global',
                'appointed_by' => 'Admin Sistem Aktif / Genesis',
                'permissions' => json_encode([
                    'Mengelola Role & Permission Builder (tambah/ubah/hapus nomenklatur role & matriks batasan)',
                    'Menetapkan Operator Aplikasi & mengukuhkan mandat Ketua Paguyuban terpilih',
                    'Mengakses Buku Induk Kavling pusat, pendaftaran warga terfasilitasi, dan rekonsiliasi kontak Grup WhatsApp',
                ]),
                'restrictions' => json_encode([
                    'Dilarang mengubah pembukuan kas/iuran Bendahara maupun menerbitkan pengumuman/notulensi resmi paguyuban',
                    'Dilarang mengangkat/mencabut kabinet Ketua Paguyuban (Sekretaris, Bendahara, Seksi, Koordinator)',
                    'Tertutup penuh dari angka mentah NIB SHM maupun isi terenkripsi NIK/KK warga',
                ]),
                'is_core' => true,
            ],
            [
                'role_code' => 'operator_aplikasi',
                'label' => 'Operator Aplikasi',
                'domain' => 'system',
                'domain_label' => 'Teknis & Infrastruktur Sistem',
                'scope' => 'global',
                'appointed_by' => 'Admin Sistem',
                'permissions' => json_encode([
                    'Menjalankan pemadupadanan kontak Grup WhatsApp dengan database warga',
                    'Membantu pendaftaran warga terfasilitasi dan pengukuhan mandat Ketua Paguyuban hasil musyawarah',
                    'Mengaudit matriks hak akses seluruh pengguna aktif (mode baca)',
                ]),
                'restrictions' => json_encode([
                    'Dilarang menambah, mengubah, atau menghapus definisi skema role maupun matriks izin sistem',
                    'Dilarang mengelola pembukuan kas paguyuban atau mengintervensi kabinet Ketua Paguyuban',
                    'Tertutup penuh dari data rahasia NIB SHM dan dokumen sipil terenkripsi',
                ]),
                'is_core' => true,
            ],
            [
                'role_code' => 'ketua_paguyuban',
                'label' => 'Ketua Paguyuban',
                'domain' => 'organization',
                'domain_label' => 'Pimpinan Inti Organisasi',
                'scope' => 'global',
                'appointed_by' => 'Admin Sistem / Operator Aplikasi (Mandat Musyawarah Warga)',
                'permissions' => json_encode([
                    'Menyusun & mencabut kabinet kepengurusan (Sekretaris, Bendahara, Seksi Bidang, Koordinator Zona/Blok)',
                    'Mengesahkan klaim warga baru & bukti kepemilikan SHM di seluruh blok',
                    'Memantau laporan keuangan paguyuban (view-only), menerbitkan pengumuman/agenda, & mengkurasi aspirasi warga',
                ]),
                'restrictions' => json_encode([
                    'Tidak dapat mengubah catatan jurnal pembukuan kas Bendahara (menjaga pemisahan fungsi audit)',
                    'Tidak dapat menunjuk atau mencabut role teknis Admin Sistem maupun Operator Aplikasi',
                    'Tertutup dari angka mentah NIB SHM serta NIK/KK terenkripsi warga',
                ]),
                'is_core' => true,
            ],
            [
                'role_code' => 'sekretaris_paguyuban',
                'label' => 'Sekretaris Paguyuban',
                'domain' => 'organization',
                'domain_label' => 'Pimpinan Inti Organisasi',
                'scope' => 'global',
                'appointed_by' => 'Ketua Paguyuban',
                'permissions' => json_encode([
                    'Menerbitkan & mengelola Papan Pengumuman, Jadwal Agenda Warga, serta Arsip Notulensi & Tata Tertib',
                    'Mengakses Buku Induk Kavling seluruh blok untuk keperluan administrasi warga',
                    'Ikut mengesahkan bukti kepemilikan SHM warga sesuai cakupan',
                ]),
                'restrictions' => json_encode([
                    'Tidak memiliki akses ke pembukuan kas maupun mutasi iuran Bendahara',
                    'Tidak dapat mengangkat atau mencabut jabatan pengurus lain',
                    'Tertutup dari angka mentah NIB SHM dan NIK/KK terenkripsi',
                ]),
                'is_core' => true,
            ],
            [
                'role_code' => 'bendahara_paguyuban',
                'label' => 'Bendahara Paguyuban',
                'domain' => 'organization',
                'domain_label' => 'Pimpinan Inti Organisasi',
                'scope' => 'global',
                'appointed_by' => 'Ketua Paguyuban',
                'permissions' => json_encode([
                    'Mencatat pemasukan/pengeluaran kas paguyuban dan memperbarui status pelunasan iuran sampah bulanan warga',
                    'Mengakses Buku Induk Kavling seluruh blok untuk pencocokan tagihan per unit',
                ]),
                'restrictions' => json_encode([
                    'Tidak dapat menerbitkan pengumuman/notulensi resmi maupun mengubah konfigurasi sistem',
                    'Tidak dapat mengelola kurasi Pasar Tetangga (menjaga pemisahan antara kas sosial & usaha pribadi warga)',
                    'Tertutup dari angka mentah NIB SHM dan NIK/KK terenkripsi',
                ]),
                'is_core' => true,
            ],
            [
                'role_code' => 'seksi_ekonomi',
                'label' => 'Seksi Ekonomi & UMKM',
                'domain' => 'organization',
                'domain_label' => 'Kabinet / Bidang Operasional',
                'scope' => 'global',
                'appointed_by' => 'Ketua Paguyuban',
                'permissions' => json_encode([
                    'Memantau seluruh lapak & katalog produk/jasa di Pasar Tetangga PPMT',
                    'Mengatur parameter algoritma pemerataan distribusi etalase (fair exposure) & status moderasi lapak',
                    'Melihat Buku Induk Kavling untuk memastikan pemilik lapak berdomisili sah di unit terkait',
                ]),
                'restrictions' => json_encode([
                    'Dilarang mengakses modul Kas Paguyuban, Iuran Sampah, maupun Publikasi/Notulensi',
                    'Tidak dapat mengangkat atau mencabut jabatan pengurus lain',
                    'Tertutup penuh dari angka mentah NIB SHM dan NIK/KK terenkripsi',
                ]),
                'is_core' => true,
            ],
            [
                'role_code' => 'keamanan_lingkungan',
                'label' => 'Keamanan Lingkungan',
                'domain' => 'organization',
                'domain_label' => 'Kabinet / Bidang Operasional',
                'scope' => 'global',
                'appointed_by' => 'Ketua Paguyuban',
                'permissions' => json_encode([
                    'Mengakses Buku Induk Kavling seluruh blok untuk validasi penghuni & kendaraan terdaftar',
                    'Ikut mengesahkan verifikasi penghuni pada kavling terkait',
                ]),
                'restrictions' => json_encode([
                    'Tidak memiliki akses ke keuangan paguyuban, publikasi/notulensi, maupun pengaturan sistem',
                    'Tertutup penuh dari angka mentah NIB SHM dan NIK/KK terenkripsi',
                ]),
                'is_core' => true,
            ],
            [
                'role_code' => 'koordinator_zona',
                'label' => 'Koordinator Zona',
                'domain' => 'organization',
                'domain_label' => 'Koordinator Wilayah',
                'scope' => 'zone',
                'appointed_by' => 'Ketua Paguyuban',
                'permissions' => json_encode([
                    'Mengesahkan klaim pendaftaran warga baru dan kepemilikan SHM khusus pada Zona yang ditugaskan',
                    'Melihat daftar hunian Buku Induk terbatas pada kavling di dalam Zonanya',
                ]),
                'restrictions' => json_encode([
                    'Dilarang mengakses data hunian di luar Zona tugasnya',
                    'Tidak memiliki akses ke kas paguyuban, publikasi pusat, maupun penetapan jabatan',
                    'Tertutup penuh dari angka mentah NIB SHM dan NIK/KK terenkripsi',
                ]),
                'is_core' => true,
            ],
            [
                'role_code' => 'koordinator_blok',
                'label' => 'Koordinator Blok',
                'domain' => 'organization',
                'domain_label' => 'Koordinator Wilayah',
                'scope' => 'block',
                'appointed_by' => 'Ketua Paguyuban',
                'permissions' => json_encode([
                    'Mengesahkan klaim pendaftaran warga baru dan kepemilikan SHM khusus pada Blok yang ditugaskan',
                    'Melihat daftar hunian Buku Induk terbatas pada kavling di dalam Bloknya',
                ]),
                'restrictions' => json_encode([
                    'Dilarang mengakses data hunian di luar Blok tugasnya',
                    'Tidak memiliki akses ke kas paguyuban, publikasi pusat, maupun penetapan jabatan',
                    'Tertutup penuh dari angka mentah NIB SHM dan NIK/KK terenkripsi',
                ]),
                'is_core' => true,
            ],
        ];

        foreach ($coreRoles as $role) {
            DB::table('governance_role_definitions')->insert(array_merge($role, [
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('governance_role_definitions');
    }
};
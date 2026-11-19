<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserGovernanceRole extends Model
{
    public const ROLES = [
        'admin' => [
            'label' => 'Admin Sistem',
            'domain' => 'system',
            'domain_label' => 'Teknis & Infrastruktur Sistem',
            'scope' => 'global',
            'appointed_by' => 'Admin Sistem Aktif / Genesis',
            'permissions' => [
                'Mengelola Role Management, audit matriks izin, serta penetapan Operator Aplikasi & Ketua Paguyuban terpilih',
                'Mengakses Buku Induk Kavling pusat, pendaftaran warga terfasilitasi, dan rekonsiliasi kontak Grup WhatsApp',
                'Memantau integritas konfigurasi & log operasional sistem',
            ],
            'restrictions' => [
                'Dilarang mengubah pembukuan kas/iuran Bendahara maupun menerbitkan pengumuman/notulensi resmi paguyuban',
                'Dilarang mengangkat/mencabut kabinet Ketua Paguyuban (Sekretaris, Bendahara, Seksi, Koordinator)',
                'Tertutup penuh dari angka mentah NIB SHM maupun isi terenkripsi NIK/KK warga',
            ],
        ],
        'operator_aplikasi' => [
            'label' => 'Operator Aplikasi',
            'domain' => 'system',
            'domain_label' => 'Teknis & Infrastruktur Sistem',
            'scope' => 'global',
            'appointed_by' => 'Admin Sistem',
            'permissions' => [
                'Menjalankan pemadupadanan kontak Grup WhatsApp dengan database warga',
                'Membantu pendaftaran warga terfasilitasi dan penetapan mandat Ketua Paguyuban hasil musyawarah',
                'Mengaudit matriks hak akses seluruh pengguna aktif',
            ],
            'restrictions' => [
                'Dilarang mengelola pembukuan kas paguyuban atau mengubah status iuran warga',
                'Dilarang mengintervensi penyusunan perangkat kabinet Ketua Paguyuban',
                'Tertutup penuh dari data rahasia NIB SHM dan dokumen sipil terenkripsi',
            ],
        ],
        'ketua_paguyuban' => [
            'label' => 'Ketua Paguyuban',
            'domain' => 'organization',
            'domain_label' => 'Pimpinan Inti Organisasi',
            'scope' => 'global',
            'appointed_by' => 'Admin Sistem / Operator Aplikasi (Mandat Musyawarah Warga)',
            'permissions' => [
                'Menyusun & mencabut kabinet kepengurusan (Sekretaris, Bendahara, Seksi Ekonomi, Keamanan, Koordinator Zona/Blok)',
                'Mengesahkan klaim warga baru & bukti kepemilikan SHM di seluruh blok',
                'Memantau laporan keuangan paguyuban (view-only), menerbitkan pengumuman/agenda, & mengkurasi aspirasi warga',
            ],
            'restrictions' => [
                'Tidak dapat mengubah catatan jurnal pembukuan kas Bendahara (menjaga pemisahan fungsi audit)',
                'Tidak dapat menunjuk atau mencabut role teknis Admin Sistem maupun Operator Aplikasi',
                'Tertutup dari angka mentah NIB SHM serta NIK/KK terenkripsi warga',
            ],
        ],
        'sekretaris_paguyuban' => [
            'label' => 'Sekretaris Paguyuban',
            'domain' => 'organization',
            'domain_label' => 'Pimpinan Inti Organisasi',
            'scope' => 'global',
            'appointed_by' => 'Ketua Paguyuban',
            'permissions' => [
                'Menerbitkan & mengelola Papan Pengumuman, Jadwal Agenda Warga, serta Arsip Notulensi & Tata Tertib',
                'Mengakses Buku Induk Kavling seluruh blok untuk keperluan administrasi warga',
                'Ikut mengesahkan bukti kepemilikan SHM warga sesuai cakupan',
            ],
            'restrictions' => [
                'Tidak memiliki akses ke pembukuan kas maupun mutasi iuran Bendahara',
                'Tidak dapat mengangkat atau mencabut jabatan pengurus lain',
                'Tertutup dari angka mentah NIB SHM dan NIK/KK terenkripsi',
            ],
        ],
        'bendahara_paguyuban' => [
            'label' => 'Bendahara Paguyuban',
            'domain' => 'organization',
            'domain_label' => 'Pimpinan Inti Organisasi',
            'scope' => 'global',
            'appointed_by' => 'Ketua Paguyuban',
            'permissions' => [
                'Mencatat pemasukan/pengeluaran kas paguyuban dan memperbarui status pelunasan iuran sampah bulanan warga',
                'Mengakses Buku Induk Kavling seluruh blok untuk pencocokan tagihan per unit',
            ],
            'restrictions' => [
                'Tidak dapat menerbitkan pengumuman/notulensi resmi maupun mengubah konfigurasi sistem',
                'Tidak dapat mengelola kurasi Pasar Tetangga (menjaga pemisahan antara kas sosial & usaha pribadi warga)',
                'Tertutup dari angka mentah NIB SHM dan NIK/KK terenkripsi',
            ],
        ],
        'seksi_ekonomi' => [
            'label' => 'Seksi Ekonomi & UMKM',
            'domain' => 'organization',
            'domain_label' => 'Kabinet / Bidang Operasional',
            'scope' => 'global',
            'appointed_by' => 'Ketua Paguyuban',
            'permissions' => [
                'Memantau seluruh lapak & katalog produk/jasa di Pasar Tetangga PPMT',
                'Mengatur parameter algoritma pemerataan distribusi etalase (fair exposure) & status moderasi lapak',
                'Melihat Buku Induk Kavling untuk memastikan pemilik lapak berdomisili sah di unit terkait',
            ],
            'restrictions' => [
                'Dilarang mengakses modul Kas Paguyuban, Iuran Sampah, maupun Publikasi/Notulensi',
                'Tidak dapat mengangkat atau mencabut jabatan pengurus lain',
                'Tertutup penuh dari angka mentah NIB SHM dan NIK/KK terenkripsi',
            ],
        ],
        'keamanan_lingkungan' => [
            'label' => 'Keamanan Lingkungan',
            'domain' => 'organization',
            'domain_label' => 'Kabinet / Bidang Operasional',
            'scope' => 'global',
            'appointed_by' => 'Ketua Paguyuban',
            'permissions' => [
                'Mengakses Buku Induk Kavling seluruh blok untuk validasi penghuni & kendaraan terdaftar',
                'Ikut mengesahkan verifikasi penghuni pada kavling terkait',
            ],
            'restrictions' => [
                'Tidak memiliki akses ke keuangan paguyuban, publikasi/notulensi, maupun pengaturan sistem',
                'Tertutup penuh dari angka mentah NIB SHM dan NIK/KK terenkripsi',
            ],
        ],
        'koordinator_zona' => [
            'label' => 'Koordinator Zona',
            'domain' => 'organization',
            'domain_label' => 'Koordinator Wilayah',
            'scope' => 'zone',
            'appointed_by' => 'Ketua Paguyuban',
            'permissions' => [
                'Mengesahkan klaim pendaftaran warga baru dan kepemilikan SHM khusus pada Zona yang ditugaskan',
                'Melihat daftar hunian Buku Induk terbatas pada kavling di dalam Zonanya',
            ],
            'restrictions' => [
                'Dilarang mengakses data hunian di luar Zona tugasnya',
                'Tidak memiliki akses ke kas paguyuban, publikasi pusat, maupun penetapan jabatan',
                'Tertutup penuh dari angka mentah NIB SHM dan NIK/KK terenkripsi',
            ],
        ],
        'koordinator_blok' => [
            'label' => 'Koordinator Blok',
            'domain' => 'organization',
            'domain_label' => 'Koordinator Wilayah',
            'scope' => 'block',
            'appointed_by' => 'Ketua Paguyuban',
            'permissions' => [
                'Mengesahkan klaim pendaftaran warga baru dan kepemilikan SHM khusus pada Blok yang ditugaskan',
                'Melihat daftar hunian Buku Induk terbatas pada kavling di dalam Bloknya',
            ],
            'restrictions' => [
                'Dilarang mengakses data hunian di luar Blok tugasnya',
                'Tidak memiliki akses ke kas paguyuban, publikasi pusat, maupun penetapan jabatan',
                'Tertutup penuh dari angka mentah NIB SHM dan NIK/KK terenkripsi',
            ],
        ],
    ];

    protected $fillable = [
        'user_id',
        'role_code',
        'scope_type',
        'scope_value',
        'assigned_by_user_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public static function validRoleCodes(): array
    {
        return array_keys(self::ROLES);
    }

    public static function requiredScopeFor(string $roleCode): string
    {
        return self::ROLES[$roleCode]['scope'] ?? 'global';
    }

    public static function labelFor(string $roleCode): string
    {
        return self::ROLES[$roleCode]['label']
            ?? ucwords(str_replace('_', ' ', $roleCode));
    }

    public static function permissionMatrix(): array
    {
        return collect(self::ROLES)->map(function (array $meta, string $code) {
            return [
                'code' => $code,
                'label' => $meta['label'],
                'domain' => $meta['domain'],
                'domain_label' => $meta['domain_label'],
                'scope' => $meta['scope'],
                'appointed_by' => $meta['appointed_by'],
                'permissions' => $meta['permissions'],
                'restrictions' => $meta['restrictions'],
            ];
        })->values()->all();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by_user_id');
    }

    public function getDisplayLabelAttribute(): string
    {
        $label = self::labelFor($this->role_code);

        if ($this->scope_type !== 'global' && $this->scope_value) {
            return "{$label} ({$this->scope_value})";
        }

        return $label;
    }
}
@extends('layouts.app')

@section('title', 'Pengaturan — PPMT Digital')
@section('subpage_title', 'Pengaturan')
@section('subpage_back_url', '/dashboard')

@section('content')
<div x-data="ppmtSettingsHub()" x-init="init()" class="max-w-3xl mx-auto px-4 py-6 sm:py-8 space-y-6">

    <!-- Loading State -->
    <div x-show="loading" class="bg-white rounded-2xl p-10 text-center text-sm text-slate-500 border border-slate-200">
        Memuat ringkasan pengaturan Anda...
    </div>

    <div x-show="!loading && user" x-cloak class="space-y-6">

        <!-- Kartu Ringkasan Profil Warga -->
        <section class="bg-white rounded-2xl p-5 sm:p-6 shadow-sm border border-slate-200 flex items-center gap-4">
            <template x-if="user?.avatar_url">
                <img :src="user.avatar_url"
                     :alt="user.name"
                     class="w-14 h-14 rounded-full object-cover border border-slate-200 shrink-0">
            </template>
            <template x-if="!user?.avatar_url">
                <div class="w-14 h-14 rounded-full bg-emerald-600 text-white text-base font-extrabold flex items-center justify-center tracking-wider shrink-0 shadow-xs"
                     x-text="userInitials">
                </div>
            </template>

            <div class="min-w-0 flex-1 space-y-1">
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-base sm:text-lg font-extrabold text-slate-900 truncate"
                        x-text="user?.display_name || user?.name"></h2>
                    <span x-show="unitCodesSummary"
                          x-text="unitCodesSummary"
                          class="px-2 py-0.5 rounded-md text-[11px] font-bold font-mono bg-slate-100 text-slate-800 border border-slate-200">
                    </span>
                </div>
                <p class="text-xs text-slate-500 truncate" x-text="user?.name"></p>
                <div class="flex flex-wrap items-center gap-2 pt-0.5 text-[11px] text-slate-600">
                    <span x-show="user?.phone" class="font-mono" x-text="user?.phone"></span>
                    <span x-show="user?.phone && user?.username" class="text-slate-300">&bull;</span>
                    <span x-show="user?.username" class="font-mono text-slate-500" x-text="'@' + user?.username"></span>
                </div>
            </div>
        </section>

        <!-- Daftar Menu Sub-Halaman Pengaturan -->
        <section class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden divide-y divide-slate-100">

            <!-- Menu 1: Akun, Privasi & Dokumen Kependudukan -->
            <a href="/pengaturan/akun"
               class="p-5 sm:p-6 flex items-center justify-between gap-4 hover:bg-slate-50/90 transition group">
                <div class="flex items-start gap-4 min-w-0">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center justify-center shrink-0 mt-0.5">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                        </svg>
                    </div>
                    <div class="space-y-1 min-w-0">
                        <div class="text-sm sm:text-base font-bold text-slate-900 group-hover:text-emerald-800 transition">
                            Akun, Privasi &amp; Dokumen Sipil
                        </div>
                        <p class="text-xs text-slate-500 leading-relaxed">
                            Nama panggilan lingkungan, identitas login (WhatsApp &amp; Username), kata sandi, visibilitas direktori warga, serta NIK &amp; Nomor KK terenkripsi (AES-256).
                        </p>
                        <div class="flex flex-wrap items-center gap-1.5 pt-1">
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-semibold"
                                  :class="user?.has_nik && user?.has_kk ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-amber-50 text-amber-800 border border-amber-200'"
                                  x-text="user?.has_nik && user?.has_kk ? '✓ NIK & KK Terenkripsi' : '⚠ NIK / KK Belum Lengkap'">
                            </span>
                        </div>
                    </div>
                </div>
                <span class="text-slate-400 group-hover:text-emerald-700 group-hover:translate-x-0.5 transition text-xl font-medium shrink-0">&rsaquo;</span>
            </a>

            <!-- Menu 2: Hunian, Kepemilikan & Kendaraan -->
            <a href="/pengaturan/hunian"
               class="p-5 sm:p-6 flex items-center justify-between gap-4 hover:bg-slate-50/90 transition group">
                <div class="flex items-start gap-4 min-w-0">
                    <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-700 border border-amber-200 flex items-center justify-center shrink-0 mt-0.5">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/>
                        </svg>
                    </div>
                    <div class="space-y-1 min-w-0">
                        <div class="text-sm sm:text-base font-bold text-slate-900 group-hover:text-emerald-800 transition">
                            Hunian, Kepemilikan &amp; Kendaraan
                        </div>
                        <p class="text-xs text-slate-500 leading-relaxed">
                            Atur status pemilik kavling (beserta verifikasi NIB SHM), status penghuni fisik, masa sewa, serta plat nomor kendaraan terdaftar di kavling.
                        </p>
                        <div class="flex flex-wrap items-center gap-1.5 pt-1">
                            <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 border border-slate-200 text-[10px] font-semibold"
                                  x-text="(user?.memberships?.length || 0) + ' Kavling Terhubung'">
                            </span>
                            <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 border border-slate-200 text-[10px] font-semibold"
                                  x-text="(user?.vehicles?.length || 0) + ' Kendaraan'">
                            </span>
                        </div>
                    </div>
                </div>
                <span class="text-slate-400 group-hover:text-emerald-700 group-hover:translate-x-0.5 transition text-xl font-medium shrink-0">&rsaquo;</span>
            </a>

            <!-- Menu 3: Lapak Usaha & Pasar Tetangga -->
            <a href="/pengaturan/lapak"
            class="p-5 sm:p-6 flex items-center justify-between gap-4 hover:bg-slate-50/90 transition group">
                <div class="flex items-start gap-4 min-w-0">
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-700 border border-indigo-200 flex items-center justify-center shrink-0 mt-0.5">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72m-13.5 8.65h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.75c0 .415.336.75.75.75z" />
                        </svg>
                    </div>
                    <div class="space-y-1 min-w-0">
                        <div class="text-sm sm:text-base font-bold text-slate-900 group-hover:text-emerald-800 transition">
                            Lapak Usaha &amp; Pasar Tetangga
                        </div>
                        <p class="text-xs text-slate-500 leading-relaxed">
                            Buka lapak usaha rumahan, atur status buka/tutup, dan kelola daftar produk, kuliner, jasa, maupun pinjam alat untuk tetangga PPMT.
                        </p>
                        <div class="flex flex-wrap items-center gap-1.5 pt-1">
                            <span class="px-2 py-0.5 rounded-md text-[10px] font-semibold"
                                :class="myStore ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-slate-100 text-slate-600 border border-slate-200'"
                                x-text="myStore ? ('🏪 ' + myStore.store_name + ' (' + (myStore.catalog_items?.length || 0) + ' item)') : 'Belum Membuka Lapak'">
                            </span>
                        </div>
                    </div>
                </div>
                <span class="text-slate-400 group-hover:text-emerald-700 group-hover:translate-x-0.5 transition text-xl font-medium shrink-0">&rsaquo;</span>
            </a>

        </section>

    </div>
</div>

<script>
function ppmtSettingsHub() {
    return {
        token: localStorage.getItem('ppmt_token'),
        user: null,
        loading: true,

        get userInitials() {
            const raw = (this.user?.display_name || this.user?.name || '').trim();
            if (!raw) return 'W';
            const parts = raw.split(/\s+/);
            if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
            return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
        },

        get unitCodesSummary() {
            const list = Array.isArray(this.user?.memberships) ? this.user.memberships : [];
            const codes = list.map(m => m.unit?.code).filter(Boolean);
            return Array.from(new Set(codes)).join(', ');
        },

        async init() {
            if (!this.token) {
                window.location.href = '/';
                return;
            }
            try {
                const res = await fetch('/api/v1/me', {
                    headers: {
                        'Authorization': 'Bearer ' + this.token,
                        'Accept': 'application/json'
                    }
                });
                if (!res.ok) throw new Error('Unauthorized');
                const json = await res.json();
                this.user = json.data;
            } catch (e) {
                localStorage.removeItem('ppmt_token');
                window.location.href = '/';
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>
@endsection
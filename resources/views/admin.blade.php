@extends('layouts.app')

@section('title', 'Panel Pengurus PPMT — PPMT Digital')
@section('subpage_title', 'Panel Pengurus')
@section('subpage_back_url', '/dashboard')

@section('content')
<div x-data="ppmtAdminHub()" x-init="init()" class="max-w-5xl mx-auto px-4 py-6 sm:py-8 space-y-6">

    <!-- Loading State -->
    <div x-show="loading" class="bg-white rounded-2xl p-12 text-center text-slate-500 border border-slate-200">
        Memverifikasi mandat jabatan pengurus PPMT...
    </div>

    <div x-show="!loading && user" x-cloak class="space-y-6">

        <!-- Notifikasi Global -->
        <div x-show="toastMessage"
             x-cloak
             :class="toastType === 'error' ? 'bg-rose-50 text-rose-800 border-rose-200' : 'bg-emerald-50 text-emerald-800 border-emerald-200'"
             class="p-4 rounded-xl border text-sm font-medium flex justify-between items-center shadow-sm">
            <span x-text="toastMessage"></span>
            <button type="button" @click="toastMessage = ''" class="text-xs opacity-70 hover:opacity-100 cursor-pointer">✕</button>
        </div>

        <!-- Header Panel Pengurus -->
        <section class="bg-indigo-950 text-white rounded-2xl p-5 sm:p-6 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="space-y-1.5">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-800/80 text-indigo-200 text-xs font-semibold">
                    <span>&#9733; Ruang Kerja Tata Kelola &amp; Administrasi PPMT</span>
                </div>
                <h1 class="text-xl sm:text-2xl font-extrabold"
                    x-text="'Halo, ' + (user?.display_name || user?.name)"></h1>
                <p class="text-xs text-indigo-200">
                    Pilih ruang kerja di bawah sesuai bidang tugas Anda. Seluruh aktivitas kepengurusan tercatat dalam jejak audit lingkungan.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <template x-for="gov in (user?.governance_roles || [])" :key="gov.id">
                    <span class="px-3 py-1 rounded-xl bg-amber-400 text-slate-950 text-xs font-bold shadow-sm"
                          x-text="gov.display_label">
                    </span>
                </template>
            </div>
        </section>

        <!-- Stats Strip: Ringkasan Kondisi Hunian & Administrasi -->
        <section x-show="registryStats !== null" class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200 shadow-sm">
                <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Kavling Terdata</div>
                <div class="mt-1.5 flex items-baseline gap-1.5">
                    <span class="text-2xl font-extrabold text-emerald-700" x-text="registryStats?.filled_units ?? 0"></span>
                    <span class="text-xs font-semibold text-slate-400" x-text="'/ ' + (registryStats?.total_units ?? 0) + ' unit'"></span>
                </div>
                <div class="text-[11px] text-slate-500 mt-1"
                     x-text="(registryStats?.occupied_units ?? 0) + ' Terdata • ' + (registryStats?.rented_units ?? 0) + ' Disewakan'">
                </div>
            </div>

            <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200 shadow-sm">
                <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Warga Terverifikasi</div>
                <div class="mt-1.5 flex items-baseline gap-1.5">
                    <span class="text-2xl font-extrabold text-slate-900" x-text="registryStats?.verified_residents ?? 0"></span>
                    <span class="text-xs font-semibold text-slate-400">akun aktif</span>
                </div>
                <div class="text-[11px] text-slate-500 mt-1"
                     x-text="(registryStats?.unclaimed_units ?? 0) + ' kavling belum terdata'">
                </div>
            </div>

            <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200 shadow-sm">
                <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Antrean Verifikasi</div>
                <div class="mt-1.5 flex items-baseline gap-1.5">
                    <span :class="(pendingApprovals.length + pendingOwnerships.length) > 0 ? 'text-amber-600' : 'text-slate-900'"
                          class="text-2xl font-extrabold"
                          x-text="pendingApprovals.length + pendingOwnerships.length"></span>
                    <span class="text-xs font-semibold text-slate-400">berkas</span>
                </div>
                <div class="text-[11px] text-slate-500 mt-1"
                     x-text="pendingApprovals.length + ' warga baru • ' + pendingOwnerships.length + ' NIB SHM'">
                </div>
            </div>

            <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200 shadow-sm">
                <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Papan &amp; Arsip</div>
                <div class="mt-1.5 flex items-baseline gap-1.5">
                    <span class="text-2xl font-extrabold text-indigo-700" x-text="registryStats?.published_announcements ?? 0"></span>
                    <span class="text-xs font-semibold text-slate-400">pengumuman</span>
                </div>
                <div class="text-[11px] text-slate-500 mt-1"
                     x-text="(registryStats?.total_minutes ?? 0) + ' dokumen notulensi & tatib'">
                </div>
            </div>
        </section>

        <!-- Menu Ruang Kerja Pengurus (Modular Hub - Ringkas) -->
        <section class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden divide-y divide-slate-100">

            <!-- 1. Keuangan & Iuran Sampah -->
            <a x-show="user?.can_view_treasury"
               x-cloak
               href="/pengurus/keuangan"
               class="px-4 py-3.5 sm:px-6 sm:py-4 flex items-center justify-between gap-4 hover:bg-slate-50/90 transition group">
                <div class="flex items-center gap-3.5 sm:gap-4 min-w-0">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-700 border border-emerald-200 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z"/>
                        </svg>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 min-w-0">
                        <span class="text-sm sm:text-base font-bold text-slate-900 group-hover:text-emerald-800 transition">
                            Kas Paguyuban &amp; Iuran Sampah
                        </span>
                        <span class="px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-[10px] font-bold">
                            Bendahara &amp; Ketua
                        </span>
                    </div>
                </div>
                <span class="text-slate-400 group-hover:text-emerald-700 group-hover:translate-x-0.5 transition text-xl font-medium shrink-0">&rsaquo;</span>
            </a>

            <!-- 2. Publikasi, Agenda & Notulensi -->
            <a x-show="canManageEditorial"
               x-cloak
               href="/pengurus/publikasi"
               class="px-4 py-3.5 sm:px-6 sm:py-4 flex items-center justify-between gap-4 hover:bg-slate-50/90 transition group">
                <div class="flex items-center gap-3.5 sm:gap-4 min-w-0">
                    <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-700 border border-amber-200 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 01-2.25 2.25M16.5 7.5V18a2.25 2.25 0 002.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 002.25 2.25h13.5M6 7.5h3v3H6v-3z"/>
                        </svg>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 min-w-0">
                        <span class="text-sm sm:text-base font-bold text-slate-900 group-hover:text-amber-800 transition">
                            Publikasi, Agenda &amp; Arsip Notulensi
                        </span>
                        <span class="px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 border border-amber-200 text-[10px] font-bold">
                            Sekretaris &amp; Ketua
                        </span>
                    </div>
                </div>
                <span class="text-slate-400 group-hover:text-amber-700 group-hover:translate-x-0.5 transition text-xl font-medium shrink-0">&rsaquo;</span>
            </a>

            <!-- 2b. Pasar Tetangga & UMKM Warga (Seksi Ekonomi & Ketua) -->
            <a x-show="user?.can_manage_marketplace"
            x-cloak
            href="/pengurus/pasar-tetangga"
            class="px-4 py-3.5 sm:px-6 sm:py-4 flex items-center justify-between gap-4 hover:bg-slate-50/90 transition group">
                <div class="flex items-center gap-3.5 sm:gap-4 min-w-0">
                    <div class="w-10 h-10 rounded-xl bg-teal-50 text-teal-700 border border-teal-200 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-16.5 11.65V9.35m0 0a3.001 3.001 0 003.75-.615A2.993 2.993 0 009.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 002.25 1.016c.896 0 1.7-.393 2.25-1.016a3.001 3.001 0 003.75.614m-16.5 0a3.004 3.004 0 01-.621-4.72L4.318 3.44A1.5 1.5 0 015.378 3h13.243a1.5 1.5 0 011.06.44l1.19 1.189a3 3 0 01-.621 4.72m-13.5 8.65h3.75a.75.75 0 00.75-.75V13.5a.75.75 0 00-.75-.75H6.75a.75.75 0 00-.75.75v3.75c0 .415.336.75.75.75z" />
                        </svg>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 min-w-0">
                        <span class="text-sm sm:text-base font-bold text-slate-900 group-hover:text-teal-800 transition">
                            Kurasi Pasar Tetangga &amp; Algoritma Etalase
                        </span>
                        <span class="px-2 py-0.5 rounded-full bg-teal-50 text-teal-700 border border-teal-200 text-[10px] font-bold">
                            Seksi Ekonomi &amp; Ketua
                        </span>
                    </div>
                </div>
                <span class="text-slate-400 group-hover:text-teal-700 group-hover:translate-x-0.5 transition text-xl font-medium shrink-0">&rsaquo;</span>
            </a>

            <!-- 3. Buku Induk Kavling & Tata Kelola Jabatan -->
            <a href="/pengurus/buku-induk"
               class="px-4 py-3.5 sm:px-6 sm:py-4 flex items-center justify-between gap-4 hover:bg-slate-50/90 transition group">
                <div class="flex items-center gap-3.5 sm:gap-4 min-w-0">
                    <div class="w-10 h-10 rounded-xl bg-sky-50 text-sky-700 border border-sky-200 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25"/>
                        </svg>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 min-w-0">
                        <span class="text-sm sm:text-base font-bold text-slate-900 group-hover:text-sky-800 transition">
                            Buku Induk Kavling, Pendaftaran Tetangga &amp; Jabatan
                        </span>
                        <span class="px-2 py-0.5 rounded-full bg-sky-50 text-sky-700 border border-sky-200 text-[10px] font-bold">
                            Semua Pengurus
                        </span>
                    </div>
                </div>
                <span class="text-slate-400 group-hover:text-sky-700 group-hover:translate-x-0.5 transition text-xl font-medium shrink-0">&rsaquo;</span>
            </a>

            <!-- 4. Pemadupadanan Kontak Grup WA -->
            <a x-show="user?.is_operator_aplikasi"
               x-cloak
               href="/pengurus/pemadupadanan-kontak"
               class="px-4 py-3.5 sm:px-6 sm:py-4 flex items-center justify-between gap-4 hover:bg-slate-50/90 transition group">
                <div class="flex items-center gap-3.5 sm:gap-4 min-w-0">
                    <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-700 border border-indigo-200 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/>
                        </svg>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 min-w-0">
                        <span class="text-sm sm:text-base font-bold text-slate-900 group-hover:text-indigo-800 transition">
                            Pemadupadanan Kontak Grup WhatsApp
                        </span>
                        <span class="px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200 text-[10px] font-bold">
                            Admin Sistem
                        </span>
                    </div>
                </div>
                <span class="text-slate-400 group-hover:text-indigo-700 group-hover:translate-x-0.5 transition text-xl font-medium shrink-0">&rsaquo;</span>
            </a>

        </section>

        <!-- ================================================================= -->
        <!-- ANTREAN TINDAK LANJUT CEPAT: VERIFIKASI WARGA & NIB SHM           -->
        <!-- ================================================================= -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

            <!-- Antrean Verifikasi Sosial Warga Baru (6 Kolom) -->
            <section x-show="showVerificationPanel"
                     :class="pendingOwnerships.length > 0 ? 'lg:col-span-6' : 'lg:col-span-12'"
                     class="bg-white rounded-2xl p-5 sm:p-6 shadow-sm border border-slate-200 space-y-3.5">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Antrean Verifikasi Warga Baru</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Pengesahan klaim hunian/kepemilikan warga di wilayah cakupan Anda.</p>
                    </div>
                    <span class="px-2.5 py-0.5 rounded-full bg-amber-500 text-white text-xs font-bold shrink-0"
                          x-text="pendingApprovals.length"></span>
                </div>

                <div x-show="pendingApprovals.length === 0" class="text-xs text-slate-500 py-5 text-center bg-slate-50 rounded-xl border border-dashed border-slate-200">
                    Tidak ada antrean verifikasi warga baru saat ini.
                </div>

                <div class="space-y-2.5">
                    <template x-for="item in pendingApprovals" :key="item.membership_id">
                        <div class="bg-amber-50/60 p-3.5 rounded-xl border border-amber-200 flex items-center justify-between gap-2">
                            <div class="text-xs space-y-1 min-w-0">
                                <div class="font-bold text-slate-900" x-text="item.name + ' • Unit ' + item.unit_code"></div>
                                <div class="text-slate-600"
                                     x-text="(item.phone ? 'WA: ' + item.phone : 'Username: @' + (item.username || '-')) + ' (' + item.sub_role.replace('_', ' ') + ')'">
                                </div>
                                <template x-if="item.guarantor_name">
                                    <div class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-emerald-100/80 border border-emerald-200 text-[11px] font-semibold text-emerald-900">
                                        <span>Penjamin:</span>
                                        <span x-text="item.guarantor_name"></span>
                                    </div>
                                </template>
                            </div>
                            <button type="button" @click="approveResident(item)"
                                    class="px-3 py-1.5 rounded-lg bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-semibold shrink-0 cursor-pointer">
                                ✓ Sahkan
                            </button>
                        </div>
                    </template>
                </div>
            </section>

            <!-- Antrean Pengesahan Bukti Kepemilikan NIB SHM (6 Kolom) -->
            <section x-show="pendingOwnerships.length > 0"
                     :class="showVerificationPanel ? 'lg:col-span-6' : 'lg:col-span-12'"
                     class="bg-white rounded-2xl p-5 sm:p-6 shadow-sm border-2 border-amber-300 space-y-3.5">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Pengesahan Bukti Kepemilikan (NIB SHM)</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Tambahkan pengesahan jabatan Anda berdasarkan kecocokan data pemilik.</p>
                    </div>
                    <span class="px-2.5 py-0.5 rounded-full bg-amber-500 text-slate-950 text-xs font-extrabold shrink-0"
                          x-text="pendingOwnerships.length"></span>
                </div>

                <div class="space-y-3">
                    <template x-for="item in pendingOwnerships" :key="item.membership_id">
                        <div class="p-3.5 rounded-xl bg-amber-50/70 border border-amber-300 space-y-2.5">
                            <div class="space-y-1 text-xs">
                                <div class="flex items-center justify-between gap-2">
                                    <div class="flex items-center gap-2">
                                        <span class="px-2 py-0.5 rounded-lg bg-slate-900 text-white font-extrabold"
                                              x-text="item.unit_code"></span>
                                        <span class="font-bold text-slate-900 text-sm" x-text="item.name"></span>
                                    </div>
                                    <span class="text-[11px] text-slate-500" x-text="item.submitted_at"></span>
                                </div>

                                <div class="text-slate-600"
                                     x-text="item.owner_role_label + ' • ' + item.zone + ' • Kontak: ' + item.contact"></div>

                                <div class="p-2 rounded-lg bg-white border border-amber-200 text-xs text-slate-800 flex items-center justify-between gap-2">
                                    <span>
                                        Berkas NIB SHM:
                                        <strong class="font-mono text-slate-900"
                                                x-text="item.can_see_raw_nib ? item.nib_shm_plain : item.nib_shm_masked"></strong>
                                    </span>
                                    <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-600 text-[10px] font-semibold shrink-0"
                                          x-text="item.can_see_raw_nib ? 'Akses Legal' : 'Rahasia Tertutup'"></span>
                                </div>

                                <div x-show="item.endorsed_by_sentence"
                                     class="p-2 rounded-lg bg-emerald-50 border border-emerald-200 text-[11px] font-semibold text-emerald-900">
                                    &#10003; <span x-text="item.endorsed_by_sentence"></span>
                                </div>
                            </div>

                            <div class="flex items-center justify-end pt-1 border-t border-amber-200/70">
                                <button type="button"
                                        @click="verifyUnitOwnership(item.unit_code, item, 'approve_shm')"
                                        class="px-3.5 py-1.5 rounded-lg bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold shadow-xs transition cursor-pointer">
                                    <span x-text="item.endorsement_count > 0 ? '+ Tambahkan Pengesahan Saya' : '✓ Sahkan NIB SHM'"></span>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </section>

        </div>
    </div>
</div>

@push('scripts')
<script>
function ppmtAdminHub() {
    return {
        token: localStorage.getItem('ppmt_token'),
        user: null,
        loading: true,
        toastMessage: '',
        toastType: 'success',
        registryStats: null,
        pendingApprovals: [],
        pendingOwnerships: [],

        get canManageEditorial() {
            return Boolean(this.user?.can_manage_announcements || this.user?.is_global_governor);
        },

        get showVerificationPanel() {
            if (!this.user) return false;
            if (this.user.is_global_governor) return true;
            const roles = (this.user.governance_roles || []).map(r => r.role_code);
            return roles.includes('koordinator_zona') || roles.includes('koordinator_blok') || this.pendingApprovals.length > 0;
        },

        async init() {
            if (!this.token) {
                window.location.href = '/';
                return;
            }
            await this.loadMe();
            if (!this.user?.has_admin_access) {
                window.location.href = '/dashboard';
                return;
            }

            await Promise.all([
                this.loadStatsSummary(),
                this.loadPendingApprovals(),
                this.loadPendingOwnerships()
            ]);
        },

        headers() {
            return {
                'Authorization': 'Bearer ' + this.token,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            };
        },

        async loadMe() {
            try {
                const res = await fetch('/api/v1/me', { headers: this.headers() });
                if (!res.ok) throw new Error('Unauthorized');
                const json = await res.json();
                this.user = json.data;
            } catch (e) {
                localStorage.removeItem('ppmt_token');
                window.location.href = '/';
            } finally {
                this.loading = false;
            }
        },

        async loadStatsSummary() {
            const res = await fetch('/api/v1/governance/occupancy-registry', { headers: this.headers() });
            if (res.ok) {
                const json = await res.json();
                this.registryStats = json.data?.stats || null;
            }
        },

        async loadPendingApprovals() {
            const res = await fetch('/api/v1/verifications/pending', { headers: this.headers() });
            if (res.ok) {
                const json = await res.json();
                this.pendingApprovals = json.data || [];
            }
        },

        async loadPendingOwnerships() {
            const res = await fetch('/api/v1/verifications/ownership-pending', { headers: this.headers() });
            if (res.ok) {
                const json = await res.json();
                this.pendingOwnerships = json.data || [];
            }
        },

        async approveResident(item) {
            const res = await fetch('/api/v1/verifications/approve', {
                method: 'POST',
                headers: this.headers(),
                body: JSON.stringify({
                    target_user_id: item.target_user_id,
                    unit_code: item.unit_code
                })
            });
            const json = await res.json();
            if (res.ok) {
                this.toastType = 'success';
                this.toastMessage = json.message;
                await Promise.all([this.loadPendingApprovals(), this.loadStatsSummary()]);
            } else {
                this.toastType = 'error';
                this.toastMessage = json.message || 'Gagal mengesahkan warga.';
            }
        },

        async verifyUnitOwnership(unitCode, member, action) {
            let nibInput = '';

            if (action === 'approve_shm') {
                if (member.can_see_raw_nib) {
                    const entered = prompt(
                        `Konfirmasi / masukkan Nomor NIB SHM untuk ${member.name} (Kavling ${unitCode}):`,
                        member.nib_shm_plain || ''
                    );
                    if (entered === null) return;
                    nibInput = entered.trim();
                } else {
                    if (!confirm(`Sahkan bukti kepemilikan NIB SHM untuk ${member.name} pada Kavling ${unitCode} atas nama jabatan Anda?`)) {
                        return;
                    }
                }
            }

            const res = await fetch(`/api/v1/verifications/ownership/${member.membership_id}`, {
                method: 'POST',
                headers: this.headers(),
                body: JSON.stringify({
                    action: action,
                    nib_shm: nibInput || null
                })
            });
            const json = await res.json();
            if (res.ok) {
                this.toastType = 'success';
                this.toastMessage = json.message;
                await Promise.all([this.loadPendingOwnerships(), this.loadStatsSummary()]);
            } else {
                this.toastType = 'error';
                this.toastMessage = json.message || 'Gagal memproses pengesahan NIB SHM.';
            }
        }
    }
}
</script>
@endpush
@endsection
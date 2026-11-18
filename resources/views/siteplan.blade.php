@extends('layouts.app')

@section('title', 'Peta 91 Kavling PPMT — PPMT Digital')
@section('subpage_title', 'Peta Kavling')
@section('subpage_back_url', '/komunitas')

@section('content')
<div x-data="ppmtSiteplan()" x-init="init()" @keydown.escape.window="selectedUnit = null" class="max-w-7xl mx-auto px-4 py-6 sm:py-8 space-y-6">

    <!-- Loading State -->
    <div x-show="loading" class="bg-white rounded-2xl p-12 text-center text-slate-500 border border-slate-200">
        Memuat pemetaan kavling internal PPMT...
    </div>

    <div x-show="!loading && me" x-cloak class="space-y-6">

        <!-- Notifikasi -->
        <div x-show="toastMessage"
             :class="toastType === 'error' ? 'bg-rose-50 text-rose-800 border-rose-200' : 'bg-emerald-50 text-emerald-800 border-emerald-200'"
             class="p-4 rounded-xl border text-xs font-medium flex justify-between items-center shadow-sm">
            <span x-text="toastMessage"></span>
            <button type="button" @click="toastMessage = ''" class="cursor-pointer">✕</button>
        </div>

        <!-- Header & Ringkasan Spasial -->
        <section class="bg-emerald-950 text-white rounded-2xl p-5 sm:p-6 shadow-sm space-y-4">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 border-b border-emerald-800/80 pb-4">
                <div class="space-y-1">
                    <div class="inline-flex items-center gap-2 px-2.5 py-0.5 rounded-full bg-emerald-800/80 text-emerald-200 text-[11px] font-bold">
                        <span>&#128274; Area Terproteksi Warga Terverifikasi PPMT</span>
                    </div>
                    <h1 class="text-xl sm:text-2xl font-extrabold">Peta Kavling &amp; Tata Ruang Lingkungan (<span x-text="stats?.total_units ?? 0"></span> Unit)</h1>
                    <p class="text-xs text-emerald-200">
                        Klik salah satu nomor kavling untuk melihat informasi hunian, lapak usaha tetangga, serta status koordinasi wilayah.
                    </p>
                </div>

                <!-- Mode Lapisan (Layer Switcher) -->
                <div class="flex flex-wrap items-center gap-1.5 bg-emerald-900/90 p-1.5 rounded-xl border border-emerald-800 shrink-0">
                    <button type="button"
                            @click="mapLayer = 'occupancy'"
                            :class="mapLayer === 'occupancy' ? 'bg-amber-400 text-slate-950 shadow' : 'text-emerald-100 hover:bg-emerald-800/70'"
                            class="px-3 py-1.5 rounded-lg text-xs font-bold transition cursor-pointer">
                        Lapisan Hunian
                    </button>
                    <button type="button"
                            @click="mapLayer = 'umkm'"
                            :class="mapLayer === 'umkm' ? 'bg-amber-400 text-slate-950 shadow' : 'text-emerald-100 hover:bg-emerald-800/70'"
                            class="px-3 py-1.5 rounded-lg text-xs font-bold transition cursor-pointer">
                        Pasar Tetangga
                    </button>
                    <button x-show="viewerContext?.can_view_treasury"
                            type="button"
                            @click="mapLayer = 'treasury'"
                            :class="mapLayer === 'treasury' ? 'bg-amber-400 text-slate-950 shadow' : 'text-emerald-100 hover:bg-emerald-800/70'"
                            class="px-3 py-1.5 rounded-lg text-xs font-bold transition cursor-pointer">
                        Iuran Sampah
                    </button>
                </div>
            </div>

            <!-- Strip Statistik Cepat -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div class="bg-emerald-900/70 rounded-xl px-4 py-3 border border-emerald-800">
                    <div class="text-[11px] text-emerald-300 font-semibold">Kavling Terisi</div>
                    <div class="text-lg sm:text-xl font-extrabold text-white mt-0.5">
                        <span x-text="stats?.occupied_units ?? 0"></span>
                        <span class="text-xs font-normal text-emerald-300">unit</span>
                    </div>
                </div>

                <div class="bg-emerald-900/70 rounded-xl px-4 py-3 border border-emerald-800">
                    <div class="text-[11px] text-emerald-300 font-semibold">Belum Terklaim</div>
                    <div class="text-lg sm:text-xl font-extrabold text-slate-200 mt-0.5">
                        <span x-text="stats?.unclaimed_units ?? 0"></span>
                        <span class="text-xs font-normal text-emerald-300">unit</span>
                    </div>
                </div>

                <div class="bg-emerald-900/70 rounded-xl px-4 py-3 border border-emerald-800">
                    <div class="text-[11px] text-emerald-300 font-semibold">Antrean Verifikasi</div>
                    <div class="text-lg sm:text-xl font-extrabold text-amber-300 mt-0.5">
                        <span x-text="stats?.pending_units ?? 0"></span>
                        <span class="text-xs font-normal text-emerald-300">unit</span>
                    </div>
                </div>

                <div class="bg-emerald-900/70 rounded-xl px-4 py-3 border border-emerald-800">
                    <div class="text-[11px] text-emerald-300 font-semibold">Kavling Lapak Buka</div>
                    <div class="text-lg sm:text-xl font-extrabold text-sky-300 mt-0.5">
                        <span x-text="stats?.active_umkm_units ?? 0"></span>
                        <span class="text-xs font-normal text-emerald-300">lapak</span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Banner Batas Cakupan Koordinator Wilayah -->
        <div x-show="viewerContext?.is_scoped_coordinator"
             x-cloak
             class="bg-indigo-50 border border-indigo-200 rounded-2xl px-4 py-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs text-indigo-950">
            <div class="flex items-center gap-2.5">
                <span class="px-2.5 py-1 rounded-lg bg-indigo-700 text-white font-extrabold shrink-0">
                    Mode Wilayah Tugas
                </span>
                <span>
                    Anda masuk sebagai koordinator wilayah (<strong x-text="(viewerContext?.active_scopes || []).join(', ')"></strong>). Hanya kotak kavling di dalam wilayah cakupan Anda (serta kavling pribadi &amp; fasum) yang aktif dan dapat diinspeksi.
                </span>
            </div>
        </div>

        <!-- Bar Filter & Pencarian -->
        <section class="bg-white rounded-2xl p-4 border border-slate-200 shadow-sm flex flex-col lg:flex-row lg:items-center justify-between gap-3">
            <div class="grid grid-cols-2 sm:flex sm:flex-wrap items-center gap-2">
                <input type="text"
                       x-model="searchQuery"
                       placeholder="Cari kode kavling atau nama..."
                       class="col-span-2 sm:w-52 rounded-xl border-slate-300 text-xs focus:border-emerald-600 focus:ring-emerald-600">

                <select x-model="filterBlock" class="rounded-xl border-slate-300 text-xs">
                    <option value="">Semua Blok</option>
                    <option value="R">Blok R (Ruko)</option>
                    <option value="A">Blok A</option>
                    <option value="B">Blok B</option>
                    <option value="C">Blok C</option>
                    <option value="D">Blok D</option>
                    <option value="FASUM">Fasilitas Umum</option>
                </select>

                <select x-model="filterZone" class="rounded-xl border-slate-300 text-xs">
                    <option value="">Semua Zona</option>
                    <option value="ZONA-1">ZONA-1</option>
                    <option value="ZONA-2">ZONA-2</option>
                    <option value="ZONA-3">ZONA-3</option>
                    <option value="ZONA-4">ZONA-4</option>
                </select>

                <select x-model="filterStatus" class="col-span-2 sm:col-span-1 rounded-xl border-slate-300 text-xs">
                    <option value="">Semua Kondisi</option>
                    <option value="filled">Terisi (Dihuni / Disewa)</option>
                    <option value="unclaimed">Belum Terklaim</option>
                    <option value="pending">Menunggu Verifikasi</option>
                    <option value="umkm">Memiliki Lapak Buka</option>
                    <option x-show="viewerContext?.can_view_treasury" value="dues_unpaid">Iuran Sampah Belum Lunas</option>
                </select>
            </div>

            <!-- Legenda Dinamis Sesuai Layer Aktif -->
            <div class="flex flex-wrap items-center gap-3 text-[11px] text-slate-600">
                <template x-if="mapLayer === 'occupancy'">
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-emerald-100 border border-emerald-400"></span> Dihuni</span>
                        <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-sky-100 border border-sky-400"></span> Disewa</span>
                        <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-amber-100 border border-amber-400"></span> Menunggu</span>
                        <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-slate-100 border border-slate-300"></span> Belum Klaim</span>
                        <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-teal-100 border border-teal-400"></span> Fasum</span>
                    </div>
                </template>

                <template x-if="mapLayer === 'umkm'">
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-amber-200 border border-amber-500"></span> Ada Lapak Buka / PO</span>
                        <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-slate-100 border border-slate-300"></span> Non-Lapak</span>
                    </div>
                </template>

                <template x-if="mapLayer === 'treasury'">
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-emerald-100 border border-emerald-400"></span> Lunas (<span x-text="viewerContext?.current_month"></span>)</span>
                        <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-rose-100 border border-rose-400"></span> Belum Lunas</span>
                        <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-slate-100 border border-slate-300"></span> Kosong / Bebas</span>
                    </div>
                </template>
            </div>
        </section>

        <!-- Konten Utama: Grid Peta (Kiri/Atas) + Inspector Detail Unit (Kanan/Bawah) -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

            <!-- Kolom Peta Kavling -->
            <div :class="selectedUnit ? 'lg:col-span-8' : 'lg:col-span-12'"
                 class="bg-white rounded-2xl p-5 sm:p-6 border border-slate-200 shadow-sm space-y-6 transition-all">

                <div x-show="filteredUnits.length === 0"
                     class="p-10 rounded-xl bg-slate-50 border border-dashed border-slate-300 text-center text-xs text-slate-500">
                    Tidak ada kavling yang sesuai dengan filter pencarian saat ini.
                </div>

                <template x-for="(groupUnits, blockName) in groupedFilteredUnits" :key="blockName">
                    <div class="space-y-2.5">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-1.5">
                            <h2 class="text-xs font-extrabold uppercase tracking-wider text-slate-700"
                                x-text="formatBlockTitle(blockName)"></h2>
                            <span class="text-[11px] font-semibold text-slate-400" x-text="groupUnits.length + ' unit'"></span>
                        </div>

                        <div :class="selectedUnit ? 'grid-cols-4 sm:grid-cols-6 md:grid-cols-8' : 'grid-cols-4 sm:grid-cols-7 md:grid-cols-10'"
                             class="grid gap-2">
                            <template x-for="unit in groupUnits" :key="unit.id">
                                <button type="button"
                                        :disabled="!unit.is_interactive"
                                        @click="selectUnit(unit)"
                                        :title="unit.is_interactive ? ('Klik untuk detail Kavling ' + unit.code) : ('Kavling ' + unit.code + ' berada di luar wilayah tugas Anda')"
                                        :class="[
                                            getUnitTileClass(unit),
                                            !unit.is_interactive
                                                ? 'opacity-40 cursor-not-allowed grayscale'
                                                : (selectedUnit?.id === unit.id
                                                    ? 'ring-2 ring-indigo-600 ring-offset-1 scale-[1.03] cursor-pointer'
                                                    : 'hover:brightness-95 cursor-pointer')
                                        ]"
                                        class="relative px-2 py-2.5 rounded-xl border text-xs text-center flex flex-col items-center justify-center transition select-none">

                                    <!-- Badge Rumah Sendiri -->
                                    <span x-show="unit.is_my_unit"
                                          title="Kavling Anda"
                                          class="absolute -top-1.5 -left-1.5 px-1.5 py-0.2 rounded-full bg-indigo-700 text-white text-[8px] font-extrabold shadow-xs">
                                        KU
                                    </span>

                                    <!-- Badge Lapak UMKM Aktif -->
                                    <span x-show="unit.active_stores_count > 0"
                                          title="Memiliki Lapak Pasar Tetangga Buka"
                                          class="absolute -top-1.5 -right-1.5 w-4 h-4 rounded-full bg-amber-400 text-slate-950 text-[9px] font-extrabold flex items-center justify-center shadow-xs">
                                        ★
                                    </span>

                                    <!-- Badge Antrean Verifikasi untuk Pengurus/Penjamin -->
                                    <span x-show="unit.pending_count > 0 && unit.can_manage_unit && unit.active_stores_count === 0"
                                          title="Ada pendaftar menunggu verifikasi"
                                          class="absolute -top-1.5 -right-1.5 w-4 h-4 rounded-full bg-rose-600 text-white text-[9px] font-extrabold flex items-center justify-center shadow-xs">
                                        !
                                    </span>

                                    <span class="font-extrabold tracking-tight" x-text="unit.code"></span>
                                    <span class="text-[9px] opacity-75 mt-0.5" x-text="unit.zone.replace('ZONA-', 'Z')"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Kolom Inspector Detail Kavling (Muncul saat kavling diklik) -->
            <aside x-show="selectedUnit !== null"
                   x-transition
                   class="lg:col-span-4 bg-white rounded-2xl border border-slate-200 shadow-md overflow-hidden sticky top-20">

                <div class="px-5 py-4 bg-slate-900 text-white flex items-center justify-between gap-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="text-lg font-extrabold" x-text="'Kavling ' + selectedUnit?.code"></span>
                            <span x-show="selectedUnit?.is_my_unit"
                                  class="px-2 py-0.5 rounded-full bg-indigo-500 text-white text-[10px] font-bold">
                                Kavling Anda
                            </span>
                        </div>
                        <p class="text-[11px] text-slate-300 mt-0.5"
                           x-text="formatBlockTitle(selectedUnit?.block || '') + ' • ' + (selectedUnit?.zone || '')"></p>
                    </div>
                    <button type="button"
                            @click="selectedUnit = null"
                            class="w-7 h-7 rounded-lg bg-white/10 hover:bg-white/20 text-white text-xs flex items-center justify-center cursor-pointer">
                        ✕
                    </button>
                </div>

                <div class="p-5 space-y-5 max-h-[78vh] overflow-y-auto">

                    <!-- Status Hunian & Iuran -->
                    <div class="flex flex-wrap items-center gap-2">
                        <span :class="{
                                'bg-emerald-100 text-emerald-800 border-emerald-200': selectedUnit?.occupancy_status === 'occupied',
                                'bg-sky-100 text-sky-800 border-sky-200': selectedUnit?.occupancy_status === 'rented',
                                'bg-amber-100 text-amber-900 border-amber-300': selectedUnit?.occupancy_status === 'pending',
                                'bg-teal-100 text-teal-800 border-teal-200': selectedUnit?.unit_type === 'fasum',
                                'bg-slate-100 text-slate-700 border-slate-300': selectedUnit?.occupancy_status === 'unclaimed' && selectedUnit?.unit_type !== 'fasum'
                              }"
                              class="px-2.5 py-1 rounded-full text-xs font-bold border"
                              x-text="selectedUnit?.unit_type === 'fasum' ? 'Fasilitas Umum Lingkungan' : formatUnitStatus(selectedUnit?.occupancy_status)">
                        </span>

                        <template x-if="viewerContext?.can_view_treasury && selectedUnit?.unit_type !== 'fasum'">
                            <span :class="{
                                    'bg-emerald-50 text-emerald-700 border-emerald-200': selectedUnit?.dues_status === 'paid',
                                    'bg-rose-50 text-rose-700 border-rose-200': selectedUnit?.dues_status === 'unpaid',
                                    'bg-slate-100 text-slate-600 border-slate-200': !['paid', 'unpaid'].includes(selectedUnit?.dues_status)
                                  }"
                                  class="px-2.5 py-1 rounded-full text-xs font-bold border"
                                  x-text="'Iuran Sampah: ' + formatDuesStatus(selectedUnit?.dues_status)">
                            </span>
                        </template>
                    </div>

                    <!-- Visualisasi Spasial Pemilik vs Penghuni -->
                    <div x-show="selectedUnit?.unit_type !== 'fasum'" class="space-y-3">
                        <div class="flex items-center justify-between">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">Peta Relasi Kepemilikan &amp; Hunian</h3>
                            <span x-show="selectedUnit?.can_manage_unit"
                                  class="text-[10px] font-bold text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded">
                                Pengurus Wilayah
                            </span>
                        </div>

                        <!-- Legenda Mikro Ikon Berkas vs Bingkai -->
                        <div class="flex flex-wrap items-center gap-3 text-[10px] text-slate-500 bg-slate-50 px-3 py-2 rounded-xl border border-slate-200/80">
                            <span class="inline-flex items-center gap-1 font-semibold text-amber-800">
                                <span class="w-4 h-4 rounded bg-amber-400 text-slate-950 inline-flex items-center justify-center shadow-2xs">
                                    <svg class="w-2.5 h-2.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </span>
                                Status Pemilik
                            </span>
                            <span class="inline-flex items-center gap-1 font-semibold text-emerald-800">
                                <span class="w-3.5 h-3.5 rounded border-2 border-emerald-600 bg-emerald-50 inline-block"></span>
                                Di Dalam Bingkai = Menghuni
                            </span>
                        </div>

                        <div x-show="(selectedUnit?.members?.length || 0) === 0"
                             class="p-4 rounded-xl bg-slate-50 border border-dashed border-slate-300 text-xs text-slate-500 text-center">
                            Belum ada warga yang terdata pada kavling ini.
                        </div>

                        <template x-if="(selectedUnit?.members?.length || 0) > 0">
                            <div class="space-y-3">

                                <!-- ZONA 1 (LUAR ATAS): Pemilik Tidak Menghuni Kavling -->
                                <div x-show="unitRolesBreakdown.externalOwners.length > 0" class="space-y-1.5">
                                    <div class="flex items-center justify-between text-[10px] font-bold uppercase tracking-wider text-amber-800 px-1">
                                        <span>Pemilik (Di Luar Kavling)</span>
                                        <span>Tidak Menghuni</span>
                                    </div>

                                    <div class="space-y-2">
                                        <template x-for="m in unitRolesBreakdown.externalOwners" :key="m.membership_id">
                                            <div :class="m.is_approved ? 'bg-amber-50/60 border-amber-300' : 'bg-amber-100/80 border-amber-400'"
                                                 class="relative p-3 rounded-xl border text-xs space-y-1.5 shadow-2xs">
                                                <!-- Ikon Berkas Kepemilikan di Sudut Kartu -->
                                                <span title="Berkas Kepemilikan Kavling"
                                                      class="absolute -top-2 -right-2 w-6 h-6 rounded-lg bg-amber-400 border border-amber-500 text-slate-950 flex items-center justify-center shadow-xs">
                                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.3">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                    </svg>
                                                </span>

                                                <div class="flex items-start justify-between gap-2 pr-5">
                                                    <div>
                                                        <div class="font-bold text-slate-900" x-text="m.name"></div>
                                                        <div class="text-[11px] text-amber-900 font-medium" x-text="m.sub_role_label"></div>
                                                    </div>
                                                    <span :class="m.is_approved ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-200 text-amber-950'"
                                                          class="px-2 py-0.5 rounded-full text-[10px] font-bold shrink-0"
                                                          x-text="m.is_approved ? 'Terverifikasi' : 'Menunggu'">
                                                    </span>
                                                </div>

                                                <div class="flex items-center justify-between gap-2 pt-0.5">
                                                    <template x-if="m.phone">
                                                        <a :href="'https://wa.me/' + m.phone.replace(/^0/, '62').replace(/[^0-9]/g, '')"
                                                           target="_blank"
                                                           class="text-[11px] font-semibold text-emerald-700 hover:underline">
                                                            WA: <span x-text="m.phone"></span> ↗
                                                        </a>
                                                    </template>
                                                    <template x-if="!m.phone">
                                                        <span class="text-[11px] text-slate-400 italic">Kontak privat</span>
                                                    </template>

                                                    <button x-show="!m.is_approved && selectedUnit?.can_manage_unit"
                                                            type="button"
                                                            :disabled="approvingMembershipId === m.membership_id"
                                                            @click="approveMemberOnUnit(selectedUnit, m)"
                                                            class="px-2.5 py-1 rounded-lg bg-emerald-700 hover:bg-emerald-800 text-white text-[11px] font-bold transition cursor-pointer">
                                                        <span x-show="approvingMembershipId !== m.membership_id">✓ Sahkan</span>
                                                        <span x-show="approvingMembershipId === m.membership_id">...</span>
                                                    </button>
                                                </div>
                                            </div>
                                        </template>
                                    </div>

                                    <!-- Garis Penghubung Hak Milik ke Bingkai Kavling -->
                                    <div class="flex justify-center">
                                        <div class="h-3 border-l-2 border-dashed border-amber-400"></div>
                                    </div>
                                </div>

                                <!-- ZONA 2 (TENGAH): Bingkai Fisik Kavling (Semua yang Menghuni) -->
                                <div :class="unitRolesBreakdown.insideOccupants.length > 0 ? 'border-emerald-600 bg-emerald-50/35' : 'border-dashed border-slate-300 bg-slate-50/70'"
                                     class="rounded-2xl border-2 p-3.5 space-y-2.5">
                                    <div class="flex items-center justify-between border-b border-slate-200/80 pb-1.5">
                                        <span class="text-[11px] font-extrabold text-slate-800 flex items-center gap-1.5">
                                            <span class="w-2 h-2 rounded-full"
                                                  :class="unitRolesBreakdown.insideOccupants.length > 0 ? 'bg-emerald-600' : 'bg-slate-400'"></span>
                                            <span>Bingkai Kavling <span x-text="selectedUnit?.code"></span></span>
                                        </span>
                                        <span class="text-[10px] font-semibold text-slate-500"
                                              x-text="unitRolesBreakdown.insideOccupants.length > 0 ? (unitRolesBreakdown.insideOccupants.length + ' penghuni di dalam') : 'Kosong'"></span>
                                    </div>

                                    <div x-show="unitRolesBreakdown.insideOccupants.length === 0"
                                         class="py-3 text-center text-[11px] text-slate-500 italic">
                                        Belum ada penghuni yang tinggal di dalam kavling ini.
                                    </div>

                                    <div class="space-y-2.5">
                                        <template x-for="m in unitRolesBreakdown.insideOccupants" :key="m.membership_id">
                                            <div :class="m.is_approved ? 'bg-white border-slate-200' : 'bg-amber-50 border-amber-300'"
                                                 class="relative p-3 rounded-xl border text-xs space-y-1.5 shadow-2xs">

                                                <!-- Ikon Berkas HANYA muncul jika penghuni ini juga berstatus Pemilik -->
                                                <span x-show="m.hasOwnerDocument"
                                                      title="Pemilik Sekaligus Penghuni Kavling"
                                                      class="absolute -top-2 -right-2 w-6 h-6 rounded-lg bg-amber-400 border border-amber-500 text-slate-950 flex items-center justify-center shadow-xs">
                                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.3">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                    </svg>
                                                </span>

                                                <div class="flex items-start justify-between gap-2" :class="m.hasOwnerDocument ? 'pr-5' : ''">
                                                    <div>
                                                        <div class="font-bold text-slate-900" x-text="m.name"></div>
                                                        <div class="text-[11px] text-slate-500" x-text="m.sub_role_label"></div>
                                                    </div>
                                                    <span :class="m.is_approved ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-200 text-amber-950'"
                                                          class="px-2 py-0.5 rounded-full text-[10px] font-bold shrink-0"
                                                          x-text="m.is_approved ? 'Terverifikasi' : 'Menunggu'">
                                                    </span>
                                                </div>

                                                <div class="flex items-center justify-between gap-2 pt-0.5">
                                                    <template x-if="m.phone">
                                                        <a :href="'https://wa.me/' + m.phone.replace(/^0/, '62').replace(/[^0-9]/g, '')"
                                                           target="_blank"
                                                           class="text-[11px] font-semibold text-emerald-700 hover:underline">
                                                            WA: <span x-text="m.phone"></span> ↗
                                                        </a>
                                                    </template>
                                                    <template x-if="!m.phone">
                                                        <span class="text-[11px] text-slate-400 italic">Kontak privat</span>
                                                    </template>

                                                    <button x-show="!m.is_approved && selectedUnit?.can_manage_unit"
                                                            type="button"
                                                            :disabled="approvingMembershipId === m.membership_id"
                                                            @click="approveMemberOnUnit(selectedUnit, m)"
                                                            class="px-2.5 py-1 rounded-lg bg-emerald-700 hover:bg-emerald-800 text-white text-[11px] font-bold transition cursor-pointer">
                                                        <span x-show="approvingMembershipId !== m.membership_id">✓ Sahkan Warga</span>
                                                        <span x-show="approvingMembershipId === m.membership_id">Mengesahkan...</span>
                                                    </button>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                <!-- ZONA 3 (LUAR BAWAH): Warga Terverifikasi dengan Privasi Peran Tertutup -->
                                <div x-show="unitRolesBreakdown.privateMembers.length > 0"
                                     class="pt-2 border-t border-dashed border-slate-200 space-y-2">
                                    <div class="space-y-0.5 px-1">
                                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500">
                                            Warga Terverifikasi (Status Peran Diprivasi)
                                        </div>
                                        <p class="text-[10px] text-slate-400 leading-relaxed">
                                            Warga di bawah ini terhubung resmi dengan kavling <span x-text="selectedUnit?.code"></span>, namun menyembunyikan detail status kepemilikan maupun huniannya dari publik.
                                        </p>
                                    </div>

                                    <div class="space-y-1.5">
                                        <template x-for="m in unitRolesBreakdown.privateMembers" :key="m.membership_id">
                                            <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-200 text-xs flex items-center justify-between gap-2">
                                                <div>
                                                    <div class="font-bold text-slate-800" x-text="m.name"></div>
                                                    <div class="text-[10px] text-slate-500" x-text="m.sub_role_label"></div>
                                                </div>
                                                <template x-if="m.phone">
                                                    <a :href="'https://wa.me/' + m.phone.replace(/^0/, '62').replace(/[^0-9]/g, '')"
                                                       target="_blank"
                                                       class="text-[11px] font-semibold text-emerald-700 hover:underline shrink-0">
                                                        WA ↗
                                                    </a>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                            </div>
                        </template>
                    </div>

                    <!-- Lapak Pasar Tetangga di Kavling Ini -->
                    <div x-show="(selectedUnit?.active_stores?.length || 0) > 0" class="space-y-2">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-amber-700">Lapak Pasar Tetangga Buka</h3>
                        <div class="space-y-2">
                            <template x-for="st in (selectedUnit?.active_stores || [])" :key="st.id">
                                <div class="p-3 rounded-xl bg-amber-50/70 border border-amber-200 flex items-center justify-between gap-2 text-xs">
                                    <div>
                                        <div class="font-bold text-slate-900" x-text="st.store_name"></div>
                                        <div class="text-[11px] text-slate-600 capitalize" x-text="st.category.replace('_', ' ')"></div>
                                    </div>
                                    <a href="/komunitas?tab=umkm"
                                       class="px-2.5 py-1.5 rounded-lg bg-emerald-700 hover:bg-emerald-800 text-white text-[11px] font-bold shrink-0">
                                        Lihat Lapak
                                    </a>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Aksi Cepat Pengurus Pusat untuk Kavling Belum Terklaim -->
                    <div x-show="viewerContext?.is_global_governor && selectedUnit?.unit_type !== 'fasum' && selectedUnit?.occupancy_status === 'unclaimed'"
                         class="pt-3 border-t border-slate-100">
                        <a :href="'/pengurus?prefill_unit=' + encodeURIComponent(selectedUnit?.code || '') + '#assisted-register'"
                           class="w-full py-2.5 px-4 rounded-xl bg-indigo-700 hover:bg-indigo-800 text-white text-xs font-bold flex items-center justify-center gap-1.5 transition">
                            <span>+ Daftarkan Tetangga di Kavling <span x-text="selectedUnit?.code"></span></span>
                        </a>
                    </div>
                </div>
            </aside>

        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function ppmtSiteplan() {
    const urlParams = new URLSearchParams(window.location.search);
    const initialUnitCode = (urlParams.get('unit') || '').trim().toUpperCase();

    return {
        token: localStorage.getItem('ppmt_token'),
        me: null,
        loading: true,
        units: [],
        stats: null,
        viewerContext: null,
        selectedUnit: null,
        mapLayer: 'occupancy',
        searchQuery: '',
        filterBlock: '',
        filterZone: '',
        filterStatus: '',
        approvingMembershipId: null,
        toastMessage: '',
        toastType: 'success',

        async init() {
            if (!this.token) {
                window.location.href = '/';
                return;
            }

            try {
                const meRes = await fetch('/api/v1/me', { headers: this.headers() });
                if (!meRes.ok) {
                    window.location.href = '/';
                    return;
                }
                const meJson = await meRes.json();
                this.me = meJson.data;

                // Blokir total akses bagi akun terdaftar yang belum terverifikasi
                if (!this.me?.is_verified) {
                    window.location.href = '/dashboard';
                    return;
                }

                await this.loadSiteplan(initialUnitCode);
            } finally {
                this.loading = false;
            }
        },

        headers() {
            return {
                'Authorization': 'Bearer ' + this.token,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            };
        },

        async loadSiteplan(preselectCode = '') {
            const res = await fetch('/api/v1/siteplan/units', { headers: this.headers() });
            if (!res.ok) {
                window.location.href = '/dashboard';
                return;
            }
            const json = await res.json();
            this.units = json.data || [];
            this.stats = json.stats || null;
            this.viewerContext = json.viewer_context || null;

            if (preselectCode) {
                const found = this.units.find(u => u.code.toUpperCase() === preselectCode && u.is_interactive);
                if (found) {
                    this.selectedUnit = found;
                }
            } else if (this.selectedUnit) {
                this.selectedUnit = this.units.find(u => u.id === this.selectedUnit.id && u.is_interactive) || null;
            }
        },

        selectUnit(unit) {
            if (!unit || !unit.is_interactive) return;
            this.selectedUnit = this.selectedUnit?.id === unit.id ? null : unit;
            const url = new URL(window.location);
            if (this.selectedUnit) {
                url.searchParams.set('unit', this.selectedUnit.code);
            } else {
                url.searchParams.delete('unit');
            }
            window.history.replaceState({}, '', url);
        },

        get unitRolesBreakdown() {
            const unit = this.selectedUnit;
            if (!unit || !Array.isArray(unit.members)) {
                return { externalOwners: [], insideOccupants: [], privateMembers: [] };
            }

            const externalOwners = [];
            const insideOccupants = [];
            const privateMembers = [];

            const explicitResidentUserIds = new Set(
                unit.members
                    .filter(m => m.entity_type === 'resident')
                    .map(m => m.user_id)
            );

            const hasExternalTenantOrResident = unit.members.some(m =>
                m.entity_type === 'resident' && ['tenant', 'primary_resident'].includes(m.sub_role)
            );

            for (const m of unit.members) {
                // 1. Warga yang menyembunyikan status kepemilikan/huniannya (entity_type === 'member')
                if (m.entity_type === 'member') {
                    privateMembers.push(m);
                    continue;
                }

                // 2. Entitas Pemilik (entity_type === 'owner')
                if (m.entity_type === 'owner') {
                    const alsoListedAsResident = explicitResidentUserIds.has(m.user_id);

                    // Pemilik dianggap TIDAK menghuni (di luar bingkai) jika:
                    // - Status unit disewakan ('rented') atau masih 'unclaimed', DAN tidak punya record resident eksplisit
                    // - ATAU ada penyewa/penghuni utama lain di dalam dan pemilik tidak tercatat sebagai penghuni
                    const isExternal = !alsoListedAsResident && (
                        ['rented', 'unclaimed'].includes(unit.occupancy_status) || hasExternalTenantOrResident
                    );

                    if (isExternal) {
                        externalOwners.push({ ...m, hasOwnerDocument: true });
                    } else {
                        insideOccupants.push({ ...m, hasOwnerDocument: true });
                    }
                    continue;
                }

                // 3. Entitas Penghuni Non-Pemilik (entity_type === 'resident')
                // Jika user yang sama sudah masuk sebagai pemilik di dalam bingkai, hindari duplikasi kartu
                const alreadyInInsideAsOwner = insideOccupants.some(o => o.user_id === m.user_id);
                if (!alreadyInInsideAsOwner) {
                    insideOccupants.push({ ...m, hasOwnerDocument: false });
                }
            }

            return { externalOwners, insideOccupants, privateMembers };
        },

        get filteredUnits() {
            const q = (this.searchQuery || '').trim().toLowerCase();
            return this.units.filter(u => {
                if (this.filterBlock && u.block !== this.filterBlock) return false;
                if (this.filterZone && u.zone !== this.filterZone) return false;

                if (this.filterStatus === 'filled' && !['occupied', 'rented'].includes(u.occupancy_status)) return false;
                if (this.filterStatus === 'unclaimed' && (u.occupancy_status !== 'unclaimed' || u.unit_type === 'fasum')) return false;
                if (this.filterStatus === 'pending' && u.occupancy_status !== 'pending') return false;
                if (this.filterStatus === 'umkm' && (u.active_stores_count || 0) === 0) return false;
                if (this.filterStatus === 'dues_unpaid' && u.dues_status !== 'unpaid') return false;

                if (q) {
                    const matchCode = (u.code || '').toLowerCase().includes(q);
                    const matchMember = (u.members || []).some(m => (m.name || '').toLowerCase().includes(q));
                    const matchStore = (u.active_stores || []).some(s => (s.store_name || '').toLowerCase().includes(q));
                    if (!matchCode && !matchMember && !matchStore) return false;
                }

                return true;
            });
        },

        get groupedFilteredUnits() {
            return this.filteredUnits.reduce((groups, unit) => {
                if (!groups[unit.block]) groups[unit.block] = [];
                groups[unit.block].push(unit);
                return groups;
            }, {});
        },

        getUnitTileClass(unit) {
            if (!unit.is_interactive) {
                return 'bg-slate-100 text-slate-400 border-slate-200';
            }

            if (this.mapLayer === 'umkm') {
                if ((unit.active_stores_count || 0) > 0) {
                    return 'bg-amber-100 text-amber-950 border-amber-400 shadow-xs';
                }
                return 'bg-slate-50 text-slate-400 border-slate-200';
            }

            if (this.mapLayer === 'treasury') {
                if (unit.unit_type === 'fasum' || unit.dues_status === 'vacant' || unit.dues_status === 'exempt') {
                    return 'bg-slate-100 text-slate-500 border-slate-200';
                }
                if (unit.dues_status === 'paid') {
                    return 'bg-emerald-100 text-emerald-900 border-emerald-400';
                }
                return 'bg-rose-100 text-rose-900 border-rose-400';
            }

            // Default: Lapisan Hunian
            if (unit.unit_type === 'fasum') {
                return 'bg-teal-50 text-teal-800 border-teal-300';
            }
            if (unit.occupancy_status === 'rented') {
                return 'bg-sky-50 text-sky-900 border-sky-300';
            }
            if (unit.occupancy_status === 'occupied') {
                return 'bg-emerald-50 text-emerald-900 border-emerald-300';
            }
            if (unit.occupancy_status === 'pending') {
                return 'bg-amber-50 text-amber-900 border-amber-300';
            }
            return 'bg-slate-100 text-slate-600 border-slate-200';
        },

        formatBlockTitle(block) {
            const titles = {
                'R': 'Ruko Depan (R1 – R2)',
                'A': 'Blok A (A-1 s/d A-25 • Tanpa No. 13)',
                'B': 'Blok B (B-1 s/d B-24 • Tanpa No. 13)',
                'C': 'Blok C (C-1 s/d C-22 • Tanpa No. 13)',
                'D': 'Blok D (D-1 s/d D-17 • Tanpa No. 13)',
                'FASUM': 'Fasilitas Umum'
            };
            return titles[block] || 'Blok ' + block;
        },

        formatUnitStatus(status) {
            const labels = {
                'unclaimed': 'Belum Terklaim',
                'pending': 'Menunggu Verifikasi',
                'occupied': 'Berpenghuni',
                'rented': 'Disewakan'
            };
            return labels[status] || status;
        },

        formatDuesStatus(status) {
            const labels = {
                'paid': 'Lunas',
                'unpaid': 'Belum Lunas',
                'exempt': 'Dibebaskan',
                'vacant': 'Kavling Kosong'
            };
            return labels[status] || status;
        },

        async approveMemberOnUnit(unit, member) {
            this.approvingMembershipId = member.membership_id;
            try {
                const res = await fetch('/api/v1/verifications/approve', {
                    method: 'POST',
                    headers: this.headers(),
                    body: JSON.stringify({
                        target_user_id: member.user_id,
                        unit_code: unit.code
                    })
                });
                const json = await res.json();
                if (res.ok) {
                    this.toastType = 'success';
                    this.toastMessage = json.message;
                    await this.loadSiteplan(unit.code);
                } else {
                    this.toastType = 'error';
                    this.toastMessage = json.message || 'Gagal mengesahkan warga.';
                }
            } finally {
                this.approvingMembershipId = null;
            }
        }
    }
}
</script>
@endpush
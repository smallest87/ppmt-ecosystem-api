@extends('layouts.app')

@section('title', 'Beranda Warga — PPMT Digital')

@section('content')
<div x-data="ppmtFamilyRoom()" x-init="init()" @keydown.escape.window="selectedAnnouncement = null; showDuesModal = false" class="max-w-6xl mx-auto px-4 py-6 sm:py-8 space-y-6">

    <!-- Loading State -->
    <div x-show="loading" class="bg-white rounded-2xl p-12 text-center text-slate-500 border border-slate-200">
        Memuat beranda warga PPMT...
    </div>

    <div x-show="!loading && user" class="space-y-6">

        <!-- Notifikasi Global -->
        <div x-show="toastMessage"
             :class="toastType === 'error' ? 'bg-rose-50 text-rose-800 border-rose-200' : 'bg-emerald-50 text-emerald-800 border-emerald-200'"
             class="p-4 rounded-xl border text-sm font-medium flex justify-between items-center shadow-sm">
            <span x-text="toastMessage"></span>
            <button @click="toastMessage = ''" class="text-xs opacity-70 hover:opacity-100 cursor-pointer">✕</button>
        </div>

        <!-- Banner Peringatan Akun Pending -->
        <div x-show="!user?.is_verified"
             class="bg-amber-50 border-l-4 border-amber-500 p-5 rounded-r-2xl shadow-sm space-y-1">
            <h2 class="font-bold text-amber-900 text-base">⏳ Akun Anda Menunggu Verifikasi Sosial Tetangga</h2>
            <p class="text-xs sm:text-sm text-amber-800 leading-relaxed">
                Demi keamanan lingkungan PPMT, akses ke Direktori Warga, Peta Kavling, dan Pasar Tetangga baru dibuka setelah klaim kavling Anda disahkan oleh <strong>Koordinator Zona/Blok</strong> terkait atau <strong>Pemilik Utama</strong> unit Anda.
            </p>
        </div>

        <!-- Konten Utama (Hanya aktif jika sudah terverifikasi) -->
        <div x-show="user?.is_verified" class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

            <!-- ========================================================= -->
            <!-- KOLOM KIRI (7 Kolom): KABAR PAGUYUBAN & AGENDA LINGKUNGAN -->
            <!-- ========================================================= -->
            <div class="lg:col-span-7 space-y-6">

                <!-- Bagian 1: Pengumuman Warga (Clean Bootstrap-style Alert Cards) -->
                <section x-show="announcements.length > 0"
                         class="space-y-2.5"
                         x-data="{ showAllAnnouncements: false }">
                    <!-- Susunan Vertikal Alert Box per Item -->
                    <div class="space-y-2.5">
                        <template x-for="item in (showAllAnnouncements ? announcements : announcements.slice(0, 5))" :key="item.id">
                            <button type="button"
                                    @click="selectedAnnouncement = item"
                                    :class="/penting|darurat|mendesak|peringatan|urgent/i.test(item.category || '')
                                        ? 'bg-rose-50 hover:bg-rose-100/80 border-rose-300 border-l-4 border-l-rose-600 text-rose-950'
                                        : (item.is_pinned
                                            ? 'bg-amber-50 hover:bg-amber-100/80 border-amber-300 border-l-4 border-l-amber-500 text-amber-950'
                                            : 'bg-white hover:bg-emerald-50/50 border-slate-200 border-l-4 border-l-emerald-600 text-slate-900')"
                                    class="relative w-full px-4 py-3.5 rounded-xl border shadow-xs flex items-center justify-between gap-3.5 text-left transition cursor-pointer group">

                                <!-- Ikon Pin di Pojok Kiri Atas (Hanya untuk item yang di-pin) -->
                                <span x-show="item.is_pinned"
                                      title="Pengumuman Disematkan"
                                      :class="/penting|darurat|mendesak|peringatan|urgent/i.test(item.category || '') ? 'bg-rose-600' : 'bg-amber-500'"
                                      class="absolute -top-2 -left-2 w-5 h-5 rounded-full text-white shadow-xs flex items-center justify-center ring-2 ring-white">
                                    <svg class="w-2.5 h-2.5" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M16 3a1 1 0 0 1 .707.293l4 4a1 1 0 0 1-.493 1.686l-3.437.687-2.61 2.61.556 3.337a1 1 0 0 1-1.693.88L9.5 12.963l-4.793 4.794a1 1 0 0 1-1.414-1.414L8.086 11.55 4.557 8.02a1 1 0 0 1 .88-1.693l3.337.556 2.61-2.61.687-3.437A1 1 0 0 1 13.757.343L16 3Z"/>
                                    </svg>
                                </span>

                                <!-- Ikon Kategori Berukuran Besar Sejajar Judul -->
                                <div :title="item.category"
                                     :class="/penting|darurat|mendesak|peringatan|urgent/i.test(item.category || '')
                                         ? 'bg-rose-100 text-rose-700 border-rose-300/90'
                                         : (item.is_pinned
                                             ? 'bg-amber-100 text-amber-800 border-amber-300/80'
                                             : 'bg-emerald-50 text-emerald-700 border-emerald-200/80')"
                                     class="w-10 h-10 rounded-xl border flex items-center justify-center shrink-0">
                                    <!-- Ikon Penting / Darurat / Mendesak -->
                                    <template x-if="/penting|darurat|mendesak|peringatan|urgent/i.test(item.category || '')">
                                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                                        </svg>
                                    </template>
                                    <!-- Ikon Keamanan / Tertib -->
                                    <template x-if="!/penting|darurat|mendesak|peringatan|urgent/i.test(item.category || '') && /keamanan|tertib/i.test(item.category || '')">
                                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                                        </svg>
                                    </template>
                                    <!-- Ikon Iuran / Keuangan / Kas -->
                                    <template x-if="!/penting|darurat|mendesak|peringatan|urgent|keamanan|tertib/i.test(item.category || '') && /iuran|kas|keuangan|sampah/i.test(item.category || '')">
                                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                                        </svg>
                                    </template>
                                    <!-- Ikon Musyawarah / Rapat / Agenda -->
                                    <template x-if="!/penting|darurat|mendesak|peringatan|urgent|keamanan|tertib|iuran|kas|keuangan|sampah/i.test(item.category || '') && /musyawarah|rapat|agenda|takmir|musholla/i.test(item.category || '')">
                                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                                        </svg>
                                    </template>
                                    <!-- Ikon Info Umum / Info Lingkungan (Default) -->
                                    <template x-if="!/penting|darurat|mendesak|peringatan|urgent|keamanan|tertib|iuran|kas|keuangan|sampah|musyawarah|rapat|agenda|takmir|musholla/i.test(item.category || '')">
                                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                                        </svg>
                                    </template>
                                </div>

                                <!-- Judul Pengumuman -->
                                <div :class="/penting|darurat|mendesak|peringatan|urgent/i.test(item.category || '')
                                         ? 'font-extrabold text-rose-950 group-hover:text-rose-800'
                                         : (item.is_pinned
                                             ? 'font-extrabold text-amber-950 group-hover:text-emerald-800'
                                             : 'font-bold text-slate-900 group-hover:text-emerald-800')"
                                     class="min-w-0 flex-1 text-sm transition leading-snug"
                                     x-text="item.title"></div>

                                <!-- Panah Indikator Klik -->
                                <span :class="/penting|darurat|mendesak|peringatan|urgent/i.test(item.category || '')
                                          ? 'text-rose-600 group-hover:text-rose-800'
                                          : (item.is_pinned
                                              ? 'text-amber-700 group-hover:text-emerald-700'
                                              : 'text-slate-400 group-hover:text-emerald-700')"
                                      class="group-hover:translate-x-0.5 transition text-lg font-bold shrink-0">&rsaquo;</span>
                            </button>
                        </template>
                    </div>

                    <div x-show="announcements.length > 5" class="px-1 pt-0.5">
                        <button type="button"
                                @click="showAllAnnouncements = !showAllAnnouncements"
                                class="text-xs font-bold text-emerald-700 hover:text-emerald-900 cursor-pointer">
                            <span x-show="!showAllAnnouncements" x-text="'Lihat semua (' + announcements.length + ' pengumuman) ↓'"></span>
                            <span x-show="showAllAnnouncements">Ringkas tampilan ↑</span>
                        </button>
                    </div>
                </section>

                <!-- Kartu 2: Agenda & Jadwal Lingkungan (Live API) -->
                <section class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-base font-bold text-slate-900">Agenda &amp; Jadwal Lingkungan</h2>
                            <p class="text-xs text-slate-500 mt-0.5">Jadwal kegiatan rutin, musyawarah, dan pemeliharaan lingkungan PPMT.</p>
                        </div>
                        <a x-show="user?.can_manage_announcements"
                           href="/pengurus"
                           class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200 hover:bg-indigo-100 transition shrink-0">
                            + Kelola
                        </a>
                    </div>

                    <div x-show="agendas.length === 0" class="px-6 py-8 text-center text-xs text-slate-500">
                        Belum ada agenda atau jadwal kegiatan lingkungan dalam waktu dekat.
                    </div>

                    <div class="divide-y divide-slate-100" x-show="agendas.length > 0">
                        <template x-for="ag in agendas" :key="ag.id">
                            <div class="px-6 py-4 flex items-start gap-4">
                                <div class="w-12 h-14 rounded-xl bg-emerald-50 border border-emerald-200 flex flex-col items-center justify-center shrink-0 text-emerald-900">
                                    <span class="text-[9px] font-bold uppercase leading-none" x-text="ag.dayShort"></span>
                                    <span class="text-base font-extrabold leading-tight" x-text="ag.dateNum"></span>
                                    <span class="text-[9px] font-semibold uppercase text-emerald-700 leading-none" x-text="ag.monthShort"></span>
                                </div>
                                <div class="min-w-0 flex-1 space-y-1">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <span :class="ag.badgeClass"
                                              class="px-2 py-0.5 rounded-md text-[10px] font-bold border"
                                              x-text="ag.category"></span>
                                    </div>
                                    <div class="text-sm font-bold text-slate-900 leading-snug" x-text="ag.title"></div>
                                    <div class="text-xs text-slate-500" x-text="ag.time + ' • ' + ag.location"></div>
                                    <p x-show="ag.notes" class="text-[11px] text-slate-500 italic" x-text="ag.notes"></p>
                                </div>
                            </div>
                        </template>
                    </div>
                </section>

            </div>

            <!-- ========================================================= -->
            <!-- KOLOM KANAN (5 Kolom): VERIFIKASI, IURAN UNIT & UNDANGAN  -->
            <!-- ========================================================= -->
            <div class="lg:col-span-5 space-y-6">

                <!-- Panel 1: Antrean Verifikasi Sosial (Live API) -->
                <section x-show="pendingApprovals.length > 0"
                         class="bg-amber-50/80 rounded-2xl p-5 shadow-sm border border-amber-300 space-y-3">
                    <div class="flex items-center justify-between">
                        <h2 class="text-base font-bold text-amber-950">Antrean Verifikasi Tetangga</h2>
                        <span class="px-2.5 py-0.5 rounded-full bg-amber-500 text-white text-xs font-bold"
                              x-text="pendingApprovals.length"></span>
                    </div>
                    <p class="text-xs text-amber-900">Sahkan pendaftar berikut hanya jika Anda mengenal dan memastikan mereka benar penghuni kavling terkait:</p>

                    <div class="space-y-2.5">
                        <template x-for="item in pendingApprovals" :key="item.membership_id">
                            <div class="bg-white p-3.5 rounded-xl border border-amber-200 flex items-center justify-between gap-2">
                                <div class="text-xs space-y-0.5">
                                    <div class="font-bold text-slate-900" x-text="item.name + ' • Unit ' + item.unit_code"></div>
                                    <div class="text-slate-500" x-text="(item.phone || item.username || '-') + ' (' + subRoleLabel(item.sub_role) + ')'"></div>
                                </div>
                                <button type="button" @click="approveResident(item)"
                                        class="px-3 py-1.5 rounded-lg bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-semibold shrink-0 cursor-pointer">
                                    ✓ Sahkan
                                </button>
                            </div>
                        </template>
                    </div>
                </section>

                <!-- Panel 2: Status Iuran Sampah & Saldo Kas Paguyuban (Live API) -->
                <section class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between gap-2">
                        <div>
                            <h2 class="text-base font-bold text-slate-900">Iuran Sampah Kavling</h2>
                            <p class="text-xs text-slate-500 mt-0.5"
                               x-text="'Periode ' + (treasuryReport?.period_label || 'Bulan Ini') + ' • Jatuh Tempo Tgl 1'"></p>
                        </div>
                        <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-slate-100 text-slate-700 border border-slate-200 shrink-0">
                            Rp 20.000 / bln
                        </span>
                    </div>

                    <div class="p-5 space-y-3">
                        <template x-for="uDues in (treasuryReport?.my_units_dues || [])" :key="uDues.unit_id">
                            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/80 flex items-center justify-between gap-3">
                                <div>
                                    <div class="text-xs font-bold text-slate-800" x-text="'Kavling ' + uDues.unit_code"></div>
                                    <div class="text-base font-extrabold text-slate-900 mt-0.5"
                                         x-text="uDues.current?.status === 'exempt' ? 'Rp 0' : formatRupiah(uDues.current?.amount ?? 20000)"></div>
                                </div>
                                <div class="text-right space-y-1">
                                    <span :class="{
                                            'bg-emerald-100 text-emerald-900 border-emerald-200': uDues.current?.status === 'paid',
                                            'bg-amber-100 text-amber-900 border-amber-300': uDues.current?.status === 'unpaid',
                                            'bg-slate-200 text-slate-700 border-slate-300': uDues.current?.status === 'exempt'
                                          }"
                                          class="inline-block px-2.5 py-0.5 rounded-full text-[11px] font-bold border"
                                          x-text="uDues.current?.status === 'paid' ? '✓ Lunas' : (uDues.current?.status === 'exempt' ? 'Dibebaskan' : 'Belum Terbayar')">
                                    </span>
                                    <div class="text-[11px] text-slate-500"
                                         x-text="uDues.current?.paid_at ? ((uDues.current?.payment_method === 'transfer' ? 'Transfer' : 'Tunai') + ' • ' + uDues.current.paid_at) : 'Tagihan rutin tgl 1'">
                                    </div>
                                </div>
                            </div>
                        </template>

                        <div x-show="treasuryReport !== null"
                             class="px-3.5 py-2.5 rounded-xl bg-emerald-50/70 border border-emerald-200/80 flex items-center justify-between text-xs">
                            <span class="text-emerald-900 font-medium">Saldo Kas Paguyuban:</span>
                            <strong class="text-emerald-950 font-extrabold"
                                    x-text="formatRupiah(treasuryReport?.summary?.current_balance ?? 0)"></strong>
                        </div>

                        <div class="grid grid-cols-2 gap-2 pt-1">
                            <button type="button"
                                    @click="showDuesModal = true"
                                    class="w-full py-2 px-3 rounded-xl border border-slate-300 hover:bg-slate-50 text-slate-700 text-xs font-semibold transition cursor-pointer">
                                Riwayat 3 Bulan
                            </button>
                            <a href="/komunitas?tab=kas"
                               class="w-full py-2 px-3 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold text-center transition">
                                Laporan Kas &rarr;
                            </a>
                        </div>
                    </div>
                </section>

                <!-- Panel 2B: Sorotan Aspirasi & Petisi Kolektif Warga -->
                <section x-show="aspirations.length > 0"
                         x-cloak
                         class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between gap-2">
                        <div>
                            <h2 class="text-base font-bold text-slate-900">Aspirasi &amp; Petisi Warga</h2>
                            <p class="text-xs text-slate-500 mt-0.5">Suara kolektif kavling untuk audiensi pengembang.</p>
                        </div>
                        <span x-show="unsignedOpenAspirations.length > 0"
                              class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-amber-100 text-amber-900 border border-amber-300/80 shrink-0"
                              x-text="unsignedOpenAspirations.length + ' Menunggu'">
                        </span>
                    </div>

                    <!-- Mode Aktif: Tampilkan maksimal 2 petisi terbuka yang belum didukung kavling user -->
                    <div x-show="unsignedOpenAspirations.length > 0" class="p-5 space-y-3">
                        <template x-for="asp in unsignedOpenAspirations.slice(0, 2)" :key="asp.id">
                            <a href="/komunitas?tab=aspirasi"
                               class="block p-3.5 rounded-xl bg-slate-50 hover:bg-emerald-50/70 border border-slate-200/90 hover:border-emerald-300 transition space-y-2 group">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-800 bg-emerald-100/80 px-2 py-0.5 rounded"
                                          x-text="asp.category"></span>
                                    <span class="text-[11px] font-bold text-slate-600"
                                          x-text="asp.signatures_count + '/' + asp.target_signatures + ' kavling'"></span>
                                </div>

                                <div class="text-xs font-bold text-slate-900 group-hover:text-emerald-900 leading-snug line-clamp-2"
                                     x-text="asp.title"></div>

                                <div class="w-full h-1.5 rounded-full bg-slate-200 overflow-hidden">
                                    <div class="h-full rounded-full bg-emerald-600"
                                         :style="'width: ' + Math.min(100, Math.round((asp.signatures_count / Math.max(1, asp.target_signatures)) * 100)) + '%'">
                                    </div>
                                </div>
                            </a>
                        </template>

                        <a href="/komunitas?tab=aspirasi"
                           class="w-full py-2.5 px-4 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold flex items-center justify-center gap-1.5 shadow-xs transition">
                            <span>Tinjau &amp; Beri Dukungan Kavling</span>
                            <span>&rarr;</span>
                        </a>
                    </div>

                    <!-- Mode Ringkas: Jika kavling user sudah mendukung semua petisi terbuka -->
                    <div x-show="unsignedOpenAspirations.length === 0"
                         class="px-5 py-4 flex items-center justify-between gap-3 text-xs bg-slate-50/60">
                        <div class="text-slate-600">
                            <strong class="text-slate-800" x-text="aspirations.filter(a => ['open', 'submitted'].includes(a.status)).length + ' isu kolektif'"></strong>
                            sedang dikawal bersama warga.
                        </div>
                        <a href="/komunitas?tab=aspirasi"
                           class="font-bold text-emerald-700 hover:text-emerald-900 shrink-0">
                            Lihat Semua &rarr;
                        </a>
                    </div>
                </section>

                <!-- Panel 3: Generator Tautan Undangan Keluarga / Penyewa (Live API) -->
                <section x-show="manageableUnits.length > 0"
                         class="bg-white rounded-2xl p-5 shadow-sm border border-slate-200 space-y-4">
                    <div class="border-b border-slate-100 pb-3">
                        <h2 class="text-base font-bold text-slate-900">Undang Anggota Keluarga / Penyewa</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Buat tautan undangan berbatas waktu (48 jam) agar penghuni serumah langsung aktif tanpa antre verifikasi.</p>
                    </div>

                    <form @submit.prevent="generateInvite()" class="space-y-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Pilih Kavling Anda</label>
                            <select x-model="inviteForm.unit_code" required class="w-full rounded-xl border-slate-300 text-xs">
                                <template x-for="m in manageableUnits" :key="m.id">
                                    <option :value="m.unit.code" x-text="'Kavling ' + m.unit.code"></option>
                                </template>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Entitas</label>
                                <select x-model="inviteForm.entity_type" class="w-full rounded-xl border-slate-300 text-xs">
                                    <option value="resident">Penghuni</option>
                                    <option value="owner">Pemilik Bersama</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Peran</label>
                                <select x-model="inviteForm.sub_role" class="w-full rounded-xl border-slate-300 text-xs">
                                    <option value="family_member">Anggota Keluarga</option>
                                    <option value="primary_resident">Penghuni Utama</option>
                                    <option value="tenant">Penyewa / Kontrak</option>
                                    <option value="co_owner">Pemilik Bersama</option>
                                    <option value="caretaker">Penjaga Rumah</option>
                                </select>
                            </div>
                        </div>

                        <div x-show="inviteForm.sub_role === 'tenant'">
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Batas Akhir Masa Sewa *</label>
                            <input type="date" x-model="inviteForm.member_valid_until"
                                   :required="inviteForm.sub_role === 'tenant'"
                                   class="w-full rounded-xl border-slate-300 text-xs">
                        </div>

                        <button type="submit"
                                class="w-full py-2 px-4 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold transition cursor-pointer">
                            Buat Tautan Undangan
                        </button>
                    </form>

                    <div x-show="generatedInviteUrl" class="p-3 rounded-xl bg-emerald-50 border border-emerald-200 space-y-2">
                        <div class="text-[11px] font-semibold text-emerald-900">Tautan Undangan Siap Dikirim via WhatsApp:</div>
                        <input type="text" readonly :value="generatedInviteUrl"
                               class="w-full rounded-lg bg-white border-emerald-300 text-[11px] font-mono text-slate-700">
                        <button type="button" @click="copyInviteUrl()"
                                class="w-full py-1.5 rounded-lg bg-emerald-700 text-white text-xs font-semibold cursor-pointer">
                            Salin Tautan Undangan
                        </button>
                    </div>
                </section>

            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- MODAL BACA PENGUMUMAN LENGKAP                                         -->
    <!-- ===================================================================== -->
    <div x-show="selectedAnnouncement !== null"
         x-cloak
         class="fixed inset-0 z-50 !mt-0 flex items-center justify-center p-3 sm:p-4 bg-slate-900/55 backdrop-blur-[2px] overflow-y-auto">
        <div @click.away="selectedAnnouncement = null"
             class="bg-white rounded-2xl shadow-2xl border border-slate-200/90 w-full max-w-[calc(100vw-1.5rem)] sm:max-w-lg max-h-[88dvh] flex flex-col overflow-hidden my-auto">

            <!-- Header Modal: Badge Kategori, Pin, Tanggal & Tombol Close -->
            <div class="px-5 sm:px-6 py-3.5 border-b border-slate-100 flex items-center justify-between gap-3 shrink-0 bg-slate-50/60">
                <div class="flex flex-wrap items-center gap-2">
                    <span x-show="selectedAnnouncement?.is_pinned"
                          class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-100 text-amber-900 border border-amber-300/80">
                        <svg class="w-2.5 h-2.5 text-amber-700" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M16 3a1 1 0 0 1 .707.293l4 4a1 1 0 0 1-.493 1.686l-3.437.687-2.61 2.61.556 3.337a1 1 0 0 1-1.693.88L9.5 12.963l-4.793 4.794a1 1 0 0 1-1.414-1.414L8.086 11.55 4.557 8.02a1 1 0 0 1 .88-1.693l3.337.556 2.61-2.61.687-3.437A1 1 0 0 1 13.757.343L16 3Z"/>
                        </svg>
                        Disematkan
                    </span>
                    <span :class="selectedAnnouncement?.badgeClass"
                          class="px-2.5 py-0.5 rounded-md text-[11px] font-bold border"
                          x-text="selectedAnnouncement?.category"></span>
                    <span class="text-xs font-medium text-slate-400" x-text="selectedAnnouncement?.date"></span>
                </div>
                <button type="button"
                        @click="selectedAnnouncement = null"
                        aria-label="Tutup modal"
                        class="w-7 h-7 rounded-full flex items-center justify-center text-slate-400 hover:text-slate-700 hover:bg-slate-200/70 transition cursor-pointer shrink-0">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Konten Utama Pengumuman -->
            <div class="p-5 sm:p-6 space-y-4 overflow-y-auto">
                <div class="space-y-2">
                    <h3 class="text-lg sm:text-xl font-extrabold text-slate-900 leading-snug tracking-tight"
                        x-text="selectedAnnouncement?.title"></h3>

                    <div class="flex items-center gap-2 text-xs text-slate-500">
                        <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-800 font-bold text-[10px] inline-flex items-center justify-center shrink-0"
                              x-text="(selectedAnnouncement?.author || 'P').charAt(0).toUpperCase()"></span>
                        <span x-text="selectedAnnouncement?.author || 'Pengurus Paguyuban PPMT'"></span>
                    </div>
                </div>

                <div class="pt-3.5 border-t border-slate-100 text-[13.5px] sm:text-sm text-slate-700 leading-relaxed whitespace-pre-line"
                     x-text="selectedAnnouncement?.body"></div>
            </div>

            <!-- Footer Modal: Tombol Salin Teks & Tutup -->
            <div class="px-5 sm:px-6 py-3.5 bg-slate-50/80 border-t border-slate-100 flex items-center justify-between gap-3 shrink-0">
                <button type="button"
                        @click="copyAnnouncementText(selectedAnnouncement)"
                        :class="copiedAnnouncement
                            ? 'border-emerald-300 bg-emerald-50 text-emerald-800'
                            : 'border-slate-200 bg-white hover:bg-emerald-50 hover:border-emerald-300 text-slate-700 hover:text-emerald-800'"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border text-xs font-semibold transition cursor-pointer">
                    <svg x-show="!copiedAnnouncement" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                    <svg x-show="copiedAnnouncement" x-cloak class="w-3.5 h-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    <span x-text="copiedAnnouncement ? 'Tersalin' : 'Salin Isi'"></span>
                </button>

                <button type="button"
                        @click="selectedAnnouncement = null"
                        class="px-5 py-2 rounded-xl bg-emerald-900 hover:bg-emerald-950 text-white text-xs font-bold shadow-xs transition cursor-pointer">
                    Tutup
                </button>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- MODAL RIWAYAT IURAN SAMPAH KAVLING (LIVE API)                         -->
    <!-- ===================================================================== -->
    <div x-show="showDuesModal"
         x-cloak
         class="fixed inset-0 z-50 !mt-0 flex items-center justify-center p-3 sm:p-4 bg-slate-900/50 backdrop-blur-[1px] overflow-y-auto">
        <div @click.away="showDuesModal = false"
             class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-[calc(100vw-1.5rem)] sm:max-w-md max-h-[90dvh] flex flex-col overflow-hidden my-auto">
            <div class="px-5 sm:px-6 py-4 border-b border-slate-100 flex items-center justify-between gap-3 shrink-0">
                <h3 class="text-base font-bold text-slate-900">Riwayat Iuran Sampah Kavling</h3>
                <button type="button" @click="showDuesModal = false" class="text-slate-400 hover:text-slate-700 text-sm p-1 cursor-pointer">✕</button>
            </div>
            <div class="p-5 sm:p-6 space-y-4 overflow-y-auto">
                <template x-for="uDues in (treasuryReport?.my_units_dues || [])" :key="uDues.unit_id">
                    <div class="space-y-2">
                        <div class="text-xs font-extrabold uppercase tracking-wider text-slate-500"
                             x-text="'Kavling ' + uDues.unit_code + ' (' + uDues.zone + ')'"></div>
                        <div class="divide-y divide-slate-100 border border-slate-200 rounded-xl overflow-hidden">
                            <template x-for="row in uDues.history" :key="row.period_month">
                                <div class="px-4 py-3 flex items-center justify-between text-xs bg-white">
                                    <div>
                                        <div class="font-bold text-slate-900" x-text="row.period_label"></div>
                                        <div class="text-slate-500 mt-0.5"
                                             x-text="row.paid_at ? ('Dibayar ' + row.paid_at + ' (' + (row.payment_method === 'transfer' ? 'Transfer' : 'Tunai') + ')') : ('Jatuh tempo ' + row.due_date)"></div>
                                    </div>
                                    <div class="text-right">
                                        <div class="font-bold text-slate-800"
                                             x-text="row.status === 'exempt' ? 'Rp 0' : formatRupiah(row.amount)"></div>
                                        <span :class="{
                                                'bg-emerald-50 text-emerald-700 border-emerald-200': row.status === 'paid',
                                                'bg-amber-50 text-amber-800 border-amber-200': row.status === 'unpaid',
                                                'bg-slate-100 text-slate-600 border-slate-200': row.status === 'exempt'
                                              }"
                                              class="inline-block mt-0.5 px-2 py-0.5 rounded-full text-[10px] font-bold border"
                                              x-text="row.status === 'paid' ? 'Lunas' : (row.status === 'exempt' ? 'Dibebaskan' : 'Belum Bayar')">
                                        </span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
            <div class="px-5 sm:px-6 py-3.5 bg-slate-50 border-t border-slate-100 flex items-center justify-between shrink-0">
                <a href="/komunitas?tab=kas" class="text-xs font-bold text-emerald-700 hover:underline">
                    Buka Laporan Kas Lengkap &rarr;
                </a>
                <button type="button" @click="showDuesModal = false"
                        class="px-4 py-2 rounded-xl bg-slate-900 text-white text-xs font-semibold cursor-pointer">
                    Tutup
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function ppmtFamilyRoom() {
    return {
        token: localStorage.getItem('ppmt_token'),
        user: null,
        loading: true,
        toastMessage: '',
        toastType: 'success',
        pendingApprovals: [],
        generatedInviteUrl: '',
        selectedAnnouncement: null,
        copiedAnnouncement: false,
        showDuesModal: false,
        announcements: [],
        agendas: [],
        aspirations: [],
        treasuryReport: null,

        inviteForm: {
            unit_code: '',
            entity_type: 'resident',
            sub_role: 'family_member',
            member_valid_until: ''
        },

        async init() {
            if (!this.token) {
                window.location.href = '/';
                return;
            }
            await this.loadMe();
            if (this.user?.is_verified) {
                await Promise.all([
                    this.loadAnnouncements(),
                    this.loadAgendas(),
                    this.loadAspirations(),
                    this.loadPendingApprovals(),
                    this.loadTreasuryReport()
                ]);
            }
        },

        headers() {
            return {
                'Authorization': 'Bearer ' + this.token,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            };
        },

        formatRupiah(value) {
            return 'Rp ' + Number(value || 0).toLocaleString('id-ID');
        },

        subRoleLabel(role) {
            const labels = {
                'primary_owner': 'Pemilik Utama',
                'co_owner': 'Pemilik Bersama',
                'primary_resident': 'Penghuni Utama',
                'family_member': 'Anggota Keluarga',
                'co_resident': 'Penghuni Bersama',
                'tenant': 'Penyewa / Kontrak',
                'caretaker': 'Penjaga Rumah'
            };
            return labels[role] || role;
        },

        async loadMe() {
            try {
                const res = await fetch('/api/v1/me', { headers: this.headers() });
                if (!res.ok) throw new Error('Unauthorized');
                const json = await res.json();
                this.user = json.data;

                if (this.manageableUnits.length > 0 && !this.inviteForm.unit_code) {
                    this.inviteForm.unit_code = this.manageableUnits[0].unit.code;
                }
            } catch (e) {
                localStorage.removeItem('ppmt_token');
                window.location.href = '/';
            } finally {
                this.loading = false;
            }
        },

        get manageableUnits() {
            if (!this.user?.memberships) return [];
            return this.user.memberships.filter(m =>
                m.is_approved && ['primary_owner', 'primary_resident'].includes(m.sub_role)
            );
        },

        get unsignedOpenAspirations() {
            return this.aspirations.filter(a => a.status === 'open' && !a.is_signed_by_me);
        },

        async loadTreasuryReport() {
            const res = await fetch('/api/v1/treasury/public-report', { headers: this.headers() });
            if (res.ok) {
                const json = await res.json();
                this.treasuryReport = json.data || null;
            }
        },

        async loadAnnouncements() {
            const res = await fetch('/api/v1/announcements', { headers: this.headers() });
            if (res.ok) {
                const json = await res.json();
                this.announcements = json.data || [];
            }
        },

        async loadAgendas() {
            const res = await fetch('/api/v1/agendas', { headers: this.headers() });
            if (res.ok) {
                const json = await res.json();
                this.agendas = json.data || [];
            }
        },

        async loadAspirations() {
            const res = await fetch('/api/v1/aspirations', { headers: this.headers() });
            if (res.ok) {
                const json = await res.json();
                this.aspirations = json.data || [];
            }
        },

        async loadPendingApprovals() {
            const res = await fetch('/api/v1/verifications/pending', { headers: this.headers() });
            if (res.ok) {
                const json = await res.json();
                this.pendingApprovals = json.data || [];
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
                await this.loadPendingApprovals();
            } else {
                this.toastType = 'error';
                this.toastMessage = json.message || 'Gagal mengesahkan warga.';
            }
        },

        async generateInvite() {
            const res = await fetch(`/api/v1/units/${this.inviteForm.unit_code}/invites`, {
                method: 'POST',
                headers: this.headers(),
                body: JSON.stringify(this.inviteForm)
            });
            const json = await res.json();
            if (res.ok) {
                const token = json.data.invite_token;
                const unit = json.data.unit_code;
                this.generatedInviteUrl = `${window.location.origin}/?unit=${unit}&invite=${token}`;
                this.toastType = 'success';
                this.toastMessage = json.message;
            } else {
                this.toastType = 'error';
                this.toastMessage = json.message || 'Gagal membuat tautan undangan.';
            }
        },

        copyInviteUrl() {
            navigator.clipboard.writeText(this.generatedInviteUrl);
            this.toastType = 'success';
            this.toastMessage = 'Tautan undangan disalin! Siap ditempel di WhatsApp keluarga/penyewa.';
        },

        async copyAnnouncementText(item) {
            if (!item) return;
            const text = `*${item.title}*\n(${item.category} • ${item.date})\n\n${item.body}\n\n— Info Warga PPMT Digital`;

            try {
                if (navigator.clipboard && window.isSecureContext) {
                    await navigator.clipboard.writeText(text);
                } else {
                    const textarea = document.createElement('textarea');
                    textarea.value = text;
                    textarea.style.position = 'fixed';
                    textarea.style.opacity = '0';
                    document.body.appendChild(textarea);
                    textarea.focus();
                    textarea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textarea);
                }

                this.copiedAnnouncement = true;
                setTimeout(() => { this.copiedAnnouncement = false; }, 2000);
            } catch (e) {
                this.toastType = 'error';
                this.toastMessage = 'Gagal menyalin teks pengumuman.';
            }
        }
    }
}
</script>
@endpush
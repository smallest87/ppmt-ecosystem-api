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

                <!-- Bagian 1: Pengumuman Warga (Refined Frameless) -->
                <section class="space-y-2.5" x-data="{ showAllAnnouncements: false }">
                    <div class="px-1 flex items-center justify-between gap-3">
                        <h2 class="text-base font-bold text-slate-900 tracking-tight">Pengumuman Warga</h2>
                        <div class="flex items-center gap-2 shrink-0">
                            <!-- Indikator Lonceng + Pill Jumlah Pengumuman -->
                            <div x-show="announcements.length > 0"
                                 :title="announcements.length + ' Pengumuman aktif'"
                                 class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-amber-50/90 text-amber-900 border border-amber-200/80">
                                <svg class="w-3.5 h-3.5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                                </svg>
                                <span class="text-[11px] font-extrabold leading-none" x-text="announcements.length"></span>
                            </div>

                            <!-- Tombol Ikon Gear Kelola Pengumuman -->
                            <a x-show="user?.can_manage_announcements"
                               href="/pengurus"
                               title="Kelola Pengumuman"
                               aria-label="Kelola Pengumuman"
                               class="p-1.5 rounded-lg bg-white text-slate-600 border border-slate-200/90 hover:text-emerald-800 hover:border-emerald-300 hover:bg-emerald-50/60 transition">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </a>
                        </div>
                    </div>

                    <div x-show="announcements.length === 0"
                         class="py-6 px-4 rounded-xl border border-dashed border-slate-300/90 text-center text-xs text-slate-500">
                        Belum ada pengumuman resmi paguyuban yang diterbitkan saat ini.
                    </div>

                    <div class="divide-y divide-slate-200/75 border-y border-slate-200/75" x-show="announcements.length > 0">
                        <template x-for="item in (showAllAnnouncements ? announcements : announcements.slice(0, 5))" :key="item.id">
                            <button type="button"
                                    @click="selectedAnnouncement = item"
                                    :class="item.is_pinned ? 'bg-amber-50/55 hover:bg-amber-50/90 px-2.5 my-1 rounded-xl border border-amber-200/70' : 'px-1 hover:bg-slate-200/45 rounded-lg'"
                                    class="w-full py-3 flex items-center justify-between gap-3 text-left transition cursor-pointer group">
                                <div :class="item.is_pinned ? 'font-bold text-slate-950' : 'font-semibold text-slate-800'"
                                     class="min-w-0 text-sm group-hover:text-emerald-800 transition leading-snug">
                                    <!-- Ikon Pin SVG Monokrom -->
                                    <span x-show="item.is_pinned"
                                          title="Disematkan"
                                          class="inline-flex items-center justify-center align-middle mr-1.5 w-5 h-5 rounded-md bg-amber-100 text-amber-700 border border-amber-300/80">
                                        <svg class="w-3 h-3" viewBox="0 0 24 24" fill="currentColor">
                                            <path d="M16 3a1 1 0 0 1 .707.293l4 4a1 1 0 0 1-.493 1.686l-3.437.687-2.61 2.61.556 3.337a1 1 0 0 1-1.693.88L9.5 12.963l-4.793 4.794a1 1 0 0 1-1.414-1.414L8.086 11.55 4.557 8.02a1 1 0 0 1 .88-1.693l3.337.556 2.61-2.61.687-3.437A1 1 0 0 1 13.757.343L16 3Z"/>
                                        </svg>
                                    </span><span x-text="item.title"></span>
                                </div>
                                <span class="text-slate-400 group-hover:text-emerald-700 group-hover:translate-x-0.5 transition text-lg font-medium shrink-0">&rsaquo;</span>
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
@endsection
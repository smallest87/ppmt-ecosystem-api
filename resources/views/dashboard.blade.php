@extends('layouts.app')

@section('title', 'Beranda Warga — PPMT Digital')

@section('content')
<div x-data="ppmtFamilyRoom()" x-init="init()" @keydown.escape.window="selectedAnnouncement = null; showDuesModal = false" class="max-w-6xl mx-auto px-4 py-8 space-y-6">

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

        <!-- ========================================================= -->
        <!-- HEADER SAPAAN HANGAT & STATUS RINGKAS KAVLING             -->
        <!-- ========================================================= -->
        <section class="bg-gradient-to-br from-emerald-900 via-emerald-950 to-slate-900 text-white rounded-2xl p-6 shadow-sm border border-emerald-800/60 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="space-y-1.5">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-500/20 text-emerald-200 border border-emerald-400/30"
                          x-text="unitSummaryText">
                    </span>
                    <template x-for="gov in (user?.governance_roles || [])" :key="gov.id">
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-400/20 text-amber-200 border border-amber-300/30"
                              x-text="'★ ' + gov.display_label">
                        </span>
                    </template>
                </div>
                <h1 class="text-xl sm:text-2xl font-extrabold tracking-tight"
                    x-text="greetingText + ', ' + shortName + '!'">
                </h1>
                <p class="text-xs sm:text-sm text-emerald-100/80 max-w-xl leading-relaxed">
                    Selamat datang di ruang informasi bersama warga Perumahan Pesona Mandiri Turen. Pantau kabar lingkungan, agenda gotong royong, dan status kavling Anda di sini.
                </p>
            </div>

            <!-- Pintasan Cepat -->
            <div class="flex flex-wrap sm:flex-nowrap items-center gap-2.5 shrink-0">
                <a href="/komunitas"
                   class="px-4 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 text-xs font-extrabold shadow-sm transition">
                    Direktori &amp; Peta Warga
                </a>
                <a href="/pengaturan"
                   class="px-4 py-2.5 rounded-xl bg-white/10 hover:bg-white/15 text-white border border-white/15 text-xs font-semibold transition">
                    Pengaturan Akun
                </a>
            </div>
        </section>

        <!-- Konten Utama (Hanya aktif jika sudah terverifikasi) -->
        <div x-show="user?.is_verified" class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

            <!-- ========================================================= -->
            <!-- KOLOM KIRI (7 Kolom): KABAR PAGUYUBAN & AGENDA LINGKUNGAN -->
            <!-- ========================================================= -->
            <div class="lg:col-span-7 space-y-6">

                <!-- Kartu 1: Papan Pengumuman Paguyuban (Live API) -->
                <section class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-base font-bold text-slate-900">Papan Pengumuman Paguyuban</h2>
                            <p class="text-xs text-slate-500 mt-0.5">Informasi resmi pengurus PPMT. Klik pengumuman untuk membaca selengkapnya.</p>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <a x-show="user?.can_manage_announcements"
                               href="/pengurus"
                               class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200 hover:bg-indigo-100 transition">
                                + Kelola
                            </a>
                            <span x-show="announcements.length > 0"
                                  class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 text-slate-600 border border-slate-200"
                                  x-text="announcements.length + ' Pengumuman'">
                            </span>
                        </div>
                    </div>

                    <div x-show="announcements.length === 0" class="px-6 py-8 text-center text-xs text-slate-500">
                        Belum ada pengumuman resmi paguyuban yang diterbitkan saat ini.
                    </div>

                    <div class="divide-y divide-slate-100">
                        <template x-for="item in announcements" :key="item.id">
                            <button type="button"
                                    @click="selectedAnnouncement = item"
                                    class="w-full px-6 py-4 flex items-start justify-between gap-4 text-left hover:bg-slate-50/90 transition cursor-pointer group">
                                <div class="space-y-1.5 min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span x-show="item.is_pinned"
                                              class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-100 text-amber-900 border border-amber-300">
                                            &#128204; Disematkan
                                        </span>
                                        <span :class="item.badgeClass"
                                              class="px-2 py-0.5 rounded-md text-[10px] font-bold border"
                                              x-text="item.category">
                                        </span>
                                        <span class="text-[11px] text-slate-400 font-medium" x-text="item.date"></span>
                                    </div>
                                    <div class="text-sm font-bold text-slate-900 group-hover:text-emerald-800 transition leading-snug"
                                         x-text="item.title">
                                    </div>
                                    <p class="text-xs text-slate-500 line-clamp-2 leading-relaxed"
                                       x-text="item.excerpt">
                                    </p>
                                </div>
                                <span class="text-slate-400 group-hover:text-emerald-700 group-hover:translate-x-0.5 transition text-lg font-medium shrink-0 mt-1">&rsaquo;</span>
                            </button>
                        </template>
                    </div>
                </section>

                <!-- Kartu 2: Agenda & Jadwal Lingkungan Minggu Ini (Mockup) -->
                <section class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100">
                        <h2 class="text-base font-bold text-slate-900">Agenda &amp; Jadwal Lingkungan</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Jadwal kegiatan rutin dan layanan kebersihan perumahan minggu ini.</p>
                    </div>

                    <div class="divide-y divide-slate-100">
                        <template x-for="ag in agendas" :key="ag.id">
                            <div class="px-6 py-4 flex items-center gap-4">
                                <div class="w-12 h-12 rounded-xl bg-emerald-50 border border-emerald-200 flex flex-col items-center justify-center shrink-0 text-emerald-900">
                                    <span class="text-[10px] font-bold uppercase leading-none" x-text="ag.dayShort"></span>
                                    <span class="text-base font-extrabold leading-tight mt-0.5" x-text="ag.dateNum"></span>
                                </div>
                                <div class="min-w-0 flex-1 space-y-0.5">
                                    <div class="text-sm font-bold text-slate-900 truncate" x-text="ag.title"></div>
                                    <div class="text-xs text-slate-500" x-text="ag.time + ' • ' + ag.location"></div>
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

                <!-- Panel 2: Status Iuran Lingkungan Kavling (Mockup) -->
                <section class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between gap-2">
                        <div>
                            <h2 class="text-base font-bold text-slate-900">Iuran Paguyuban Kavling</h2>
                            <p class="text-xs text-slate-500 mt-0.5">Periode November 2026</p>
                        </div>
                        <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-900 border border-emerald-200">
                            ✓ Lunas
                        </span>
                    </div>

                    <div class="p-5 space-y-3">
                        <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/80 flex items-center justify-between">
                            <div>
                                <div class="text-[11px] font-medium text-slate-500">Iuran Bulanan (Keamanan &amp; Sampah)</div>
                                <div class="text-base font-extrabold text-slate-900 mt-0.5">Rp 75.000</div>
                            </div>
                            <div class="text-right text-[11px] text-slate-500">
                                <div>Dibayar: 05 Nov 2026</div>
                                <div class="text-emerald-700 font-semibold">Terverifikasi Bendahara</div>
                            </div>
                        </div>

                        <button type="button"
                                @click="showDuesModal = true"
                                class="w-full py-2 px-4 rounded-xl border border-slate-300 hover:bg-slate-50 text-slate-700 text-xs font-semibold transition cursor-pointer">
                            Lihat Riwayat Pembayaran Kavling
                        </button>
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
         class="fixed inset-0 z-50 !mt-0 flex items-center justify-center p-3 sm:p-4 bg-slate-900/50 backdrop-blur-[1px] overflow-y-auto">
        <div @click.away="selectedAnnouncement = null"
             class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-[calc(100vw-1.5rem)] sm:max-w-lg max-h-[90dvh] flex flex-col overflow-hidden my-auto">
            <div class="px-5 sm:px-6 py-4 border-b border-slate-100 flex items-center justify-between gap-3 shrink-0">
                <div class="flex items-center gap-2">
                    <span :class="selectedAnnouncement?.badgeClass"
                          class="px-2 py-0.5 rounded-md text-[10px] font-bold border"
                          x-text="selectedAnnouncement?.category"></span>
                    <span class="text-xs text-slate-400" x-text="selectedAnnouncement?.date"></span>
                </div>
                <button type="button" @click="selectedAnnouncement = null" class="text-slate-400 hover:text-slate-700 text-sm p-1 cursor-pointer">✕</button>
            </div>
            <div class="p-5 sm:p-6 space-y-3 overflow-y-auto">
                <h3 class="text-base sm:text-lg font-extrabold text-slate-900 leading-snug" x-text="selectedAnnouncement?.title"></h3>
                <div class="text-xs text-slate-500" x-text="'Diterbitkan oleh: ' + (selectedAnnouncement?.author || 'Pengurus Paguyuban PPMT')"></div>
                <p class="text-xs sm:text-sm text-slate-700 leading-relaxed whitespace-pre-line pt-2 border-t border-slate-100"
                   x-text="selectedAnnouncement?.body"></p>
            </div>
            <div class="px-5 sm:px-6 py-3.5 bg-slate-50 border-t border-slate-100 flex justify-end shrink-0">
                <button type="button" @click="selectedAnnouncement = null"
                        class="px-4 py-2 rounded-xl bg-slate-900 text-white text-xs font-semibold cursor-pointer">
                    Tutup
                </button>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- MODAL RIWAYAT IURAN KAVLING (MOCKUP)                                  -->
    <!-- ===================================================================== -->
    <div x-show="showDuesModal"
         x-cloak
         class="fixed inset-0 z-50 !mt-0 flex items-center justify-center p-3 sm:p-4 bg-slate-900/50 backdrop-blur-[1px] overflow-y-auto">
        <div @click.away="showDuesModal = false"
             class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-[calc(100vw-1.5rem)] sm:max-w-md max-h-[90dvh] flex flex-col overflow-hidden my-auto">
            <div class="px-5 sm:px-6 py-4 border-b border-slate-100 flex items-center justify-between gap-3 shrink-0">
                <h3 class="text-base font-bold text-slate-900">Riwayat Iuran Paguyuban</h3>
                <button type="button" @click="showDuesModal = false" class="text-slate-400 hover:text-slate-700 text-sm p-1 cursor-pointer">✕</button>
            </div>
            <div class="divide-y divide-slate-100 overflow-y-auto">
                <template x-for="row in duesHistory" :key="row.month">
                    <div class="px-5 sm:px-6 py-3.5 flex items-center justify-between text-xs">
                        <div>
                            <div class="font-bold text-slate-900" x-text="row.month"></div>
                            <div class="text-slate-500 mt-0.5" x-text="row.paidAt"></div>
                        </div>
                        <div class="text-right">
                            <div class="font-bold text-slate-800" x-text="row.amount"></div>
                            <span class="inline-block mt-0.5 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                Lunas
                            </span>
                        </div>
                    </div>
                </template>
            </div>
            <div class="px-5 sm:px-6 py-3.5 bg-slate-50 border-t border-slate-100 flex justify-end shrink-0">
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
        showDuesModal: false,
        announcements: [],

        inviteForm: {
            unit_code: '',
            entity_type: 'resident',
            sub_role: 'family_member',
            member_valid_until: ''
        },

        // Data Mockup Agenda Lingkungan
        agendas: [
            {
                id: 1,
                dayShort: 'RAB',
                dateNum: '18',
                title: 'Pemeliharaan Tandon & Pipa Air Blok C–D',
                time: '09.00 – 12.00 WIB',
                location: 'Area Tandon Utama PPMT'
            },
            {
                id: 2,
                dayShort: 'SAB',
                dateNum: '21',
                title: 'Ronda Malam & Patroli Keamanan Akhir Pekan',
                time: '22.00 – 03.00 WIB',
                location: 'Pos Gerbang Utama (Regu Blok C)'
            },
            {
                id: 3,
                dayShort: 'MIN',
                dateNum: '22',
                title: 'Kerja Bakti Drainase & Perapian Taman Fasum',
                time: '06.30 – 09.30 WIB',
                location: 'Fasum Tengah Perumahan'
            }
        ],

        // Data Mockup Riwayat Iuran
        duesHistory: [
            { month: 'November 2026', paidAt: 'Dibayar 05 Nov 2026', amount: 'Rp 75.000' },
            { month: 'Oktober 2026', paidAt: 'Dibayar 04 Okt 2026', amount: 'Rp 75.000' },
            { month: 'September 2026', paidAt: 'Dibayar 06 Sep 2026', amount: 'Rp 75.000' },
            { month: 'Agustus 2026', paidAt: 'Dibayar 03 Agu 2026', amount: 'Rp 75.000' }
        ],

        async init() {
            if (!this.token) {
                window.location.href = '/';
                return;
            }
            await this.loadMe();
            if (this.user?.is_verified) {
                await Promise.all([
                    this.loadAnnouncements(),
                    this.loadPendingApprovals()
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

        get greetingText() {
            const hour = new Date().getHours();
            if (hour < 11) return 'Selamat Pagi';
            if (hour < 15) return 'Selamat Siang';
            if (hour < 18) return 'Selamat Sore';
            return 'Selamat Malam';
        },

        get shortName() {
            if (this.user?.display_name) return this.user.display_name;
            const full = (this.user?.name || 'Warga').trim();
            return full.split(/\s+/)[0];
        },

        get unitSummaryText() {
            const memberships = this.user?.memberships || [];
            if (memberships.length === 0) return 'Warga PPMT';
            return memberships.map(m => 'Kavling ' + m.unit.code).join(' • ');
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

        async loadAnnouncements() {
            const res = await fetch('/api/v1/announcements', { headers: this.headers() });
            if (res.ok) {
                const json = await res.json();
                this.announcements = json.data || [];
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
        }
    }
}
</script>
@endsection
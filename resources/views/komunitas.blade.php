@extends('layouts.app')

@section('title', 'Komunitas, UMKM & Peta Kavling — PPMT Digital')

@section('content')
<div x-data="ppmtCommunity()" x-init="init()" @keydown.escape.window="selectedMinute = null" class="max-w-6xl mx-auto px-4 py-8 space-y-6">

    <!-- Tab Switcher Utama (4 Tab Internal Terproteksi) -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
        <div>
            <h1 class="text-xl font-extrabold text-slate-900">Komunitas &amp; Peta Lingkungan PPMT</h1>
            <p class="text-xs text-slate-500">Eksklusif bagi warga terverifikasi &bull; Perlindungan privasi hunian, arsip musyawarah &amp; ekonomi tetangga.</p>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-4 bg-slate-100 p-1 rounded-xl text-xs font-bold shrink-0 gap-1 sm:gap-0">
            <button type="button" @click="tab = 'umkm'"
                    :class="tab === 'umkm' ? 'bg-emerald-700 text-white shadow' : 'text-slate-600 hover:text-slate-900'"
                    class="px-3 py-2 rounded-lg transition cursor-pointer">
                Pasar Tetangga
            </button>
            <button type="button" @click="tab = 'directory'"
                    :class="tab === 'directory' ? 'bg-emerald-700 text-white shadow' : 'text-slate-600 hover:text-slate-900'"
                    class="px-3 py-2 rounded-lg transition cursor-pointer">
                Direktori Warga
            </button>
            <button type="button" @click="tab = 'minutes'"
                    :class="tab === 'minutes' ? 'bg-emerald-700 text-white shadow' : 'text-slate-600 hover:text-slate-900'"
                    class="px-3 py-2 rounded-lg transition cursor-pointer">
                Notulensi &amp; Tatib
            </button>
            <button type="button" @click="tab = 'siteplan'"
                    :class="tab === 'siteplan' ? 'bg-emerald-700 text-white shadow' : 'text-slate-600 hover:text-slate-900'"
                    class="px-3 py-2 rounded-lg transition cursor-pointer">
                Peta 91 Kavling
            </button>
        </div>
    </div>

    <!-- Notifikasi -->
    <div x-show="toastMessage"
         :class="toastType === 'error' ? 'bg-rose-50 text-rose-800 border-rose-200' : 'bg-emerald-50 text-emerald-800 border-emerald-200'"
         class="p-4 rounded-xl border text-xs font-medium flex justify-between items-center">
        <span x-text="toastMessage"></span>
        <button @click="toastMessage = ''" class="cursor-pointer">✕</button>
    </div>

    <!-- BAGIAN 1: PASAR TETANGGA (UMKM) -->
    <div x-show="tab === 'umkm'" class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        <!-- Etalase Lapak Warga (8 Kolom) -->
        <div class="lg:col-span-8 space-y-4">
            <div class="flex flex-wrap gap-2">
                <select x-model="filterCategory" @change="loadStores()" class="rounded-xl border-slate-300 text-xs">
                    <option value="">Semua Kategori Usaha</option>
                    <option value="kuliner">Kuliner &amp; Katering</option>
                    <option value="sembako">Sembako &amp; Air/Gas</option>
                    <option value="jasa">Jasa &amp; Perbaikan</option>
                    <option value="pinjam_alat">Pinjam / Sewa Alat</option>
                    <option value="lainnya">Lainnya</option>
                </select>
                <select x-model="filterStatus" @change="loadStores()" class="rounded-xl border-slate-300 text-xs">
                    <option value="">Semua Status Lapak</option>
                    <option value="open">Buka Sekarang</option>
                    <option value="open_po">Open PO</option>
                    <option value="closed">Tutup</option>
                </select>
            </div>

            <div x-show="stores.length === 0" class="bg-white rounded-2xl p-10 text-center text-sm text-slate-400 border border-slate-200">
                Belum ada lapak usaha warga yang ditampilkan untuk filter ini.
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <template x-for="store in stores" :key="store.id">
                    <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex flex-col justify-between space-y-4">
                        <div class="space-y-2">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded"
                                          x-text="store.category.replace('_', ' ')"></span>
                                    <h3 class="font-bold text-base text-slate-900 mt-1" x-text="store.store_name"></h3>
                                </div>
                                <span :class="{
                                        'bg-emerald-100 text-emerald-800': store.status === 'open',
                                        'bg-amber-100 text-amber-800': store.status === 'open_po',
                                        'bg-slate-200 text-slate-600': store.status === 'closed'
                                      }"
                                      class="px-2.5 py-1 rounded-full text-[11px] font-bold shrink-0"
                                      x-text="store.status === 'open' ? '● Buka' : (store.status === 'open_po' ? '◐ Open PO' : '○ Tutup')">
                                </span>
                            </div>

                            <p class="text-xs text-slate-600 leading-relaxed" x-text="store.description || 'Lapak usaha warga internal PPMT.'"></p>
                            <div class="text-[11px] text-slate-500">
                                Kavling <strong class="text-slate-700" x-text="store.unit?.code"></strong> &bull; <span x-text="store.owner?.name"></span>
                            </div>
                        </div>

                        <div class="pt-3 border-t border-slate-100">
                            <template x-if="store.owner_user_id === me?.id">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-[11px] font-semibold text-slate-500">Ubah Status:</span>
                                    <div class="flex gap-1">
                                        <button type="button" @click="changeStoreStatus(store.id, 'open')" class="px-2 py-1 rounded text-[11px] font-bold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 cursor-pointer">Buka</button>
                                        <button type="button" @click="changeStoreStatus(store.id, 'open_po')" class="px-2 py-1 rounded text-[11px] font-bold bg-amber-50 text-amber-700 hover:bg-amber-100 cursor-pointer">PO</button>
                                        <button type="button" @click="changeStoreStatus(store.id, 'closed')" class="px-2 py-1 rounded text-[11px] font-bold bg-slate-100 text-slate-700 hover:bg-slate-200 cursor-pointer">Tutup</button>
                                    </div>
                                </div>
                            </template>

                            <template x-if="store.owner_user_id !== me?.id">
                                <a :href="buildWaUrl(store)" target="_blank"
                                   class="w-full py-2 px-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold flex items-center justify-center gap-1.5 transition">
                                    <span>Pesan via WhatsApp Tetangga</span>
                                </a>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Form Buka Lapak Baru (4 Kolom) -->
        <div class="lg:col-span-4 bg-white rounded-2xl p-5 border border-slate-200 shadow-sm space-y-4">
            <div class="border-b border-slate-100 pb-3">
                <h2 class="text-base font-bold text-slate-900">Buka Lapak Usaha Warga</h2>
                <p class="text-xs text-slate-500">Promosikan usaha rumahan, stok galon/gas, atau jasa ke seluruh tetangga PPMT.</p>
            </div>

            <form @submit.prevent="createStore()" class="space-y-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Kavling Lokasi Usaha *</label>
                    <select x-model="storeForm.unit_code" required class="w-full rounded-xl border-slate-300 text-xs">
                        <template x-for="m in (me?.memberships || [])" :key="m.id">
                            <option :value="m.unit.code" x-text="'Kavling ' + m.unit.code"></option>
                        </template>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lapak / Usaha *</label>
                    <input type="text" x-model="storeForm.store_name" required placeholder="Contoh: Warung Sarapan Bu Siti"
                           class="w-full rounded-xl border-slate-300 text-xs">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Kategori *</label>
                    <select x-model="storeForm.category" class="w-full rounded-xl border-slate-300 text-xs">
                        <option value="kuliner">Kuliner &amp; Katering</option>
                        <option value="sembako">Sembako &amp; Air/Gas</option>
                        <option value="jasa">Jasa &amp; Perbaikan</option>
                        <option value="pinjam_alat">Pinjam / Sewa Alat</option>
                        <option value="lainnya">Lainnya</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Deskripsi Singkat &amp; Daftar Menu/Jasa</label>
                    <textarea x-model="storeForm.description" rows="3" placeholder="Contoh: Sedia nasi pecel pagi, galon isi ulang, dan gas LPG 3kg siap antar blok."
                              class="w-full rounded-xl border-slate-300 text-xs"></textarea>
                </div>

                <button type="submit"
                        class="w-full py-2.5 px-4 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold shadow-sm transition cursor-pointer">
                    Publikasikan ke Pasar Tetangga
                </button>
            </form>
        </div>
    </div>

    <!-- BAGIAN 2: DIREKTORI WARGA TERFILTER PRIVASI -->
    <div x-show="tab === 'directory'" class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-4">
        <div class="border-b border-slate-100 pb-3">
            <h2 class="text-lg font-bold text-slate-900">Direktori Warga Terverifikasi</h2>
            <p class="text-xs text-slate-500">Nomor WhatsApp dan status kepemilikan hanya ditampilkan jika diizinkan oleh pengaturan privasi masing-masing warga.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <template x-for="resident in residents" :key="resident.id">
                <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/60 space-y-2">
                    <div class="flex items-center justify-between gap-2">
                        <div class="font-bold text-sm text-slate-900" x-text="resident.name"></div>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800"
                              x-text="resident.reputation_badge?.label"></span>
                    </div>

                    <div class="flex flex-wrap gap-1.5">
                        <template x-for="u in resident.units" :key="u.unit_code">
                            <span class="px-2 py-0.5 rounded bg-white border border-slate-200 text-[11px] font-semibold text-slate-700"
                                  x-text="u.unit_code + ' (' + u.zone + ')' + (u.sub_role ? ' • ' + u.sub_role : '')">
                            </span>
                        </template>
                    </div>

                    <div class="pt-1 text-xs">
                        <template x-if="resident.phone">
                            <a :href="'https://wa.me/' + resident.phone.replace(/^0/, '62')" target="_blank"
                               class="text-emerald-700 font-semibold hover:underline"
                               x-text="'WA: ' + resident.phone"></a>
                        </template>
                        <template x-if="!resident.phone">
                            <span class="text-slate-400 italic">Nomor WhatsApp disamarkan (Privat)</span>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- BAGIAN 3: NOTULENSI MUSYAWARAH & TATA TERTIB PAGUYUBAN -->
    <div x-show="tab === 'minutes'" class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
            <div>
                <h2 class="text-lg font-bold text-slate-900">Arsip Notulensi Rapat &amp; Tata Tertib Lingkungan</h2>
                <p class="text-xs text-slate-500">Dokumen kesepakatan musyawarah warga dan tata tertib yang berlaku di Perumahan Pesona Mandiri Turen.</p>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <select x-model="filterMinuteCategory" class="rounded-xl border-slate-300 text-xs">
                    <option value="">Semua Dokumen</option>
                    <option value="musyawarah_warga">Musyawarah Warga</option>
                    <option value="tata_tertib">Tata Tertib Lingkungan</option>
                    <option value="rapat_pengurus">Rapat Pengurus</option>
                </select>
                <a x-show="me?.can_manage_announcements"
                   href="/pengurus"
                   class="px-3 py-2 rounded-xl bg-indigo-50 text-indigo-700 border border-indigo-200 hover:bg-indigo-100 text-xs font-bold transition">
                    + Catat
                </a>
            </div>
        </div>

        <div x-show="filteredMinutes.length === 0"
             class="p-10 rounded-xl bg-slate-50 border border-dashed border-slate-300 text-center text-xs text-slate-500">
            Belum ada arsip notulensi musyawarah atau tata tertib pada kategori ini.
        </div>

        <div class="divide-y divide-slate-100 border border-slate-200 rounded-xl overflow-hidden" x-show="filteredMinutes.length > 0">
            <template x-for="m in filteredMinutes" :key="m.id">
                <button type="button"
                        @click="selectedMinute = m"
                        class="w-full px-5 py-4 flex items-start justify-between gap-4 text-left hover:bg-slate-50/90 transition cursor-pointer group">
                    <div class="space-y-1.5 min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span :class="m.badgeClass"
                                  class="px-2 py-0.5 rounded-md text-[10px] font-bold border"
                                  x-text="m.category">
                            </span>
                            <span x-show="m.visibility === 'internal_pengurus'"
                                  class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-300">
                                Internal Pengurus
                            </span>
                            <span class="text-[11px] text-slate-400 font-medium"
                                  x-text="m.meeting_date_formatted + (m.location ? ' • ' + m.location : '')">
                            </span>
                        </div>
                        <div class="text-sm font-bold text-slate-900 group-hover:text-emerald-800 transition leading-snug"
                             x-text="m.title">
                        </div>
                        <p class="text-xs text-slate-500 line-clamp-2 leading-relaxed" x-text="m.excerpt"></p>
                    </div>
                    <span class="text-slate-400 group-hover:text-emerald-700 group-hover:translate-x-0.5 transition text-lg font-medium shrink-0 mt-1">&rsaquo;</span>
                </button>
            </template>
        </div>
    </div>

    <!-- BAGIAN 4: PETA 91 KAVLING INTERNAL -->
    <div x-show="tab === 'siteplan'" class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-4">
            <div>
                <h2 class="text-lg font-bold text-slate-900">Peta Kavling Internal PPMT (<span x-text="units.length"></span> Unit)</h2>
                <p class="text-xs text-slate-500">Pemetaan blok, zona koordinasi, dan status keterisian hunian (khusus internal warga).</p>
            </div>
            <div class="flex flex-wrap gap-3 text-xs">
                <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-slate-200 border border-slate-300"></span> Belum Klaim</span>
                <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-amber-200 border border-amber-400"></span> Menunggu</span>
                <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-emerald-200 border border-emerald-400"></span> Terisi</span>
            </div>
        </div>

        <div class="space-y-6">
            <template x-for="(groupUnits, blockName) in groupedUnits" :key="blockName">
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-600"
                            x-text="formatBlockTitle(blockName)"></h3>
                        <span class="text-[11px] text-slate-400" x-text="groupUnits.length + ' unit'"></span>
                    </div>

                    <div class="grid grid-cols-5 sm:grid-cols-8 md:grid-cols-10 gap-2">
                        <template x-for="unit in groupUnits" :key="unit.id">
                            <div :class="{
                                    'bg-teal-50 text-teal-800 border-teal-300': unit.unit_type === 'fasum',
                                    'bg-emerald-50 text-emerald-900 border-emerald-300': ['occupied', 'rented'].includes(unit.occupancy_status) && unit.unit_type !== 'fasum',
                                    'bg-amber-50 text-amber-900 border-amber-300': unit.occupancy_status === 'pending',
                                    'bg-slate-100 text-slate-700 border-slate-200': unit.occupancy_status === 'unclaimed' && unit.unit_type !== 'fasum'
                                 }"
                                 class="px-2 py-2 rounded-lg border text-xs text-center flex flex-col items-center justify-center">
                                <span class="font-bold" x-text="unit.code"></span>
                                <span class="text-[9px] opacity-75" x-text="unit.zone.replace('ZONA-', 'Z')"></span>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- MODAL BACA DETAIL NOTULENSI / TATA TERTIB                             -->
    <!-- ===================================================================== -->
    <div x-show="selectedMinute !== null"
         x-cloak
         class="fixed inset-0 z-50 !mt-0 flex items-center justify-center p-3 sm:p-4 bg-slate-900/50 backdrop-blur-[1px] overflow-y-auto">
        <div @click.away="selectedMinute = null"
             class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-[calc(100vw-1.5rem)] sm:max-w-lg max-h-[90dvh] flex flex-col overflow-hidden my-auto">
            <div class="px-5 sm:px-6 py-4 border-b border-slate-100 flex items-center justify-between gap-3 shrink-0">
                <div class="flex flex-wrap items-center gap-2">
                    <span :class="selectedMinute?.badgeClass"
                          class="px-2 py-0.5 rounded-md text-[10px] font-bold border"
                          x-text="selectedMinute?.category"></span>
                    <span class="text-xs text-slate-400" x-text="selectedMinute?.meeting_date_formatted"></span>
                </div>
                <button type="button" @click="selectedMinute = null" class="text-slate-400 hover:text-slate-700 text-sm p-1 cursor-pointer">✕</button>
            </div>
            <div class="p-5 sm:p-6 space-y-3 overflow-y-auto">
                <h3 class="text-base sm:text-lg font-extrabold text-slate-900 leading-snug" x-text="selectedMinute?.title"></h3>
                <div class="text-xs text-slate-500 space-y-0.5">
                    <div x-show="selectedMinute?.location" x-text="'Lokasi / Forum: ' + selectedMinute?.location"></div>
                    <div x-text="'Dicatat oleh: ' + (selectedMinute?.author || 'Sekretariat Paguyuban PPMT')"></div>
                </div>
                <p class="text-xs sm:text-sm text-slate-700 leading-relaxed whitespace-pre-line pt-2 border-t border-slate-100"
                   x-text="selectedMinute?.decisions"></p>
            </div>
            <div class="px-5 sm:px-6 py-3.5 bg-slate-50 border-t border-slate-100 flex justify-end shrink-0">
                <button type="button" @click="selectedMinute = null"
                        class="px-4 py-2 rounded-xl bg-slate-900 text-white text-xs font-semibold cursor-pointer">
                    Tutup
                </button>
            </div>
        </div>
    </div>

</div>

<script>
function ppmtCommunity() {
    return {
        token: localStorage.getItem('ppmt_token'),
        tab: 'umkm',
        me: null,
        stores: [],
        residents: [],
        units: [],
        meetingMinutes: [],
        selectedMinute: null,
        filterCategory: '',
        filterStatus: '',
        filterMinuteCategory: '',
        toastMessage: '',
        toastType: 'success',
        storeForm: {
            unit_code: '',
            store_name: '',
            category: 'kuliner',
            description: ''
        },

        async init() {
            if (!this.token) {
                window.location.href = '/';
                return;
            }
            const meRes = await fetch('/api/v1/me', { headers: this.headers() });
            if (!meRes.ok) {
                window.location.href = '/';
                return;
            }
            const meJson = await meRes.json();
            this.me = meJson.data;

            if (!this.me.is_verified) {
                window.location.href = '/dashboard';
                return;
            }

            if (this.me.memberships?.length > 0) {
                this.storeForm.unit_code = this.me.memberships[0].unit.code;
            }

            await Promise.all([
                this.loadStores(),
                this.loadDirectory(),
                this.loadUnits(),
                this.loadMeetingMinutes()
            ]);
        },

        headers() {
            return {
                'Authorization': 'Bearer ' + this.token,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            };
        },

        get filteredMinutes() {
            if (!this.filterMinuteCategory) return this.meetingMinutes;
            return this.meetingMinutes.filter(m => m.category_code === this.filterMinuteCategory);
        },

        async loadStores() {
            const params = new URLSearchParams();
            if (this.filterCategory) params.append('category', this.filterCategory);
            if (this.filterStatus) params.append('status', this.filterStatus);

            const res = await fetch('/api/v1/umkm/stores?' + params.toString(), { headers: this.headers() });
            if (res.ok) {
                const json = await res.json();
                this.stores = json.data || [];
            }
        },

        async loadDirectory() {
            const res = await fetch('/api/v1/residents/directory', { headers: this.headers() });
            if (res.ok) {
                const json = await res.json();
                this.residents = json.data || [];
            }
        },

        async loadMeetingMinutes() {
            const res = await fetch('/api/v1/meeting-minutes', { headers: this.headers() });
            if (res.ok) {
                const json = await res.json();
                this.meetingMinutes = json.data || [];
            }
        },

        async loadUnits() {
            const res = await fetch('/api/v1/public/units');
            if (res.ok) {
                const json = await res.json();
                this.units = json.data || [];
            }
        },

        get groupedUnits() {
            return this.units.reduce((groups, unit) => {
                if (!groups[unit.block]) groups[unit.block] = [];
                groups[unit.block].push(unit);
                return groups;
            }, {});
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

        async createStore() {
            const res = await fetch('/api/v1/umkm/stores', {
                method: 'POST',
                headers: this.headers(),
                body: JSON.stringify(this.storeForm)
            });
            const json = await res.json();
            if (res.ok) {
                this.toastType = 'success';
                this.toastMessage = json.message;
                this.storeForm.store_name = '';
                this.storeForm.description = '';
                await this.loadStores();
            } else {
                this.toastType = 'error';
                this.toastMessage = json.message || 'Gagal membuka lapak.';
            }
        },

        async changeStoreStatus(storeId, status) {
            const res = await fetch(`/api/v1/umkm/stores/${storeId}/status`, {
                method: 'PATCH',
                headers: this.headers(),
                body: JSON.stringify({ status })
            });
            const json = await res.json();
            if (res.ok) {
                this.toastType = 'success';
                this.toastMessage = json.message;
                await this.loadStores();
            }
        },

        buildWaUrl(store) {
            const phone = (store.whatsapp_number || '').replace(/^0/, '62').replace(/[^0-9]/g, '');
            const myUnit = this.me?.memberships?.[0]?.unit?.code || 'PPMT';
            const text = encodeURIComponent(
                `Halo *${store.store_name}*, saya *${this.me?.name}* dari *Kavling ${myUnit}* (PPMT). Saya ingin memesan produk/jasa dari Pasar Tetangga PPMT.`
            );
            return `https://wa.me/${phone}?text=${text}`;
        }
    }
}
</script>
@endsection
@extends('layouts.app')

@section('title', 'Agenda Warga — PPMT Digital')
@section('subpage_title', 'Agenda & Jadwal Warga')
@section('subpage_back_url', '/dashboard')

@section('content')
<div x-data="ppmtAgendaDirectory()"
     x-init="init()"
     class="max-w-4xl mx-auto px-4 py-6 sm:py-8 space-y-5">

    <!-- Loading State -->
    <div x-show="loading" class="bg-white rounded-2xl p-12 text-center text-sm text-slate-500 border border-slate-200">
        Memuat daftar agenda warga PPMT...
    </div>

    <div x-show="!loading && user" x-cloak class="space-y-5">

        <!-- Notifikasi Salin / Error -->
        <div x-show="toastMessage"
             x-cloak
             :class="toastType === 'error' ? 'bg-rose-50 text-rose-800 border-rose-200' : 'bg-emerald-50 text-emerald-800 border-emerald-200'"
             class="p-3.5 rounded-xl border text-xs sm:text-sm font-medium flex justify-between items-center shadow-xs">
            <span x-text="toastMessage"></span>
            <button type="button" @click="toastMessage = ''" class="text-xs opacity-70 hover:opacity-100 cursor-pointer">✕</button>
        </div>

        <!-- Banner Peringatan Akun Pending -->
        <div x-show="!user?.is_verified"
             x-cloak
             class="bg-amber-50 border-l-4 border-amber-500 p-5 rounded-r-2xl shadow-sm space-y-1">
            <h2 class="font-bold text-amber-900 text-base">⏳ Akun Anda Menunggu Verifikasi Sosial Tetangga</h2>
            <p class="text-xs sm:text-sm text-amber-800 leading-relaxed">
                Jadwal kegiatan internal PPMT hanya dapat diakses setelah klaim kavling Anda disahkan oleh <strong>Koordinator Zona/Blok</strong> atau <strong>Pemilik Utama</strong> unit Anda.
            </p>
        </div>

        <!-- Konten Utama (Hanya Warga Terverifikasi) -->
        <div x-show="user?.is_verified" x-cloak class="space-y-5">

            <!-- Bar Pencarian, Filter Sasaran, Filter Kategori & Aksi Pengurus -->
            <section class="bg-white rounded-2xl p-4 sm:p-5 shadow-xs border border-slate-200 space-y-3.5">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="relative flex-1">
                        <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                        </svg>
                        <input type="text"
                               x-model="searchQuery"
                               placeholder="Cari judul agenda, lokasi, atau catatan..."
                               class="w-full pl-10 pr-4 py-2 rounded-xl border-slate-300 text-xs sm:text-sm focus:border-emerald-600 focus:ring-emerald-600">
                    </div>

                    <!-- Filter Sasaran / Visibilitas -->
                    <select x-model="selectedVisibility"
                            aria-label="Filter Sasaran Agenda"
                            class="rounded-xl border-slate-300 text-xs sm:text-sm font-medium text-slate-700 focus:border-emerald-600 focus:ring-emerald-600">
                        <option value="all">Semua Sasaran</option>
                        <option value="public">Seluruh Warga</option>
                        <option value="block">Khusus Blok</option>
                        <option value="invite_only">Undangan Khusus</option>
                    </select>

                    <a x-show="canManageAgenda"
                       href="/pengurus/publikasi#agenda"
                       class="inline-flex items-center justify-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200 hover:bg-indigo-100 transition shrink-0">
                        <span>+ Kelola Agenda</span>
                    </a>
                </div>

                <!-- Chip Filter Kategori -->
                <div class="flex items-center gap-1.5 overflow-x-auto pb-1">
                    <template x-for="cat in categories" :key="cat.code">
                        <button type="button"
                                @click="selectedCategory = cat.code"
                                :class="selectedCategory === cat.code
                                    ? 'bg-emerald-800 text-white border-emerald-800 shadow-2xs'
                                    : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100'"
                                class="px-3 py-1.5 rounded-full text-xs font-semibold border transition whitespace-nowrap cursor-pointer">
                            <span x-text="cat.label"></span>
                        </button>
                    </template>
                </div>
            </section>

            <!-- Empty State Jika Tidak Ada Agenda -->
            <div x-show="filteredAgendas.length === 0"
                 class="bg-white rounded-2xl p-10 text-center border border-slate-200 space-y-1.5">
                <div class="text-sm font-bold text-slate-700">Tidak ada agenda yang sesuai</div>
                <p class="text-xs text-slate-500">
                    Belum ada jadwal kegiatan terkini untuk filter yang dipilih.
                </p>
            </div>

            <!-- Daftar Kartu Agenda Terkini -->
            <div x-show="filteredAgendas.length > 0" class="space-y-3.5">
                <template x-for="ag in filteredAgendas" :key="ag.id">
                    <article :class="isToday(ag.event_date) ? 'border-emerald-400 ring-1 ring-emerald-200/70' : 'border-slate-200'"
                             class="bg-white rounded-2xl p-4 sm:p-5 shadow-xs border flex items-start gap-4 transition">

                        <!-- Kotak Kalender Besar -->
                        <div :class="isToday(ag.event_date) ? 'bg-emerald-700 border-emerald-800 text-white' : 'bg-emerald-50 border-emerald-200 text-emerald-950'"
                             class="w-16 h-20 sm:w-[4.5rem] sm:h-[5.5rem] rounded-2xl border flex flex-col items-center justify-center shrink-0 shadow-2xs">
                            <span :class="isToday(ag.event_date) ? 'text-emerald-200' : 'text-emerald-700'"
                                  class="text-[11px] font-bold uppercase tracking-wider leading-none"
                                  x-text="ag.dayShort"></span>
                            <span class="text-2xl sm:text-[26px] font-extrabold leading-tight my-0.5"
                                  x-text="ag.dateNum"></span>
                            <span :class="isToday(ag.event_date) ? 'text-emerald-100' : 'text-emerald-800'"
                                  class="text-[11px] font-extrabold uppercase tracking-wider leading-none"
                                  x-text="indoMonthShort(ag.monthShort)"></span>
                        </div>

                        <!-- Detail Informasi Agenda -->
                        <div class="min-w-0 flex-1 space-y-2.5">
                            <div class="flex flex-wrap items-center gap-2">
                                <span x-show="isToday(ag.event_date)"
                                      class="px-2 py-0.5 rounded-md text-[10px] font-extrabold uppercase tracking-wider bg-amber-400 text-slate-950">
                                    Hari Ini
                                </span>
                                <span :class="ag.badgeClass || 'bg-slate-100 text-slate-700 border-slate-200'"
                                      class="px-2.5 py-0.5 rounded-md text-[11px] font-bold border"
                                      x-text="ag.category"></span>

                                <!-- Badge Visibilitas / Sasaran (Tampil jika bukan Seluruh Warga) -->
                                <span x-show="ag.visibility && ag.visibility !== 'public'"
                                      :class="ag.visibility === 'invite_only'
                                          ? 'bg-purple-50 text-purple-800 border-purple-200'
                                          : 'bg-amber-50 text-amber-800 border-amber-200'"
                                      class="px-2.5 py-0.5 rounded-md text-[11px] font-bold border"
                                      x-text="ag.visibility_label"></span>

                                <span class="text-xs text-slate-400 font-medium" x-text="ag.event_date_formatted"></span>
                            </div>

                            <h2 class="text-base sm:text-lg font-extrabold text-slate-900 leading-snug"
                                x-text="ag.title"></h2>

                            <div class="flex flex-wrap items-center gap-x-5 gap-y-1.5 text-xs sm:text-[13px] text-slate-600">
                                <!-- Jam Pelaksanaan -->
                                <span class="inline-flex items-center gap-1.5 font-medium">
                                    <svg class="w-4 h-4 text-emerald-700 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span x-text="ag.time"></span>
                                </span>

                                <!-- Lokasi Pelaksanaan -->
                                <span class="inline-flex items-center gap-1.5 font-medium">
                                    <svg class="w-4 h-4 text-emerald-700 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                                    </svg>
                                    <span x-text="ag.location"></span>
                                </span>
                            </div>

                            <!-- Catatan Tambahan -->
                            <div x-show="ag.notes"
                                 class="p-3 rounded-xl bg-slate-50 border border-slate-200/80 text-xs text-slate-600 leading-relaxed whitespace-pre-line"
                                 x-text="ag.notes"></div>

                            <!-- Footer Kartu: Penulis & Tombol Salin ke WhatsApp -->
                            <div class="pt-1 flex items-center justify-between gap-3 border-t border-slate-100">
                                <span class="text-[11px] text-slate-400"
                                      x-text="'Diterbitkan oleh ' + (ag.author || 'Sekretariat PPMT')"></span>

                                <button type="button"
                                        @click="copyAgendaInfo(ag)"
                                        :class="copiedId === ag.id
                                            ? 'bg-emerald-50 text-emerald-800 border-emerald-300'
                                            : 'bg-white hover:bg-emerald-50 text-slate-700 hover:text-emerald-800 border-slate-200 hover:border-emerald-300'"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border text-xs font-semibold transition cursor-pointer shrink-0">
                                    <svg x-show="copiedId !== ag.id" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                    <svg x-show="copiedId === ag.id" x-cloak class="w-3.5 h-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span x-text="copiedId === ag.id ? 'Tersalin' : 'Salin Info'"></span>
                                </button>
                            </div>
                        </div>
                    </article>
                </template>
            </div>

        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function ppmtAgendaDirectory() {
    return {
        token: localStorage.getItem('ppmt_token'),
        user: null,
        loading: true,
        agendas: [],
        searchQuery: '',
        selectedCategory: 'all',
        selectedVisibility: 'all',
        copiedId: null,
        toastMessage: '',
        toastType: 'success',
        todayIso: new Date().toLocaleDateString('en-CA'),

        categories: [
            { code: 'all', label: 'Semua Kategori' },
            { code: 'kegiatan', label: 'Kegiatan Warga' },
            { code: 'musyawarah', label: 'Musyawarah / Rapat' },
            { code: 'kebersihan', label: 'Kebersihan & Sampah' },
            { code: 'keamanan', label: 'Keamanan & Ronda' },
            { code: 'pemeliharaan', label: 'Pemeliharaan Fasum' }
        ],

        async init() {
            if (!this.token) {
                window.location.href = '/';
                return;
            }
            await this.loadMe();
            if (this.user?.is_verified) {
                await this.loadAgendas();
            }
        },

        headers() {
            return {
                'Authorization': 'Bearer ' + this.token,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            };
        },

        get canManageAgenda() {
            return Boolean(this.user?.can_manage_announcements || this.user?.is_global_governor);
        },

        get filteredAgendas() {
            const q = this.searchQuery.trim().toLowerCase();
            return this.agendas.filter(ag => {
                const matchCat = this.selectedCategory === 'all' || ag.category_code === this.selectedCategory;
                if (!matchCat) return false;

                const agVis = ag.visibility || 'public';
                const matchVis = this.selectedVisibility === 'all' || agVis === this.selectedVisibility;
                if (!matchVis) return false;

                if (!q) return true;
                return (ag.title || '').toLowerCase().includes(q)
                    || (ag.location || '').toLowerCase().includes(q)
                    || (ag.notes || '').toLowerCase().includes(q)
                    || (ag.visibility_label || '').toLowerCase().includes(q);
            });
        },

        isToday(eventDate) {
            return eventDate === this.todayIso;
        },

        indoMonthShort(month) {
            const key = String(month || '').trim().toUpperCase();
            const map = {
                'JAN': 'JAN', 'JANUARY': 'JAN', 'JANUARI': 'JAN',
                'FEB': 'FEB', 'FEBRUARY': 'FEB', 'FEBRUARI': 'FEB',
                'MAR': 'MAR', 'MARCH': 'MAR', 'MARET': 'MAR',
                'APR': 'APR', 'APRIL': 'APR',
                'MAY': 'MEI', 'MEI': 'MEI',
                'JUN': 'JUN', 'JUNE': 'JUN', 'JUNI': 'JUN',
                'JUL': 'JUL', 'JULY': 'JUL', 'JULI': 'JUL',
                'AUG': 'AGU', 'AUGUST': 'AGU', 'AGUSTUS': 'AGU', 'AGT': 'AGU',
                'SEP': 'SEP', 'SEPTEMBER': 'SEP',
                'OCT': 'OKT', 'OCTOBER': 'OKT', 'OKTOBER': 'OKT',
                'NOV': 'NOV', 'NOVEMBER': 'NOV',
                'DEC': 'DES', 'DECEMBER': 'DES', 'DESEMBER': 'DES'
            };
            return map[key] || key;
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

        async loadAgendas() {
            const res = await fetch('/api/v1/agendas', { headers: this.headers() });
            if (res.ok) {
                const json = await res.json();
                this.agendas = json.data || [];
            }
        },

        async copyAgendaInfo(ag) {
            if (!ag) return;
            const lines = [
                `*AGENDA WARGA PPMT*`,
                `*${ag.title}*`,
                `📅 ${ag.dayShort}, ${ag.event_date_formatted}`,
                `⏰ ${ag.time}`,
                `📍 ${ag.location}`,
            ];
            if (ag.visibility && ag.visibility !== 'public' && ag.visibility_label) {
                lines.push(`👥 Sasaran: ${ag.visibility_label}`);
            }
            if (ag.notes) {
                lines.push(`\nCatatan:\n${ag.notes}`);
            }
            lines.push(`\n— Sekretariat Paguyuban PPMT Digital`);
            const text = lines.join('\n');

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
                this.copiedId = ag.id;
                setTimeout(() => { this.copiedId = null; }, 2000);
            } catch (e) {
                this.toastType = 'error';
                this.toastMessage = 'Gagal menyalin informasi agenda.';
            }
        }
    }
}
</script>
@endpush
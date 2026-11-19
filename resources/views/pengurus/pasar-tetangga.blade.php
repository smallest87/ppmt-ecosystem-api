@extends('layouts.app')

@section('title', 'Kurasi Pasar Tetangga — Panel Seksi Ekonomi PPMT')
@section('subpage_title', 'Kurasi Pasar Tetangga & UMKM')
@section('subpage_back_url', '/pengurus')

@section('content')
<div x-data="ppmtMarketplaceGovernance()"
     x-init="init()"
     @keydown.escape.window="moderatingStore = null"
     class="max-w-6xl mx-auto px-4 py-6 sm:py-8 space-y-6">

    <!-- Loading -->
    <div x-show="loading" class="bg-white rounded-2xl p-12 text-center text-sm text-slate-500 border border-slate-200">
        Memuat data lapak usaha warga &amp; konfigurasi etalase Pasar Tetangga...
    </div>

    <div x-show="!loading && user" x-cloak class="space-y-6">

        <!-- Toast -->
        <div x-show="toastMessage"
             x-cloak
             :class="toastType === 'error' ? 'bg-rose-50 text-rose-800 border-rose-200' : 'bg-emerald-50 text-emerald-800 border-emerald-200'"
             class="p-4 rounded-xl border text-sm font-medium flex justify-between items-center shadow-sm">
            <span x-text="toastMessage"></span>
            <button type="button" @click="toastMessage = ''" class="text-xs opacity-70 hover:opacity-100 cursor-pointer">✕</button>
        </div>

        <!-- Header Seksi Ekonomi -->
        <section class="bg-gradient-to-r from-teal-950 via-emerald-950 to-slate-900 text-white rounded-2xl p-5 sm:p-6 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="space-y-1.5 max-w-2xl">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-teal-400/20 border border-teal-300/30 text-teal-200 text-xs font-bold">
                    <span>Ruang Kerja Seksi Ekonomi &amp; Ketua Paguyuban</span>
                </div>
                <h1 class="text-xl sm:text-2xl font-extrabold">Kurasi Etalase &amp; Pemberdayaan UMKM Warga</h1>
                <p class="text-xs sm:text-sm text-teal-100/90 leading-relaxed">
                    Atur maksimal <strong>3 lapak Sorotan Pekan Ini</strong> untuk promosi bazar/program warga, sementara seluruh lapak lainnya tetap tampil bergantian secara adil tiap jam dengan algoritma <em>Round-Robin</em>.
                </p>
            </div>

            <a href="/komunitas/pasar-tetangga"
               class="px-4 py-2.5 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-950 text-xs sm:text-sm font-extrabold shadow-sm transition text-center shrink-0">
                Lihat Etalase Publik &rarr;
            </a>
        </section>

        <!-- Ringkasan Statistik UMKM Warga -->
        <section x-show="stats !== null" class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200 shadow-2xs">
                <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Total Lapak Warga</div>
                <div class="mt-1.5 flex items-baseline gap-1.5">
                    <span class="text-2xl font-extrabold text-slate-900" x-text="stats?.total_stores ?? 0"></span>
                    <span class="text-xs text-slate-400">unit usaha</span>
                </div>
                <div class="text-[11px] text-emerald-700 font-semibold mt-1"
                     x-text="(stats?.active_stores ?? 0) + ' aktif di etalase'"></div>
            </div>

            <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200 shadow-2xs">
                <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Total Produk &amp; Jasa</div>
                <div class="mt-1.5 flex items-baseline gap-1.5">
                    <span class="text-2xl font-extrabold text-emerald-700" x-text="stats?.total_items ?? 0"></span>
                    <span class="text-xs text-slate-400">item katalog</span>
                </div>
                <div class="text-[11px] text-slate-500 mt-1">Tersebar di seluruh kategori</div>
            </div>

            <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200 shadow-2xs">
                <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Kuota Sorotan Pekan Ini</div>
                <div class="mt-1.5 flex items-baseline gap-1.5">
                    <span class="text-2xl font-extrabold text-amber-600" x-text="stats?.featured_stores ?? 0"></span>
                    <span class="text-xs text-slate-400">/ 3 slot</span>
                </div>
                <div class="text-[11px] text-slate-500 mt-1">Tampil berbadge emas di atas</div>
            </div>

            <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200 shadow-2xs">
                <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Ditangguhkan</div>
                <div class="mt-1.5 flex items-baseline gap-1.5">
                    <span :class="(stats?.suspended_stores ?? 0) > 0 ? 'text-rose-600' : 'text-slate-900'"
                          class="text-2xl font-extrabold"
                          x-text="stats?.suspended_stores ?? 0"></span>
                    <span class="text-xs text-slate-400">lapak</span>
                </div>
                <div class="text-[11px] text-slate-500 mt-1">Disembunyikan sementara</div>
            </div>
        </section>

        <!-- Filter & Pencarian Lapak -->
        <section class="bg-white rounded-2xl p-4 border border-slate-200 shadow-2xs flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
            <input type="text"
                   x-model="search"
                   placeholder="Cari nama lapak, pemilik, atau kode kavling..."
                   class="w-full sm:max-w-sm rounded-xl border-slate-300 text-xs sm:text-sm">

            <div class="flex flex-wrap gap-1.5">
                <button type="button"
                        @click="filter = 'all'"
                        :class="filter === 'all' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700'"
                        class="px-3 py-1.5 rounded-lg text-xs font-bold cursor-pointer">
                    Semua (<span x-text="stores.length"></span>)
                </button>
                <button type="button"
                        @click="filter = 'featured'"
                        :class="filter === 'featured' ? 'bg-amber-500 text-slate-950' : 'bg-amber-50 text-amber-800'"
                        class="px-3 py-1.5 rounded-lg text-xs font-bold cursor-pointer">
                    ★ Sorotan (<span x-text="stores.filter(s => s.is_currently_featured).length"></span>)
                </button>
                <button type="button"
                        @click="filter = 'suspended'"
                        :class="filter === 'suspended' ? 'bg-rose-700 text-white' : 'bg-rose-50 text-rose-800'"
                        class="px-3 py-1.5 rounded-lg text-xs font-bold cursor-pointer">
                    Ditangguhkan (<span x-text="stores.filter(s => s.is_suspended).length"></span>)
                </button>
            </div>
        </section>

        <!-- Daftar Lapak Warga -->
        <section class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden divide-y divide-slate-100">
            <div x-show="filteredStores.length === 0" class="p-10 text-center text-xs text-slate-500">
                Tidak ada data lapak yang cocok dengan filter saat ini.
            </div>

            <template x-for="store in filteredStores" :key="store.id">
                <div :class="store.is_suspended ? 'bg-rose-50/40' : (store.is_currently_featured ? 'bg-amber-50/30' : '')"
                     class="p-4 sm:p-5 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
                    <div class="space-y-1.5 min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="px-2 py-0.5 rounded-md bg-slate-900 text-white font-mono text-xs font-bold"
                                  x-text="store.unit?.code || '-'"></span>
                            <h2 class="text-sm sm:text-base font-extrabold text-slate-900" x-text="store.store_name"></h2>
                            <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-700 text-[11px] font-semibold uppercase"
                                  x-text="store.category"></span>

                            <span x-show="store.is_currently_featured"
                                  class="px-2.5 py-0.5 rounded-full bg-amber-400 text-slate-950 text-[11px] font-extrabold">
                                ★ Sorotan Pekan Ini
                            </span>

                            <span x-show="store.is_suspended"
                                  class="px-2.5 py-0.5 rounded-full bg-rose-600 text-white text-[11px] font-bold">
                                Ditangguhkan
                            </span>
                        </div>

                        <div class="text-xs text-slate-600"
                             x-text="'Pemilik: ' + (store.owner?.profile?.display_name || store.owner?.name || '-') + ' • ' + store.items_count + ' item katalog • Status: ' + store.status.toUpperCase()"></div>

                        <p x-show="store.description"
                           class="text-xs text-slate-500 line-clamp-2"
                           x-text="store.description"></p>

                        <div x-show="store.moderation_note"
                             class="p-2.5 rounded-xl bg-rose-50 border border-rose-200 text-xs text-rose-900">
                            <strong>Catatan Seksi Ekonomi:</strong> <span x-text="store.moderation_note"></span>
                        </div>
                    </div>

                    <!-- Tombol Aksi Kurasi -->
                    <div class="flex flex-wrap items-center gap-2 shrink-0">
                        <button x-show="!store.is_suspended"
                                type="button"
                                @click="toggleFeatured(store)"
                                :class="store.is_currently_featured
                                    ? 'bg-amber-100 text-amber-900 border-amber-300 hover:bg-amber-200'
                                    : 'bg-white text-slate-700 border-slate-300 hover:bg-amber-50'"
                                class="px-3 py-1.5 rounded-xl border text-xs font-bold transition cursor-pointer">
                            <span x-text="store.is_currently_featured ? 'Lepas Sorotan' : '★ Jadikan Sorotan (7 Hari)'"></span>
                        </button>

                        <button type="button"
                                @click="openModerationModal(store)"
                                :class="store.is_suspended
                                    ? 'bg-emerald-700 hover:bg-emerald-800 text-white'
                                    : 'bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200'"
                                class="px-3 py-1.5 rounded-xl text-xs font-bold transition cursor-pointer">
                            <span x-text="store.is_suspended ? '✓ Aktifkan Kembali' : 'Tangguhkan / Catatan'"></span>
                        </button>
                    </div>
                </div>
            </template>
        </section>
    </div>

    <!-- Modal Catatan & Penangguhan Lapak -->
    <div x-show="moderatingStore !== null"
         x-cloak
         class="fixed inset-0 z-50 !mt-0 flex items-center justify-center p-4 bg-slate-900/55 backdrop-blur-[1px]">
        <div @click.away="moderatingStore = null"
             class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-md overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="text-base font-bold text-slate-900"
                    x-text="'Moderasi Lapak: ' + (moderatingStore?.store_name || '')"></h3>
                <button type="button" @click="moderatingStore = null" class="text-slate-400 hover:text-slate-700 text-sm cursor-pointer">✕</button>
            </div>

            <form @submit.prevent="submitModeration()" class="p-5 space-y-4">
                <label class="flex items-center gap-2.5 p-3 rounded-xl border border-slate-200 bg-slate-50 cursor-pointer">
                    <input type="checkbox" x-model="modForm.is_suspended" class="rounded border-slate-300 text-rose-600 focus:ring-rose-600">
                    <span class="text-xs font-bold text-slate-800">Tangguhkan sementara (sembunyikan dari etalase publik)</span>
                </label>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan Pembinaan / Alasan Penertiban</label>
                    <textarea x-model="modForm.moderation_note"
                              rows="3"
                              placeholder="Contoh: Mohon lengkapi informasi harga satuan atau sesuaikan kategori layanan..."
                              class="w-full rounded-xl border-slate-300 text-xs sm:text-sm"></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" @click="moderatingStore = null"
                            class="px-4 py-2 rounded-xl border border-slate-300 text-slate-700 text-xs font-semibold cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" :disabled="saving"
                            class="px-4 py-2 rounded-xl bg-teal-800 hover:bg-teal-900 text-white text-xs font-bold cursor-pointer">
                        <span x-show="!saving">Simpan Keputusan</span>
                        <span x-show="saving">Menyimpan...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function ppmtMarketplaceGovernance() {
    return {
        token: localStorage.getItem('ppmt_token'),
        user: null,
        loading: true,
        saving: false,
        stats: null,
        stores: [],
        search: '',
        filter: 'all',
        toastMessage: '',
        toastType: 'success',
        moderatingStore: null,
        modForm: {
            is_suspended: false,
            moderation_note: ''
        },

        async init() {
            if (!this.token) {
                window.location.href = '/';
                return;
            }
            try {
                const meRes = await fetch('/api/v1/me', { headers: this.headers() });
                if (!meRes.ok) throw new Error('Unauthorized');
                const meJson = await meRes.json();
                this.user = meJson.data;

                if (!this.user?.can_manage_marketplace) {
                    window.location.href = '/pengurus';
                    return;
                }

                await this.loadStores();
            } catch (e) {
                localStorage.removeItem('ppmt_token');
                window.location.href = '/';
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

        get filteredStores() {
            const q = this.search.trim().toLowerCase();
            return this.stores.filter(s => {
                if (this.filter === 'featured' && !s.is_currently_featured) return false;
                if (this.filter === 'suspended' && !s.is_suspended) return false;
                if (!q) return true;
                return (s.store_name || '').toLowerCase().includes(q)
                    || (s.owner?.name || '').toLowerCase().includes(q)
                    || (s.unit?.code || '').toLowerCase().includes(q);
            });
        },

        async loadStores() {
            const res = await fetch('/api/v1/umkm/governance/stores', { headers: this.headers() });
            if (res.ok) {
                const json = await res.json();
                this.stats = json.stats || null;
                this.stores = json.data || [];
            }
        },

        async toggleFeatured(store) {
            const nextState = !store.is_currently_featured;
            const res = await fetch(`/api/v1/umkm/governance/stores/${store.id}/curate`, {
                method: 'PATCH',
                headers: this.headers(),
                body: JSON.stringify({
                    is_featured: nextState,
                    featured_days: nextState ? 7 : null
                })
            });
            const json = await res.json();
            if (res.ok) {
                this.toastType = 'success';
                this.toastMessage = json.message;
                await this.loadStores();
            } else {
                this.toastType = 'error';
                this.toastMessage = json.message || 'Gagal memperbarui status sorotan.';
            }
        },

        openModerationModal(store) {
            this.moderatingStore = store;
            this.modForm = {
                is_suspended: Boolean(store.is_suspended),
                moderation_note: store.moderation_note || ''
            };
        },

        async submitModeration() {
            if (!this.moderatingStore) return;
            this.saving = true;
            try {
                const res = await fetch(`/api/v1/umkm/governance/stores/${this.moderatingStore.id}/curate`, {
                    method: 'PATCH',
                    headers: this.headers(),
                    body: JSON.stringify(this.modForm)
                });
                const json = await res.json();
                if (res.ok) {
                    this.moderatingStore = null;
                    this.toastType = 'success';
                    this.toastMessage = json.message;
                    await this.loadStores();
                } else {
                    this.toastType = 'error';
                    this.toastMessage = json.message || 'Gagal menyimpan moderasi lapak.';
                }
            } finally {
                this.saving = false;
            }
        }
    }
}
</script>
@endpush
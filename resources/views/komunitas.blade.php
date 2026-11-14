@extends('layouts.app')

@section('title', 'Direktori Warga & Pasar Tetangga — PPMT Digital')

@section('content')
<div x-data="ppmtCommunity()" x-init="init()" class="max-w-6xl mx-auto px-4 py-8 space-y-6">

    <!-- Tab Switcher Utama -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
        <div>
            <h1 class="text-xl font-extrabold text-slate-900">Komunitas &amp; Ekonomi Warga PPMT</h1>
            <p class="text-xs text-slate-500">Eksklusif bagi warga terverifikasi • Perlindungan privasi &amp; transaksi langsung tanpa biaya admin.</p>
        </div>
        <div class="grid grid-cols-2 bg-slate-100 p-1 rounded-xl text-xs font-bold shrink-0">
            <button type="button" @click="tab = 'umkm'"
                    :class="tab === 'umkm' ? 'bg-emerald-700 text-white shadow' : 'text-slate-600 hover:text-slate-900'"
                    class="px-4 py-2 rounded-lg transition">
                Pasar Tetangga (UMKM)
            </button>
            <button type="button" @click="tab = 'directory'"
                    :class="tab === 'directory' ? 'bg-emerald-700 text-white shadow' : 'text-slate-600 hover:text-slate-900'"
                    class="px-4 py-2 rounded-lg transition">
                Direktori Warga
            </button>
        </div>
    </div>

    <!-- Notifikasi -->
    <div x-show="toastMessage"
         :class="toastType === 'error' ? 'bg-rose-50 text-rose-800 border-rose-200' : 'bg-emerald-50 text-emerald-800 border-emerald-200'"
         class="p-4 rounded-xl border text-xs font-medium flex justify-between items-center">
        <span x-text="toastMessage"></span>
        <button @click="toastMessage = ''">✕</button>
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
                                Kavling <strong class="text-slate-700" x-text="store.unit?.code"></strong> • <span x-text="store.owner?.name"></span>
                            </div>
                        </div>

                        <!-- Kontrol Pemilik Lapak atau Tombol Pesan WA -->
                        <div class="pt-3 border-t border-slate-100">
                            <template x-if="store.owner_user_id === me?.id">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-[11px] font-semibold text-slate-500">Ubah Status:</span>
                                    <div class="flex gap-1">
                                        <button type="button" @click="changeStoreStatus(store.id, 'open')" class="px-2 py-1 rounded text-[11px] font-bold bg-emerald-50 text-emerald-700 hover:bg-emerald-100">Buka</button>
                                        <button type="button" @click="changeStoreStatus(store.id, 'open_po')" class="px-2 py-1 rounded text-[11px] font-bold bg-amber-50 text-amber-700 hover:bg-amber-100">PO</button>
                                        <button type="button" @click="changeStoreStatus(store.id, 'closed')" class="px-2 py-1 rounded text-[11px] font-bold bg-slate-100 text-slate-700 hover:bg-slate-200">Tutup</button>
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
                        <template x-for="m in me?.memberships" :key="m.id">
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
                        class="w-full py-2.5 px-4 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold shadow-sm transition">
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

</div>

<script>
function ppmtCommunity() {
    return {
        token: localStorage.getItem('ppmt_token'),
        tab: 'umkm',
        me: null,
        stores: [],
        residents: [],
        filterCategory: '',
        filterStatus: '',
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

            await Promise.all([this.loadStores(), this.loadDirectory()]);
        },

        headers() {
            return {
                'Authorization': 'Bearer ' + this.token,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            };
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
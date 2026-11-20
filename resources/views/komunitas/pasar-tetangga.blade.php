@extends('layouts.app')

@section('title', 'Pasar Tetangga PPMT — Katalog Produk & Jasa Warga')
@section('subpage_title', 'Pasar Tetangga PPMT')
@section('subpage_back_url', '/komunitas')

@section('content')
<div x-data="ppmtMarketplace()"
     x-init="init()"
     @keydown.escape.window="selectedItem = null"
     class="max-w-6xl mx-auto px-4 py-6 sm:py-8 space-y-5">

    <!-- Loading State -->
    <div x-show="loading" class="bg-white rounded-2xl p-12 text-center text-sm text-slate-500 border border-slate-200">
        Memuat etalase Pasar Tetangga PPMT...
    </div>

    <div x-show="!loading && user" x-cloak class="space-y-5">

        <!-- Banner Storefront -->
        <section class="bg-gradient-to-r from-emerald-950 via-emerald-900 to-teal-900 text-white rounded-2xl p-5 sm:p-6 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="space-y-1.5 max-w-2xl">
                <div class="inline-flex items-center gap-2 px-2.5 py-0.5 rounded-full bg-amber-400/20 border border-amber-300/30 text-amber-200 text-[11px] font-bold">
                    <span>Ekonomi Internal Warga &bull; Dari Tetangga untuk Tetangga</span>
                </div>
                <h1 class="text-lg sm:text-2xl font-extrabold tracking-tight">Belanja, Pesan Jasa &amp; Pinjam Alat dari Tetangga Sendiri</h1>
                <p class="text-xs sm:text-sm text-emerald-100/90 leading-relaxed">
                    Dari sarapan pagi, isi ulang galon &amp; gas antar depan pintu, jasa tukang/servis AC, hingga pinjam tangga lipat antarblok di Perumahan Pesona Mandiri Turen.
                </p>
            </div>
            <div class="flex flex-wrap sm:flex-nowrap items-center gap-2.5 shrink-0">
                <a href="/pengaturan/lapak"
                   class="w-full sm:w-auto px-4 py-2.5 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-950 text-xs sm:text-sm font-extrabold shadow-sm transition text-center">
                    Kelola Lapak Saya &rarr;
                </a>
            </div>
        </section>

        <!-- Search Bar, Filter Status & Chip Kategori -->
        <section class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200 shadow-2xs space-y-3.5 sticky top-14 z-30">
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5">
                <div class="relative flex-1">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                    </svg>
                    <input type="text"
                           x-model="searchQuery"
                           placeholder="Cari nasi pecel, galon, gas 3kg, servis AC, bor, atau nama lapak..."
                           class="w-full pl-10 pr-4 py-2 rounded-xl border-slate-300 text-xs sm:text-sm focus:border-emerald-600 focus:ring-emerald-600">
                </div>

                <div class="flex items-center gap-2">
                    <select x-model="selectedAvailability"
                            aria-label="Filter Ketersediaan"
                            class="flex-1 sm:flex-none rounded-xl border-slate-300 text-xs sm:text-sm font-medium text-slate-700 focus:border-emerald-600 focus:ring-emerald-600">
                        <option value="all">Semua Status</option>
                        <option value="ready">Ready / Buka Sekarang</option>
                        <option value="po">Open PO</option>
                        <option value="service">Jasa &amp; Pinjam Alat</option>
                    </select>

                    <select x-model="sortBy"
                            aria-label="Urutkan"
                            class="flex-1 sm:flex-none rounded-xl border-slate-300 text-xs sm:text-sm font-medium text-slate-700 focus:border-emerald-600 focus:ring-emerald-600">
                        <option value="featured">Etalase Merata (Adil)</option>
                        <option value="price_asc">Harga Terendah</option>
                        <option value="price_desc">Harga Tertinggi</option>
                    </select>
                </div>
            </div>

            <!-- Chip Kategori -->
            <div class="flex items-center gap-1.5 overflow-x-auto pb-0.5">
                <template x-for="cat in categories" :key="cat.code">
                    <button type="button"
                            @click="selectedCategory = cat.code"
                            :class="selectedCategory === cat.code
                                ? 'bg-emerald-800 text-white border-emerald-800 shadow-2xs'
                                : 'bg-slate-50 text-slate-600 border-slate-200 hover:bg-slate-100'"
                            class="px-3 py-1.5 rounded-full text-xs font-semibold border transition whitespace-nowrap cursor-pointer inline-flex items-center gap-1.5">
                        <span x-text="cat.icon"></span>
                        <span x-text="cat.label"></span>
                    </button>
                </template>
            </div>
        </section>

        <!-- Info Jumlah Hasil -->
        <div class="flex items-center justify-between text-xs text-slate-500 px-0.5">
            <span>
                Menampilkan <strong class="text-slate-800" x-text="filteredItems.length"></strong> produk &amp; jasa
                <span x-show="liveItemsCount > 0"
                      class="text-emerald-700 font-semibold"
                      x-text="'(' + liveItemsCount + ' dari ' + activeStoresCount + ' lapak warga)'"></span>
            </span>
            <span class="hidden sm:inline text-slate-400">Urutan dirotasi bergantian antar-lapak setiap jam</span>
        </div>

        <!-- Empty State -->
        <div x-show="filteredItems.length === 0"
             class="bg-white rounded-2xl p-12 text-center border border-slate-200 space-y-2">
            <div class="text-sm font-bold text-slate-700">Produk atau jasa tidak ditemukan</div>
            <p class="text-xs text-slate-500">Coba ubah kata kunci pencarian atau pilih kategori lainnya.</p>
            <button type="button"
                    @click="searchQuery = ''; selectedCategory = 'all'; selectedAvailability = 'all'"
                    class="mt-2 px-3.5 py-1.5 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-200 text-xs font-bold cursor-pointer">
                Reset Filter
            </button>
        </div>

        <!-- Grid Etalase Online Shop (2 Kolom HP, 3 Kolom Tablet, 4 Kolom Desktop) -->
        <section x-show="filteredItems.length > 0"
                 class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4">
            <template x-for="item in filteredItems" :key="item.id">
                @include('komunitas.partials.pasar-tetangga.item-card')
            </template>
        </section>

    </div>

    <!-- ===================================================================== -->
    <!-- MODAL DETAIL PRODUK / JASA & CHECKOUT WHATSAPP                        -->
    <!-- ===================================================================== -->
    <div x-show="selectedItem !== null"
         x-cloak
         class="fixed inset-0 z-50 !mt-0 flex items-center justify-center p-3 sm:p-4 bg-slate-900/55 backdrop-blur-[1px] overflow-y-auto">
        <div @click.away="selectedItem = null"
             class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-[calc(100vw-1.5rem)] sm:max-w-lg max-h-[90dvh] flex flex-col overflow-hidden my-auto">

            <!-- Header Visual Produk -->
            <div :class="selectedItem?.coverGradient || 'bg-emerald-50'"
                 class="relative px-5 py-6 flex items-center gap-4 border-b border-slate-200/80 shrink-0">
                <button type="button"
                        @click="selectedItem = null"
                        aria-label="Tutup"
                        class="absolute top-3 right-3 w-8 h-8 rounded-full bg-white/90 hover:bg-white text-slate-700 flex items-center justify-center text-sm font-bold shadow-2xs cursor-pointer">
                    ✕
                </button>

                <div class="w-20 h-20 rounded-2xl bg-white/85 border border-white shadow-xs flex items-center justify-center text-4xl shrink-0 overflow-hidden">
                    <img x-show="selectedItem?.imageUrl"
                        :src="selectedItem?.imageUrl"
                        :alt="selectedItem?.name"
                        x-on:error="if (selectedItem) selectedItem.imageUrl = ''"
                        class="w-full h-full object-cover">
                    <span x-show="!selectedItem?.imageUrl" x-text="selectedItem?.emoji"></span>
                </div>

                <div class="min-w-0 flex-1 pr-6 space-y-1">
                    <div class="flex flex-wrap items-center gap-1.5">
                        <span class="px-2 py-0.5 rounded-md text-[10px] font-extrabold uppercase bg-white/90 text-slate-800"
                              x-text="selectedItem?.badgeLabel"></span>
                        <span x-show="selectedItem?.isFeatured"
                              class="px-2 py-0.5 rounded-md text-[10px] font-extrabold bg-amber-400 text-slate-950">
                            &#9733; Sorotan Seksi Ekonomi
                        </span>
                        <span class="px-2 py-0.5 rounded-md text-[10px] font-mono font-bold bg-slate-900/80 text-white"
                              x-text="'Kav. ' + selectedItem?.unitCode"></span>
                    </div>
                    <h3 class="text-base sm:text-lg font-extrabold text-slate-900 leading-snug"
                        x-text="selectedItem?.name"></h3>
                    <div class="flex flex-wrap items-baseline text-emerald-800 leading-tight">
                        <span class="text-base font-extrabold"
                              x-text="selectedItem?.priceLabel"></span>
                        <span x-show="selectedItem?.numericPrice > 0 && selectedItem?.unitNote && !String(selectedItem?.priceLabel || '').includes('–')"
                              class="text-xs font-medium text-slate-600 ml-1"
                              x-text="'/ ' + String(selectedItem?.unitNote || '').replace(/^(\/\s*|per\s+)/i, '')"></span>
                    </div>
                </div>
            </div>

            <!-- Isi Detail & Form Pesan Singkat -->
            <div class="p-5 space-y-4 overflow-y-auto">
                <!-- Banner Simulasi jika Item Mockup -->
                <div x-show="selectedItem?.isMockup"
                     class="p-3 rounded-xl bg-amber-50 border border-amber-200 text-xs text-amber-900">
                    Ini adalah contoh katalog etalase. Anda dapat mendaftarkan produk atau jasa asli Anda melalui menu <a href="/pengaturan/lapak" class="font-bold underline">Pengaturan &rarr; Lapak Usaha</a>.
                </div>

                <!-- Info Lapak -->
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/80 flex items-center justify-between gap-2 text-xs">
                    <div>
                        <div class="font-bold text-slate-900" x-text="selectedItem?.storeName"></div>
                        <div class="text-slate-500" x-text="'Dikelola oleh ' + selectedItem?.ownerName + (selectedItem?.deliveryTag ? ' • ' + selectedItem.deliveryTag : '')"></div>
                    </div>
                    <span :class="selectedItem?.storeStatus === 'closed' ? 'bg-slate-200 text-slate-700' : (selectedItem?.storeStatus === 'open_po' ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800')"
                          class="px-2.5 py-1 rounded-full text-[11px] font-bold shrink-0"
                          x-text="selectedItem?.storeStatus === 'closed' ? '○ Tutup' : (selectedItem?.storeStatus === 'open_po' ? '◐ Open PO' : '● Buka')">
                    </span>
                </div>

                <!-- Deskripsi -->
                <div class="space-y-1">
                    <div class="text-xs font-bold text-slate-700">Deskripsi &amp; Ketentuan</div>
                    <p class="text-xs sm:text-sm text-slate-600 leading-relaxed whitespace-pre-line"
                       x-text="selectedItem?.description || 'Layanan usaha internal warga Perumahan Pesona Mandiri Turen.'"></p>
                </div>

                <!-- Pilihan Jumlah & Pengiriman -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-2 border-t border-slate-100">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Jumlah / Kuantitas</label>
                        <div class="flex items-center gap-2">
                            <button type="button"
                                    @click="orderQty = Math.max(1, orderQty - 1)"
                                    class="w-9 h-9 rounded-xl border border-slate-300 bg-slate-50 hover:bg-slate-100 font-bold text-slate-700 cursor-pointer">
                                -
                            </button>
                            <input type="number"
                                   x-model.number="orderQty"
                                   min="1"
                                   max="50"
                                   class="w-16 text-center rounded-xl border-slate-300 text-sm font-bold">
                            <button type="button"
                                    @click="orderQty = Math.min(50, orderQty + 1)"
                                    class="w-9 h-9 rounded-xl border border-slate-300 bg-slate-50 hover:bg-slate-100 font-bold text-slate-700 cursor-pointer">
                                +
                            </button>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Metode Pengambilan</label>
                        <select x-model="orderDelivery"
                                class="w-full rounded-xl border-slate-300 text-xs sm:text-sm">
                            <option value="Antar ke Kavling Saya">Antar ke Kavling Saya</option>
                            <option value="Ambil Sendiri di Lapak">Ambil Sendiri di Lapak</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan Tambahan (Opsional)</label>
                    <input type="text"
                           x-model="orderNote"
                           placeholder="Contoh: Sambal dipisah, atau antar jam 4 sore..."
                           class="w-full rounded-xl border-slate-300 text-xs sm:text-sm">
                </div>
            </div>

            <!-- Footer Aksi WhatsApp -->
            <div class="px-5 py-3.5 bg-slate-50 border-t border-slate-200 flex items-center justify-between gap-3 shrink-0">
                <div class="text-xs">
                    <span class="text-slate-500 block">Estimasi Total:</span>
                    <span class="text-sm sm:text-base font-extrabold text-slate-900"
                          x-text="selectedItem?.numericPrice > 0 ? formatRupiah(selectedItem.numericPrice * orderQty) : selectedItem?.priceLabel"></span>
                </div>

                <a :href="buildOrderWhatsappUrl()"
                   target="_blank"
                   class="px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs sm:text-sm font-bold shadow-sm transition inline-flex items-center gap-2 shrink-0">
                    <span>Pesan via WhatsApp</span>
                    <span>&rarr;</span>
                </a>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function ppmtMarketplace() {
    return {
        token: localStorage.getItem('ppmt_token'),
        user: null,
        loading: true,
        liveItems: [],
        activeStoresCount: 0,
        searchQuery: '',
        selectedCategory: 'all',
        selectedAvailability: 'all',
        sortBy: 'featured',
        selectedItem: null,
        orderQty: 1,
        orderDelivery: 'Antar ke Kavling Saya',
        orderNote: '',

        categories: [
            { code: 'all', label: 'Semua Etalase', icon: '🏪' },
            { code: 'kuliner', label: 'Kuliner & Minuman', icon: '🍱' },
            { code: 'sembako', label: 'Sembako, Galon & Gas', icon: '💧' },
            { code: 'jasa', label: 'Jasa & Pertukangan', icon: '🛠️' },
            { code: 'pinjam_alat', label: 'Pinjam / Sewa Alat', icon: '🪜' },
            { code: 'lainnya', label: 'Lainnya', icon: '📦' }
        ],

        mockupItems: [
            {
                id: 'mock-1',
                storeKey: 'mock-store-1',
                isMockup: true,
                isFeatured: false,
                isAvailable: true,
                storeStatus: 'open',
                name: 'Galon Air Mineral & Isi Ulang RO Antar Depan Pintu',
                category: 'sembako',
                categoryLabel: 'Sembako, Galon & Gas',
                badgeType: 'ready',
                badgeLabel: 'Ready Antar',
                numericPrice: 6000,
                priceLabel: 'Rp 6.000 – Rp 19.000',
                unitNote: 'RO Rp 6rb • Aqua/Le Minerale Rp 19rb',
                unitCode: 'R-1',
                storeName: 'Depot & Toko Ruko R1',
                ownerName: 'Pak Hendra',
                deliveryTag: 'Gratis antar semua blok',
                imageUrl: '',
                emoji: '💧',
                coverGradient: 'bg-gradient-to-br from-sky-100 via-cyan-50 to-blue-100',
                whatsapp: '6281234567801',
                description: 'Sedia air isi ulang RO higienis (Rp 6.000) dan galon segel pabrik Aqua / Cleo (Rp 19.000). Pesan lewat WA langsung diantar sampai depan teras rumah.'
            },
            {
                id: 'mock-2',
                storeKey: 'mock-store-2',
                isMockup: true,
                isFeatured: false,
                isAvailable: true,
                storeStatus: 'open',
                name: 'Nasi Pecel Madiun Komplit + Rempeyek Kacang',
                category: 'kuliner',
                categoryLabel: 'Kuliner & Minuman',
                badgeType: 'ready',
                badgeLabel: 'Buka Pagi',
                numericPrice: 10000,
                priceLabel: 'Rp 10.000',
                unitNote: 'Tambah telur / ayam +Rp 4.000',
                unitCode: 'B-4',
                storeName: 'Warung Sarapan Bu Siti',
                ownerName: 'Bu Siti Aminah',
                deliveryTag: 'Bisa titip antar jam 06.30',
                imageUrl: '',
                emoji: '🍛',
                coverGradient: 'bg-gradient-to-br from-amber-100 via-orange-50 to-yellow-100',
                whatsapp: '6281234567802',
                description: 'Sarapan pagi hangat dengan bumbu pecel kacang sangrai asli Madiun, sayuran segar, tahu tempe bacem, dan rempeyek renyah.'
            },
            {
                id: 'mock-3',
                storeKey: 'mock-store-3',
                isMockup: true,
                isFeatured: false,
                isAvailable: true,
                storeStatus: 'open',
                name: 'Jasa Cuci & Servis AC Split (Semua Merk)',
                category: 'jasa',
                categoryLabel: 'Jasa & Pertukangan',
                badgeType: 'service',
                badgeLabel: 'Layanan Jasa',
                numericPrice: 65000,
                priceLabel: 'Rp 65.000 / unit',
                unitNote: 'Harga khusus tetangga PPMT',
                unitCode: 'C-11',
                storeName: 'Turen Cool Teknik',
                ownerName: 'Mas Arifin',
                deliveryTag: 'Tanpa ongkos transport',
                imageUrl: '',
                emoji: '❄️',
                coverGradient: 'bg-gradient-to-br from-indigo-100 via-blue-50 to-sky-100',
                whatsapp: '6281234567804',
                description: 'Layanan cuci rutin unit indoor & outdoor, pengecekan tekanan freon, perbaikan AC bocor air/tidak dingin, serta bongkar pasang AC.'
            },
            {
                id: 'mock-4',
                storeKey: 'mock-store-4',
                isMockup: true,
                isFeatured: false,
                isAvailable: true,
                storeStatus: 'open',
                name: 'Pinjam / Sewa Tangga Lipat Aluminium 2,5 Meter',
                category: 'pinjam_alat',
                categoryLabel: 'Pinjam / Sewa Alat',
                badgeType: 'loan',
                badgeLabel: 'Pinjam Tetangga',
                numericPrice: 0,
                priceLabel: 'Gratis (Khusus Warga)',
                unitNote: 'Maksimal pinjam 1x24 jam',
                unitCode: 'B-15',
                storeName: 'Inventaris & Alat Kav. B-15',
                ownerName: 'Pak Bambang',
                deliveryTag: 'Ambil & kembalikan bersih',
                imageUrl: '',
                emoji: '🪜',
                coverGradient: 'bg-gradient-to-br from-teal-100 via-emerald-50 to-cyan-100',
                whatsapp: '6281234567806',
                description: 'Tangga lipat aluminium kokoh tinggi 2,5 meter (bisa dibentang 5 meter) untuk pasang lampu teras, bersihkan talang, atau pasang kanopi.'
            }
        ],

        async init() {
            if (!this.token) {
                window.location.href = '/';
                return;
            }
            try {
                const [meRes, storesRes] = await Promise.all([
                    fetch('/api/v1/me', { headers: this.headers() }),
                    fetch('/api/v1/umkm/stores', { headers: this.headers() }),
                ]);

                if (!meRes.ok) throw new Error('Unauthorized');
                const meJson = await meRes.json();
                this.user = meJson.data;

                if (!this.user?.is_verified) {
                    window.location.href = '/dashboard';
                    return;
                }

                if (storesRes.ok) {
                    const storesJson = await storesRes.json();
                    this.hydrateStoresCatalog(storesJson.data || []);
                }
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
                'Accept': 'application/json'
            };
        },

        categoryMeta(cat) {
            const map = {
                kuliner: { label: 'Kuliner & Minuman', emoji: '🍱', grad: 'bg-gradient-to-br from-amber-100 via-orange-50 to-yellow-100' },
                sembako: { label: 'Sembako, Galon & Gas', emoji: '💧', grad: 'bg-gradient-to-br from-sky-100 via-cyan-50 to-blue-100' },
                jasa: { label: 'Jasa & Pertukangan', emoji: '🛠️', grad: 'bg-gradient-to-br from-indigo-100 via-blue-50 to-sky-100' },
                pinjam_alat: { label: 'Pinjam / Sewa Alat', emoji: '🪜', grad: 'bg-gradient-to-br from-teal-100 via-emerald-50 to-cyan-100' },
                lainnya: { label: 'Produk & Jasa Warga', emoji: '📦', grad: 'bg-gradient-to-br from-emerald-100 via-teal-50 to-green-100' }
            };
            return map[cat] || map.lainnya;
        },

        badgeLabelFor(type) {
            const map = {
                ready: 'Ready Stock',
                po: 'Open PO',
                service: 'Layanan Jasa',
                loan: 'Pinjam / Sewa'
            };
            return map[type] || 'Ready Stock';
        },

        normalizeWhatsapp(raw) {
            const digits = String(raw || '').replace(/[^0-9]/g, '');
            if (!digits) return '';
            return digits.startsWith('0') ? '62' + digits.slice(1) : digits;
        },

        rotateArray(arr, offset) {
            if (!Array.isArray(arr) || arr.length <= 1) return arr;
            const shift = ((offset % arr.length) + arr.length) % arr.length;
            return [...arr.slice(shift), ...arr.slice(0, shift)];
        },

        hydrateStoresCatalog(stores) {
            this.activeStoresCount = stores.length;
            const flattened = [];

            for (const store of stores) {
                const rawCatalog = Array.isArray(store.catalog_items) ? store.catalog_items : [];
                const catalog = this.rotateArray(rawCatalog, Number(store.rotation_seed || 0));
                const unitCode = store.unit?.code || 'PPMT';
                const ownerName = store.owner?.name || 'Warga PPMT';
                const wa = this.normalizeWhatsapp(store.whatsapp_number);
                const storeKey = `store-${store.id}`;
                const isFeatured = Boolean(store.is_currently_featured);

                if (catalog.length > 0) {
                    catalog.forEach((prod, idx) => {
                        const catCode = prod.category || store.category || 'lainnya';
                        const meta = this.categoryMeta(catCode);
                        const badgeType = prod.badge_type || (store.status === 'open_po' ? 'po' : 'ready');
                        const priceNum = Number(prod.price || 0);

                        flattened.push({
                            id: `live-${store.id}-${prod.id || idx}`,
                            storeKey,
                            isMockup: false,
                            isFeatured,
                            isAvailable: prod.is_available !== false,
                            storeStatus: store.status || 'open',
                            name: prod.name,
                            category: catCode,
                            categoryLabel: meta.label,
                            badgeType,
                            badgeLabel: this.badgeLabelFor(badgeType),
                            numericPrice: priceNum,
                            priceLabel: priceNum > 0 ? this.formatRupiah(priceNum) : 'Gratis / Pinjam',
                            unitNote: prod.unit_note || '',
                            unitCode,
                            storeName: store.store_name,
                            ownerName,
                            deliveryTag: prod.delivery_tag || '',
                            imageUrl: prod.image_url || '',
                            emoji: meta.emoji,
                            coverGradient: meta.grad,
                            whatsapp: wa,
                            description: prod.description || store.description || ''
                        });
                    });
                } else {
                    const catCode = store.category || 'lainnya';
                    const meta = this.categoryMeta(catCode);
                    const badgeType = store.status === 'open_po' ? 'po' : (catCode === 'jasa' ? 'service' : (catCode === 'pinjam_alat' ? 'loan' : 'ready'));

                    flattened.push({
                        id: `store-${store.id}`,
                        storeKey,
                        isMockup: false,
                        isFeatured,
                        isAvailable: store.status !== 'closed',
                        storeStatus: store.status || 'open',
                        name: store.store_name,
                        category: catCode,
                        categoryLabel: meta.label,
                        badgeType,
                        badgeLabel: this.badgeLabelFor(badgeType),
                        numericPrice: 0,
                        priceLabel: 'Tanya Harga via WA',
                        unitNote: 'Lapak Usaha Warga',
                        unitCode,
                        storeName: store.store_name,
                        ownerName,
                        deliveryTag: '',
                        imageUrl: '',
                        emoji: meta.emoji,
                        coverGradient: meta.grad,
                        whatsapp: wa,
                        description: store.description || ''
                    });
                }
            }

            this.liveItems = flattened;
        },

        // Menyusun item secara bergantian (1 item per lapak per putaran) agar tidak ada satu lapak mendominasi layar
        interleaveByStore(items) {
            const storeOrder = [];
            const buckets = new Map();

            for (const item of items) {
                const key = item.storeKey || item.storeName || item.id;
                if (!buckets.has(key)) {
                    buckets.set(key, []);
                    storeOrder.push(key);
                }
                buckets.get(key).push(item);
            }

            const result = [];
            let round = 0;
            let addedInRound = true;

            while (addedInRound) {
                addedInRound = false;
                for (const key of storeOrder) {
                    const list = buckets.get(key);
                    if (round < list.length) {
                        result.push(list[round]);
                        addedInRound = true;
                    }
                }
                round++;
            }

            return result;
        },

        get liveItemsCount() {
            return this.liveItems.length;
        },

        get combinedItems() {
            if (this.liveItems.length >= 4) {
                return this.liveItems;
            }
            const needed = Math.max(0, 4 - this.liveItems.length);
            return [...this.liveItems, ...this.mockupItems.slice(0, needed)];
        },

        get filteredItems() {
            const q = this.searchQuery.trim().toLowerCase();
            let list = this.combinedItems.filter(item => {
                if (this.selectedCategory !== 'all' && item.category !== this.selectedCategory) {
                    return false;
                }
                if (this.selectedAvailability === 'ready' && (item.badgeType !== 'ready' || item.storeStatus === 'closed' || item.isAvailable === false)) {
                    return false;
                }
                if (this.selectedAvailability === 'po' && item.badgeType !== 'po') {
                    return false;
                }
                if (this.selectedAvailability === 'service' && !['service', 'loan'].includes(item.badgeType)) {
                    return false;
                }
                if (!q) return true;
                return (item.name || '').toLowerCase().includes(q)
                    || (item.storeName || '').toLowerCase().includes(q)
                    || (item.description || '').toLowerCase().includes(q)
                    || (item.unitCode || '').toLowerCase().includes(q);
            });

            if (this.sortBy === 'price_asc') {
                return [...list].sort((a, b) => a.numericPrice - b.numericPrice);
            }
            if (this.sortBy === 'price_desc') {
                return [...list].sort((a, b) => b.numericPrice - a.numericPrice);
            }

            // Mode 'featured' (Etalase Merata / Default):
            // 1. Sorotan Seksi Ekonomi (maksimal 3 lapak terpilih yang sedang buka & tersedia)
            // 2. Seluruh lapak reguler yang sedang buka & tersedia (Round-Robin bergantian antar-lapak)
            // 3. Lapak yang sedang tutup
            // 4. Item yang stoknya sedang habis
            const featuredItems = list.filter(i => i.isFeatured && i.storeStatus !== 'closed' && i.isAvailable !== false && !i.isMockup);
            const activeAvailable = list.filter(i => !i.isFeatured && i.storeStatus !== 'closed' && i.isAvailable !== false && !i.isMockup);
            const closedStores = list.filter(i => i.storeStatus === 'closed' && !i.isMockup);
            const outOfStock = list.filter(i => i.storeStatus !== 'closed' && i.isAvailable === false && !i.isMockup);
            const mockups = list.filter(i => i.isMockup);

            return [
                ...this.interleaveByStore(featuredItems),
                ...this.interleaveByStore(activeAvailable),
                ...this.interleaveByStore(closedStores),
                ...this.interleaveByStore(outOfStock),
                ...mockups,
            ];
        },

        formatRupiah(value) {
            return 'Rp ' + Number(value || 0).toLocaleString('id-ID');
        },

        openItemDetail(item) {
            this.selectedItem = item;
            this.orderQty = 1;
            this.orderDelivery = 'Antar ke Kavling Saya';
            this.orderNote = '';
        },

        buildOrderWhatsappUrl() {
            if (!this.selectedItem) return '#';
            const item = this.selectedItem;
            const myName = this.user?.display_name || this.user?.name || 'Warga PPMT';
            const myUnit = this.user?.memberships?.[0]?.unit?.code || 'PPMT';
            const lines = [
                `Halo *${item.storeName}* (Kav. ${item.unitCode}),`,
                `Saya *${myName}* dari *Kavling ${myUnit}* ingin memesan dari Pasar Tetangga PPMT:`,
                ``,
                `• *Item:* ${item.name}`,
                `• *Jumlah:* ${this.orderQty}`,
                `• *Pengambilan:* ${this.orderDelivery}`,
                this.orderNote.trim() ? `• *Catatan:* ${this.orderNote.trim()}` : null,
                ``,
                `Terima kasih!`
            ].filter(Boolean).join('\n');

            return `https://wa.me/${item.whatsapp}?text=${encodeURIComponent(lines)}`;
        }
    }
}
</script>
@endpush
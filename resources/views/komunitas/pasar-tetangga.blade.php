@extends('layouts.app')

@section('title', 'Pasar Tetangga PPMT — Katalog Produk & Jasa Warga')
@section('subpage_title', 'Pasar Tetangga PPMT')
@section('subpage_back_url', '/komunitas')

@section('content')
<div x-data="ppmtMarketplaceMockup()"
     x-init="init()"
     @keydown.escape.window="selectedItem = null; showOpenLapakModal = false"
     class="max-w-6xl mx-auto px-4 py-6 sm:py-8 space-y-5">

    <!-- Loading State -->
    <div x-show="loading" class="bg-white rounded-2xl p-12 text-center text-sm text-slate-500 border border-slate-200">
        Memuat etalase Pasar Tetangga PPMT...
    </div>

    <div x-show="!loading && user" x-cloak class="space-y-5">

        <!-- Toast Notifikasi -->
        <div x-show="toastMessage"
             x-cloak
             class="p-3.5 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-200 text-xs sm:text-sm font-medium flex justify-between items-center shadow-2xs">
            <span x-text="toastMessage"></span>
            <button type="button" @click="toastMessage = ''" class="text-xs opacity-70 hover:opacity-100 cursor-pointer">✕</button>
        </div>

        <!-- Banner Storefront & Mode Mockup -->
        <section class="bg-gradient-to-r from-emerald-950 via-emerald-900 to-teal-900 text-white rounded-2xl p-5 sm:p-6 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="space-y-1.5 max-w-2xl">
                <div class="inline-flex items-center gap-2 px-2.5 py-0.5 rounded-full bg-amber-400/20 border border-amber-300/30 text-amber-200 text-[11px] font-bold">
                    <span>Pratinjau Etalase Online Shop &bull; Ekonomi Internal Warga</span>
                </div>
                <h1 class="text-lg sm:text-2xl font-extrabold tracking-tight">Belanja, Pesan Jasa &amp; Pinjam Alat dari Tetangga Sendiri</h1>
                <p class="text-xs sm:text-sm text-emerald-100/90 leading-relaxed">
                    Dari sarapan pagi, isi ulang galon &amp; gas antar depan pintu, jasa tukang/servis AC, hingga pinjam tangga lipat antarblok di Perumahan Pesona Mandiri Turen.
                </p>
            </div>
            <div class="flex flex-wrap sm:flex-nowrap items-center gap-2.5 shrink-0">
                <button type="button"
                        @click="showOpenLapakModal = true"
                        class="w-full sm:w-auto px-4 py-2.5 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-950 text-xs sm:text-sm font-extrabold shadow-sm transition cursor-pointer">
                    + Pasang Produk / Jasa
                </button>
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
                        <option value="featured">Rekomendasi</option>
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
            <span>Menampilkan <strong class="text-slate-800" x-text="filteredItems.length"></strong> produk &amp; jasa tetangga</span>
            <span class="hidden sm:inline text-slate-400">Klik kartu untuk detail, opsi antar, &amp; pesan cepat via WhatsApp</span>
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
                <article @click="openItemDetail(item)"
                         class="group bg-white rounded-2xl border border-slate-200 hover:border-emerald-400 shadow-2xs hover:shadow-md overflow-hidden flex flex-col justify-between transition cursor-pointer">

                    <div>
                        <!-- Area Visual Produk (Aspect Square) -->
                        <div :class="item.coverGradient"
                             class="relative aspect-square w-full flex flex-col items-center justify-center p-4 overflow-hidden select-none">

                            <!-- Badge Status Kiri Atas -->
                            <span :class="{
                                    'bg-emerald-600 text-white': item.badgeType === 'ready',
                                    'bg-amber-500 text-slate-950': item.badgeType === 'po',
                                    'bg-indigo-600 text-white': item.badgeType === 'service',
                                    'bg-teal-600 text-white': item.badgeType === 'loan'
                                  }"
                                  class="absolute top-2.5 left-2.5 px-2 py-0.5 rounded-md text-[10px] font-extrabold uppercase tracking-wide shadow-2xs"
                                  x-text="item.badgeLabel"></span>

                            <!-- Badge Blok / Kavling Kanan Atas -->
                            <span class="absolute top-2.5 right-2.5 px-2 py-0.5 rounded-md text-[10px] font-mono font-bold bg-white/90 text-slate-800 border border-slate-200/80 shadow-2xs"
                                  x-text="item.unitCode"></span>

                            <!-- Ilustrasi Besar Kategori -->
                            <div class="text-5xl sm:text-6xl group-hover:scale-110 transition-transform duration-200 drop-shadow-xs"
                                 x-text="item.emoji"></div>

                            <!-- Label Antar / Ambil di Bawah Gambar -->
                            <div x-show="item.deliveryTag"
                                 class="absolute bottom-2 inset-x-2.5 flex justify-center">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-900/75 text-white backdrop-blur-[1px] truncate"
                                      x-text="item.deliveryTag"></span>
                            </div>
                        </div>

                        <!-- Info Produk -->
                        <div class="p-3 sm:p-3.5 space-y-1.5">
                            <div class="text-[10px] font-bold uppercase tracking-wider text-emerald-700"
                                 x-text="item.categoryLabel"></div>

                            <h2 class="text-xs sm:text-sm font-bold text-slate-900 group-hover:text-emerald-800 leading-snug line-clamp-2 min-h-[2.4em]"
                                x-text="item.name"></h2>

                            <div class="pt-0.5">
                                <div class="text-sm sm:text-base font-extrabold text-emerald-800"
                                     x-text="item.priceLabel"></div>
                                <div x-show="item.unitNote"
                                     class="text-[10px] text-slate-400"
                                     x-text="item.unitNote"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Kartu: Nama Lapak & Tombol Cepat -->
                    <div class="px-3 sm:px-3.5 pb-3 sm:pb-3.5 pt-2 border-t border-slate-100 space-y-2.5">
                        <div class="flex items-center justify-between gap-1.5 text-[11px] text-slate-500">
                            <span class="truncate font-medium text-slate-700" x-text="item.storeName"></span>
                            <span class="inline-flex items-center gap-1 shrink-0 text-[10px] font-bold text-emerald-700">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                Buka
                            </span>
                        </div>

                        <button type="button"
                                @click.stop="openItemDetail(item)"
                                class="w-full py-1.5 px-2.5 rounded-xl bg-emerald-50 group-hover:bg-emerald-700 text-emerald-800 group-hover:text-white border border-emerald-200 group-hover:border-emerald-700 text-[11px] font-bold transition cursor-pointer">
                            Lihat &amp; Pesan
                        </button>
                    </div>
                </article>
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

                <div class="w-20 h-20 rounded-2xl bg-white/85 border border-white shadow-xs flex items-center justify-center text-4xl shrink-0"
                     x-text="selectedItem?.emoji"></div>

                <div class="min-w-0 flex-1 pr-6 space-y-1">
                    <div class="flex flex-wrap items-center gap-1.5">
                        <span class="px-2 py-0.5 rounded-md text-[10px] font-extrabold uppercase bg-white/90 text-slate-800"
                              x-text="selectedItem?.badgeLabel"></span>
                        <span class="px-2 py-0.5 rounded-md text-[10px] font-mono font-bold bg-slate-900/80 text-white"
                              x-text="'Kav. ' + selectedItem?.unitCode"></span>
                    </div>
                    <h3 class="text-base sm:text-lg font-extrabold text-slate-900 leading-snug"
                        x-text="selectedItem?.name"></h3>
                    <div class="text-base font-extrabold text-emerald-800"
                         x-text="selectedItem?.priceLabel"></div>
                </div>
            </div>

            <!-- Isi Detail & Form Pesan Singkat -->
            <div class="p-5 space-y-4 overflow-y-auto">
                <!-- Info Lapak -->
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/80 flex items-center justify-between gap-2 text-xs">
                    <div>
                        <div class="font-bold text-slate-900" x-text="selectedItem?.storeName"></div>
                        <div class="text-slate-500" x-text="'Dikelola oleh ' + selectedItem?.ownerName + ' • ' + selectedItem?.openHours"></div>
                    </div>
                    <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-800 shrink-0">
                        ● Buka
                    </span>
                </div>

                <!-- Deskripsi -->
                <div class="space-y-1">
                    <div class="text-xs font-bold text-slate-700">Deskripsi &amp; Ketentuan</div>
                    <p class="text-xs sm:text-sm text-slate-600 leading-relaxed whitespace-pre-line"
                       x-text="selectedItem?.description"></p>
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

    <!-- ===================================================================== -->
    <!-- MODAL PRATINJAU TAMBAH PRODUK / JASA BARU (MOCKUP INTERAKTIF)         -->
    <!-- ===================================================================== -->
    <div x-show="showOpenLapakModal"
         x-cloak
         class="fixed inset-0 z-50 !mt-0 flex items-center justify-center p-3 sm:p-4 bg-slate-900/55 backdrop-blur-[1px] overflow-y-auto">
        <div @click.away="showOpenLapakModal = false"
             class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-[calc(100vw-1.5rem)] sm:max-w-lg max-h-[90dvh] flex flex-col overflow-hidden my-auto">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between gap-3 shrink-0">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Tambah Produk / Jasa ke Etalase</h3>
                    <p class="text-xs text-slate-500">Simulasi penambahan katalog lapak warga PPMT.</p>
                </div>
                <button type="button" @click="showOpenLapakModal = false" class="text-slate-400 hover:text-slate-700 text-sm p-1 cursor-pointer">✕</button>
            </div>

            <form @submit.prevent="addMockupItem()" class="p-5 space-y-3.5 overflow-y-auto">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Produk atau Jasa *</label>
                    <input type="text" x-model="draftItem.name" required placeholder="Contoh: Risol Mayo Isi 5 / Jasa Tambal Ban"
                           class="w-full rounded-xl border-slate-300 text-sm">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Kategori *</label>
                        <select x-model="draftItem.category" class="w-full rounded-xl border-slate-300 text-sm">
                            <option value="kuliner">Kuliner &amp; Minuman</option>
                            <option value="sembako">Sembako, Galon &amp; Gas</option>
                            <option value="jasa">Jasa &amp; Pertukangan</option>
                            <option value="pinjam_alat">Pinjam / Sewa Alat</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Status Ketersediaan *</label>
                        <select x-model="draftItem.badgeType" class="w-full rounded-xl border-slate-300 text-sm">
                            <option value="ready">Ready Stock / Buka</option>
                            <option value="po">Open PO</option>
                            <option value="service">Layanan Jasa</option>
                            <option value="loan">Pinjam / Sewa</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Harga (Rp) *</label>
                        <input type="number" x-model.number="draftItem.numericPrice" min="0" step="500"
                               class="w-full rounded-xl border-slate-300 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lapak Anda *</label>
                        <input type="text" x-model="draftItem.storeName" required placeholder="Contoh: Dapur Blok B"
                               class="w-full rounded-xl border-slate-300 text-sm">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Deskripsi Singkat *</label>
                    <textarea x-model="draftItem.description" required rows="3" placeholder="Jelaskan detail produk, jam antar, atau syarat peminjaman..."
                              class="w-full rounded-xl border-slate-300 text-sm"></textarea>
                </div>

                <div class="pt-2 flex justify-end gap-2 border-t border-slate-100">
                    <button type="button" @click="showOpenLapakModal = false"
                            class="px-4 py-2 rounded-xl border border-slate-300 text-slate-700 text-xs font-semibold cursor-pointer">
                        Batal
                    </button>
                    <button type="submit"
                            class="px-5 py-2 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold shadow-sm cursor-pointer">
                        Tampilkan di Etalase
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function ppmtMarketplaceMockup() {
    return {
        token: localStorage.getItem('ppmt_token'),
        user: null,
        loading: true,
        toastMessage: '',
        searchQuery: '',
        selectedCategory: 'all',
        selectedAvailability: 'all',
        sortBy: 'featured',
        selectedItem: null,
        orderQty: 1,
        orderDelivery: 'Antar ke Kavling Saya',
        orderNote: '',
        showOpenLapakModal: false,
        draftItem: {
            name: '',
            category: 'kuliner',
            badgeType: 'ready',
            numericPrice: 15000,
            storeName: '',
            description: ''
        },

        categories: [
            { code: 'all', label: 'Semua Etalase', icon: '🏪' },
            { code: 'kuliner', label: 'Kuliner & Minuman', icon: '🍱' },
            { code: 'sembako', label: 'Sembako, Galon & Gas', icon: '💧' },
            { code: 'jasa', label: 'Jasa & Pertukangan', icon: '🛠️' },
            { code: 'pinjam_alat', label: 'Pinjam / Sewa Alat', icon: '🪜' }
        ],

        items: [
            {
                id: 1,
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
                openHours: '07.00 – 20.30 WIB',
                deliveryTag: 'Gratis antar semua blok',
                emoji: '💧',
                coverGradient: 'bg-gradient-to-br from-sky-100 via-cyan-50 to-blue-100',
                whatsapp: '6281234567801',
                description: 'Sedia air isi ulang RO higienis (Rp 6.000) dan galon segel pabrik Aqua / Cleo (Rp 19.000). Pesan lewat WA langsung diantar sampai depan teras rumah.'
            },
            {
                id: 2,
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
                openHours: '06.00 – 09.30 WIB',
                deliveryTag: 'Bisa titip antar jam 06.30',
                emoji: '🍛',
                coverGradient: 'bg-gradient-to-br from-amber-100 via-orange-50 to-yellow-100',
                whatsapp: '6281234567802',
                description: 'Sarapan pagi hangat dengan bumbu pecel kacang sangrai asli Madiun, sayuran segar, tahu tempe bacem, dan rempeyek renyah. Cocok untuk bekal kerja atau sekolah.'
            },
            {
                id: 3,
                name: 'Gas LPG 3 Kg Melon & Bright Gas 5.5 Kg',
                category: 'sembako',
                categoryLabel: 'Sembako, Galon & Gas',
                badgeType: 'ready',
                badgeLabel: 'Ready Stok',
                numericPrice: 20000,
                priceLabel: 'Rp 20.000',
                unitNote: 'Tukar tabung kosong • Pasang gratis',
                unitCode: 'A-3',
                storeName: 'Agen Gas & Sembako Blok A',
                ownerName: 'Mas Dani',
                openHours: '06.30 – 21.00 WIB',
                deliveryTag: 'Siap bantu pasangkan regulator',
                emoji: '🔥',
                coverGradient: 'bg-gradient-to-br from-emerald-100 via-teal-50 to-green-100',
                whatsapp: '6281234567803',
                description: 'Stok tabung gas LPG 3 kg dan Bright Gas 5.5 kg siap antar ke seluruh blok PPMT. Gratis bantu pemasangan regulator dan cek kebocoran selang.'
            },
            {
                id: 4,
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
                openHours: '08.00 – 17.00 WIB',
                deliveryTag: 'Panggilan cepat tanpa ongkos transport',
                emoji: '❄️',
                coverGradient: 'bg-gradient-to-br from-indigo-100 via-blue-50 to-sky-100',
                whatsapp: '6281234567804',
                description: 'Layanan cuci rutin unit indoor & outdoor, pengecekan tekanan freon, perbaikan AC bocor air/tidak dingin, serta bongkar pasang AC pindahan.'
            },
            {
                id: 5,
                name: 'Open PO Ayam Geprek Sambal Bawang + Es Teh',
                category: 'kuliner',
                categoryLabel: 'Kuliner & Minuman',
                badgeType: 'po',
                badgeLabel: 'Open PO',
                numericPrice: 13000,
                priceLabel: 'Rp 13.000 / porsi',
                unitNote: 'Tutup PO jam 10.30 • Antar jam 12.00',
                unitCode: 'D-8',
                storeName: 'Dapur Mbak Rina',
                ownerName: 'Mbak Rina',
                openHours: 'PO Harian Makan Siang',
                deliveryTag: 'Level pedas 1 – 5 sesuai selera',
                emoji: '🍗',
                coverGradient: 'bg-gradient-to-br from-rose-100 via-red-50 to-orange-100',
                whatsapp: '6281234567805',
                description: 'Ayam krispi ukuran besar digeprek dadakan dengan sambal bawang segar. Sudah termasuk nasi putih pulen, lalapan timun, dan es teh manis.'
            },
            {
                id: 6,
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
                openHours: '07.00 – 20.00 WIB',
                deliveryTag: 'Ambil & kembalikan bersih ke B-15',
                emoji: '🪜',
                coverGradient: 'bg-gradient-to-br from-teal-100 via-emerald-50 to-cyan-100',
                whatsapp: '6281234567806',
                description: 'Tangga lipat aluminium kokoh tinggi 2,5 meter (bisa dibentang 5 meter) untuk pasang lampu teras, bersihkan talang, atau pasang kanopi.'
            },
            {
                id: 7,
                name: 'Jasa Las Kanopi, Pagar Minimalis & Perbaikan Engsel',
                category: 'jasa',
                categoryLabel: 'Jasa & Pertukangan',
                badgeType: 'service',
                badgeLabel: 'Jasa Bengkel',
                numericPrice: 100000,
                priceLabel: 'Mulai Rp 100.000',
                unitNote: 'Gratis survei ukur lokasi di PPMT',
                unitCode: 'D-2',
                storeName: 'Karya Mandiri Las',
                ownerName: 'Pak Agus',
                openHours: '08.00 – 16.30 WIB',
                deliveryTag: 'Bisa perbaikan kecil panggilan',
                emoji: '🛠️',
                coverGradient: 'bg-gradient-to-br from-slate-200 via-zinc-100 to-stone-200',
                whatsapp: '6281234567807',
                description: 'Menerima pembuatan kanopi galvalum/spandek, pagar dorong minimalis, teralis jendela, serta servis las engsel pintu pagar yang turun atau macet.'
            },
            {
                id: 8,
                name: 'Sewa Mesin Bor Beton (Rotary Hammer) & Gerinda',
                category: 'pinjam_alat',
                categoryLabel: 'Pinjam / Sewa Alat',
                badgeType: 'loan',
                badgeLabel: 'Sewa Harian',
                numericPrice: 25000,
                priceLabel: 'Rp 25.000 / hari',
                unitNote: 'Sudah termasuk mata bor tembok',
                unitCode: 'C-5',
                storeName: 'Perkakas Blok C',
                ownerName: 'Mas Yoga',
                openHours: '07.30 – 20.00 WIB',
                deliveryTag: 'Lengkap koper & mata bor 6–12mm',
                emoji: '🔌',
                coverGradient: 'bg-gradient-to-br from-violet-100 via-purple-50 to-indigo-100',
                whatsapp: '6281234567808',
                description: 'Sewa bor listrik mode hammer untuk pasang bracket TV, rak dinding, gorden, atau lubang kabel. Hemat tanpa perlu beli mesin baru.'
            }
        ],

        async init() {
            if (!this.token) {
                window.location.href = '/';
                return;
            }
            try {
                const res = await fetch('/api/v1/me', {
                    headers: {
                        'Authorization': 'Bearer ' + this.token,
                        'Accept': 'application/json'
                    }
                });
                if (!res.ok) throw new Error('Unauthorized');
                const json = await res.json();
                this.user = json.data;
                if (!this.user?.is_verified) {
                    window.location.href = '/dashboard';
                    return;
                }
            } catch (e) {
                localStorage.removeItem('ppmt_token');
                window.location.href = '/';
            } finally {
                this.loading = false;
            }
        },

        get filteredItems() {
            const q = this.searchQuery.trim().toLowerCase();
            let list = this.items.filter(item => {
                if (this.selectedCategory !== 'all' && item.category !== this.selectedCategory) {
                    return false;
                }
                if (this.selectedAvailability === 'ready' && item.badgeType !== 'ready') {
                    return false;
                }
                if (this.selectedAvailability === 'po' && item.badgeType !== 'po') {
                    return false;
                }
                if (this.selectedAvailability === 'service' && !['service', 'loan'].includes(item.badgeType)) {
                    return false;
                }
                if (!q) return true;
                return item.name.toLowerCase().includes(q)
                    || item.storeName.toLowerCase().includes(q)
                    || item.description.toLowerCase().includes(q)
                    || item.unitCode.toLowerCase().includes(q);
            });

            if (this.sortBy === 'price_asc') {
                list = [...list].sort((a, b) => a.numericPrice - b.numericPrice);
            } else if (this.sortBy === 'price_desc') {
                list = [...list].sort((a, b) => b.numericPrice - a.numericPrice);
            }
            return list;
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
        },

        addMockupItem() {
            const catMeta = {
                kuliner: { label: 'Kuliner & Minuman', emoji: '🍱', grad: 'bg-gradient-to-br from-amber-100 via-orange-50 to-yellow-100' },
                sembako: { label: 'Sembako, Galon & Gas', emoji: '🛒', grad: 'bg-gradient-to-br from-emerald-100 via-teal-50 to-green-100' },
                jasa: { label: 'Jasa & Pertukangan', emoji: '🛠️', grad: 'bg-gradient-to-br from-indigo-100 via-blue-50 to-sky-100' },
                pinjam_alat: { label: 'Pinjam / Sewa Alat', emoji: '🪜', grad: 'bg-gradient-to-br from-teal-100 via-emerald-50 to-cyan-100' }
            };
            const badgeMeta = {
                ready: 'Ready Stok',
                po: 'Open PO',
                service: 'Layanan Jasa',
                loan: 'Pinjam / Sewa'
            };

            const meta = catMeta[this.draftItem.category] || catMeta.kuliner;
            const myUnit = this.user?.memberships?.[0]?.unit?.code || 'PPMT';
            const myName = this.user?.display_name || this.user?.name || 'Warga PPMT';
            const priceNum = Number(this.draftItem.numericPrice || 0);

            this.items.unshift({
                id: Date.now(),
                name: this.draftItem.name.trim(),
                category: this.draftItem.category,
                categoryLabel: meta.label,
                badgeType: this.draftItem.badgeType,
                badgeLabel: badgeMeta[this.draftItem.badgeType] || 'Ready Stok',
                numericPrice: priceNum,
                priceLabel: priceNum > 0 ? this.formatRupiah(priceNum) : 'Gratis / Pinjam',
                unitNote: 'Item simulasi baru ditambahkan',
                unitCode: myUnit,
                storeName: this.draftItem.storeName.trim(),
                ownerName: myName,
                openHours: 'Sesuai kesepakatan WA',
                deliveryTag: 'Layanan internal warga PPMT',
                emoji: meta.emoji,
                coverGradient: meta.grad,
                whatsapp: '6281234567890',
                description: this.draftItem.description.trim()
            });

            this.showOpenLapakModal = false;
            this.draftItem = {
                name: '',
                category: 'kuliner',
                badgeType: 'ready',
                numericPrice: 15000,
                storeName: '',
                description: ''
            };
            this.toastMessage = 'Produk/jasa simulasi berhasil ditambahkan ke etalase!';
        }
    }
}
</script>
@endpush
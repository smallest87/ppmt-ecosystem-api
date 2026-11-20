@extends('layouts.app')

@php
    $sharedItem = $sharedItem ?? null;
    $ogTitle = $sharedItem
        ? ($sharedItem['name'] . ' — ' . $sharedItem['storeName'] . ' (Pasar Tetangga PPMT)')
        : 'Pasar Tetangga PPMT — Katalog Produk & Jasa Warga';
    $ogPrice = $sharedItem
        ? (($sharedItem['numericPrice'] ?? 0) > 0
            ? 'Rp ' . number_format($sharedItem['numericPrice'], 0, ',', '.')
            : 'Gratis / Pinjam')
        : '';
    $ogDesc = $sharedItem
        ? trim($ogPrice . ($sharedItem['unitNote'] ? ' (' . $sharedItem['unitNote'] . ')' : '') . ' • Kav. ' . $sharedItem['unitCode'] . ($sharedItem['description'] ? ' — ' . \Illuminate\Support\Str::limit($sharedItem['description'], 130) : ''))
        : 'Belanja, pesan jasa, dan pinjam alat langsung dari tetangga di Perumahan Pesona Mandiri Turen.';
    $ogImage = (!empty($sharedItem['imageUrl'])) ? url($sharedItem['imageUrl']) : null;
@endphp

@section('title', $ogTitle)
@section('subpage_title', 'Pasar Tetangga PPMT')
@section('subpage_back_url', '/komunitas')

@push('meta')
    <meta property="og:type" content="product">
    <meta property="og:site_name" content="PPMT Digital — Pasar Tetangga">
    <meta property="og:title" content="{{ $ogTitle }}">
    <meta property="og:description" content="{{ $ogDesc }}">
    <meta property="og:url" content="{{ url()->current() }}">
    @if($ogImage)
        <meta property="og:image" content="{{ $ogImage }}">
    @endif
    <meta name="twitter:card" content="{{ $ogImage ? 'summary_large_image' : 'summary' }}">
    <meta name="twitter:title" content="{{ $ogTitle }}">
    <meta name="twitter:description" content="{{ $ogDesc }}">
    @if($ogImage)
        <meta name="twitter:image" content="{{ $ogImage }}">
    @endif
@endpush

@section('content')
<div x-data="ppmtMarketplace()"
     x-init="init()"
     @keydown.escape.window="if (cartModalOpen) { cartModalOpen = false; } else { closeItemDetail(); }"
     class="max-w-6xl mx-auto px-4 py-6 sm:py-8 space-y-5">

    <!-- Loading State -->
    <div x-show="loading" class="bg-white rounded-2xl p-12 text-center text-sm text-slate-500 border border-slate-200">
        Memuat etalase Pasar Tetangga PPMT...
    </div>

    <!-- ===================================================================== -->
    <!-- TAMPILAN TAMU (BELUM LOGIN / BELUM TERVERIFIKASI)                     -->
    <!-- ===================================================================== -->
    <div x-show="!loading && !isVerifiedCitizen" x-cloak class="space-y-5">

        <!-- Kondisi 1: Tamu Membuka Link Item yang Dibagikan -->
        <template x-if="guestSharedItem">
            <div class="max-w-lg mx-auto bg-white rounded-2xl shadow-lg border border-slate-200 overflow-hidden">
                <!-- Banner Info Pratinjau -->
                <div class="px-4 py-2.5 bg-emerald-950 text-emerald-100 text-[11px] font-semibold flex items-center justify-between gap-2">
                    <span>Pasar Tetangga PPMT &bull; Pratinjau Produk Warga</span>
                    <span class="px-2 py-0.5 rounded bg-white/10 font-mono text-amber-300"
                          x-text="'Kav. ' + guestSharedItem.unitCode"></span>
                </div>

                <!-- Foto / Ilustrasi Produk -->
                <div :class="guestSharedItem.coverGradient"
                     class="relative aspect-square max-h-80 w-full flex items-center justify-center overflow-hidden border-b border-slate-200/80">
                    <img x-show="guestSharedItem.imageUrl"
                         :src="guestSharedItem.imageUrl"
                         :alt="guestSharedItem.name"
                         x-on:error="guestSharedItem.imageUrl = ''"
                         class="w-full h-full object-cover">
                    <div x-show="!guestSharedItem.imageUrl"
                         class="text-7xl select-none"
                         x-text="guestSharedItem.emoji"></div>

                    <span :class="{
                            'bg-rose-600 text-white': guestSharedItem.isAvailable === false,
                            'bg-slate-700/95 text-white': guestSharedItem.isAvailable !== false && guestSharedItem.storeStatus === 'closed' && guestSharedItem.badgeType !== 'po',
                            'bg-emerald-600 text-white': guestSharedItem.isAvailable !== false && guestSharedItem.storeStatus !== 'closed' && guestSharedItem.badgeType === 'ready',
                            'bg-amber-500 text-slate-950': guestSharedItem.isAvailable !== false && guestSharedItem.badgeType === 'po',
                            'bg-indigo-600 text-white': guestSharedItem.isAvailable !== false && guestSharedItem.storeStatus !== 'closed' && guestSharedItem.badgeType === 'service',
                            'bg-teal-600 text-white': guestSharedItem.isAvailable !== false && guestSharedItem.storeStatus !== 'closed' && guestSharedItem.badgeType === 'loan'
                          }"
                          class="absolute top-3 left-3 px-2.5 py-1 rounded-lg text-[11px] font-extrabold uppercase tracking-wide shadow-sm"
                          x-text="guestSharedItem.isAvailable === false
                              ? 'Stok Habis'
                              : (guestSharedItem.storeStatus === 'closed' && guestSharedItem.badgeType !== 'po' ? 'Sedang Tutup' : guestSharedItem.badgeLabel)"></span>
                </div>

                <!-- Detail Harga, Lapak & Deskripsi -->
                <div class="p-5 space-y-4">
                    <div class="space-y-1">
                        <div class="text-[11px] font-bold uppercase tracking-wider text-emerald-700"
                             x-text="guestSharedItem.categoryLabel"></div>
                        <h2 class="text-lg sm:text-xl font-extrabold text-slate-900 leading-snug"
                            x-text="guestSharedItem.name"></h2>
                        <div class="flex flex-wrap items-baseline text-emerald-800 pt-0.5">
                            <span class="text-xl font-extrabold"
                                  x-text="guestSharedItem.priceLabel"></span>
                            <span x-show="guestSharedItem.numericPrice > 0 && guestSharedItem.unitNote"
                                  class="text-xs font-medium text-slate-500 ml-1.5"
                                  x-text="'/ ' + String(guestSharedItem.unitNote || '').replace(/^(\/\s*|per\s+)/i, '')"></span>
                        </div>
                    </div>

                    <!-- Info Lapak -->
                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/80 flex items-center justify-between gap-2 text-xs">
                        <div class="min-w-0">
                            <div class="font-bold text-slate-900 truncate" x-text="guestSharedItem.storeName"></div>
                            <div class="text-slate-500"
                                 x-text="'Kavling ' + guestSharedItem.unitCode + (guestSharedItem.operatingHours ? ' • Jam: ' + guestSharedItem.operatingHours : '')"></div>
                        </div>
                        <span :class="guestSharedItem.storeStatus === 'closed' ? 'bg-slate-200 text-slate-700' : 'bg-emerald-100 text-emerald-800'"
                              class="px-2.5 py-1 rounded-full text-[11px] font-bold shrink-0"
                              x-text="guestSharedItem.storeStatus === 'closed' ? '○ Tutup' : '● Buka'">
                        </span>
                    </div>

                    <!-- Deskripsi -->
                    <div x-show="guestSharedItem.description" class="space-y-1">
                        <div class="text-xs font-bold text-slate-700">Deskripsi &amp; Ketentuan</div>
                        <p class="text-xs sm:text-sm text-slate-600 leading-relaxed whitespace-pre-line"
                           x-text="guestSharedItem.description"></p>
                    </div>

                    <!-- Pengganti Form & Tombol Pesan: Login jika ingin memesan -->
                    <div class="pt-3 border-t border-slate-100 space-y-2.5">
                        <p class="text-[11px] text-slate-500 text-center leading-relaxed">
                            Pemesanan dan kontak WhatsApp pelapak hanya tersedia bagi warga terverifikasi Perumahan Pesona Mandiri Turen.
                        </p>
                        <button type="button"
                                @click="redirectToLoginForOrder()"
                                class="w-full py-3 px-4 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white text-xs sm:text-sm font-extrabold shadow-sm transition cursor-pointer">
                            <span x-text="user && !user.is_verified ? 'Menunggu Verifikasi Akun Warga' : 'Login jika ingin memesan →'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </template>

        <!-- Kondisi 2: Tamu Membuka Link Item yang Sudah Tidak Tersedia / Dihapus -->
        <template x-if="!guestSharedItem">
            <div class="max-w-md mx-auto bg-white rounded-2xl p-8 text-center border border-slate-200 shadow-sm space-y-3">
                <div class="text-3xl">🏪</div>
                <h2 class="text-base font-extrabold text-slate-900">Produk Tidak Ditemukan atau Etalase Khusus Warga</h2>
                <p class="text-xs text-slate-600 leading-relaxed">
                    Item yang Anda cari mungkin sudah dihapus oleh pelapak atau tautannya tidak valid. Silakan masuk sebagai warga PPMT untuk melihat seluruh katalog Pasar Tetangga.
                </p>
                <button type="button"
                        @click="redirectToLoginForOrder()"
                        class="w-full py-2.5 px-4 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white text-xs sm:text-sm font-bold shadow-sm transition cursor-pointer">
                    Login ke Portal Warga &rarr;
                </button>
            </div>
        </template>
    </div>

    <!-- ===================================================================== -->
    <!-- TAMPILAN PENUH WARGA TERVERIFIKASI                                    -->
    <!-- ===================================================================== -->
    <div x-show="!loading && isVerifiedCitizen"
         x-cloak
         :class="cartTotalItems > 0 ? 'pb-24' : ''"
         class="space-y-5">

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
                        <option value="all">Semua Penawaran</option>
                        <option value="ready">Ready Stock (Buka)</option>
                        <option value="po">Open PO (Pre-Order)</option>
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
                Menampilkan <strong class="text-slate-800" x-text="visibleItems.length"></strong>
                <span x-show="filteredItems.length > visibleItems.length"
                      x-text="' dari ' + filteredItems.length"></span>
                produk &amp; jasa
                <span x-show="liveItemsCount > 0"
                      class="text-emerald-700 font-semibold"
                      x-text="'(' + liveItemsCount + ' item dari ' + activeStoresCount + ' lapak warga)'"></span>
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
            <template x-for="item in visibleItems" :key="item.id">
                @include('komunitas.partials.pasar-tetangga.item-card')
            </template>
        </section>

        <!-- Sentinel & Indikator Ekspansi Vertikal Bertahap -->
        <div x-show="filteredItems.length > 0" class="pt-1 pb-4">
            <div x-ref="scrollSentinel" class="h-1 w-full" aria-hidden="true"></div>

            <div x-show="loadingMore"
                 x-cloak
                 class="flex items-center justify-center gap-2 py-4 text-xs font-semibold text-emerald-800">
                <svg class="w-4 h-4 animate-spin text-emerald-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
                <span>Memuat produk &amp; jasa berikutnya...</span>
            </div>

            <div x-show="hasMoreItems && !loadingMore"
                 class="flex justify-center pt-2">
                <button type="button"
                        @click="loadNextPage()"
                        class="px-4 py-2 rounded-xl bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 text-xs font-bold shadow-2xs transition cursor-pointer">
                    Tampilkan Lebih Banyak (<span x-text="remainingItemsCount"></span> tersisa)
                </button>
            </div>

            <div x-show="!hasMoreItems && filteredItems.length > pageSize"
                 class="text-center text-[11px] text-slate-400 pt-2">
                Semua <span x-text="filteredItems.length"></span> produk &amp; jasa telah ditampilkan
            </div>
        </div>

    </div>

    <!-- ===================================================================== -->
    <!-- TOAST NOTIFIKASI KERANJANG                                            -->
    <!-- ===================================================================== -->
    <div x-show="cartToastMessage"
         x-cloak
         x-transition.opacity.duration.150ms
         class="fixed bottom-20 left-1/2 -translate-x-1/2 z-50 px-4 py-2 rounded-full bg-slate-900 text-white text-xs font-bold shadow-lg flex items-center gap-2 whitespace-nowrap">
        <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
        <span x-text="cartToastMessage"></span>
    </div>

    <!-- ===================================================================== -->
    <!-- FLOATING BAR KERANJANG TERKELOMPOK PER LAPAK                          -->
    <!-- ===================================================================== -->
    <div x-show="isVerifiedCitizen && cartTotalItems > 0"
         x-cloak
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 translate-y-4"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="fixed inset-x-0 bottom-4 z-40 px-4 pointer-events-none">
        <div class="max-w-2xl mx-auto pointer-events-auto">
            <button type="button"
                    @click="cartModalOpen = true"
                    class="w-full px-4 py-3 rounded-2xl bg-emerald-950 hover:bg-emerald-900 text-white shadow-xl border border-emerald-700/80 flex items-center justify-between gap-3 transition cursor-pointer">
                <div class="flex items-center gap-3 min-w-0 text-left">
                    <div class="w-9 h-9 rounded-xl bg-amber-400 text-slate-950 font-extrabold text-sm flex items-center justify-center shrink-0"
                         x-text="cartTotalItems"></div>
                    <div class="min-w-0">
                        <div class="text-xs sm:text-sm font-extrabold truncate">
                            Keranjang Belanja (<span x-text="cartStoreGroups.length"></span> Lapak)
                        </div>
                        <div class="text-[11px] text-emerald-200 truncate">
                            <span x-text="cartEstimatedTotal > 0 ? 'Estimasi ' + formatRupiah(cartEstimatedTotal) : 'Siap dikirim via WhatsApp'"></span>
                            &bull; Terkelompok otomatis per lapak
                        </div>
                    </div>
                </div>
                <span class="px-3 py-1.5 rounded-xl bg-emerald-500 text-slate-950 text-xs font-extrabold shrink-0">
                    Lihat &amp; Pesan &rarr;
                </span>
            </button>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- MODAL DETAIL PRODUK / JASA & CHECKOUT WHATSAPP / KERANJANG            -->
    <!-- ===================================================================== -->
    <div x-show="selectedItem !== null && isVerifiedCitizen"
         x-cloak
         class="fixed inset-0 z-50 !mt-0 flex items-center justify-center p-3 sm:p-4 bg-slate-900/55 backdrop-blur-[1px] overflow-y-auto">
        <div @click.away="closeItemDetail()"
             class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-[calc(100vw-1.5rem)] sm:max-w-lg max-h-[90dvh] flex flex-col overflow-hidden my-auto">

            <!-- Header Visual Produk -->
            <div :class="selectedItem?.coverGradient || 'bg-emerald-50'"
                 class="relative px-5 py-6 flex items-center gap-4 border-b border-slate-200/80 shrink-0">
                <!-- Tombol Bagikan & Tutup -->
                <div class="absolute top-3 right-3 flex items-center gap-1.5">
                    <button x-show="selectedItem && !selectedItem.isMockup"
                            type="button"
                            @click="shareCurrentItem()"
                            title="Bagikan link produk ini"
                            class="h-8 px-2.5 rounded-full bg-white/95 hover:bg-white text-emerald-800 border border-emerald-200/80 flex items-center gap-1 text-[11px] font-bold shadow-2xs cursor-pointer transition">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.217 10.907a2.25 2.25 0 100 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186l9.566-5.314m-9.566 7.5l9.566 5.314m0 0a2.25 2.25 0 103.935 2.186 2.25 2.25 0 00-3.935-2.186zm0-12.814a2.25 2.25 0 103.933-2.185 2.25 2.25 0 00-3.933 2.185z" />
                        </svg>
                        <span x-text="shareCopied ? 'Link Tersalin!' : 'Bagikan'"></span>
                    </button>

                    <button type="button"
                            @click="closeItemDetail()"
                            aria-label="Tutup"
                            class="w-8 h-8 rounded-full bg-white/90 hover:bg-white text-slate-700 flex items-center justify-center text-sm font-bold shadow-2xs cursor-pointer">
                        ✕
                    </button>
                </div>

                <div class="w-20 h-20 rounded-2xl bg-white/85 border border-white shadow-xs flex items-center justify-center text-4xl shrink-0 overflow-hidden">
                    <img x-show="selectedItem?.imageUrl"
                         :src="selectedItem?.imageUrl"
                         :alt="selectedItem?.name"
                         x-on:error="if (selectedItem) selectedItem.imageUrl = ''"
                         class="w-full h-full object-cover">
                    <span x-show="!selectedItem?.imageUrl" x-text="selectedItem?.emoji"></span>
                </div>

                <div class="min-w-0 flex-1 pr-20 space-y-1">
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

                <!-- Banner Pemberitahuan jika Lapak Sedang Tutup / Jam Istirahat -->
                <div x-show="selectedItem?.storeStatus === 'closed'"
                     class="p-3 rounded-xl bg-slate-100 border border-slate-300/90 text-xs text-slate-800 space-y-1">
                    <div class="font-bold text-slate-900">
                        &#128337; Lapak Sedang Tutup / Di Luar Jam Operasional
                    </div>
                    <p class="leading-relaxed text-slate-600"
                       x-text="(selectedItem?.operatingHours ? 'Jam layanan lapak: ' + selectedItem.operatingHours + '. ' : '') + 'Anda tetap dapat menitipkan pesan WhatsApp sekarang; pelapak akan memprosesnya saat lapak buka kembali.'"></p>
                </div>

                <!-- Info Lapak -->
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/80 flex items-center justify-between gap-2 text-xs">
                    <div class="min-w-0">
                        <div class="font-bold text-slate-900 truncate" x-text="selectedItem?.storeName"></div>
                        <div class="text-slate-500"
                             x-text="'Dikelola oleh ' + selectedItem?.ownerName + (selectedItem?.operatingHours ? ' • Jam: ' + selectedItem.operatingHours : '')"></div>
                    </div>
                    <span :class="selectedItem?.storeStatus === 'closed' ? 'bg-slate-200 text-slate-700' : 'bg-emerald-100 text-emerald-800'"
                          class="px-2.5 py-1 rounded-full text-[11px] font-bold shrink-0"
                          x-text="selectedItem?.storeStatus === 'closed' ? '○ Tutup' : '● Buka'">
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
                           :placeholder="selectedItem?.storeStatus === 'closed' ? 'Contoh: Untuk besok pagi jam 07.00 ya...' : 'Contoh: Sambal dipisah, atau antar jam 4 sore...'"
                           class="w-full rounded-xl border-slate-300 text-xs sm:text-sm">
                </div>

                <!-- Rekomendasi Item Lain dari Lapak yang Sama -->
                <div x-show="sameStoreOtherItems.length > 0"
                     class="pt-3 border-t border-slate-100 space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-700"
                              x-text="'Sekalian pesan dari ' + selectedItem?.storeName + '?'"></span>
                        <span class="text-[11px] text-slate-400">Tambah ke keranjang</span>
                    </div>

                    <div class="space-y-1.5">
                        <template x-for="other in sameStoreOtherItems" :key="other.id">
                            <div class="p-2.5 rounded-xl bg-slate-50 hover:bg-slate-100/80 border border-slate-200/80 flex items-center justify-between gap-2.5">
                                <div class="flex items-center gap-2.5 min-w-0">
                                    <div class="w-9 h-9 rounded-lg bg-white border border-slate-200 flex items-center justify-center text-base shrink-0 overflow-hidden">
                                        <img x-show="other.imageUrl" :src="other.imageUrl" :alt="other.name" class="w-full h-full object-cover">
                                        <span x-show="!other.imageUrl" x-text="other.emoji"></span>
                                    </div>
                                    <div class="min-w-0">
                                        <div class="text-xs font-bold text-slate-800 truncate" x-text="other.name"></div>
                                        <div class="text-[11px] font-semibold text-emerald-700" x-text="other.priceLabel"></div>
                                    </div>
                                </div>

                                <button type="button"
                                        @click="quickAddSiblingToCart(other)"
                                        class="px-2.5 py-1.5 rounded-lg bg-white hover:bg-emerald-50 text-emerald-800 border border-emerald-300 text-[11px] font-extrabold shrink-0 cursor-pointer transition">
                                    <span x-text="getCartItemQty(other.id) > 0 ? '+ Tambah (' + getCartItemQty(other.id) + ')' : '+ Keranjang'"></span>
                                </button>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Footer Aksi: Tambah ke Keranjang + Pesan Langsung WhatsApp -->
            <div class="px-4 sm:px-5 py-3.5 bg-slate-50 border-t border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 shrink-0">
                <div class="text-xs flex items-center justify-between sm:block">
                    <span class="text-slate-500 sm:block">Estimasi Total:</span>
                    <span class="text-sm sm:text-base font-extrabold text-slate-900"
                          x-text="selectedItem?.numericPrice > 0 ? formatRupiah(selectedItem.numericPrice * orderQty) : selectedItem?.priceLabel"></span>
                </div>

                <div class="flex items-center gap-2">
                    <button x-show="selectedItem && !selectedItem.isMockup"
                            type="button"
                            @click="addCurrentModalItemToCart()"
                            class="flex-1 sm:flex-none px-3.5 py-2.5 rounded-xl bg-white hover:bg-emerald-50 text-emerald-800 border border-emerald-300 text-xs sm:text-sm font-extrabold transition cursor-pointer">
                        + Keranjang
                    </button>

                    <a :href="buildOrderWhatsappUrl()"
                       target="_blank"
                       class="flex-1 sm:flex-none px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs sm:text-sm font-bold shadow-sm transition inline-flex items-center justify-center gap-1.5 shrink-0">
                        <span x-text="selectedItem?.storeStatus === 'closed' ? 'Titip Besok (WA)' : (selectedItem?.badgeType === 'po' ? 'Ikut PO (WA)' : 'Pesan via WA')"></span>
                        <span>&rarr;</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- MODAL KERANJANG BELANJA (TERKELOMPOK PER LAPAK)                        -->
    <!-- ===================================================================== -->
    <div x-show="cartModalOpen && isVerifiedCitizen"
         x-cloak
         class="fixed inset-0 z-50 !mt-0 flex items-center justify-center p-3 sm:p-4 bg-slate-900/60 backdrop-blur-[1px] overflow-y-auto">
        <div @click.away="cartModalOpen = false"
             class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-[calc(100vw-1.5rem)] sm:max-w-xl max-h-[90dvh] flex flex-col overflow-hidden my-auto">

            <!-- Header Modal Keranjang -->
            <div class="px-5 py-4 bg-emerald-950 text-white flex items-center justify-between gap-3 shrink-0">
                <div>
                    <h3 class="text-base font-extrabold">Keranjang Pasar Tetangga</h3>
                    <p class="text-[11px] text-emerald-200">
                        Pesanan otomatis dikelompokkan per lapak agar mudah dikirim lewat WhatsApp
                    </p>
                </div>
                <button type="button"
                        @click="cartModalOpen = false"
                        aria-label="Tutup Keranjang"
                        class="w-8 h-8 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center text-sm font-bold cursor-pointer">
                    ✕
                </button>
            </div>

            <!-- Daftar Grup Lapak -->
            <div class="p-4 sm:p-5 space-y-4 overflow-y-auto">
                <template x-if="cartStoreGroups.length === 0">
                    <div class="py-10 text-center space-y-2">
                        <div class="text-sm font-bold text-slate-700">Keranjang belanja masih kosong</div>
                        <p class="text-xs text-slate-500">Pilih produk atau jasa dari katalog lalu tekan tombol "+ Keranjang".</p>
                    </div>
                </template>

                <template x-for="group in cartStoreGroups" :key="group.storeKey">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50/70 overflow-hidden">
                        <!-- Header Grup Lapak -->
                        <div class="px-4 py-3 bg-white border-b border-slate-200 flex items-center justify-between gap-2">
                            <div class="min-w-0">
                                <div class="flex items-center gap-1.5">
                                    <span class="font-extrabold text-sm text-slate-900 truncate" x-text="group.storeName"></span>
                                    <span class="px-1.5 py-0.5 rounded bg-slate-900 text-white font-mono text-[10px] font-bold shrink-0"
                                          x-text="'Kav. ' + group.unitCode"></span>
                                </div>
                                <div class="text-[11px] text-slate-500"
                                     x-text="group.items.length + ' jenis item' + (group.operatingHours ? ' • Jam: ' + group.operatingHours : '')"></div>
                            </div>

                            <div class="flex items-center gap-2 shrink-0">
                                <span :class="group.storeStatus === 'closed' ? 'bg-slate-200 text-slate-700' : 'bg-emerald-100 text-emerald-800'"
                                      class="px-2 py-0.5 rounded-full text-[10px] font-bold"
                                      x-text="group.storeStatus === 'closed' ? '○ Tutup' : '● Buka'"></span>
                                <button type="button"
                                        @click="clearStoreFromCart(group.storeKey)"
                                        title="Hapus semua pesanan di lapak ini"
                                        class="text-[11px] font-semibold text-rose-600 hover:underline cursor-pointer">
                                    Hapus
                                </button>
                            </div>
                        </div>

                        <!-- Daftar Item dalam Lapak Ini -->
                        <div class="divide-y divide-slate-200/80 bg-white">
                            <template x-for="entry in group.items" :key="entry.itemId">
                                <div class="px-4 py-3 flex items-start justify-between gap-3">
                                    <div class="min-w-0 flex-1 space-y-0.5">
                                        <div class="text-xs sm:text-sm font-bold text-slate-900 leading-snug" x-text="entry.name"></div>
                                        <div class="text-xs font-semibold text-emerald-700"
                                             x-text="entry.numericPrice > 0 ? formatRupiah(entry.numericPrice) + ' × ' + entry.qty + ' = ' + formatRupiah(entry.numericPrice * entry.qty) : entry.priceLabel"></div>
                                        <div x-show="entry.note"
                                             class="text-[11px] text-slate-500 italic"
                                             x-text="'Catatan: ' + entry.note"></div>
                                    </div>

                                    <!-- Kontrol Qty -->
                                    <div class="flex items-center gap-1.5 shrink-0">
                                        <button type="button"
                                                @click="updateCartItemQty(entry.itemId, entry.qty - 1)"
                                                class="w-7 h-7 rounded-lg border border-slate-300 bg-slate-50 hover:bg-slate-100 font-bold text-xs text-slate-700 cursor-pointer">
                                            -
                                        </button>
                                        <span class="w-6 text-center text-xs font-extrabold text-slate-900" x-text="entry.qty"></span>
                                        <button type="button"
                                                @click="updateCartItemQty(entry.itemId, entry.qty + 1)"
                                                class="w-7 h-7 rounded-lg border border-slate-300 bg-slate-50 hover:bg-slate-100 font-bold text-xs text-slate-700 cursor-pointer">
                                            +
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- Pengaturan Pengiriman & Tombol Kirim WA Per Lapak -->
                        <div class="p-3.5 bg-slate-50 border-t border-slate-200 space-y-2.5">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <select x-model="storeDeliveryPrefs[group.storeKey]"
                                        @change="persistCart()"
                                        aria-label="Metode Pengambilan Lapak"
                                        class="rounded-xl border-slate-300 text-xs bg-white">
                                    <option value="Antar ke Kavling Saya">Antar ke Kavling Saya</option>
                                    <option value="Ambil Sendiri di Lapak">Ambil Sendiri di Lapak</option>
                                </select>

                                <input type="text"
                                       x-model="storeNotes[group.storeKey]"
                                       @input="persistCart()"
                                       placeholder="Catatan tambahan untuk lapak..."
                                       class="rounded-xl border-slate-300 text-xs bg-white">
                            </div>

                            <div class="flex items-center justify-between gap-3 pt-1">
                                <div class="text-xs">
                                    <span class="text-slate-500 block">Subtotal Lapak:</span>
                                    <span class="text-sm font-extrabold text-slate-900"
                                          x-text="group.subtotal > 0 ? formatRupiah(group.subtotal) : 'Tanya / Pinjam'"></span>
                                </div>

                                <a :href="buildStoreCartWhatsappUrl(group)"
                                   @click="markStoreCartSent(group.storeKey)"
                                   target="_blank"
                                   class="px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-extrabold shadow-sm transition inline-flex items-center gap-1.5 shrink-0">
                                    <span x-text="group.storeStatus === 'closed' ? 'Titip ' + group.totalQty + ' Item (WA)' : 'Pesan ' + group.totalQty + ' Item via WA'"></span>
                                    <span>&rarr;</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
const PPMT_INITIAL_SHARED_ITEM = @json($sharedItem);
const PPMT_CART_STORAGE_KEY = 'ppmt_marketplace_cart_v1';

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

        // Progressive Pagination (2 halaman awal: 1 terlihat + 1 siap di bawah)
        pageSize: 12,
        visibleCount: 24,
        loadingMore: false,
        sentinelObserver: null,
        loadTimer: null,

        // Detail & Deep Link
        selectedItem: null,
        guestSharedItem: null,
        shareCopied: false,
        orderQty: 1,
        orderDelivery: 'Antar ke Kavling Saya',
        orderNote: '',

        // Keranjang Terkelompok per Lapak
        cartItems: [],
        storeDeliveryPrefs: {},
        storeNotes: {},
        cartModalOpen: false,
        cartToastMessage: '',
        cartToastTimer: null,

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
                storeId: null,
                itemKey: null,
                storeKey: 'mock-store-1',
                isMockup: true,
                isFeatured: false,
                isAvailable: true,
                storeStatus: 'open',
                operatingHours: '07.00 – 20.00 WIB',
                name: 'Galon Air Mineral & Isi Ulang RO Antar Depan Pintu',
                category: 'sembako',
                categoryLabel: 'Sembako, Galon & Gas',
                badgeType: 'ready',
                badgeLabel: 'Ready Stock',
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
                storeId: null,
                itemKey: null,
                storeKey: 'mock-store-2',
                isMockup: true,
                isFeatured: false,
                isAvailable: true,
                storeStatus: 'open',
                operatingHours: '06.00 – 09.30 WIB',
                name: 'Nasi Pecel Madiun Komplit + Rempeyek Kacang',
                category: 'kuliner',
                categoryLabel: 'Kuliner & Minuman',
                badgeType: 'ready',
                badgeLabel: 'Ready Stock',
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
                storeId: null,
                itemKey: null,
                storeKey: 'mock-store-3',
                isMockup: true,
                isFeatured: false,
                isAvailable: true,
                storeStatus: 'open',
                operatingHours: '08.00 – 17.00 WIB',
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
                storeId: null,
                itemKey: null,
                storeKey: 'mock-store-4',
                isMockup: true,
                isFeatured: false,
                isAvailable: true,
                storeStatus: 'open',
                operatingHours: '07.00 – 21.00 WIB',
                name: 'Pinjam / Sewa Tangga Lipat Aluminium 2,5 Meter',
                category: 'pinjam_alat',
                categoryLabel: 'Pinjam / Sewa Alat',
                badgeType: 'loan',
                badgeLabel: 'Pinjam / Sewa',
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

        get isVerifiedCitizen() {
            return Boolean(this.user && this.user.is_verified);
        },

        formatSharedItemForGuest(raw) {
            if (!raw) return null;
            const catCode = raw.category || 'lainnya';
            const meta = this.categoryMeta(catCode);
            const priceNum = Number(raw.numericPrice || 0);
            const badgeType = raw.badgeType || 'ready';

            return {
                ...raw,
                category: catCode,
                categoryLabel: meta.label,
                badgeType,
                badgeLabel: this.badgeLabelFor(badgeType),
                numericPrice: priceNum,
                priceLabel: priceNum > 0 ? this.formatRupiah(priceNum) : (raw.itemKey === 'store' ? 'Tanya Harga via WA' : 'Gratis / Pinjam'),
                emoji: meta.emoji,
                coverGradient: meta.grad,
            };
        },

        loadSavedCart() {
            try {
                const raw = JSON.parse(localStorage.getItem(PPMT_CART_STORAGE_KEY) || '{}');
                this.cartItems = Array.isArray(raw.items) ? raw.items : [];
                this.storeDeliveryPrefs = (raw.deliveryPrefs && typeof raw.deliveryPrefs === 'object') ? raw.deliveryPrefs : {};
                this.storeNotes = (raw.storeNotes && typeof raw.storeNotes === 'object') ? raw.storeNotes : {};
            } catch (e) {
                this.cartItems = [];
                this.storeDeliveryPrefs = {};
                this.storeNotes = {};
            }
        },

        persistCart() {
            try {
                localStorage.setItem(PPMT_CART_STORAGE_KEY, JSON.stringify({
                    items: this.cartItems,
                    deliveryPrefs: this.storeDeliveryPrefs,
                    storeNotes: this.storeNotes
                }));
            } catch (e) {}
        },

        syncCartWithLiveCatalog() {
            if (!this.liveItems.length || !this.cartItems.length) return;
            const liveMap = new Map(this.liveItems.map(i => [i.id, i]));
            this.cartItems = this.cartItems
                .filter(entry => liveMap.has(entry.itemId))
                .map(entry => {
                    const fresh = liveMap.get(entry.itemId);
                    return {
                        ...entry,
                        name: fresh.name,
                        numericPrice: fresh.numericPrice,
                        priceLabel: fresh.priceLabel,
                        badgeType: fresh.badgeType,
                        storeKey: fresh.storeKey,
                        storeName: fresh.storeName,
                        unitCode: fresh.unitCode,
                        storeStatus: fresh.storeStatus,
                        operatingHours: fresh.operatingHours,
                        whatsapp: fresh.whatsapp
                    };
                });
            this.persistCart();
        },

        showCartToast(msg) {
            this.cartToastMessage = msg;
            if (this.cartToastTimer) clearTimeout(this.cartToastTimer);
            this.cartToastTimer = setTimeout(() => {
                this.cartToastMessage = '';
                this.cartToastTimer = null;
            }, 2200);
        },

        getCartItemQty(itemId) {
            const found = this.cartItems.find(c => c.itemId === itemId);
            return found ? found.qty : 0;
        },

        upsertCartItem(item, qtyToAdd = 1, note = '', deliveryPref = null) {
            if (!item || item.isMockup) return;
            const addCount = Math.max(1, Math.min(50, Number(qtyToAdd || 1)));

            if (!this.storeDeliveryPrefs[item.storeKey]) {
                this.storeDeliveryPrefs[item.storeKey] = deliveryPref || 'Antar ke Kavling Saya';
            } else if (deliveryPref) {
                this.storeDeliveryPrefs[item.storeKey] = deliveryPref;
            }

            const existingIdx = this.cartItems.findIndex(c => c.itemId === item.id);
            if (existingIdx >= 0) {
                const updated = [...this.cartItems];
                const current = updated[existingIdx];
                updated[existingIdx] = {
                    ...current,
                    qty: Math.min(50, current.qty + addCount),
                    note: note.trim() ? note.trim() : current.note
                };
                this.cartItems = updated;
            } else {
                this.cartItems = [
                    ...this.cartItems,
                    {
                        itemId: item.id,
                        storeKey: item.storeKey,
                        storeName: item.storeName,
                        unitCode: item.unitCode,
                        storeStatus: item.storeStatus,
                        operatingHours: item.operatingHours || '',
                        whatsapp: item.whatsapp,
                        name: item.name,
                        badgeType: item.badgeType,
                        numericPrice: item.numericPrice,
                        priceLabel: item.priceLabel,
                        qty: addCount,
                        note: note.trim()
                    }
                ];
            }
            this.persistCart();
        },

        addCurrentModalItemToCart() {
            if (!this.selectedItem || this.selectedItem.isMockup) return;
            const addedName = this.selectedItem.name;
            const addedQty = this.orderQty;
            this.upsertCartItem(this.selectedItem, addedQty, this.orderNote, this.orderDelivery);
            this.closeItemDetail();
            this.showCartToast(`+${addedQty} ${addedName} masuk keranjang`);
        },

        quickAddSiblingToCart(otherItem) {
            if (!otherItem || otherItem.isMockup) return;
            if (!this.storeDeliveryPrefs[otherItem.storeKey]) {
                this.storeDeliveryPrefs[otherItem.storeKey] = this.orderDelivery || 'Antar ke Kavling Saya';
            }
            this.upsertCartItem(otherItem, 1, '', null);
            this.showCartToast(`+1 ${otherItem.name} masuk keranjang`);
        },

        updateCartItemQty(itemId, newQty) {
            if (newQty <= 0) {
                this.cartItems = this.cartItems.filter(c => c.itemId !== itemId);
            } else {
                this.cartItems = this.cartItems.map(c => c.itemId === itemId
                    ? { ...c, qty: Math.min(50, newQty) }
                    : c
                );
            }
            this.persistCart();
            if (this.cartItems.length === 0) {
                this.cartModalOpen = false;
            }
        },

        clearStoreFromCart(storeKey) {
            this.cartItems = this.cartItems.filter(c => c.storeKey !== storeKey);
            delete this.storeDeliveryPrefs[storeKey];
            delete this.storeNotes[storeKey];
            this.persistCart();
            if (this.cartItems.length === 0) {
                this.cartModalOpen = false;
            }
        },

        markStoreCartSent(storeKey) {
            const storeName = this.cartItems.find(c => c.storeKey === storeKey)?.storeName || 'Lapak';
            setTimeout(() => {
                this.clearStoreFromCart(storeKey);
                this.showCartToast(`Pesanan ke ${storeName} diteruskan ke WhatsApp`);
            }, 300);
        },

        get cartTotalItems() {
            return this.cartItems.reduce((sum, item) => sum + Number(item.qty || 0), 0);
        },

        get cartEstimatedTotal() {
            return this.cartItems.reduce((sum, item) => sum + (Number(item.numericPrice || 0) * Number(item.qty || 0)), 0);
        },

        get cartStoreGroups() {
            const map = new Map();
            for (const entry of this.cartItems) {
                if (!map.has(entry.storeKey)) {
                    map.set(entry.storeKey, {
                        storeKey: entry.storeKey,
                        storeName: entry.storeName,
                        unitCode: entry.unitCode,
                        storeStatus: entry.storeStatus,
                        operatingHours: entry.operatingHours,
                        whatsapp: entry.whatsapp,
                        items: [],
                        totalQty: 0,
                        subtotal: 0
                    });
                }
                const g = map.get(entry.storeKey);
                g.items.push(entry);
                g.totalQty += Number(entry.qty || 0);
                g.subtotal += Number(entry.numericPrice || 0) * Number(entry.qty || 0);
            }
            return Array.from(map.values());
        },

        get sameStoreOtherItems() {
            if (!this.selectedItem || this.selectedItem.isMockup || !this.selectedItem.storeKey) {
                return [];
            }
            return this.liveItems
                .filter(i => i.storeKey === this.selectedItem.storeKey
                    && i.id !== this.selectedItem.id
                    && i.isAvailable !== false)
                .slice(0, 4);
        },

        async init() {
            this.loadSavedCart();
            this.guestSharedItem = this.formatSharedItemForGuest(PPMT_INITIAL_SHARED_ITEM);

            if (!this.token && !this.guestSharedItem) {
                localStorage.setItem('ppmt_intended_url', window.location.pathname);
                window.location.href = '/?login=1';
                return;
            }

            const resetPagination = () => {
                if (this.loadTimer) {
                    clearTimeout(this.loadTimer);
                    this.loadTimer = null;
                }
                this.loadingMore = false;
                this.visibleCount = this.pageSize * 2;
                this.$nextTick(() => this.checkSentinelImmediate());
            };

            this.$watch('searchQuery', resetPagination);
            this.$watch('selectedCategory', resetPagination);
            this.$watch('selectedAvailability', resetPagination);
            this.$watch('sortBy', resetPagination);

            if (!this.token) {
                this.loading = false;
                return;
            }

            try {
                const [meRes, storesRes] = await Promise.all([
                    fetch('/api/v1/me', { headers: this.headers() }),
                    fetch('/api/v1/umkm/stores', { headers: this.headers() }),
                ]);

                if (!meRes.ok) {
                    localStorage.removeItem('ppmt_token');
                    this.token = null;
                    if (!this.guestSharedItem) {
                        localStorage.setItem('ppmt_intended_url', window.location.pathname);
                        window.location.href = '/?login=1';
                        return;
                    }
                    this.loading = false;
                    return;
                }

                const meJson = await meRes.json();
                this.user = meJson.data;

                if (!this.user?.is_verified) {
                    if (!this.guestSharedItem) {
                        window.location.href = '/dashboard';
                        return;
                    }
                    this.loading = false;
                    return;
                }

                if (storesRes.ok) {
                    const storesJson = await storesRes.json();
                    this.hydrateStoresCatalog(storesJson.data || []);
                    this.syncCartWithLiveCatalog();

                    if (this.guestSharedItem) {
                        const matched = this.liveItems.find(i => i.id === this.guestSharedItem.id);
                        if (matched) {
                            this.openItemDetail(matched, false);
                        }
                    }
                }
            } catch (e) {
                localStorage.removeItem('ppmt_token');
                this.token = null;
                if (!this.guestSharedItem) {
                    window.location.href = '/?login=1';
                    return;
                }
            } finally {
                this.loading = false;
                if (this.isVerifiedCitizen) {
                    this.$nextTick(() => this.setupScrollObserver());
                }
            }
        },

        redirectToLoginForOrder() {
            if (this.user && !this.user.is_verified) {
                window.location.href = '/dashboard';
                return;
            }
            localStorage.setItem('ppmt_intended_url', window.location.pathname);
            window.location.href = '/?login=1';
        },

        setupScrollObserver() {
            const el = this.$refs.scrollSentinel;
            if (!el || typeof IntersectionObserver === 'undefined') return;

            if (this.sentinelObserver) {
                this.sentinelObserver.disconnect();
            }

            this.sentinelObserver = new IntersectionObserver((entries) => {
                if (entries.some(entry => entry.isIntersecting)) {
                    this.loadNextPage();
                }
            }, {
                root: null,
                rootMargin: '250px 0px',
                threshold: 0
            });

            this.sentinelObserver.observe(el);
        },

        checkSentinelImmediate() {
            const el = this.$refs.scrollSentinel;
            if (!el || !this.hasMoreItems) return;
            const rect = el.getBoundingClientRect();
            if (rect.top <= window.innerHeight + 250) {
                this.loadNextPage();
            }
        },

        loadNextPage() {
            if (this.loadingMore || !this.hasMoreItems) return;
            this.loadingMore = true;

            this.loadTimer = setTimeout(() => {
                this.visibleCount += this.pageSize;
                this.loadingMore = false;
                this.loadTimer = null;
                this.$nextTick(() => this.checkSentinelImmediate());
            }, 220);
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
                const normalizedStoreStatus = store.status === 'closed' ? 'closed' : 'open';

                if (catalog.length > 0) {
                    catalog.forEach((prod, idx) => {
                        const catCode = prod.category || store.category || 'lainnya';
                        const meta = this.categoryMeta(catCode);
                        const badgeType = prod.badge_type || (store.status === 'open_po' ? 'po' : 'ready');
                        const priceNum = Number(prod.price || 0);
                        const itemKey = String(prod.id ?? idx);

                        flattened.push({
                            id: `live-${store.id}-${itemKey}`,
                            storeId: store.id,
                            itemKey,
                            storeKey,
                            isMockup: false,
                            isFeatured,
                            isAvailable: prod.is_available !== false,
                            storeStatus: normalizedStoreStatus,
                            operatingHours: store.operating_hours || '',
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
                        storeId: store.id,
                        itemKey: 'store',
                        storeKey,
                        isMockup: false,
                        isFeatured,
                        isAvailable: true,
                        storeStatus: normalizedStoreStatus,
                        operatingHours: store.operating_hours || '',
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

        get visibleItems() {
            return this.filteredItems.slice(0, this.visibleCount);
        },

        get hasMoreItems() {
            return this.visibleCount < this.filteredItems.length;
        },

        get remainingItemsCount() {
            return Math.max(0, this.filteredItems.length - this.visibleCount);
        },

        formatRupiah(value) {
            return 'Rp ' + Number(value || 0).toLocaleString('id-ID');
        },

        getShareablePath(item) {
            if (!item || item.isMockup || !item.storeId) {
                return '/komunitas/pasar-tetangga';
            }
            const itemKey = item.itemKey || 'store';
            return `/komunitas/pasar-tetangga/p/${item.storeId}/${encodeURIComponent(itemKey)}`;
        },

        openItemDetail(item, syncUrl = true) {
            this.selectedItem = item;
            this.shareCopied = false;
            this.orderQty = 1;
            this.orderDelivery = this.storeDeliveryPrefs[item.storeKey] || 'Antar ke Kavling Saya';
            this.orderNote = '';

            if (syncUrl && window.history && typeof window.history.replaceState === 'function') {
                const targetPath = this.getShareablePath(item);
                if (window.location.pathname !== targetPath) {
                    window.history.replaceState({ itemId: item.id }, '', targetPath);
                }
            }
        },

        closeItemDetail() {
            if (!this.selectedItem) return;
            this.selectedItem = null;
            this.shareCopied = false;

            if (window.history && typeof window.history.replaceState === 'function') {
                if (window.location.pathname !== '/komunitas/pasar-tetangga') {
                    window.history.replaceState({}, '', '/komunitas/pasar-tetangga');
                }
            }
        },

        async shareCurrentItem() {
            if (!this.selectedItem) return;
            const shareUrl = window.location.origin + this.getShareablePath(this.selectedItem);
            const shareText = `${this.selectedItem.name} (${this.selectedItem.priceLabel}) di ${this.selectedItem.storeName} — Pasar Tetangga PPMT`;

            if (navigator.share) {
                try {
                    await navigator.share({
                        title: this.selectedItem.name,
                        text: shareText,
                        url: shareUrl
                    });
                    return;
                } catch (e) {}
            }

            try {
                await navigator.clipboard.writeText(shareUrl);
                this.shareCopied = true;
                setTimeout(() => { this.shareCopied = false; }, 2500);
            } catch (e) {
                window.prompt('Salin tautan produk berikut:', shareUrl);
            }
        },

        buildOrderWhatsappUrl() {
            if (!this.selectedItem) return '#';
            const item = this.selectedItem;
            const myName = this.user?.display_name || this.user?.name || 'Warga PPMT';
            const myUnit = this.user?.memberships?.[0]?.unit?.code || 'PPMT';
            const isClosed = item.storeStatus === 'closed';

            const introLine = isClosed
                ? `Mohon maaf mengganggu waktu istirahatnya. Saya *${myName}* dari *Kavling ${myUnit}* ingin menitipkan pesanan untuk besok / saat lapak buka kembali:`
                : (item.badgeType === 'po'
                    ? `Saya *${myName}* dari *Kavling ${myUnit}* ingin ikut Pre-Order (PO) dari Pasar Tetangga PPMT:`
                    : `Saya *${myName}* dari *Kavling ${myUnit}* ingin memesan dari Pasar Tetangga PPMT:`);

            const lines = [
                `Halo *${item.storeName}* (Kav. ${item.unitCode}),`,
                introLine,
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

        buildStoreCartWhatsappUrl(group) {
            if (!group) return '#';
            const myName = this.user?.display_name || this.user?.name || 'Warga PPMT';
            const myUnit = this.user?.memberships?.[0]?.unit?.code || 'PPMT';
            const isClosed = group.storeStatus === 'closed';
            const delivery = this.storeDeliveryPrefs[group.storeKey] || 'Antar ke Kavling Saya';
            const generalNote = (this.storeNotes[group.storeKey] || '').trim();

            const introLine = isClosed
                ? `Mohon maaf mengganggu waktu istirahatnya. Saya *${myName}* dari *Kavling ${myUnit}* ingin menitipkan pesanan untuk besok / saat lapak buka kembali:`
                : `Saya *${myName}* dari *Kavling ${myUnit}* ingin memesan beberapa item dari Pasar Tetangga PPMT:`;

            const itemLines = group.items.map(entry => {
                const subText = entry.numericPrice > 0
                    ? ` (${this.formatRupiah(entry.numericPrice * entry.qty)})`
                    : '';
                const noteText = entry.note ? ` — _${entry.note}_` : '';
                return `• *${entry.qty}x ${entry.name}*${subText}${noteText}`;
            });

            const lines = [
                `Halo *${group.storeName}* (Kav. ${group.unitCode}),`,
                introLine,
                ``,
                ...itemLines,
                ``,
                group.subtotal > 0 ? `• *Estimasi Total:* ${this.formatRupiah(group.subtotal)}` : null,
                `• *Pengambilan:* ${delivery}`,
                generalNote ? `• *Catatan:* ${generalNote}` : null,
                ``,
                `Terima kasih!`
            ].filter(Boolean).join('\n');

            return `https://wa.me/${group.whatsapp}?text=${encodeURIComponent(lines)}`;
        }
    }
}
</script>
@endpush
<article @click="openItemDetail(item)"
         :class="item.isAvailable === false ? 'opacity-70' : ''"
         class="group bg-white rounded-2xl border border-slate-200 hover:border-emerald-400 shadow-2xs hover:shadow-md overflow-hidden flex flex-col justify-between transition cursor-pointer">

    <div>
        <!-- Area Visual Produk (Aspect Square) -->
        <div :class="item.coverGradient"
             class="relative aspect-square w-full flex flex-col items-center justify-center p-4 overflow-hidden select-none">

            <!-- Foto Produk (Jika Diunggah Pelapak) -->
            <img x-show="item.imageUrl"
                 :src="item.imageUrl"
                 :alt="item.name"
                 loading="lazy"
                 x-on:error="item.imageUrl = ''"
                 class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">

            <!-- Scrim Tipis Agar Badge Tetap Kontras di Atas Foto Terang -->
            <div x-show="item.imageUrl"
                 class="absolute inset-0 bg-gradient-to-b from-black/20 via-transparent to-black/25 pointer-events-none"></div>

            <!-- Badge Status Kiri Atas:
                 - Jika stok habis: Stok Habis
                 - Jika lapak tutup TAPI item bertipe Open PO: tetap Open PO (bisa ikut PO untuk jadwal berikutnya)
                 - Jika lapak tutup & item Ready/Jasa/Pinjam: Sedang Tutup (bisa titip pesan WA untuk besok) -->
            <span :class="{
                    'bg-rose-600 text-white': item.isAvailable === false,
                    'bg-slate-700/95 text-white': item.isAvailable !== false && item.storeStatus === 'closed' && item.badgeType !== 'po',
                    'bg-emerald-600 text-white': item.isAvailable !== false && item.storeStatus !== 'closed' && item.badgeType === 'ready',
                    'bg-amber-500 text-slate-950': item.isAvailable !== false && item.badgeType === 'po',
                    'bg-indigo-600 text-white': item.isAvailable !== false && item.storeStatus !== 'closed' && item.badgeType === 'service',
                    'bg-teal-600 text-white': item.isAvailable !== false && item.storeStatus !== 'closed' && item.badgeType === 'loan'
                  }"
                  class="z-10 absolute top-2.5 left-2.5 px-2 py-0.5 rounded-md text-[10px] font-extrabold uppercase tracking-wide shadow-sm"
                  x-text="item.isAvailable === false
                      ? 'Stok Habis'
                      : (item.storeStatus === 'closed' && item.badgeType !== 'po' ? 'Sedang Tutup' : item.badgeLabel)"></span>

            <!-- Pita Sorotan Seksi Ekonomi -->
            <span x-show="item.isFeatured"
                  class="z-10 absolute top-9 left-2.5 px-2 py-0.5 rounded-md text-[10px] font-extrabold bg-amber-400 text-slate-950 shadow-sm">
                &#9733; Sorotan
            </span>

            <!-- Badge Blok / Kavling Kanan Atas -->
            <span class="z-10 absolute top-2.5 right-2.5 px-2 py-0.5 rounded-md text-[10px] font-mono font-bold bg-white/95 text-slate-800 border border-slate-200/80 shadow-sm"
                  x-text="item.unitCode"></span>

            <!-- Ilustrasi Besar Kategori (Fallback jika belum ada foto) -->
            <div x-show="!item.imageUrl"
                 class="text-5xl sm:text-6xl group-hover:scale-110 transition-transform duration-200 drop-shadow-xs"
                 x-text="item.emoji"></div>

            <!-- Label Antar / Ambil / Mockup di Bawah Gambar -->
            <div x-show="item.deliveryTag || item.isMockup"
                 class="z-10 absolute bottom-2 inset-x-2.5 flex justify-center">
                <span :class="item.isMockup ? 'bg-slate-800/80 text-amber-200' : 'bg-slate-900/80 text-white'"
                      class="px-2.5 py-0.5 rounded-full text-[10px] font-semibold backdrop-blur-[1px] truncate max-w-full shadow-2xs"
                      x-text="item.isMockup ? 'Contoh Etalase' : item.deliveryTag"></span>
            </div>
        </div>

        <!-- Info Produk -->
        <div class="p-3 sm:p-3.5 space-y-1">
            <div class="text-[10px] font-bold uppercase tracking-wider text-emerald-700"
                 x-text="item.categoryLabel"></div>

            <h2 class="text-xs sm:text-sm font-bold text-slate-900 group-hover:text-emerald-800 leading-snug line-clamp-2"
                x-text="item.name"></h2>

            <div class="pt-0.5">
                <!-- Kondisi 1: Harga angka tunggal (misal: Rp 10.000 / Porsi) -->
                <template x-if="item.numericPrice > 0 && !String(item.priceLabel || '').includes('–')">
                    <div class="flex flex-wrap items-baseline leading-tight">
                        <span class="text-xs font-bold text-emerald-700 mr-0.5">Rp</span>
                        <span class="text-lg font-extrabold tracking-tight text-emerald-800"
                              x-text="Number(item.numericPrice).toLocaleString('id-ID')"></span>
                        <span x-show="item.unitNote"
                              class="text-xs font-medium text-slate-500 ml-1"
                              x-text="'/ ' + String(item.unitNote || '').replace(/^(\/\s*|per\s+)/i, '')"></span>
                    </div>
                </template>

                <!-- Kondisi 2: Gratis / Pinjam / Tanya Harga / Rentang Harga -->
                <template x-if="!(item.numericPrice > 0 && !String(item.priceLabel || '').includes('–'))">
                    <div class="space-y-0.5">
                        <div class="text-sm sm:text-base font-extrabold text-emerald-800 leading-tight"
                             x-text="item.priceLabel"></div>
                        <div x-show="item.unitNote"
                             class="text-xs font-medium text-slate-500 truncate"
                             x-text="item.unitNote"></div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Footer Kartu: Nama Lapak & Tombol Cepat -->
    <div class="px-3 sm:px-3.5 pb-3 sm:pb-3.5 pt-2 border-t border-slate-100 space-y-2.5">
        <div class="flex items-center justify-between gap-1.5 text-[11px] text-slate-500">
            <span class="truncate font-medium text-slate-700" x-text="item.storeName"></span>
            <span :class="item.storeStatus === 'closed' ? 'text-slate-500' : 'text-emerald-700'"
                  class="inline-flex items-center gap-1 shrink-0 text-[10px] font-bold">
                <span :class="item.storeStatus === 'closed' ? 'bg-slate-400' : 'bg-emerald-500'"
                      class="w-1.5 h-1.5 rounded-full"></span>
                <span x-text="item.storeStatus === 'closed' ? 'Tutup' : 'Buka'"></span>
            </span>
        </div>

        <button type="button"
                @click.stop="openItemDetail(item)"
                class="w-full py-1.5 px-2.5 rounded-xl bg-emerald-50 group-hover:bg-emerald-700 text-emerald-800 group-hover:text-white border border-emerald-200 group-hover:border-emerald-700 text-[11px] font-bold transition cursor-pointer"
                x-text="item.storeStatus === 'closed' && item.badgeType !== 'po' ? 'Titip Pesan Besok' : 'Lihat & Pesan'">
        </button>
    </div>
</article>
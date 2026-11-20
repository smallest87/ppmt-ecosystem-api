<article @click="openItemDetail(item)"
         :class="item.isAvailable === false ? 'opacity-70' : ''"
         class="group bg-white rounded-2xl border border-slate-200 hover:border-emerald-400 shadow-2xs hover:shadow-md overflow-hidden flex flex-col justify-between transition cursor-pointer">

    <div>
        <!-- Area Visual Produk (Aspect Square) -->
        <div :class="item.coverGradient"
             class="relative aspect-square w-full flex flex-col items-center justify-center p-4 overflow-hidden select-none">

            <!-- Badge Status Kiri Atas -->
            <span :class="{
                    'bg-rose-600 text-white': item.isAvailable === false || item.storeStatus === 'closed',
                    'bg-emerald-600 text-white': item.isAvailable !== false && item.storeStatus !== 'closed' && item.badgeType === 'ready',
                    'bg-amber-500 text-slate-950': item.isAvailable !== false && item.storeStatus !== 'closed' && item.badgeType === 'po',
                    'bg-indigo-600 text-white': item.isAvailable !== false && item.storeStatus !== 'closed' && item.badgeType === 'service',
                    'bg-teal-600 text-white': item.isAvailable !== false && item.storeStatus !== 'closed' && item.badgeType === 'loan'
                  }"
                  class="absolute top-2.5 left-2.5 px-2 py-0.5 rounded-md text-[10px] font-extrabold uppercase tracking-wide shadow-2xs"
                  x-text="item.storeStatus === 'closed' ? 'Lapak Tutup' : (item.isAvailable === false ? 'Stok Habis' : item.badgeLabel)"></span>

            <!-- Pita Sorotan Seksi Ekonomi -->
            <span x-show="item.isFeatured"
                  class="absolute top-8 left-2.5 px-2 py-0.5 rounded-md text-[10px] font-extrabold bg-amber-400 text-slate-950 shadow-2xs">
                &#9733; Sorotan
            </span>

            <!-- Badge Blok / Kavling Kanan Atas -->
            <span class="absolute top-2.5 right-2.5 px-2 py-0.5 rounded-md text-[10px] font-mono font-bold bg-white/90 text-slate-800 border border-slate-200/80 shadow-2xs"
                  x-text="item.unitCode"></span>

            <!-- Ilustrasi Besar Kategori -->
            <div class="text-5xl sm:text-6xl group-hover:scale-110 transition-transform duration-200 drop-shadow-xs"
                 x-text="item.emoji"></div>

            <!-- Label Antar / Ambil / Mockup di Bawah Gambar -->
            <div x-show="item.deliveryTag || item.isMockup"
                 class="absolute bottom-2 inset-x-2.5 flex justify-center">
                <span :class="item.isMockup ? 'bg-slate-800/75 text-amber-200' : 'bg-slate-900/75 text-white'"
                      class="px-2 py-0.5 rounded-full text-[10px] font-semibold backdrop-blur-[1px] truncate"
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
                              x-text="'/ ' + item.unitNote.replace(/^(\/\s*|per\s+)/i, '')"></span>
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
            <span :class="item.storeStatus === 'closed' ? 'text-slate-400' : (item.storeStatus === 'open_po' ? 'text-amber-700' : 'text-emerald-700')"
                  class="inline-flex items-center gap-1 shrink-0 text-[10px] font-bold">
                <span :class="item.storeStatus === 'closed' ? 'bg-slate-400' : (item.storeStatus === 'open_po' ? 'bg-amber-500' : 'bg-emerald-500')"
                      class="w-1.5 h-1.5 rounded-full"></span>
                <span x-text="item.storeStatus === 'closed' ? 'Tutup' : (item.storeStatus === 'open_po' ? 'PO' : 'Buka')"></span>
            </span>
        </div>

        <button type="button"
                @click.stop="openItemDetail(item)"
                class="w-full py-1.5 px-2.5 rounded-xl bg-emerald-50 group-hover:bg-emerald-700 text-emerald-800 group-hover:text-white border border-emerald-200 group-hover:border-emerald-700 text-[11px] font-bold transition cursor-pointer">
            Lihat &amp; Pesan
        </button>
    </div>
</article>
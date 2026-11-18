<!-- Kartu 3: Kabar & Berita Warga (Hanya Pengurus yang bisa menerbitkan) -->
<section class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
    <!-- Header Kartu Berita -->
    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between gap-3">
        <div class="flex items-center gap-2.5">
            <span class="w-2.5 h-2.5 rounded-full bg-emerald-600"></span>
            <h2 class="text-base font-bold text-slate-900">Kabar &amp; Berita Warga</h2>
        </div>
        <button type="button"
                x-show="canPublishNews"
                @click="showContributeModal = true"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold bg-emerald-700 hover:bg-emerald-800 text-white shadow-2xs transition cursor-pointer shrink-0">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.4">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125" />
            </svg>
            <span>Terbitkan Kabar</span>
        </button>
    </div>

    <!-- Empty State Jika Belum Ada Berita Terbit -->
    <div x-show="newsArticles.length === 0" class="px-6 py-10 text-center space-y-1.5">
        <div class="text-xs font-semibold text-slate-600">Belum ada kabar atau berita warga yang diterbitkan.</div>
        <p x-show="canPublishNews" class="text-[11px] text-slate-400">
            Klik tombol <strong>Terbitkan Kabar</strong> di kanan atas untuk membagikan informasi kegiatan lingkungan.
        </p>
    </div>

    <div x-show="newsArticles.length > 0" class="divide-y divide-slate-100">

        <!-- VARIASI 1: HERO / HEADLINE UTAMA (GAMBAR LEBAR) -->
        <template x-if="heroNews">
            <a :href="newsUrl(heroNews)"
               class="block p-5 sm:p-6 space-y-3.5 hover:bg-slate-50/70 transition cursor-pointer group">
                <div class="relative aspect-[16/9] w-full rounded-xl overflow-hidden bg-slate-100 border border-slate-200/80">
                    <template x-if="heroNews.thumbnail">
                        <img :src="heroNews.thumbnail"
                             :alt="heroNews.title"
                             class="w-full h-full object-cover group-hover:scale-[1.02] transition duration-300">
                    </template>
                    <template x-if="!heroNews.thumbnail">
                        <div class="w-full h-full bg-gradient-to-br from-emerald-900 via-emerald-800 to-slate-900 flex items-center justify-center p-6 text-center">
                            <span class="text-white/90 text-sm font-bold tracking-wide" x-text="heroNews.category"></span>
                        </div>
                    </template>
                    <div class="absolute inset-0 bg-gradient-to-t from-slate-950/75 via-slate-950/15 to-transparent"></div>
                    <div class="absolute top-3 left-3 flex items-center gap-1.5">
                        <span class="px-2.5 py-1 rounded-lg bg-emerald-600 text-white text-[10px] font-extrabold uppercase tracking-wider shadow-xs"
                              x-text="heroNews.category"></span>
                        <span class="px-2 py-1 rounded-lg bg-slate-900/75 backdrop-blur-xs text-amber-300 text-[10px] font-bold">
                            Sorotan Utama
                        </span>
                    </div>
                    <div class="absolute bottom-3 left-3.5 right-3.5 flex items-center justify-between text-[11px] text-white/90 font-medium">
                        <span x-text="heroNews.author + ' (' + heroNews.unit + ')'"></span>
                        <span x-text="heroNews.published_at"></span>
                    </div>
                </div>

                <div class="space-y-1.5">
                    <h3 class="text-base sm:text-lg font-extrabold text-slate-900 group-hover:text-emerald-800 transition leading-snug"
                        x-text="heroNews.title"></h3>
                    <p class="text-xs sm:text-[13px] text-slate-600 leading-relaxed line-clamp-2"
                       x-text="heroNews.excerpt"></p>
                </div>
            </a>
        </template>

        <!-- VARIASI 2: LIST + THUMBNAIL SAMPING -->
        <div x-show="thumbnailNewsList.length > 0" class="divide-y divide-slate-100">
            <template x-for="item in thumbnailNewsList" :key="item.id">
                <a :href="newsUrl(item)"
                   class="px-5 sm:px-6 py-4 flex items-start gap-4 hover:bg-slate-50/80 transition cursor-pointer group">
                    <div x-show="item.thumbnail"
                         class="w-24 h-20 sm:w-28 sm:h-20 rounded-xl overflow-hidden bg-slate-100 border border-slate-200/80 shrink-0">
                        <img :src="item.thumbnail"
                             :alt="item.title"
                             class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                    </div>

                    <div class="min-w-0 flex-1 space-y-1">
                        <div class="flex items-center gap-2 text-[11px]">
                            <span class="font-bold text-emerald-700" x-text="item.category"></span>
                            <span class="text-slate-300">&bull;</span>
                            <span class="text-slate-400" x-text="item.published_at"></span>
                        </div>

                        <h4 class="text-sm font-bold text-slate-900 group-hover:text-emerald-800 transition leading-snug line-clamp-2"
                            x-text="item.title"></h4>

                        <div class="text-[11px] text-slate-500 truncate"
                             x-text="'Oleh ' + item.author + ' • Kavling ' + item.unit"></div>
                    </div>
                </a>
            </template>
        </div>

        <!-- VARIASI 3: GRID 2 KOLOM KARTU FOTO RINGKAS -->
        <div x-show="gridNewsList.length > 0" class="p-5 sm:p-6 bg-slate-50/50">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <template x-for="gItem in gridNewsList" :key="gItem.id">
                    <a :href="newsUrl(gItem)"
                       class="bg-white rounded-xl border border-slate-200/90 overflow-hidden shadow-2xs hover:border-emerald-300 transition cursor-pointer group flex flex-col">
                        <div class="aspect-[16/10] w-full bg-slate-100 overflow-hidden relative">
                            <template x-if="gItem.thumbnail">
                                <img :src="gItem.thumbnail"
                                     :alt="gItem.title"
                                     class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                            </template>
                            <template x-if="!gItem.thumbnail">
                                <div class="w-full h-full bg-gradient-to-br from-emerald-800 to-slate-800 flex items-center justify-center p-4 text-center">
                                    <span class="text-white/90 text-xs font-bold" x-text="gItem.category"></span>
                                </div>
                            </template>
                            <span class="absolute top-2.5 left-2.5 px-2 py-0.5 rounded-md bg-white/95 text-slate-800 text-[10px] font-bold shadow-2xs"
                                  x-text="gItem.category"></span>
                        </div>
                        <div class="p-3.5 flex-1 flex flex-col justify-between space-y-2">
                            <h4 class="text-xs sm:text-[13px] font-bold text-slate-900 group-hover:text-emerald-800 leading-snug line-clamp-2"
                                x-text="gItem.title"></h4>
                            <div class="flex items-center justify-between text-[10px] text-slate-500 pt-1 border-t border-slate-100">
                                <span class="truncate" x-text="gItem.author + ' (' + gItem.unit + ')'"></span>
                                <span class="shrink-0" x-text="gItem.published_at"></span>
                            </div>
                        </div>
                    </a>
                </template>
            </div>
        </div>

        <!-- VARIASI 4: LIST HANYA TEKS (KILAS KABAR WARGA) -->
        <div x-show="textOnlyNewsList.length > 0" class="px-5 sm:px-6 py-4 space-y-2.5">
            <div class="text-[11px] font-extrabold uppercase tracking-wider text-slate-400">
                Kilas Kabar Lingkungan
            </div>
            <div class="divide-y divide-slate-100">
                <template x-for="tItem in textOnlyNewsList" :key="tItem.id">
                    <a :href="newsUrl(tItem)"
                       class="py-2.5 first:pt-1 last:pb-1 flex items-start justify-between gap-3 cursor-pointer group">
                        <div class="min-w-0 flex-1 space-y-0.5">
                            <h4 class="text-xs sm:text-[13px] font-bold text-slate-800 group-hover:text-emerald-800 transition leading-snug"
                                x-text="tItem.title"></h4>
                            <div class="flex flex-wrap items-center gap-1.5 text-[11px] text-slate-500">
                                <span class="font-semibold text-emerald-700" x-text="tItem.category"></span>
                                <span>&bull;</span>
                                <span x-text="tItem.author + ' (' + tItem.unit + ')'"></span>
                                <span>&bull;</span>
                                <span x-text="tItem.published_at"></span>
                            </div>
                        </div>
                        <span class="text-slate-400 group-hover:text-emerald-700 group-hover:translate-x-0.5 transition text-base font-bold shrink-0">&rsaquo;</span>
                    </a>
                </template>
            </div>
        </div>

    </div>
</section>
<div x-show="activeTab === 'publikasi'" class="space-y-6">

    <!-- TABEL 1A: PAPAN PENGUMUMAN PAGUYUBAN -->
    <section class="bg-white rounded-2xl shadow-sm border border-slate-200">
        <div class="p-4 sm:px-6 border-b border-slate-200 bg-slate-50/70 rounded-t-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-base sm:text-lg font-bold text-slate-900">Papan Pengumuman Paguyuban</h2>
                    <span class="px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-700 border border-slate-200 text-xs font-bold"
                          x-text="announcements.length + ' Total'"></span>
                </div>
                <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Pengumuman aktif otomatis tampil di Beranda Warga.</p>
            </div>

            <button type="button"
                    @click="openCreateAnnouncementModal()"
                    class="inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white text-xs sm:text-sm font-bold shadow-xs transition shrink-0 cursor-pointer">
                <span>+ Tambahkan Pengumuman</span>
            </button>
        </div>

        <div x-show="announcements.length === 0"
             class="p-10 text-center text-sm text-slate-500">
            Belum ada pengumuman yang diterbitkan. Klik tombol <strong>+ Tambahkan Pengumuman</strong> di atas untuk membuat pengumuman pertama.
        </div>

        <!-- Tabel Desktop -->
        <div x-show="announcements.length > 0" class="hidden md:block">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-xs font-bold uppercase tracking-wider text-slate-500">
                        <th class="py-3 px-5">Judul Pengumuman</th>
                        <th class="py-3 px-4 w-40">Kategori</th>
                        <th class="py-3 px-4 w-28">Status</th>
                        <th class="py-3 px-4 w-36">Tanggal</th>
                        <th class="py-3 px-5 w-16 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <template x-for="item in announcements" :key="'dt-ann-' + item.id">
                        <tr :class="item.is_published ? 'hover:bg-slate-50/80' : 'bg-slate-50/80 hover:bg-slate-100/70'" class="align-middle">
                            <td class="py-3 px-5">
                                <div class="flex items-center gap-2">
                                    <span x-show="item.is_pinned"
                                          class="px-2 py-0.5 rounded text-xs font-bold bg-amber-100 text-amber-900 border border-amber-300 shrink-0">
                                        &#128204; Pin
                                    </span>
                                    <span :class="item.is_published ? 'text-slate-900' : 'text-slate-500'"
                                          class="font-bold"
                                          x-text="item.title"></span>
                                </div>
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap">
                                <span :class="item.badgeClass"
                                      class="px-2.5 py-0.5 rounded-full text-xs font-bold border"
                                      x-text="item.category"></span>
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap">
                                <span :class="item.is_published ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-200 text-slate-700 border-slate-300'"
                                      class="px-2.5 py-0.5 rounded-full text-xs font-bold border"
                                      x-text="item.is_published ? 'Tayang' : 'Arsip'"></span>
                            </td>
                            <td :class="item.is_published ? 'text-slate-600' : 'text-slate-400'"
                                class="py-3 px-4 text-xs font-medium whitespace-nowrap"
                                x-text="item.date"></td>
                            <td class="py-3 px-5 text-right whitespace-nowrap">
                                <div :class="openMenuKey === 'dt-ann-' + item.id ? 'z-40' : ''"
                                     class="relative inline-block text-left"
                                     @click.away="closeMenu('dt-ann-' + item.id)">
                                    <button type="button"
                                            @click.stop="toggleMenu('dt-ann-' + item.id)"
                                            aria-label="Menu Aksi Pengumuman"
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-slate-500 hover:text-slate-900 hover:bg-slate-100 transition cursor-pointer">
                                        <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path d="M10 6a1.5 1.5 0 110-3 1.5 1.5 0 010 3zm0 5.5a1.5 1.5 0 110-3 1.5 1.5 0 010 3zm0 5.5a1.5 1.5 0 110-3 1.5 1.5 0 010 3z" />
                                        </svg>
                                    </button>

                                    <div x-show="openMenuKey === 'dt-ann-' + item.id"
                                         x-cloak
                                         class="absolute right-0 top-full mt-1 w-40 rounded-xl bg-white shadow-xl border border-slate-200 py-1.5 z-50 text-xs text-left">
                                        <button type="button"
                                                @click="openMenuKey = null; toggleAnnouncementPin(item)"
                                                class="w-full px-3.5 py-2 text-left font-semibold text-slate-700 hover:bg-amber-50 hover:text-amber-900 flex items-center gap-2 cursor-pointer">
                                            <span x-text="item.is_pinned ? 'Lepas Pin' : 'Sematkan (Pin)'"></span>
                                        </button>
                                        <button type="button"
                                                @click="openMenuKey = null; openEditAnnouncement(item)"
                                                class="w-full px-3.5 py-2 text-left font-semibold text-slate-700 hover:bg-slate-50 flex items-center gap-2 cursor-pointer">
                                            <span>Edit</span>
                                        </button>
                                        <button type="button"
                                                @click="openMenuKey = null; toggleAnnouncementPublish(item)"
                                                class="w-full px-3.5 py-2 text-left font-semibold text-indigo-700 hover:bg-indigo-50 flex items-center gap-2 cursor-pointer">
                                            <span x-text="item.is_published ? 'Arsipkan' : 'Tayangkan'"></span>
                                        </button>
                                        <div class="my-1 border-t border-slate-100"></div>
                                        <button type="button"
                                                @click="openMenuKey = null; deleteAnnouncement(item)"
                                                class="w-full px-3.5 py-2 text-left font-semibold text-rose-600 hover:bg-rose-50 flex items-center gap-2 cursor-pointer">
                                            <span>Hapus</span>
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Daftar Mobile (< md) -->
        <div x-show="announcements.length > 0" class="md:hidden divide-y divide-slate-100">
            <template x-for="item in announcements" :key="'mb-ann-' + item.id">
                <div :class="item.is_published ? 'bg-white' : 'bg-slate-50/90'" class="p-4 space-y-2 text-sm">
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex flex-wrap items-center gap-1.5 pt-0.5">
                            <span x-show="item.is_pinned"
                                  class="px-2 py-0.5 rounded text-xs font-bold bg-amber-100 text-amber-900 border border-amber-300">
                                &#128204; Pin
                            </span>
                            <span :class="item.badgeClass"
                                  class="px-2 py-0.5 rounded-full text-xs font-bold border"
                                  x-text="item.category"></span>
                            <span :class="item.is_published ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-200 text-slate-700 border-slate-300'"
                                  class="px-2 py-0.5 rounded-full text-xs font-bold border"
                                  x-text="item.is_published ? 'Tayang' : 'Arsip'"></span>
                        </div>

                        <div :class="openMenuKey === 'mb-ann-' + item.id ? 'z-40' : ''"
                             class="relative shrink-0 -mr-1 -mt-1"
                             @click.away="closeMenu('mb-ann-' + item.id)">
                            <button type="button"
                                    @click.stop="toggleMenu('mb-ann-' + item.id)"
                                    aria-label="Menu Aksi Pengumuman"
                                    class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-slate-500 hover:text-slate-900 hover:bg-slate-100 transition cursor-pointer">
                                <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path d="M10 6a1.5 1.5 0 110-3 1.5 1.5 0 010 3zm0 5.5a1.5 1.5 0 110-3 1.5 1.5 0 010 3zm0 5.5a1.5 1.5 0 110-3 1.5 1.5 0 010 3z" />
                                </svg>
                            </button>

                            <div x-show="openMenuKey === 'mb-ann-' + item.id"
                                 x-cloak
                                 class="absolute right-0 top-full mt-1 w-40 rounded-xl bg-white shadow-xl border border-slate-200 py-1.5 z-50 text-xs text-left">
                                <button type="button"
                                        @click="openMenuKey = null; toggleAnnouncementPin(item)"
                                        class="w-full px-3.5 py-2 text-left font-semibold text-slate-700 hover:bg-amber-50 hover:text-amber-900 cursor-pointer"
                                        x-text="item.is_pinned ? 'Lepas Pin' : 'Sematkan (Pin)'"></button>
                                <button type="button"
                                        @click="openMenuKey = null; openEditAnnouncement(item)"
                                        class="w-full px-3.5 py-2 text-left font-semibold text-slate-700 hover:bg-slate-50 cursor-pointer">Edit</button>
                                <button type="button"
                                        @click="openMenuKey = null; toggleAnnouncementPublish(item)"
                                        class="w-full px-3.5 py-2 text-left font-semibold text-indigo-700 hover:bg-indigo-50 cursor-pointer"
                                        x-text="item.is_published ? 'Arsipkan' : 'Tayangkan'"></button>
                                <div class="my-1 border-t border-slate-100"></div>
                                <button type="button"
                                        @click="openMenuKey = null; deleteAnnouncement(item)"
                                        class="w-full px-3.5 py-2 text-left font-semibold text-rose-600 hover:bg-rose-50 cursor-pointer">Hapus</button>
                            </div>
                        </div>
                    </div>

                    <div :class="item.is_published ? 'text-slate-900' : 'text-slate-500'"
                         class="font-bold leading-snug"
                         x-text="item.title"></div>
                    <div class="text-xs text-slate-400" x-text="item.date"></div>
                </div>
            </template>
        </div>
    </section>

    <!-- TABEL 1B: KABAR & BERITA WARGA -->
    <section class="bg-white rounded-2xl shadow-sm border border-slate-200">
        <div class="p-4 sm:px-6 border-b border-slate-200 bg-slate-50/70 rounded-t-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-base sm:text-lg font-bold text-slate-900">Kabar &amp; Berita Warga</h2>
                    <span class="px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-700 border border-slate-200 text-xs font-bold"
                          x-text="newsArticles.length + ' Berita'"></span>
                </div>
                <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Atur tata letak berita di Beranda Warga atau sunting isi berita.</p>
            </div>

            <a href="/dashboard"
               class="inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white text-xs sm:text-sm font-bold shadow-xs transition shrink-0">
                <span>+ Tambahkan Kabar Warga</span>
            </a>
        </div>

        <div x-show="newsArticles.length === 0"
             class="p-10 text-center text-sm text-slate-500">
            Belum ada berita warga yang diterbitkan. Klik tombol <strong>+ Tambahkan Kabar Warga</strong> untuk membuat berita baru.
        </div>

        <!-- Tabel Desktop -->
        <div x-show="newsArticles.length > 0" class="hidden md:block">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-xs font-bold uppercase tracking-wider text-slate-500">
                        <th class="py-3 px-5">Judul Berita</th>
                        <th class="py-3 px-4 w-44">Rubrik</th>
                        <th class="py-3 px-4 w-36">Tata Letak</th>
                        <th class="py-3 px-4 w-36">Tanggal Terbit</th>
                        <th class="py-3 px-5 w-16 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <template x-for="art in newsArticles" :key="'dt-news-' + art.id">
                        <tr class="hover:bg-slate-50/80 align-middle">
                            <td class="py-3 px-5">
                                <div class="flex items-center gap-3">
                                    <template x-if="art.thumbnail">
                                        <img :src="art.thumbnail" :alt="art.title"
                                             class="w-12 h-9 rounded-lg object-cover border border-slate-200 shrink-0 bg-slate-100">
                                    </template>
                                    <div class="font-bold text-slate-900 line-clamp-2" x-text="art.title"></div>
                                </div>
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap">
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200"
                                      x-text="art.category"></span>
                            </td>
                            <td class="py-3 px-4 whitespace-nowrap">
                                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200"
                                      x-text="formatNewsLayout(art.layout)"></span>
                            </td>
                            <td class="py-3 px-4 text-xs font-medium text-slate-600 whitespace-nowrap" x-text="art.published_at"></td>
                            <td class="py-3 px-5 text-right whitespace-nowrap">
                                <div :class="openMenuKey === 'dt-news-' + art.id ? 'z-40' : ''"
                                     class="relative inline-block text-left"
                                     @click.away="closeMenu('dt-news-' + art.id)">
                                    <button type="button"
                                            @click.stop="toggleMenu('dt-news-' + art.id)"
                                            aria-label="Menu Aksi Berita"
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-slate-500 hover:text-slate-900 hover:bg-slate-100 transition cursor-pointer">
                                        <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path d="M10 6a1.5 1.5 0 110-3 1.5 1.5 0 010 3zm0 5.5a1.5 1.5 0 110-3 1.5 1.5 0 010 3zm0 5.5a1.5 1.5 0 110-3 1.5 1.5 0 010 3z" />
                                        </svg>
                                    </button>

                                    <div x-show="openMenuKey === 'dt-news-' + art.id"
                                         x-cloak
                                         class="absolute right-0 top-full mt-1 w-40 rounded-xl bg-white shadow-xl border border-slate-200 py-1.5 z-50 text-xs text-left">
                                        <a :href="'/berita/' + art.date_path + '/' + art.slug"
                                           target="_blank"
                                           @click="openMenuKey = null"
                                           class="block w-full px-3.5 py-2 text-left font-semibold text-emerald-700 hover:bg-emerald-50">
                                            Lihat Berita ↗
                                        </a>
                                        <button type="button"
                                                @click="openMenuKey = null; openNewsEditModal(art)"
                                                class="w-full px-3.5 py-2 text-left font-semibold text-slate-700 hover:bg-slate-50 cursor-pointer">
                                            Edit
                                        </button>
                                        <div class="my-1 border-t border-slate-100"></div>
                                        <button type="button"
                                                @click="openMenuKey = null; deleteNewsArticle(art)"
                                                class="w-full px-3.5 py-2 text-left font-semibold text-rose-600 hover:bg-rose-50 cursor-pointer">
                                            Hapus
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Daftar Mobile (< md) -->
        <div x-show="newsArticles.length > 0" class="md:hidden divide-y divide-slate-100">
            <template x-for="art in newsArticles" :key="'mb-news-' + art.id">
                <div class="p-4 space-y-2 text-sm">
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex flex-wrap items-center gap-1.5 pt-0.5">
                            <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200"
                                  x-text="art.category"></span>
                            <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200"
                                  x-text="formatNewsLayout(art.layout)"></span>
                        </div>

                        <div :class="openMenuKey === 'mb-news-' + art.id ? 'z-40' : ''"
                             class="relative shrink-0 -mr-1 -mt-1"
                             @click.away="closeMenu('mb-news-' + art.id)">
                            <button type="button"
                                    @click.stop="toggleMenu('mb-news-' + art.id)"
                                    aria-label="Menu Aksi Berita"
                                    class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-slate-500 hover:text-slate-900 hover:bg-slate-100 transition cursor-pointer">
                                <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path d="M10 6a1.5 1.5 0 110-3 1.5 1.5 0 010 3zm0 5.5a1.5 1.5 0 110-3 1.5 1.5 0 010 3zm0 5.5a1.5 1.5 0 110-3 1.5 1.5 0 010 3z" />
                                </svg>
                            </button>

                            <div x-show="openMenuKey === 'mb-news-' + art.id"
                                 x-cloak
                                 class="absolute right-0 top-full mt-1 w-40 rounded-xl bg-white shadow-xl border border-slate-200 py-1.5 z-50 text-xs text-left">
                                <a :href="'/berita/' + art.date_path + '/' + art.slug"
                                   target="_blank"
                                   @click="openMenuKey = null"
                                   class="block w-full px-3.5 py-2 text-left font-semibold text-emerald-700 hover:bg-emerald-50">
                                    Lihat Berita ↗
                                </a>
                                <button type="button"
                                        @click="openMenuKey = null; openNewsEditModal(art)"
                                        class="w-full px-3.5 py-2 text-left font-semibold text-slate-700 hover:bg-slate-50 cursor-pointer">
                                    Edit
                                </button>
                                <div class="my-1 border-t border-slate-100"></div>
                                <button type="button"
                                        @click="openMenuKey = null; deleteNewsArticle(art)"
                                        class="w-full px-3.5 py-2 text-left font-semibold text-rose-600 hover:bg-rose-50 cursor-pointer">
                                    Hapus
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <template x-if="art.thumbnail">
                            <img :src="art.thumbnail" :alt="art.title"
                                 class="w-14 h-11 rounded-lg object-cover border border-slate-200 shrink-0 bg-slate-100">
                        </template>
                        <div class="font-bold text-slate-900 leading-snug" x-text="art.title"></div>
                    </div>

                    <div class="text-xs text-slate-500" x-text="art.published_at"></div>
                </div>
            </template>
        </div>
    </section>
</div>
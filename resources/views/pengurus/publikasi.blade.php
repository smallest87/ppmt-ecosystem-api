@extends('layouts.app')

@section('title', 'Publikasi, Agenda & Notulensi — Panel Pengurus PPMT')
@section('subpage_title', 'Publikasi, Agenda & Notulensi')
@section('subpage_back_url', '/pengurus')

@section('content')
<div x-data="ppmtEditorialPanel()"
     x-init="init()"
     x-effect="document.body.classList.toggle('overflow-hidden', Boolean(showCreateAnnouncement || showCreateAgenda || showCreateMinute || editingAnnouncement || editingNews || editingAgenda || editingMinute))"
     @keydown.escape.window="closeAllModals(); openMenuKey = null"
     class="max-w-6xl mx-auto px-4 py-6 sm:py-8 space-y-6">

    <!-- Loading State -->
    <div x-show="loading" class="bg-white rounded-2xl p-12 text-center text-sm text-slate-500 border border-slate-200">
        Memuat papan pengumuman, kabar warga, agenda &amp; arsip notulensi...
    </div>

    <div x-show="!loading && user" x-cloak class="space-y-6">

        <!-- Notifikasi Global -->
        <div x-show="toastMessage"
             x-cloak
             :class="toastType === 'error' ? 'bg-rose-50 text-rose-800 border-rose-200' : 'bg-emerald-50 text-emerald-800 border-emerald-200'"
             class="p-4 rounded-xl border text-sm font-medium flex justify-between items-center shadow-sm">
            <span x-text="toastMessage"></span>
            <button type="button" @click="toastMessage = ''" class="text-sm opacity-70 hover:opacity-100 cursor-pointer">✕</button>
        </div>

        <!-- Navigasi Menu Tab -->
        <nav aria-label="Menu Publikasi, Agenda & Notulensi"
             class="flex flex-wrap items-center gap-2 border-b border-slate-200 pb-3">
            <button type="button"
                    @click="switchTab('publikasi')"
                    :class="activeTab === 'publikasi'
                        ? 'bg-slate-900 text-white shadow-xs'
                        : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-50'"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition cursor-pointer">
                <span>Publikasi (Pengumuman &amp; Berita)</span>
                <span :class="activeTab === 'publikasi' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600'"
                      class="px-2 py-0.5 rounded-full text-xs font-extrabold"
                      x-text="announcements.length + newsArticles.length"></span>
            </button>

            <button type="button"
                    @click="switchTab('agenda')"
                    :class="activeTab === 'agenda'
                        ? 'bg-slate-900 text-white shadow-xs'
                        : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-50'"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition cursor-pointer">
                <span>Agenda &amp; Jadwal</span>
                <span :class="activeTab === 'agenda' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600'"
                      class="px-2 py-0.5 rounded-full text-xs font-extrabold"
                      x-text="agendas.length"></span>
            </button>

            <button type="button"
                    @click="switchTab('notulensi')"
                    :class="activeTab === 'notulensi'
                        ? 'bg-slate-900 text-white shadow-xs'
                        : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-50'"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition cursor-pointer">
                <span>Arsip Notulensi &amp; Tatib</span>
                <span :class="activeTab === 'notulensi' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600'"
                      class="px-2 py-0.5 rounded-full text-xs font-extrabold"
                      x-text="meetingMinutes.length"></span>
            </button>
        </nav>

        <!-- ================================================================= -->
        <!-- TAB 1: PUBLIKASI (PAPAN PENGUMUMAN & KABAR BERITA WARGA)          -->
        <!-- ================================================================= -->
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

                                <!-- Tombol Titik Tiga Pojok Kanan Atas -->
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

                                <!-- Tombol Titik Tiga Pojok Kanan Atas -->
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

        <!-- ================================================================= -->
        <!-- TAB 2: AGENDA & JADWAL LINGKUNGAN                                 -->
        <!-- ================================================================= -->
        <section x-show="activeTab === 'agenda'" class="bg-white rounded-2xl shadow-sm border border-slate-200">
            <div class="p-4 sm:px-6 border-b border-slate-200 bg-slate-50/70 rounded-t-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-base sm:text-lg font-bold text-slate-900">Daftar Agenda &amp; Jadwal Lingkungan</h2>
                        <span class="px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-700 border border-slate-200 text-xs font-bold"
                              x-text="agendas.length + ' Jadwal'"></span>
                    </div>
                    <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Jadwal yang masih akan datang otomatis tampil di kartu Agenda Warga pada Beranda.</p>
                </div>

                <button type="button"
                        @click="openCreateAgendaModal()"
                        class="inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white text-xs sm:text-sm font-bold shadow-xs transition shrink-0 cursor-pointer">
                    <span>+ Tambahkan Agenda</span>
                </button>
            </div>

            <div x-show="agendas.length === 0"
                 class="p-10 text-center text-sm text-slate-500">
                Belum ada agenda atau jadwal lingkungan yang dibuat. Klik tombol <strong>+ Tambahkan Agenda</strong> di atas untuk membuat jadwal baru.
            </div>

            <!-- Tabel Desktop -->
            <div x-show="agendas.length > 0" class="hidden md:block">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-xs font-bold uppercase tracking-wider text-slate-500">
                            <th class="py-3 px-5 w-44">Tanggal &amp; Waktu</th>
                            <th class="py-3 px-4">Nama Kegiatan &amp; Lokasi</th>
                            <th class="py-3 px-4 w-40">Kategori</th>
                            <th class="py-3 px-4 w-36">Status</th>
                            <th class="py-3 px-5 w-16 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="ag in agendas" :key="'dt-ag-' + ag.id">
                            <tr :class="ag.is_upcoming ? 'hover:bg-slate-50/80' : 'bg-slate-50/80 hover:bg-slate-100/70'" class="align-middle">
                                <td class="py-3.5 px-5 whitespace-nowrap">
                                    <div :class="ag.is_upcoming ? 'text-slate-900' : 'text-slate-500'"
                                         class="font-bold"
                                         x-text="ag.event_date_formatted"></div>
                                    <div class="text-xs text-slate-400 mt-0.5" x-text="ag.time"></div>
                                </td>
                                <td class="py-3.5 px-4 space-y-1">
                                    <div :class="ag.is_upcoming ? 'text-slate-900' : 'text-slate-500'"
                                         class="font-bold"
                                         x-text="ag.title"></div>
                                    <div class="text-xs text-slate-400" x-text="'Lokasi: ' + ag.location"></div>
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <span :class="ag.badgeClass"
                                          class="px-2.5 py-0.5 rounded-full text-xs font-bold border"
                                          x-text="ag.category"></span>
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <span :class="ag.is_upcoming ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-200 text-slate-600 border-slate-300'"
                                          class="px-2.5 py-0.5 rounded-full text-xs font-bold border"
                                          x-text="ag.is_upcoming ? 'Akan Datang' : 'Selesai'"></span>
                                </td>
                                <td class="py-3.5 px-5 text-right whitespace-nowrap">
                                    <div :class="openMenuKey === 'dt-ag-' + ag.id ? 'z-40' : ''"
                                         class="relative inline-block text-left"
                                         @click.away="closeMenu('dt-ag-' + ag.id)">
                                        <button type="button"
                                                @click.stop="toggleMenu('dt-ag-' + ag.id)"
                                                aria-label="Menu Aksi Agenda"
                                                class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-slate-500 hover:text-slate-900 hover:bg-slate-100 transition cursor-pointer">
                                            <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path d="M10 6a1.5 1.5 0 110-3 1.5 1.5 0 010 3zm0 5.5a1.5 1.5 0 110-3 1.5 1.5 0 010 3zm0 5.5a1.5 1.5 0 110-3 1.5 1.5 0 010 3z" />
                                            </svg>
                                        </button>

                                        <div x-show="openMenuKey === 'dt-ag-' + ag.id"
                                             x-cloak
                                             class="absolute right-0 top-full mt-1 w-36 rounded-xl bg-white shadow-xl border border-slate-200 py-1.5 z-50 text-xs text-left">
                                            <button type="button"
                                                    @click="openMenuKey = null; openEditAgenda(ag)"
                                                    class="w-full px-3.5 py-2 text-left font-semibold text-slate-700 hover:bg-slate-50 cursor-pointer">
                                                Edit
                                            </button>
                                            <div class="my-1 border-t border-slate-100"></div>
                                            <button type="button"
                                                    @click="openMenuKey = null; deleteAgenda(ag)"
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
            <div x-show="agendas.length > 0" class="md:hidden divide-y divide-slate-100">
                <template x-for="ag in agendas" :key="'mb-ag-' + ag.id">
                    <div :class="ag.is_upcoming ? 'bg-white' : 'bg-slate-50/90'" class="p-4 space-y-2 text-sm">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex flex-wrap items-center gap-1.5 pt-0.5">
                                <span :class="ag.badgeClass"
                                      class="px-2.5 py-0.5 rounded-full text-xs font-bold border"
                                      x-text="ag.category"></span>
                                <span :class="ag.is_upcoming ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-200 text-slate-600 border-slate-300'"
                                      class="px-2.5 py-0.5 rounded-full text-xs font-bold border"
                                      x-text="ag.is_upcoming ? 'Akan Datang' : 'Selesai'"></span>
                            </div>

                            <!-- Tombol Titik Tiga Pojok Kanan Atas -->
                            <div :class="openMenuKey === 'mb-ag-' + ag.id ? 'z-40' : ''"
                                 class="relative shrink-0 -mr-1 -mt-1"
                                 @click.away="closeMenu('mb-ag-' + ag.id)">
                                <button type="button"
                                        @click.stop="toggleMenu('mb-ag-' + ag.id)"
                                        aria-label="Menu Aksi Agenda"
                                        class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-slate-500 hover:text-slate-900 hover:bg-slate-100 transition cursor-pointer">
                                    <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path d="M10 6a1.5 1.5 0 110-3 1.5 1.5 0 010 3zm0 5.5a1.5 1.5 0 110-3 1.5 1.5 0 010 3zm0 5.5a1.5 1.5 0 110-3 1.5 1.5 0 010 3z" />
                                    </svg>
                                </button>

                                <div x-show="openMenuKey === 'mb-ag-' + ag.id"
                                     x-cloak
                                     class="absolute right-0 top-full mt-1 w-36 rounded-xl bg-white shadow-xl border border-slate-200 py-1.5 z-50 text-xs text-left">
                                    <button type="button"
                                            @click="openMenuKey = null; openEditAgenda(ag)"
                                            class="w-full px-3.5 py-2 text-left font-semibold text-slate-700 hover:bg-slate-50 cursor-pointer">
                                        Edit
                                    </button>
                                    <div class="my-1 border-t border-slate-100"></div>
                                    <button type="button"
                                            @click="openMenuKey = null; deleteAgenda(ag)"
                                            class="w-full px-3.5 py-2 text-left font-semibold text-rose-600 hover:bg-rose-50 cursor-pointer">
                                        Hapus
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div :class="ag.is_upcoming ? 'text-slate-900' : 'text-slate-500'"
                             class="font-bold leading-snug"
                             x-text="ag.title"></div>
                        <div class="text-xs text-slate-400" x-text="ag.event_date_formatted + ' • ' + ag.time + ' • ' + ag.location"></div>
                    </div>
                </template>
            </div>
        </section>

        <!-- ================================================================= -->
        <!-- TAB 3: ARSIP NOTULENSI RAPAT & TATA TERTIB                        -->
        <!-- ================================================================= -->
        <section x-show="activeTab === 'notulensi'" class="bg-white rounded-2xl shadow-sm border border-slate-200">
            <div class="p-4 sm:px-6 border-b border-slate-200 bg-slate-50/70 rounded-t-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-base sm:text-lg font-bold text-slate-900">Arsip Notulensi Rapat &amp; Tata Tertib</h2>
                        <span class="px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-700 border border-slate-200 text-xs font-bold"
                              x-text="meetingMinutes.length + ' Dokumen'"></span>
                    </div>
                    <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Rekam jejak keputusan resmi musyawarah warga dan aturan lingkungan PPMT.</p>
                </div>

                <button type="button"
                        @click="openCreateMinuteModal()"
                        class="inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs sm:text-sm font-bold shadow-xs transition shrink-0 cursor-pointer">
                    <span>+ Tambahkan Notulensi</span>
                </button>
            </div>

            <div x-show="meetingMinutes.length === 0"
                 class="p-10 text-center text-sm text-slate-500">
                Belum ada catatan notulensi rapat atau tata tertib yang disimpan. Klik tombol <strong>+ Tambahkan Notulensi</strong> di atas untuk mencatat dokumen pertama.
            </div>

            <!-- Tabel Desktop -->
            <div x-show="meetingMinutes.length > 0" class="hidden md:block">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-xs font-bold uppercase tracking-wider text-slate-500">
                            <th class="py-3 px-5">Judul Dokumen &amp; Ringkasan Keputusan</th>
                            <th class="py-3 px-4 w-44">Kategori</th>
                            <th class="py-3 px-4 w-36">Visibilitas</th>
                            <th class="py-3 px-4 w-36">Tanggal</th>
                            <th class="py-3 px-5 w-16 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="m in meetingMinutes" :key="'dt-min-' + m.id">
                            <tr class="hover:bg-slate-50/80 align-middle">
                                <td class="py-3.5 px-5 space-y-1">
                                    <div class="font-bold text-slate-900" x-text="m.title"></div>
                                    <p class="text-xs text-slate-500 line-clamp-1" x-text="m.excerpt"></p>
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <span :class="m.badgeClass"
                                          class="px-2.5 py-0.5 rounded-full text-xs font-bold border"
                                          x-text="m.category"></span>
                                </td>
                                <td class="py-3.5 px-4 whitespace-nowrap">
                                    <span :class="m.visibility === 'public_residents' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-700 border-slate-300'"
                                          class="px-2.5 py-0.5 rounded-full text-xs font-bold border"
                                          x-text="m.visibility === 'public_residents' ? 'Terbuka Warga' : 'Internal'"></span>
                                </td>
                                <td class="py-3.5 px-4 text-xs font-medium text-slate-600 whitespace-nowrap" x-text="m.meeting_date_formatted"></td>
                                <td class="py-3.5 px-5 text-right whitespace-nowrap">
                                    <div :class="openMenuKey === 'dt-min-' + m.id ? 'z-40' : ''"
                                         class="relative inline-block text-left"
                                         @click.away="closeMenu('dt-min-' + m.id)">
                                        <button type="button"
                                                @click.stop="toggleMenu('dt-min-' + m.id)"
                                                aria-label="Menu Aksi Notulensi"
                                                class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-slate-500 hover:text-slate-900 hover:bg-slate-100 transition cursor-pointer">
                                            <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                <path d="M10 6a1.5 1.5 0 110-3 1.5 1.5 0 010 3zm0 5.5a1.5 1.5 0 110-3 1.5 1.5 0 010 3zm0 5.5a1.5 1.5 0 110-3 1.5 1.5 0 010 3z" />
                                            </svg>
                                        </button>

                                        <div x-show="openMenuKey === 'dt-min-' + m.id"
                                             x-cloak
                                             class="absolute right-0 top-full mt-1 w-36 rounded-xl bg-white shadow-xl border border-slate-200 py-1.5 z-50 text-xs text-left">
                                            <button type="button"
                                                    @click="openMenuKey = null; openEditMinute(m)"
                                                    class="w-full px-3.5 py-2 text-left font-semibold text-slate-700 hover:bg-slate-50 cursor-pointer">
                                                Edit
                                            </button>
                                            <div class="my-1 border-t border-slate-100"></div>
                                            <button type="button"
                                                    @click="openMenuKey = null; deleteMinute(m)"
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
            <div x-show="meetingMinutes.length > 0" class="md:hidden divide-y divide-slate-100">
                <template x-for="m in meetingMinutes" :key="'mb-min-' + m.id">
                    <div class="p-4 space-y-2 text-sm">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex flex-wrap items-center gap-1.5 pt-0.5">
                                <span :class="m.badgeClass"
                                      class="px-2.5 py-0.5 rounded-full text-xs font-bold border"
                                      x-text="m.category"></span>
                                <span :class="m.visibility === 'public_residents' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-700 border-slate-300'"
                                      class="px-2.5 py-0.5 rounded-full text-xs font-bold border"
                                      x-text="m.visibility === 'public_residents' ? 'Terbuka Warga' : 'Internal'"></span>
                            </div>

                            <!-- Tombol Titik Tiga Pojok Kanan Atas -->
                            <div :class="openMenuKey === 'mb-min-' + m.id ? 'z-40' : ''"
                                 class="relative shrink-0 -mr-1 -mt-1"
                                 @click.away="closeMenu('mb-min-' + m.id)">
                                <button type="button"
                                        @click.stop="toggleMenu('mb-min-' + m.id)"
                                        aria-label="Menu Aksi Notulensi"
                                        class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-slate-500 hover:text-slate-900 hover:bg-slate-100 transition cursor-pointer">
                                    <svg class="w-4 h-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                        <path d="M10 6a1.5 1.5 0 110-3 1.5 1.5 0 010 3zm0 5.5a1.5 1.5 0 110-3 1.5 1.5 0 010 3zm0 5.5a1.5 1.5 0 110-3 1.5 1.5 0 010 3z" />
                                    </svg>
                                </button>

                                <div x-show="openMenuKey === 'mb-min-' + m.id"
                                     x-cloak
                                     class="absolute right-0 top-full mt-1 w-36 rounded-xl bg-white shadow-xl border border-slate-200 py-1.5 z-50 text-xs text-left">
                                    <button type="button"
                                            @click="openMenuKey = null; openEditMinute(m)"
                                            class="w-full px-3.5 py-2 text-left font-semibold text-slate-700 hover:bg-slate-50 cursor-pointer">
                                        Edit
                                    </button>
                                    <div class="my-1 border-t border-slate-100"></div>
                                    <button type="button"
                                            @click="openMenuKey = null; deleteMinute(m)"
                                            class="w-full px-3.5 py-2 text-left font-semibold text-rose-600 hover:bg-rose-50 cursor-pointer">
                                        Hapus
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div>
                            <div class="font-bold text-slate-900 leading-snug" x-text="m.title"></div>
                            <p class="text-xs text-slate-600 line-clamp-2 mt-1" x-text="m.excerpt"></p>
                        </div>

                        <div class="text-xs text-slate-500" x-text="m.meeting_date_formatted"></div>
                    </div>
                </template>
            </div>
        </section>
    </div>

    <!-- ===================================================================== -->
    <!-- MODAL TAMBAH PENGUMUMAN PAGUYUBAN                                     -->
    <!-- ===================================================================== -->
    <div x-show="showCreateAnnouncement"
         x-cloak
         class="fixed inset-0 z-50 !mt-0 flex items-center justify-center p-3 sm:p-4 bg-slate-900/50 backdrop-blur-[1px] overflow-y-auto">
        <div @click.away="showCreateAnnouncement = false"
             class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-[calc(100vw-1.5rem)] sm:max-w-lg max-h-[90dvh] flex flex-col overflow-hidden my-auto">
            <div class="px-5 sm:px-6 py-4 border-b border-slate-100 flex items-center justify-between gap-3 shrink-0">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Terbitkan Pengumuman Paguyuban</h3>
                    <p class="text-xs text-slate-500">Pengumuman akan langsung tampil di Beranda Warga.</p>
                </div>
                <button type="button" @click="showCreateAnnouncement = false" class="text-slate-400 hover:text-slate-700 text-sm p-1 cursor-pointer">✕</button>
            </div>

            <form @submit.prevent="createAnnouncement()" class="p-5 sm:p-6 space-y-4 overflow-y-auto">
                <div>
                    <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1">Judul Pengumuman *</label>
                    <input type="text"
                           x-model="announcementForm.title"
                           required
                           maxlength="180"
                           placeholder="Contoh: Jadwal Kerja Bakti Drainase Blok A–D"
                           class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                </div>

                <div>
                    <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1">Kategori / Label *</label>
                    <select x-model="announcementForm.category"
                            class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                        <option value="info">Info Lingkungan</option>
                        <option value="penting">Penting / Mendesak</option>
                        <option value="kerja_bakti">Kegiatan Warga / Kerja Bakti</option>
                        <option value="musyawarah">Musyawarah Warga</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1">Isi Pengumuman Lengkap *</label>
                    <textarea x-model="announcementForm.body"
                              required
                              rows="5"
                              placeholder="Tuliskan detail waktu, tempat, dan informasi penting bagi warga PPMT..."
                              class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600"></textarea>
                </div>

                <label class="flex items-center gap-2.5 p-3 rounded-xl bg-amber-50/70 border border-amber-200 text-xs sm:text-sm text-slate-700 cursor-pointer select-none">
                    <input type="checkbox" x-model="announcementForm.is_pinned" class="rounded text-emerald-700 focus:ring-emerald-600">
                    <span>Sematkan (<strong>Pin</strong>) di urutan paling atas papan pengumuman</span>
                </label>

                <div class="pt-2 flex items-center justify-end gap-2 border-t border-slate-100">
                    <button type="button"
                            @click="showCreateAnnouncement = false"
                            class="px-4 py-2 rounded-xl border border-slate-300 text-slate-700 text-xs sm:text-sm font-semibold cursor-pointer">
                        Batal
                    </button>
                    <button type="submit"
                            :disabled="savingAnnouncement"
                            class="px-5 py-2 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white text-xs sm:text-sm font-bold shadow-sm transition disabled:opacity-50 cursor-pointer">
                        <span x-show="!savingAnnouncement">Terbitkan Pengumuman</span>
                        <span x-show="savingAnnouncement">Menerbitkan...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- MODAL TAMBAH AGENDA & JADWAL LINGKUNGAN                               -->
    <!-- ===================================================================== -->
    <div x-show="showCreateAgenda"
         x-cloak
         class="fixed inset-0 z-50 !mt-0 flex items-center justify-center p-3 sm:p-4 bg-slate-900/50 backdrop-blur-[1px] overflow-y-auto">
        <div @click.away="showCreateAgenda = false"
             class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-[calc(100vw-1.5rem)] sm:max-w-lg max-h-[90dvh] flex flex-col overflow-hidden my-auto">
            <div class="px-5 sm:px-6 py-4 border-b border-slate-100 flex items-center justify-between gap-3 shrink-0">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Tambah Agenda &amp; Jadwal Lingkungan</h3>
                    <p class="text-xs text-slate-500">Jadwal yang akan datang tampil di kartu Agenda Warga pada Beranda.</p>
                </div>
                <button type="button" @click="showCreateAgenda = false" class="text-slate-400 hover:text-slate-700 text-sm p-1 cursor-pointer">✕</button>
            </div>

            <form @submit.prevent="createAgenda()" class="p-5 sm:p-6 space-y-4 overflow-y-auto">
                <div>
                    <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1">Nama Kegiatan / Jadwal *</label>
                    <input type="text"
                           x-model="agendaForm.title"
                           required
                           maxlength="180"
                           placeholder="Contoh: Kerja Bakti Drainase & Perapian Taman Fasum"
                           class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1">Tanggal Kegiatan *</label>
                        <input type="date"
                               x-model="agendaForm.event_date"
                               required
                               class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                    </div>
                    <div>
                        <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1">Kategori Jadwal *</label>
                        <select x-model="agendaForm.category"
                                class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                            <option value="kegiatan">Kegiatan Warga</option>
                            <option value="kebersihan">Kebersihan &amp; Sampah</option>
                            <option value="keamanan">Keamanan &amp; Ronda</option>
                            <option value="pemeliharaan">Pemeliharaan Fasum</option>
                            <option value="musyawarah">Musyawarah / Rapat</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1">Waktu / Jam *</label>
                        <input type="text"
                               x-model="agendaForm.time_label"
                               required
                               maxlength="80"
                               placeholder="Contoh: 06.30 – 09.30 WIB"
                               class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                    </div>
                    <div>
                        <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1">Lokasi Kegiatan *</label>
                        <input type="text"
                               x-model="agendaForm.location"
                               required
                               maxlength="140"
                               placeholder="Contoh: Fasum Tengah PPMT"
                               class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                    </div>
                </div>

                <div>
                    <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1">Catatan Tambahan (Opsional)</label>
                    <textarea x-model="agendaForm.notes"
                              rows="2"
                              placeholder="Contoh: Dimohon warga membawa alat kebersihan masing-masing."
                              class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600"></textarea>
                </div>

                <div class="pt-2 flex items-center justify-end gap-2 border-t border-slate-100">
                    <button type="button"
                            @click="showCreateAgenda = false"
                            class="px-4 py-2 rounded-xl border border-slate-300 text-slate-700 text-xs sm:text-sm font-semibold cursor-pointer">
                        Batal
                    </button>
                    <button type="submit"
                            :disabled="savingAgenda"
                            class="px-5 py-2 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white text-xs sm:text-sm font-bold shadow-sm transition disabled:opacity-50 cursor-pointer">
                        <span x-show="!savingAgenda">Simpan Agenda</span>
                        <span x-show="savingAgenda">Menyimpan...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- MODAL TAMBAH NOTULENSI RAPAT & TATA TERTIB                            -->
    <!-- ===================================================================== -->
    <div x-show="showCreateMinute"
         x-cloak
         class="fixed inset-0 z-50 !mt-0 flex items-center justify-center p-3 sm:p-4 bg-slate-900/50 backdrop-blur-[1px] overflow-y-auto">
        <div @click.away="showCreateMinute = false"
             class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-[calc(100vw-1.5rem)] sm:max-w-lg max-h-[90dvh] flex flex-col overflow-hidden my-auto">
            <div class="px-5 sm:px-6 py-4 border-b border-slate-100 flex items-center justify-between gap-3 shrink-0">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Catat Notulensi Rapat &amp; Tata Tertib</h3>
                    <p class="text-xs text-slate-500">Dokumentasikan keputusan musyawarah warga atau aturan lingkungan.</p>
                </div>
                <button type="button" @click="showCreateMinute = false" class="text-slate-400 hover:text-slate-700 text-sm p-1 cursor-pointer">✕</button>
            </div>

            <form @submit.prevent="createMinute()" class="p-5 sm:p-6 space-y-4 overflow-y-auto">
                <div>
                    <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1">Judul Agenda / Dokumen *</label>
                    <input type="text"
                           x-model="minuteForm.title"
                           required
                           maxlength="180"
                           placeholder="Contoh: Kesepakatan Musyawarah Keamanan & Iuran Bulanan"
                           class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1">Tanggal Rapat / Penetapan *</label>
                        <input type="date"
                               x-model="minuteForm.meeting_date"
                               required
                               class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                    </div>
                    <div>
                        <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1">Kategori Dokumen *</label>
                        <select x-model="minuteForm.category"
                                class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                            <option value="musyawarah_warga">Musyawarah Warga</option>
                            <option value="tata_tertib">Tata Tertib Lingkungan</option>
                            <option value="rapat_pengurus">Rapat Pengurus</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1">Lokasi / Forum Pertemuan (Opsional)</label>
                    <input type="text"
                           x-model="minuteForm.location"
                           maxlength="120"
                           placeholder="Contoh: Fasum Tengah / Pos Utama PPMT"
                           class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                </div>

                <div>
                    <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1">Poin Kesepakatan &amp; Isi Lengkap *</label>
                    <textarea x-model="minuteForm.decisions"
                              required
                              rows="5"
                              placeholder="Tuliskan poin-poin keputusan musyawarah, aturan yang disepakati, atau tindak lanjut pengurus..."
                              class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600"></textarea>
                </div>

                <div>
                    <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1">Visibilitas Dokumen *</label>
                    <select x-model="minuteForm.visibility"
                            class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                        <option value="public_residents">Terbuka untuk Semua Warga PPMT (Tampil di Komunitas)</option>
                        <option value="internal_pengurus">Internal Pengurus Saja</option>
                    </select>
                </div>

                <div class="pt-2 flex items-center justify-end gap-2 border-t border-slate-100">
                    <button type="button"
                            @click="showCreateMinute = false"
                            class="px-4 py-2 rounded-xl border border-slate-300 text-slate-700 text-xs sm:text-sm font-semibold cursor-pointer">
                        Batal
                    </button>
                    <button type="submit"
                            :disabled="savingMinute"
                            class="px-5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs sm:text-sm font-bold shadow-sm transition disabled:opacity-50 cursor-pointer">
                        <span x-show="!savingMinute">Simpan ke Arsip Notulensi</span>
                        <span x-show="savingMinute">Menyimpan...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- MODAL EDIT KABAR & BERITA WARGA                                       -->
    <!-- ===================================================================== -->
    <div x-show="editingNews !== null"
         x-cloak
         class="fixed inset-0 z-50 !mt-0 flex items-center justify-center p-3 sm:p-4 bg-slate-900/50 backdrop-blur-[1px] overflow-y-auto">
        <div @click.away="editingNews = null"
             class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-[calc(100vw-1.5rem)] sm:max-w-2xl max-h-[90dvh] flex flex-col overflow-hidden my-auto">
            <div class="px-5 sm:px-6 py-4 border-b border-slate-100 flex items-center justify-between gap-3 shrink-0">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Edit Kabar &amp; Berita Warga</h3>
                    <p class="text-xs text-slate-500"
                       x-text="editingNews ? ('Penulis: ' + editingNews.author + ' • Kavling ' + editingNews.unit) : ''"></p>
                </div>
                <button type="button" @click="editingNews = null" class="text-slate-400 hover:text-slate-700 text-sm p-1 cursor-pointer">✕</button>
            </div>

            <form @submit.prevent="saveEditedNews()" class="p-5 sm:p-6 space-y-4 overflow-y-auto">
                <template x-if="editingNews?.thumbnail">
                    <div class="rounded-xl overflow-hidden border border-slate-200 bg-slate-100 max-h-52">
                        <img :src="editingNews.thumbnail" :alt="editNewsForm.title" class="w-full h-48 object-cover">
                    </div>
                </template>

                <div>
                    <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1">Judul Berita *</label>
                    <input type="text" x-model="editNewsForm.title" required minlength="8" maxlength="180"
                           class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1">Rubrik / Kategori *</label>
                        <select x-model="editNewsForm.category"
                                class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                            <option value="Kegiatan Warga">Kegiatan Warga</option>
                            <option value="Keamanan & Lingkungan">Keamanan &amp; Lingkungan</option>
                            <option value="Fasilitas Umum">Fasilitas Umum</option>
                            <option value="Sosial & Kekeluargaan">Sosial &amp; Kekeluargaan</option>
                            <option value="Info UMKM">Info UMKM</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1">Posisi Tampilan di Beranda *</label>
                        <select x-model="editNewsForm.layout"
                                class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                            <option value="hero">Berita Utama (Hero Headline)</option>
                            <option value="thumbnail">Kartu Foto (Thumbnail)</option>
                            <option value="grid">Grid 2 Kolom</option>
                            <option value="text">Daftar Berita Teks</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1">Isi Lengkap Berita *</label>
                    <textarea x-model="editNewsForm.body" required minlength="20" rows="7"
                              class="w-full rounded-xl border-slate-300 text-sm leading-relaxed focus:border-emerald-600 focus:ring-emerald-600"></textarea>
                </div>

                <div class="pt-3 flex items-center justify-end gap-2 border-t border-slate-100">
                    <button type="button"
                            @click="editingNews = null"
                            class="px-4 py-2 rounded-xl border border-slate-300 text-slate-700 text-xs sm:text-sm font-semibold cursor-pointer">
                        Batal
                    </button>
                    <button type="submit"
                            :disabled="savingNews"
                            class="px-5 py-2 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white text-xs sm:text-sm font-bold shadow-sm cursor-pointer">
                        <span x-show="!savingNews">Simpan Perubahan</span>
                        <span x-show="savingNews">Menyimpan...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- MODAL EDIT PENGUMUMAN PAGUYUBAN                                       -->
    <!-- ===================================================================== -->
    <div x-show="editingAnnouncement !== null"
         x-cloak
         class="fixed inset-0 z-50 !mt-0 flex items-center justify-center p-3 sm:p-4 bg-slate-900/50 backdrop-blur-[1px] overflow-y-auto">
        <div @click.away="editingAnnouncement = null"
             class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-[calc(100vw-1.5rem)] sm:max-w-lg max-h-[90dvh] flex flex-col overflow-hidden my-auto">
            <div class="px-5 sm:px-6 py-4 border-b border-slate-100 flex items-center justify-between gap-3 shrink-0">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Edit Pengumuman Paguyuban</h3>
                    <p class="text-xs text-slate-500"
                       x-show="editingAnnouncement?.author"
                       x-text="'Diterbitkan oleh: ' + editingAnnouncement?.author"></p>
                </div>
                <button type="button" @click="editingAnnouncement = null" class="text-slate-400 hover:text-slate-700 text-sm p-1 cursor-pointer">✕</button>
            </div>

            <form @submit.prevent="saveEditedAnnouncement()" class="p-5 sm:p-6 space-y-4 overflow-y-auto">
                <div>
                    <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1">Judul Pengumuman *</label>
                    <input type="text" x-model="editAnnouncementForm.title" required maxlength="180"
                           class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                </div>

                <div>
                    <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1">Kategori / Label *</label>
                    <select x-model="editAnnouncementForm.category"
                            class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                        <option value="info">Info Lingkungan</option>
                        <option value="penting">Penting / Mendesak</option>
                        <option value="kerja_bakti">Kegiatan Warga / Kerja Bakti</option>
                        <option value="musyawarah">Musyawarah Warga</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1">Isi Pengumuman Lengkap *</label>
                    <textarea x-model="editAnnouncementForm.body" required rows="6"
                              class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600"></textarea>
                </div>

                <label class="flex items-center gap-2.5 p-3 rounded-xl bg-amber-50/70 border border-amber-200 text-xs sm:text-sm text-slate-700 cursor-pointer select-none">
                    <input type="checkbox" x-model="editAnnouncementForm.is_pinned" class="rounded text-emerald-700">
                    <span>Sematkan (<strong>Pin</strong>) di urutan paling atas</span>
                </label>

                <div class="pt-2 flex items-center justify-end gap-2 border-t border-slate-100">
                    <button type="button" @click="editingAnnouncement = null"
                            class="px-4 py-2 rounded-xl border border-slate-300 text-slate-700 text-xs sm:text-sm font-semibold cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" :disabled="savingAnnouncement"
                            class="px-5 py-2 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white text-xs sm:text-sm font-bold shadow-sm cursor-pointer">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- MODAL EDIT AGENDA & JADWAL LINGKUNGAN                                 -->
    <!-- ===================================================================== -->
    <div x-show="editingAgenda !== null"
         x-cloak
         class="fixed inset-0 z-50 !mt-0 flex items-center justify-center p-3 sm:p-4 bg-slate-900/50 backdrop-blur-[1px] overflow-y-auto">
        <div @click.away="editingAgenda = null"
             class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-[calc(100vw-1.5rem)] sm:max-w-lg max-h-[90dvh] flex flex-col overflow-hidden my-auto">
            <div class="px-5 sm:px-6 py-4 border-b border-slate-100 flex items-center justify-between gap-3 shrink-0">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Edit Agenda &amp; Jadwal Lingkungan</h3>
                    <p class="text-xs text-slate-500"
                       x-show="editingAgenda?.author"
                       x-text="'Dicatat oleh: ' + editingAgenda?.author"></p>
                </div>
                <button type="button" @click="editingAgenda = null" class="text-slate-400 hover:text-slate-700 text-sm p-1 cursor-pointer">✕</button>
            </div>

            <form @submit.prevent="saveEditedAgenda()" class="p-5 sm:p-6 space-y-4 overflow-y-auto">
                <div>
                    <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1">Nama Kegiatan / Jadwal *</label>
                    <input type="text" x-model="editAgendaForm.title" required maxlength="180"
                           class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1">Tanggal Kegiatan *</label>
                        <input type="date" x-model="editAgendaForm.event_date" required
                               class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                    </div>
                    <div>
                        <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1">Kategori Jadwal *</label>
                        <select x-model="editAgendaForm.category"
                                class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                            <option value="kegiatan">Kegiatan Warga</option>
                            <option value="kebersihan">Kebersihan &amp; Sampah</option>
                            <option value="keamanan">Keamanan &amp; Ronda</option>
                            <option value="pemeliharaan">Pemeliharaan Fasum</option>
                            <option value="musyawarah">Musyawarah / Rapat</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1">Waktu / Jam *</label>
                        <input type="text" x-model="editAgendaForm.time_label" required maxlength="80"
                               class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                    </div>
                    <div>
                        <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1">Lokasi Kegiatan *</label>
                        <input type="text" x-model="editAgendaForm.location" required maxlength="140"
                               class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                    </div>
                </div>

                <div>
                    <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1">Catatan Tambahan</label>
                    <textarea x-model="editAgendaForm.notes" rows="3"
                              class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600"></textarea>
                </div>

                <div class="pt-2 flex items-center justify-end gap-2 border-t border-slate-100">
                    <button type="button" @click="editingAgenda = null"
                            class="px-4 py-2 rounded-xl border border-slate-300 text-slate-700 text-xs sm:text-sm font-semibold cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" :disabled="savingAgenda"
                            class="px-5 py-2 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white text-xs sm:text-sm font-bold shadow-sm cursor-pointer">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- MODAL EDIT NOTULENSI RAPAT & TATA TERTIB                              -->
    <!-- ===================================================================== -->
    <div x-show="editingMinute !== null"
         x-cloak
         class="fixed inset-0 z-50 !mt-0 flex items-center justify-center p-3 sm:p-4 bg-slate-900/50 backdrop-blur-[1px] overflow-y-auto">
        <div @click.away="editingMinute = null"
             class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-[calc(100vw-1.5rem)] sm:max-w-lg max-h-[90dvh] flex flex-col overflow-hidden my-auto">
            <div class="px-5 sm:px-6 py-4 border-b border-slate-100 flex items-center justify-between gap-3 shrink-0">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Edit Notulensi Rapat &amp; Tata Tertib</h3>
                    <p class="text-xs text-slate-500"
                       x-show="editingMinute?.author"
                       x-text="'Pencatat: ' + editingMinute?.author"></p>
                </div>
                <button type="button" @click="editingMinute = null" class="text-slate-400 hover:text-slate-700 text-sm p-1 cursor-pointer">✕</button>
            </div>

            <form @submit.prevent="saveEditedMinute()" class="p-5 sm:p-6 space-y-4 overflow-y-auto">
                <div>
                    <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1">Judul Agenda / Dokumen *</label>
                    <input type="text" x-model="editMinuteForm.title" required maxlength="180"
                           class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1">Tanggal Rapat / Penetapan *</label>
                        <input type="date" x-model="editMinuteForm.meeting_date" required
                               class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                    </div>
                    <div>
                        <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1">Kategori Dokumen *</label>
                        <select x-model="editMinuteForm.category"
                                class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                            <option value="musyawarah_warga">Musyawarah Warga</option>
                            <option value="tata_tertib">Tata Tertib Lingkungan</option>
                            <option value="rapat_pengurus">Rapat Pengurus</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1">Lokasi / Forum Pertemuan</label>
                    <input type="text" x-model="editMinuteForm.location" maxlength="120"
                           class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                </div>

                <div>
                    <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1">Poin Kesepakatan &amp; Isi Lengkap *</label>
                    <textarea x-model="editMinuteForm.decisions" required rows="6"
                              class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600"></textarea>
                </div>

                <div>
                    <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1">Visibilitas Dokumen *</label>
                    <select x-model="editMinuteForm.visibility"
                            class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                        <option value="public_residents">Terbuka untuk Semua Warga PPMT</option>
                        <option value="internal_pengurus">Internal Pengurus Saja</option>
                    </select>
                </div>

                <div class="pt-2 flex items-center justify-end gap-2 border-t border-slate-100">
                    <button type="button" @click="editingMinute = null"
                            class="px-4 py-2 rounded-xl border border-slate-300 text-slate-700 text-xs sm:text-sm font-semibold cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" :disabled="savingMinute"
                            class="px-5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs sm:text-sm font-bold shadow-sm cursor-pointer">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function ppmtEditorialPanel() {
    const todayStr = new Date().toISOString().slice(0, 10);
    const validTabs = ['publikasi', 'agenda', 'notulensi'];

    return {
        token: localStorage.getItem('ppmt_token'),
        user: null,
        loading: true,
        activeTab: 'publikasi',
        openMenuKey: null,
        toastMessage: '',
        toastType: 'success',
        announcements: [],
        newsArticles: [],
        agendas: [],
        meetingMinutes: [],
        showCreateAnnouncement: false,
        showCreateAgenda: false,
        showCreateMinute: false,
        savingAnnouncement: false,
        editingAnnouncement: null,
        savingNews: false,
        editingNews: null,
        savingAgenda: false,
        editingAgenda: null,
        savingMinute: false,
        editingMinute: null,
        announcementForm: {
            title: '',
            category: 'info',
            body: '',
            is_pinned: false
        },
        editAnnouncementForm: {
            id: null,
            title: '',
            category: 'info',
            body: '',
            is_pinned: false
        },
        editNewsForm: {
            id: null,
            title: '',
            category: 'Kegiatan Warga',
            layout: 'thumbnail',
            body: ''
        },
        agendaForm: {
            title: '',
            category: 'kegiatan',
            event_date: todayStr,
            time_label: '',
            location: '',
            notes: ''
        },
        editAgendaForm: {
            id: null,
            title: '',
            category: 'kegiatan',
            event_date: todayStr,
            time_label: '',
            location: '',
            notes: ''
        },
        minuteForm: {
            title: '',
            meeting_date: todayStr,
            location: '',
            category: 'musyawarah_warga',
            decisions: '',
            visibility: 'public_residents'
        },
        editMinuteForm: {
            id: null,
            title: '',
            meeting_date: todayStr,
            location: '',
            category: 'musyawarah_warga',
            decisions: '',
            visibility: 'public_residents'
        },

        toggleMenu(key) {
            this.openMenuKey = this.openMenuKey === key ? null : key;
        },

        closeMenu(key) {
            if (this.openMenuKey === key) {
                this.openMenuKey = null;
            }
        },

        closeAllModals() {
            this.showCreateAnnouncement = false;
            this.showCreateAgenda = false;
            this.showCreateMinute = false;
            this.editingAnnouncement = null;
            this.editingNews = null;
            this.editingAgenda = null;
            this.editingMinute = null;
        },

        switchTab(tab) {
            if (!validTabs.includes(tab)) return;
            this.openMenuKey = null;
            this.activeTab = tab;
            window.history.replaceState({}, '', '#' + tab);
        },

        openCreateAnnouncementModal() {
            this.openMenuKey = null;
            this.announcementForm = { title: '', category: 'info', body: '', is_pinned: false };
            this.showCreateAnnouncement = true;
        },

        openCreateAgendaModal() {
            this.openMenuKey = null;
            this.agendaForm = {
                title: '',
                category: 'kegiatan',
                event_date: todayStr,
                time_label: '',
                location: '',
                notes: ''
            };
            this.showCreateAgenda = true;
        },

        openCreateMinuteModal() {
            this.openMenuKey = null;
            this.minuteForm = {
                title: '',
                meeting_date: todayStr,
                location: '',
                category: 'musyawarah_warga',
                decisions: '',
                visibility: 'public_residents'
            };
            this.showCreateMinute = true;
        },

        async init() {
            if (!this.token) {
                window.location.href = '/';
                return;
            }

            const initialHash = (window.location.hash || '').replace('#', '').toLowerCase();
            if (validTabs.includes(initialHash)) {
                this.activeTab = initialHash;
            }

            await this.loadMe();
            const canManage = Boolean(this.user?.can_manage_announcements || this.user?.is_global_governor);
            if (!this.user?.has_admin_access || !canManage) {
                window.location.href = '/pengurus';
                return;
            }
            await Promise.all([
                this.loadAnnouncements(),
                this.loadNewsArticles(),
                this.loadAgendas(),
                this.loadMeetingMinutes()
            ]);
        },

        headers() {
            return {
                'Authorization': 'Bearer ' + this.token,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            };
        },

        formatNewsLayout(layout) {
            const labels = {
                'hero': 'Hero Utama',
                'thumbnail': 'Kartu Foto',
                'grid': 'Grid 2 Kolom',
                'text': 'Daftar Teks'
            };
            return labels[layout] || layout;
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

        async loadAnnouncements() {
            const res = await fetch('/api/v1/announcements?all=1', { headers: this.headers() });
            if (res.ok) {
                const json = await res.json();
                this.announcements = json.data || [];
            }
        },

        async createAnnouncement() {
            this.savingAnnouncement = true;
            try {
                const res = await fetch('/api/v1/announcements', {
                    method: 'POST',
                    headers: this.headers(),
                    body: JSON.stringify(this.announcementForm)
                });
                const json = await res.json();
                if (res.ok) {
                    this.showCreateAnnouncement = false;
                    this.toastType = 'success';
                    this.toastMessage = json.message;
                    this.announcementForm = { title: '', category: 'info', body: '', is_pinned: false };
                    await this.loadAnnouncements();
                } else {
                    this.toastType = 'error';
                    this.toastMessage = json.message || 'Gagal menerbitkan pengumuman.';
                }
            } finally {
                this.savingAnnouncement = false;
            }
        },

        openEditAnnouncement(item) {
            this.editAnnouncementForm = {
                id: item.id,
                title: item.title,
                category: item.category_code,
                body: item.body,
                is_pinned: Boolean(item.is_pinned)
            };
            this.editingAnnouncement = item;
        },

        async saveEditedAnnouncement() {
            this.savingAnnouncement = true;
            try {
                const res = await fetch(`/api/v1/announcements/${this.editAnnouncementForm.id}`, {
                    method: 'PATCH',
                    headers: this.headers(),
                    body: JSON.stringify({
                        title: this.editAnnouncementForm.title,
                        category: this.editAnnouncementForm.category,
                        body: this.editAnnouncementForm.body,
                        is_pinned: this.editAnnouncementForm.is_pinned
                    })
                });
                const json = await res.json();
                if (res.ok) {
                    this.editingAnnouncement = null;
                    this.toastType = 'success';
                    this.toastMessage = json.message;
                    await this.loadAnnouncements();
                } else {
                    this.toastType = 'error';
                    this.toastMessage = json.message || 'Gagal memperbarui pengumuman.';
                }
            } finally {
                this.savingAnnouncement = false;
            }
        },

        async toggleAnnouncementPin(item) {
            const res = await fetch(`/api/v1/announcements/${item.id}`, {
                method: 'PATCH',
                headers: this.headers(),
                body: JSON.stringify({ is_pinned: !item.is_pinned })
            });
            if (res.ok) {
                this.toastType = 'success';
                this.toastMessage = item.is_pinned ? 'Sematkan pengumuman dilepas.' : 'Pengumuman disematkan di urutan teratas.';
                await this.loadAnnouncements();
            }
        },

        async toggleAnnouncementPublish(item) {
            const res = await fetch(`/api/v1/announcements/${item.id}`, {
                method: 'PATCH',
                headers: this.headers(),
                body: JSON.stringify({ is_published: !item.is_published })
            });
            if (res.ok) {
                this.toastType = 'success';
                this.toastMessage = item.is_published ? 'Pengumuman diarsipkan dari beranda warga.' : 'Pengumuman kembali ditayangkan.';
                await this.loadAnnouncements();
            }
        },

        async deleteAnnouncement(item) {
            const res = await fetch(`/api/v1/announcements/${item.id}`, {
                method: 'DELETE',
                headers: this.headers()
            });
            const json = await res.json();
            if (res.ok) {
                this.toastType = 'success';
                this.toastMessage = json.message;
                await this.loadAnnouncements();
            }
        },

        async loadNewsArticles() {
            const res = await fetch('/api/v1/admin/news', { headers: this.headers() });
            if (res.ok) {
                const json = await res.json();
                this.newsArticles = json.data || [];
            }
        },

        openNewsEditModal(art) {
            this.editNewsForm = {
                id: art.id,
                title: art.title,
                category: art.category,
                layout: art.layout,
                body: art.body || ''
            };
            this.editingNews = art;
        },

        async saveEditedNews() {
            if (!this.editNewsForm.id) return;
            this.savingNews = true;
            try {
                const res = await fetch(`/api/v1/admin/news/${this.editNewsForm.id}`, {
                    method: 'PATCH',
                    headers: this.headers(),
                    body: JSON.stringify({
                        title: this.editNewsForm.title,
                        category: this.editNewsForm.category,
                        layout: this.editNewsForm.layout,
                        body: this.editNewsForm.body
                    })
                });
                const json = await res.json();
                if (res.ok) {
                    this.editingNews = null;
                    this.toastType = 'success';
                    this.toastMessage = json.message;
                    await this.loadNewsArticles();
                } else {
                    this.toastType = 'error';
                    this.toastMessage = json.message || 'Gagal menyimpan perubahan berita.';
                }
            } finally {
                this.savingNews = false;
            }
        },

        async deleteNewsArticle(art) {
            if (!confirm(`Hapus berita "${art.title}"?`)) return;
            const res = await fetch(`/api/v1/admin/news/${art.id}`, {
                method: 'DELETE',
                headers: this.headers()
            });
            const json = await res.json();
            if (res.ok) {
                this.toastType = 'success';
                this.toastMessage = json.message;
                await this.loadNewsArticles();
            } else {
                this.toastType = 'error';
                this.toastMessage = json.message || 'Gagal menghapus berita.';
            }
        },

        async loadAgendas() {
            const res = await fetch('/api/v1/agendas?all=1', { headers: this.headers() });
            if (res.ok) {
                const json = await res.json();
                this.agendas = json.data || [];
            }
        },

        async createAgenda() {
            this.savingAgenda = true;
            try {
                const res = await fetch('/api/v1/agendas', {
                    method: 'POST',
                    headers: this.headers(),
                    body: JSON.stringify(this.agendaForm)
                });
                const json = await res.json();
                if (res.ok) {
                    this.showCreateAgenda = false;
                    this.toastType = 'success';
                    this.toastMessage = json.message;
                    this.agendaForm = {
                        title: '',
                        category: 'kegiatan',
                        event_date: todayStr,
                        time_label: '',
                        location: '',
                        notes: ''
                    };
                    await this.loadAgendas();
                } else {
                    this.toastType = 'error';
                    this.toastMessage = json.message || 'Gagal menyimpan agenda.';
                }
            } finally {
                this.savingAgenda = false;
            }
        },

        openEditAgenda(ag) {
            this.editAgendaForm = {
                id: ag.id,
                title: ag.title,
                category: ag.category_code,
                event_date: ag.event_date,
                time_label: ag.time,
                location: ag.location,
                notes: ag.notes || ''
            };
            this.editingAgenda = ag;
        },

        async saveEditedAgenda() {
            this.savingAgenda = true;
            try {
                const res = await fetch(`/api/v1/agendas/${this.editAgendaForm.id}`, {
                    method: 'PATCH',
                    headers: this.headers(),
                    body: JSON.stringify({
                        title: this.editAgendaForm.title,
                        category: this.editAgendaForm.category,
                        event_date: this.editAgendaForm.event_date,
                        time_label: this.editAgendaForm.time_label,
                        location: this.editAgendaForm.location,
                        notes: this.editAgendaForm.notes || null
                    })
                });
                const json = await res.json();
                if (res.ok) {
                    this.editingAgenda = null;
                    this.toastType = 'success';
                    this.toastMessage = json.message;
                    await this.loadAgendas();
                } else {
                    this.toastType = 'error';
                    this.toastMessage = json.message || 'Gagal memperbarui agenda.';
                }
            } finally {
                this.savingAgenda = false;
            }
        },

        async deleteAgenda(ag) {
            const res = await fetch(`/api/v1/agendas/${ag.id}`, {
                method: 'DELETE',
                headers: this.headers()
            });
            const json = await res.json();
            if (res.ok) {
                this.toastType = 'success';
                this.toastMessage = json.message;
                await this.loadAgendas();
            }
        },

        async loadMeetingMinutes() {
            const res = await fetch('/api/v1/meeting-minutes', { headers: this.headers() });
            if (res.ok) {
                const json = await res.json();
                this.meetingMinutes = json.data || [];
            }
        },

        async createMinute() {
            this.savingMinute = true;
            try {
                const res = await fetch('/api/v1/meeting-minutes', {
                    method: 'POST',
                    headers: this.headers(),
                    body: JSON.stringify(this.minuteForm)
                });
                const json = await res.json();
                if (res.ok) {
                    this.showCreateMinute = false;
                    this.toastType = 'success';
                    this.toastMessage = json.message;
                    this.minuteForm = {
                        title: '',
                        meeting_date: todayStr,
                        location: '',
                        category: 'musyawarah_warga',
                        decisions: '',
                        visibility: 'public_residents'
                    };
                    await this.loadMeetingMinutes();
                } else {
                    this.toastType = 'error';
                    this.toastMessage = json.message || 'Gagal menyimpan notulensi.';
                }
            } finally {
                this.savingMinute = false;
            }
        },

        openEditMinute(m) {
            this.editMinuteForm = {
                id: m.id,
                title: m.title,
                meeting_date: m.meeting_date,
                location: m.location || '',
                category: m.category_code,
                decisions: m.decisions,
                visibility: m.visibility
            };
            this.editingMinute = m;
        },

        async saveEditedMinute() {
            this.savingMinute = true;
            try {
                const res = await fetch(`/api/v1/meeting-minutes/${this.editMinuteForm.id}`, {
                    method: 'PATCH',
                    headers: this.headers(),
                    body: JSON.stringify({
                        title: this.editMinuteForm.title,
                        meeting_date: this.editMinuteForm.meeting_date,
                        location: this.editMinuteForm.location || null,
                        category: this.editMinuteForm.category,
                        decisions: this.editMinuteForm.decisions,
                        visibility: this.editMinuteForm.visibility
                    })
                });
                const json = await res.json();
                if (res.ok) {
                    this.editingMinute = null;
                    this.toastType = 'success';
                    this.toastMessage = json.message;
                    await this.loadMeetingMinutes();
                } else {
                    this.toastType = 'error';
                    this.toastMessage = json.message || 'Gagal memperbarui notulensi.';
                }
            } finally {
                this.savingMinute = false;
            }
        },

        async deleteMinute(m) {
            const res = await fetch(`/api/v1/meeting-minutes/${m.id}`, {
                method: 'DELETE',
                headers: this.headers()
            });
            const json = await res.json();
            if (res.ok) {
                this.toastType = 'success';
                this.toastMessage = json.message;
                await this.loadMeetingMinutes();
            }
        }
    }
}
</script>
@endpush
@endsection
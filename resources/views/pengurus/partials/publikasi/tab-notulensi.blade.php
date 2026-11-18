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
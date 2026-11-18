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
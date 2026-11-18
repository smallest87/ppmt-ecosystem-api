<!-- Kartu 2: Agenda Warga (Live API) -->
<section class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between gap-3">
        <h2 class="text-base font-bold text-slate-900">Agenda Warga</h2>
        <a x-show="canPublishNews"
           href="/pengurus"
           class="px-2.5 py-1 rounded-lg text-[11px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200 hover:bg-indigo-100 transition shrink-0">
            + Kelola
        </a>
    </div>

    <div x-show="agendas.length === 0" class="px-6 py-8 text-center text-xs text-slate-500">
        Belum ada agenda atau jadwal kegiatan warga dalam waktu dekat.
    </div>

    <div class="divide-y divide-slate-100" x-show="agendas.length > 0">
        <template x-for="ag in agendas" :key="ag.id">
            <div class="px-6 py-4 flex items-center gap-4">
                <!-- Kotak Hari, Tanggal & Bulan Relatif Besar -->
                <div class="w-16 h-20 rounded-2xl bg-emerald-50 border border-emerald-200 flex flex-col items-center justify-center shrink-0 text-emerald-950 shadow-2xs">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-emerald-700 leading-none" x-text="ag.dayShort"></span>
                    <span class="text-2xl font-extrabold leading-tight my-0.5" x-text="ag.dateNum"></span>
                    <span class="text-[11px] font-extrabold uppercase tracking-wider text-emerald-800 leading-none" x-text="indoMonthShort(ag.monthShort)"></span>
                </div>

                <!-- Detail Agenda -->
                <div class="min-w-0 flex-1 space-y-2">
                    <div class="text-sm sm:text-base font-bold text-slate-900 leading-snug" x-text="ag.title"></div>

                    <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-600">
                        <!-- Waktu dengan Ikon Jam -->
                        <span class="inline-flex items-center gap-1.5 font-medium">
                            <svg class="w-4 h-4 text-emerald-700 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span x-text="ag.time"></span>
                        </span>

                        <!-- Lokasi dengan Ikon Pin Lokasi -->
                        <span class="inline-flex items-center gap-1.5 font-medium">
                            <svg class="w-4 h-4 text-emerald-700 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                            </svg>
                            <span x-text="ag.location"></span>
                        </span>
                    </div>

                    <p x-show="ag.notes" class="text-xs text-slate-500 italic" x-text="ag.notes"></p>
                </div>
            </div>
        </template>
    </div>
</section>
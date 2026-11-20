<!-- Notifikasi Ringkas Dikelompokkan per Visibilitas: Agenda Warga (Live API) -->
<section x-show="groupedAgendaNotifications.length > 0" x-cloak class="pt-2.5 space-y-4">
    <template x-for="grp in groupedAgendaNotifications" :key="grp.key">
        <div :class="grp.visibility === 'invite_only'
                ? 'bg-purple-50/90 hover:bg-purple-100/70 border-purple-200 border-l-purple-600'
                : (grp.visibility === 'block'
                    ? 'bg-amber-50/90 hover:bg-amber-100/70 border-amber-200 border-l-amber-600'
                    : 'bg-emerald-50/90 hover:bg-emerald-100/70 border-emerald-200 border-l-emerald-600')"
             class="relative w-full px-3.5 pt-4 pb-3 rounded-md border border-l-4 shadow-2xs flex items-center justify-between gap-3 transition">

            <!-- Floating Badge Kiri Atas (Ikon Kalender + Visibilitas) -->
            <span :class="grp.visibility === 'invite_only'
                    ? 'bg-purple-700 text-white border-purple-800/30'
                    : (grp.visibility === 'block'
                        ? 'bg-amber-600 text-white border-amber-700/30'
                        : 'bg-emerald-700 text-white border-emerald-800/30')"
                  class="absolute -top-3 left-3.5 inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-md text-[11px] font-bold tracking-wide shadow-xs border">
                <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                </svg>
                <span x-text="grp.label"></span>
            </span>

            <!-- Teks Notifikasi -->
            <p class="text-sm text-slate-800 leading-snug min-w-0 flex-1">
                <span>Ada </span>
                <a :href="'/komunitas/agenda?visibility=' + encodeURIComponent(grp.visibility)"
                    :class="grp.visibility === 'invite_only'
                            ? 'text-purple-800 decoration-purple-500/70 hover:text-purple-950'
                            : (grp.visibility === 'block'
                                ? 'text-amber-900 decoration-amber-500/70 hover:text-amber-950'
                                : 'text-emerald-800 decoration-emerald-500/70 hover:text-emerald-950')"
                    class="font-extrabold underline underline-offset-2 transition"
                    x-text="grp.count + ' Agenda,'"></a>
                <span>cek sekarang juga!</span>
            </p>

            <!-- Aksi Kanan (Kelola untuk Pengurus + Panah ke /agenda) -->
            <div class="flex items-center gap-2 shrink-0">
                <a x-show="canPublishNews"
                   href="/pengurus/publikasi#agenda"
                   class="hidden sm:inline-flex px-2.5 py-1 rounded-lg text-[11px] font-bold bg-white text-indigo-700 border border-indigo-200 hover:bg-indigo-50 transition">
                    + Kelola
                </a>
                <a :href="'/komunitas/agenda?visibility=' + encodeURIComponent(grp.visibility)"
                aria-label="Lihat detail agenda"
                class="text-slate-600 hover:text-slate-900 font-bold text-lg leading-none px-1 transition">
                    &rsaquo;
                </a>
            </div>
        </div>
    </template>
</section>
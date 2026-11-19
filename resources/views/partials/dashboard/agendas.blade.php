<!-- Notifikasi Ringkas Dikelompokkan per Visibilitas: Agenda Warga (Live API) -->
<section x-show="groupedAgendaNotifications.length > 0" x-cloak class="space-y-2.5">
    <template x-for="grp in groupedAgendaNotifications" :key="grp.key">
        <div :class="grp.visibility === 'invite_only'
                ? 'bg-purple-50/90 hover:bg-purple-100/70 border-purple-200 border-l-purple-600'
                : (grp.visibility === 'block'
                    ? 'bg-amber-50/90 hover:bg-amber-100/70 border-amber-200 border-l-amber-600'
                    : 'bg-emerald-50/90 hover:bg-emerald-100/70 border-emerald-200 border-l-emerald-600')"
             class="w-full px-3 py-2.5 rounded-md border border-l-4 shadow-2xs flex items-center justify-between gap-3 transition">

            <div class="flex items-center gap-3 min-w-0 flex-1">
                <!-- Ikon Kalender -->
                <div :class="grp.visibility === 'invite_only'
                        ? 'bg-purple-100 text-purple-800 border-purple-300/80'
                        : (grp.visibility === 'block'
                            ? 'bg-amber-100 text-amber-800 border-amber-300/80'
                            : 'bg-emerald-100 text-emerald-800 border-emerald-300/80')"
                     class="w-9 h-9 rounded-xl border flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                    </svg>
                </div>

                <!-- Teks Notifikasi -->
                <p class="text-sm sm:text-sm text-slate-800 leading-snug min-w-0">
                    <span :class="grp.visibility === 'invite_only'
                            ? 'text-purple-900 border-purple-300'
                            : (grp.visibility === 'block'
                                ? 'text-amber-900 border-amber-300'
                                : 'text-emerald-900 border-emerald-300')"
                          class="inline-flex items-center px-2 py-0.5 mr-1 rounded-md text-[11px] font-bold bg-white border align-middle"
                          x-text="grp.label"></span>
                    <span>Ada </span>
                    <a :href="'/agenda?visibility=' + encodeURIComponent(grp.visibility)"
                       :class="grp.visibility === 'invite_only'
                            ? 'text-purple-800 decoration-purple-500/70 hover:text-purple-950'
                            : (grp.visibility === 'block'
                                ? 'text-amber-900 decoration-amber-500/70 hover:text-amber-950'
                                : 'text-emerald-800 decoration-emerald-500/70 hover:text-emerald-950')"
                       class="font-extrabold underline underline-offset-2 transition"
                       x-text="grp.count + ' agenda'"></a>
                    <span> sedang menanti keterlibatanmu!</span>
                </p>
            </div>

            <!-- Aksi Kanan (Kelola untuk Pengurus + Panah ke /agenda) -->
            <div class="flex items-center gap-2 shrink-0">
                <a x-show="canPublishNews"
                   href="/pengurus/publikasi#agenda"
                   class="hidden sm:inline-flex px-2.5 py-1 rounded-lg text-[11px] font-bold bg-white text-indigo-700 border border-indigo-200 hover:bg-indigo-50 transition">
                    + Kelola
                </a>
                <a :href="'/agenda?visibility=' + encodeURIComponent(grp.visibility)"
                   aria-label="Lihat detail agenda"
                   class="text-slate-600 hover:text-slate-900 font-bold text-lg leading-none px-1 transition">
                    &rsaquo;
                </a>
            </div>
        </div>
    </template>
</section>
<!-- Bagian 1: Pengumuman Warga (Clean Bootstrap-style Alert Cards) -->
<section x-show="announcements.length > 0"
        class="space-y-2.5"
        x-data="{ showAllAnnouncements: false }">
    <!-- Susunan Vertikal Alert Box per Item -->
    <div class="space-y-2.5">
        <template x-for="item in (showAllAnnouncements ? announcements : announcements.slice(0, 5))" :key="item.id">
            <button type="button"
                @click="copiedAnnouncement = false; selectedAnnouncement = item"
                    :class="/penting|darurat|mendesak|peringatan|urgent/i.test(item.category || '')
                        ? 'bg-rose-50 hover:bg-rose-100/80 border-rose-300 border-l-4 border-l-rose-600 text-rose-950'
                        : (item.is_pinned
                            ? 'bg-amber-50 hover:bg-amber-100/80 border-amber-300 border-l-4 border-l-amber-500 text-amber-950'
                            : 'bg-white hover:bg-emerald-50/50 border-slate-200 border-l-4 border-l-emerald-600 text-slate-900')"
                    class="relative w-full px-4 py-3.5 rounded-md border shadow-xs flex items-center justify-between gap-3.5 text-left transition cursor-pointer group">

                <!-- Ikon Pin di Pojok Kiri Atas (Hanya untuk item yang di-pin) -->
                <span x-show="item.is_pinned"
                    title="Pengumuman Disematkan"
                    :class="/penting|darurat|mendesak|peringatan|urgent/i.test(item.category || '') ? 'bg-rose-600' : 'bg-amber-500'"
                    class="absolute -top-2 -left-2 w-5 h-5 rounded-full text-white shadow-xs flex items-center justify-center ring-2 ring-white">
                    <svg class="w-2.5 h-2.5" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M16 3a1 1 0 0 1 .707.293l4 4a1 1 0 0 1-.493 1.686l-3.437.687-2.61 2.61.556 3.337a1 1 0 0 1-1.693.88L9.5 12.963l-4.793 4.794a1 1 0 0 1-1.414-1.414L8.086 11.55 4.557 8.02a1 1 0 0 1 .88-1.693l3.337.556 2.61-2.61.687-3.437A1 1 0 0 1 13.757.343L16 3Z"/>
                    </svg>
                </span>

                <!-- Ikon Kategori Berukuran Besar Sejajar Judul -->
                <div :title="item.category"
                     :class="/penting|darurat|mendesak|peringatan|urgent/i.test(item.category || '')
                         ? 'bg-rose-100 text-rose-700 border-rose-300/90'
                         : (item.is_pinned
                             ? 'bg-amber-100 text-amber-800 border-amber-300/80'
                             : 'bg-emerald-50 text-emerald-700 border-emerald-200/80')"
                     class="w-10 h-10 rounded-xl border flex items-center justify-center shrink-0">
                    <!-- Ikon Penting / Darurat / Mendesak -->
                    <template x-if="/penting|darurat|mendesak|peringatan|urgent/i.test(item.category || '')">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                    </template>
                    <!-- Ikon Keamanan / Tertib -->
                    <template x-if="!/penting|darurat|mendesak|peringatan|urgent/i.test(item.category || '') && /keamanan|tertib/i.test(item.category || '')">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />
                        </svg>
                    </template>
                    <!-- Ikon Iuran / Keuangan / Kas -->
                    <template x-if="!/penting|darurat|mendesak|peringatan|urgent|keamanan|tertib/i.test(item.category || '') && /iuran|kas|keuangan|sampah/i.test(item.category || '')">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                        </svg>
                    </template>
                    <!-- Ikon Musyawarah / Rapat / Agenda -->
                    <template x-if="!/penting|darurat|mendesak|peringatan|urgent|keamanan|tertib|iuran|kas|keuangan|sampah/i.test(item.category || '') && /musyawarah|rapat|agenda|takmir|musholla/i.test(item.category || '')">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                        </svg>
                    </template>
                    <!-- Ikon Info Umum / Info Lingkungan (Default) -->
                    <template x-if="!/penting|darurat|mendesak|peringatan|urgent|keamanan|tertib|iuran|kas|keuangan|sampah|musyawarah|rapat|agenda|takmir|musholla/i.test(item.category || '')">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                        </svg>
                    </template>
                </div>

                <!-- Judul Pengumuman -->
                <div :class="/penting|darurat|mendesak|peringatan|urgent/i.test(item.category || '')
                         ? 'font-extrabold text-rose-950 group-hover:text-rose-800'
                         : (item.is_pinned
                             ? 'font-extrabold text-amber-950 group-hover:text-emerald-800'
                             : 'font-bold text-slate-900 group-hover:text-emerald-800')"
                     class="min-w-0 flex-1 text-sm transition leading-snug"
                     x-text="item.title"></div>

                <!-- Panah Indikator Klik -->
                <span :class="/penting|darurat|mendesak|peringatan|urgent/i.test(item.category || '')
                          ? 'text-rose-600 group-hover:text-rose-800'
                          : (item.is_pinned
                              ? 'text-amber-700 group-hover:text-emerald-700'
                              : 'text-slate-400 group-hover:text-emerald-700')"
                      class="group-hover:translate-x-0.5 transition text-lg font-bold shrink-0">&rsaquo;</span>
            </button>
        </template>
    </div>

    <div x-show="announcements.length > 5" class="px-1 pt-0.5">
        <button type="button"
                @click="showAllAnnouncements = !showAllAnnouncements"
                class="text-xs font-bold text-emerald-700 hover:text-emerald-900 cursor-pointer">
            <span x-show="!showAllAnnouncements" x-text="'Lihat semua (' + announcements.length + ' pengumuman) ↓'"></span>
            <span x-show="showAllAnnouncements">Ringkas tampilan ↑</span>
        </button>
    </div>
</section>
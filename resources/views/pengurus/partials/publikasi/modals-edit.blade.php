<!-- MODAL EDIT KABAR & BERITA WARGA -->
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

<!-- MODAL EDIT PENGUMUMAN PAGUYUBAN -->
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

<!-- MODAL EDIT AGENDA & JADWAL LINGKUNGAN -->
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

            <!-- Visibilitas / Sasaran Penerima Agenda -->
            <div class="space-y-2.5 p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                <label class="block text-xs sm:text-sm font-semibold text-slate-700">Sasaran / Visibilitas Agenda *</label>
                <select x-model="editAgendaForm.visibility"
                        class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                    <option value="public">Seluruh Warga PPMT</option>
                    <option value="block">Blok Tertentu Saja</option>
                    <option value="invite_only">Undangan Warga Tertentu</option>
                </select>

                <!-- Pilihan Blok -->
                <div x-show="editAgendaForm.visibility === 'block'" x-cloak class="pt-1 space-y-1.5">
                    <div class="text-xs font-medium text-slate-600">Pilih Blok Kavling:</div>
                    <div class="flex flex-wrap gap-2">
                        <template x-for="blk in ['R', 'A', 'B', 'C', 'D']" :key="blk">
                            <label class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white border border-slate-300 text-xs font-bold text-slate-700 cursor-pointer select-none">
                                <input type="checkbox"
                                       :value="blk"
                                       x-model="editAgendaForm.target_blocks"
                                       class="rounded text-emerald-700 focus:ring-emerald-600">
                                <span x-text="'Blok ' + blk"></span>
                            </label>
                        </template>
                    </div>
                </div>

                <!-- Pilihan Warga Diundang -->
                <div x-show="editAgendaForm.visibility === 'invite_only'" x-cloak class="pt-1 space-y-2">
                    <input type="text"
                           x-model="editAgendaInviteSearch"
                           placeholder="Cari nama warga atau nomor kavling..."
                           class="w-full rounded-xl border-slate-300 text-xs focus:border-emerald-600 focus:ring-emerald-600">
                    <div class="max-h-36 overflow-y-auto rounded-xl bg-white border border-slate-200 divide-y divide-slate-100">
                        <template x-for="res in filteredResidentOptions(editAgendaInviteSearch)" :key="res.id">
                            <label class="px-3 py-2 flex items-center justify-between gap-2 text-xs hover:bg-slate-50 cursor-pointer">
                                <div class="flex items-center gap-2 min-w-0">
                                    <input type="checkbox"
                                           :value="String(res.id)"
                                           x-model="editAgendaForm.invited_user_ids"
                                           class="rounded text-emerald-700 focus:ring-emerald-600">
                                    <span class="font-semibold text-slate-800 truncate" x-text="res.name"></span>
                                </div>
                                <span x-show="res.unit_codes"
                                      x-text="res.unit_codes"
                                      class="px-1.5 py-0.5 rounded bg-slate-100 font-mono text-[10px] font-bold text-slate-700 shrink-0"></span>
                            </label>
                        </template>
                    </div>
                    <div class="text-[11px] text-slate-500"
                         x-text="editAgendaForm.invited_user_ids.length + ' warga dipilih'"></div>
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

<!-- MODAL EDIT NOTULENSI RAPAT & TATA TERTIB -->
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
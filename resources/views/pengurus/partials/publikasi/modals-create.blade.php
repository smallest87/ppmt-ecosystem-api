<!-- MODAL TAMBAH PENGUMUMAN PAGUYUBAN -->
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

<!-- MODAL TAMBAH AGENDA & JADWAL LINGKUNGAN -->
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

<!-- MODAL TAMBAH NOTULENSI RAPAT & TATA TERTIB -->
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
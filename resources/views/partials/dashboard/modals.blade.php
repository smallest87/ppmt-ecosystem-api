<!-- MODAL BACA PENGUMUMAN LENGKAP -->
<div x-show="selectedAnnouncement !== null"
     x-cloak
     class="fixed inset-0 z-50 !mt-0 flex items-center justify-center p-3 sm:p-4 bg-slate-900/55 backdrop-blur-[2px] overflow-y-auto">
    <div @click.away="selectedAnnouncement = null"
         class="bg-white rounded-2xl shadow-2xl border border-slate-200/90 w-full max-w-[calc(100vw-1.5rem)] sm:max-w-lg max-h-[88dvh] flex flex-col overflow-hidden my-auto">

        <!-- Header Modal -->
        <div class="px-5 sm:px-6 py-3.5 border-b border-slate-100 flex items-center justify-between gap-3 shrink-0 bg-slate-50/60">
            <div class="flex flex-wrap items-center gap-2">
                <span x-show="selectedAnnouncement?.is_pinned"
                      class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-100 text-amber-900 border border-amber-300/80">
                    <svg class="w-2.5 h-2.5 text-amber-700" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M16 3a1 1 0 0 1 .707.293l4 4a1 1 0 0 1-.493 1.686l-3.437.687-2.61 2.61.556 3.337a1 1 0 0 1-1.693.88L9.5 12.963l-4.793 4.794a1 1 0 0 1-1.414-1.414L8.086 11.55 4.557 8.02a1 1 0 0 1 .88-1.693l3.337.556 2.61-2.61.687-3.437A1 1 0 0 1 13.757.343L16 3Z"/>
                    </svg>
                    Disematkan
                </span>
                <span :class="selectedAnnouncement?.badgeClass"
                      class="px-2.5 py-0.5 rounded-md text-[11px] font-bold border"
                      x-text="selectedAnnouncement?.category"></span>
                <span class="text-xs font-medium text-slate-400" x-text="selectedAnnouncement?.date"></span>
            </div>
            <button type="button"
                    @click="selectedAnnouncement = null"
                    aria-label="Tutup modal"
                    class="w-7 h-7 rounded-full flex items-center justify-center text-slate-400 hover:text-slate-700 hover:bg-slate-200/70 transition cursor-pointer shrink-0">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <!-- Konten Utama Pengumuman -->
        <div class="p-5 sm:p-6 space-y-4 overflow-y-auto">
            <div class="space-y-2">
                <h3 class="text-lg sm:text-xl font-extrabold text-slate-900 leading-snug tracking-tight"
                    x-text="selectedAnnouncement?.title"></h3>

                <div class="flex items-center gap-2 text-xs text-slate-500">
                    <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-800 font-bold text-[10px] inline-flex items-center justify-center shrink-0"
                          x-text="(selectedAnnouncement?.author || 'P').charAt(0).toUpperCase()"></span>
                    <span x-text="selectedAnnouncement?.author || 'Pengurus Paguyuban PPMT'"></span>
                </div>
            </div>

            <div class="pt-3.5 border-t border-slate-100 text-[13.5px] sm:text-sm text-slate-700 leading-relaxed whitespace-pre-line"
                 x-text="selectedAnnouncement?.body"></div>
        </div>

        <!-- Footer Modal -->
        <div class="px-5 sm:px-6 py-3.5 bg-slate-50/80 border-t border-slate-100 flex items-center justify-between gap-3 shrink-0">
            <button type="button"
                    @click="copyAnnouncementText(selectedAnnouncement)"
                    :class="copiedAnnouncement
                        ? 'border-emerald-300 bg-emerald-50 text-emerald-800'
                        : 'border-slate-200 bg-white hover:bg-emerald-50 hover:border-emerald-300 text-slate-700 hover:text-emerald-800'"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl border text-xs font-semibold transition cursor-pointer">
                <svg x-show="!copiedAnnouncement" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                </svg>
                <svg x-show="copiedAnnouncement" x-cloak class="w-3.5 h-3.5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
                <span x-text="copiedAnnouncement ? 'Tersalin' : 'Salin Isi'"></span>
            </button>

            <button type="button"
                    @click="selectedAnnouncement = null"
                    class="px-5 py-2 rounded-xl bg-emerald-900 hover:bg-emerald-950 text-white text-xs font-bold shadow-xs transition cursor-pointer">
                Tutup
            </button>
        </div>
    </div>
</div>

<!-- MODAL RIWAYAT IURAN SAMPAH KAVLING (LIVE API) -->
<div x-show="showDuesModal"
     x-cloak
     class="fixed inset-0 z-50 !mt-0 flex items-center justify-center p-3 sm:p-4 bg-slate-900/50 backdrop-blur-[1px] overflow-y-auto">
    <div @click.away="showDuesModal = false"
         class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-[calc(100vw-1.5rem)] sm:max-w-md max-h-[90dvh] flex flex-col overflow-hidden my-auto">
        <div class="px-5 sm:px-6 py-4 border-b border-slate-100 flex items-center justify-between gap-3 shrink-0">
            <h3 class="text-base font-bold text-slate-900">Riwayat Iuran Sampah Kavling</h3>
            <button type="button" @click="showDuesModal = false" class="text-slate-400 hover:text-slate-700 text-sm p-1 cursor-pointer">✕</button>
        </div>
        <div class="p-5 sm:p-6 space-y-4 overflow-y-auto">
            <template x-for="uDues in (treasuryReport?.my_units_dues || [])" :key="uDues.unit_id">
                <div class="space-y-2">
                    <div class="text-xs font-extrabold uppercase tracking-wider text-slate-500"
                         x-text="'Kavling ' + uDues.unit_code + ' (' + uDues.zone + ')'"></div>
                    <div class="divide-y divide-slate-100 border border-slate-200 rounded-xl overflow-hidden">
                        <template x-for="row in uDues.history" :key="row.period_month">
                            <div class="px-4 py-3 flex items-center justify-between text-xs bg-white">
                                <div>
                                    <div class="font-bold text-slate-900" x-text="row.period_label"></div>
                                    <div class="text-slate-500 mt-0.5"
                                         x-text="row.paid_at ? ('Dibayar ' + row.paid_at + ' (' + (row.payment_method === 'transfer' ? 'Transfer' : 'Tunai') + ')') : ('Jatuh tempo ' + row.due_date)"></div>
                                </div>
                                <div class="text-right">
                                    <div class="font-bold text-slate-800"
                                         x-text="row.status === 'exempt' ? 'Rp 0' : formatRupiah(row.amount)"></div>
                                    <span :class="{
                                            'bg-emerald-50 text-emerald-700 border-emerald-200': row.status === 'paid',
                                            'bg-amber-50 text-amber-800 border-amber-200': row.status === 'unpaid',
                                            'bg-slate-100 text-slate-600 border-slate-200': row.status === 'exempt'
                                          }"
                                          class="inline-block mt-0.5 px-2 py-0.5 rounded-full text-[10px] font-bold border"
                                          x-text="row.status === 'paid' ? 'Lunas' : (row.status === 'exempt' ? 'Dibebaskan' : 'Belum Bayar')">
                                    </span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </div>
        <div class="px-5 sm:px-6 py-3.5 bg-slate-50 border-t border-slate-100 flex items-center justify-between shrink-0">
            <a href="/komunitas?tab=kas" class="text-xs font-bold text-emerald-700 hover:underline">
                Buka Laporan Kas Lengkap &rarr;
            </a>
            <button type="button" @click="showDuesModal = false"
                    class="px-4 py-2 rounded-xl bg-slate-900 text-white text-xs font-semibold cursor-pointer">
                Tutup
            </button>
        </div>
    </div>
</div>

<!-- MODAL TERBITKAN KABAR & BERITA WARGA (KHUSUS PENGURUS) -->
<div x-show="showContributeModal && canPublishNews"
     x-cloak
     class="fixed inset-0 z-50 !mt-0 flex items-center justify-center p-3 sm:p-4 bg-slate-900/55 backdrop-blur-[2px] overflow-y-auto">
    <div @click.away="showContributeModal = false"
         class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-[calc(100vw-1.5rem)] sm:max-w-lg max-h-[90dvh] flex flex-col overflow-hidden my-auto">

        <div class="px-5 sm:px-6 py-4 border-b border-slate-100 flex items-center justify-between shrink-0">
            <div>
                <h3 class="text-base font-bold text-slate-900">Terbitkan Kabar &amp; Berita Warga</h3>
                <p class="text-xs text-slate-500 mt-0.5">Kabar yang diterbitkan langsung tayang ke Beranda Warga PPMT.</p>
            </div>
            <button type="button" @click="showContributeModal = false" class="text-slate-400 hover:text-slate-700 p-1 cursor-pointer">✕</button>
        </div>

        <form @submit.prevent="submitNewsContribution()" class="p-5 sm:p-6 space-y-3.5 overflow-y-auto">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Rubrik Kabar *</label>
                    <select x-model="newsDraft.category" class="w-full rounded-xl border-slate-300 text-xs">
                        <option value="Kegiatan Warga">Kegiatan Warga</option>
                        <option value="Fasilitas Umum">Fasilitas Umum</option>
                        <option value="Keamanan & Lingkungan">Keamanan &amp; Lingkungan</option>
                        <option value="Sosial & Kekeluargaan">Sosial &amp; Kekeluargaan</option>
                        <option value="Info UMKM">Info UMKM</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Posisi Tampilan</label>
                    <select x-model="newsDraft.layout" class="w-full rounded-xl border-slate-300 text-xs">
                        <option value="hero">Berita Utama (Hero)</option>
                        <option value="thumbnail">Kartu Foto (Thumbnail)</option>
                        <option value="grid">Grid 2 Kolom</option>
                        <option value="text">Kilas Kabar (Teks Saja)</option>
                    </select>
                </div>
            </div>

            <div x-show="approvedUnits.length > 1">
                <label class="block text-xs font-semibold text-slate-700 mb-1">Atas Nama Kavling</label>
                <select x-model="newsDraft.unit_id" class="w-full rounded-xl border-slate-300 text-xs">
                    <template x-for="m in approvedUnits" :key="m.id">
                        <option :value="m.unit_id" x-text="'Kavling ' + m.unit.code"></option>
                    </template>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Judul Berita (Min. 8 Karakter) *</label>
                <input type="text"
                       x-model="newsDraft.title"
                       required
                       minlength="8"
                       maxlength="180"
                       placeholder="Contoh: Pemasangan Lampu Jalan Blok C Rampung Swadaya"
                       class="w-full rounded-xl border-slate-300 text-xs">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Foto Dokumentasi (Opsional, Maks. 3 MB)</label>
                <input type="file"
                       x-ref="newsPhotoInput"
                       @change="handleNewsPhotoChange($event)"
                       accept="image/jpeg,image/png,image/webp"
                       class="block w-full text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-800 hover:file:bg-emerald-100 cursor-pointer">
                <div x-show="newsPhotoPreview" class="mt-2 relative rounded-xl overflow-hidden border border-slate-200 max-h-40">
                    <img :src="newsPhotoPreview" alt="Pratinjau foto" class="w-full h-36 object-cover">
                    <button type="button"
                            @click="clearNewsPhoto()"
                            class="absolute top-2 right-2 px-2 py-0.5 rounded-lg bg-slate-900/75 text-white text-[11px] font-bold cursor-pointer">
                        Hapus Foto
                    </button>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Isi Laporan / Berita (Min. 20 Karakter) *</label>
                <textarea x-model="newsDraft.body"
                          rows="4"
                          required
                          minlength="20"
                          placeholder="Tuliskan kronologi, waktu, dan detail kegiatan atau informasi lingkungan..."
                          class="w-full rounded-xl border-slate-300 text-xs"></textarea>
            </div>

            <div class="pt-1 flex items-center justify-end gap-2">
                <button type="button" @click="showContributeModal = false"
                        class="px-4 py-2 rounded-xl border border-slate-300 text-xs font-semibold text-slate-700 hover:bg-slate-50 cursor-pointer">
                    Batal
                </button>
                <button type="submit"
                        :disabled="submittingNews"
                        class="px-5 py-2 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold disabled:opacity-50 cursor-pointer">
                    <span x-show="!submittingNews">Terbitkan Kabar</span>
                    <span x-show="submittingNews">Menerbitkan...</span>
                </button>
            </div>
        </form>
    </div>
</div>
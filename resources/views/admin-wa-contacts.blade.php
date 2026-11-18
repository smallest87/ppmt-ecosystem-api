@extends('layouts.app')

@section('title', 'Pemadupadanan Kontak Grup WA — Panel Pengurus PPMT')
@section('subpage_title', 'Pemadupadanan Kontak WA')
@section('subpage_back_url', '/pengurus')

@section('content')
<div x-data="ppmtWaReconcilePage()"
     x-init="init()"
     @keydown.escape.window="editingWaContact = null"
     class="max-w-6xl mx-auto px-4 py-6 sm:py-8 space-y-6">

    <!-- Loading State -->
    <div x-show="loading" class="bg-white rounded-2xl p-12 text-center text-slate-500 border border-slate-200">
        Memverifikasi hak akses Admin Sistem...
    </div>

    <div x-show="!loading && user?.is_operator_aplikasi" class="space-y-6">

        <!-- Notifikasi Global -->
        <div x-show="toastMessage"
             :class="toastType === 'error' ? 'bg-rose-50 text-rose-800 border-rose-200' : 'bg-emerald-50 text-emerald-800 border-emerald-200'"
             class="p-4 rounded-xl border text-sm font-medium flex justify-between items-center shadow-sm">
            <span x-text="toastMessage"></span>
            <button type="button" @click="toastMessage = ''" class="text-xs opacity-70 hover:opacity-100 cursor-pointer">✕</button>
        </div>

        <!-- Header Halaman -->
        <section class="bg-indigo-950 text-white rounded-2xl p-5 sm:p-6 shadow-sm flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div class="space-y-1.5">
                <div class="inline-flex items-center gap-2 px-3 py-0.5 rounded-full bg-indigo-800/90 text-indigo-200 text-[11px] font-bold uppercase tracking-wider">
                    <span>Sub-Modul Panel Pengurus &bull; Khusus Admin Sistem</span>
                </div>
                <h1 class="text-xl sm:text-2xl font-extrabold">Pemadupadanan &amp; Pelabelan Kontak Grup WhatsApp</h1>
                <p class="text-xs text-indigo-200 max-w-2xl leading-relaxed">
                    Kelola impor CSV Google Contacts dari <strong>Grup Warga PPMT</strong> &amp; <strong>Grup Pesona Mandiri</strong> (sumber otomatis tercatat sebagai <em>"data dari grup whatsapp"</em>), padukan dengan database warga, serta sematkan label otomatis maupun manual.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2 shrink-0">
                <a href="/pengurus"
                   class="px-3.5 py-2 rounded-xl bg-indigo-900 hover:bg-indigo-800 border border-indigo-700 text-indigo-100 text-xs font-semibold transition">
                    ← Kembali ke Panel Pengurus
                </a>
                <button type="button"
                        @click="reconcileWaContacts()"
                        :disabled="reconcilingWa"
                        class="inline-flex items-center justify-center gap-1.5 px-4 py-2 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-950 text-xs font-extrabold shadow-sm transition cursor-pointer disabled:opacity-50">
                    <span x-show="!reconcilingWa">↻ Padupadankan Ulang dengan Data Warga</span>
                    <span x-show="reconcilingWa">Memproses...</span>
                </button>
            </div>
        </section>

        <!-- Konten Utama Pemadupadanan -->
        <section class="bg-white rounded-2xl p-5 sm:p-6 shadow-sm border border-slate-200 space-y-5">

            <!-- Baris Impor CSV & Statistik Ringkas -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-4 items-start">
                <!-- Form Upload CSV (5 Kolom) -->
                <form @submit.prevent="importWaCsv()"
                      class="lg:col-span-5 bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-3">
                    <div class="text-xs font-bold text-slate-900">Unggah File CSV Kontak Grup WhatsApp</div>

                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">Sumber Grup WhatsApp *</label>
                        <select x-model="waImportGroup" class="w-full rounded-xl border-slate-300 text-xs">
                            <option value="Grup Warga PPMT">Grup Warga PPMT</option>
                            <option value="Grup Pesona Mandiri">Grup Pesona Mandiri</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11px] font-semibold text-slate-700 mb-1">File CSV Google Contacts *</label>
                        <input type="file"
                               x-ref="waCsvInput"
                               accept=".csv,text/csv"
                               required
                               class="w-full text-xs text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-700 file:text-white hover:file:bg-emerald-800 cursor-pointer">
                    </div>

                    <button type="submit"
                            :disabled="importingWa"
                            class="w-full py-2 px-4 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold transition cursor-pointer disabled:opacity-50">
                        <span x-show="!importingWa">+ Impor &amp; Padupadankan CSV</span>
                        <span x-show="importingWa">Mengimpor &amp; Menggabungkan Nomor...</span>
                    </button>
                </form>

                <!-- Ringkasan Statistik Kontak (7 Kolom) -->
                <div class="lg:col-span-7 grid grid-cols-2 sm:grid-cols-5 gap-2.5">
                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200">
                        <div class="text-[10px] font-bold uppercase text-slate-500">Nomor Unik</div>
                        <div class="text-xl font-extrabold text-slate-900 mt-1" x-text="waStats?.total ?? 0"></div>
                        <div class="text-[10px] text-slate-500 mt-0.5">Gabungan grup</div>
                    </div>
                    <div class="p-3.5 rounded-xl bg-indigo-50/70 border border-indigo-200">
                        <div class="text-[10px] font-bold uppercase text-indigo-700">Di 2 Grup</div>
                        <div class="text-xl font-extrabold text-indigo-900 mt-1" x-text="waStats?.both_groups ?? 0"></div>
                        <div class="text-[10px] text-indigo-700 mt-0.5">Irisan anggota</div>
                    </div>
                    <div class="p-3.5 rounded-xl bg-emerald-50/70 border border-emerald-200">
                        <div class="text-[10px] font-bold uppercase text-emerald-700">Sudah Padan</div>
                        <div class="text-xl font-extrabold text-emerald-800 mt-1" x-text="waStats?.matched ?? 0"></div>
                        <div class="text-[10px] text-emerald-700 mt-0.5">Terhubung portal</div>
                    </div>
                    <div class="p-3.5 rounded-xl bg-amber-50/70 border border-amber-200">
                        <div class="text-[10px] font-bold uppercase text-amber-800">Belum Padan</div>
                        <div class="text-xl font-extrabold text-amber-700 mt-1" x-text="waStats?.unmatched ?? 0"></div>
                        <div class="text-[10px] text-amber-700 mt-0.5">Belum terpetakan</div>
                    </div>
                    <div class="p-3.5 rounded-xl bg-slate-100/80 border border-slate-200 col-span-2 sm:col-span-1">
                        <div class="text-[10px] font-bold uppercase text-slate-500">Non-Warga</div>
                        <div class="text-xl font-extrabold text-slate-700 mt-1" x-text="waStats?.ignored ?? 0"></div>
                        <div class="text-[10px] text-slate-500 mt-0.5">Diabaikan</div>
                    </div>
                </div>
            </div>

            <!-- Filter Daftar Kontak WA -->
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-2.5 pt-1">
                <input type="text"
                       x-model="waSearch"
                       placeholder="Cari nomor HP, nama kontak, kavling, atau label..."
                       class="sm:col-span-2 rounded-xl border-slate-300 text-xs focus:border-indigo-600 focus:ring-indigo-600">

                <select x-model="waFilterStatus" class="rounded-xl border-slate-300 text-xs">
                    <option value="">Semua Status Padan</option>
                    <option value="unmatched">Belum Padan / Belum Terdaftar</option>
                    <option value="matched_auto">Padan Otomatis (Nomor Cocok)</option>
                    <option value="matched_manual">Padan Manual (Admin)</option>
                    <option value="ignored">Diabaikan / Non-Warga</option>
                </select>

                <select x-model="waFilterGroup" class="rounded-xl border-slate-300 text-xs">
                    <option value="">Semua Sumber Grup</option>
                    <option value="both">Ada di Kedua Grup WA</option>
                    <option value="Grup Warga PPMT">Grup Warga PPMT</option>
                    <option value="Grup Pesona Mandiri">Grup Pesona Mandiri</option>
                </select>
            </div>

            <!-- Daftar Item Kontak WA -->
            <div x-show="filteredWaContacts.length === 0"
                 class="p-8 rounded-xl bg-slate-50 border border-dashed border-slate-300 text-center text-xs text-slate-500">
                Belum ada data kontak WhatsApp yang sesuai filter. Unggah file CSV dari Google Contacts untuk memulai.
            </div>

            <div x-show="filteredWaContacts.length > 0"
                 class="divide-y divide-slate-100 border border-slate-200 rounded-xl overflow-hidden">
                <template x-for="c in filteredWaContacts" :key="c.id">
                    <div class="p-3.5 sm:p-4 flex flex-col lg:flex-row lg:items-center justify-between gap-3 hover:bg-slate-50/70">
                        <div class="space-y-1.5 min-w-0">
                            <!-- Baris 1: Nomor, Nama Kontak WA, Status Padan -->
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="px-2 py-0.5 rounded bg-slate-900 text-white font-mono text-xs font-bold"
                                      x-text="c.phone"></span>
                                <span class="text-xs sm:text-sm font-bold text-slate-900"
                                      x-text="c.wa_display_name || '(Tanpa Nama Kontak)'"></span>

                                <span :class="{
                                        'bg-emerald-100 text-emerald-800 border-emerald-200': c.reconciliation_status === 'matched_auto',
                                        'bg-sky-100 text-sky-800 border-sky-200': c.reconciliation_status === 'matched_manual',
                                        'bg-amber-100 text-amber-800 border-amber-200': c.reconciliation_status === 'unmatched',
                                        'bg-slate-200 text-slate-600 border-slate-300': c.reconciliation_status === 'ignored'
                                      }"
                                      class="px-2 py-0.5 rounded-full text-[10px] font-bold border"
                                      x-text="formatWaReconStatus(c.reconciliation_status)">
                                </span>
                            </div>

                            <!-- Baris 2: Keterangan Sumber & Hasil Padan Warga/Kavling -->
                            <div class="text-[11px] text-slate-500 flex flex-wrap items-center gap-x-2 gap-y-1">
                                <span class="italic" x-text="'Sumber: ' + c.source_note"></span>
                                <span>&bull;</span>
                                <span class="font-semibold text-slate-700" x-text="c.groups.join(' + ')"></span>

                                <template x-if="c.matched_user">
                                    <span class="text-emerald-700 font-bold"
                                          x-text="'• Akun Portal: ' + c.matched_user.name + (c.matched_user.is_verified ? ' (Aktif)' : ' (Pending)')"></span>
                                </template>

                                <template x-if="c.matched_unit">
                                    <span class="px-1.5 py-0.5 rounded bg-indigo-50 text-indigo-800 border border-indigo-200 font-bold"
                                          x-text="'Kavling ' + c.matched_unit.code"></span>
                                </template>
                            </div>

                            <!-- Baris 3: Lencana Label Otomatis + Label Manual -->
                            <div class="flex flex-wrap items-center gap-1.5 pt-0.5">
                                <template x-for="lbl in (c.auto_labels || [])" :key="'auto-' + c.id + '-' + lbl">
                                    <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-600 border border-slate-200 text-[10px] font-medium"
                                          x-text="'Auto: ' + lbl"></span>
                                </template>

                                <template x-for="mlbl in (c.manual_labels || [])" :key="'man-' + c.id + '-' + mlbl">
                                    <span class="px-2 py-0.5 rounded-md bg-amber-100 text-amber-900 border border-amber-300 text-[10px] font-bold"
                                          x-text="'★ ' + mlbl"></span>
                                </template>
                            </div>

                            <div x-show="c.admin_notes"
                                 class="text-[11px] text-slate-600 bg-slate-100/80 px-2.5 py-1 rounded-lg border border-slate-200/80"
                                 x-text="'Catatan: ' + c.admin_notes"></div>
                        </div>

                        <!-- Tombol Aksi Admin Sistem -->
                        <div class="flex flex-wrap items-center gap-1.5 shrink-0">
                            <button type="button"
                                    @click="openWaLabelModal(c)"
                                    class="px-3 py-1.5 rounded-lg border border-indigo-200 bg-indigo-50 hover:bg-indigo-100 text-indigo-800 text-xs font-bold transition cursor-pointer">
                                Label &amp; Padankan
                            </button>

                            <a x-show="!c.matched_user && c.reconciliation_status !== 'ignored'"
                               :href="buildAssistedRegisterUrl(c)"
                               class="px-3 py-1.5 rounded-lg bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-semibold transition">
                                + Daftarkan Warga →
                            </a>
                        </div>
                    </div>
                </template>
            </div>
        </section>
    </div>

    <!-- ===================================================================== -->
    <!-- MODAL PELABELAN & PEMADUPADANAN MANUAL KONTAK WA (ADMIN SISTEM)       -->
    <!-- ===================================================================== -->
    <div x-show="editingWaContact !== null"
         x-cloak
         class="fixed inset-0 z-50 !mt-0 flex items-center justify-center p-3 sm:p-4 bg-slate-900/50 backdrop-blur-[1px] overflow-y-auto">
        <div @click.away="editingWaContact = null"
             class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-[calc(100vw-1.5rem)] sm:max-w-lg max-h-[90dvh] flex flex-col overflow-hidden my-auto">
            <div class="px-5 sm:px-6 py-4 border-b border-slate-100 flex items-center justify-between gap-3 shrink-0">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Pelabelan &amp; Padankan Kontak WA</h3>
                    <p class="text-[11px] font-mono text-slate-500"
                       x-text="editingWaContact ? (editingWaContact.phone + ' • ' + (editingWaContact.groups || []).join(', ')) : ''"></p>
                </div>
                <button type="button" @click="editingWaContact = null" class="text-slate-400 hover:text-slate-700 text-sm p-1 cursor-pointer">✕</button>
            </div>

            <form @submit.prevent="saveWaContactLabeling()" class="p-5 sm:p-6 space-y-4 overflow-y-auto">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Identifikasi Kontak</label>
                    <input type="text" x-model="waEditForm.wa_display_name" maxlength="180"
                           class="w-full rounded-xl border-slate-300 text-xs sm:text-sm">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Status Pemadupadanan</label>
                        <select x-model="waEditForm.reconciliation_status" class="w-full rounded-xl border-slate-300 text-xs">
                            <option value="unmatched">Belum Padan</option>
                            <option value="matched_manual">Padan Manual (Warga Teridentifikasi)</option>
                            <option value="matched_auto">Padan Otomatis</option>
                            <option value="ignored">Abaikan (Developer / Vendor / Non-Warga)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Tautkan ke Kavling</label>
                        <select x-model="waEditForm.matched_unit_id" class="w-full rounded-xl border-slate-300 text-xs">
                            <option value="">-- Belum Ditautkan Kavling --</option>
                            <template x-for="u in allUnits" :key="'wa-u-' + u.id">
                                <option :value="u.id" x-text="u.code + ' (' + u.zone + ')'"></option>
                            </template>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Tautkan ke Akun Warga Terdaftar (Jika Pakai Nomor Berbeda)</label>
                    <select x-model="waEditForm.matched_user_id" class="w-full rounded-xl border-slate-300 text-xs">
                        <option value="">-- Tidak Ditautkan ke Akun Warga --</option>
                        <template x-for="r in verifiedResidents" :key="'wa-r-' + r.id">
                            <option :value="r.id" x-text="(r.display_name || r.name) + ' (' + r.units.map(u => u.unit_code).join(', ') + ')'"></option>
                        </template>
                    </select>
                </div>

                <!-- Label Manual (Preset + Kustom) -->
                <div class="space-y-2">
                    <label class="block text-xs font-semibold text-slate-700">Label Manual Admin Sistem</label>

                    <div class="flex flex-wrap gap-1.5">
                        <template x-for="preset in ['Pemilik Rumah', 'Penyewa', 'Nomor Kedua (Pasangan)', 'Belum Teridentifikasi', 'Developer / Marketing', 'Vendor / Non-Warga']" :key="preset">
                            <button type="button"
                                    @click="toggleWaManualLabel(preset)"
                                    :class="waEditForm.manual_labels.includes(preset)
                                        ? 'bg-amber-400 text-slate-950 border-amber-500 font-bold'
                                        : 'bg-slate-100 text-slate-700 border-slate-200 hover:bg-slate-200'"
                                    class="px-2.5 py-1 rounded-lg border text-[11px] transition cursor-pointer"
                                    x-text="(waEditForm.manual_labels.includes(preset) ? '✓ ' : '+ ') + preset">
                            </button>
                        </template>
                    </div>

                    <div class="flex items-center gap-2 pt-1">
                        <input type="text"
                               x-model="waCustomLabelInput"
                               @keydown.enter.prevent="addCustomWaLabel()"
                               placeholder="Ketik label kustom lain lalu klik Tambah..."
                               class="flex-1 rounded-xl border-slate-300 text-xs">
                        <button type="button"
                                @click="addCustomWaLabel()"
                                class="px-3 py-2 rounded-xl bg-slate-800 text-white text-xs font-semibold cursor-pointer">
                            Tambah
                        </button>
                    </div>

                    <div x-show="waEditForm.manual_labels.length > 0" class="flex flex-wrap gap-1.5 pt-1">
                        <template x-for="lbl in waEditForm.manual_labels" :key="'sel-' + lbl">
                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-amber-100 text-amber-900 border border-amber-300 text-[11px] font-bold">
                                <span x-text="lbl"></span>
                                <button type="button" @click="toggleWaManualLabel(lbl)" class="hover:text-rose-700 cursor-pointer">✕</button>
                            </span>
                        </template>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan Admin (Opsional)</label>
                    <textarea x-model="waEditForm.admin_notes" rows="2"
                              placeholder="Misal: Nomor istri Pak Budi C-19, atau nomor lama sebelum ganti kartu..."
                              class="w-full rounded-xl border-slate-300 text-xs"></textarea>
                </div>

                <div class="pt-2 flex items-center justify-end gap-2 border-t border-slate-100">
                    <button type="button" @click="editingWaContact = null"
                            class="px-4 py-2 rounded-xl border border-slate-300 text-slate-700 text-xs font-semibold cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" :disabled="savingWaContact"
                            class="px-5 py-2 rounded-xl bg-indigo-700 hover:bg-indigo-800 text-white text-xs font-bold shadow-sm cursor-pointer">
                        <span x-show="!savingWaContact">Simpan Label &amp; Padanan</span>
                        <span x-show="savingWaContact">Menyimpan...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function ppmtWaReconcilePage() {
    return {
        token: localStorage.getItem('ppmt_token'),
        user: null,
        loading: true,
        toastMessage: '',
        toastType: 'success',
        waContacts: [],
        waStats: null,
        allUnits: [],
        verifiedResidents: [],
        waImportGroup: 'Grup Warga PPMT',
        importingWa: false,
        reconcilingWa: false,
        waSearch: '',
        waFilterStatus: '',
        waFilterGroup: '',
        editingWaContact: null,
        savingWaContact: false,
        waCustomLabelInput: '',
        waEditForm: {
            id: null,
            wa_display_name: '',
            reconciliation_status: 'unmatched',
            matched_unit_id: '',
            matched_user_id: '',
            manual_labels: [],
            admin_notes: ''
        },

        get filteredWaContacts() {
            const q = (this.waSearch || '').trim().toLowerCase();
            return this.waContacts.filter(c => {
                if (this.waFilterStatus && c.reconciliation_status !== this.waFilterStatus) return false;
                if (this.waFilterGroup === 'both' && (c.groups || []).length < 2) return false;
                if (this.waFilterGroup && this.waFilterGroup !== 'both' && !(c.groups || []).includes(this.waFilterGroup)) return false;

                if (q) {
                    const haystack = [
                        c.phone,
                        c.wa_display_name,
                        c.matched_unit?.code,
                        c.matched_user?.name,
                        c.admin_notes,
                        ...(c.auto_labels || []),
                        ...(c.manual_labels || [])
                    ].filter(Boolean).join(' ').toLowerCase();

                    if (!haystack.includes(q)) return false;
                }
                return true;
            });
        },

        headers() {
            return {
                'Authorization': 'Bearer ' + this.token,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            };
        },

        formatWaReconStatus(status) {
            const labels = {
                'unmatched': 'Belum Padan',
                'matched_auto': 'Padan Otomatis',
                'matched_manual': 'Padan Manual',
                'ignored': 'Diabaikan / Non-Warga'
            };
            return labels[status] || status;
        },

        buildAssistedRegisterUrl(c) {
            const cleanName = (c.wa_display_name || '')
                .replace(/^PPMT\s+([A-D]-?\d+[A-B]?\s+)?/i, '')
                .replace(/\b\d{4}\b/g, '')
                .trim();

            const params = new URLSearchParams();
            if (c.phone) params.set('prefill_phone', c.phone);
            if (cleanName || c.wa_display_name) params.set('prefill_name', cleanName || c.wa_display_name);
            const unitCode = c.matched_unit?.code || c.detected_unit_code || '';
            if (unitCode) params.set('prefill_unit', unitCode);

            return '/pengurus?' + params.toString() + '#assisted-register';
        },

        async init() {
            if (!this.token) {
                window.location.href = '/';
                return;
            }
            try {
                const res = await fetch('/api/v1/me', { headers: this.headers() });
                if (!res.ok) throw new Error('Unauthorized');
                const json = await res.json();
                this.user = json.data;
            } catch (e) {
                localStorage.removeItem('ppmt_token');
                window.location.href = '/';
                return;
            } finally {
                this.loading = false;
            }

            if (!this.user?.is_operator_aplikasi) {
                window.location.href = '/pengurus';
                return;
            }

            await Promise.all([
                this.loadWaContacts(),
                this.loadLookupData()
            ]);
        },

        async loadLookupData() {
            const [dirRes, unitsRes] = await Promise.all([
                fetch('/api/v1/residents/directory', { headers: this.headers() }),
                fetch('/api/v1/public/units')
            ]);
            if (dirRes.ok) {
                const dirJson = await dirRes.json();
                this.verifiedResidents = dirJson.data || [];
            }
            if (unitsRes.ok) {
                const unitsJson = await unitsRes.json();
                this.allUnits = (unitsJson.data || []).filter(u => u.unit_type !== 'fasum');
            }
        },

        async loadWaContacts() {
            const res = await fetch('/api/v1/admin/wa-contacts', { headers: this.headers() });
            if (res.ok) {
                const json = await res.json();
                this.waStats = json.data?.stats || null;
                this.waContacts = json.data?.contacts || [];
            }
        },

        async importWaCsv() {
            const fileInput = this.$refs.waCsvInput;
            if (!fileInput || !fileInput.files || !fileInput.files[0]) return;

            this.importingWa = true;
            try {
                const formData = new FormData();
                formData.append('file', fileInput.files[0]);
                formData.append('group_name', this.waImportGroup);

                const res = await fetch('/api/v1/admin/wa-contacts/import', {
                    method: 'POST',
                    headers: {
                        'Authorization': 'Bearer ' + this.token,
                        'Accept': 'application/json'
                    },
                    body: formData
                });
                const json = await res.json();
                if (res.ok) {
                    this.toastType = 'success';
                    this.toastMessage = json.message;
                    fileInput.value = '';
                    await this.loadWaContacts();
                } else {
                    this.toastType = 'error';
                    this.toastMessage = json.message || 'Gagal mengimpor file CSV kontak.';
                }
            } finally {
                this.importingWa = false;
            }
        },

        async reconcileWaContacts() {
            this.reconcilingWa = true;
            try {
                const res = await fetch('/api/v1/admin/wa-contacts/reconcile', {
                    method: 'POST',
                    headers: this.headers()
                });
                const json = await res.json();
                if (res.ok) {
                    this.toastType = 'success';
                    this.toastMessage = json.message;
                    await this.loadWaContacts();
                }
            } finally {
                this.reconcilingWa = false;
            }
        },

        openWaLabelModal(c) {
            this.waCustomLabelInput = '';
            this.waEditForm = {
                id: c.id,
                wa_display_name: c.wa_display_name || '',
                reconciliation_status: c.reconciliation_status || 'unmatched',
                matched_unit_id: c.matched_unit?.id || '',
                matched_user_id: c.matched_user?.id || '',
                manual_labels: [...(c.manual_labels || [])],
                admin_notes: c.admin_notes || ''
            };
            this.editingWaContact = c;
        },

        toggleWaManualLabel(label) {
            const idx = this.waEditForm.manual_labels.indexOf(label);
            if (idx >= 0) {
                this.waEditForm.manual_labels.splice(idx, 1);
            } else {
                this.waEditForm.manual_labels.push(label);
            }
        },

        addCustomWaLabel() {
            const val = (this.waCustomLabelInput || '').trim();
            if (!val) return;
            if (!this.waEditForm.manual_labels.includes(val)) {
                this.waEditForm.manual_labels.push(val);
            }
            this.waCustomLabelInput = '';
        },

        async saveWaContactLabeling() {
            if (!this.waEditForm.id) return;
            this.savingWaContact = true;
            try {
                const res = await fetch(`/api/v1/admin/wa-contacts/${this.waEditForm.id}`, {
                    method: 'PATCH',
                    headers: this.headers(),
                    body: JSON.stringify({
                        wa_display_name: this.waEditForm.wa_display_name || null,
                        reconciliation_status: this.waEditForm.reconciliation_status,
                        matched_unit_id: this.waEditForm.matched_unit_id ? Number(this.waEditForm.matched_unit_id) : null,
                        matched_user_id: this.waEditForm.matched_user_id ? Number(this.waEditForm.matched_user_id) : null,
                        manual_labels: this.waEditForm.manual_labels,
                        admin_notes: this.waEditForm.admin_notes || null
                    })
                });
                const json = await res.json();
                if (res.ok) {
                    this.editingWaContact = null;
                    this.toastType = 'success';
                    this.toastMessage = json.message;
                    await this.loadWaContacts();
                } else {
                    this.toastType = 'error';
                    this.toastMessage = json.message || 'Gagal menyimpan pelabelan kontak.';
                }
            } finally {
                this.savingWaContact = false;
            }
        }
    }
}
</script>
@endsection
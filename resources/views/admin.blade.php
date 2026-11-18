@extends('layouts.app')

@section('title', 'Panel Pengurus PPMT — PPMT Digital')
@section('subpage_title', 'Panel Pengurus')
@section('subpage_back_url', '/dashboard')

@section('content')
<div x-data="ppmtAdminPanel()" x-init="init()" @keydown.escape.window="editingAnnouncement = null; editingMinute = null; editingAgenda = null; editingNews = null" class="max-w-6xl mx-auto px-4 py-6 sm:py-8 space-y-6">

    <!-- Loading State -->
    <div x-show="loading" class="bg-white rounded-2xl p-12 text-center text-slate-500 border border-slate-200">
        Memverifikasi mandat jabatan pengurus PPMT...
    </div>

    <div x-show="!loading && user" class="space-y-6">

        <!-- Notifikasi Global -->
        <div x-show="toastMessage"
             :class="toastType === 'error' ? 'bg-rose-50 text-rose-800 border-rose-200' : 'bg-emerald-50 text-emerald-800 border-emerald-200'"
             class="p-4 rounded-xl border text-sm font-medium flex justify-between items-center shadow-sm">
            <span x-text="toastMessage"></span>
            <button @click="toastMessage = ''" class="text-xs opacity-70 hover:opacity-100 cursor-pointer">✕</button>
        </div>

        <!-- Header Panel Pengurus -->
        <section class="bg-indigo-950 text-white rounded-2xl p-6 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="space-y-1.5">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-800/80 text-indigo-200 text-xs font-semibold">
                    <span>&#9733; Ruang Kerja Tata Kelola &amp; Administrasi PPMT</span>
                </div>
                <h1 class="text-2xl font-extrabold"
                    x-text="'Halo, ' + (user?.display_name || user?.name)"></h1>
                <p class="text-xs text-indigo-200">
                    Anda masuk sebagai warga dengan mandat jabatan aktif. Seluruh aktivitas kepengurusan tercatat dalam jejak audit lingkungan.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <template x-for="gov in (user?.governance_roles || [])" :key="gov.id">
                    <span class="px-3 py-1 rounded-xl bg-amber-400 text-slate-950 text-xs font-bold shadow-sm"
                          x-text="gov.display_label">
                    </span>
                </template>
            </div>
        </section>

        <!-- BANNER NOTIFIKASI PENGESAHAN NIB SHM (Muncul otomatis jika ada warga mengirim NIB SHM) -->
        <section x-show="pendingOwnerships.length > 0"
                 class="bg-amber-50 border-2 border-amber-400 rounded-2xl p-4 sm:p-5 shadow-sm flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-0.5 rounded-full bg-amber-500 text-slate-950 text-[11px] font-extrabold">
                        &#128196; <span x-text="pendingOwnerships.length"></span> Pengajuan Bukti NIB SHM
                    </span>
                    <span class="text-xs font-bold text-amber-950">Menunggu Pemeriksaan &amp; Pengesahan Pengurus</span>
                </div>
                <p class="text-xs text-amber-900">
                    Terdapat warga yang telah menginputkan Nomor NIB / SHM untuk verifikasi kepemilikan kavling di wilayah cakupan Anda.
                </p>
            </div>
            <a href="#antrean-nib-shm"
               class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-slate-950 text-xs font-extrabold transition shrink-0 text-center">
                Periksa &amp; Sahkan Sekarang ↓
            </a>
        </section>

        <!-- ================================================================= -->
        <!-- STATS STRIP: RINGKASAN KONDISI HUNIAN & ADMINISTRASI              -->
        <!-- ================================================================= -->
        <section x-show="registryStats !== null" class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200 shadow-sm">
                <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Kavling Terisi</div>
                <div class="mt-1.5 flex items-baseline gap-1.5">
                    <span class="text-2xl font-extrabold text-emerald-700" x-text="registryStats?.filled_units ?? 0"></span>
                    <span class="text-xs font-semibold text-slate-400" x-text="'/ ' + (registryStats?.total_units ?? 0) + ' unit'"></span>
                </div>
                <div class="text-[11px] text-slate-500 mt-1"
                     x-text="(registryStats?.occupied_units ?? 0) + ' Dihuni • ' + (registryStats?.rented_units ?? 0) + ' Disewa'">
                </div>
            </div>

            <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200 shadow-sm">
                <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Warga Terverifikasi</div>
                <div class="mt-1.5 flex items-baseline gap-1.5">
                    <span class="text-2xl font-extrabold text-slate-900" x-text="registryStats?.verified_residents ?? 0"></span>
                    <span class="text-xs font-semibold text-slate-400">akun aktif</span>
                </div>
                <div class="text-[11px] text-slate-500 mt-1"
                     x-text="(registryStats?.unclaimed_units ?? 0) + ' kavling belum terklaim'">
                </div>
            </div>

            <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200 shadow-sm">
                <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Antrean Verifikasi</div>
                <div class="mt-1.5 flex items-baseline gap-1.5">
                    <span :class="(pendingApprovals.length + pendingOwnerships.length) > 0 ? 'text-amber-600' : 'text-slate-900'"
                          class="text-2xl font-extrabold"
                          x-text="pendingApprovals.length + pendingOwnerships.length"></span>
                    <span class="text-xs font-semibold text-slate-400">berkas</span>
                </div>
                <div class="text-[11px] text-slate-500 mt-1"
                     x-text="pendingApprovals.length + ' warga baru • ' + pendingOwnerships.length + ' NIB SHM'">
                </div>
            </div>

            <div class="bg-white rounded-2xl p-4 sm:p-5 border border-slate-200 shadow-sm">
                <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Papan &amp; Arsip</div>
                <div class="mt-1.5 flex items-baseline gap-1.5">
                    <span class="text-2xl font-extrabold text-indigo-700" x-text="registryStats?.published_announcements ?? 0"></span>
                    <span class="text-xs font-semibold text-slate-400">pengumuman</span>
                </div>
                <div class="text-[11px] text-slate-500 mt-1"
                     x-text="(registryStats?.total_minutes ?? 0) + ' dokumen notulensi & tatib'">
                </div>
            </div>
        </section>

        <!-- ================================================================= -->
        <!-- MODUL ANTREAN VERIFIKASI NIB SHM (LANGSUNG TERLIHAT DI ATAS)      -->
        <!-- ================================================================= -->
        <section id="antrean-nib-shm"
                 x-show="pendingOwnerships.length > 0"
                 class="bg-white rounded-2xl p-5 sm:p-6 shadow-sm border-2 border-amber-300 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-3">
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-base font-bold text-slate-900">Antrean Pengesahan Bukti Kepemilikan (NIB SHM)</h2>
                        <span class="px-2.5 py-0.5 rounded-full bg-amber-500 text-slate-950 text-xs font-extrabold"
                              x-text="pendingOwnerships.length"></span>
                    </div>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Cocokkan nomor NIB / SHM yang diinputkan warga dengan data kepemilikan fisik kavling sebelum mengesahkan.
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
                <template x-for="item in pendingOwnerships" :key="item.membership_id">
                    <div class="p-4 rounded-xl bg-amber-50/70 border border-amber-300 flex flex-col justify-between gap-3">
                        <div class="space-y-1.5 text-xs">
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <span class="px-2.5 py-0.5 rounded-lg bg-slate-900 text-white font-extrabold"
                                          x-text="item.unit_code"></span>
                                    <span class="font-bold text-slate-900 text-sm" x-text="item.name"></span>
                                </div>
                                <span class="text-[11px] text-slate-500" x-text="item.submitted_at"></span>
                            </div>

                            <div class="text-slate-600"
                                 x-text="item.owner_role_label + ' • ' + item.zone + ' • Kontak: ' + item.contact"></div>

                            <div class="p-2.5 rounded-lg bg-white border border-amber-200 font-mono text-xs text-slate-900">
                                Nomor NIB / SHM: <strong class="text-emerald-800" x-text="item.nib_shm_plain"></strong>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-1 border-t border-amber-200/70">
                            <button type="button"
                                    @click="verifyUnitOwnership(item.unit_code, item, 'approve_shm')"
                                    class="px-3.5 py-1.5 rounded-lg bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold shadow-xs transition cursor-pointer">
                                ✓ Sahkan NIB SHM
                            </button>
                        </div>
                    </div>
                </template>
            </div>
        </section>

        <!-- ================================================================= -->
        <!-- MODUL BENDAHARA: KAS PAGUYUBAN & IURAN SAMPAH (Rp 20.000 / Tgl 1) -->
        <!-- ================================================================= -->
        <section x-show="user?.can_view_treasury && treasury !== null" class="space-y-6">

            <!-- Ringkasan Saldo Kas & Progres Iuran Sampah -->
            <div class="bg-emerald-950 text-white rounded-2xl p-5 sm:p-6 shadow-sm space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-emerald-800/80 pb-3">
                    <div>
                        <div class="text-xs font-bold uppercase tracking-wider text-emerald-300">Pembukuan Keuangan Paguyuban PPMT</div>
                        <h2 class="text-lg font-extrabold">Kas Umum &amp; Iuran Sampah Bulanan (Rp 20.000 / Tgl 1)</h2>
                    </div>
                    <div class="flex items-center gap-2">
                        <label class="text-xs text-emerald-200 font-semibold">Periode Bulan:</label>
                        <input type="month"
                               x-model="treasuryMonth"
                               @change="loadTreasury()"
                               class="rounded-xl border-emerald-700 bg-emerald-900 text-white text-xs font-bold focus:border-amber-400 focus:ring-amber-400">
                    </div>
                </div>

                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                    <div class="bg-emerald-900/70 rounded-xl p-4 border border-emerald-800">
                        <div class="text-[11px] text-emerald-300 font-semibold">Saldo Kas Paguyuban</div>
                        <div class="text-lg sm:text-xl font-extrabold text-amber-300 mt-1"
                             x-text="formatRupiah(treasury?.summary?.current_balance ?? 0)"></div>
                        <div class="text-[11px] text-emerald-200/80 mt-0.5">Akumulasi bersih saat ini</div>
                    </div>

                    <div class="bg-emerald-900/70 rounded-xl p-4 border border-emerald-800">
                        <div class="text-[11px] text-emerald-300 font-semibold" x-text="'Pemasukan (' + (treasury?.period_label || '') + ')'"></div>
                        <div class="text-lg sm:text-xl font-extrabold text-white mt-1"
                             x-text="formatRupiah(treasury?.summary?.month_income ?? 0)"></div>
                        <div class="text-[11px] text-emerald-200/80 mt-0.5">Iuran &amp; pemasukan kas</div>
                    </div>

                    <div class="bg-emerald-900/70 rounded-xl p-4 border border-emerald-800">
                        <div class="text-[11px] text-emerald-300 font-semibold" x-text="'Pengeluaran (' + (treasury?.period_label || '') + ')'"></div>
                        <div class="text-lg sm:text-xl font-extrabold text-rose-300 mt-1"
                             x-text="formatRupiah(treasury?.summary?.month_expense ?? 0)"></div>
                        <div class="text-[11px] text-emerald-200/80 mt-0.5">Operasional sampah &amp; fasum</div>
                    </div>

                    <div class="bg-emerald-900/70 rounded-xl p-4 border border-emerald-800">
                        <div class="text-[11px] text-emerald-300 font-semibold">Iuran Sampah Lunas</div>
                        <div class="text-lg sm:text-xl font-extrabold text-white mt-1">
                            <span x-text="treasury?.summary?.paid_units ?? 0"></span>
                            <span class="text-xs font-normal text-emerald-300" x-text="'/ ' + (treasury?.summary?.billable_units ?? 0) + ' rumah'"></span>
                        </div>
                        <div class="text-[11px] text-emerald-200/80 mt-0.5"
                             x-text="'Terkumpul: ' + formatRupiah(treasury?.summary?.collected_dues_amount ?? 0)"></div>
                    </div>
                </div>
            </div>

            <!-- Baris 2 Bendahara: Kartu Iuran Sampah (7 Kolom) & Buku Kas Umum (5 Kolom) -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

                <!-- Kartu Iuran Sampah per Kavling (7 Kolom) -->
                <div class="lg:col-span-7 bg-white rounded-2xl p-5 sm:p-6 shadow-sm border border-slate-200 space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-3">
                        <div>
                            <h3 class="text-base font-bold text-slate-900"
                                x-text="'Kartu Iuran Sampah — ' + (treasury?.period_label || '')"></h3>
                            <p class="text-xs text-slate-500 mt-0.5">
                                Tarif <strong>Rp 20.000 / rumah</strong> &bull; Jatuh tempo tiap <strong>tanggal 1</strong> (<span x-text="treasury?.due_date_formatted"></span>).
                            </p>
                        </div>
                        <span class="px-2.5 py-1 rounded-full bg-amber-50 text-amber-800 border border-amber-200 text-xs font-bold shrink-0"
                              x-text="(treasury?.summary?.unpaid_units ?? 0) + ' Belum Lunas'"></span>
                    </div>

                    <!-- Filter Kartu Iuran -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <input type="text"
                               x-model="duesSearch"
                               placeholder="Cari kode kavling atau nama warga..."
                               class="rounded-xl border-slate-300 text-xs focus:border-emerald-600 focus:ring-emerald-600">
                        <select x-model="duesFilterStatus" class="rounded-xl border-slate-300 text-xs">
                            <option value="">Semua Kavling (Terisi &amp; Kosong)</option>
                            <option value="unpaid">Belum Lunas (Wajib Tagih)</option>
                            <option value="paid">Sudah Lunas</option>
                            <option value="vacant">Kavling Kosong / Belum Dihuni</option>
                            <option value="exempt">Dibebaskan</option>
                        </select>
                    </div>

                    <div class="max-h-[34rem] overflow-y-auto divide-y divide-slate-100 border border-slate-200 rounded-xl">
                        <template x-for="row in filteredDuesUnits" :key="row.unit_id">
                            <div class="p-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5 hover:bg-slate-50/80">
                                <div class="space-y-0.5 min-w-0">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <span class="px-2 py-0.5 rounded bg-slate-900 text-white text-xs font-extrabold"
                                              x-text="row.code"></span>
                                        <span class="text-xs font-bold text-slate-900 truncate"
                                              x-text="row.resident_name || 'Belum ada penghuni terdaftar'"></span>
                                        <span :class="{
                                                'bg-emerald-100 text-emerald-800 border-emerald-200': row.status === 'paid',
                                                'bg-rose-50 text-rose-700 border-rose-200': row.status === 'unpaid',
                                                'bg-amber-50 text-amber-800 border-amber-200': row.status === 'exempt',
                                                'bg-slate-100 text-slate-500 border-slate-200': row.status === 'vacant'
                                              }"
                                              class="px-2 py-0.5 rounded-full text-[10px] font-bold border"
                                              x-text="formatDuesStatus(row.status)">
                                        </span>
                                    </div>
                                    <div class="text-[11px] text-slate-500">
                                        <span x-text="row.zone"></span>
                                        <template x-if="row.status === 'paid'">
                                            <span class="text-emerald-700 font-semibold"
                                                  x-text="' • Lunas ' + (row.payment_method === 'transfer' ? 'Transfer' : 'Tunai') + (row.paid_at ? ' (' + row.paid_at + ')' : '')"></span>
                                        </template>
                                        <template x-if="row.status === 'unpaid'">
                                            <span class="text-rose-600 font-semibold"> • Tagihan Rp 20.000</span>
                                        </template>
                                    </div>
                                </div>

                                <!-- Tombol Aksi Cepat Bendahara -->
                                <div x-show="user?.can_manage_treasury" class="flex flex-wrap items-center gap-1.5 shrink-0">
                                    <template x-if="row.status === 'unpaid' || row.status === 'vacant'">
                                        <div class="flex items-center gap-1.5">
                                            <button type="button"
                                                    @click="updateDuesStatus(row, 'paid', 'cash')"
                                                    class="px-2.5 py-1 rounded-lg bg-emerald-700 hover:bg-emerald-800 text-white text-[11px] font-bold transition cursor-pointer">
                                                ✓ Tunai
                                            </button>
                                            <button type="button"
                                                    @click="updateDuesStatus(row, 'paid', 'transfer')"
                                                    class="px-2.5 py-1 rounded-lg bg-sky-700 hover:bg-sky-800 text-white text-[11px] font-bold transition cursor-pointer">
                                                ✓ Transfer
                                            </button>
                                            <button x-show="row.status === 'unpaid'"
                                                    type="button"
                                                    @click="updateDuesStatus(row, 'exempt')"
                                                    class="px-2 py-1 rounded-lg border border-slate-200 hover:bg-slate-100 text-slate-600 text-[11px] font-semibold transition cursor-pointer">
                                                Bebaskan
                                            </button>
                                        </div>
                                    </template>

                                    <template x-if="row.status === 'paid' || row.status === 'exempt'">
                                        <button type="button"
                                                @click="updateDuesStatus(row, 'unpaid')"
                                                class="px-2.5 py-1 rounded-lg border border-slate-200 hover:bg-rose-50 text-rose-600 text-[11px] font-semibold transition cursor-pointer">
                                            Batalkan
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Buku Kas Umum: Catat Mutasi & Riwayat Kas (5 Kolom) -->
                <div class="lg:col-span-5 space-y-5">

                    <!-- Form Catat Pemasukan / Pengeluaran Kas -->
                    <div x-show="user?.can_manage_treasury"
                         class="bg-white rounded-2xl p-5 shadow-sm border border-slate-200 space-y-3.5">
                        <div class="border-b border-slate-100 pb-3">
                            <h3 class="text-base font-bold text-slate-900">Catat Mutasi Buku Kas Umum</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Catat pengeluaran operasional sampah, PJU, keamanan, atau pemasukan kas lainnya.</p>
                        </div>

                        <form @submit.prevent="createLedgerEntry()" class="space-y-3">
                            <div class="grid grid-cols-2 gap-2.5">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Jenis Transaksi *</label>
                                    <select x-model="ledgerForm.type"
                                            @change="ledgerForm.category = ledgerForm.type === 'expense' ? 'pengangkutan_sampah' : 'sumbangan'"
                                            class="w-full rounded-xl border-slate-300 text-xs">
                                        <option value="expense">Pengeluaran Kas (-)</option>
                                        <option value="income">Pemasukan Kas (+)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal *</label>
                                    <input type="date" x-model="ledgerForm.entry_date" required
                                           class="w-full rounded-xl border-slate-300 text-xs">
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-2.5">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Kategori Pos *</label>
                                    <select x-model="ledgerForm.category" class="w-full rounded-xl border-slate-300 text-xs">
                                        <option value="pengangkutan_sampah">Operasional Angkut Sampah</option>
                                        <option value="keamanan">Keamanan &amp; Ronda</option>
                                        <option value="listrik_pju">Listrik PJU &amp; Pos</option>
                                        <option value="perawatan_fasum">Perawatan Fasum &amp; Drainase</option>
                                        <option value="sumbangan">Sumbangan / Dana Kegiatan</option>
                                        <option value="lainnya">Operasional Lainnya</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Metode Kas *</label>
                                    <select x-model="ledgerForm.payment_method" class="w-full rounded-xl border-slate-300 text-xs">
                                        <option value="cash">Tunai (Kas Bendahara)</option>
                                        <option value="transfer">Transfer Rekening</option>
                                    </select>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Nominal Rupiah (Rp) *</label>
                                <input type="number" x-model="ledgerForm.amount" required min="500" step="500"
                                       placeholder="Contoh: 150000"
                                       class="w-full rounded-xl border-slate-300 text-xs font-bold">
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Keterangan / Keperluan *</label>
                                <input type="text" x-model="ledgerForm.description" required maxlength="255"
                                       placeholder="Contoh: Setoran petugas angkut sampah bulan November"
                                       class="w-full rounded-xl border-slate-300 text-xs">
                            </div>

                            <button type="submit"
                                    :disabled="savingLedger"
                                    class="w-full py-2.5 px-4 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold shadow-sm transition cursor-pointer">
                                <span x-show="!savingLedger">Simpan ke Buku Kas</span>
                                <span x-show="savingLedger">Menyimpan...</span>
                            </button>
                        </form>
                    </div>

                    <!-- Riwayat Mutasi Kas Terbaru -->
                    <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-200 space-y-3">
                        <div class="flex items-center justify-between border-b border-slate-100 pb-2.5">
                            <h3 class="text-sm font-bold text-slate-900">Mutasi Buku Kas Terbaru</h3>
                            <span class="text-[11px] text-slate-400" x-text="(treasury?.ledger_entries?.length || 0) + ' transaksi'"></span>
                        </div>

                        <div x-show="(treasury?.ledger_entries?.length || 0) === 0"
                             class="p-6 rounded-xl bg-slate-50 border border-dashed border-slate-300 text-center text-xs text-slate-500">
                            Belum ada transaksi kas yang tercatat.
                        </div>

                        <div class="max-h-80 overflow-y-auto divide-y divide-slate-100">
                            <template x-for="entry in (treasury?.ledger_entries || [])" :key="entry.id">
                                <div class="py-2.5 flex items-start justify-between gap-2 text-xs">
                                    <div class="space-y-0.5 min-w-0">
                                        <div class="font-bold text-slate-900 leading-snug" x-text="entry.description"></div>
                                        <div class="text-[11px] text-slate-400"
                                             x-text="entry.entry_date_formatted + ' • ' + entry.category_label + ' (' + (entry.payment_method === 'transfer' ? 'Transfer' : 'Tunai') + ')'">
                                        </div>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <div :class="entry.type === 'income' ? 'text-emerald-700' : 'text-rose-600'"
                                             class="font-extrabold"
                                             x-text="(entry.type === 'income' ? '+ ' : '- ') + formatRupiah(entry.amount)">
                                        </div>
                                        <button x-show="user?.can_manage_treasury && !entry.is_auto_dues"
                                                type="button"
                                                @click="deleteLedgerEntry(entry)"
                                                class="text-[10px] text-rose-500 hover:underline cursor-pointer">
                                            Hapus
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ================================================================= -->
        <!-- MODUL 1: PAPAN PENGUMUMAN PAGUYUBAN (Sekretaris & Ketua)          -->
        <!-- ================================================================= -->
        <section x-show="canManageEditorial"
                 class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

            <!-- Form Terbitkan Pengumuman Baru (5 Kolom) -->
            <div class="lg:col-span-5 bg-white rounded-2xl p-5 sm:p-6 shadow-sm border border-slate-200 space-y-4">
                <div class="border-b border-slate-100 pb-3">
                    <h2 class="text-base font-bold text-slate-900">Terbitkan Pengumuman Paguyuban</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Pengumuman yang diterbitkan akan langsung tampil di <strong>Beranda Warga</strong>.</p>
                </div>

                <form @submit.prevent="createAnnouncement()" class="space-y-3.5">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Judul Pengumuman *</label>
                        <input type="text"
                               x-model="announcementForm.title"
                               required
                               maxlength="180"
                               placeholder="Contoh: Jadwal Kerja Bakti Drainase Blok A–D"
                               class="w-full rounded-xl border-slate-300 text-xs sm:text-sm focus:border-emerald-600 focus:ring-emerald-600">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Kategori / Label *</label>
                        <select x-model="announcementForm.category"
                                class="w-full rounded-xl border-slate-300 text-xs sm:text-sm focus:border-emerald-600 focus:ring-emerald-600">
                            <option value="info">Info Lingkungan</option>
                            <option value="penting">Penting / Mendesak</option>
                            <option value="kerja_bakti">Kegiatan Warga / Kerja Bakti</option>
                            <option value="musyawarah">Musyawarah Warga</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Isi Pengumuman Lengkap *</label>
                        <textarea x-model="announcementForm.body"
                                  required
                                  rows="4"
                                  placeholder="Tuliskan detail waktu, tempat, dan informasi penting bagi warga PPMT..."
                                  class="w-full rounded-xl border-slate-300 text-xs sm:text-sm focus:border-emerald-600 focus:ring-emerald-600"></textarea>
                    </div>

                    <label class="flex items-center gap-2.5 p-3 rounded-xl bg-amber-50/70 border border-amber-200 text-xs text-slate-700 cursor-pointer select-none">
                        <input type="checkbox" x-model="announcementForm.is_pinned" class="rounded text-emerald-700 focus:ring-emerald-600">
                        <span>Sematkan (<strong>Pin</strong>) di urutan paling atas papan pengumuman</span>
                    </label>

                    <button type="submit"
                            :disabled="savingAnnouncement"
                            class="w-full py-2.5 px-4 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white text-xs sm:text-sm font-bold shadow-sm transition disabled:opacity-50 cursor-pointer">
                        <span x-show="!savingAnnouncement">Terbitkan ke Papan Pengumuman</span>
                        <span x-show="savingAnnouncement">Menerbitkan...</span>
                    </button>
                </form>
            </div>

            <!-- Daftar Pengumuman Terbit & Arsip (7 Kolom) -->
            <div class="lg:col-span-7 bg-white rounded-2xl p-5 sm:p-6 shadow-sm border border-slate-200 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Kelola Papan Pengumuman Paguyuban</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Edit isi, sematkan pengumuman prioritas, atau arsipkan pengumuman lama.</p>
                    </div>
                    <span class="px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-700 border border-slate-200 text-xs font-bold shrink-0"
                          x-text="announcements.length + ' Total'"></span>
                </div>

                <div x-show="announcements.length === 0"
                     class="p-8 rounded-xl bg-slate-50 border border-dashed border-slate-300 text-center text-xs text-slate-500">
                    Belum ada pengumuman yang diterbitkan. Gunakan formulir di samping untuk membuat pengumuman pertama.
                </div>

                <div class="space-y-3">
                    <template x-for="item in announcements" :key="item.id">
                        <div :class="item.is_published ? 'bg-white border-slate-200' : 'bg-slate-50/80 border-slate-200 opacity-75'"
                             class="p-4 rounded-xl border space-y-2.5">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span x-show="item.is_pinned"
                                          class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-100 text-amber-900 border border-amber-300">
                                        &#128204; Disematkan
                                    </span>
                                    <span :class="item.badgeClass"
                                          class="px-2 py-0.5 rounded-md text-[10px] font-bold border"
                                          x-text="item.category">
                                    </span>
                                    <span :class="item.is_published ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-200 text-slate-700 border-slate-300'"
                                          class="px-2 py-0.5 rounded-md text-[10px] font-bold border"
                                          x-text="item.is_published ? 'Tayang' : 'Diarsipkan'">
                                    </span>
                                </div>
                                <span class="text-[11px] text-slate-400" x-text="item.date"></span>
                            </div>

                            <div>
                                <div class="text-sm font-bold text-slate-900" x-text="item.title"></div>
                                <p class="text-xs text-slate-600 line-clamp-2 mt-1 leading-relaxed" x-text="item.excerpt"></p>
                            </div>

                            <div class="pt-2 border-t border-slate-100 flex flex-wrap items-center justify-between gap-2 text-xs">
                                <span class="text-[11px] text-slate-400" x-text="'Oleh: ' + item.author"></span>

                                <div class="flex flex-wrap items-center gap-1.5">
                                    <button type="button"
                                            @click="toggleAnnouncementPin(item)"
                                            class="px-2.5 py-1 rounded-lg border border-slate-200 hover:bg-amber-50 text-slate-700 font-semibold transition cursor-pointer"
                                            x-text="item.is_pinned ? 'Lepas Pin' : 'Sematkan'">
                                    </button>
                                    <button type="button"
                                            @click="openEditAnnouncement(item)"
                                            class="px-2.5 py-1 rounded-lg border border-slate-200 hover:bg-slate-100 text-slate-700 font-semibold transition cursor-pointer">
                                        Edit
                                    </button>
                                    <button type="button"
                                            @click="toggleAnnouncementPublish(item)"
                                            class="px-2.5 py-1 rounded-lg border border-slate-200 hover:bg-indigo-50 text-indigo-700 font-semibold transition cursor-pointer"
                                            x-text="item.is_published ? 'Arsipkan' : 'Tayangkan'">
                                    </button>
                                    <button type="button"
                                            @click="deleteAnnouncement(item)"
                                            class="px-2.5 py-1 rounded-lg text-rose-600 hover:bg-rose-50 font-semibold transition cursor-pointer">
                                        Hapus
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </section>

        <!-- ================================================================= -->
        <!-- MODUL KELOLA KABAR & BERITA WARGA (Khusus Pengurus)               -->
        <!-- ================================================================= -->
        <section x-show="canManageEditorial"
                 class="bg-white rounded-2xl p-5 sm:p-6 shadow-sm border border-slate-200 space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3.5">
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-base sm:text-lg font-bold text-slate-900">Kelola Kabar &amp; Berita Warga</h2>
                        <span class="px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-700 border border-slate-200 text-xs font-bold"
                              x-text="newsArticles.length + ' Berita'"></span>
                    </div>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Sunting isi berita, ubah posisi tampilan di Beranda Warga (<strong>Hero Utama</strong>, <strong>Kartu Foto</strong>, <strong>Grid</strong>, atau <strong>Daftar Teks</strong>), atau hapus berita lama.
                    </p>
                </div>

                <a href="/dashboard"
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold shadow-xs transition shrink-0">
                    <span>+ Terbitkan dari Beranda</span>
                </a>
            </div>

            <div x-show="newsArticles.length === 0"
                 class="p-8 rounded-xl bg-slate-50 border border-dashed border-slate-300 text-center text-xs text-slate-500">
                Belum ada berita warga yang diterbitkan. Klik <strong>Terbitkan Kabar</strong> di Beranda Warga untuk membuat berita pertama.
            </div>

            <div x-show="newsArticles.length > 0" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <template x-for="art in newsArticles" :key="art.id">
                    <div class="p-4 rounded-xl border border-slate-200 bg-white flex flex-col justify-between gap-3">
                        <div class="space-y-2.5">
                            <div class="flex flex-wrap items-center justify-between gap-1.5">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200"
                                          x-text="art.category"></span>
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200"
                                          x-text="formatNewsLayout(art.layout)"></span>
                                </div>
                                <span class="text-[11px] text-slate-400" x-text="art.published_at"></span>
                            </div>

                            <div class="flex items-start gap-3">
                                <template x-if="art.thumbnail">
                                    <img :src="art.thumbnail"
                                         :alt="art.title"
                                         class="w-20 h-16 rounded-lg object-cover border border-slate-200 shrink-0 bg-slate-100">
                                </template>
                                <div class="min-w-0 space-y-1">
                                    <h3 class="text-sm font-bold text-slate-900 leading-snug" x-text="art.title"></h3>
                                    <p class="text-xs text-slate-600 line-clamp-2 leading-relaxed" x-text="art.excerpt"></p>
                                </div>
                            </div>
                        </div>

                        <div class="pt-2.5 border-t border-slate-100 flex flex-wrap items-center justify-between gap-2 text-xs">
                            <span class="text-[11px] text-slate-500 font-medium"
                                  x-text="'Oleh: ' + art.author + ' (Kavling ' + art.unit + ')'"></span>

                            <div class="flex flex-wrap items-center gap-1.5">
                                <a :href="'/berita/' + art.date_path + '/' + art.slug"
                                   target="_blank"
                                   class="px-2.5 py-1 rounded-lg border border-emerald-200 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 font-semibold transition">
                                    Lihat ↗
                                </a>
                                <button type="button"
                                        @click="openNewsEditModal(art)"
                                        class="px-2.5 py-1 rounded-lg border border-slate-300 bg-white hover:bg-slate-100 text-slate-700 font-semibold transition cursor-pointer">
                                    Edit
                                </button>
                                <button type="button"
                                        @click="deleteNewsArticle(art)"
                                        class="px-2.5 py-1 rounded-lg text-rose-600 hover:bg-rose-50 font-semibold transition cursor-pointer">
                                    Hapus
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </section>

        <!-- ================================================================= -->
        <!-- MODUL AGENDA & JADWAL LINGKUNGAN (Sekretaris & Ketua)             -->
        <!-- ================================================================= -->
        <section x-show="canManageEditorial"
                 class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

            <!-- Form Tambah Agenda & Jadwal Lingkungan (5 Kolom) -->
            <div class="lg:col-span-5 bg-white rounded-2xl p-5 sm:p-6 shadow-sm border border-slate-200 space-y-4">
                <div class="border-b border-slate-100 pb-3">
                    <h2 class="text-base font-bold text-slate-900">Agenda &amp; Jadwal Lingkungan</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Jadwal yang masih akan datang otomatis tampil di kartu <strong>Agenda Warga</strong> pada Beranda Warga.</p>
                </div>

                <form @submit.prevent="createAgenda()" class="space-y-3.5">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Kegiatan / Jadwal *</label>
                        <input type="text"
                               x-model="agendaForm.title"
                               required
                               maxlength="180"
                               placeholder="Contoh: Kerja Bakti Drainase & Perapian Taman Fasum"
                               class="w-full rounded-xl border-slate-300 text-xs sm:text-sm focus:border-emerald-600 focus:ring-emerald-600">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Kegiatan *</label>
                            <input type="date"
                                   x-model="agendaForm.event_date"
                                   required
                                   class="w-full rounded-xl border-slate-300 text-xs sm:text-sm focus:border-emerald-600 focus:ring-emerald-600">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Kategori Jadwal *</label>
                            <select x-model="agendaForm.category"
                                    class="w-full rounded-xl border-slate-300 text-xs sm:text-sm focus:border-emerald-600 focus:ring-emerald-600">
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
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Waktu / Jam *</label>
                            <input type="text"
                                   x-model="agendaForm.time_label"
                                   required
                                   maxlength="80"
                                   placeholder="Contoh: 06.30 – 09.30 WIB"
                                   class="w-full rounded-xl border-slate-300 text-xs sm:text-sm focus:border-emerald-600 focus:ring-emerald-600">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Lokasi Kegiatan *</label>
                            <input type="text"
                                   x-model="agendaForm.location"
                                   required
                                   maxlength="140"
                                   placeholder="Contoh: Fasum Tengah PPMT"
                                   class="w-full rounded-xl border-slate-300 text-xs sm:text-sm focus:border-emerald-600 focus:ring-emerald-600">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan Tambahan (Opsional)</label>
                        <textarea x-model="agendaForm.notes"
                                  rows="2"
                                  placeholder="Contoh: Dimohon warga membawa alat kebersihan masing-masing."
                                  class="w-full rounded-xl border-slate-300 text-xs sm:text-sm focus:border-emerald-600 focus:ring-emerald-600"></textarea>
                    </div>

                    <button type="submit"
                            :disabled="savingAgenda"
                            class="w-full py-2.5 px-4 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white text-xs sm:text-sm font-bold shadow-sm transition disabled:opacity-50 cursor-pointer">
                        <span x-show="!savingAgenda">+ Simpan Agenda Lingkungan</span>
                        <span x-show="savingAgenda">Menyimpan...</span>
                    </button>
                </form>
            </div>

            <!-- Daftar Agenda Mendatang & Riwayat Kegiatan (7 Kolom) -->
            <div class="lg:col-span-7 bg-white rounded-2xl p-5 sm:p-6 shadow-sm border border-slate-200 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Daftar Agenda &amp; Jadwal Lingkungan</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Agenda yang sudah lewat otomatis disembunyikan dari Beranda Warga namun tetap tersimpan di sini.</p>
                    </div>
                    <span class="px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-700 border border-slate-200 text-xs font-bold shrink-0"
                          x-text="agendas.length + ' Jadwal'"></span>
                </div>

                <div x-show="agendas.length === 0"
                     class="p-8 rounded-xl bg-slate-50 border border-dashed border-slate-300 text-center text-xs text-slate-500">
                    Belum ada agenda atau jadwal lingkungan yang dibuat.
                </div>

                <div class="space-y-3">
                    <template x-for="ag in agendas" :key="ag.id">
                        <div :class="ag.is_upcoming ? 'bg-white border-slate-200' : 'bg-slate-50/80 border-slate-200 opacity-75'"
                             class="p-4 rounded-xl border flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div class="flex items-start gap-3.5 min-w-0">
                                <div :class="ag.is_upcoming ? 'bg-emerald-50 border-emerald-200 text-emerald-900' : 'bg-slate-100 border-slate-200 text-slate-500'"
                                     class="w-12 h-12 rounded-xl border flex flex-col items-center justify-center shrink-0">
                                    <span class="text-[10px] font-bold uppercase leading-none" x-text="ag.dayShort"></span>
                                    <span class="text-base font-extrabold leading-tight mt-0.5" x-text="ag.dateNum"></span>
                                </div>

                                <div class="space-y-1 min-w-0">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <span :class="ag.badgeClass"
                                              class="px-2 py-0.5 rounded-md text-[10px] font-bold border"
                                              x-text="ag.category"></span>
                                        <span :class="ag.is_upcoming ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-200 text-slate-600 border-slate-300'"
                                              class="px-2 py-0.5 rounded-md text-[10px] font-bold border"
                                              x-text="ag.is_upcoming ? 'Tampil di Beranda' : 'Selesai'"></span>
                                        <span class="text-[11px] text-slate-400" x-text="ag.event_date_formatted"></span>
                                    </div>
                                    <div class="text-sm font-bold text-slate-900 leading-snug" x-text="ag.title"></div>
                                    <div class="text-xs text-slate-500" x-text="ag.time + ' • ' + ag.location"></div>
                                </div>
                            </div>

                            <div class="flex items-center gap-1.5 shrink-0 self-end sm:self-center text-xs">
                                <button type="button"
                                        @click="openEditAgenda(ag)"
                                        class="px-2.5 py-1 rounded-lg border border-slate-200 hover:bg-slate-100 text-slate-700 font-semibold transition cursor-pointer">
                                    Edit
                                </button>
                                <button type="button"
                                        @click="deleteAgenda(ag)"
                                        class="px-2.5 py-1 rounded-lg text-rose-600 hover:bg-rose-50 font-semibold transition cursor-pointer">
                                    Hapus
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </section>

        <!-- ================================================================= -->
        <!-- MODUL 2: ARSIP NOTULENSI RAPAT & TATA TERTIB (Sekretaris & Ketua) -->
        <!-- ================================================================= -->
        <section x-show="canManageEditorial"
                 class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

            <!-- Form Catat Notulensi / Tata Tertib (5 Kolom) -->
            <div class="lg:col-span-5 bg-white rounded-2xl p-5 sm:p-6 shadow-sm border border-slate-200 space-y-4">
                <div class="border-b border-slate-100 pb-3">
                    <h2 class="text-base font-bold text-slate-900">Catat Notulensi Rapat &amp; Tata Tertib</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Dokumentasikan hasil musyawarah warga atau aturan lingkungan agar menjadi rujukan resmi.</p>
                </div>

                <form @submit.prevent="createMinute()" class="space-y-3.5">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Judul Agenda / Dokumen *</label>
                        <input type="text"
                               x-model="minuteForm.title"
                               required
                               maxlength="180"
                               placeholder="Contoh: Kesepakatan Musyawarah Keamanan & Iuran Bulanan"
                               class="w-full rounded-xl border-slate-300 text-xs sm:text-sm focus:border-emerald-600 focus:ring-emerald-600">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Rapat / Penetapan *</label>
                            <input type="date"
                                   x-model="minuteForm.meeting_date"
                                   required
                                   class="w-full rounded-xl border-slate-300 text-xs sm:text-sm focus:border-emerald-600 focus:ring-emerald-600">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Kategori Dokumen *</label>
                            <select x-model="minuteForm.category"
                                    class="w-full rounded-xl border-slate-300 text-xs sm:text-sm focus:border-emerald-600 focus:ring-emerald-600">
                                <option value="musyawarah_warga">Musyawarah Warga</option>
                                <option value="tata_tertib">Tata Tertib Lingkungan</option>
                                <option value="rapat_pengurus">Rapat Pengurus</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Lokasi / Forum Pertemuan (Opsional)</label>
                        <input type="text"
                               x-model="minuteForm.location"
                               maxlength="120"
                               placeholder="Contoh: Fasum Tengah / Pos Utama PPMT"
                               class="w-full rounded-xl border-slate-300 text-xs sm:text-sm focus:border-emerald-600 focus:ring-emerald-600">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Poin Kesepakatan &amp; Isi Lengkap *</label>
                        <textarea x-model="minuteForm.decisions"
                                  required
                                  rows="5"
                                  placeholder="Tuliskan poin-poin keputusan musyawarah, aturan yang disepakati, atau tindak lanjut pengurus..."
                                  class="w-full rounded-xl border-slate-300 text-xs sm:text-sm focus:border-emerald-600 focus:ring-emerald-600"></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Visibilitas Dokumen *</label>
                        <select x-model="minuteForm.visibility"
                                class="w-full rounded-xl border-slate-300 text-xs sm:text-sm focus:border-emerald-600 focus:ring-emerald-600">
                            <option value="public_residents">Terbuka untuk Semua Warga PPMT (Tampil di Komunitas)</option>
                            <option value="internal_pengurus">Internal Pengurus Saja</option>
                        </select>
                    </div>

                    <button type="submit"
                            :disabled="savingMinute"
                            class="w-full py-2.5 px-4 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs sm:text-sm font-bold shadow-sm transition disabled:opacity-50 cursor-pointer">
                        <span x-show="!savingMinute">Simpan ke Arsip Notulensi</span>
                        <span x-show="savingMinute">Menyimpan...</span>
                    </button>
                </form>
            </div>

            <!-- Daftar Arsip Notulensi Rapat & Tata Tertib (7 Kolom) -->
            <div class="lg:col-span-7 bg-white rounded-2xl p-5 sm:p-6 shadow-sm border border-slate-200 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Arsip Notulensi Rapat &amp; Tata Tertib</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Rekam jejak keputusan resmi paguyuban yang tersusun kronologis.</p>
                    </div>
                    <span class="px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-700 border border-slate-200 text-xs font-bold shrink-0"
                          x-text="meetingMinutes.length + ' Dokumen'"></span>
                </div>

                <div x-show="meetingMinutes.length === 0"
                     class="p-8 rounded-xl bg-slate-50 border border-dashed border-slate-300 text-center text-xs text-slate-500">
                    Belum ada catatan notulensi rapat atau tata tertib yang disimpan.
                </div>

                <div class="space-y-3">
                    <template x-for="m in meetingMinutes" :key="m.id">
                        <div class="p-4 rounded-xl border border-slate-200 bg-white space-y-2.5">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span :class="m.badgeClass"
                                          class="px-2 py-0.5 rounded-md text-[10px] font-bold border"
                                          x-text="m.category">
                                    </span>
                                    <span :class="m.visibility === 'public_residents' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-700 border-slate-300'"
                                          class="px-2 py-0.5 rounded-md text-[10px] font-bold border"
                                          x-text="m.visibility === 'public_residents' ? 'Terbuka Warga' : 'Internal Pengurus'">
                                    </span>
                                </div>
                                <span class="text-[11px] font-medium text-slate-500"
                                      x-text="m.meeting_date_formatted + (m.location ? ' • ' + m.location : '')">
                                </span>
                            </div>

                            <div>
                                <div class="text-sm font-bold text-slate-900" x-text="m.title"></div>
                                <p class="text-xs text-slate-600 line-clamp-2 mt-1 leading-relaxed" x-text="m.excerpt"></p>
                            </div>

                            <div class="pt-2 border-t border-slate-100 flex flex-wrap items-center justify-between gap-2 text-xs">
                                <span class="text-[11px] text-slate-400" x-text="'Dicatat oleh: ' + m.author"></span>

                                <div class="flex items-center gap-1.5">
                                    <button type="button"
                                            @click="openEditMinute(m)"
                                            class="px-2.5 py-1 rounded-lg border border-slate-200 hover:bg-slate-100 text-slate-700 font-semibold transition cursor-pointer">
                                        Edit
                                    </button>
                                    <button type="button"
                                            @click="deleteMinute(m)"
                                            class="px-2.5 py-1 rounded-lg text-rose-600 hover:bg-rose-50 font-semibold transition cursor-pointer">
                                        Hapus
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </section>

        <!-- ================================================================= -->
        <!-- MODUL 3: VERIFIKASI WILAYAH & TATA KELOLA JABATAN                 -->
        <!-- ================================================================= -->
        <div x-show="showVerificationPanel || user?.is_global_governor"
             class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

            <!-- Kolom Kiri (5 Kolom): Antrean Verifikasi Sosial & Assisted Onboarding -->
            <div class="lg:col-span-5 space-y-6">

                <!-- Panel 1: Antrean Verifikasi Sosial -->
                <section x-show="showVerificationPanel"
                         class="bg-white rounded-2xl p-5 shadow-sm border border-slate-200 space-y-3">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <h2 class="text-base font-bold text-slate-900">Antrean Verifikasi Warga</h2>
                        <span class="px-2.5 py-0.5 rounded-full bg-amber-500 text-white text-xs font-bold"
                              x-text="pendingApprovals.length"></span>
                    </div>

                    <div x-show="pendingApprovals.length === 0" class="text-xs text-slate-500 py-4 text-center bg-slate-50 rounded-xl">
                        Tidak ada antrean verifikasi warga baru di wilayah cakupan Anda saat ini.
                    </div>

                    <div class="space-y-2.5">
                        <template x-for="item in pendingApprovals" :key="item.membership_id">
                            <div class="bg-amber-50/60 p-3.5 rounded-xl border border-amber-200 flex items-center justify-between gap-2">
                                <div class="text-xs space-y-1 min-w-0">
                                    <div class="font-bold text-slate-900" x-text="item.name + ' • Unit ' + item.unit_code"></div>
                                    <div class="text-slate-600"
                                        x-text="(item.phone ? 'WA: ' + item.phone : 'Username: @' + (item.username || '-')) + ' (' + item.sub_role.replace('_', ' ') + ')'">
                                    </div>
                                    <template x-if="item.guarantor_name">
                                        <div class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-emerald-100/80 border border-emerald-200 text-[11px] font-semibold text-emerald-900">
                                            <span>Penjamin:</span>
                                            <span x-text="item.guarantor_name"></span>
                                        </div>
                                    </template>
                                </div>
                                <button type="button" @click="approveResident(item)"
                                        class="px-3 py-1.5 rounded-lg bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-semibold shrink-0 cursor-pointer">
                                    ✓ Sahkan
                                </button>
                            </div>
                        </template>
                    </div>
                </section>

                <!-- Panel 2: Pendaftaran Langsung Tetangga Terkenal (Assisted Onboarding) -->
                <section id="assisted-register"
                         x-show="user?.is_global_governor"
                         class="bg-emerald-50/60 rounded-2xl p-5 shadow-sm border border-emerald-200 space-y-4">
                    <div class="border-b border-emerald-200/70 pb-3">
                        <h2 class="text-base font-bold text-emerald-950">Daftarkan Tetangga Terkenal</h2>
                        <p class="text-xs text-emerald-800">Daftarkan tetangga yang sudah Anda kenal secara langsung ke kavlingnya (otomatis aktif dengan status dasar <strong>Warga PPMT</strong>).</p>
                    </div>

                    <form @submit.prevent="submitAssistedRegister()" class="space-y-3">
                        <div class="space-y-2.5 p-3 rounded-xl bg-white/90 border border-emerald-200">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">
                                    Nomor WhatsApp Tetangga <span class="text-slate-400 font-normal">(Opsional jika isi Username)</span>
                                </label>
                                <input type="tel" x-model="assistForm.phone" placeholder="0812xxxxxxxx"
                                       class="w-full rounded-xl border-slate-300 text-xs">
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">
                                    Username <span class="text-slate-400 font-normal">(Opsional jika isi No. WA)</span>
                                </label>
                                <input type="text"
                                       x-model="assistForm.username"
                                       @input="assistForm.username = (assistForm.username || '').toLowerCase().replace(/[^a-z0-9._]/g, '')"
                                       placeholder="Contoh: paksugeng.b12a"
                                       class="w-full rounded-xl border-slate-300 text-xs font-mono lowercase">
                            </div>
                            <p class="text-[11px] text-emerald-800">Isi minimal salah satu untuk dipakai masuk ke aplikasi.</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Kata Sandi Sementara (Min. 8 Karakter) *</label>
                            <input type="text" x-model="assistForm.password" required minlength="8" placeholder="Contoh: ppmt12345"
                                   class="w-full rounded-xl border-slate-300 text-xs font-mono">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap Tetangga *</label>
                            <input type="text" x-model="assistForm.name" required placeholder="Contoh: Ahmad Fauzi"
                                   class="w-full rounded-xl border-slate-300 text-xs">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Kavling Tetangga *</label>
                            <select x-model="assistForm.unit_code" required class="w-full rounded-xl border-slate-300 text-xs">
                                <option value="">-- Pilih Kavling PPMT --</option>
                                <template x-for="u in allUnits" :key="u.id">
                                    <option :value="u.code"
                                            x-text="u.code + ' (' + u.zone + ') • ' + formatUnitStatus(u.occupancy_status)">
                                    </option>
                                </template>
                            </select>
                        </div>

                        <div class="space-y-2.5 p-3 rounded-xl bg-white/95 border border-emerald-200 text-xs">
                            <div class="font-bold text-slate-800">Peran Tetangga di Kavling (Pilih satu atau keduanya):</div>

                            <label class="flex items-start gap-2 cursor-pointer">
                                <input type="checkbox" x-model="assistForm.is_owner" class="mt-0.5 rounded text-amber-600 focus:ring-amber-500">
                                <div class="flex-1 space-y-1.5">
                                    <span class="font-semibold text-slate-900">Pemilik Kavling</span>
                                    <div x-show="assistForm.is_owner" class="space-y-1.5">
                                        <select x-model="assistForm.owner_role" class="w-full rounded-lg border-slate-300 text-xs">
                                            <option value="primary_owner">Pemilik Utama</option>
                                            <option value="co_owner">Pemilik Bersama</option>
                                        </select>
                                        <input type="text" x-model="assistForm.nib_shm"
                                               placeholder="Nomor NIB SHM (isi jika sudah diverifikasi)"
                                               class="w-full rounded-lg border-slate-300 text-xs font-mono uppercase">
                                    </div>
                                </div>
                            </label>

                            <label class="flex items-start gap-2 cursor-pointer pt-1 border-t border-slate-100">
                                <input type="checkbox" x-model="assistForm.is_occupant" class="mt-0.5 rounded text-emerald-600 focus:ring-emerald-600">
                                <div class="flex-1 space-y-1.5">
                                    <span class="font-semibold text-slate-900">Menghuni / Tinggal di Kavling</span>
                                    <div x-show="assistForm.is_occupant">
                                        <select x-model="assistForm.resident_role" class="w-full rounded-lg border-slate-300 text-xs">
                                            <option value="primary_resident">Penghuni Utama</option>
                                            <option value="family_member">Anggota Keluarga</option>
                                            <option value="co_resident">Penghuni Bersama</option>
                                            <option value="tenant" x-show="!assistForm.is_owner">Penyewa / Kontrak</option>
                                            <option value="caretaker" x-show="!assistForm.is_owner">Penjaga Rumah</option>
                                        </select>
                                    </div>
                                </div>
                            </label>
                        </div>

                        <button type="submit"
                                class="w-full py-2.5 px-4 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold transition cursor-pointer">
                            Daftarkan &amp; Aktifkan Warga
                        </button>
                    </form>
                </section>
            </div>

            <!-- Kolom Kanan (7 Kolom): Penyematan Jabatan & Hak Akses -->
            <section x-show="user?.is_global_governor"
                     class="lg:col-span-7 bg-white rounded-2xl p-6 shadow-sm border border-slate-200 space-y-5">
                <div class="border-b border-slate-100 pb-3">
                    <h2 class="text-lg font-bold text-slate-900">Penyematan Jabatan &amp; Hak Akses Warga</h2>
                    <p class="text-xs text-slate-500">
                        Wewenang penyematan jabatan mengikuti pemisahan peran tata kelola: <strong>Admin Sistem</strong> menetapkan infrastruktur &amp; pelantikan Ketua Paguyuban, sedangkan <strong>Ketua Paguyuban</strong> menyusun pengurus dan koordinator wilayah.
                    </p>
                </div>

                <form x-show="availableRoleOptions.length > 0"
                      @submit.prevent="assignGovernance()"
                      class="bg-indigo-50/60 p-4 rounded-xl border border-indigo-200 space-y-3">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Pilih Warga Terverifikasi *</label>
                            <select x-model="govForm.target_user_id" required class="w-full rounded-xl border-slate-300 text-xs">
                                <option value="">-- Pilih Warga PPMT --</option>
                                <template x-for="r in verifiedResidents" :key="r.id">
                                    <option :value="r.id" x-text="(r.display_name || r.name) + ' (' + r.units.map(u => u.unit_code).join(', ') + ')'"></option>
                                </template>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Jabatan Akses yang Disematkan *</label>
                            <select x-model="govForm.role_code" @change="updateGovScopeDefault()" class="w-full rounded-xl border-slate-300 text-xs">
                                <template x-for="opt in availableRoleOptions" :key="opt.code">
                                    <option :value="opt.code" x-text="opt.label"></option>
                                </template>
                            </select>
                        </div>
                    </div>

                    <div x-show="govForm.role_code === 'koordinator_zona'">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Pilih Cakupan Zona *</label>
                        <select x-model="govForm.scope_value" class="w-full rounded-xl border-slate-300 text-xs">
                            <option value="ZONA-1">ZONA-1 (R1-R2, A-1..16)</option>
                            <option value="ZONA-2">ZONA-2 (B-1..24)</option>
                            <option value="ZONA-3">ZONA-3 (A-17..25, C-1..14)</option>
                            <option value="ZONA-4">ZONA-4 (C-15..22, D-1..17)</option>
                        </select>
                    </div>

                    <div x-show="govForm.role_code === 'koordinator_blok'">
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Pilih Cakupan Blok *</label>
                        <select x-model="govForm.scope_value" class="w-full rounded-xl border-slate-300 text-xs">
                            <option value="R">Blok R (Ruko)</option>
                            <option value="A">Blok A</option>
                            <option value="B">Blok B</option>
                            <option value="C">Blok C</option>
                            <option value="D">Blok D</option>
                        </select>
                    </div>

                    <button type="submit"
                            class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-indigo-700 hover:bg-indigo-800 text-white text-xs font-bold transition cursor-pointer">
                        + Sematkan Jabatan Akses
                    </button>
                </form>

                <!-- Daftar Jabatan Aktif -->
                <div class="space-y-2.5">
                    <div class="text-xs font-bold uppercase tracking-wider text-slate-700">Daftar Warga Pemegang Jabatan Aktif</div>
                    <template x-for="item in governanceList" :key="item.id">
                        <div class="bg-slate-50 p-3.5 rounded-xl border border-slate-200 flex items-center justify-between gap-2 text-xs">
                            <div>
                                <div class="font-bold text-slate-900" x-text="item.user_name + ' (Kavling ' + item.unit_codes + ')'"></div>
                                <div class="text-indigo-700 font-semibold mt-0.5" x-text="item.display_label + ' • Disematkan oleh ' + item.assigned_by"></div>
                            </div>
                            <button x-show="item.can_revoke"
                                    type="button"
                                    @click="revokeGovernance(item.id)"
                                    class="px-3 py-1.5 rounded-lg text-xs text-rose-600 hover:bg-rose-100 font-semibold transition cursor-pointer">
                                Cabut
                            </button>
                        </div>
                    </template>
                </div>
            </section>

        </div>

        <!-- ================================================================= -->
        <!-- PINTASAN ADMIN SISTEM: RUANG PEMADUPADANAN KONTAK GRUP WHATSAPP   -->
        <!-- ================================================================= -->
        <section x-show="user?.is_operator_aplikasi"
                 class="bg-indigo-50/70 rounded-2xl p-5 shadow-sm border border-indigo-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="space-y-1">
                <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-indigo-100 text-indigo-800 text-[10px] font-bold uppercase tracking-wider">
                    Infrastruktur Data &bull; Khusus Admin Sistem
                </div>
                <h2 class="text-base font-bold text-slate-900">Pemadupadanan &amp; Pelabelan Kontak Grup WhatsApp</h2>
                <p class="text-xs text-slate-600">
                    Kelola impor CSV Google Contacts (<em>Grup Warga PPMT</em> &amp; <em>Grup Pesona Mandiri</em>), penyematan label otomatis/manual, dan pemadupadanan nomor warga pada halaman khusus.
                </p>
            </div>

            <a href="/pengurus/pemadupadanan-kontak"
               class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-700 hover:bg-indigo-800 text-white text-xs font-bold shadow-sm transition shrink-0">
                <span>Buka Pemadupadanan Kontak WA</span>
                <span>→</span>
            </a>
        </section>

        <!-- ================================================================= -->
        <!-- MODUL 4: BUKU INDUK & STATUS HUNIAN KAVLING (Seluruh Pengurus)    -->
        <!-- ================================================================= -->
        <section x-show="registryUnits.length > 0"
                 class="bg-white rounded-2xl p-5 sm:p-6 shadow-sm border border-slate-200 space-y-4">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-3 border-b border-slate-100 pb-4">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-base sm:text-lg font-bold text-slate-900">Buku Induk &amp; Status Hunian Kavling</h2>
                        <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200"
                              x-text="filteredRegistryUnits.length + ' Kavling'"></span>
                        <a href="/peta-kavling"
                           class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 transition">
                            Lihat Peta 91 Kavling ↗
                        </a>
                    </div>
                    <p class="text-xs text-slate-500 mt-0.5"
                       x-text="registryScopeIsCentral ? 'Menampilkan seluruh kavling hunian PPMT untuk pendataan & administrasi pengurus pusat.' : 'Menampilkan kavling hunian di dalam wilayah cakupan koordinasi Anda.'">
                    </p>
                </div>

                <!-- Baris Filter & Pencarian -->
                <div class="grid grid-cols-2 sm:flex sm:flex-wrap items-center gap-2">
                    <input type="text"
                           x-model="registrySearch"
                           placeholder="Cari kavling / nama..."
                           class="col-span-2 sm:w-44 rounded-xl border-slate-300 text-xs focus:border-emerald-600 focus:ring-emerald-600">

                    <select x-model="registryFilterBlock" class="rounded-xl border-slate-300 text-xs">
                        <option value="">Semua Blok</option>
                        <option value="R">Blok R</option>
                        <option value="A">Blok A</option>
                        <option value="B">Blok B</option>
                        <option value="C">Blok C</option>
                        <option value="D">Blok D</option>
                    </select>

                    <select x-model="registryFilterZone" class="rounded-xl border-slate-300 text-xs">
                        <option value="">Semua Zona</option>
                        <option value="ZONA-1">ZONA-1</option>
                        <option value="ZONA-2">ZONA-2</option>
                        <option value="ZONA-3">ZONA-3</option>
                        <option value="ZONA-4">ZONA-4</option>
                    </select>

                    <select x-model="registryFilterStatus" class="col-span-2 sm:col-span-1 rounded-xl border-slate-300 text-xs">
                        <option value="">Semua Status</option>
                        <option value="occupied">Berpenghuni</option>
                        <option value="rented">Disewakan</option>
                        <option value="pending">Menunggu Verifikasi</option>
                        <option value="unclaimed">Belum Terklaim</option>
                    </select>
                </div>
            </div>

            <div x-show="filteredRegistryUnits.length === 0"
                 class="p-8 rounded-xl bg-slate-50 border border-dashed border-slate-300 text-center text-xs text-slate-500">
                Tidak ada kavling yang cocok dengan kriteria pencarian atau filter saat ini.
            </div>

            <div x-show="filteredRegistryUnits.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3.5">
                <template x-for="u in filteredRegistryUnits" :key="u.id">
                    <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 space-y-2.5 flex flex-col justify-between">
                        <div class="space-y-2">
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <a :href="'/peta-kavling?unit=' + encodeURIComponent(u.code)"
                                       title="Lihat posisi kavling di Peta"
                                       class="px-2.5 py-1 rounded-lg bg-slate-900 hover:bg-emerald-800 text-white text-xs font-extrabold transition"
                                       x-text="u.code"></a>
                                    <span class="text-[11px] font-semibold text-slate-500" x-text="u.zone"></span>
                                </div>
                                <span :class="{
                                        'bg-emerald-100 text-emerald-800 border-emerald-200': u.occupancy_status === 'occupied',
                                        'bg-sky-100 text-sky-800 border-sky-200': u.occupancy_status === 'rented',
                                        'bg-amber-100 text-amber-800 border-amber-300': u.occupancy_status === 'pending',
                                        'bg-slate-200 text-slate-600 border-slate-300': u.occupancy_status === 'unclaimed'
                                      }"
                                      class="px-2 py-0.5 rounded-full text-[10px] font-bold border"
                                      x-text="formatUnitStatus(u.occupancy_status)">
                                </span>
                            </div>

                            <div x-show="u.members.length === 0" class="text-[11px] text-slate-400 italic py-1">
                                Belum ada warga terdaftar pada kavling ini.
                            </div>

                            <div x-show="u.members.length > 0" class="space-y-1.5">
                                <template x-for="m in u.members" :key="m.membership_id">
                                    <div class="p-2.5 rounded-lg bg-white border border-slate-200/90 text-xs">
                                        <div class="flex items-start justify-between gap-1.5">
                                            <div class="font-bold text-slate-900 leading-snug" x-text="m.name"></div>
                                            <span :class="m.is_approved ? 'text-emerald-700 bg-emerald-50 border-emerald-200' : 'text-amber-700 bg-amber-50 border-amber-200'"
                                                  class="px-1.5 py-0.5 rounded text-[9px] font-bold border shrink-0"
                                                  x-text="m.is_approved ? 'Aktif' : 'Pending'">
                                            </span>
                                        </div>
                                        <div class="text-[11px] text-slate-500 mt-0.5 flex flex-wrap items-center justify-between gap-1">
                                            <span x-text="m.sub_role_label"></span>
                                            <span class="font-mono text-[10px] text-slate-600" x-text="m.contact"></span>
                                        </div>
                                        <div x-show="m.valid_until" class="text-[10px] text-sky-700 font-semibold mt-0.5"
                                             x-text="'Masa sewa s/d: ' + m.valid_until">
                                        </div>

                                        <!-- Status Verifikasi Kepemilikan NIB SHM & Tombol Pengesahan Pengurus -->
                                        <div x-show="m.is_owner" class="mt-1.5 pt-1.5 border-t border-slate-100 space-y-1.5">
                                            <div class="flex flex-wrap items-center justify-between gap-1">
                                                <span x-show="m.ownership_verification_status === 'verified_shm'"
                                                      class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-900 border border-emerald-300 text-[10px] font-bold">
                                                    &#10003; NIB SHM Terverifikasi
                                                </span>
                                                <span x-show="m.ownership_verification_status !== 'verified_shm'"
                                                      class="px-2 py-0.5 rounded bg-amber-100 text-amber-950 border border-dashed border-amber-400 text-[10px] font-bold">
                                                    &#9888; Klaim Tanpa NIB SHM
                                                </span>

                                                <button x-show="m.can_verify_ownership && m.ownership_verification_status !== 'verified_shm'"
                                                        type="button"
                                                        @click="verifyUnitOwnership(u.code, m, 'approve_shm')"
                                                        class="px-2 py-0.5 rounded bg-emerald-700 hover:bg-emerald-800 text-white text-[10px] font-bold transition cursor-pointer">
                                                    Sahkan SHM
                                                </button>

                                                <button x-show="m.can_verify_ownership && m.ownership_verification_status === 'verified_shm'"
                                                        type="button"
                                                        @click="verifyUnitOwnership(u.code, m, 'revoke_shm')"
                                                        class="px-2 py-0.5 rounded border border-slate-200 hover:bg-rose-50 text-rose-600 text-[10px] font-semibold transition cursor-pointer">
                                                    Cabut SHM
                                                </button>
                                            </div>

                                            <div x-show="m.nib_shm_plain" class="text-[10px] font-mono text-slate-700 bg-slate-50 px-2 py-0.5 rounded border border-slate-200">
                                                NIB/SHM: <strong x-text="m.nib_shm_plain"></strong>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </section>
    </div>

    <!-- ===================================================================== -->
    <!-- MODAL EDIT KABAR & BERITA WARGA (KHUSUS PENGURUS)                     -->
    <!-- ===================================================================== -->
    <div x-show="editingNews !== null"
         x-cloak
         class="fixed inset-0 z-50 !mt-0 flex items-center justify-center p-3 sm:p-4 bg-slate-900/50 backdrop-blur-[1px] overflow-y-auto">
        <div @click.away="editingNews = null"
             class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-[calc(100vw-1.5rem)] sm:max-w-2xl max-h-[90dvh] flex flex-col overflow-hidden my-auto">
            <div class="px-5 sm:px-6 py-4 border-b border-slate-100 flex items-center justify-between gap-3 shrink-0">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Edit Kabar &amp; Berita Warga</h3>
                    <p class="text-[11px] text-slate-500"
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
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Judul Berita *</label>
                    <input type="text" x-model="editNewsForm.title" required minlength="8" maxlength="180"
                           class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Rubrik / Kategori *</label>
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
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Posisi Tampilan di Beranda *</label>
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
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Isi Lengkap Berita *</label>
                    <textarea x-model="editNewsForm.body" required minlength="20" rows="7"
                              class="w-full rounded-xl border-slate-300 text-sm leading-relaxed focus:border-emerald-600 focus:ring-emerald-600"></textarea>
                </div>

                <div class="pt-3 flex items-center justify-end gap-2 border-t border-slate-100">
                    <button type="button"
                            @click="editingNews = null"
                            class="px-4 py-2 rounded-xl border border-slate-300 text-slate-700 text-xs font-semibold cursor-pointer">
                        Batal
                    </button>
                    <button type="submit"
                            :disabled="savingNews"
                            class="px-5 py-2 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold shadow-sm cursor-pointer">
                        <span x-show="!savingNews">Simpan Perubahan</span>
                        <span x-show="savingNews">Menyimpan...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- MODAL EDIT PENGUMUMAN PAGUYUBAN                                       -->
    <!-- ===================================================================== -->
    <div x-show="editingAnnouncement !== null"
         x-cloak
         class="fixed inset-0 z-50 !mt-0 flex items-center justify-center p-3 sm:p-4 bg-slate-900/50 backdrop-blur-[1px] overflow-y-auto">
        <div @click.away="editingAnnouncement = null"
             class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-[calc(100vw-1.5rem)] sm:max-w-lg max-h-[90dvh] flex flex-col overflow-hidden my-auto">
            <div class="px-5 sm:px-6 py-4 border-b border-slate-100 flex items-center justify-between gap-3 shrink-0">
                <h3 class="text-base font-bold text-slate-900">Edit Pengumuman Paguyuban</h3>
                <button type="button" @click="editingAnnouncement = null" class="text-slate-400 hover:text-slate-700 text-sm p-1 cursor-pointer">✕</button>
            </div>

            <form @submit.prevent="saveEditedAnnouncement()" class="p-5 sm:p-6 space-y-4 overflow-y-auto">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Judul Pengumuman *</label>
                    <input type="text" x-model="editAnnouncementForm.title" required maxlength="180"
                           class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Kategori / Label *</label>
                    <select x-model="editAnnouncementForm.category"
                            class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                        <option value="info">Info Lingkungan</option>
                        <option value="penting">Penting / Mendesak</option>
                        <option value="kerja_bakti">Kegiatan Warga / Kerja Bakti</option>
                        <option value="musyawarah">Musyawarah Warga</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Isi Pengumuman Lengkap *</label>
                    <textarea x-model="editAnnouncementForm.body" required rows="6"
                              class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600"></textarea>
                </div>

                <label class="flex items-center gap-2.5 p-3 rounded-xl bg-amber-50/70 border border-amber-200 text-xs text-slate-700 cursor-pointer select-none">
                    <input type="checkbox" x-model="editAnnouncementForm.is_pinned" class="rounded text-emerald-700">
                    <span>Sematkan (<strong>Pin</strong>) di urutan paling atas</span>
                </label>

                <div class="pt-2 flex items-center justify-end gap-2 border-t border-slate-100">
                    <button type="button" @click="editingAnnouncement = null"
                            class="px-4 py-2 rounded-xl border border-slate-300 text-slate-700 text-xs font-semibold cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" :disabled="savingAnnouncement"
                            class="px-5 py-2 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold shadow-sm cursor-pointer">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- MODAL EDIT AGENDA & JADWAL LINGKUNGAN                                 -->
    <!-- ===================================================================== -->
    <div x-show="editingAgenda !== null"
         x-cloak
         class="fixed inset-0 z-50 !mt-0 flex items-center justify-center p-3 sm:p-4 bg-slate-900/50 backdrop-blur-[1px] overflow-y-auto">
        <div @click.away="editingAgenda = null"
             class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-[calc(100vw-1.5rem)] sm:max-w-lg max-h-[90dvh] flex flex-col overflow-hidden my-auto">
            <div class="px-5 sm:px-6 py-4 border-b border-slate-100 flex items-center justify-between gap-3 shrink-0">
                <h3 class="text-base font-bold text-slate-900">Edit Agenda &amp; Jadwal Lingkungan</h3>
                <button type="button" @click="editingAgenda = null" class="text-slate-400 hover:text-slate-700 text-sm p-1 cursor-pointer">✕</button>
            </div>

            <form @submit.prevent="saveEditedAgenda()" class="p-5 sm:p-6 space-y-4 overflow-y-auto">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Kegiatan / Jadwal *</label>
                    <input type="text" x-model="editAgendaForm.title" required maxlength="180"
                           class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Kegiatan *</label>
                        <input type="date" x-model="editAgendaForm.event_date" required
                               class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Kategori Jadwal *</label>
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
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Waktu / Jam *</label>
                        <input type="text" x-model="editAgendaForm.time_label" required maxlength="80"
                               class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Lokasi Kegiatan *</label>
                        <input type="text" x-model="editAgendaForm.location" required maxlength="140"
                               class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan Tambahan</label>
                    <textarea x-model="editAgendaForm.notes" rows="3"
                              class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600"></textarea>
                </div>

                <div class="pt-2 flex items-center justify-end gap-2 border-t border-slate-100">
                    <button type="button" @click="editingAgenda = null"
                            class="px-4 py-2 rounded-xl border border-slate-300 text-slate-700 text-xs font-semibold cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" :disabled="savingAgenda"
                            class="px-5 py-2 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold shadow-sm cursor-pointer">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- MODAL EDIT NOTULENSI RAPAT & TATA TERTIB                              -->
    <!-- ===================================================================== -->
    <div x-show="editingMinute !== null"
         x-cloak
         class="fixed inset-0 z-50 !mt-0 flex items-center justify-center p-3 sm:p-4 bg-slate-900/50 backdrop-blur-[1px] overflow-y-auto">
        <div @click.away="editingMinute = null"
             class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-[calc(100vw-1.5rem)] sm:max-w-lg max-h-[90dvh] flex flex-col overflow-hidden my-auto">
            <div class="px-5 sm:px-6 py-4 border-b border-slate-100 flex items-center justify-between gap-3 shrink-0">
                <h3 class="text-base font-bold text-slate-900">Edit Notulensi Rapat &amp; Tata Tertib</h3>
                <button type="button" @click="editingMinute = null" class="text-slate-400 hover:text-slate-700 text-sm p-1 cursor-pointer">✕</button>
            </div>

            <form @submit.prevent="saveEditedMinute()" class="p-5 sm:p-6 space-y-4 overflow-y-auto">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Judul Agenda / Dokumen *</label>
                    <input type="text" x-model="editMinuteForm.title" required maxlength="180"
                           class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Rapat / Penetapan *</label>
                        <input type="date" x-model="editMinuteForm.meeting_date" required
                               class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Kategori Dokumen *</label>
                        <select x-model="editMinuteForm.category"
                                class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                            <option value="musyawarah_warga">Musyawarah Warga</option>
                            <option value="tata_tertib">Tata Tertib Lingkungan</option>
                            <option value="rapat_pengurus">Rapat Pengurus</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Lokasi / Forum Pertemuan</label>
                    <input type="text" x-model="editMinuteForm.location" maxlength="120"
                           class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Poin Kesepakatan &amp; Isi Lengkap *</label>
                    <textarea x-model="editMinuteForm.decisions" required rows="6"
                              class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Visibilitas Dokumen *</label>
                    <select x-model="editMinuteForm.visibility"
                            class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                        <option value="public_residents">Terbuka untuk Semua Warga PPMT</option>
                        <option value="internal_pengurus">Internal Pengurus Saja</option>
                    </select>
                </div>

                <div class="pt-2 flex items-center justify-end gap-2 border-t border-slate-100">
                    <button type="button" @click="editingMinute = null"
                            class="px-4 py-2 rounded-xl border border-slate-300 text-slate-700 text-xs font-semibold cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" :disabled="savingMinute"
                            class="px-5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold shadow-sm cursor-pointer">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function ppmtAdminPanel() {
    const ALL_ROLE_OPTIONS = [
        { code: 'admin', label: 'Admin Sistem' },
        { code: 'operator_aplikasi', label: 'Operator Aplikasi' },
        { code: 'ketua_paguyuban', label: 'Ketua Paguyuban' },
        { code: 'sekretaris_paguyuban', label: 'Sekretaris Paguyuban' },
        { code: 'bendahara_paguyuban', label: 'Bendahara Paguyuban' },
        { code: 'koordinator_zona', label: 'Koordinator Zona' },
        { code: 'koordinator_blok', label: 'Koordinator Blok' },
        { code: 'keamanan_lingkungan', label: 'Keamanan Lingkungan' }
    ];

    const todayStr = new Date().toISOString().slice(0, 10);
    const currentMonthStr = todayStr.slice(0, 7);

    return {
        token: localStorage.getItem('ppmt_token'),
        user: null,
        loading: true,
        toastMessage: '',
        toastType: 'success',
        pendingApprovals: [],
        pendingOwnerships: [],
        verifiedResidents: [],
        governanceList: [],
        allUnits: [],
        announcements: [],
        newsArticles: [],
        savingNews: false,
        editingNews: null,
        editNewsForm: {
            id: null,
            title: '',
            category: 'Kegiatan Warga',
            layout: 'thumbnail',
            body: ''
        },
        agendas: [],
        meetingMinutes: [],
        treasuryMonth: currentMonthStr,
        treasury: null,
        duesSearch: '',
        duesFilterStatus: '',
        savingLedger: false,
        ledgerForm: {
            entry_date: todayStr,
            type: 'expense',
            category: 'pengangkutan_sampah',
            amount: '',
            payment_method: 'cash',
            description: ''
        },
        registryStats: null,
        registryScopeIsCentral: false,
        registryUnits: [],
        registrySearch: '',
        registryFilterBlock: '',
        registryFilterZone: '',
        registryFilterStatus: '',
        savingAnnouncement: false,
        editingAnnouncement: null,
        savingAgenda: false,
        editingAgenda: null,
        savingMinute: false,
        editingMinute: null,
        announcementForm: {
            title: '',
            category: 'info',
            body: '',
            is_pinned: false
        },
        editAnnouncementForm: {
            id: null,
            title: '',
            category: 'info',
            body: '',
            is_pinned: false
        },
        agendaForm: {
            title: '',
            category: 'kegiatan',
            event_date: todayStr,
            time_label: '',
            location: '',
            notes: ''
        },
        editAgendaForm: {
            id: null,
            title: '',
            category: 'kegiatan',
            event_date: todayStr,
            time_label: '',
            location: '',
            notes: ''
        },
        minuteForm: {
            title: '',
            meeting_date: todayStr,
            location: '',
            category: 'musyawarah_warga',
            decisions: '',
            visibility: 'public_residents'
        },
        editMinuteForm: {
            id: null,
            title: '',
            meeting_date: todayStr,
            location: '',
            category: 'musyawarah_warga',
            decisions: '',
            visibility: 'public_residents'
        },
        govForm: {
            target_user_id: '',
            role_code: '',
            scope_type: 'global',
            scope_value: ''
        },
        assistForm: {
            unit_code: '',
            name: '',
            phone: '',
            username: '',
            is_owner: true,
            owner_role: 'primary_owner',
            is_occupant: true,
            resident_role: 'primary_resident',
            nib_shm: '',
            password: ''
        },

        get canManageEditorial() {
            return Boolean(this.user?.can_manage_announcements || this.user?.is_global_governor);
        },

        get showVerificationPanel() {
            if (!this.user) return false;
            if (this.user.is_global_governor) return true;
            const roles = (this.user.governance_roles || []).map(r => r.role_code);
            return roles.includes('koordinator_zona') || roles.includes('koordinator_blok') || this.pendingApprovals.length > 0;
        },

        get availableRoleOptions() {
            const allowed = Array.isArray(this.user?.assignable_governance_roles)
                ? this.user.assignable_governance_roles
                : [];
            return ALL_ROLE_OPTIONS.filter(opt => allowed.includes(opt.code));
        },

        get filteredDuesUnits() {
            const units = this.treasury?.dues_units || [];
            const q = (this.duesSearch || '').trim().toLowerCase();
            return units.filter(row => {
                if (this.duesFilterStatus && row.status !== this.duesFilterStatus) return false;
                if (q) {
                    const matchCode = (row.code || '').toLowerCase().includes(q);
                    const matchName = (row.resident_name || '').toLowerCase().includes(q);
                    if (!matchCode && !matchName) return false;
                }
                return true;
            });
        },

        get filteredRegistryUnits() {
            const q = (this.registrySearch || '').trim().toLowerCase();
            return this.registryUnits.filter(u => {
                if (this.registryFilterBlock && u.block !== this.registryFilterBlock) return false;
                if (this.registryFilterZone && u.zone !== this.registryFilterZone) return false;
                if (this.registryFilterStatus && u.occupancy_status !== this.registryFilterStatus) return false;
                if (q) {
                    const matchCode = (u.code || '').toLowerCase().includes(q);
                    const matchMember = (u.members || []).some(m =>
                        (m.name || '').toLowerCase().includes(q) ||
                        (m.contact || '').toLowerCase().includes(q)
                    );
                    if (!matchCode && !matchMember) return false;
                }
                return true;
            });
        },

        async init() {
            if (!this.token) {
                window.location.href = '/';
                return;
            }
            await this.loadMe();
            if (!this.user?.has_admin_access) {
                window.location.href = '/dashboard';
                return;
            }
            if (this.availableRoleOptions.length > 0) {
                this.govForm.role_code = this.availableRoleOptions[0].code;
                this.updateGovScopeDefault();
            }

            // Tangkap prefill otomatis jika Admin Sistem datang dari tombol "+ Daftarkan Warga"
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('prefill_phone') || urlParams.has('prefill_name') || urlParams.has('prefill_unit')) {
                this.assistForm.phone = urlParams.get('prefill_phone') || '';
                this.assistForm.name = urlParams.get('prefill_name') || '';
                this.assistForm.unit_code = urlParams.get('prefill_unit') || '';
                this.toastType = 'success';
                this.toastMessage = urlParams.has('prefill_unit') && !urlParams.has('prefill_phone')
                    ? `Kavling ${this.assistForm.unit_code} telah dipilih pada formulir "Daftarkan Tetangga Terkenal".`
                    : 'Data kontak dari Grup WhatsApp telah diisikan ke formulir "Daftarkan Tetangga Terkenal".';
                window.history.replaceState({}, '', '/pengurus#assisted-register');
                this.$nextTick(() => {
                    document.getElementById('assisted-register')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                });
            }

            const tasks = [
                this.loadPendingApprovals(),
                this.loadPendingOwnerships(),
                this.loadOccupancyRegistry()
            ];
            if (this.user?.can_view_treasury) {
                tasks.push(this.loadTreasury());
            }
            if (this.canManageEditorial) {
                tasks.push(this.loadAnnouncements(), this.loadNewsArticles(), this.loadAgendas(), this.loadMeetingMinutes());
            }
            if (this.user?.is_global_governor) {
                tasks.push(this.loadGovernanceData());
            }
            await Promise.all(tasks);
        },

        headers() {
            return {
                'Authorization': 'Bearer ' + this.token,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            };
        },

        formatNewsLayout(layout) {
            const labels = {
                'hero': 'Hero Utama',
                'thumbnail': 'Kartu Foto',
                'grid': 'Grid 2 Kolom',
                'text': 'Daftar Teks'
            };
            return labels[layout] || layout;
        },

        formatRupiah(value) {
            return 'Rp ' + Number(value || 0).toLocaleString('id-ID');
        },

        formatDuesStatus(status) {
            const labels = {
                'paid': 'Lunas',
                'unpaid': 'Belum Lunas',
                'exempt': 'Dibebaskan',
                'vacant': 'Kosong'
            };
            return labels[status] || status;
        },

        formatUnitStatus(status) {
            const labels = {
                'unclaimed': 'Belum Terklaim',
                'pending': 'Menunggu Verifikasi',
                'occupied': 'Berpenghuni',
                'rented': 'Disewakan'
            };
            return labels[status] || status;
        },

        async loadMe() {
            try {
                const res = await fetch('/api/v1/me', { headers: this.headers() });
                if (!res.ok) throw new Error('Unauthorized');
                const json = await res.json();
                this.user = json.data;
            } catch (e) {
                localStorage.removeItem('ppmt_token');
                window.location.href = '/';
            } finally {
                this.loading = false;
            }
        },

        async loadTreasury() {
            const params = new URLSearchParams({ month: this.treasuryMonth });
            const res = await fetch('/api/v1/treasury/overview?' + params.toString(), { headers: this.headers() });
            if (res.ok) {
                const json = await res.json();
                this.treasury = json.data || null;
            }
        },

        async updateDuesStatus(row, action, paymentMethod = 'cash') {
            const res = await fetch('/api/v1/treasury/dues', {
                method: 'POST',
                headers: this.headers(),
                body: JSON.stringify({
                    unit_id: row.unit_id,
                    period_month: this.treasuryMonth,
                    action: action,
                    payment_method: paymentMethod
                })
            });
            const json = await res.json();
            if (res.ok) {
                this.toastType = 'success';
                this.toastMessage = json.message;
                await this.loadTreasury();
            } else {
                this.toastType = 'error';
                this.toastMessage = json.message || 'Gagal memperbarui status iuran.';
            }
        },

        async createLedgerEntry() {
            this.savingLedger = true;
            try {
                const res = await fetch('/api/v1/treasury/ledger', {
                    method: 'POST',
                    headers: this.headers(),
                    body: JSON.stringify({
                        entry_date: this.ledgerForm.entry_date,
                        type: this.ledgerForm.type,
                        category: this.ledgerForm.category,
                        amount: Number(this.ledgerForm.amount),
                        payment_method: this.ledgerForm.payment_method,
                        description: this.ledgerForm.description
                    })
                });
                const json = await res.json();
                if (res.ok) {
                    this.toastType = 'success';
                    this.toastMessage = json.message;
                    this.ledgerForm.amount = '';
                    this.ledgerForm.description = '';
                    await this.loadTreasury();
                } else {
                    this.toastType = 'error';
                    this.toastMessage = json.message || 'Gagal mencatat mutasi kas.';
                }
            } finally {
                this.savingLedger = false;
            }
        },

        async deleteLedgerEntry(entry) {
            const res = await fetch(`/api/v1/treasury/ledger/${entry.id}`, {
                method: 'DELETE',
                headers: this.headers()
            });
            const json = await res.json();
            if (res.ok) {
                this.toastType = 'success';
                this.toastMessage = json.message;
                await this.loadTreasury();
            } else {
                this.toastType = 'error';
                this.toastMessage = json.message || 'Gagal menghapus mutasi kas.';
            }
        },

        async loadOccupancyRegistry() {
            const res = await fetch('/api/v1/governance/occupancy-registry', { headers: this.headers() });
            if (res.ok) {
                const json = await res.json();
                const data = json.data || {};
                this.registryStats = data.stats || null;
                this.registryScopeIsCentral = Boolean(data.scope_is_central);
                this.registryUnits = data.units || [];
            }
        },

        async loadAnnouncements() {
            const res = await fetch('/api/v1/announcements?all=1', { headers: this.headers() });
            if (res.ok) {
                const json = await res.json();
                this.announcements = json.data || [];
            }
        },

        async loadNewsArticles() {
            const res = await fetch('/api/v1/admin/news', { headers: this.headers() });
            if (res.ok) {
                const json = await res.json();
                this.newsArticles = json.data || [];
            }
        },

        openNewsEditModal(art) {
            this.editNewsForm = {
                id: art.id,
                title: art.title,
                category: art.category,
                layout: art.layout,
                body: art.body || ''
            };
            this.editingNews = art;
        },

        async saveEditedNews() {
            if (!this.editNewsForm.id) return;
            this.savingNews = true;
            try {
                const res = await fetch(`/api/v1/admin/news/${this.editNewsForm.id}`, {
                    method: 'PATCH',
                    headers: this.headers(),
                    body: JSON.stringify({
                        title: this.editNewsForm.title,
                        category: this.editNewsForm.category,
                        layout: this.editNewsForm.layout,
                        body: this.editNewsForm.body
                    })
                });
                const json = await res.json();
                if (res.ok) {
                    this.editingNews = null;
                    this.toastType = 'success';
                    this.toastMessage = json.message;
                    await this.loadNewsArticles();
                } else {
                    this.toastType = 'error';
                    this.toastMessage = json.message || 'Gagal menyimpan perubahan berita.';
                }
            } finally {
                this.savingNews = false;
            }
        },

        async deleteNewsArticle(art) {
            if (!confirm(`Hapus berita "${art.title}"?`)) return;
            const res = await fetch(`/api/v1/admin/news/${art.id}`, {
                method: 'DELETE',
                headers: this.headers()
            });
            const json = await res.json();
            if (res.ok) {
                this.toastType = 'success';
                this.toastMessage = json.message;
                await this.loadNewsArticles();
            } else {
                this.toastType = 'error';
                this.toastMessage = json.message || 'Gagal menghapus berita.';
            }
        },

        async loadAgendas() {
            const res = await fetch('/api/v1/agendas?all=1', { headers: this.headers() });
            if (res.ok) {
                const json = await res.json();
                this.agendas = json.data || [];
            }
        },

        async createAgenda() {
            this.savingAgenda = true;
            try {
                const res = await fetch('/api/v1/agendas', {
                    method: 'POST',
                    headers: this.headers(),
                    body: JSON.stringify(this.agendaForm)
                });
                const json = await res.json();
                if (res.ok) {
                    this.toastType = 'success';
                    this.toastMessage = json.message;
                    this.agendaForm = {
                        title: '',
                        category: 'kegiatan',
                        event_date: todayStr,
                        time_label: '',
                        location: '',
                        notes: ''
                    };
                    await this.loadAgendas();
                } else {
                    this.toastType = 'error';
                    this.toastMessage = json.message || 'Gagal menyimpan agenda.';
                }
            } finally {
                this.savingAgenda = false;
            }
        },

        openEditAgenda(ag) {
            this.editAgendaForm = {
                id: ag.id,
                title: ag.title,
                category: ag.category_code,
                event_date: ag.event_date,
                time_label: ag.time,
                location: ag.location,
                notes: ag.notes || ''
            };
            this.editingAgenda = ag;
        },

        async saveEditedAgenda() {
            this.savingAgenda = true;
            try {
                const res = await fetch(`/api/v1/agendas/${this.editAgendaForm.id}`, {
                    method: 'PATCH',
                    headers: this.headers(),
                    body: JSON.stringify({
                        title: this.editAgendaForm.title,
                        category: this.editAgendaForm.category,
                        event_date: this.editAgendaForm.event_date,
                        time_label: this.editAgendaForm.time_label,
                        location: this.editAgendaForm.location,
                        notes: this.editAgendaForm.notes || null
                    })
                });
                const json = await res.json();
                if (res.ok) {
                    this.editingAgenda = null;
                    this.toastType = 'success';
                    this.toastMessage = json.message;
                    await this.loadAgendas();
                } else {
                    this.toastType = 'error';
                    this.toastMessage = json.message || 'Gagal memperbarui agenda.';
                }
            } finally {
                this.savingAgenda = false;
            }
        },

        async deleteAgenda(ag) {
            const res = await fetch(`/api/v1/agendas/${ag.id}`, {
                method: 'DELETE',
                headers: this.headers()
            });
            const json = await res.json();
            if (res.ok) {
                this.toastType = 'success';
                this.toastMessage = json.message;
                await this.loadAgendas();
            }
        },

        async loadMeetingMinutes() {
            const res = await fetch('/api/v1/meeting-minutes', { headers: this.headers() });
            if (res.ok) {
                const json = await res.json();
                this.meetingMinutes = json.data || [];
            }
        },

        async createAnnouncement() {
            this.savingAnnouncement = true;
            try {
                const res = await fetch('/api/v1/announcements', {
                    method: 'POST',
                    headers: this.headers(),
                    body: JSON.stringify(this.announcementForm)
                });
                const json = await res.json();
                if (res.ok) {
                    this.toastType = 'success';
                    this.toastMessage = json.message;
                    this.announcementForm = { title: '', category: 'info', body: '', is_pinned: false };
                    await Promise.all([this.loadAnnouncements(), this.loadOccupancyRegistry()]);
                } else {
                    this.toastType = 'error';
                    this.toastMessage = json.message || 'Gagal menerbitkan pengumuman.';
                }
            } finally {
                this.savingAnnouncement = false;
            }
        },

        openEditAnnouncement(item) {
            this.editAnnouncementForm = {
                id: item.id,
                title: item.title,
                category: item.category_code,
                body: item.body,
                is_pinned: Boolean(item.is_pinned)
            };
            this.editingAnnouncement = item;
        },

        async saveEditedAnnouncement() {
            this.savingAnnouncement = true;
            try {
                const res = await fetch(`/api/v1/announcements/${this.editAnnouncementForm.id}`, {
                    method: 'PATCH',
                    headers: this.headers(),
                    body: JSON.stringify({
                        title: this.editAnnouncementForm.title,
                        category: this.editAnnouncementForm.category,
                        body: this.editAnnouncementForm.body,
                        is_pinned: this.editAnnouncementForm.is_pinned
                    })
                });
                const json = await res.json();
                if (res.ok) {
                    this.editingAnnouncement = null;
                    this.toastType = 'success';
                    this.toastMessage = json.message;
                    await Promise.all([this.loadAnnouncements(), this.loadOccupancyRegistry()]);
                } else {
                    this.toastType = 'error';
                    this.toastMessage = json.message || 'Gagal memperbarui pengumuman.';
                }
            } finally {
                this.savingAnnouncement = false;
            }
        },

        async toggleAnnouncementPin(item) {
            const res = await fetch(`/api/v1/announcements/${item.id}`, {
                method: 'PATCH',
                headers: this.headers(),
                body: JSON.stringify({ is_pinned: !item.is_pinned })
            });
            if (res.ok) {
                this.toastType = 'success';
                this.toastMessage = item.is_pinned ? 'Sematkan pengumuman dilepas.' : 'Pengumuman disematkan di urutan teratas.';
                await this.loadAnnouncements();
            }
        },

        async toggleAnnouncementPublish(item) {
            const res = await fetch(`/api/v1/announcements/${item.id}`, {
                method: 'PATCH',
                headers: this.headers(),
                body: JSON.stringify({ is_published: !item.is_published })
            });
            if (res.ok) {
                this.toastType = 'success';
                this.toastMessage = item.is_published ? 'Pengumuman diarsipkan dari beranda warga.' : 'Pengumuman kembali ditayangkan.';
                await Promise.all([this.loadAnnouncements(), this.loadOccupancyRegistry()]);
            }
        },

        async deleteAnnouncement(item) {
            const res = await fetch(`/api/v1/announcements/${item.id}`, {
                method: 'DELETE',
                headers: this.headers()
            });
            const json = await res.json();
            if (res.ok) {
                this.toastType = 'success';
                this.toastMessage = json.message;
                await Promise.all([this.loadAnnouncements(), this.loadOccupancyRegistry()]);
            }
        },

        async createMinute() {
            this.savingMinute = true;
            try {
                const res = await fetch('/api/v1/meeting-minutes', {
                    method: 'POST',
                    headers: this.headers(),
                    body: JSON.stringify(this.minuteForm)
                });
                const json = await res.json();
                if (res.ok) {
                    this.toastType = 'success';
                    this.toastMessage = json.message;
                    this.minuteForm = {
                        title: '',
                        meeting_date: todayStr,
                        location: '',
                        category: 'musyawarah_warga',
                        decisions: '',
                        visibility: 'public_residents'
                    };
                    await Promise.all([this.loadMeetingMinutes(), this.loadOccupancyRegistry()]);
                } else {
                    this.toastType = 'error';
                    this.toastMessage = json.message || 'Gagal menyimpan notulensi.';
                }
            } finally {
                this.savingMinute = false;
            }
        },

        openEditMinute(m) {
            this.editMinuteForm = {
                id: m.id,
                title: m.title,
                meeting_date: m.meeting_date,
                location: m.location || '',
                category: m.category_code,
                decisions: m.decisions,
                visibility: m.visibility
            };
            this.editingMinute = m;
        },

        async saveEditedMinute() {
            this.savingMinute = true;
            try {
                const res = await fetch(`/api/v1/meeting-minutes/${this.editMinuteForm.id}`, {
                    method: 'PATCH',
                    headers: this.headers(),
                    body: JSON.stringify({
                        title: this.editMinuteForm.title,
                        meeting_date: this.editMinuteForm.meeting_date,
                        location: this.editMinuteForm.location || null,
                        category: this.editMinuteForm.category,
                        decisions: this.editMinuteForm.decisions,
                        visibility: this.editMinuteForm.visibility
                    })
                });
                const json = await res.json();
                if (res.ok) {
                    this.editingMinute = null;
                    this.toastType = 'success';
                    this.toastMessage = json.message;
                    await this.loadMeetingMinutes();
                } else {
                    this.toastType = 'error';
                    this.toastMessage = json.message || 'Gagal memperbarui notulensi.';
                }
            } finally {
                this.savingMinute = false;
            }
        },

        async deleteMinute(m) {
            const res = await fetch(`/api/v1/meeting-minutes/${m.id}`, {
                method: 'DELETE',
                headers: this.headers()
            });
            const json = await res.json();
            if (res.ok) {
                this.toastType = 'success';
                this.toastMessage = json.message;
                await Promise.all([this.loadMeetingMinutes(), this.loadOccupancyRegistry()]);
            }
        },

        async loadPendingApprovals() {
            const res = await fetch('/api/v1/verifications/pending', { headers: this.headers() });
            if (res.ok) {
                const json = await res.json();
                this.pendingApprovals = json.data || [];
            }
        },

        async loadPendingOwnerships() {
            const res = await fetch('/api/v1/verifications/ownership-pending', { headers: this.headers() });
            if (res.ok) {
                const json = await res.json();
                this.pendingOwnerships = json.data || [];
            }
        },

        async loadGovernanceData() {
            const [govRes, dirRes, unitsRes] = await Promise.all([
                fetch('/api/v1/governance/roles', { headers: this.headers() }),
                fetch('/api/v1/residents/directory', { headers: this.headers() }),
                fetch('/api/v1/public/units')
            ]);
            if (govRes.ok) {
                const govJson = await govRes.json();
                this.governanceList = govJson.data || [];
            }
            if (dirRes.ok) {
                const dirJson = await dirRes.json();
                this.verifiedResidents = dirJson.data || [];
            }
            if (unitsRes.ok) {
                const unitsJson = await unitsRes.json();
                this.allUnits = (unitsJson.data || []).filter(u => u.unit_type !== 'fasum');
            }
        },

        updateGovScopeDefault() {
            if (this.govForm.role_code === 'koordinator_zona') {
                this.govForm.scope_type = 'zone';
                this.govForm.scope_value = 'ZONA-1';
            } else if (this.govForm.role_code === 'koordinator_blok') {
                this.govForm.scope_type = 'block';
                this.govForm.scope_value = 'A';
            } else {
                this.govForm.scope_type = 'global';
                this.govForm.scope_value = '';
            }
        },

        async assignGovernance() {
            const res = await fetch('/api/v1/governance/assign', {
                method: 'POST',
                headers: this.headers(),
                body: JSON.stringify(this.govForm)
            });
            const json = await res.json();
            if (res.ok) {
                this.toastType = 'success';
                this.toastMessage = json.message;
                await Promise.all([this.loadMe(), this.loadGovernanceData()]);
            } else {
                this.toastType = 'error';
                this.toastMessage = json.message || 'Gagal menyematkan jabatan.';
            }
        },

        async revokeGovernance(roleId) {
            const res = await fetch(`/api/v1/governance/roles/${roleId}`, {
                method: 'DELETE',
                headers: this.headers()
            });
            const json = await res.json();
            if (res.ok) {
                this.toastType = 'success';
                this.toastMessage = json.message;
                await Promise.all([this.loadMe(), this.loadGovernanceData()]);
            } else {
                this.toastType = 'error';
                this.toastMessage = json.message || 'Gagal mencabut jabatan.';
            }
        },

        async submitAssistedRegister() {
            if (!this.assistForm.phone && !this.assistForm.username) {
                this.toastType = 'error';
                this.toastMessage = 'Isi minimal Nomor WhatsApp atau Username untuk warga tersebut.';
                return;
            }

            if (!this.assistForm.is_owner && !this.assistForm.is_occupant) {
                this.toastType = 'error';
                this.toastMessage = 'Centang minimal satu peran: Pemilik Kavling atau Penghuni Kavling.';
                return;
            }

            const payload = {
                unit_code: this.assistForm.unit_code,
                name: this.assistForm.name,
                phone: this.assistForm.phone || null,
                username: this.assistForm.username || null,
                is_owner: Boolean(this.assistForm.is_owner),
                owner_role: this.assistForm.is_owner ? this.assistForm.owner_role : null,
                is_occupant: Boolean(this.assistForm.is_occupant),
                resident_role: this.assistForm.is_occupant ? this.assistForm.resident_role : null,
                nib_shm: this.assistForm.is_owner && (this.assistForm.nib_shm || '').trim() !== ''
                    ? this.assistForm.nib_shm.trim()
                    : null,
                password: this.assistForm.password
            };

            const res = await fetch('/api/v1/governance/assisted-register', {
                method: 'POST',
                headers: this.headers(),
                body: JSON.stringify(payload)
            });
            const json = await res.json();
            if (res.ok) {
                this.toastType = 'success';
                this.toastMessage = json.message;
                this.assistForm.unit_code = '';
                this.assistForm.name = '';
                this.assistForm.phone = '';
                this.assistForm.username = '';
                this.assistForm.nib_shm = '';
                this.assistForm.password = '';

                const afterRegisterTasks = [this.loadGovernanceData(), this.loadOccupancyRegistry(), this.loadPendingOwnerships()];
                if (this.user?.is_operator_aplikasi) {
                    afterRegisterTasks.push(fetch('/api/v1/admin/wa-contacts/reconcile', {
                        method: 'POST',
                        headers: this.headers()
                    }));
                }
                await Promise.all(afterRegisterTasks);
            } else {
                this.toastType = 'error';
                this.toastMessage = json.message || 'Gagal mendaftarkan tetangga.';
            }
        },

        async approveResident(item) {
            const res = await fetch('/api/v1/verifications/approve', {
                method: 'POST',
                headers: this.headers(),
                body: JSON.stringify({
                    target_user_id: item.target_user_id,
                    unit_code: item.unit_code
                })
            });
            const json = await res.json();
            if (res.ok) {
                this.toastType = 'success';
                this.toastMessage = json.message;
                await Promise.all([this.loadPendingApprovals(), this.loadOccupancyRegistry()]);
            } else {
                this.toastType = 'error';
                this.toastMessage = json.message || 'Gagal mengesahkan warga.';
            }
        },

        async verifyUnitOwnership(unitCode, member, action) {
            let nibInput = member.nib_shm_plain || '';
            if (action === 'approve_shm') {
                const entered = prompt(
                    `Konfirmasi / masukkan Nomor NIB SHM untuk ${member.name} (Kavling ${unitCode}):\n(Kosongkan jika sudah terverifikasi fisik secara langsung)`,
                    nibInput
                );
                if (entered === null) return;
                nibInput = entered.trim();
            } else {
                if (!confirm(`Kembalikan status kepemilikan ${member.name} pada Kavling ${unitCode} menjadi "Klaim Sepihak Tanpa Bukti NIB SHM"?`)) {
                    return;
                }
            }

            const res = await fetch(`/api/v1/verifications/ownership/${member.membership_id}`, {
                method: 'POST',
                headers: this.headers(),
                body: JSON.stringify({
                    action: action,
                    nib_shm: nibInput || null
                })
            });
            const json = await res.json();
            if (res.ok) {
                this.toastType = 'success';
                this.toastMessage = json.message;
                await Promise.all([this.loadPendingOwnerships(), this.loadOccupancyRegistry()]);
            } else {
                this.toastType = 'error';
                this.toastMessage = json.message || 'Gagal memproses status NIB SHM.';
            }
        }
    }
}
</script>
@endsection
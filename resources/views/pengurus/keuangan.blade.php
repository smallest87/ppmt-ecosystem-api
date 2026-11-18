@extends('layouts.app')

@section('title', 'Kas & Iuran Sampah — Panel Pengurus PPMT')
@section('subpage_title', 'Kas & Iuran Sampah')
@section('subpage_back_url', '/pengurus')

@section('content')
<div x-data="ppmtTreasuryPanel()" x-init="init()" class="max-w-6xl mx-auto px-4 py-6 sm:py-8 space-y-6">

    <!-- Loading State -->
    <div x-show="loading" class="bg-white rounded-2xl p-12 text-center text-slate-500 border border-slate-200">
        Memuat pembukuan kas paguyuban &amp; kartu iuran sampah...
    </div>

    <div x-show="!loading && user" class="space-y-6">

        <!-- Notifikasi Global -->
        <div x-show="toastMessage"
             x-cloak
             :class="toastType === 'error' ? 'bg-rose-50 text-rose-800 border-rose-200' : 'bg-emerald-50 text-emerald-800 border-emerald-200'"
             class="p-4 rounded-xl border text-sm font-medium flex justify-between items-center shadow-sm">
            <span x-text="toastMessage"></span>
            <button type="button" @click="toastMessage = ''" class="text-xs opacity-70 hover:opacity-100 cursor-pointer">✕</button>
        </div>

        <!-- Ringkasan Saldo Kas & Progres Iuran Sampah -->
        <section x-show="treasury !== null" class="bg-emerald-950 text-white rounded-2xl p-5 sm:p-6 shadow-sm space-y-4">
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
        </section>

        <!-- Baris 2 Bendahara: Kartu Iuran Sampah (7 Kolom) & Buku Kas Umum (5 Kolom) -->
        <section x-show="treasury !== null" class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

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
        </section>
    </div>
</div>

@push('scripts')
<script>
function ppmtTreasuryPanel() {
    const todayStr = new Date().toISOString().slice(0, 10);
    const currentMonthStr = todayStr.slice(0, 7);

    return {
        token: localStorage.getItem('ppmt_token'),
        user: null,
        loading: true,
        toastMessage: '',
        toastType: 'success',
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

        async init() {
            if (!this.token) {
                window.location.href = '/';
                return;
            }
            await this.loadMe();
            if (!this.user?.has_admin_access || !this.user?.can_view_treasury) {
                window.location.href = '/pengurus';
                return;
            }
            await this.loadTreasury();
        },

        headers() {
            return {
                'Authorization': 'Bearer ' + this.token,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            };
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
        }
    }
}
</script>
@endpush
@endsection
@extends('layouts.app')

@section('title', 'Hunian & Kepemilikan Kavling — Pengaturan PPMT Digital')
@section('subpage_title', 'Hunian, Kepemilikan & Kendaraan')
@section('subpage_back_url', '/pengaturan')

@section('content')
<div x-data="ppmtHousingSettings()" x-init="init()" @keydown.escape.window="closeModal()" class="max-w-3xl mx-auto px-4 py-6 sm:py-8 space-y-6">

    <p class="text-xs sm:text-sm text-slate-500">
        Kelola peran kepemilikan sertifikat (NIB SHM), visibilitas kepemilikan, status hunian fisik di kavling, serta kendaraan warga yang terdaftar di lingkungan.
    </p>

    <!-- Loading State -->
    <div x-show="loading" class="bg-white rounded-2xl p-12 text-center text-slate-500 border border-slate-200">
        Memuat data hunian &amp; kavling Anda...
    </div>

    <div x-show="!loading && user" x-cloak class="space-y-6">

        <!-- Notifikasi Global -->
        <div x-show="toastMessage"
             :class="toastType === 'error' ? 'bg-rose-50 text-rose-800 border-rose-200' : 'bg-emerald-50 text-emerald-800 border-emerald-200'"
             class="p-4 rounded-xl border text-sm font-medium flex justify-between items-center shadow-sm">
            <span x-text="toastMessage"></span>
            <button type="button" @click="toastMessage = ''" class="text-xs opacity-70 hover:opacity-100 cursor-pointer">✕</button>
        </div>

        <!-- ========================================================= -->
        <!-- KARTU 1: KEPEMILIKAN & HUNIAN DI KAVLING (2 Dimensi)      -->
        <!-- ========================================================= -->
        <section class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100">
                <h2 class="text-base font-bold text-slate-900">1. Status Kepemilikan &amp; Hunian di Kavling</h2>
                <p class="text-xs text-slate-500 mt-0.5">Klik pada kavling di bawah ini untuk mengatur status pemilik (beserta bukti NIB SHM &amp; privasi kepemilikan) dan/atau status penghuni.</p>
            </div>

            <div class="divide-y divide-slate-100">
                <div x-show="(user?.memberships || []).length === 0"
                     class="p-6 text-center text-xs text-slate-500">
                    Belum ada kavling yang terhubung dengan akun Anda.
                </div>

                <template x-for="m in (user?.memberships || [])" :key="m.id">
                    <button type="button" @click="openMembershipModal(m)"
                            class="w-full px-6 py-4 flex items-center justify-between gap-4 text-left hover:bg-slate-50/90 transition cursor-pointer group">
                        <div class="space-y-1.5">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-sm font-bold text-slate-900"
                                      x-text="'Kavling ' + m.unit?.code + ' (' + m.unit?.zone + ')'"></span>
                                <span :class="m.is_approved ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-amber-50 text-amber-800 border-amber-200'"
                                      class="px-2 py-0.5 rounded-full text-[10px] font-bold border"
                                      x-text="m.is_approved ? '✓ Warga Disahkan' : '⏳ Menunggu Pengesahan'">
                                </span>
                                <span x-show="isMemberOwner(m)"
                                      :class="user?.privacy_settings?.show_ownership
                                        ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                        : 'bg-slate-100 text-slate-600 border-slate-200'"
                                      class="px-2 py-0.5 rounded-full text-[10px] font-bold border"
                                      x-text="user?.privacy_settings?.show_ownership ? 'Kepemilikan: Publik' : 'Kepemilikan: Privat'">
                                </span>
                            </div>

                            <!-- Ringkasan 2 Dimensi -->
                            <div class="text-xs text-slate-700 font-medium" x-text="formatMembershipSummary(m)"></div>

                            <!-- Badge Status Verifikasi Kepemilikan NIB SHM (Jika dicentang Pemilik) -->
                            <div x-show="isMemberOwner(m)" class="flex flex-wrap items-center gap-1.5 pt-0.5">
                                <span x-show="m.ownership_verification_status === 'verified_shm'"
                                      class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md bg-emerald-100 text-emerald-900 border border-emerald-300 text-[11px] font-bold">
                                    &#10003; Klaim Terverifikasi dengan Bukti NIB SHM
                                </span>
                                <span x-show="m.ownership_verification_status !== 'verified_shm'"
                                      class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md bg-amber-100 text-amber-950 border border-amber-300 text-[11px] font-bold">
                                    &#9888; Klaim Sepihak Tanpa Bukti NIB SHM (Belum Terverifikasi)
                                </span>
                                <span x-show="m.has_nib_shm && m.ownership_verification_status !== 'verified_shm'"
                                      class="text-[10px] font-semibold text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded border border-indigo-200">
                                    Nomor NIB SHM terenkripsi, menunggu cek pengurus
                                </span>
                            </div>

                            <div x-show="isMemberOccupant(m) && (m.resident_role || m.sub_role) === 'tenant' && m.valid_until"
                                 class="text-[11px] text-slate-500"
                                 x-text="'Batas masa sewa: ' + (m.valid_until ? m.valid_until.slice(0, 10) : '-')">
                            </div>
                        </div>
                        <span class="text-slate-400 group-hover:text-emerald-700 group-hover:translate-x-0.5 transition text-lg font-medium shrink-0">&rsaquo;</span>
                    </button>
                </template>
            </div>
        </section>

        <!-- ========================================================= -->
        <!-- KARTU 2: KENDARAAN TERDAFTAR DI KAVLING                   -->
        <!-- ========================================================= -->
        <section class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100">
                <h2 class="text-base font-bold text-slate-900">2. Kendaraan Terdaftar di Kavling</h2>
                <p class="text-xs text-slate-500 mt-0.5">Data plat nomor kendaraan penghuni untuk memudahkan koordinasi parkir &amp; keamanan gerbang lingkungan.</p>
            </div>

            <div class="divide-y divide-slate-100">
                <button type="button" @click="openVehiclesModal()"
                        class="w-full px-6 py-4 flex items-center justify-between gap-4 text-left hover:bg-slate-50/90 transition cursor-pointer group">
                    <div class="space-y-1.5">
                        <div class="text-xs font-medium text-slate-500">Daftar Plat Nomor Kendaraan</div>
                        <div x-show="(user?.vehicles || []).length > 0" class="flex flex-wrap gap-1.5">
                            <template x-for="(v, idx) in (user?.vehicles || [])" :key="idx">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-slate-100 border border-slate-200 text-xs font-semibold text-slate-800">
                                    <span class="capitalize text-slate-500 font-medium" x-text="v.type + ':'"></span>
                                    <span class="font-mono uppercase" x-text="v.plate || '-'"></span>
                                </span>
                            </template>
                        </div>
                        <div x-show="(user?.vehicles || []).length === 0" class="text-sm font-medium text-slate-400">
                            Belum ada kendaraan terdaftar
                        </div>
                    </div>
                    <span class="text-slate-400 group-hover:text-emerald-700 group-hover:translate-x-0.5 transition text-lg font-medium shrink-0">&rsaquo;</span>
                </button>
            </div>
        </section>

    </div>

    <!-- ===================================================================== -->
    <!-- POPUP MODAL HUNIAN & KENDARAAN                                        -->
    <!-- ===================================================================== -->
    <div x-show="activeModal !== null"
         x-cloak
         class="fixed inset-0 z-50 !mt-0 flex items-center justify-center p-3 sm:p-4 bg-slate-900/50 backdrop-blur-[1px] overflow-y-auto">
        <div @click.away="closeModal()"
             class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-[calc(100vw-1.5rem)] sm:max-w-lg max-h-[90dvh] flex flex-col overflow-hidden my-auto">

            <div class="px-5 sm:px-6 py-4 border-b border-slate-100 flex items-center justify-between gap-3 shrink-0">
                <h3 class="text-base font-bold text-slate-900 leading-snug"
                    x-text="activeModal === 'vehicles' ? 'Kelola Kendaraan Terdaftar' : 'Atur Status Kepemilikan & Hunian Kavling'"></h3>
                <button type="button" @click="closeModal()" class="text-slate-400 hover:text-slate-700 text-sm p-1 cursor-pointer shrink-0">✕</button>
            </div>

            <form @submit.prevent="submitModal()" class="p-5 sm:p-6 space-y-4 overflow-y-auto">
                <div x-show="modalError"
                     class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-xs text-rose-800 font-medium"
                     x-text="modalError"></div>

                <!-- MODAL KENDARAAN -->
                <template x-if="activeModal === 'vehicles'">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-slate-700">Daftar Kendaraan Kavling</span>
                            <button type="button" @click="vehiclesForm.push({ type: 'motor', plate: '' })"
                                    class="text-xs font-bold text-emerald-700 hover:underline cursor-pointer">
                                + Tambah Baris
                            </button>
                        </div>

                        <div x-show="vehiclesForm.length === 0"
                             class="p-4 rounded-xl bg-slate-50 border border-dashed border-slate-300 text-center text-xs text-slate-500">
                            Belum ada kendaraan. Klik <strong>+ Tambah Baris</strong> untuk mendaftarkan plat nomor.
                        </div>

                        <div class="space-y-2 max-h-60 overflow-y-auto pr-1">
                            <template x-for="(v, idx) in vehiclesForm" :key="idx">
                                <div class="flex items-center gap-2">
                                    <select x-model="v.type" class="rounded-xl border-slate-300 text-xs">
                                        <option value="mobil">Mobil</option>
                                        <option value="motor">Motor</option>
                                        <option value="sepeda">Sepeda</option>
                                    </select>
                                    <input type="text" x-model="v.plate" required placeholder="N 1234 AB"
                                           class="flex-1 rounded-xl border-slate-300 text-xs font-mono uppercase">
                                    <button type="button" @click="vehiclesForm.splice(idx, 1)"
                                            class="px-2.5 py-1.5 text-xs font-semibold text-rose-600 hover:bg-rose-50 rounded-lg cursor-pointer">
                                        Hapus
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>

                <!-- MODAL KEANGGOTAAN 2 DIMENSI: KEPEMILIKAN (NIB SHM) & HUNIAN -->
                <template x-if="activeModal === 'membership'">
                    <div class="space-y-4">
                        <div class="p-3 rounded-xl bg-slate-900 text-white flex items-center justify-between text-xs">
                            <span class="font-extrabold"
                                  x-text="'Kavling ' + membershipForm.unit_code + ' (' + membershipForm.zone + ')'"></span>
                            <span class="text-slate-300 text-[11px]">Dapat dicentang salah satu atau keduanya</span>
                        </div>

                        <!-- DIMENSI A: STATUS KEPEMILIKAN + PRIVASI + NIB SHM -->
                        <div :class="membershipForm.is_owner ? 'border-amber-400 bg-amber-50/40' : 'border-slate-200 bg-white'"
                             class="rounded-2xl border-2 p-4 space-y-3 transition">
                            <label class="flex items-start gap-3 cursor-pointer select-none">
                                <input type="checkbox"
                                       x-model="membershipForm.is_owner"
                                       class="mt-0.5 rounded border-slate-300 text-amber-600 focus:ring-amber-500">
                                <div class="space-y-0.5">
                                    <div class="text-sm font-bold text-slate-900">Saya adalah Pemilik Kavling ini</div>
                                    <div class="text-[11px] text-slate-600 leading-relaxed">
                                        Centang jika nama Anda (atau pasangan dalam satu keluarga inti) tercantum pada hak milik kavling ini.
                                    </div>
                                </div>
                            </label>

                            <div x-show="membershipForm.is_owner" x-transition class="space-y-3 pt-2 border-t border-amber-200/80">
                                <!-- Kontrol Privasi Status Kepemilikan -->
                                <div @click="membershipForm.show_ownership = !membershipForm.show_ownership"
                                     class="p-3 rounded-xl border border-amber-200/90 bg-white flex items-center justify-between gap-3 cursor-pointer select-none">
                                    <div class="space-y-0.5">
                                        <div class="text-xs font-bold text-slate-800"
                                             x-text="membershipForm.show_ownership ? 'Tampil ke Tetangga' : 'Privat (Disembunyikan)'"></div>
                                        <div class="text-[11px] text-slate-500 leading-relaxed"
                                             x-text="membershipForm.show_ownership
                                                ? 'Status pemilik kavling & tanda verifikasi SHM tampil di direktori warga.'
                                                : 'Status pemilik & SHM disembunyikan dari warga umum (status penghuni tetap tampil).'"></div>
                                    </div>
                                    <button type="button"
                                            role="switch"
                                            :aria-checked="membershipForm.show_ownership"
                                            :class="membershipForm.show_ownership ? '!bg-emerald-600' : '!bg-slate-300'"
                                            class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full bg-slate-300 border border-slate-300/80 transition-colors duration-200 focus:outline-none cursor-pointer">
                                        <span :class="membershipForm.show_ownership ? 'translate-x-5' : 'translate-x-0.5'"
                                              class="inline-block h-4 w-4 transform rounded-full bg-white shadow-sm transition-transform duration-200"></span>
                                    </button>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Peran Kepemilikan</label>
                                    <select x-model="membershipForm.owner_role" class="w-full rounded-xl border-slate-300 text-xs">
                                        <option value="primary_owner">Pemilik Utama</option>
                                        <option value="co_owner">Pemilik Bersama (Pasangan / Keluarga)</option>
                                    </select>
                                </div>

                                <div class="p-3 rounded-xl border text-xs space-y-1.5"
                                     :class="membershipForm.ownership_verification_status === 'verified_shm'
                                        ? 'bg-emerald-50 border-emerald-300 text-emerald-950'
                                        : 'bg-amber-100/80 border-amber-300 text-amber-950'">
                                    <div class="font-extrabold flex items-center gap-1.5">
                                        <span x-show="membershipForm.ownership_verification_status === 'verified_shm'">&#10003; Status: Klaim Terverifikasi dengan Bukti NIB SHM</span>
                                        <span x-show="membershipForm.ownership_verification_status !== 'verified_shm'">&#9888; Status: Klaim Sepihak Tanpa Bukti NIB SHM (Belum Terverifikasi)</span>
                                    </div>
                                    <p class="text-[11px] leading-relaxed opacity-90">
                                        Untuk mencegah klaim kepemilikan sepihak, status <strong>Pemilik Terverifikasi</strong> hanya aktif setelah Nomor NIB SHM dicocokkan dan disahkan oleh Pengurus / Koordinator Wilayah.
                                    </p>
                                </div>

                                <div class="space-y-1.5">
                                    <div class="flex items-center justify-between">
                                        <label class="block text-xs font-semibold text-slate-700">
                                            Nomor Identifikasi Bidang (NIB) / Nomor SHM
                                        </label>
                                        <span x-show="membershipForm.has_nib_shm"
                                              class="text-[10px] font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                                            &#128274; Tersimpan Terenkripsi
                                        </span>
                                    </div>
                                    <input type="text"
                                           x-model="membershipForm.nib_shm"
                                           :placeholder="membershipForm.has_nib_shm ? 'Isi hanya jika ingin memperbarui Nomor NIB SHM' : 'Contoh: 12.16.08.04.01234 / SHM No. 567'"
                                           class="w-full rounded-xl border-slate-300 text-xs font-mono uppercase focus:border-amber-600 focus:ring-amber-600">
                                    <p class="text-[10px] text-slate-500 leading-relaxed">
                                        Nomor NIB SHM dienkripsi (AES-256) di server dan hanya dapat diperiksa oleh Pengurus Berwenang untuk pengesahan kepemilikan.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- DIMENSI B: STATUS MENGHUNI FISIK -->
                        <div :class="membershipForm.is_occupant ? 'border-emerald-500 bg-emerald-50/35' : 'border-slate-200 bg-white'"
                             class="rounded-2xl border-2 p-4 space-y-3 transition">
                            <label class="flex items-start gap-3 cursor-pointer select-none">
                                <input type="checkbox"
                                       x-model="membershipForm.is_occupant"
                                       class="mt-0.5 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                <div class="space-y-0.5">
                                    <div class="text-sm font-bold text-slate-900">Saya tinggal / menghuni di Kavling ini</div>
                                    <div class="text-[11px] text-slate-600 leading-relaxed">
                                        Centang jika Anda berdomisili fisik di kavling ini. Kosongkan jika kavling masih kosong atau disewakan ke pihak lain.
                                    </div>
                                </div>
                            </label>

                            <div x-show="membershipForm.is_occupant" x-transition class="space-y-3 pt-2 border-t border-emerald-200/80">
                                <div>
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Peran Hunian di Kavling</label>
                                    <select x-model="membershipForm.resident_role" class="w-full rounded-xl border-slate-300 text-xs">
                                        <option value="primary_resident">Penghuni Utama / Kepala Keluarga</option>
                                        <option value="family_member">Anggota Keluarga (Pasangan / Anak / Orang Tua)</option>
                                        <option value="co_resident">Penghuni Bersama</option>
                                        <option value="tenant" x-show="!membershipForm.is_owner">Penyewa / Kontrak</option>
                                        <option value="caretaker" x-show="!membershipForm.is_owner">Penjaga Rumah</option>
                                    </select>
                                </div>

                                <div x-show="membershipForm.resident_role === 'tenant' && !membershipForm.is_owner">
                                    <label class="block text-xs font-semibold text-slate-700 mb-1">Batas Akhir Masa Sewa (Opsional)</label>
                                    <input type="date" x-model="membershipForm.valid_until"
                                           class="w-full rounded-xl border-slate-300 text-xs">
                                </div>
                            </div>
                        </div>

                    </div>
                </template>

                <div class="pt-2 flex items-center justify-end gap-2.5 border-t border-slate-100">
                    <button type="button" @click="closeModal()"
                            class="px-4 py-2 rounded-xl border border-slate-300 text-slate-700 hover:bg-slate-50 text-xs font-semibold cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" :disabled="savingModal"
                            class="px-5 py-2 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold shadow-sm transition cursor-pointer">
                        <span x-show="!savingModal">Simpan Perubahan</span>
                        <span x-show="savingModal">Menyimpan...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function ppmtHousingSettings() {
    return {
        token: localStorage.getItem('ppmt_token'),
        user: null,
        loading: true,
        toastMessage: '',
        toastType: 'success',
        activeModal: null,
        modalError: '',
        savingModal: false,
        vehiclesForm: [],
        membershipForm: {
            id: null,
            unit_code: '',
            zone: '',
            is_owner: false,
            show_ownership: false,
            owner_role: 'primary_owner',
            is_occupant: true,
            resident_role: 'primary_resident',
            ownership_verification_status: 'none',
            has_nib_shm: false,
            nib_shm: '',
            valid_until: ''
        },

        async init() {
            if (!this.token) {
                window.location.href = '/';
                return;
            }
            await this.loadMe();
        },

        headers() {
            return {
                'Authorization': 'Bearer ' + this.token,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            };
        },

        isMemberOwner(m) {
            return typeof m?.is_owner === 'boolean' ? m.is_owner : m?.entity_type === 'owner';
        },

        isMemberOccupant(m) {
            return typeof m?.is_occupant === 'boolean' ? m.is_occupant : true;
        },

        subRoleLabel(role) {
            const labels = {
                'primary_owner': 'Pemilik Utama',
                'co_owner': 'Pemilik Bersama',
                'primary_resident': 'Penghuni Utama',
                'family_member': 'Anggota Keluarga',
                'co_resident': 'Penghuni Bersama',
                'tenant': 'Penyewa / Kontrak',
                'caretaker': 'Penjaga Rumah'
            };
            return labels[role] || role;
        },

        formatMembershipSummary(m) {
            const owner = this.isMemberOwner(m);
            const occupant = this.isMemberOccupant(m);
            const ownerText = this.subRoleLabel(m.owner_role || (m.entity_type === 'owner' ? m.sub_role : 'primary_owner'));
            const residentText = this.subRoleLabel(m.resident_role || (m.entity_type === 'resident' ? m.sub_role : 'primary_resident'));

            if (owner && occupant) {
                return `${ownerText} • Sekaligus Menghuni (${residentText})`;
            }
            if (owner && !occupant) {
                return `${ownerText} • Tidak Menghuni Kavling`;
            }
            return `Penghuni Kavling • ${residentText}`;
        },

        openVehiclesModal() {
            this.modalError = '';
            const existing = Array.isArray(this.user?.vehicles)
                ? JSON.parse(JSON.stringify(this.user.vehicles))
                : [];
            if (existing.length === 0) {
                existing.push({ type: 'motor', plate: '' });
            }
            this.vehiclesForm = existing;
            this.activeModal = 'vehicles';
        },

        openMembershipModal(m) {
            this.modalError = '';
            const isOwner = this.isMemberOwner(m);
            const isOccupant = this.isMemberOccupant(m);
            this.membershipForm = {
                id: m.id,
                unit_code: m.unit?.code || '',
                zone: m.unit?.zone || '',
                is_owner: isOwner,
                show_ownership: Boolean(this.user?.privacy_settings?.show_ownership),
                owner_role: m.owner_role || (m.entity_type === 'owner' ? m.sub_role : 'primary_owner'),
                is_occupant: isOccupant,
                resident_role: m.resident_role || (m.entity_type === 'resident' ? m.sub_role : 'primary_resident'),
                ownership_verification_status: m.ownership_verification_status || (isOwner ? 'unverified_claim' : 'none'),
                has_nib_shm: Boolean(m.has_nib_shm),
                nib_shm: '',
                valid_until: m.valid_until ? m.valid_until.slice(0, 10) : ''
            };
            this.activeModal = 'membership';
        },

        closeModal() {
            this.activeModal = null;
            this.modalError = '';
        },

        async submitModal() {
            this.savingModal = true;
            this.modalError = '';
            try {
                if (this.activeModal === 'membership') {
                    const m = this.membershipForm;
                    if (!m.is_owner && !m.is_occupant) {
                        this.modalError = 'Centang minimal satu pilihan: Pemilik Kavling, Penghuni Kavling, atau keduanya.';
                        return;
                    }
                    const res = await fetch(`/api/v1/memberships/${m.id}`, {
                        method: 'PATCH',
                        headers: this.headers(),
                        body: JSON.stringify({
                            is_owner: Boolean(m.is_owner),
                            owner_role: m.is_owner ? m.owner_role : null,
                            is_occupant: Boolean(m.is_occupant),
                            resident_role: m.is_occupant ? m.resident_role : null,
                            nib_shm: m.is_owner && (m.nib_shm || '').trim() !== '' ? m.nib_shm.trim() : null,
                            valid_until: (m.is_occupant && m.resident_role === 'tenant' && !m.is_owner)
                                ? (m.valid_until || null)
                                : null
                        })
                    });
                    const json = await res.json();
                    if (!res.ok) {
                        this.modalError = json.message || 'Gagal memperbarui status kavling.';
                        return;
                    }

                    // Sinkronkan privasi status kepemilikan jika berubah
                    const prevShowOwnership = Boolean(this.user?.privacy_settings?.show_ownership);
                    if (m.is_owner && Boolean(m.show_ownership) !== prevShowOwnership) {
                        await fetch('/api/v1/profile', {
                            method: 'PATCH',
                            headers: this.headers(),
                            body: JSON.stringify({
                                name: this.user.name,
                                display_name: this.user.display_name,
                                phone: this.user.phone || null,
                                username: this.user.username || null,
                                privacy_settings: {
                                    show_ownership: Boolean(m.show_ownership)
                                }
                            })
                        });
                    }

                    this.closeModal();
                    this.toastType = 'success';
                    this.toastMessage = json.message || 'Status kepemilikan & hunian kavling berhasil diperbarui.';
                    await this.loadMe();
                    return;
                }

                if (this.activeModal === 'vehicles') {
                    const vehicles = this.vehiclesForm
                        .filter(v => (v.plate || '').trim() !== '')
                        .map(v => ({ type: v.type || 'motor', plate: v.plate.trim().toUpperCase() }));

                    const res = await fetch('/api/v1/profile', {
                        method: 'PATCH',
                        headers: this.headers(),
                        body: JSON.stringify({
                            name: this.user.name,
                            display_name: this.user.display_name,
                            phone: this.user.phone || null,
                            username: this.user.username || null,
                            vehicles: vehicles
                        })
                    });
                    const json = await res.json();
                    if (!res.ok) {
                        this.modalError = json.message || 'Gagal menyimpan daftar kendaraan.';
                        return;
                    }
                    this.closeModal();
                    this.toastType = 'success';
                    this.toastMessage = json.message || 'Daftar kendaraan berhasil diperbarui.';
                    await this.loadMe();
                }
            } catch (e) {
                this.modalError = 'Terjadi gangguan koneksi saat menyimpan.';
            } finally {
                this.savingModal = false;
            }
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
        }
    }
}
</script>
@endsection
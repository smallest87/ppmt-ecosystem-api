@extends('layouts.app')

@section('title', 'Buku Induk Kavling & Tata Kelola — Panel Pengurus PPMT')
@section('subpage_title', 'Buku Induk & Tata Kelola')
@section('subpage_back_url', '/pengurus')

@section('content')
<div x-data="ppmtRegistryGovernancePanel()" x-init="init()" class="max-w-6xl mx-auto px-4 py-6 sm:py-8 space-y-6">

    <!-- Loading State -->
    <div x-show="loading" class="bg-white rounded-2xl p-12 text-center text-slate-500 border border-slate-200">
        Memuat buku induk hunian kavling &amp; struktur tata kelola...
    </div>

    <div x-show="!loading && user" x-cloak class="space-y-6">

        <!-- Notifikasi Global -->
        <div x-show="toastMessage"
             x-cloak
             :class="toastType === 'error' ? 'bg-rose-50 text-rose-800 border-rose-200' : 'bg-emerald-50 text-emerald-800 border-emerald-200'"
             class="p-4 rounded-xl border text-sm font-medium flex justify-between items-center shadow-sm">
            <span x-text="toastMessage"></span>
            <button type="button" @click="toastMessage = ''" class="text-xs opacity-70 hover:opacity-100 cursor-pointer">✕</button>
        </div>

        <!-- ================================================================= -->
        <!-- BAGIAN 1: BUKU INDUK & STATUS HUNIAN KAVLING (Seluruh Pengurus)   -->
        <!-- ================================================================= -->
        <section class="bg-white rounded-2xl p-5 sm:p-6 shadow-sm border border-slate-200 space-y-4">
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

                                        <!-- Status Verifikasi Kepemilikan NIB SHM Kolektif & Masking Penuh -->
                                        <div x-show="m.is_owner" class="mt-1.5 pt-1.5 border-t border-slate-100 space-y-1.5">
                                            <div class="flex flex-wrap items-center justify-between gap-1">
                                                <span x-show="m.ownership_verification_status === 'verified_shm'"
                                                      class="px-2 py-0.5 rounded bg-emerald-100 text-emerald-900 border border-emerald-300 text-[10px] font-bold">
                                                    &#10003; NIB SHM Sah (<span x-text="m.endorsement_count"></span> Pengesah)
                                                </span>
                                                <span x-show="m.ownership_verification_status !== 'verified_shm'"
                                                      class="px-2 py-0.5 rounded bg-amber-100 text-amber-950 border border-dashed border-amber-400 text-[10px] font-bold">
                                                    &#9888; Klaim Tanpa Bukti NIB SHM
                                                </span>

                                                <button x-show="m.can_verify_ownership && !m.is_endorsed_by_me"
                                                        type="button"
                                                        @click="verifyUnitOwnership(u.code, m, 'approve_shm')"
                                                        class="px-2 py-0.5 rounded bg-emerald-700 hover:bg-emerald-800 text-white text-[10px] font-bold transition cursor-pointer"
                                                        x-text="m.endorsement_count > 0 ? '+ Sahkan Juga' : 'Sahkan SHM'">
                                                </button>

                                                <button x-show="m.can_verify_ownership && m.is_endorsed_by_me"
                                                        type="button"
                                                        @click="verifyUnitOwnership(u.code, m, 'revoke_shm')"
                                                        class="px-2 py-0.5 rounded border border-slate-200 hover:bg-rose-50 text-rose-600 text-[10px] font-semibold transition cursor-pointer">
                                                    Cabut Pengesahan Saya
                                                </button>
                                            </div>

                                            <!-- Rincian Siapa Saja yang Mengesahkan -->
                                            <div x-show="m.endorsed_by_sentence"
                                                 class="text-[10px] text-emerald-800 font-medium leading-snug bg-emerald-50/80 px-2 py-1 rounded border border-emerald-200/80"
                                                 x-text="m.endorsed_by_sentence">
                                            </div>

                                            <!-- Status Input NIB SHM (Tertutup Penuh bagi Pengurus Sosial/Admin) -->
                                            <div x-show="m.has_nib_shm" class="text-[10px] font-mono text-slate-700 bg-slate-50 px-2 py-0.5 rounded border border-slate-200">
                                                NIB/SHM: <strong x-text="m.can_see_raw_nib ? m.nib_shm_plain : m.nib_shm_masked"></strong>
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

        <!-- ================================================================= -->
        <!-- BAGIAN 2: PENDAFTARAN TETANGGA & PENYEMATAN JABATAN (Gubernur)    -->
        <!-- ================================================================= -->
        <div x-show="user?.is_global_governor"
             class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

            <!-- Kolom Kiri (5 Kolom): Pendaftaran Langsung Tetangga Terkenal (Assisted Onboarding) -->
            <section id="assisted-register"
                     class="lg:col-span-5 bg-emerald-50/60 rounded-2xl p-5 sm:p-6 shadow-sm border border-emerald-200 space-y-4">
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
                                           placeholder="Nomor NIB SHM (opsional, otomatis terenkripsi)"
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

            <!-- Kolom Kanan (7 Kolom): Penyematan Jabatan & Hak Akses -->
            <section class="lg:col-span-7 bg-white rounded-2xl p-5 sm:p-6 shadow-sm border border-slate-200 space-y-5">
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
    </div>
</div>

@push('scripts')
<script>
function ppmtRegistryGovernancePanel() {
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

    return {
        token: localStorage.getItem('ppmt_token'),
        user: null,
        loading: true,
        toastMessage: '',
        toastType: 'success',
        verifiedResidents: [],
        governanceList: [],
        allUnits: [],
        registryScopeIsCentral: false,
        registryUnits: [],
        registrySearch: '',
        registryFilterBlock: '',
        registryFilterZone: '',
        registryFilterStatus: '',
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

        get availableRoleOptions() {
            const allowed = Array.isArray(this.user?.assignable_governance_roles)
                ? this.user.assignable_governance_roles
                : [];
            return ALL_ROLE_OPTIONS.filter(opt => allowed.includes(opt.code));
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
                window.history.replaceState({}, '', '/pengurus/buku-induk#assisted-register');
                this.$nextTick(() => {
                    document.getElementById('assisted-register')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                });
            }

            const tasks = [this.loadOccupancyRegistry()];
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

        async loadOccupancyRegistry() {
            const res = await fetch('/api/v1/governance/occupancy-registry', { headers: this.headers() });
            if (res.ok) {
                const json = await res.json();
                const data = json.data || {};
                this.registryScopeIsCentral = Boolean(data.scope_is_central);
                this.registryUnits = data.units || [];
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

                const afterRegisterTasks = [this.loadGovernanceData(), this.loadOccupancyRegistry()];
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

        async verifyUnitOwnership(unitCode, member, action) {
            let nibInput = '';

            if (action === 'approve_shm') {
                if (member.can_see_raw_nib) {
                    const entered = prompt(
                        `Konfirmasi / masukkan Nomor NIB SHM untuk ${member.name} (Kavling ${unitCode}):`,
                        member.nib_shm_plain || ''
                    );
                    if (entered === null) return;
                    nibInput = entered.trim();
                } else {
                    if (!confirm(`Sahkan bukti kepemilikan NIB SHM untuk ${member.name} pada Kavling ${unitCode} atas nama jabatan Anda?`)) {
                        return;
                    }
                }
            } else {
                if (!confirm(`Cabut pengesahan Anda atas klaim kepemilikan ${member.name} pada Kavling ${unitCode}?\n(Pengesahan dari pejabat lain, jika ada, akan tetap dipertahankan)`)) {
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
                await this.loadOccupancyRegistry();
            } else {
                this.toastType = 'error';
                this.toastMessage = json.message || 'Gagal memproses pengesahan NIB SHM.';
            }
        }
    }
}
</script>
@endpush
@endsection
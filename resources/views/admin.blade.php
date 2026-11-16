@extends('layouts.app')

@section('title', 'Panel Pengurus & Admin — PPMT Digital')

@section('content')
<div x-data="ppmtAdminPanel()" x-init="init()" class="max-w-6xl mx-auto px-4 py-8 space-y-6">

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

        <!-- Header Panel Admin -->
        <section class="bg-indigo-950 text-white rounded-2xl p-6 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="space-y-1.5">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-800/80 text-indigo-200 text-xs font-semibold">
                    <span>&#9733; Ruang Kerja Tata Kelola &amp; Administrasi PPMT</span>
                </div>
                <h1 class="text-2xl font-extrabold"
                    x-text="'Halo, ' + (user?.display_name || user?.name)"></h1>
                <p class="text-xs text-indigo-200">
                    Anda masuk sebagai warga dengan mandat jabatan aktif. Segala pengesahan dan penyematan jabatan tercatat dalam jejak audit <em>Web of Trust</em>.
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

        <!-- Grid 3 Kolom / 2 Bagian Utama -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

            <!-- Kolom Kiri (5 Kolom): Antrean Verifikasi Sosial & Assisted Onboarding -->
            <div class="lg:col-span-5 space-y-6">

                <!-- Panel 1: Antrean Verifikasi Sosial -->
                <section class="bg-white rounded-2xl p-5 shadow-sm border border-slate-200 space-y-3">
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
                                <div class="text-xs space-y-0.5">
                                    <div class="font-bold text-slate-900" x-text="item.name + ' • Unit ' + item.unit_code"></div>
                                    <div class="text-slate-600" x-text="item.phone + ' (' + item.sub_role.replace('_', ' ') + ')'"></div>
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
                <section x-show="user?.is_global_governor"
                         class="bg-emerald-50/60 rounded-2xl p-5 shadow-sm border border-emerald-200 space-y-4">
                    <div class="border-b border-emerald-200/70 pb-3">
                        <h2 class="text-base font-bold text-emerald-950">Daftarkan Tetangga Terkenal</h2>
                        <p class="text-xs text-emerald-800">Daftarkan tetangga yang sudah Anda kenal secara langsung ke kavlingnya (otomatis aktif dengan status dasar <strong>Warga PPMT</strong>).</p>
                    </div>

                    <form @submit.prevent="submitAssistedRegister()" class="space-y-3">
                        <!-- 1. Nomor WhatsApp (Kunci Login Utama) -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Nomor WhatsApp Tetangga *</label>
                            <input type="tel" x-model="assistForm.phone" required placeholder="0812xxxxxxxx"
                                   class="w-full rounded-xl border-slate-300 text-xs">
                        </div>

                        <!-- 2. Kata Sandi Sementara (Berpasangan dengan Nomor WA) -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Kata Sandi Sementara (Min. 8 Karakter) *</label>
                            <input type="text" x-model="assistForm.password" required minlength="8" placeholder="Contoh: ppmt12345"
                                   class="w-full rounded-xl border-slate-300 text-xs font-mono">
                        </div>

                        <!-- 3. Nama Lengkap -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap Tetangga *</label>
                            <input type="text" x-model="assistForm.name" required placeholder="Contoh: Ahmad Fauzi"
                                   class="w-full rounded-xl border-slate-300 text-xs">
                        </div>

                        <!-- 4. Kavling Tetangga + Info Status Hunian -->
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

                        <!-- 5. Entitas & Peran -->
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Entitas</label>
                                <select x-model="assistForm.entity_type" @change="assistForm.sub_role = assistForm.entity_type === 'owner' ? 'primary_owner' : 'primary_resident'"
                                        class="w-full rounded-xl border-slate-300 text-xs">
                                    <option value="owner">Pemilik Rumah</option>
                                    <option value="resident">Penghuni</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Peran</label>
                                <select x-model="assistForm.sub_role" class="w-full rounded-xl border-slate-300 text-xs">
                                    <template x-if="assistForm.entity_type === 'owner'">
                                        <optgroup label="Pemilik">
                                            <option value="primary_owner">Pemilik Utama</option>
                                            <option value="co_owner">Pemilik Bersama</option>
                                        </optgroup>
                                    </template>
                                    <template x-if="assistForm.entity_type === 'resident'">
                                        <optgroup label="Penghuni">
                                            <option value="primary_resident">Penghuni Utama</option>
                                            <option value="family_member">Anggota Keluarga</option>
                                            <option value="tenant">Penyewa / Kontrak</option>
                                            <option value="caretaker">Penjaga Rumah</option>
                                        </optgroup>
                                    </template>
                                </select>
                            </div>
                        </div>

                        <button type="submit"
                                class="w-full py-2.5 px-4 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold transition cursor-pointer">
                            Daftarkan &amp; Aktifkan Warga
                        </button>
                    </form>
                </section>
            </div>

            <!-- Kolom Kanan (7 Kolom): Penyematan Jabatan & Hak Akses (Admin, Ketua, Koordinator, dll) -->
            <section x-show="user?.is_global_governor"
                     class="lg:col-span-7 bg-white rounded-2xl p-6 shadow-sm border border-slate-200 space-y-5">
                <div class="border-b border-slate-100 pb-3">
                    <h2 class="text-lg font-bold text-slate-900">Penyematan Jabatan &amp; Hak Akses Warga</h2>
                    <p class="text-xs text-slate-500">
                        Admin dan Pengurus bukanlah entitas terpisah, melainkan jabatan akses yang disematkan kepada warga PPMT terverifikasi. Satu warga dapat merangkap lebih dari satu jabatan.
                    </p>
                </div>

                <form @submit.prevent="assignGovernance()" class="bg-indigo-50/60 p-4 rounded-xl border border-indigo-200 space-y-3">
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
                                <option value="admin" x-show="user?.is_operator_aplikasi">Admin Sistem</option>
                                <option value="operator_aplikasi" x-show="user?.is_operator_aplikasi">Operator Aplikasi</option>
                                <option value="ketua_paguyuban">Ketua Paguyuban</option>
                                <option value="sekretaris_paguyuban">Sekretaris Paguyuban</option>
                                <option value="bendahara_paguyuban">Bendahara Paguyuban</option>
                                <option value="koordinator_zona">Koordinator Zona</option>
                                <option value="koordinator_blok">Koordinator Blok</option>
                                <option value="keamanan_lingkungan">Keamanan Lingkungan</option>
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
                            <button type="button" @click="revokeGovernance(item.id)"
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

<script>
function ppmtAdminPanel() {
    return {
        token: localStorage.getItem('ppmt_token'),
        user: null,
        loading: true,
        toastMessage: '',
        toastType: 'success',
        pendingApprovals: [],
        verifiedResidents: [],
        governanceList: [],
        allUnits: [],
        govForm: {
            target_user_id: '',
            role_code: 'koordinator_zona',
            scope_type: 'zone',
            scope_value: 'ZONA-1'
        },
        assistForm: {
            unit_code: '',
            name: '',
            phone: '',
            entity_type: 'owner',
            sub_role: 'primary_owner',
            password: ''
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
            await this.loadPendingApprovals();
            if (this.user?.is_global_governor) {
                await this.loadGovernanceData();
            }
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

        async loadPendingApprovals() {
            const res = await fetch('/api/v1/verifications/pending', { headers: this.headers() });
            if (res.ok) {
                const json = await res.json();
                this.pendingApprovals = json.data || [];
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
            const res = await fetch('/api/v1/governance/assisted-register', {
                method: 'POST',
                headers: this.headers(),
                body: JSON.stringify(this.assistForm)
            });
            const json = await res.json();
            if (res.ok) {
                this.toastType = 'success';
                this.toastMessage = json.message;
                this.assistForm.unit_code = '';
                this.assistForm.name = '';
                this.assistForm.phone = '';
                this.assistForm.password = '';
                await this.loadGovernanceData();
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
                await this.loadPendingApprovals();
            } else {
                this.toastType = 'error';
                this.toastMessage = json.message || 'Gagal mengesahkan warga.';
            }
        }
    }
}
</script>
@endsection
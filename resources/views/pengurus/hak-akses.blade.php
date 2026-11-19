@extends('layouts.app')

@section('title', 'Role Management & Audit Hak Akses — Admin Sistem PPMT')
@section('subpage_title', 'Role Management & Audit Hak Akses')
@section('subpage_back_url', '/pengurus')

@section('content')
<div x-data="ppmtRoleAuditPanel()"
     x-init="init()"
     @keydown.escape.window="showRoleModal = false"
     class="max-w-6xl mx-auto px-4 py-6 sm:py-8 space-y-6">

    <!-- Loading -->
    <div x-show="loading" class="bg-white rounded-2xl p-12 text-center text-sm text-slate-500 border border-slate-200">
        Memuat matriks hak akses &amp; daftar pemegang mandat...
    </div>

    <div x-show="!loading && user" x-cloak class="space-y-6">

        <!-- Toast -->
        <div x-show="toastMessage"
             x-cloak
             :class="toastType === 'error' ? 'bg-rose-50 text-rose-800 border-rose-200' : 'bg-emerald-50 text-emerald-800 border-emerald-200'"
             class="p-4 rounded-xl border text-sm font-medium flex justify-between items-center shadow-sm">
            <span x-text="toastMessage"></span>
            <button type="button" @click="toastMessage = ''" class="text-xs opacity-70 hover:opacity-100 cursor-pointer">✕</button>
        </div>

        <!-- Banner Pemisahan Tata Kelola Teknis vs Organisasi -->
        <section class="bg-slate-900 text-white rounded-2xl p-5 sm:p-6 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="space-y-2 max-w-3xl">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-rose-500/20 border border-rose-400/30 text-rose-200 text-xs font-bold">
                    <span>Teknis Tata Kelola Sistem &bull; Separation of Duties</span>
                </div>
                <h1 class="text-xl sm:text-2xl font-extrabold">Role Management &amp; Audit Hak Akses PPMT</h1>
                <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
                    <strong>Admin Sistem</strong> mengelola skema role, matriks izin &amp; batasan, serta mandat teknis; <strong>Operator Aplikasi</strong> memiliki akses audit matriks (mode baca); dan <strong>Ketua Paguyuban</strong> menyusun kabinet organisasinya secara otonom.
                </p>
            </div>

            <div x-show="user?.is_pure_system_admin" class="shrink-0">
                <button type="button"
                        @click="openCreateRoleModal()"
                        class="w-full sm:w-auto px-4 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-500 text-white text-xs sm:text-sm font-extrabold shadow-sm transition cursor-pointer">
                    + Buat Role Baru
                </button>
            </div>
        </section>

        <!-- Tab Switcher -->
        <div class="flex flex-wrap items-center gap-2 border-b border-slate-200 pb-3">
            <button type="button"
                    @click="tab = 'matrix'"
                    :class="tab === 'matrix' ? 'bg-slate-900 text-white' : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-50'"
                    class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition cursor-pointer">
                Matriks Role, Wewenang &amp; Batasan (<span x-text="matrix.length"></span>)
            </button>
            <button type="button"
                    @click="tab = 'holders'"
                    :class="tab === 'holders' ? 'bg-slate-900 text-white' : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-50'"
                    class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition cursor-pointer">
                Audit Pemegang Role &amp; Mandat Teknis (<span x-text="holders.length"></span>)
            </button>
        </div>

        <!-- ================================================================= -->
        <!-- TAB 1: MATRIKS ROLE, WEWENANG (TERHADAP APA) & BATASAN            -->
        <!-- ================================================================= -->
        <div x-show="tab === 'matrix'" class="space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-2 bg-white p-3.5 rounded-2xl border border-slate-200">
                <div class="flex flex-wrap gap-1.5">
                    <button type="button"
                            @click="domainFilter = 'all'"
                            :class="domainFilter === 'all' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-700'"
                            class="px-3 py-1.5 rounded-lg text-xs font-bold cursor-pointer">
                        Semua Role (<span x-text="matrix.length"></span>)
                    </button>
                    <button type="button"
                            @click="domainFilter = 'system'"
                            :class="domainFilter === 'system' ? 'bg-rose-700 text-white' : 'bg-rose-50 text-rose-800'"
                            class="px-3 py-1.5 rounded-lg text-xs font-bold cursor-pointer">
                        Domain Teknis Sistem
                    </button>
                    <button type="button"
                            @click="domainFilter = 'organization'"
                            :class="domainFilter === 'organization' ? 'bg-emerald-700 text-white' : 'bg-emerald-50 text-emerald-800'"
                            class="px-3 py-1.5 rounded-lg text-xs font-bold cursor-pointer">
                        Domain Organisasi Paguyuban
                    </button>
                </div>
                <span class="text-xs text-slate-500"
                      x-text="user?.is_pure_system_admin ? 'Mode Arsitek (Admin Sistem): Dapat menambah, mengedit, & menghapus role kustom' : 'Mode Audit (Operator): Hanya baca skema role'"></span>
            </div>

            <div class="space-y-4">
                <template x-for="role in filteredMatrix" :key="role.code">
                    <article class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">
                        <!-- Header Role -->
                        <div class="px-5 py-4 bg-slate-50/80 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <div class="flex flex-wrap items-center gap-2">
                                <span :class="role.domain === 'system' ? 'bg-rose-100 text-rose-800 border-rose-200' : 'bg-emerald-100 text-emerald-800 border-emerald-200'"
                                      class="px-2.5 py-0.5 rounded-full text-[11px] font-bold border"
                                      x-text="role.domain_label"></span>
                                <h2 class="text-base font-extrabold text-slate-900" x-text="role.label"></h2>
                                <code class="px-2 py-0.5 rounded bg-slate-200/80 text-slate-700 text-[11px] font-mono"
                                      x-text="role.code"></code>
                                <span x-show="role.is_core"
                                      class="px-2 py-0.5 rounded bg-slate-900 text-white text-[10px] font-bold">
                                    Inti Sistem
                                </span>
                                <span x-show="!role.is_core"
                                      class="px-2 py-0.5 rounded bg-amber-100 text-amber-900 border border-amber-200 text-[10px] font-bold">
                                    Role Kustom
                                </span>
                            </div>

                            <div class="flex flex-wrap items-center gap-2 text-xs text-slate-600">
                                <span class="px-2 py-0.5 rounded bg-white border border-slate-200 font-semibold"
                                      x-text="'Cakupan: ' + (role.scope === 'global' ? 'Seluruh Perumahan' : (role.scope === 'zone' ? 'Per Zona' : 'Per Blok'))"></span>
                                <span class="px-2 py-0.5 rounded bg-indigo-50 text-indigo-800 border border-indigo-200 font-bold"
                                      x-text="countHoldersForRole(role.code) + ' personel aktif'"></span>

                                <!-- Tombol Edit & Hapus (Hanya Admin Sistem Murni) -->
                                <template x-if="user?.is_pure_system_admin && role.id">
                                    <div class="flex items-center gap-1.5 ml-1">
                                        <button type="button"
                                                @click="openEditRoleModal(role)"
                                                class="px-2.5 py-1 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-[11px] font-bold cursor-pointer">
                                            Edit Matriks
                                        </button>
                                        <button x-show="!role.is_core"
                                                type="button"
                                                @click="deleteRoleDefinition(role)"
                                                class="px-2.5 py-1 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 border border-rose-200 text-[11px] font-bold cursor-pointer">
                                            Hapus
                                        </button>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- 2 Kolom: Terhadap Apa (Wewenang) vs Apa Batasannya -->
                        <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-5 text-xs sm:text-sm">
                            <div class="space-y-2">
                                <div class="text-xs font-extrabold uppercase tracking-wider text-emerald-800">
                                    &#10003; Wewenang &amp; Akses Modul (Terhadap Apa)
                                </div>
                                <ul class="space-y-1.5 text-slate-700">
                                    <template x-for="(perm, i) in role.permissions" :key="i">
                                        <li class="flex items-start gap-2 leading-relaxed">
                                            <span class="text-emerald-600 font-bold mt-0.5">&bull;</span>
                                            <span x-text="perm"></span>
                                        </li>
                                    </template>
                                </ul>
                            </div>

                            <div class="space-y-2 md:border-l md:border-slate-100 md:pl-5">
                                <div class="text-xs font-extrabold uppercase tracking-wider text-rose-800">
                                    &#10005; Batasan &amp; Larangan Tegas
                                </div>
                                <ul class="space-y-1.5 text-slate-700">
                                    <template x-for="(rest, j) in role.restrictions" :key="j">
                                        <li class="flex items-start gap-2 leading-relaxed">
                                            <span class="text-rose-600 font-bold mt-0.5">&bull;</span>
                                            <span x-text="rest"></span>
                                        </li>
                                    </template>
                                </ul>
                            </div>
                        </div>

                        <div class="px-5 py-2.5 bg-slate-50 border-t border-slate-100 text-[11px] text-slate-500">
                            Otoritas Pengangkat: <strong class="text-slate-700" x-text="role.appointed_by"></strong>
                        </div>
                    </article>
                </template>
            </div>
        </div>

        <!-- ================================================================= -->
        <!-- TAB 2: AUDIT PEMEGANG ROLE & PENETAPAN MANDAT TEKNIS              -->
        <!-- ================================================================= -->
        <div x-show="tab === 'holders'" class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

            <!-- Daftar Audit Personel Aktif (7 Kolom) -->
            <section class="lg:col-span-7 bg-white rounded-2xl p-5 sm:p-6 border border-slate-200 shadow-sm space-y-4">
                <div class="border-b border-slate-100 pb-3">
                    <h2 class="text-base sm:text-lg font-bold text-slate-900">Audit Personel Pemegang Role Aktif</h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Menampilkan seluruh warga yang memiliki hak akses khusus beserta kombinasi jabatan teknis maupun organisasinya.
                    </p>
                </div>

                <div class="divide-y divide-slate-100 border border-slate-200 rounded-xl overflow-hidden">
                    <template x-for="h in holders" :key="h.user_id">
                        <div class="p-4 space-y-2.5">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <div class="font-bold text-sm text-slate-900" x-text="h.name"></div>
                                    <div class="text-xs text-slate-500 font-mono"
                                         x-text="'Kav. ' + h.unit_codes + ' • ' + h.contact"></div>
                                </div>
                                <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 text-[11px] font-bold"
                                      x-text="h.roles.length + ' role aktif'"></span>
                            </div>

                            <div class="space-y-1.5">
                                <template x-for="r in h.roles" :key="r.id">
                                    <div class="px-3 py-2 rounded-lg bg-slate-50 border border-slate-200/80 flex items-center justify-between gap-2 text-xs">
                                        <div class="space-y-0.5">
                                            <div class="flex items-center gap-1.5">
                                                <span :class="r.domain === 'system' ? 'bg-rose-100 text-rose-800' : 'bg-emerald-100 text-emerald-800'"
                                                      class="px-1.5 py-0.2 rounded text-[10px] font-bold uppercase"
                                                      x-text="r.domain === 'system' ? 'Teknis' : 'Organisasi'"></span>
                                                <span class="font-bold text-slate-800" x-text="r.display_label"></span>
                                            </div>
                                            <div class="text-[11px] text-slate-400"
                                                 x-text="'Disematkan oleh: ' + r.assigned_by"></div>
                                        </div>

                                        <button x-show="r.can_revoke"
                                                type="button"
                                                @click="revokeRole(r)"
                                                class="px-2.5 py-1 rounded bg-white hover:bg-rose-50 text-rose-600 border border-rose-200 font-semibold cursor-pointer">
                                            Cabut
                                        </button>
                                        <span x-show="!r.can_revoke"
                                              class="text-[10px] text-slate-400 italic">
                                            Ranah Ketua / Diri Sendiri
                                        </span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </section>

            <!-- Panel Penetapan Role Teknis & Pengukuhan Ketua (5 Kolom) -->
            <section class="lg:col-span-5 bg-rose-50/50 rounded-2xl p-5 sm:p-6 border border-rose-200 shadow-sm space-y-4">
                <div class="border-b border-rose-200/70 pb-3">
                    <h2 class="text-base font-bold text-rose-950">Penetapan Mandat Teknis &amp; Ketua</h2>
                    <p class="text-xs text-rose-900/80 mt-0.5 leading-relaxed">
                        Menetapkan <strong>Admin Sistem</strong>, <strong>Operator Aplikasi</strong>, serta mengukuhkan <strong>Ketua Paguyuban</strong> hasil musyawarah warga.
                    </p>
                </div>

                <form @submit.prevent="assignTechnicalRole()" class="space-y-3.5">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Warga Terverifikasi *</label>
                        <select x-model="assignForm.target_user_id" required class="w-full rounded-xl border-slate-300 bg-white text-sm">
                            <option value="">-- Pilih Warga --</option>
                            <template x-for="r in residents" :key="r.id">
                                <option :value="r.id" x-text="(r.display_name || r.name) + ' (' + r.units.map(u => u.unit_code).join(', ') + ')'"></option>
                            </template>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Role Teknis / Pimpinan Inti *</label>
                        <select x-model="assignForm.role_code" required class="w-full rounded-xl border-slate-300 bg-white text-sm">
                            <template x-for="opt in (user?.assignable_role_options || [])" :key="opt.code">
                                <option :value="opt.code" x-text="opt.label"></option>
                            </template>
                        </select>
                    </div>

                    <button type="submit"
                            class="w-full py-2.5 px-4 rounded-xl bg-rose-700 hover:bg-rose-800 text-white text-sm font-bold shadow-sm transition cursor-pointer">
                        + Tetapkan Mandat
                    </button>
                </form>
            </section>

        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- MODAL ROLE & PERMISSION BUILDER (KHUSUS ADMIN SISTEM MURNI)           -->
    <!-- ===================================================================== -->
    <div x-show="showRoleModal"
         x-cloak
         class="fixed inset-0 z-50 !mt-0 flex items-center justify-center p-3 sm:p-4 bg-slate-900/55 backdrop-blur-[1px] overflow-y-auto">
        <div @click.away="showRoleModal = false"
             class="bg-white rounded-2xl shadow-2xl border border-slate-200 w-full max-w-[calc(100vw-1.5rem)] sm:max-w-2xl max-h-[90dvh] flex flex-col overflow-hidden my-auto">

            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between gap-3 shrink-0">
                <div>
                    <h3 class="text-base font-bold text-slate-900"
                        x-text="editingRoleId ? ('Edit Matriks Role: ' + roleForm.label) : 'Buat Nomenklatur Role & Matriks Baru'"></h3>
                    <p class="text-xs text-slate-500">Tentukan nomenklatur jabatan, domain tata kelola, wewenang, dan batasan aksesnya.</p>
                </div>
                <button type="button" @click="showRoleModal = false" class="text-slate-400 hover:text-slate-700 text-sm p-1 cursor-pointer">✕</button>
            </div>

            <form @submit.prevent="submitRoleDefinition()" class="p-5 space-y-4 overflow-y-auto">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Tampilan Jabatan *</label>
                        <input type="text" x-model="roleForm.label" required maxlength="100"
                               placeholder="Contoh: Seksi Kebersihan & Lingkungan"
                               class="w-full rounded-xl border-slate-300 text-sm">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Kode Sistem (Slug Unik) *</label>
                        <input type="text"
                               x-model="roleForm.role_code"
                               :disabled="Boolean(editingRoleId)"
                               @input="roleForm.role_code = (roleForm.role_code || '').toLowerCase().replace(/[^a-z0-9_]/g, '')"
                               required
                               maxlength="50"
                               placeholder="contoh: seksi_kebersihan"
                               class="w-full rounded-xl border-slate-300 text-sm font-mono disabled:bg-slate-100 disabled:text-slate-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3.5">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Domain Tata Kelola *</label>
                        <select x-model="roleForm.domain"
                                :disabled="Boolean(editingRoleId)"
                                class="w-full rounded-xl border-slate-300 text-sm disabled:bg-slate-100">
                            <option value="organization">Organisasi (Kabinet Ketua)</option>
                            <option value="system">Teknis Sistem (Admin)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Cakupan Wilayah *</label>
                        <select x-model="roleForm.scope"
                                :disabled="roleForm.is_core"
                                class="w-full rounded-xl border-slate-300 text-sm disabled:bg-slate-100">
                            <option value="global">Global (Seluruh Perumahan)</option>
                            <option value="zone">Per Zona</option>
                            <option value="block">Per Blok</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Otoritas Pengangkat *</label>
                        <input type="text" x-model="roleForm.appointed_by" required maxlength="150"
                               placeholder="Ketua Paguyuban"
                               class="w-full rounded-xl border-slate-300 text-sm">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Sub-Label Kelompok</label>
                    <input type="text" x-model="roleForm.domain_label" maxlength="100"
                           placeholder="Contoh: Kabinet / Bidang Operasional"
                           class="w-full rounded-xl border-slate-300 text-sm">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-1">
                    <div>
                        <label class="block text-xs font-bold text-emerald-800 mb-1">
                            &#10003; Wewenang &amp; Akses (1 poin per baris) *
                        </label>
                        <textarea x-model="roleForm.permissions_text"
                                  required
                                  rows="5"
                                  placeholder="Memantau jadwal pengangkutan sampah&#10;Melihat status iuran sampah per kavling"
                                  class="w-full rounded-xl border-emerald-300 focus:border-emerald-600 focus:ring-emerald-600 text-xs sm:text-sm leading-relaxed"></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-rose-800 mb-1">
                            &#10005; Batasan &amp; Larangan (1 poin per baris) *
                        </label>
                        <textarea x-model="roleForm.restrictions_text"
                                  required
                                  rows="5"
                                  placeholder="Dilarang mengubah jurnal kas Bendahara&#10;Tertutup dari angka mentah NIB SHM & NIK/KK"
                                  class="w-full rounded-xl border-rose-300 focus:border-rose-600 focus:ring-rose-600 text-xs sm:text-sm leading-relaxed"></textarea>
                    </div>
                </div>

                <div class="pt-3 flex justify-end gap-2 border-t border-slate-100">
                    <button type="button" @click="showRoleModal = false"
                            class="px-4 py-2 rounded-xl border border-slate-300 text-slate-700 text-xs font-semibold cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" :disabled="saving"
                            class="px-5 py-2 rounded-xl bg-rose-700 hover:bg-rose-800 text-white text-xs font-bold shadow-sm cursor-pointer">
                        <span x-show="!saving" x-text="editingRoleId ? 'Simpan Perubahan Matriks' : 'Buat Role & Simpan ke Sistem'"></span>
                        <span x-show="saving">Menyimpan...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function ppmtRoleAuditPanel() {
    return {
        token: localStorage.getItem('ppmt_token'),
        user: null,
        loading: true,
        saving: false,
        tab: 'matrix',
        domainFilter: 'all',
        matrix: [],
        holders: [],
        residents: [],
        toastMessage: '',
        toastType: 'success',
        showRoleModal: false,
        editingRoleId: null,
        roleForm: {
            role_code: '',
            label: '',
            domain: 'organization',
            domain_label: 'Kabinet / Bidang Operasional',
            scope: 'global',
            appointed_by: 'Ketua Paguyuban',
            permissions_text: '',
            restrictions_text: '',
            is_core: false
        },
        assignForm: {
            target_user_id: '',
            role_code: 'operator_aplikasi',
            scope_type: 'global',
            scope_value: ''
        },

        async init() {
            if (!this.token) {
                window.location.href = '/';
                return;
            }
            try {
                const meRes = await fetch('/api/v1/me', { headers: this.headers() });
                if (!meRes.ok) throw new Error('Unauthorized');
                const meJson = await meRes.json();
                this.user = meJson.data;

                if (!this.user?.is_operator_aplikasi) {
                    window.location.href = '/pengurus';
                    return;
                }

                if (this.user.assignable_role_options?.length > 0) {
                    this.assignForm.role_code = this.user.assignable_role_options[0].code;
                }

                await Promise.all([this.loadAuditData(), this.loadResidents()]);
            } catch (e) {
                localStorage.removeItem('ppmt_token');
                window.location.href = '/';
            } finally {
                this.loading = false;
            }
        },

        headers() {
            return {
                'Authorization': 'Bearer ' + this.token,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            };
        },

        get filteredMatrix() {
            if (this.domainFilter === 'all') return this.matrix;
            return this.matrix.filter(r => r.domain === this.domainFilter);
        },

        countHoldersForRole(roleCode) {
            return this.holders.filter(h => h.roles.some(r => r.role_code === roleCode)).length;
        },

        async loadAuditData() {
            const res = await fetch('/api/v1/governance/access-matrix', { headers: this.headers() });
            if (res.ok) {
                const json = await res.json();
                this.matrix = json.data?.matrix || [];
                this.holders = json.data?.holders || [];
            }
        },

        async loadResidents() {
            const res = await fetch('/api/v1/residents/directory', { headers: this.headers() });
            if (res.ok) {
                const json = await res.json();
                this.residents = json.data || [];
            }
        },

        openCreateRoleModal() {
            this.editingRoleId = null;
            this.roleForm = {
                role_code: '',
                label: '',
                domain: 'organization',
                domain_label: 'Kabinet / Bidang Operasional',
                scope: 'global',
                appointed_by: 'Ketua Paguyuban',
                permissions_text: '',
                restrictions_text: 'Tertutup penuh dari angka mentah NIB SHM dan NIK/KK terenkripsi',
                is_core: false
            };
            this.showRoleModal = true;
        },

        openEditRoleModal(role) {
            this.editingRoleId = role.id;
            this.roleForm = {
                role_code: role.code,
                label: role.label,
                domain: role.domain,
                domain_label: role.domain_label,
                scope: role.scope,
                appointed_by: role.appointed_by,
                permissions_text: (role.permissions || []).join('\n'),
                restrictions_text: (role.restrictions || []).join('\n'),
                is_core: Boolean(role.is_core)
            };
            this.showRoleModal = true;
        },

        async submitRoleDefinition() {
            const permissions = this.roleForm.permissions_text.split('\n').map(s => s.trim()).filter(Boolean);
            const restrictions = this.roleForm.restrictions_text.split('\n').map(s => s.trim()).filter(Boolean);

            if (permissions.length === 0 || restrictions.length === 0) {
                this.toastType = 'error';
                this.toastMessage = 'Isi minimal 1 poin wewenang dan 1 poin batasan.';
                return;
            }

            this.saving = true;
            try {
                const isEdit = Boolean(this.editingRoleId);
                const url = isEdit
                    ? `/api/v1/governance/role-definitions/${this.editingRoleId}`
                    : '/api/v1/governance/role-definitions';
                const method = isEdit ? 'PATCH' : 'POST';

                const res = await fetch(url, {
                    method,
                    headers: this.headers(),
                    body: JSON.stringify({
                        role_code: this.roleForm.role_code,
                        label: this.roleForm.label,
                        domain: this.roleForm.domain,
                        domain_label: this.roleForm.domain_label,
                        scope: this.roleForm.scope,
                        appointed_by: this.roleForm.appointed_by,
                        permissions,
                        restrictions
                    })
                });
                const json = await res.json();
                if (res.ok) {
                    this.showRoleModal = false;
                    this.toastType = 'success';
                    this.toastMessage = json.message;
                    await this.loadAuditData();
                } else {
                    this.toastType = 'error';
                    this.toastMessage = json.message || 'Gagal menyimpan definisi role.';
                }
            } finally {
                this.saving = false;
            }
        },

        async deleteRoleDefinition(role) {
            if (!confirm(`Hapus permanen definisi role kustom "${role.label}" (${role.code})?`)) return;
            const res = await fetch(`/api/v1/governance/role-definitions/${role.id}`, {
                method: 'DELETE',
                headers: this.headers()
            });
            const json = await res.json();
            if (res.ok) {
                this.toastType = 'success';
                this.toastMessage = json.message;
                await this.loadAuditData();
            } else {
                this.toastType = 'error';
                this.toastMessage = json.message || 'Gagal menghapus definisi role.';
            }
        },

        async assignTechnicalRole() {
            const res = await fetch('/api/v1/governance/assign', {
                method: 'POST',
                headers: this.headers(),
                body: JSON.stringify(this.assignForm)
            });
            const json = await res.json();
            if (res.ok) {
                this.toastType = 'success';
                this.toastMessage = json.message;
                await this.loadAuditData();
            } else {
                this.toastType = 'error';
                this.toastMessage = json.message || 'Gagal menetapkan mandat.';
            }
        },

        async revokeRole(role) {
            if (!confirm(`Cabut jabatan "${role.display_label}"?`)) return;
            const res = await fetch(`/api/v1/governance/roles/${role.id}`, {
                method: 'DELETE',
                headers: this.headers()
            });
            const json = await res.json();
            if (res.ok) {
                this.toastType = 'success';
                this.toastMessage = json.message;
                await this.loadAuditData();
            } else {
                this.toastType = 'error';
                this.toastMessage = json.message || 'Gagal mencabut jabatan.';
            }
        }
    }
}
</script>
@endpush
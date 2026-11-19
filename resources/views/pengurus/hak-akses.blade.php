@extends('layouts.app')

@section('title', 'Role Management & Audit Hak Akses — Admin Sistem PPMT')
@section('subpage_title', 'Role Management & Audit Hak Akses')
@section('subpage_back_url', '/pengurus')

@section('content')
<div x-data="ppmtRoleAuditPanel()" x-init="init()" class="max-w-6xl mx-auto px-4 py-6 sm:py-8 space-y-6">

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
        <section class="bg-slate-900 text-white rounded-2xl p-5 sm:p-6 shadow-sm space-y-3">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-rose-500/20 border border-rose-400/30 text-rose-200 text-xs font-bold">
                <span>Teknis Tata Kelola Sistem &bull; Separation of Duties</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-extrabold">Role Management &amp; Audit Hak Akses PPMT</h1>
            <p class="text-xs sm:text-sm text-slate-300 leading-relaxed max-w-3xl">
                Sistem memisahkan <strong>Domain Teknis</strong> (dikelola Admin Sistem: penetapan Operator &amp; pengukuhan mandat Ketua Paguyuban) dengan <strong>Domain Organisasi</strong> (hak prerogatif Ketua Paguyuban menyusun Sekretaris, Bendahara, Seksi Bidang, &amp; Koordinator).
            </p>
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
            <!-- Filter Domain -->
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
                <span class="text-xs text-slate-500">Sumber aturan langsung dari model kebijakan otorisasi server</span>
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
                            </div>

                            <div class="flex flex-wrap items-center gap-2 text-xs text-slate-600">
                                <span class="px-2 py-0.5 rounded bg-white border border-slate-200 font-semibold"
                                      x-text="'Cakupan: ' + (role.scope === 'global' ? 'Seluruh Perumahan' : (role.scope === 'zone' ? 'Per Zona' : 'Per Blok'))"></span>
                                <span class="px-2 py-0.5 rounded bg-indigo-50 text-indigo-800 border border-indigo-200 font-bold"
                                      x-text="countHoldersForRole(role.code) + ' personel aktif'"></span>
                            </div>
                        </div>

                        <!-- 2 Kolom: Terhadap Apa (Wewenang) vs Apa Batasannya -->
                        <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-5 text-xs sm:text-sm">
                            <!-- Wewenang -->
                            <div class="space-y-2">
                                <div class="text-xs font-extrabold uppercase tracking-wider text-emerald-800 flex items-center gap-1.5">
                                    <span>&#10003; Wewenang &amp; Akses Modul (Terhadap Apa)</span>
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

                            <!-- Batasan -->
                            <div class="space-y-2 md:border-l md:border-slate-100 md:pl-5">
                                <div class="text-xs font-extrabold uppercase tracking-wider text-rose-800 flex items-center gap-1.5">
                                    <span>&#10005; Batasan &amp; Larangan Tegas</span>
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

                        <!-- Footer Otoritas Pengangkat -->
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
                        Khusus Admin Sistem: menetapkan <strong>Admin Sistem</strong>, <strong>Operator Aplikasi</strong>, serta mengukuhkan <strong>Ketua Paguyuban</strong> hasil musyawarah warga.
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
</div>
@endsection

@push('scripts')
<script>
function ppmtRoleAuditPanel() {
    return {
        token: localStorage.getItem('ppmt_token'),
        user: null,
        loading: true,
        tab: 'matrix',
        domainFilter: 'all',
        matrix: [],
        holders: [],
        residents: [],
        toastMessage: '',
        toastType: 'success',
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
@extends('layouts.app')

@section('content')
<div x-data="ppmtGateway()" x-init="init()" class="max-w-6xl mx-auto px-4 py-8 space-y-10">

    <!-- Hero Banner -->
    <section class="bg-gradient-to-br from-emerald-900 via-emerald-800 to-teal-900 text-white rounded-2xl p-6 sm:p-10 shadow-lg">
        <div class="max-w-2xl space-y-4">
            <span class="inline-block px-3 py-1 rounded-full bg-emerald-700/80 text-emerald-100 text-xs font-semibold tracking-wider uppercase">
                Portal Resmi Lingkungan &bull; 91 Kavling Terintegrasi
            </span>
            <h1 class="text-2xl sm:text-4xl font-extrabold leading-tight">
                Ekosistem Digital Warga Pesona Mandiri Turen
            </h1>
            <p class="text-emerald-100 text-sm sm:text-base leading-relaxed">
                Satu pintu untuk pendataan hunian berbasis verifikasi sosial tetangga, direktori warga ramah privasi, serta Pasar Tetangga antar-warga tanpa potongan biaya.
            </p>
            <div class="pt-2 flex flex-wrap gap-3 text-xs font-medium">
                <span class="px-3 py-1.5 rounded-lg bg-white/10 backdrop-blur">✓ Verifikasi Sosial Web-of-Trust</span>
                <span class="px-3 py-1.5 rounded-lg bg-white/10 backdrop-blur">✓ Enkripsi Identitas AES-256</span>
                <span class="px-3 py-1.5 rounded-lg bg-white/10 backdrop-blur">✓ Pemisahan Pemilik & Penghuni</span>
            </div>
        </div>
    </section>

    <!-- Grid Utama: Peta Kavling (Kiri/Atas) & Form Masuk/Klaim (Kanan/Bawah) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">

        <!-- Kolom Kiri (7 Kolom): Site Plan Interaktif 91 Kavling -->
        <section id="peta-kavling" class="lg:col-span-7 bg-white rounded-2xl p-5 sm:p-6 shadow-sm border border-slate-200 space-y-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-4">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Peta Kavling PPMT (<span x-text="totalUnits"></span> Unit)</h2>
                    <p class="text-xs text-slate-500">Ketuk nomor rumah Anda untuk langsung memilih unit pada formulir pendaftaran.</p>
                </div>
                <div class="flex flex-wrap gap-2 text-[11px]">
                    <span class="inline-flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-slate-200 border border-slate-300"></span> Belum Klaim</span>
                    <span class="inline-flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-amber-200 border border-amber-400"></span> Menunggu</span>
                    <span class="inline-flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-emerald-200 border border-emerald-400"></span> Terisi</span>
                </div>
            </div>

            <!-- Loading State -->
            <div x-show="loadingUnits" class="py-12 text-center text-sm text-slate-400">
                Memuat data 91 kavling dari server API...
            </div>

            <!-- Daftar Blok Kavling -->
            <div x-show="!loadingUnits" class="space-y-5">
                <template x-for="(groupUnits, blockName) in groupedUnits" :key="blockName">
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-600"
                                x-text="formatBlockTitle(blockName)"></h3>
                            <span class="text-[11px] text-slate-400" x-text="groupUnits.length + ' unit'"></span>
                        </div>

                        <div class="grid grid-cols-5 sm:grid-cols-7 gap-2">
                            <template x-for="unit in groupUnits" :key="unit.id">
                                <button type="button"
                                        @click="selectUnit(unit)"
                                        :disabled="unit.unit_type === 'fasum'"
                                        :class="{
                                            'ring-2 ring-emerald-600 bg-emerald-600 text-white font-bold': claimForm.unit_code === unit.code,
                                            'bg-teal-50 text-teal-800 border-teal-300 cursor-default': unit.unit_type === 'fasum',
                                            'bg-emerald-50 text-emerald-900 border-emerald-300 hover:bg-emerald-100': claimForm.unit_code !== unit.code && ['occupied', 'rented'].includes(unit.occupancy_status),
                                            'bg-amber-50 text-amber-900 border-amber-300 hover:bg-amber-100': claimForm.unit_code !== unit.code && unit.occupancy_status === 'pending',
                                            'bg-slate-100 text-slate-700 border-slate-200 hover:bg-slate-200': claimForm.unit_code !== unit.code && unit.occupancy_status === 'unclaimed' && unit.unit_type !== 'fasum'
                                        }"
                                        class="px-2 py-2 rounded-lg border text-xs text-center transition flex flex-col items-center justify-center">
                                    <span x-text="unit.code"></span>
                                    <span class="text-[9px] opacity-75" x-text="unit.zone.replace('ZONA-', 'Z')"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </section>

        <!-- Kolom Kanan (5 Kolom): Tab Masuk & Klaim Unit -->
        <section id="akses-warga" class="lg:col-span-5 bg-white rounded-2xl p-5 sm:p-6 shadow-sm border border-slate-200 space-y-5 sticky top-20">
            <!-- Switcher Tab -->
            <div class="grid grid-cols-2 bg-slate-100 p-1 rounded-xl text-sm font-semibold">
                <button type="button"
                        @click="activeTab = 'claim'; alertMessage = ''"
                        :class="activeTab === 'claim' ? 'bg-white text-emerald-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                        class="py-2 rounded-lg transition">
                    Klaim Unit Baru
                </button>
                <button type="button"
                        @click="activeTab = 'login'; alertMessage = ''"
                        :class="activeTab === 'login' ? 'bg-white text-emerald-900 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                        class="py-2 rounded-lg transition">
                    Masuk Warga
                </button>
            </div>

            <!-- Notifikasi Error / Sukses -->
            <div x-show="alertMessage"
                 :class="alertType === 'error' ? 'bg-rose-50 text-rose-800 border-rose-200' : 'bg-emerald-50 text-emerald-800 border-emerald-200'"
                 class="p-3.5 rounded-xl border text-xs leading-relaxed"
                 x-text="alertMessage">
            </div>

            <!-- FORM 1: KLAIM UNIT / PENDAFTARAN WARGA -->
            <form x-show="activeTab === 'claim'" @submit.prevent="submitClaim()" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Kavling / Unit Hunian *</label>
                    <select x-model="claimForm.unit_code" required
                            class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                        <option value="">-- Pilih atau Ketuk Unit di Peta --</option>
                        <template x-for="unit in selectableUnits" :key="unit.id">
                            <option :value="unit.code" x-text="unit.code + ' (' + unit.zone + ')'"></option>
                        </template>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap Sesuai Identitas *</label>
                    <input type="text" x-model="claimForm.name" required placeholder="Contoh: Budi Santoso"
                           class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Nomor WhatsApp Aktif *</label>
                    <input type="tel" x-model="claimForm.phone" required placeholder="0812xxxxxxxx"
                           class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Status Keterkaitan *</label>
                        <select x-model="claimForm.entity_type" @change="updateDefaultSubRole()"
                                class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                            <option value="owner">Pemilik Rumah</option>
                            <option value="resident">Penghuni</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Peran Spesifik *</label>
                        <select x-model="claimForm.sub_role"
                                class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                            <template x-if="claimForm.entity_type === 'owner'">
                                <optgroup label="Entitas Pemilik">
                                    <option value="primary_owner">Pemilik Utama</option>
                                    <option value="co_owner">Pemilik Bersama (Pasangan)</option>
                                </optgroup>
                            </template>
                            <template x-if="claimForm.entity_type === 'resident'">
                                <optgroup label="Entitas Penghuni">
                                    <option value="primary_resident">Penghuni Utama</option>
                                    <option value="family_member">Anggota Keluarga</option>
                                    <option value="tenant">Penyewa / Kontrak</option>
                                    <option value="caretaker">Penjaga Rumah</option>
                                </optgroup>
                            </template>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Kata Sandi (Minimal 8 Karakter) *</label>
                    <input type="password" x-model="claimForm.password" required minlength="8" placeholder="••••••••"
                           class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                        Kode Undangan Keluarga/Pemilik <span class="text-slate-400 font-normal">(Opsional)</span>
                    </label>
                    <input type="text" x-model="claimForm.invite_token" placeholder="Kosongkan jika mendaftar mandiri"
                           class="w-full rounded-xl border-slate-300 text-xs font-mono focus:border-emerald-600 focus:ring-emerald-600">
                </div>

                <button type="submit" :disabled="submitting"
                        class="w-full py-2.5 px-4 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white font-semibold text-sm shadow transition disabled:opacity-50">
                    <span x-show="!submitting">Ajukan Klaim & Daftar Akun</span>
                    <span x-show="submitting">Memproses Pendaftaran...</span>
                </button>
            </form>

            <!-- FORM 2: LOGIN WARGA -->
            <form x-show="activeTab === 'login'" @submit.prevent="submitLogin()" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Nomor WhatsApp Terdaftar</label>
                    <input type="tel" x-model="loginForm.phone" required placeholder="0812xxxxxxxx"
                           class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Kata Sandi</label>
                    <input type="password" x-model="loginForm.password" required placeholder="••••••••"
                           class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                </div>

                <button type="submit" :disabled="submitting"
                        class="w-full py-2.5 px-4 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white font-semibold text-sm shadow transition disabled:opacity-50">
                    <span x-show="!submitting">Masuk ke Portal Warga</span>
                    <span x-show="submitting">Memverifikasi...</span>
                </button>
            </form>
        </section>
    </div>
</div>

<script>
function ppmtGateway() {
    return {
        units: [],
        totalUnits: 0,
        loadingUnits: true,
        activeTab: 'claim',
        submitting: false,
        alertMessage: '',
        alertType: 'success',
        claimForm: {
            unit_code: '',
            name: '',
            phone: '',
            entity_type: 'owner',
            sub_role: 'primary_owner',
            password: '',
            invite_token: ''
        },
        loginForm: {
            phone: '',
            password: ''
        },

        async init() {
            // Tangkap parameter URL jika warga membuka tautan undangan (?unit=C-21&invite=xxxx)
            const params = new URLSearchParams(window.location.search);
            if (params.get('unit')) this.claimForm.unit_code = params.get('unit');
            if (params.get('invite')) this.claimForm.invite_token = params.get('invite');

            await this.fetchUnits();
        },

        async fetchUnits() {
            try {
                const res = await fetch('/api/v1/public/units');
                const json = await res.json();
                this.units = json.data || [];
                this.totalUnits = json.total || 0;
            } catch (e) {
                this.alertType = 'error';
                this.alertMessage = 'Gagal memuat data kavling dari server.';
            } finally {
                this.loadingUnits = false;
            }
        },

        get groupedUnits() {
            return this.units.reduce((groups, unit) => {
                if (!groups[unit.block]) groups[unit.block] = [];
                groups[unit.block].push(unit);
                return groups;
            }, {});
        },

        get selectableUnits() {
            return this.units.filter(u => u.unit_type !== 'fasum');
        },

        formatBlockTitle(block) {
            const titles = {
                'R': 'Ruko Depan (R1 – R2)',
                'A': 'Blok A (A-1 s/d A-25 • Tanpa No. 13)',
                'B': 'Blok B (B-1 s/d B-24 • Tanpa No. 13)',
                'C': 'Blok C (C-1 s/d C-22 • Tanpa No. 13)',
                'D': 'Blok D (D-1 s/d D-17 • Tanpa No. 13)',
                'FASUM': 'Fasilitas Umum'
            };
            return titles[block] || 'Blok ' + block;
        },

        selectUnit(unit) {
            if (unit.unit_type === 'fasum') return;
            this.claimForm.unit_code = unit.code;
            this.activeTab = 'claim';
            document.getElementById('akses-warga').scrollIntoView({ behavior: 'smooth' });
        },

        updateDefaultSubRole() {
            this.claimForm.sub_role = this.claimForm.entity_type === 'owner' ? 'primary_owner' : 'primary_resident';
        },

        async submitClaim() {
            this.submitting = true;
            this.alertMessage = '';
            try {
                const res = await fetch('/api/v1/auth/register-claim', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify(this.claimForm)
                });
                const json = await res.json();

                if (!res.ok) {
                    this.alertType = 'error';
                    this.alertMessage = json.message || 'Periksa kembali data pendaftaran Anda.';
                    return;
                }

                localStorage.setItem('ppmt_token', json.token);
                this.alertType = 'success';
                this.alertMessage = json.message;
                setTimeout(() => window.location.href = '/dashboard', 1200);
            } catch (e) {
                this.alertType = 'error';
                this.alertMessage = 'Terjadi gangguan koneksi ke server.';
            } finally {
                this.submitting = false;
            }
        },

        async submitLogin() {
            this.submitting = true;
            this.alertMessage = '';
            try {
                const res = await fetch('/api/v1/auth/login', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify(this.loginForm)
                });
                const json = await res.json();

                if (!res.ok) {
                    this.alertType = 'error';
                    this.alertMessage = json.message || 'Gagal masuk ke akun.';
                    return;
                }

                localStorage.setItem('ppmt_token', json.token);
                window.location.href = '/dashboard';
            } catch (e) {
                this.alertType = 'error';
                this.alertMessage = 'Terjadi gangguan koneksi ke server.';
            } finally {
                this.submitting = false;
            }
        }
    }
}
</script>
@endsection
@extends('layouts.app')

@section('title', 'Pendaftaran Warga Baru — PPMT Digital')

@section('content')
<div x-data="ppmtRegisterPage()" x-init="init()" class="max-w-lg mx-auto px-4 py-8 sm:py-12">

    <!-- Navigasi Kembali -->
    <div class="mb-4">
        <a href="/" class="inline-flex items-center gap-1.5 text-xs font-semibold text-emerald-800 hover:text-emerald-950">
            <span>&larr;</span>
            <span>Kembali ke Beranda Publik</span>
        </a>
    </div>

    <div class="bg-white rounded-2xl shadow-xl border border-slate-200 overflow-hidden">
        <!-- Header Kartu -->
        <div class="bg-emerald-950 text-white px-6 py-5 space-y-3">
            <div class="space-y-1">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-emerald-800/80 border border-emerald-700 text-emerald-100 text-[11px] font-semibold">
                    Perumahan Pesona Mandiri Turen
                </span>
                <h1 class="text-lg sm:text-xl font-extrabold tracking-tight">Pendaftaran Warga Baru PPMT</h1>
            </div>

            <!-- Indikator Progres 4 Langkah -->
            <div class="space-y-1.5 pt-1">
                <div class="flex items-center justify-between text-[11px] font-semibold text-emerald-200">
                    <span x-text="`Langkah ${step} dari 4`"></span>
                    <span class="text-amber-300" x-text="stepTitle"></span>
                </div>
                <div class="grid grid-cols-4 gap-1.5">
                    <template x-for="n in 4" :key="n">
                        <div class="h-1.5 rounded-full transition-all duration-300"
                             :class="n <= step ? 'bg-amber-400' : 'bg-emerald-800'">
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <div class="p-6 space-y-5">
            <!-- Notifikasi Error / Sukses -->
            <div x-show="alertMessage"
                 x-cloak
                 :class="alertType === 'error' ? 'bg-rose-50 text-rose-800 border-rose-200' : 'bg-emerald-50 text-emerald-800 border-emerald-200'"
                 class="p-3.5 rounded-xl border text-xs leading-relaxed"
                 x-text="alertMessage">
            </div>

            <!-- Banner Info Undangan Keluarga (Jika Membuka Link Invite) -->
            <div x-show="isInvitedLink"
                 x-cloak
                 class="p-3.5 rounded-xl bg-amber-50 border border-amber-200 text-xs text-amber-900 space-y-0.5">
                <div class="font-bold">Tautan Undangan Keluarga / Penghuni Terdeteksi</div>
                <p class="text-[11px] text-amber-800">
                    Kode kavling dan token undangan telah diisikan otomatis pada langkah terakhir.
                </p>
            </div>

            <form @submit.prevent="handleFormSubmit()" class="space-y-5">

                <!-- ========================================================= -->
                <!-- SEGMEN 1: METODE MASUK AKUN (NOMOR WA / USERNAME)         -->
                <!-- ========================================================= -->
                <div x-show="step === 1" class="space-y-4">
                    <div class="space-y-3 p-4 rounded-xl bg-slate-50 border border-slate-200">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-xs font-semibold text-slate-700">Metode Masuk Akun *</span>
                            <div class="inline-flex p-0.5 rounded-xl bg-slate-200/80 border border-slate-300/70">
                                <button type="button"
                                        @click="switchAuthMethod('phone')"
                                        :class="authMethod === 'phone'
                                            ? 'bg-emerald-700 text-white shadow-xs'
                                            : 'text-slate-600 hover:text-slate-900'"
                                        class="px-3 py-1 rounded-lg text-xs font-bold transition cursor-pointer">
                                    Nomor WA
                                </button>
                                <button type="button"
                                        @click="switchAuthMethod('username')"
                                        :class="authMethod === 'username'
                                            ? 'bg-emerald-700 text-white shadow-xs'
                                            : 'text-slate-600 hover:text-slate-900'"
                                        class="px-3 py-1 rounded-lg text-xs font-bold transition cursor-pointer">
                                    Username
                                </button>
                            </div>
                        </div>

                        <!-- Input Nomor WhatsApp Aktif -->
                        <div x-show="authMethod === 'phone'">
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Nomor WhatsApp Aktif *</label>
                            <input type="tel"
                                x-model="claimForm.phone"
                                placeholder="Contoh: 0812xxxxxxxx"
                                class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                            <p class="text-[11px] text-slate-500 mt-1.5">
                                Nomor ini akan digunakan sebagai ID masuk ke aplikasi PPMT Digital.
                            </p>
                        </div>

                        <!-- Input Username + Wajib Penjamin -->
                        <div x-show="authMethod === 'username'" x-cloak class="space-y-3.5">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Username *</label>
                                <input type="text"
                                    x-model="claimForm.username"
                                    @input="claimForm.username = (claimForm.username || '').toLowerCase().replace(/[^a-z0-9._]/g, '')"
                                    placeholder="Contoh: budi.c21"
                                    class="w-full rounded-xl border-slate-300 text-sm font-mono lowercase focus:border-emerald-600 focus:ring-emerald-600">
                                <p class="text-[11px] text-slate-500 mt-1">
                                    Gunakan huruf kecil, angka, titik (.), atau garis bawah (_).
                                </p>
                            </div>

                            <!-- Konfirmasi Penjamin (Wajib karena tanpa Nomor WA) -->
                            <div class="p-3.5 rounded-xl bg-amber-50/90 border border-amber-200 space-y-2.5">
                                <div class="space-y-0.5">
                                    <div class="text-xs font-bold text-amber-950">Konfirmasi Penjamin Akun *</div>
                                    <p class="text-[11px] text-amber-800 leading-relaxed">
                                        Pendaftaran tanpa Nomor WhatsApp wajib dijamin oleh penghuni terverifikasi di kavling yang sama atau pengurus paguyuban.
                                    </p>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-slate-800 mb-1">
                                        Nomor WA atau Username Penjamin *
                                    </label>
                                    <input type="text"
                                        x-model="claimForm.guarantor_identifier"
                                        @input="claimForm.guarantor_identifier = (claimForm.guarantor_identifier || '').toLowerCase().replace(/[^a-z0-9._+]/g, '')"
                                        placeholder="Masukkan No. WA atau Username penjamin"
                                        class="w-full rounded-xl border-amber-300 bg-white text-sm lowercase focus:border-emerald-600 focus:ring-emerald-600">
                                </div>

                                <label class="flex items-start gap-2 text-[11px] text-slate-700 cursor-pointer select-none pt-0.5">
                                    <input type="checkbox"
                                        x-model="claimForm.guarantor_confirmed"
                                        class="mt-0.5 rounded border-amber-400 text-emerald-700 focus:ring-emerald-600">
                                    <span>
                                        Saya menyatakan penjamin di atas mengetahui pendaftaran ini dan bersedia mengonfirmasi keabsahan hunian saya.
                                    </span>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ========================================================= -->
                <!-- SEGMEN 2: NAMA LENGKAP SESUAI IDENTITAS + JENIS KELAMIN   -->
                <!-- ========================================================= -->
                <div x-show="step === 2" x-cloak class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap Sesuai Identitas *</label>
                        <input type="text"
                               x-model="claimForm.name"
                               placeholder="Contoh: Budi Santoso"
                               class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                        <p class="text-[11px] text-slate-500 mt-1">
                            Nama lengkap memudahkan tetangga atau koordinator blok mengenali saat proses verifikasi.
                        </p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Jenis Kelamin *</label>
                        <div class="grid grid-cols-2 gap-2.5">
                            <label :class="claimForm.gender === 'male'
                                       ? 'bg-emerald-50 border-emerald-600 text-emerald-950 ring-1 ring-emerald-600'
                                       : 'bg-white border-slate-300 text-slate-700 hover:bg-slate-50'"
                                   class="flex items-center justify-center gap-2 py-3 px-3 rounded-xl border text-xs sm:text-sm font-bold cursor-pointer transition select-none">
                                <input type="radio"
                                       name="gender"
                                       value="male"
                                       x-model="claimForm.gender"
                                       class="text-emerald-700 focus:ring-emerald-600">
                                <span>Laki-laki</span>
                            </label>

                            <label :class="claimForm.gender === 'female'
                                       ? 'bg-emerald-50 border-emerald-600 text-emerald-950 ring-1 ring-emerald-600'
                                       : 'bg-white border-slate-300 text-slate-700 hover:bg-slate-50'"
                                   class="flex items-center justify-center gap-2 py-3 px-3 rounded-xl border text-xs sm:text-sm font-bold cursor-pointer transition select-none">
                                <input type="radio"
                                       name="gender"
                                       value="female"
                                       x-model="claimForm.gender"
                                       class="text-emerald-700 focus:ring-emerald-600">
                                <span>Perempuan</span>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- ========================================================= -->
                <!-- SEGMEN 3: BUAT SANDI + KONFIRMASI SANDI                   -->
                <!-- ========================================================= -->
                <div x-show="step === 3" x-cloak class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Buat Kata Sandi (Minimal 8 Karakter) *</label>
                        <input type="password"
                               x-model="claimForm.password"
                               minlength="8"
                               placeholder="Minimal 8 karakter"
                               class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Konfirmasi Kata Sandi *</label>
                        <input type="password"
                               x-model="claimForm.password_confirmation"
                               minlength="8"
                               placeholder="Ketik ulang kata sandi"
                               :class="claimForm.password_confirmation && claimForm.password !== claimForm.password_confirmation
                                   ? 'border-rose-400 focus:border-rose-500 focus:ring-rose-500'
                                   : 'border-slate-300 focus:border-emerald-600 focus:ring-emerald-600'"
                               class="w-full rounded-xl text-sm">
                        <p x-show="claimForm.password_confirmation && claimForm.password !== claimForm.password_confirmation"
                           x-cloak
                           class="text-[11px] font-semibold text-rose-600 mt-1">
                            Konfirmasi kata sandi belum cocok.
                        </p>
                    </div>
                </div>

                <!-- ========================================================= -->
                <!-- SEGMEN 4: NOMOR KAVLING HUNIAN + KODE UNDANGAN (OPSIONAL) -->
                <!-- ========================================================= -->
                <div x-show="step === 4" x-cloak class="space-y-4">
                    <!-- Nomor Kavling (4 Tombol Blok A-D + Kotak Nomor Unit) -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Nomor Kavling Hunian *</label>
                        <div class="flex items-stretch gap-2">
                            <div class="grid grid-cols-4 gap-1.5 shrink-0">
                                <template x-for="blk in ['A', 'B', 'C', 'D']" :key="blk">
                                    <button type="button"
                                            @click="claimForm.block = blk"
                                            :class="claimForm.block === blk
                                                ? 'bg-emerald-700 text-white border-emerald-700 shadow-sm'
                                                : 'bg-slate-50 text-slate-700 border-slate-300 hover:bg-slate-100'"
                                            class="w-10 h-10 rounded-xl border text-sm font-extrabold transition cursor-pointer flex items-center justify-center"
                                            x-text="blk">
                                    </button>
                                </template>
                            </div>

                            <input type="text"
                                   x-model="claimForm.unit_number"
                                   @input="sanitizeUnitNumber()"
                                   maxlength="5"
                                   placeholder="No. Rumah (mis. 5 atau 12A)"
                                   class="flex-1 min-w-0 rounded-xl border-slate-300 text-sm font-mono uppercase focus:border-emerald-600 focus:ring-emerald-600">
                        </div>
                        <p class="text-[11px] text-slate-500 mt-1">
                            Pilih huruf blok (A–D) lalu ketik nomor rumah Anda (contoh: <span class="font-mono font-semibold text-slate-700">12A</span> atau <span class="font-mono font-semibold text-slate-700">21</span>).
                        </p>
                    </div>

                    <!-- Status Keterkaitan & Peran -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
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

                    <!-- Kode Undangan Keluarga/Pemilik (Collapsible) -->
                    <div class="pt-1">
                        <button type="button"
                                x-show="!showInviteBox"
                                @click="showInviteBox = true"
                                class="text-xs font-semibold text-emerald-700 hover:text-emerald-900 hover:underline cursor-pointer">
                            + Punya kode undangan dari keluarga atau pemilik unit?
                        </button>

                        <div x-show="showInviteBox" x-cloak class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 space-y-1.5">
                            <div class="flex items-center justify-between">
                                <label class="text-xs font-semibold text-slate-700">
                                    Kode Undangan Unit <span class="text-slate-400 font-normal">(Opsional)</span>
                                </label>
                                <button type="button"
                                        x-show="!isInvitedLink"
                                        @click="showInviteBox = false; claimForm.invite_token = ''"
                                        class="text-[11px] text-slate-400 hover:text-slate-700 cursor-pointer">
                                    Tutup
                                </button>
                            </div>
                            <input type="text"
                                   x-model="claimForm.invite_token"
                                   placeholder="Tempel kode undangan di sini"
                                   class="w-full rounded-xl border-slate-300 text-xs font-mono focus:border-emerald-600 focus:ring-emerald-600">
                            <p class="text-[11px] text-slate-500">
                                Jika diisi dengan kode undangan yang sah, akun Anda langsung aktif tanpa antre verifikasi.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- ========================================================= -->
                <!-- TOMBOL NAVIGASI LANGKAH (BACK / NEXT / KIRIM)             -->
                <!-- ========================================================= -->
                <div class="pt-2 flex items-center justify-between gap-3">
                    <button type="button"
                            x-show="step > 1"
                            x-cloak
                            @click="prevStep()"
                            :disabled="submitting"
                            class="px-4 py-2.5 rounded-xl border border-slate-300 hover:bg-slate-50 text-slate-700 font-bold text-sm transition cursor-pointer">
                        &larr; Kembali
                    </button>

                    <button type="button"
                            x-show="step < 4"
                            @click="nextStep()"
                            :class="step === 1 ? 'w-full' : 'flex-1'"
                            class="py-2.5 px-5 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-sm shadow transition cursor-pointer">
                        Lanjut &rarr;
                    </button>

                    <button type="submit"
                            x-show="step === 4"
                            x-cloak
                            :disabled="submitting"
                            class="flex-1 py-2.5 px-5 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-sm shadow transition disabled:opacity-50 cursor-pointer">
                        <span x-show="!submitting">Kirim Pendaftaran</span>
                        <span x-show="submitting">Memproses...</span>
                    </button>
                </div>
            </form>

            <div class="pt-3 border-t border-slate-100 text-center text-xs text-slate-600">
                Sudah punya akun warga?
                <a href="/?login=1" class="font-bold text-emerald-700 hover:text-emerald-800 hover:underline ml-0.5">
                    Masuk di sini
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function ppmtRegisterPage() {
    return {
        step: 1,
        submitting: false,
        alertMessage: '',
        alertType: 'success',
        isInvitedLink: false,
        showInviteBox: false,
        authMethod: 'phone', // 'phone' | 'username'
        claimForm: {
            guarantor_identifier: '',
            guarantor_confirmed: false,
            block: '',
            unit_number: '',
            name: '',
            gender: '',
            phone: '',
            username: '',
            entity_type: 'owner',
            sub_role: 'primary_owner',
            password: '',
            password_confirmation: '',
            invite_token: ''
        },

        get stepTitle() {
            const titles = {
                1: 'Metode Masuk Akun',
                2: 'Identitas Warga',
                3: 'Keamanan Akun',
                4: 'Nomor Kavling Hunian'
            };
            return titles[this.step] || '';
        },

        init() {
            const params = new URLSearchParams(window.location.search);
            const invite = params.get('invite');
            const rawUnit = (params.get('unit') || '').toUpperCase().trim();

            if (invite) {
                this.isInvitedLink = true;
                this.showInviteBox = true;
                this.claimForm.invite_token = invite;
                const match = rawUnit.match(/^([A-D])-([0-9]+[A-B]?)$/);
                if (match) {
                    this.claimForm.block = match[1];
                    this.claimForm.unit_number = match[2];
                }
            } else if (localStorage.getItem('ppmt_token')) {
                window.location.href = '/dashboard';
            }
        },

        switchAuthMethod(method) {
            this.alertMessage = '';
            this.authMethod = method;
            if (method === 'phone') {
                this.claimForm.username = '';
                this.claimForm.guarantor_identifier = '';
                this.claimForm.guarantor_confirmed = false;
            } else {
                this.claimForm.phone = '';
            }
        },

        sanitizeUnitNumber() {
            let val = (this.claimForm.unit_number || '').toUpperCase().replace(/[^0-9A-Z]/g, '');
            val = val.replace(/^0+/, '');
            this.claimForm.unit_number = val;
        },

        updateDefaultSubRole() {
            this.claimForm.sub_role = this.claimForm.entity_type === 'owner' ? 'primary_owner' : 'primary_resident';
        },

        validateCurrentStep() {
            this.alertMessage = '';

            if (this.step === 1) {
                if (this.authMethod === 'phone' && !this.claimForm.phone.trim()) {
                    this.alertType = 'error';
                    this.alertMessage = 'Silakan masukkan Nomor WhatsApp aktif Anda.';
                    return false;
                }
                if (this.authMethod === 'username') {
                    if (!this.claimForm.username.trim()) {
                        this.alertType = 'error';
                        this.alertMessage = 'Silakan masukkan Username yang Anda inginkan.';
                        return false;
                    }
                    if (!this.claimForm.guarantor_identifier.trim()) {
                        this.alertType = 'error';
                        this.alertMessage = 'Wajib mengisi Nomor WA atau Username penjamin (penghuni terverifikasi di kavling yang sama atau pengurus).';
                        return false;
                    }
                    if (!this.claimForm.guarantor_confirmed) {
                        this.alertType = 'error';
                        this.alertMessage = 'Silakan centang pernyataan konfirmasi penjamin akun terlebih dahulu.';
                        return false;
                    }
                }
            }

            if (this.step === 2) {
                if (!this.claimForm.name.trim()) {
                    this.alertType = 'error';
                    this.alertMessage = 'Silakan masukkan nama lengkap sesuai identitas.';
                    return false;
                }
                if (!this.claimForm.gender) {
                    this.alertType = 'error';
                    this.alertMessage = 'Silakan pilih jenis kelamin (Laki-laki atau Perempuan).';
                    return false;
                }
            }

            if (this.step === 3) {
                if ((this.claimForm.password || '').length < 8) {
                    this.alertType = 'error';
                    this.alertMessage = 'Kata sandi minimal harus 8 karakter.';
                    return false;
                }
                if (this.claimForm.password !== this.claimForm.password_confirmation) {
                    this.alertType = 'error';
                    this.alertMessage = 'Konfirmasi kata sandi tidak cocok dengan kata sandi yang Anda buat.';
                    return false;
                }
            }

            if (this.step === 4) {
                if (!this.claimForm.block) {
                    this.alertType = 'error';
                    this.alertMessage = 'Silakan pilih huruf Blok (A, B, C, atau D) terlebih dahulu.';
                    return false;
                }
                const cleanNumber = (this.claimForm.unit_number || '').trim().toUpperCase();
                if (!/^[1-9][0-9]*[A-Z]?$/.test(cleanNumber)) {
                    this.alertType = 'error';
                    this.alertMessage = 'Nomor rumah tidak valid. Masukkan angka (mis. 5 atau 21) atau nomor tambahan (mis. 12A atau 12B).';
                    return false;
                }
            }

            return true;
        },

        nextStep() {
            if (!this.validateCurrentStep()) return;
            if (this.step < 4) {
                this.step++;
            }
        },

        prevStep() {
            this.alertMessage = '';
            if (this.step > 1) {
                this.step--;
            }
        },

        handleFormSubmit() {
            if (this.step < 4) {
                this.nextStep();
                return;
            }
            this.submitClaim();
        },

        async submitClaim() {
            if (!this.validateCurrentStep()) return;

            const cleanNumber = (this.claimForm.unit_number || '').trim().toUpperCase();
            this.submitting = true;
            try {
                const payload = {
                    guarantor_identifier: this.authMethod === 'username' ? this.claimForm.guarantor_identifier.trim() : null,
                    name: this.claimForm.name.trim(),
                    gender: this.claimForm.gender,
                    phone: this.authMethod === 'phone' ? this.claimForm.phone.trim() : null,
                    username: this.authMethod === 'username' ? this.claimForm.username.trim() : null,
                    password: this.claimForm.password,
                    password_confirmation: this.claimForm.password_confirmation,
                    unit_code: `${this.claimForm.block}-${cleanNumber}`,
                    entity_type: this.claimForm.entity_type,
                    sub_role: this.claimForm.sub_role,
                    invite_token: this.claimForm.invite_token || null
                };

                const res = await fetch('/api/v1/auth/register-claim', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const json = await res.json();

                if (!res.ok) {
                    const firstError = json.errors ? Object.values(json.errors)[0]?.[0] : null;
                    this.alertType = 'error';
                    this.alertMessage = firstError || json.message || 'Periksa kembali data pendaftaran Anda.';
                    return;
                }

                localStorage.setItem('ppmt_token', json.token);
                this.alertType = 'success';
                this.alertMessage = json.message;
                setTimeout(() => window.location.href = '/dashboard', 1000);
            } catch (e) {
                this.alertType = 'error';
                this.alertMessage = 'Terjadi gangguan koneksi ke server.';
            } finally {
                this.submitting = false;
            }
        }
    };
}
</script>
@endpush
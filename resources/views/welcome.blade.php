@extends('layouts.app')

@section('content')
<div x-data="ppmtPublicHome()" x-init="init()"
     @open-warga-modal.window="handleExternalModalEvent($event.detail)"
     @keydown.escape.window="modalOpen = false">

    <!-- HERO SECTION PUBLIK -->
    <section class="bg-gradient-to-br from-emerald-950 via-emerald-900 to-teal-900 text-white">
        <div class="max-w-6xl mx-auto px-4 py-14 sm:py-20 grid grid-cols-1 lg:grid-cols-12 gap-10 items-center">
            <div class="lg:col-span-7 space-y-5">
                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-800/80 border border-emerald-700 text-emerald-100 text-xs font-semibold tracking-wide">
                    <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                    Paguyuban Resmi Lingkungan &bull; Turen, Malang
                </span>
                <h1 class="text-3xl sm:text-5xl font-extrabold leading-tight tracking-tight">
                    Guyub Rukun, Aman, dan Mandiri Bersama Warga PPMT
                </h1>
                <p class="text-emerald-100/90 text-sm sm:text-base leading-relaxed max-w-xl">
                    Selamat datang di portal informasi resmi <strong>Perumahan Pesona Mandiri Turen (PPMT)</strong>. Wadah komunikasi paguyuban, informasi kegiatan sosial warga, dan penguatan ekonomi lokal antar-tetangga.
                </p>

                <!-- Tombol Aksi Kontekstual (Tamu vs Warga Sudah Login) -->
                <div class="pt-2 flex flex-wrap items-center gap-3">
                    <template x-if="!hasToken">
                        <div class="flex flex-wrap items-center gap-2.5">
                            <button type="button" @click="openLoginModal()"
                                    class="px-6 py-3.5 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-950 font-extrabold text-sm shadow-lg transition cursor-pointer">
                                Masuk &rarr;
                            </button>
                            <a href="/register"
                               class="px-5 py-3.5 rounded-xl bg-emerald-700/80 hover:bg-emerald-700 border border-emerald-500/50 text-white font-bold text-sm transition">
                                Daftar Warga Baru
                            </a>
                        </div>
                    </template>

                    <template x-if="hasToken">
                        <a href="/dashboard"
                           class="px-6 py-3.5 rounded-xl bg-emerald-400 hover:bg-emerald-300 text-slate-950 font-extrabold text-sm shadow-lg transition inline-flex items-center gap-2">
                            <span>Masuk ke Beranda Warga</span>
                            <span>&rarr;</span>
                        </a>
                    </template>

                    <a href="#kegiatan-warga"
                       class="px-5 py-3.5 rounded-xl bg-white/10 hover:bg-white/15 text-white font-semibold text-sm backdrop-blur transition">
                        Lihat Kegiatan Warga
                    </a>
                </div>
            </div>

            <!-- Kartu Ringkasan Makro (Non-Sensitif) -->
            <div class="lg:col-span-5 grid grid-cols-2 gap-4">
                <div class="bg-white/10 backdrop-blur border border-white/15 rounded-2xl p-5 space-y-1">
                    <div class="text-3xl font-extrabold text-amber-400">91</div>
                    <div class="text-sm font-bold text-white">Unit Kavling</div>
                    <div class="text-xs text-emerald-200">Terbagi dalam 4 zona koordinasi lingkungan</div>
                </div>
                <div class="bg-white/10 backdrop-blur border border-white/15 rounded-2xl p-5 space-y-1">
                    <div class="text-3xl font-extrabold text-emerald-300">24 Jam</div>
                    <div class="text-sm font-bold text-white">Keamanan Terpadu</div>
                    <div class="text-xs text-emerald-200">Sistem satu pintu &amp; ronda swadaya warga</div>
                </div>
                <div class="bg-white/10 backdrop-blur border border-white/15 rounded-2xl p-5 space-y-1">
                    <div class="text-3xl font-extrabold text-teal-300">UMKM</div>
                    <div class="text-sm font-bold text-white">Pasar Tetangga</div>
                    <div class="text-xs text-emerald-200">Jual-beli kebutuhan harian langsung antar-warga</div>
                </div>
                <div class="bg-white/10 backdrop-blur border border-white/15 rounded-2xl p-5 space-y-1">
                    <div class="text-3xl font-extrabold text-white">Guyub</div>
                    <div class="text-sm font-bold text-white">Tata Kelola Mandiri</div>
                    <div class="text-xs text-emerald-200">Musyawarah paguyuban &amp; transparansi blok</div>
                </div>
            </div>
        </div>
    </section>

    <!-- CUPLIKAN KEGIATAN WARGA (MOCKUP BERITA & AGENDA PUBLIK) -->
    <section id="kegiatan-warga" class="max-w-6xl mx-auto px-4 py-14 space-y-8">
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-2 border-b border-slate-200 pb-4">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-700">Kabar Lingkungan</span>
                <h2 class="text-2xl font-extrabold text-slate-900 mt-1">Cuplikan Kegiatan Warga PPMT</h2>
            </div>
            <p class="text-xs text-slate-500">Dokumentasi kegiatan sosial yang terbuka untuk informasi masyarakat umum.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Kartu Kegiatan 1 -->
            <article class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm flex flex-col">
                <div class="h-40 bg-gradient-to-tr from-emerald-800 to-teal-600 p-5 flex flex-col justify-between text-white">
                    <span class="self-start px-2.5 py-1 rounded-md bg-black/25 text-[11px] font-semibold">Kerja Bakti Rutin</span>
                    <div class="text-xs text-emerald-100">Minggu Pagi &bull; Area Fasum &amp; Drainase</div>
                </div>
                <div class="p-5 flex-1 flex flex-col justify-between space-y-3">
                    <div class="space-y-2">
                        <h3 class="font-bold text-base text-slate-900">Gotong Royong Pembersihan Saluran Air &amp; Penghijauan</h3>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Warga dari seluruh blok bahu-membahu membersihkan saluran drainase menjelang musim penghujan serta menanam tanaman peneduh di area fasilitas umum perumahan.
                        </p>
                    </div>
                    <div class="pt-2 border-t border-slate-100 text-[11px] text-slate-400">
                        Paguyuban PPMT &bull; Bidang Kebersihan &amp; Lingkungan
                    </div>
                </div>
            </article>

            <!-- Kartu Kegiatan 2 -->
            <article class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm flex flex-col">
                <div class="h-40 bg-gradient-to-tr from-amber-600 to-orange-500 p-5 flex flex-col justify-between text-white">
                    <span class="self-start px-2.5 py-1 rounded-md bg-black/25 text-[11px] font-semibold">Musyawarah Warga</span>
                    <div class="text-xs text-amber-100">Malam Minggu &bull; Musholla / Balai Warga</div>
                </div>
                <div class="p-5 flex-1 flex flex-col justify-between space-y-3">
                    <div class="space-y-2">
                        <h3 class="font-bold text-base text-slate-900">Rapat Koordinasi Keamanan &amp; Tata Tertib Lingkungan</h3>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Pertemuan pengurus paguyuban bersama koordinator zona membahas jadwal ronda akhir pekan, penertiban parkir tamu, serta aktivasi pendataan warga terverifikasi.
                        </p>
                    </div>
                    <div class="pt-2 border-t border-slate-100 text-[11px] text-slate-400">
                        Paguyuban PPMT &bull; Bidang Keamanan &amp; Ketertiban
                    </div>
                </div>
            </article>

            <!-- Kartu Kegiatan 3 -->
            <article class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm flex flex-col">
                <div class="h-40 bg-gradient-to-tr from-indigo-800 to-blue-600 p-5 flex flex-col justify-between text-white">
                    <span class="self-start px-2.5 py-1 rounded-md bg-black/25 text-[11px] font-semibold">Sosial &amp; Keluarga</span>
                    <div class="text-xs text-blue-100">Akhir Pekan &bull; Lingkungan PPMT</div>
                </div>
                <div class="p-5 flex-1 flex flex-col justify-between space-y-3">
                    <div class="space-y-2">
                        <h3 class="font-bold text-base text-slate-900">Senam Sehat Bersama &amp; Bazar Kuliner Ibu-Ibu PPMT</h3>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Mempererat silaturahmi antar-keluarga penghuni perumahan sekaligus mendukung produk kuliner rumahan dan usaha mikro warga sekitar.
                        </p>
                    </div>
                    <div class="pt-2 border-t border-slate-100 text-[11px] text-slate-400">
                        Paguyuban PPMT &bull; PKK &amp; Pemberdayaan UMKM
                    </div>
                </div>
            </article>
        </div>
    </section>

    <!-- INFORMASI FASILITAS & TATA TERTIB TAMU (AMAN UNTUK PUBLIK) -->
    <section id="lingkungan" class="max-w-6xl mx-auto px-4 pb-8">
        <div class="bg-white rounded-2xl p-6 sm:p-8 border border-slate-200 shadow-sm grid grid-cols-1 md:grid-cols-2 gap-8 items-center">
            <div class="space-y-3">
                <span class="text-xs font-bold uppercase tracking-wider text-emerald-700">Informasi Pengunjung &amp; Tamu</span>
                <h2 class="text-xl sm:text-2xl font-extrabold text-slate-900">Komitmen Privasi &amp; Kenyamanan Lingkungan</h2>
                <p class="text-xs sm:text-sm text-slate-600 leading-relaxed">
                    Demi menjaga keamanan dan privasi seluruh penghuni, data detail kavling, direktori warga, dan saluran komunikasi internal hanya dapat diakses oleh pemilik maupun penghuni terverifikasi melalui Portal Warga.
                </p>
                <ul class="space-y-2 text-xs text-slate-700 pt-1">
                    <li>&#10003; Tamu maupun kurir paket dimohon melapor pada pos keamanan atau menghubungi tuan rumah secara langsung.</li>
                    <li>&#10003; Kecepatan kendaraan di dalam area blok perumahan maksimal 15 km/jam.</li>
                    <li>&#10003; Fasilitas ibadah lingkungan terbuka bagi warga dan tamu yang berkunjung.</li>
                </ul>
            </div>

            <!-- Kotak Akses Kontekstual (Tamu vs Warga Sudah Login) -->
            <div class="bg-slate-50 rounded-2xl p-6 border border-slate-200 space-y-4">
                <template x-if="!hasToken">
                    <div class="space-y-4">
                        <h3 class="font-bold text-base text-slate-900">Apakah Anda Pemilik atau Penghuni PPMT?</h3>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Masuk ke portal untuk mengakses peta kavling internal, direktori warga, pengaturan privasi, dan Pasar Tetangga UMKM—atau ajukan pendaftaran jika Anda warga baru yang belum terdaftar.
                        </p>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                            <button type="button" @click="openLoginModal()"
                                    class="w-full py-3 px-4 rounded-xl bg-emerald-800 hover:bg-emerald-900 text-white font-bold text-sm shadow transition cursor-pointer">
                                Masuk
                            </button>
                            <a href="/register"
                               class="w-full py-3 px-4 rounded-xl bg-white hover:bg-slate-100 text-emerald-900 border border-emerald-300 font-bold text-sm text-center transition">
                                Daftar Warga Baru
                            </a>
                        </div>
                    </div>
                </template>

                <template x-if="hasToken">
                    <div class="space-y-4">
                        <div class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-900 border border-emerald-200">
                            <span>&#10003; Sesi Warga Aktif</span>
                        </div>
                        <h3 class="font-bold text-base text-slate-900">Anda Sudah Terhubung ke Portal Warga</h3>
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Akses langsung pengumuman paguyuban, jadwal ronda, direktori tetangga, serta peta kavling internal melalui ruang kerja warga Anda.
                        </p>
                        <div class="flex flex-col sm:flex-row gap-2.5">
                            <a href="/dashboard"
                               class="flex-1 py-3 px-4 rounded-xl bg-emerald-800 hover:bg-emerald-900 text-white font-bold text-xs sm:text-sm text-center shadow transition">
                                Buka Beranda Warga
                            </a>
                            <a href="/komunitas"
                               class="py-3 px-4 rounded-xl bg-white hover:bg-slate-100 text-slate-800 border border-slate-300 font-semibold text-xs sm:text-sm text-center transition">
                                Komunitas &amp; Peta
                            </a>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </section>

    <!-- ===================================================================== -->
    <!-- MODAL POPUP: MASUK & PEMULIHAN SANDI WARGA                            -->
    <!-- ===================================================================== -->
    <div x-show="modalOpen"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 backdrop-blur-sm p-3 sm:p-4 overflow-y-auto">

        <div @click.away="modalOpen = false"
             class="bg-white w-full max-w-[calc(100vw-1.5rem)] sm:max-w-md max-h-[90dvh] flex flex-col rounded-2xl shadow-2xl border border-slate-200 overflow-hidden my-auto">

            <!-- Header Modal -->
            <div class="bg-emerald-950 text-white px-5 sm:px-6 py-4 flex items-center justify-between shrink-0">
                <div>
                    <h3 class="font-bold text-base" x-text="modalMode === 'login' ? 'Masuk ke Portal Warga' : 'Pemulihan Kata Sandi'"></h3>
                    <p class="text-[11px] text-emerald-200">Perumahan Pesona Mandiri Turen</p>
                </div>
                <button type="button" @click="modalOpen = false"
                        class="w-8 h-8 rounded-lg bg-white/10 hover:bg-white/20 flex items-center justify-center text-sm cursor-pointer">
                    ✕
                </button>
            </div>

            <div class="p-5 sm:p-6 space-y-5 overflow-y-auto">

                <!-- Notifikasi Error / Sukses -->
                <div x-show="alertMessage"
                     :class="alertType === 'error' ? 'bg-rose-50 text-rose-800 border-rose-200' : 'bg-emerald-50 text-emerald-800 border-emerald-200'"
                     class="p-3.5 rounded-xl border text-xs leading-relaxed"
                     x-text="alertMessage">
                </div>

                <!-- MODE 1: FORM MASUK -->
                <div x-show="modalMode === 'login'" class="space-y-4">
                    <form @submit.prevent="submitLogin()" class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Nomor WhatsApp atau Username</label>
                            <input type="text"
                                   x-model="loginForm.login_id"
                                   @input="loginForm.login_id = (loginForm.login_id || '').toLowerCase().replace(/[^a-z0-9._+]/g, '')"
                                   autocapitalize="none"
                                   autocorrect="off"
                                   spellcheck="false"
                                   required
                                   placeholder="0812xxxxxxxx atau username"
                                   class="w-full rounded-xl border-slate-300 text-sm lowercase focus:border-emerald-600 focus:ring-emerald-600">
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-xs font-semibold text-slate-700">Kata Sandi</label>
                                <button type="button"
                                        @click="switchToForgot()"
                                        class="text-xs font-semibold text-emerald-700 hover:text-emerald-800 hover:underline cursor-pointer">
                                    Lupa kata sandi?
                                </button>
                            </div>
                            <input type="password" x-model="loginForm.password" required placeholder="••••••••"
                                   class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                        </div>

                        <button type="submit" :disabled="submitting"
                                class="w-full py-2.5 px-4 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-sm shadow transition disabled:opacity-50 cursor-pointer">
                            <span x-show="!submitting">Masuk</span>
                            <span x-show="submitting">Memverifikasi...</span>
                        </button>
                    </form>

                    <div class="pt-3 border-t border-slate-100 text-center text-xs text-slate-600">
                        Belum punya akun warga?
                        <a href="/register"
                           class="font-bold text-emerald-700 hover:text-emerald-800 hover:underline ml-0.5">
                            Ajukan pendaftaran di sini
                        </a>
                    </div>
                </div>

                <!-- MODE 2: LUPA KATA SANDI (2 TAHAP OTP PENGURUS / PEMILIK UTAMA) -->
                <div x-show="modalMode === 'forgot'" class="space-y-4">
                    <div class="flex rounded-xl bg-slate-100 p-1 text-xs font-bold">
                        <button type="button"
                                @click="recoveryStep = 1; alertMessage = ''"
                                :class="recoveryStep === 1 ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600'"
                                class="flex-1 py-1.5 rounded-lg transition cursor-pointer">
                            1. Minta Kode OTP
                        </button>
                        <button type="button"
                                @click="recoveryStep = 2; alertMessage = ''"
                                :class="recoveryStep === 2 ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-600'"
                                class="flex-1 py-1.5 rounded-lg transition cursor-pointer">
                            2. Sudah Punya Kode
                        </button>
                    </div>

                    <!-- Langkah 1: Ajukan Tiket Pemulihan -->
                    <form x-show="recoveryStep === 1" @submit.prevent="submitForgotPassword()" class="space-y-3.5">
                        <p class="text-xs text-slate-600 leading-relaxed">
                            Masukkan Nomor WhatsApp/Username dan Kode Kavling Anda. Sistem akan menerbitkan <strong>6-digit Kode Pemulihan</strong> (berlaku 15 menit) yang dapat Anda minta kepada <strong>Koordinator Blok/Zona</strong>, <strong>Admin Sistem</strong>, atau <strong>Pemilik Utama Kavling</strong> Anda.
                        </p>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Nomor WhatsApp atau Username *</label>
                            <input type="text"
                                   x-model="recoveryForm.login_id"
                                   @input="recoveryForm.login_id = (recoveryForm.login_id || '').toLowerCase().replace(/[^a-z0-9._+]/g, '')"
                                   required
                                   placeholder="0812xxxxxxxx atau username"
                                   class="w-full rounded-xl border-slate-300 text-sm lowercase">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Kode Kavling *</label>
                            <input type="text"
                                   x-model="recoveryForm.unit_code"
                                   @input="recoveryForm.unit_code = (recoveryForm.unit_code || '').toUpperCase().replace(/[^A-Z0-9-]/g, '')"
                                   required
                                   placeholder="Contoh: C-21"
                                   class="w-full rounded-xl border-slate-300 text-sm font-mono uppercase">
                        </div>

                        <button type="submit" :disabled="submitting"
                                class="w-full py-2.5 px-4 rounded-xl bg-amber-500 hover:bg-amber-600 text-slate-950 font-extrabold text-sm shadow transition disabled:opacity-50 cursor-pointer">
                            <span x-show="!submitting">Kirim Permintaan Pemulihan</span>
                            <span x-show="submitting">Memproses...</span>
                        </button>
                    </form>

                    <!-- Langkah 2: Input Kode 6-Digit + Sandi Baru -->
                    <form x-show="recoveryStep === 2" @submit.prevent="submitResetPassword()" class="space-y-3.5">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Nomor WhatsApp atau Username *</label>
                            <input type="text"
                                   x-model="recoveryForm.login_id"
                                   @input="recoveryForm.login_id = (recoveryForm.login_id || '').toLowerCase().replace(/[^a-z0-9._+]/g, '')"
                                   required
                                   placeholder="0812xxxxxxxx atau username"
                                   class="w-full rounded-xl border-slate-300 text-sm lowercase">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Kode Pemulihan 6-Digit dari Pengurus *</label>
                            <input type="text"
                                   x-model="recoveryForm.recovery_code"
                                   @input="recoveryForm.recovery_code = (recoveryForm.recovery_code || '').replace(/[^0-9]/g, '').slice(0, 6)"
                                   required
                                   maxlength="6"
                                   inputmode="numeric"
                                   placeholder="000000"
                                   class="w-full rounded-xl border-slate-300 text-center text-lg font-mono font-extrabold tracking-[0.35em]">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Kata Sandi Baru (Min. 8 Karakter) *</label>
                            <input type="password" x-model="recoveryForm.password" required minlength="8" placeholder="••••••••"
                                   class="w-full rounded-xl border-slate-300 text-sm">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Ulangi Kata Sandi Baru *</label>
                            <input type="password" x-model="recoveryForm.password_confirmation" required minlength="8" placeholder="••••••••"
                                   class="w-full rounded-xl border-slate-300 text-sm">
                        </div>

                        <button type="submit" :disabled="submitting"
                                class="w-full py-2.5 px-4 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white font-bold text-sm shadow transition disabled:opacity-50 cursor-pointer">
                            <span x-show="!submitting">Simpan Sandi Baru &amp; Masuk</span>
                            <span x-show="submitting">Menyimpan...</span>
                        </button>
                    </form>

                    <div class="pt-3 border-t border-slate-100 text-center">
                        <button type="button"
                                @click="modalMode = 'login'; alertMessage = ''"
                                class="text-xs font-semibold text-slate-600 hover:text-slate-900 cursor-pointer">
                            &larr; Kembali ke form Masuk
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function ppmtPublicHome() {
    return {
        hasToken: !!localStorage.getItem('ppmt_token'),
        modalOpen: false,
        modalMode: 'login',
        recoveryStep: 1,
        submitting: false,
        alertMessage: '',
        alertType: 'success',
        loginForm: {
            login_id: '',
            password: ''
        },
        recoveryForm: {
            login_id: '',
            unit_code: '',
            recovery_code: '',
            password: '',
            password_confirmation: ''
        },

        init() {
            const params = new URLSearchParams(window.location.search);

            if (params.get('invite')) {
                window.location.href = '/register' + window.location.search;
                return;
            }

            if (params.get('login') === '1' && !this.hasToken) {
                this.openLoginModal();
            }
        },

        resolvePostLoginRedirect() {
            const intended = localStorage.getItem('ppmt_intended_url');
            localStorage.removeItem('ppmt_intended_url');
            if (intended && intended.startsWith('/') && !intended.startsWith('//')) {
                return intended;
            }
            return '/dashboard';
        },

        handleExternalModalEvent(detail) {
            if (detail === 'register') {
                window.location.href = '/register';
                return;
            }
            this.openLoginModal();
        },

        openLoginModal() {
            if (this.hasToken) {
                window.location.href = this.resolvePostLoginRedirect();
                return;
            }
            this.modalMode = 'login';
            this.alertMessage = '';
            this.modalOpen = true;
        },

        switchToForgot() {
            this.modalMode = 'forgot';
            this.recoveryStep = 1;
            this.recoveryForm.login_id = this.loginForm.login_id || '';
            this.alertMessage = '';
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

                sessionStorage.removeItem('ppmt_is_impersonated');
                sessionStorage.removeItem('ppmt_impersonated_name');
                sessionStorage.removeItem('ppmt_admin_restore_token');
                sessionStorage.removeItem('ppmt_nav_profile');
                localStorage.setItem('ppmt_token', json.token);
                window.location.href = this.resolvePostLoginRedirect();
            } catch (e) {
                this.alertType = 'error';
                this.alertMessage = 'Terjadi gangguan koneksi ke server.';
            } finally {
                this.submitting = false;
            }
        },

        async submitForgotPassword() {
            this.submitting = true;
            this.alertMessage = '';
            try {
                const res = await fetch('/api/v1/auth/forgot-password', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({
                        login_id: this.recoveryForm.login_id,
                        unit_code: this.recoveryForm.unit_code
                    })
                });
                const json = await res.json();

                if (!res.ok) {
                    this.alertType = 'error';
                    this.alertMessage = json.message || 'Gagal mengajukan pemulihan sandi.';
                    return;
                }

                this.alertType = 'success';
                this.alertMessage = json.message;
                this.recoveryStep = 2;
            } catch (e) {
                this.alertType = 'error';
                this.alertMessage = 'Terjadi gangguan koneksi ke server.';
            } finally {
                this.submitting = false;
            }
        },

        async submitResetPassword() {
            this.submitting = true;
            this.alertMessage = '';
            try {
                const res = await fetch('/api/v1/auth/reset-password', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify(this.recoveryForm)
                });
                const json = await res.json();

                if (!res.ok) {
                    this.alertType = 'error';
                    this.alertMessage = json.message || 'Kode pemulihan salah atau sudah kedaluwarsa.';
                    return;
                }

                sessionStorage.removeItem('ppmt_is_impersonated');
                sessionStorage.removeItem('ppmt_impersonated_name');
                sessionStorage.removeItem('ppmt_admin_restore_token');
                sessionStorage.removeItem('ppmt_nav_profile');
                localStorage.setItem('ppmt_token', json.token);
                window.location.href = this.resolvePostLoginRedirect();
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
@endpush
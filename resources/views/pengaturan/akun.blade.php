@extends('layouts.app')

@section('title', 'Akun & Privasi — Pengaturan PPMT Digital')
@section('subpage_title', 'Akun, Privasi & Dokumen')
@section('subpage_back_url', '/pengaturan')

@section('content')
<div x-data="ppmtAccountSettings()" x-init="init()" @keydown.escape.window="closeModal()" class="max-w-3xl mx-auto px-4 py-6 sm:py-8 space-y-6">

    <p class="text-xs sm:text-sm text-slate-500">
        Kelola identitas tampilan publik, kredensial masuk akun, visibilitas data ke tetangga, dokumen kependudukan terenkripsi, serta catatan keamanan akun Anda.
    </p>

    <!-- Loading State -->
    <div x-show="loading" class="bg-white rounded-2xl p-12 text-center text-slate-500 border border-slate-200">
        Memuat pengaturan akun Anda...
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
        <!-- KARTU 1: IDENTITAS & KEAMANAN AKUN                        -->
        <!-- ========================================================= -->
        <section class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100">
                <h2 class="text-base font-bold text-slate-900">1. Identitas &amp; Keamanan Akun</h2>
                <p class="text-xs text-slate-500 mt-0.5">Klik pada salah satu baris di bawah ini untuk memperbarui data beserta pengaturan privasinya.</p>
            </div>

            <div class="divide-y divide-slate-100">
                <button type="button" @click="openModal('display_name')"
                        class="w-full px-6 py-4 flex items-center justify-between gap-4 text-left hover:bg-slate-50/90 transition cursor-pointer group">
                    <div class="space-y-0.5">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-medium text-slate-500">Nama Panggilan Lingkungan</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                Selalu Publik
                            </span>
                        </div>
                        <div class="text-sm font-semibold text-slate-900"
                             x-text="user?.display_name || 'Belum diatur (menggunakan nama depan)'"></div>
                    </div>
                    <span class="text-slate-400 group-hover:text-emerald-700 group-hover:translate-x-0.5 transition text-lg font-medium shrink-0">&rsaquo;</span>
                </button>

                <button type="button" @click="openModal('name')"
                        class="w-full px-6 py-4 flex items-center justify-between gap-4 text-left hover:bg-slate-50/90 transition cursor-pointer group">
                    <div class="space-y-0.5">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-xs font-medium text-slate-500">Nama Lengkap Resmi</span>
                            <span :class="user?.privacy_settings?.show_full_name
                                    ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                    : 'bg-slate-100 text-slate-600 border-slate-200'"
                                  class="px-2 py-0.5 rounded-full text-[10px] font-bold border"
                                  x-text="user?.privacy_settings?.show_full_name ? 'Tampil ke Tetangga' : 'Privat'">
                            </span>
                        </div>
                        <div class="text-sm font-semibold text-slate-900" x-text="user?.name"></div>
                    </div>
                    <span class="text-slate-400 group-hover:text-emerald-700 group-hover:translate-x-0.5 transition text-lg font-medium shrink-0">&rsaquo;</span>
                </button>

                <button type="button" @click="openModal('username')"
                        class="w-full px-6 py-4 flex items-center justify-between gap-4 text-left hover:bg-slate-50/90 transition cursor-pointer group">
                    <div class="space-y-0.5">
                        <div class="text-xs font-medium text-slate-500">Username (ID Masuk Alternatif)</div>
                        <div class="text-sm font-semibold font-mono"
                             :class="user?.username ? 'text-slate-900' : 'text-slate-400 font-sans font-medium'"
                             x-text="user?.username || 'Belum diatur (opsional)'"></div>
                    </div>
                    <span class="text-slate-400 group-hover:text-emerald-700 group-hover:translate-x-0.5 transition text-lg font-medium shrink-0">&rsaquo;</span>
                </button>

                <button type="button" @click="openModal('phone')"
                        class="w-full px-6 py-4 flex items-center justify-between gap-4 text-left hover:bg-slate-50/90 transition cursor-pointer group">
                    <div class="space-y-0.5">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-xs font-medium text-slate-500">Nomor WhatsApp Aktif (ID Masuk Akun)</span>
                            <span :class="user?.privacy_settings?.show_phone
                                    ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                    : 'bg-slate-100 text-slate-600 border-slate-200'"
                                  class="px-2 py-0.5 rounded-full text-[10px] font-bold border"
                                  x-text="user?.privacy_settings?.show_phone ? 'Tampil ke Tetangga' : 'Privat'">
                            </span>
                        </div>
                        <div class="text-sm font-semibold font-mono"
                             :class="user?.phone ? 'text-slate-900' : 'text-slate-400 font-sans font-medium'"
                             x-text="user?.phone || 'Belum diatur (opsional jika punya Username)'"></div>
                    </div>
                    <span class="text-slate-400 group-hover:text-emerald-700 group-hover:translate-x-0.5 transition text-lg font-medium shrink-0">&rsaquo;</span>
                </button>

                <button type="button"
                        @click="!user?.is_impersonated && openModal('password')"
                        :disabled="user?.is_impersonated"
                        :class="user?.is_impersonated ? 'opacity-60 cursor-not-allowed bg-slate-50/60' : 'hover:bg-slate-50/90 cursor-pointer group'"
                        class="w-full px-6 py-4 flex items-center justify-between gap-4 text-left transition">
                    <div class="space-y-0.5">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-medium text-slate-500">Kata Sandi Akun</span>
                            <span x-show="user?.is_impersonated"
                                  class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                Dikunci saat sesi bantuan Admin
                            </span>
                        </div>
                        <div class="text-sm font-semibold text-slate-900 tracking-widest">&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;</div>
                    </div>
                    <span x-show="!user?.is_impersonated" class="text-slate-400 group-hover:text-emerald-700 group-hover:translate-x-0.5 transition text-lg font-medium shrink-0">&rsaquo;</span>
                </button>
            </div>
        </section>

        <!-- ========================================================= -->
        <!-- KARTU 2: DOKUMEN KEPENDUDUKAN TERENKRIPSI (AES-256)       -->
        <!-- ========================================================= -->
        <section class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h2 class="text-base font-bold text-slate-900">2. Dokumen Kependudukan Terenkripsi</h2>
                    <p class="text-xs text-slate-500 mt-0.5">NIK dan Nomor KK dienkripsi penuh (AES-256) di server dan tidak pernah ditampilkan kembali dalam teks terbuka.</p>
                </div>
                <span x-show="user?.is_impersonated"
                      class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                    Dikunci selama sesi bantuan Admin
                </span>
            </div>

            <div class="divide-y divide-slate-100">
                <button type="button"
                        @click="!user?.is_impersonated && openModal('nik')"
                        :disabled="user?.is_impersonated"
                        :class="user?.is_impersonated ? 'opacity-60 cursor-not-allowed bg-slate-50/60' : 'hover:bg-slate-50/90 cursor-pointer group'"
                        class="w-full px-6 py-4 flex items-center justify-between gap-4 text-left transition">
                    <div class="space-y-1">
                        <div class="text-xs font-medium text-slate-500">Nomor Induk Kependudukan (NIK • 16 Digit)</div>
                        <div class="flex items-center gap-2">
                            <span x-show="user?.has_nik"
                                  class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                &#10003; Tersimpan Terenkripsi (AES-256)
                            </span>
                            <span x-show="!user?.has_nik" class="text-sm font-medium text-slate-400">Belum diisi</span>
                        </div>
                    </div>
                    <span x-show="!user?.is_impersonated" class="text-slate-400 group-hover:text-emerald-700 group-hover:translate-x-0.5 transition text-lg font-medium shrink-0">&rsaquo;</span>
                </button>

                <button type="button"
                        @click="!user?.is_impersonated && openModal('kk')"
                        :disabled="user?.is_impersonated"
                        :class="user?.is_impersonated ? 'opacity-60 cursor-not-allowed bg-slate-50/60' : 'hover:bg-slate-50/90 cursor-pointer group'"
                        class="w-full px-6 py-4 flex items-center justify-between gap-4 text-left transition">
                    <div class="space-y-1">
                        <div class="text-xs font-medium text-slate-500">Nomor Kartu Keluarga (KK • 16 Digit)</div>
                        <div class="flex items-center gap-2">
                            <span x-show="user?.has_kk"
                                  class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                &#10003; Tersimpan Terenkripsi (AES-256)
                            </span>
                            <span x-show="!user?.has_kk" class="text-sm font-medium text-slate-400">Belum diisi</span>
                        </div>
                    </div>
                    <span x-show="!user?.is_impersonated" class="text-slate-400 group-hover:text-emerald-700 group-hover:translate-x-0.5 transition text-lg font-medium shrink-0">&rsaquo;</span>
                </button>
            </div>
        </section>

        <!-- ========================================================= -->
        <!-- KARTU 3: RIWAYAT KEAMANAN & BANTUAN TEKNIS ADMIN          -->
        <!-- ========================================================= -->
        <section class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between gap-2">
                <div>
                    <h2 class="text-base font-bold text-slate-900">3. Riwayat Keamanan &amp; Bantuan Teknis</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Catatan permanen setiap pemulihan kata sandi dan sesi bantuan teknis Admin Sistem (tidak dapat dihapus).</p>
                </div>
                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-slate-100 text-slate-700 border border-slate-200 shrink-0"
                      x-text="securityLogs.length + ' catatan'"></span>
            </div>

            <div x-show="securityLogs.length === 0" class="p-6 text-center text-xs text-slate-500">
                Belum ada catatan aktivitas pemulihan sandi atau sesi bantuan teknis pada akun Anda.
            </div>

            <div x-show="securityLogs.length > 0" class="divide-y divide-slate-100">
                <template x-for="log in securityLogs" :key="log.id">
                    <div class="px-6 py-4 flex flex-col sm:flex-row sm:items-start justify-between gap-2 text-xs">
                        <div class="space-y-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span :class="{
                                        'bg-rose-50 text-rose-800 border-rose-200': log.action === 'impersonation_started',
                                        'bg-slate-100 text-slate-700 border-slate-200': log.action === 'impersonation_ended',
                                        'bg-amber-50 text-amber-800 border-amber-200': log.action === 'password_recovery_requested' || log.action === 'password_recovery_issued',
                                        'bg-emerald-50 text-emerald-800 border-emerald-200': log.action === 'password_reset_completed'
                                      }"
                                      class="px-2 py-0.5 rounded-md font-bold border"
                                      x-text="formatSecurityAction(log.action)">
                                </span>
                                <span class="font-semibold text-slate-800" x-text="'Pelaksana: ' + log.actor_name"></span>
                            </div>
                            <p class="text-slate-600 leading-relaxed" x-text="log.reason"></p>
                        </div>
                        <div class="text-[11px] font-mono text-slate-400 shrink-0" x-text="log.created_at"></div>
                    </div>
                </template>
            </div>
        </section>

    </div>

    <!-- ===================================================================== -->
    <!-- POPUP MODAL TERFOKUS AKUN & DOKUMEN                                   -->
    <!-- ===================================================================== -->
    <div x-show="activeModal !== null"
         x-cloak
         class="fixed inset-0 z-50 !mt-0 flex items-center justify-center p-3 sm:p-4 bg-slate-900/50 backdrop-blur-[1px] overflow-y-auto">
        <div @click.away="closeModal()"
             class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-[calc(100vw-1.5rem)] sm:max-w-lg max-h-[90dvh] flex flex-col overflow-hidden my-auto">

            <div class="px-5 sm:px-6 py-4 border-b border-slate-100 flex items-center justify-between gap-3 shrink-0">
                <h3 class="text-base font-bold text-slate-900 leading-snug" x-text="modalTitle"></h3>
                <button type="button" @click="closeModal()" class="text-slate-400 hover:text-slate-700 text-sm p-1 cursor-pointer shrink-0">✕</button>
            </div>

            <form @submit.prevent="submitModal()" class="p-5 sm:p-6 space-y-4 overflow-y-auto">
                <div x-show="modalError"
                     class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-xs text-rose-800 font-medium"
                     x-text="modalError"></div>

                <template x-if="activeModal === 'display_name'">
                    <div class="space-y-2">
                        <label class="block text-xs font-semibold text-slate-700">Nama Panggilan Lingkungan</label>
                        <input type="text" x-model="modalForm.display_name" placeholder="Contoh: Pak Julian / Mas Budi"
                               class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600" autofocus>
                        <p class="text-[11px] text-slate-500 leading-relaxed">
                            Nama akrab ini ditampilkan kepada tetangga di Direktori Warga &amp; Pasar Tetangga saat Nama Lengkap Resmi disetel Privat.
                        </p>
                    </div>
                </template>

                <template x-if="activeModal === 'name'">
                    <div class="space-y-3">
                        <!-- Kontrol Privasi Nama Lengkap Resmi -->
                        <div @click="modalForm.show_full_name = !modalForm.show_full_name"
                             class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/80 flex items-center justify-between gap-3 cursor-pointer select-none">
                            <div class="space-y-0.5">
                                <div class="text-xs font-bold text-slate-800"
                                     x-text="modalForm.show_full_name ? 'Tampil ke Tetangga' : 'Privat (Disembunyikan)'"></div>
                                <div class="text-[11px] text-slate-500 leading-relaxed"
                                     x-text="modalForm.show_full_name
                                        ? 'Nama lengkap resmi Anda dapat dilihat sesama warga di direktori.'
                                        : 'Tetangga hanya melihat Nama Panggilan Lingkungan Anda.'"></div>
                            </div>
                            <button type="button"
                                    role="switch"
                                    :aria-checked="modalForm.show_full_name"
                                    :class="modalForm.show_full_name ? '!bg-emerald-600' : '!bg-slate-300'"
                                    class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full bg-slate-300 border border-slate-300/80 transition-colors duration-200 focus:outline-none cursor-pointer">
                                <span :class="modalForm.show_full_name ? 'translate-x-5' : 'translate-x-0.5'"
                                      class="inline-block h-4 w-4 transform rounded-full bg-white shadow-sm transition-transform duration-200"></span>
                            </button>
                        </div>

                        <div class="space-y-1.5">
                            <label class="block text-xs font-semibold text-slate-700">Nama Lengkap Resmi *</label>
                            <input type="text" x-model="modalForm.name" required
                                   class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600" autofocus>
                            <p class="text-[11px] text-slate-500 leading-relaxed">
                                Gunakan nama asli sesuai identitas resmi untuk keperluan administrasi paguyuban.
                            </p>
                        </div>
                    </div>
                </template>

                <template x-if="activeModal === 'username'">
                    <div class="space-y-2">
                        <label class="block text-xs font-semibold text-slate-700">Username (ID Masuk Alternatif)</label>
                        <input type="text"
                               x-model="modalForm.username"
                               @input="modalForm.username = (modalForm.username || '').toLowerCase().replace(/[^a-z0-9._]/g, '')"
                               placeholder="Contoh: julian.c21"
                               class="w-full rounded-xl border-slate-300 text-sm font-mono lowercase focus:border-emerald-600 focus:ring-emerald-600" autofocus>
                        <p class="text-[11px] text-slate-500 leading-relaxed">
                            Minimal 3 karakter, wajib mengandung huruf, dan hanya boleh huruf kecil, angka, titik (.), atau garis bawah (_). Dapat digunakan untuk masuk selain Nomor WhatsApp.
                        </p>
                    </div>
                </template>

                <template x-if="activeModal === 'phone'">
                    <div class="space-y-3">
                        <!-- Kontrol Privasi Nomor WhatsApp -->
                        <div @click="modalForm.show_phone = !modalForm.show_phone"
                             class="p-3.5 rounded-xl border border-slate-200 bg-slate-50/80 flex items-center justify-between gap-3 cursor-pointer select-none">
                            <div class="space-y-0.5">
                                <div class="text-xs font-bold text-slate-800"
                                     x-text="modalForm.show_phone ? 'Tampil ke Tetangga' : 'Privat (Disembunyikan)'"></div>
                                <div class="text-[11px] text-slate-500 leading-relaxed"
                                     x-text="modalForm.show_phone
                                        ? 'Sesama warga terverifikasi dapat melihat dan menghubungi nomor WA Anda di direktori.'
                                        : 'Nomor WhatsApp Anda disembunyikan dari direktori warga umum.'"></div>
                            </div>
                            <button type="button"
                                    role="switch"
                                    :aria-checked="modalForm.show_phone"
                                    :class="modalForm.show_phone ? '!bg-emerald-600' : '!bg-slate-300'"
                                    class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full bg-slate-300 border border-slate-300/80 transition-colors duration-200 focus:outline-none cursor-pointer">
                                <span :class="modalForm.show_phone ? 'translate-x-5' : 'translate-x-0.5'"
                                      class="inline-block h-4 w-4 transform rounded-full bg-white shadow-sm transition-transform duration-200"></span>
                            </button>
                        </div>

                        <div class="space-y-1.5">
                            <label class="block text-xs font-semibold text-slate-700">Nomor WhatsApp Aktif</label>
                            <input type="tel" x-model="modalForm.phone" placeholder="0812xxxxxxxx"
                                   class="w-full rounded-xl border-slate-300 text-sm font-mono focus:border-emerald-600 focus:ring-emerald-600" autofocus>
                            <p class="text-[11px] text-amber-700 bg-amber-50 p-2.5 rounded-lg border border-amber-200 leading-relaxed">
                                Perhatian: Nomor ini juga digunakan sebagai identitas masuk (login) akun Anda. Kosongkan hanya jika akun Anda sudah memiliki Username.
                            </p>
                        </div>
                    </div>
                </template>

                <template x-if="activeModal === 'password'">
                    <div class="space-y-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Kata Sandi Saat Ini *</label>
                            <input type="password" x-model="modalForm.current_password" required placeholder="••••••••"
                                   class="w-full rounded-xl border-slate-300 text-sm" autofocus>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Kata Sandi Baru (Min. 8 Karakter) *</label>
                            <input type="password" x-model="modalForm.new_password" required minlength="8" placeholder="••••••••"
                                   class="w-full rounded-xl border-slate-300 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Ulangi Kata Sandi Baru *</label>
                            <input type="password" x-model="modalForm.new_password_confirmation" required minlength="8" placeholder="••••••••"
                                   class="w-full rounded-xl border-slate-300 text-sm">
                        </div>
                    </div>
                </template>

                <template x-if="activeModal === 'nik'">
                    <div class="space-y-2">
                        <label class="block text-xs font-semibold text-slate-700">NIK Baru (16 Digit Angka) *</label>
                        <input type="text" x-model="modalForm.nik" required minlength="16" maxlength="16"
                               inputmode="numeric" pattern="[0-9]{16}" placeholder="3507xxxxxxxxxxxx"
                               class="w-full rounded-xl border-slate-300 text-sm font-mono focus:border-emerald-600 focus:ring-emerald-600" autofocus>
                        <p class="text-[11px] text-slate-500 leading-relaxed">
                            Demi keamanan privasi, NIK yang sudah tersimpan tidak pernah ditampilkan kembali.
                        </p>
                    </div>
                </template>

                <template x-if="activeModal === 'kk'">
                    <div class="space-y-2">
                        <label class="block text-xs font-semibold text-slate-700">Nomor KK Baru (16 Digit Angka) *</label>
                        <input type="text" x-model="modalForm.kk" required minlength="16" maxlength="16"
                               inputmode="numeric" pattern="[0-9]{16}" placeholder="3507xxxxxxxxxxxx"
                               class="w-full rounded-xl border-slate-300 text-sm font-mono focus:border-emerald-600 focus:ring-emerald-600" autofocus>
                        <p class="text-[11px] text-slate-500 leading-relaxed">
                            Nomor Kartu Keluarga dienkripsi dengan standar AES-256.
                        </p>
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
function ppmtAccountSettings() {
    return {
        token: localStorage.getItem('ppmt_token'),
        user: null,
        securityLogs: [],
        loading: true,
        toastMessage: '',
        toastType: 'success',
        activeModal: null,
        modalError: '',
        savingModal: false,
        modalForm: {
            display_name: '',
            name: '',
            show_full_name: false,
            username: '',
            phone: '',
            show_phone: false,
            current_password: '',
            new_password: '',
            new_password_confirmation: '',
            nik: '',
            kk: ''
        },

        async init() {
            if (!this.token) {
                window.location.href = '/';
                return;
            }
            await Promise.all([this.loadMe(), this.loadSecurityLogs()]);
        },

        headers() {
            return {
                'Authorization': 'Bearer ' + this.token,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            };
        },

        formatSecurityAction(action) {
            const labels = {
                'password_recovery_requested': 'Permintaan Reset Sandi',
                'password_recovery_issued': 'Kode Reset Diterbitkan Pengurus',
                'password_reset_completed': 'Kata Sandi Diperbarui',
                'impersonation_started': 'Sesi Bantuan Teknis Admin Dimulai',
                'impersonation_ended': 'Sesi Bantuan Teknis Admin Diakhiri'
            };
            return labels[action] || action;
        },

        get modalTitle() {
            const titles = {
                'display_name': 'Ubah Nama Panggilan Lingkungan',
                'name': 'Nama Lengkap Resmi & Privasi',
                'username': 'Atur Username Login Alternatif',
                'phone': 'Nomor WhatsApp & Privasi',
                'password': 'Ubah Kata Sandi Akun',
                'nik': 'Perbarui NIK (Terenkripsi AES-256)',
                'kk': 'Perbarui Nomor KK (Terenkripsi AES-256)'
            };
            return titles[this.activeModal] || '';
        },

        openModal(type) {
            this.modalError = '';
            this.modalForm = {
                display_name: this.user?.display_name || '',
                name: this.user?.name || '',
                show_full_name: Boolean(this.user?.privacy_settings?.show_full_name),
                username: this.user?.username || '',
                phone: this.user?.phone || '',
                show_phone: Boolean(this.user?.privacy_settings?.show_phone),
                current_password: '',
                new_password: '',
                new_password_confirmation: '',
                nik: '',
                kk: ''
            };
            this.activeModal = type;
        },

        closeModal() {
            this.activeModal = null;
            this.modalError = '';
        },

        async submitModal() {
            this.savingModal = true;
            this.modalError = '';
            try {
                const payload = {
                    name: this.user.name,
                    display_name: this.user.display_name,
                    phone: this.user.phone || null,
                    username: this.user.username || null
                };

                if (this.activeModal === 'display_name') {
                    payload.display_name = this.modalForm.display_name;
                } else if (this.activeModal === 'name') {
                    payload.name = this.modalForm.name;
                    payload.privacy_settings = {
                        show_full_name: Boolean(this.modalForm.show_full_name)
                    };
                } else if (this.activeModal === 'username') {
                    payload.username = this.modalForm.username || null;
                } else if (this.activeModal === 'phone') {
                    payload.phone = this.modalForm.phone || null;
                    payload.privacy_settings = {
                        show_phone: Boolean(this.modalForm.show_phone)
                    };
                } else if (this.activeModal === 'password') {
                    payload.current_password = this.modalForm.current_password;
                    payload.new_password = this.modalForm.new_password;
                    payload.new_password_confirmation = this.modalForm.new_password_confirmation;
                } else if (this.activeModal === 'nik') {
                    payload.nik = this.modalForm.nik;
                } else if (this.activeModal === 'kk') {
                    payload.kk = this.modalForm.kk;
                }

                const res = await fetch('/api/v1/profile', {
                    method: 'PATCH',
                    headers: this.headers(),
                    body: JSON.stringify(payload)
                });
                const json = await res.json();
                if (!res.ok) {
                    this.modalError = json.message || 'Gagal menyimpan perubahan.';
                    return;
                }
                this.closeModal();
                this.toastType = 'success';
                this.toastMessage = json.message || 'Data akun berhasil diperbarui.';
                await Promise.all([this.loadMe(), this.loadSecurityLogs()]);
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
        },

        async loadSecurityLogs() {
            try {
                const res = await fetch('/api/v1/me/security-logs', { headers: this.headers() });
                if (res.ok) {
                    const json = await res.json();
                    this.securityLogs = json.data || [];
                }
            } catch (e) {}
        }
    }
}
</script>
@endsection
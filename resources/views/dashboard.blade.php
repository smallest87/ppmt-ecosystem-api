@extends('layouts.app')

@section('title', 'Portal & Profil Warga — PPMT Digital')

@section('content')
<div x-data="ppmtDashboard()" x-init="init()" @keydown.escape.window="closeModal()" class="max-w-6xl mx-auto px-4 py-8 space-y-6">

    <!-- Loading State -->
    <div x-show="loading" class="bg-white rounded-2xl p-12 text-center text-slate-500 border border-slate-200">
        Memuat ekosistem internal warga PPMT...
    </div>

    <div x-show="!loading && user" class="space-y-6">

        <!-- Notifikasi Global -->
        <div x-show="toastMessage"
             :class="toastType === 'error' ? 'bg-rose-50 text-rose-800 border-rose-200' : 'bg-emerald-50 text-emerald-800 border-emerald-200'"
             class="p-4 rounded-xl border text-sm font-medium flex justify-between items-center shadow-sm">
            <span x-text="toastMessage"></span>
            <button @click="toastMessage = ''" class="text-xs opacity-70 hover:opacity-100 cursor-pointer">✕</button>
        </div>

        <!-- Banner Peringatan Akun Pending -->
        <div x-show="!user?.is_verified"
             class="bg-amber-50 border-l-4 border-amber-500 p-5 rounded-r-2xl shadow-sm space-y-1">
            <h2 class="font-bold text-amber-900 text-base">⏳ Akun Anda Menunggu Verifikasi Sosial Tetangga</h2>
            <p class="text-xs sm:text-sm text-amber-800 leading-relaxed">
                Demi keamanan lingkungan PPMT, akses ke Direktori Warga, Peta Kavling, dan Pasar Tetangga baru dibuka setelah klaim kavling Anda disahkan oleh <strong>Koordinator Zona/Blok</strong> terkait atau <strong>Pemilik Utama</strong> unit Anda.
            </p>
        </div>

        <!-- Header Profil, Display Name, Badge Reputasi, & Lencana Jabatan Pengurus -->
        <section class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="space-y-1.5">
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-2xl font-extrabold text-slate-900"
                        x-text="user?.display_name ? user.display_name : user?.name"></h1>
                    <span x-show="user?.display_name"
                          class="text-xs font-medium text-slate-500 bg-slate-100 px-2.5 py-0.5 rounded-md border border-slate-200"
                          x-text="'Nama Lengkap (Privat): ' + user?.name">
                    </span>
                    <span :class="badgeColorClass(user?.reputation_badge?.color)"
                          class="px-3 py-0.5 rounded-full text-xs font-bold border"
                          x-text="'Level ' + (user?.reputation_badge?.level ?? 0) + ' • ' + (user?.reputation_badge?.label ?? '')">
                    </span>
                    <template x-for="gov in (user?.governance_roles || [])" :key="gov.id">
                        <span class="px-3 py-0.5 rounded-full text-xs font-bold bg-indigo-100 text-indigo-900 border border-indigo-300"
                              x-text="'★ ' + gov.display_label">
                        </span>
                    </template>
                </div>
                <p class="text-xs text-slate-500">
                    Status Dasar: <strong class="uppercase text-slate-700" x-text="(user?.global_role || '').replace('_', ' ')"></strong>
                    &bull; WA Login: <strong class="text-slate-700" x-text="user?.phone"></strong>
                </p>
            </div>

            <!-- Daftar Unit Hunian Terkait -->
            <div class="flex flex-wrap gap-2">
                <template x-for="m in (user?.memberships || [])" :key="m.id">
                    <div class="px-3.5 py-2 rounded-xl bg-slate-100 border border-slate-200 text-xs">
                        <div class="font-bold text-slate-900" x-text="'Kavling ' + m.unit.code + ' (' + m.unit.zone + ')'"></div>
                        <div class="text-slate-500" x-text="formatMembershipSummary(m)"></div>
                    </div>
                </template>
            </div>
        </section>

        <!-- Konten Utama (Hanya aktif jika sudah terverifikasi) -->
        <div x-show="user?.is_verified" class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

            <!-- Kolom Kiri (7 Kolom) -->
            <div class="lg:col-span-7 space-y-6">

                <!-- ========================================================= -->
                <!-- KARTU 1: IDENTITAS & KEAMANAN AKUN (Interactive Rows)      -->
                <!-- ========================================================= -->
                <section class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100">
                        <h2 class="text-base font-bold text-slate-900">1. Identitas &amp; Keamanan Akun</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Klik pada salah satu baris di bawah ini untuk memperbarui data Anda.</p>
                    </div>

                    <div class="divide-y divide-slate-100">
                        <!-- Baris: Nama Panggilan Lingkungan -->
                        <button type="button" @click="openModal('display_name')"
                                class="w-full px-6 py-4 flex items-center justify-between gap-4 text-left hover:bg-slate-50/90 transition cursor-pointer group">
                            <div class="space-y-0.5">
                                <div class="text-xs font-medium text-slate-500">Nama Panggilan Lingkungan (Tampilan Publik)</div>
                                <div class="text-sm font-semibold text-slate-900"
                                     x-text="user?.display_name || 'Belum diatur (menggunakan nama lengkap)'"></div>
                            </div>
                            <span class="text-slate-400 group-hover:text-emerald-700 group-hover:translate-x-0.5 transition text-lg font-medium shrink-0">&rsaquo;</span>
                        </button>

                        <!-- Baris: Nama Lengkap Resmi -->
                        <button type="button" @click="openModal('name')"
                                class="w-full px-6 py-4 flex items-center justify-between gap-4 text-left hover:bg-slate-50/90 transition cursor-pointer group">
                            <div class="space-y-0.5">
                                <div class="text-xs font-medium text-slate-500">Nama Lengkap Resmi (Privat)</div>
                                <div class="text-sm font-semibold text-slate-900" x-text="user?.name"></div>
                            </div>
                            <span class="text-slate-400 group-hover:text-emerald-700 group-hover:translate-x-0.5 transition text-lg font-medium shrink-0">&rsaquo;</span>
                        </button>

                        <!-- Baris: Nomor WhatsApp Login -->
                        <button type="button" @click="openModal('phone')"
                                class="w-full px-6 py-4 flex items-center justify-between gap-4 text-left hover:bg-slate-50/90 transition cursor-pointer group">
                            <div class="space-y-0.5">
                                <div class="text-xs font-medium text-slate-500">Nomor WhatsApp Aktif (ID Masuk Akun)</div>
                                <div class="text-sm font-semibold text-slate-900 font-mono" x-text="user?.phone"></div>
                            </div>
                            <span class="text-slate-400 group-hover:text-emerald-700 group-hover:translate-x-0.5 transition text-lg font-medium shrink-0">&rsaquo;</span>
                        </button>

                        <!-- Baris: Kata Sandi -->
                        <button type="button" @click="openModal('password')"
                                class="w-full px-6 py-4 flex items-center justify-between gap-4 text-left hover:bg-slate-50/90 transition cursor-pointer group">
                            <div class="space-y-0.5">
                                <div class="text-xs font-medium text-slate-500">Kata Sandi Akun</div>
                                <div class="text-sm font-semibold text-slate-900 tracking-widest">&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;</div>
                            </div>
                            <span class="text-slate-400 group-hover:text-emerald-700 group-hover:translate-x-0.5 transition text-lg font-medium shrink-0">&rsaquo;</span>
                        </button>
                    </div>
                </section>

                <!-- ========================================================= -->
                <!-- KARTU 2: KONTROL PRIVASI DIREKTORI (Instant Toggle Switch) -->
                <!-- ========================================================= -->
                <section class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-base font-bold text-slate-900">2. Kontrol Privasi di Direktori Warga</h2>
                            <p class="text-xs text-slate-500 mt-0.5">Atur visibilitas informasi Anda bagi sesama warga terverifikasi. Perubahan tersimpan otomatis.</p>
                        </div>
                        <span x-show="privacyStatusText"
                              x-text="privacyStatusText"
                              class="text-[11px] font-semibold px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 shrink-0">
                        </span>
                    </div>

                    <div class="divide-y divide-slate-100">
                        <!-- Sakelar 1: Nama Lengkap Resmi -->
                        <div @click="togglePrivacy('show_full_name')"
                             class="px-6 py-4 flex items-center justify-between gap-4 hover:bg-slate-50/80 transition cursor-pointer select-none">
                            <div class="space-y-0.5">
                                <div class="text-sm font-semibold text-slate-800">Tampilkan Nama Lengkap Resmi ke Tetangga</div>
                                <div class="text-xs text-slate-500">Jika dimatikan, tetangga hanya melihat <strong>Nama Panggilan Lingkungan</strong> Anda.</div>
                            </div>
                            <button type="button"
                                    role="switch"
                                    :aria-checked="privacySettings.show_full_name"
                                    :disabled="savingPrivacy"
                                    :class="privacySettings.show_full_name ? '!bg-emerald-600' : '!bg-slate-300'"
                                    class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full bg-slate-300 border border-slate-300/80 transition-colors duration-200 focus:outline-none cursor-pointer">
                                <span :class="privacySettings.show_full_name ? 'translate-x-5' : 'translate-x-0.5'"
                                      class="inline-block h-4 w-4 transform rounded-full bg-white shadow-sm transition-transform duration-200"></span>
                            </button>
                        </div>

                        <!-- Sakelar 2: Nomor WhatsApp -->
                        <div @click="togglePrivacy('show_phone')"
                             class="px-6 py-4 flex items-center justify-between gap-4 hover:bg-slate-50/80 transition cursor-pointer select-none">
                            <div class="space-y-0.5">
                                <div class="text-sm font-semibold text-slate-800">Tampilkan Nomor WhatsApp ke Tetangga</div>
                                <div class="text-xs text-slate-500">Jika dimatikan, nomor WA Anda disamarkan dari warga umum di direktori.</div>
                            </div>
                            <button type="button"
                                    role="switch"
                                    :aria-checked="privacySettings.show_phone"
                                    :disabled="savingPrivacy"
                                    :class="privacySettings.show_phone ? '!bg-emerald-600' : '!bg-slate-300'"
                                    class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full bg-slate-300 border border-slate-300/80 transition-colors duration-200 focus:outline-none cursor-pointer">
                                <span :class="privacySettings.show_phone ? 'translate-x-5' : 'translate-x-0.5'"
                                      class="inline-block h-4 w-4 transform rounded-full bg-white shadow-sm transition-transform duration-200"></span>
                            </button>
                        </div>

                        <!-- Sakelar 3: Status Kepemilikan Rumah -->
                        <div @click="togglePrivacy('show_ownership')"
                             class="px-6 py-4 flex items-center justify-between gap-4 hover:bg-slate-50/80 transition cursor-pointer select-none">
                            <div class="space-y-0.5">
                                <div class="text-sm font-semibold text-slate-800">Tampilkan Status Kepemilikan Rumah</div>
                                <div class="text-xs text-slate-500">Menampilkan apakah Anda pemilik atau penyewa pada direktori internal.</div>
                            </div>
                            <button type="button"
                                    role="switch"
                                    :aria-checked="privacySettings.show_ownership"
                                    :disabled="savingPrivacy"
                                    :class="privacySettings.show_ownership ? '!bg-emerald-600' : '!bg-slate-300'"
                                    class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full bg-slate-300 border border-slate-300/80 transition-colors duration-200 focus:outline-none cursor-pointer">
                                <span :class="privacySettings.show_ownership ? 'translate-x-5' : 'translate-x-0.5'"
                                      class="inline-block h-4 w-4 transform rounded-full bg-white shadow-sm transition-transform duration-200"></span>
                            </button>
                        </div>
                    </div>
                </section>

                <!-- ========================================================= -->
                <!-- KARTU 3: DOKUMEN TERENKRIPSI & KENDARAAN (Interactive Rows) -->
                <!-- ========================================================= -->
                <section class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100">
                        <h2 class="text-base font-bold text-slate-900">3. Dokumen Terenkripsi &amp; Kendaraan</h2>
                        <p class="text-xs text-slate-500 mt-0.5">NIK dan Nomor KK dienkripsi penuh (AES-256) di server dan tidak pernah ditampilkan kembali dalam teks terbuka.</p>
                    </div>

                    <div class="divide-y divide-slate-100">
                        <!-- Baris: NIK -->
                        <button type="button" @click="openModal('nik')"
                                class="w-full px-6 py-4 flex items-center justify-between gap-4 text-left hover:bg-slate-50/90 transition cursor-pointer group">
                            <div class="space-y-1">
                                <div class="text-xs font-medium text-slate-500">Nomor Induk Kependudukan (NIK • 16 Digit)</div>
                                <div class="flex items-center gap-2">
                                    <span x-show="user?.has_nik"
                                          class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                        &#10003; Tersimpan Terenkripsi (AES-256)
                                    </span>
                                    <span x-show="!user?.has_nik"
                                          class="text-sm font-medium text-slate-400">
                                        Belum diisi
                                    </span>
                                </div>
                            </div>
                            <span class="text-slate-400 group-hover:text-emerald-700 group-hover:translate-x-0.5 transition text-lg font-medium shrink-0">&rsaquo;</span>
                        </button>

                        <!-- Baris: Nomor KK -->
                        <button type="button" @click="openModal('kk')"
                                class="w-full px-6 py-4 flex items-center justify-between gap-4 text-left hover:bg-slate-50/90 transition cursor-pointer group">
                            <div class="space-y-1">
                                <div class="text-xs font-medium text-slate-500">Nomor Kartu Keluarga (KK • 16 Digit)</div>
                                <div class="flex items-center gap-2">
                                    <span x-show="user?.has_kk"
                                          class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 text-emerald-800 border border-emerald-200">
                                        &#10003; Tersimpan Terenkripsi (AES-256)
                                    </span>
                                    <span x-show="!user?.has_kk"
                                          class="text-sm font-medium text-slate-400">
                                        Belum diisi
                                    </span>
                                </div>
                            </div>
                            <span class="text-slate-400 group-hover:text-emerald-700 group-hover:translate-x-0.5 transition text-lg font-medium shrink-0">&rsaquo;</span>
                        </button>

                        <!-- Baris: Kendaraan Terdaftar -->
                        <button type="button" @click="openModal('vehicles')"
                                class="w-full px-6 py-4 flex items-center justify-between gap-4 text-left hover:bg-slate-50/90 transition cursor-pointer group">
                            <div class="space-y-1.5">
                                <div class="text-xs font-medium text-slate-500">Kendaraan Terdaftar di Kavling</div>
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

                <!-- ========================================================= -->
                <!-- KARTU 4: STATUS ENTITAS & PERAN DI KAVLING (Interactive)  -->
                <!-- ========================================================= -->
                <section class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100">
                        <h2 class="text-base font-bold text-slate-900">4. Status Entitas &amp; Peran di Kavling</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Klik pada kavling di bawah ini untuk mengoreksi status kepemilikan atau peran hunian Anda.</p>
                    </div>

                    <div class="divide-y divide-slate-100">
                        <template x-for="m in (user?.memberships || [])" :key="m.id">
                            <button type="button" @click="openMembershipModal(m)"
                                    class="w-full px-6 py-4 flex items-center justify-between gap-4 text-left hover:bg-slate-50/90 transition cursor-pointer group">
                                <div class="space-y-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="text-sm font-bold text-slate-900"
                                              x-text="'Kavling ' + m.unit?.code + ' (' + m.unit?.zone + ')'"></span>
                                        <span :class="m.is_approved ? 'bg-emerald-50 text-emerald-800 border-emerald-200' : 'bg-amber-50 text-amber-800 border-amber-200'"
                                              class="px-2 py-0.5 rounded-full text-[10px] font-bold border"
                                              x-text="m.is_approved ? '✓ Disahkan' : '⏳ Menunggu Pengesahan'">
                                        </span>
                                    </div>
                                    <div class="text-xs text-slate-600 font-medium" x-text="formatMembershipSummary(m)"></div>
                                    <div x-show="m.sub_role === 'tenant' && m.valid_until"
                                         class="text-[11px] text-slate-500"
                                         x-text="'Batas masa sewa: ' + (m.valid_until ? m.valid_until.slice(0, 10) : '-')">
                                    </div>
                                </div>
                                <span class="text-slate-400 group-hover:text-emerald-700 group-hover:translate-x-0.5 transition text-lg font-medium shrink-0">&rsaquo;</span>
                            </button>
                        </template>
                    </div>
                </section>
            </div>

            <!-- Kolom Kanan (5 Kolom): Murni Aktivitas Komunitas Warga -->
            <div class="lg:col-span-5 space-y-6">

                <!-- Panel 1: Antrean Verifikasi Sosial -->
                <section x-show="pendingApprovals.length > 0"
                         class="bg-amber-50/70 rounded-2xl p-5 shadow-sm border border-amber-300 space-y-3">
                    <div class="flex items-center justify-between">
                        <h2 class="text-base font-bold text-amber-950">Antrean Verifikasi Tetangga</h2>
                        <span class="px-2.5 py-0.5 rounded-full bg-amber-500 text-white text-xs font-bold"
                              x-text="pendingApprovals.length"></span>
                    </div>
                    <p class="text-xs text-amber-900">Sahkan pendaftar berikut hanya jika Anda mengenal dan memastikan mereka benar penghuni kavling terkait:</p>

                    <div class="space-y-2.5">
                        <template x-for="item in pendingApprovals" :key="item.membership_id">
                            <div class="bg-white p-3.5 rounded-xl border border-amber-200 flex items-center justify-between gap-2">
                                <div class="text-xs space-y-0.5">
                                    <div class="font-bold text-slate-900" x-text="item.name + ' • Unit ' + item.unit_code"></div>
                                    <div class="text-slate-500" x-text="item.phone + ' (' + subRoleLabel(item.sub_role) + ')'"></div>
                                </div>
                                <button type="button" @click="approveResident(item)"
                                        class="px-3 py-1.5 rounded-lg bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-semibold shrink-0 cursor-pointer">
                                    ✓ Sahkan
                                </button>
                            </div>
                        </template>
                    </div>
                </section>

                <!-- Panel 2: Generator Tautan Undangan Keluarga / Penyewa -->
                <section x-show="manageableUnits.length > 0"
                         class="bg-white rounded-2xl p-5 shadow-sm border border-slate-200 space-y-4">
                    <div class="border-b border-slate-100 pb-3">
                        <h2 class="text-base font-bold text-slate-900">Undang Anggota Keluarga / Penyewa</h2>
                        <p class="text-xs text-slate-500">Buat tautan undangan berbatas waktu (48 jam) agar penghuni serumah langsung aktif tanpa antre verifikasi.</p>
                    </div>

                    <form @submit.prevent="generateInvite()" class="space-y-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Pilih Kavling Anda</label>
                            <select x-model="inviteForm.unit_code" required class="w-full rounded-xl border-slate-300 text-xs">
                                <template x-for="m in manageableUnits" :key="m.id">
                                    <option :value="m.unit.code" x-text="'Kavling ' + m.unit.code"></option>
                                </template>
                            </select>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Entitas</label>
                                <select x-model="inviteForm.entity_type" class="w-full rounded-xl border-slate-300 text-xs">
                                    <option value="resident">Penghuni</option>
                                    <option value="owner">Pemilik Bersama</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Peran</label>
                                <select x-model="inviteForm.sub_role" class="w-full rounded-xl border-slate-300 text-xs">
                                    <option value="family_member">Anggota Keluarga</option>
                                    <option value="primary_resident">Penghuni Utama</option>
                                    <option value="tenant">Penyewa / Kontrak</option>
                                    <option value="co_owner">Pemilik Bersama</option>
                                    <option value="caretaker">Penjaga Rumah</option>
                                </select>
                            </div>
                        </div>

                        <div x-show="inviteForm.sub_role === 'tenant'">
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Batas Akhir Masa Sewa *</label>
                            <input type="date" x-model="inviteForm.member_valid_until"
                                   :required="inviteForm.sub_role === 'tenant'"
                                   class="w-full rounded-xl border-slate-300 text-xs">
                        </div>

                        <button type="submit"
                                class="w-full py-2 px-4 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold transition cursor-pointer">
                            Buat Tautan Undangan
                        </button>
                    </form>

                    <div x-show="generatedInviteUrl" class="p-3 rounded-xl bg-emerald-50 border border-emerald-200 space-y-2">
                        <div class="text-[11px] font-semibold text-emerald-900">Tautan Undangan Siap Dikirim via WhatsApp:</div>
                        <input type="text" readonly :value="generatedInviteUrl"
                               class="w-full rounded-lg bg-white border-emerald-300 text-[11px] font-mono text-slate-700">
                        <button type="button" @click="copyInviteUrl()"
                                class="w-full py-1.5 rounded-lg bg-emerald-700 text-white text-xs font-semibold cursor-pointer">
                            Salin Tautan Undangan
                        </button>
                    </div>
                </section>

            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- POPUP MODAL TERFOKUS (KARTU 1, KARTU 3, & KARTU 4)                    -->
    <!-- ===================================================================== -->
    <div x-show="activeModal !== null"
         x-cloak
         class="fixed inset-0 z-50 !mt-0 flex items-center justify-center p-3 sm:p-4 bg-slate-900/50 backdrop-blur-[1px] overflow-y-auto">
        <div @click.away="closeModal()"
             class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-[calc(100vw-1.5rem)] sm:max-w-md max-h-[90dvh] flex flex-col overflow-hidden my-auto">

            <!-- Header Modal -->
            <div class="px-5 sm:px-6 py-4 border-b border-slate-100 flex items-center justify-between gap-3 shrink-0">
                <h3 class="text-base font-bold text-slate-900 leading-snug" x-text="modalTitle"></h3>
                <button type="button" @click="closeModal()" class="text-slate-400 hover:text-slate-700 text-sm p-1 cursor-pointer shrink-0">✕</button>
            </div>

            <!-- Isi Modal -->
            <form @submit.prevent="submitModal()" class="p-5 sm:p-6 space-y-4 overflow-y-auto">
                <div x-show="modalError"
                     class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-xs text-rose-800 font-medium"
                     x-text="modalError"></div>

                <!-- Modal: Nama Panggilan Lingkungan -->
                <template x-if="activeModal === 'display_name'">
                    <div class="space-y-2">
                        <label class="block text-xs font-semibold text-slate-700">Nama Panggilan Lingkungan</label>
                        <input type="text" x-model="modalForm.display_name" placeholder="Contoh: Pak Julian / Mas Budi"
                               class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600" autofocus>
                        <p class="text-[11px] text-slate-500 leading-relaxed">
                            Nama akrab ini akan ditampilkan kepada tetangga di Direktori Warga &amp; Pasar Tetangga menggantikan nama lengkap resmi Anda (kecuali Anda mengizinkan nama resmi ditampilkan).
                        </p>
                    </div>
                </template>

                <!-- Modal: Nama Lengkap Resmi -->
                <template x-if="activeModal === 'name'">
                    <div class="space-y-2">
                        <label class="block text-xs font-semibold text-slate-700">Nama Lengkap Resmi *</label>
                        <input type="text" x-model="modalForm.name" required
                               class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600" autofocus>
                        <p class="text-[11px] text-slate-500 leading-relaxed">
                            Gunakan nama asli sesuai identitas resmi untuk keperluan administrasi paguyuban dan keabsahan verifikasi komunitas.
                        </p>
                    </div>
                </template>

                <!-- Modal: Nomor WhatsApp Login -->
                <template x-if="activeModal === 'phone'">
                    <div class="space-y-2">
                        <label class="block text-xs font-semibold text-slate-700">Nomor WhatsApp Aktif *</label>
                        <input type="tel" x-model="modalForm.phone" required placeholder="0812xxxxxxxx"
                               class="w-full rounded-xl border-slate-300 text-sm font-mono focus:border-emerald-600 focus:ring-emerald-600" autofocus>
                        <p class="text-[11px] text-amber-700 bg-amber-50 p-2.5 rounded-lg border border-amber-200 leading-relaxed">
                            Perhatian: Nomor ini juga digunakan sebagai identitas masuk (login) akun Anda pada sesi berikutnya.
                        </p>
                    </div>
                </template>

                <!-- Modal: Ganti Kata Sandi -->
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

                <!-- Modal: NIK (16 Digit AES-256) -->
                <template x-if="activeModal === 'nik'">
                    <div class="space-y-2">
                        <label class="block text-xs font-semibold text-slate-700">NIK Baru (16 Digit Angka) *</label>
                        <input type="text" x-model="modalForm.nik" required minlength="16" maxlength="16"
                               inputmode="numeric" pattern="[0-9]{16}"
                               placeholder="3507xxxxxxxxxxxx"
                               class="w-full rounded-xl border-slate-300 text-sm font-mono focus:border-emerald-600 focus:ring-emerald-600" autofocus>
                        <p class="text-[11px] text-slate-500 leading-relaxed">
                            Demi keamanan privasi, NIK yang sudah tersimpan tidak pernah ditampilkan kembali. Masukkan 16 digit NIK baru jika Anda ingin menyimpan atau menggantinya.
                        </p>
                    </div>
                </template>

                <!-- Modal: Nomor KK (16 Digit AES-256) -->
                <template x-if="activeModal === 'kk'">
                    <div class="space-y-2">
                        <label class="block text-xs font-semibold text-slate-700">Nomor KK Baru (16 Digit Angka) *</label>
                        <input type="text" x-model="modalForm.kk" required minlength="16" maxlength="16"
                               inputmode="numeric" pattern="[0-9]{16}"
                               placeholder="3507xxxxxxxxxxxx"
                               class="w-full rounded-xl border-slate-300 text-sm font-mono focus:border-emerald-600 focus:ring-emerald-600" autofocus>
                        <p class="text-[11px] text-slate-500 leading-relaxed">
                            Nomor Kartu Keluarga dienkripsi dengan standar AES-256 dan hanya digunakan untuk keabsahan pendataan internal warga PPMT.
                        </p>
                    </div>
                </template>

                <!-- Modal: Kelola Kendaraan -->
                <template x-if="activeModal === 'vehicles'">
                    <div class="space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-slate-700">Daftar Kendaraan Kavling</span>
                            <button type="button" @click="modalForm.vehicles.push({ type: 'motor', plate: '' })"
                                    class="text-xs font-bold text-emerald-700 hover:underline cursor-pointer">
                                + Tambah Baris
                            </button>
                        </div>

                        <div x-show="modalForm.vehicles.length === 0"
                             class="p-4 rounded-xl bg-slate-50 border border-dashed border-slate-300 text-center text-xs text-slate-500">
                            Belum ada kendaraan. Klik <strong>+ Tambah Baris</strong> untuk mendaftarkan plat nomor kendaraan Anda.
                        </div>

                        <div class="space-y-2 max-h-60 overflow-y-auto pr-1">
                            <template x-for="(v, idx) in modalForm.vehicles" :key="idx">
                                <div class="flex items-center gap-2">
                                    <select x-model="v.type" class="rounded-xl border-slate-300 text-xs">
                                        <option value="mobil">Mobil</option>
                                        <option value="motor">Motor</option>
                                        <option value="sepeda">Sepeda</option>
                                    </select>
                                    <input type="text" x-model="v.plate" required placeholder="N 1234 AB"
                                           class="flex-1 rounded-xl border-slate-300 text-xs font-mono uppercase">
                                    <button type="button" @click="modalForm.vehicles.splice(idx, 1)"
                                            class="px-2.5 py-1.5 text-xs font-semibold text-rose-600 hover:bg-rose-50 rounded-lg cursor-pointer">
                                        Hapus
                                    </button>
                                </div>
                            </template>
                        </div>
                        <p class="text-[11px] text-slate-500 leading-relaxed">
                            Data plat nomor membantu petugas keamanan lingkungan mengenali kendaraan milik warga dan mencegah salah tegur parkir.
                        </p>
                    </div>
                </template>

                <!-- Modal: Koreksi Status Entitas & Peran Kavling -->
                <template x-if="activeModal === 'membership'">
                    <div class="space-y-3">
                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between text-xs">
                            <span class="font-bold text-slate-900"
                                  x-text="'Kavling ' + modalForm.membership.unit_code + ' (' + modalForm.membership.zone + ')'"></span>
                            <span class="text-slate-500">Status Saat Ini</span>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Entitas di Kavling *</label>
                            <select x-model="modalForm.membership.entity_type"
                                    @change="modalForm.membership.sub_role = modalForm.membership.entity_type === 'owner' ? 'primary_owner' : 'primary_resident'"
                                    class="w-full rounded-xl border-slate-300 text-sm">
                                <option value="owner">Pemilik Rumah</option>
                                <option value="resident">Penghuni</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Peran Spesifik *</label>
                            <select x-model="modalForm.membership.sub_role" class="w-full rounded-xl border-slate-300 text-sm">
                                <template x-if="modalForm.membership.entity_type === 'owner'">
                                    <optgroup label="Pemilik">
                                        <option value="primary_owner">Pemilik Utama</option>
                                        <option value="co_owner">Pemilik Bersama</option>
                                    </optgroup>
                                </template>
                                <template x-if="modalForm.membership.entity_type === 'resident'">
                                    <optgroup label="Penghuni">
                                        <option value="primary_resident">Penghuni Utama</option>
                                        <option value="family_member">Anggota Keluarga</option>
                                        <option value="co_resident">Penghuni Bersama</option>
                                        <option value="tenant">Penyewa / Kontrak</option>
                                        <option value="caretaker">Penjaga Rumah</option>
                                    </optgroup>
                                </template>
                            </select>
                        </div>

                        <div x-show="modalForm.membership.sub_role === 'tenant'">
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Batas Akhir Masa Sewa (Opsional)</label>
                            <input type="date" x-model="modalForm.membership.valid_until"
                                   class="w-full rounded-xl border-slate-300 text-sm">
                        </div>

                        <p x-show="modalForm.membership.entity_type === 'owner'"
                           class="text-[11px] text-amber-800 bg-amber-50 p-2.5 rounded-lg border border-amber-200 leading-relaxed">
                            Catatan: Perubahan status dari Penghuni menjadi <strong>Pemilik Rumah</strong> memerlukan pengesahan ulang oleh Koordinator Zona/Blok terkait.
                        </p>
                    </div>
                </template>

                <!-- Footer Tombol Modal -->
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
function ppmtDashboard() {
    return {
        token: localStorage.getItem('ppmt_token'),
        user: null,
        loading: true,
        savingPrivacy: false,
        privacyStatusText: '',
        privacyStatusTimer: null,
        toastMessage: '',
        toastType: 'success',
        pendingApprovals: [],
        generatedInviteUrl: '',

        // State Popup Modal (Kartu 1, Kartu 3, & Kartu 4)
        activeModal: null,
        modalError: '',
        savingModal: false,
        modalForm: {
            display_name: '',
            name: '',
            phone: '',
            current_password: '',
            new_password: '',
            new_password_confirmation: '',
            nik: '',
            kk: '',
            vehicles: [],
            membership: {
                id: null,
                unit_code: '',
                zone: '',
                entity_type: 'owner',
                sub_role: 'primary_owner',
                valid_until: ''
            }
        },

        privacySettings: {
            show_full_name: false,
            show_phone: false,
            show_ownership: false
        },

        inviteForm: {
            unit_code: '',
            entity_type: 'resident',
            sub_role: 'family_member',
            member_valid_until: ''
        },

        async init() {
            if (!this.token) {
                window.location.href = '/';
                return;
            }
            await this.loadMe();
            if (this.user?.is_verified) {
                await this.loadPendingApprovals();
            }
        },

        headers() {
            return {
                'Authorization': 'Bearer ' + this.token,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            };
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
            const entityLabel = m.entity_type === 'owner' ? 'Pemilik Rumah' : 'Penghuni';
            return `${entityLabel} • ${this.subRoleLabel(m.sub_role)}`;
        },

        get modalTitle() {
            const titles = {
                'display_name': 'Ubah Nama Panggilan Lingkungan',
                'name': 'Koreksi Nama Lengkap Resmi',
                'phone': 'Ubah Nomor WhatsApp Login',
                'password': 'Ubah Kata Sandi Akun',
                'nik': 'Perbarui NIK (Terenkripsi AES-256)',
                'kk': 'Perbarui Nomor KK (Terenkripsi AES-256)',
                'vehicles': 'Kelola Kendaraan Terdaftar',
                'membership': 'Koreksi Status Entitas & Peran Kavling'
            };
            return titles[this.activeModal] || '';
        },

        openModal(type) {
            this.modalError = '';
            const existingVehicles = Array.isArray(this.user?.vehicles)
                ? JSON.parse(JSON.stringify(this.user.vehicles))
                : [];

            if (type === 'vehicles' && existingVehicles.length === 0) {
                existingVehicles.push({ type: 'motor', plate: '' });
            }

            this.modalForm = {
                display_name: this.user?.display_name || '',
                name: this.user?.name || '',
                phone: this.user?.phone || '',
                current_password: '',
                new_password: '',
                new_password_confirmation: '',
                nik: '',
                kk: '',
                vehicles: existingVehicles,
                membership: {
                    id: null,
                    unit_code: '',
                    zone: '',
                    entity_type: 'owner',
                    sub_role: 'primary_owner',
                    valid_until: ''
                }
            };
            this.activeModal = type;
        },

        openMembershipModal(m) {
            this.openModal('membership');
            this.modalForm.membership = {
                id: m.id,
                unit_code: m.unit?.code || '',
                zone: m.unit?.zone || '',
                entity_type: m.entity_type,
                sub_role: m.sub_role,
                valid_until: m.valid_until ? m.valid_until.slice(0, 10) : ''
            };
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
                    const m = this.modalForm.membership;
                    const res = await fetch(`/api/v1/memberships/${m.id}`, {
                        method: 'PATCH',
                        headers: this.headers(),
                        body: JSON.stringify({
                            entity_type: m.entity_type,
                            sub_role: m.sub_role,
                            valid_until: m.sub_role === 'tenant' ? (m.valid_until || null) : null
                        })
                    });
                    const json = await res.json();

                    if (!res.ok) {
                        this.modalError = json.message || 'Gagal memperbarui status kavling.';
                        return;
                    }

                    this.closeModal();
                    this.toastType = 'success';
                    this.toastMessage = json.message || 'Status keanggotaan kavling berhasil diperbarui.';
                    await this.loadMe();
                    return;
                }

                const payload = {
                    name: this.user.name,
                    display_name: this.user.display_name,
                    phone: this.user.phone
                };

                if (this.activeModal === 'display_name') {
                    payload.display_name = this.modalForm.display_name;
                } else if (this.activeModal === 'name') {
                    payload.name = this.modalForm.name;
                } else if (this.activeModal === 'phone') {
                    payload.phone = this.modalForm.phone;
                } else if (this.activeModal === 'password') {
                    payload.current_password = this.modalForm.current_password;
                    payload.new_password = this.modalForm.new_password;
                    payload.new_password_confirmation = this.modalForm.new_password_confirmation;
                } else if (this.activeModal === 'nik') {
                    payload.nik = this.modalForm.nik;
                } else if (this.activeModal === 'kk') {
                    payload.kk = this.modalForm.kk;
                } else if (this.activeModal === 'vehicles') {
                    payload.vehicles = this.modalForm.vehicles
                        .filter(v => (v.plate || '').trim() !== '')
                        .map(v => ({
                            type: v.type || 'motor',
                            plate: v.plate.trim().toUpperCase()
                        }));
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
                this.toastMessage = json.message || 'Data berhasil diperbarui.';
                await this.loadMe();
            } catch (e) {
                this.modalError = 'Terjadi gangguan koneksi saat menyimpan.';
            } finally {
                this.savingModal = false;
            }
        },

        async togglePrivacy(key) {
            if (this.savingPrivacy) return;

            const previousValue = Boolean(this.privacySettings[key]);
            this.privacySettings[key] = !previousValue;
            this.savingPrivacy = true;
            this.privacyStatusText = 'Menyimpan...';

            try {
                const res = await fetch('/api/v1/profile', {
                    method: 'PATCH',
                    headers: this.headers(),
                    body: JSON.stringify({
                        name: this.user.name,
                        display_name: this.user.display_name,
                        phone: this.user.phone,
                        privacy_settings: this.privacySettings
                    })
                });

                if (!res.ok) {
                    throw new Error('Gagal memperbarui pengaturan privasi');
                }

                this.user.privacy_settings = { ...this.privacySettings };
                this.privacyStatusText = '✓ Tersimpan';

                clearTimeout(this.privacyStatusTimer);
                this.privacyStatusTimer = setTimeout(() => {
                    this.privacyStatusText = '';
                }, 2200);
            } catch (e) {
                this.privacySettings[key] = previousValue;
                this.privacyStatusText = '';
                this.toastType = 'error';
                this.toastMessage = 'Gagal menyimpan pengaturan privasi. Periksa koneksi Anda.';
            } finally {
                this.savingPrivacy = false;
            }
        },

        async loadMe() {
            try {
                const res = await fetch('/api/v1/me', { headers: this.headers() });
                if (!res.ok) throw new Error('Unauthorized');
                const json = await res.json();
                this.user = json.data;

                this.privacySettings = {
                    show_full_name: Boolean(this.user.privacy_settings?.show_full_name),
                    show_phone: Boolean(this.user.privacy_settings?.show_phone),
                    show_ownership: Boolean(this.user.privacy_settings?.show_ownership)
                };

                if (this.manageableUnits.length > 0 && !this.inviteForm.unit_code) {
                    this.inviteForm.unit_code = this.manageableUnits[0].unit.code;
                }
            } catch (e) {
                localStorage.removeItem('ppmt_token');
                window.location.href = '/';
            } finally {
                this.loading = false;
            }
        },

        get manageableUnits() {
            if (!this.user?.memberships) return [];
            return this.user.memberships.filter(m =>
                m.is_approved && ['primary_owner', 'primary_resident'].includes(m.sub_role)
            );
        },

        async loadPendingApprovals() {
            const res = await fetch('/api/v1/verifications/pending', { headers: this.headers() });
            if (res.ok) {
                const json = await res.json();
                this.pendingApprovals = json.data || [];
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
        },

        async generateInvite() {
            const res = await fetch(`/api/v1/units/${this.inviteForm.unit_code}/invites`, {
                method: 'POST',
                headers: this.headers(),
                body: JSON.stringify(this.inviteForm)
            });
            const json = await res.json();
            if (res.ok) {
                const token = json.data.invite_token;
                const unit = json.data.unit_code;
                this.generatedInviteUrl = `${window.location.origin}/?unit=${unit}&invite=${token}`;
                this.toastType = 'success';
                this.toastMessage = json.message;
            } else {
                this.toastType = 'error';
                this.toastMessage = json.message || 'Gagal membuat tautan undangan.';
            }
        },

        copyInviteUrl() {
            navigator.clipboard.writeText(this.generatedInviteUrl);
            this.toastType = 'success';
            this.toastMessage = 'Tautan undangan disalin! Siap ditempel di WhatsApp keluarga/penyewa.';
        },

        badgeColorClass(color) {
            const map = {
                'amber': 'bg-amber-100 text-amber-900 border-amber-300',
                'emerald': 'bg-emerald-100 text-emerald-900 border-emerald-300',
                'blue': 'bg-blue-100 text-blue-900 border-blue-300',
                'teal': 'bg-teal-100 text-teal-900 border-teal-300',
                'gray': 'bg-slate-100 text-slate-700 border-slate-300'
            };
            return map[color] || map['gray'];
        }
    }
}
</script>
@endsection
@extends('layouts.app')

@section('title', 'Portal Internal Warga — PPMT Digital')

@section('content')
<div x-data="ppmtDashboard()" x-init="init()" class="max-w-6xl mx-auto px-4 py-8 space-y-6">

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
            <button @click="toastMessage = ''" class="text-xs opacity-70 hover:opacity-100">✕</button>
        </div>

        <!-- Banner Peringatan Akun Pending -->
        <div x-show="!user?.is_verified"
             class="bg-amber-50 border-l-4 border-amber-500 p-5 rounded-r-2xl shadow-sm space-y-1">
            <h2 class="font-bold text-amber-900 text-base">⏳ Akun Anda Menunggu Verifikasi Sosial Tetangga</h2>
            <p class="text-xs sm:text-sm text-amber-800 leading-relaxed">
                Demi keamanan lingkungan PPMT, akses ke Direktori Warga dan Pasar Tetangga baru dibuka setelah klaim kavling Anda disahkan oleh <strong>Koordinator Zona/Blok</strong> terkait atau <strong>Pemilik Utama</strong> unit Anda.
            </p>
        </div>

        <!-- Header Profil, Badge Reputasi, & Lencana Jabatan Pengurus -->
        <section class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="space-y-1.5">
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-2xl font-extrabold text-slate-900" x-text="user?.name"></h1>
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
                </p>
            </div>

            <!-- Daftar Unit Hunian Terkait -->
            <div class="flex flex-wrap gap-2">
                <template x-for="m in (user?.memberships || [])" :key="m.id">
                    <div class="px-3.5 py-2 rounded-xl bg-slate-100 border border-slate-200 text-xs">
                        <div class="font-bold text-slate-900" x-text="'Kavling ' + m.unit.code + ' (' + m.unit.zone + ')'"></div>
                        <div class="text-slate-500 capitalize" x-text="m.entity_type + ' • ' + m.sub_role.replace('_', ' ')"></div>
                    </div>
                </template>
            </div>
        </section>

        <!-- Konten Utama (Hanya aktif jika sudah terverifikasi) -->
        <div x-show="user?.is_verified" class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

            <!-- Kolom Kiri (7 Kolom): Progressive Profiling & Privacy Toggle -->
            <section class="lg:col-span-7 bg-white rounded-2xl p-6 shadow-sm border border-slate-200 space-y-5">
                <div class="border-b border-slate-100 pb-3">
                    <h2 class="text-lg font-bold text-slate-900">Profil Bertahap &amp; Kontrol Privasi</h2>
                    <p class="text-xs text-slate-500">Lengkapi data secara sukarela untuk meningkatkan Badge Reputasi. Data kependudukan dienkripsi AES-256.</p>
                </div>

                <form @submit.prevent="saveProfile()" class="space-y-5">
                    <!-- Sakelar Privasi -->
                    <div class="bg-slate-50 p-4 rounded-xl border border-slate-200 space-y-3">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700">Pengaturan Privasi Direktori Warga</h3>

                        <label class="flex items-center justify-between cursor-pointer">
                            <div>
                                <div class="text-sm font-semibold text-slate-800">Tampilkan Nomor WhatsApp ke Tetangga</div>
                                <div class="text-xs text-slate-500">Jika dimatikan, nomor WA Anda disamarkan dari warga umum di direktori.</div>
                            </div>
                            <input type="checkbox" x-model="profileForm.privacy_settings.show_phone"
                                   class="w-5 h-5 rounded text-emerald-600 focus:ring-emerald-600">
                        </label>

                        <label class="flex items-center justify-between cursor-pointer pt-2 border-t border-slate-200">
                            <div>
                                <div class="text-sm font-semibold text-slate-800">Tampilkan Status Kepemilikan Rumah</div>
                                <div class="text-xs text-slate-500">Menampilkan apakah Anda pemilik atau penyewa pada direktori publik internal.</div>
                            </div>
                            <input type="checkbox" x-model="profileForm.privacy_settings.show_ownership"
                                   class="w-5 h-5 rounded text-emerald-600 focus:ring-emerald-600">
                        </label>
                    </div>

                    <!-- Data Kendaraan -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <label class="text-xs font-bold text-slate-700">Data Kendaraan Penghuni (Untuk Keamanan Gerbang)</label>
                            <button type="button" @click="addVehicle()"
                                    class="text-xs font-semibold text-emerald-700 hover:underline">
                                + Tambah Kendaraan
                            </button>
                        </div>

                        <template x-for="(v, idx) in profileForm.vehicles" :key="idx">
                            <div class="flex gap-2 items-center">
                                <select x-model="v.type" class="rounded-xl border-slate-300 text-xs">
                                    <option value="mobil">Mobil</option>
                                    <option value="motor">Motor</option>
                                    <option value="sepeda">Sepeda</option>
                                </select>
                                <input type="text" x-model="v.plate" placeholder="Nomor Polisi (Contoh: N 1234 AB)"
                                       class="flex-1 rounded-xl border-slate-300 text-xs uppercase">
                                <button type="button" @click="profileForm.vehicles.splice(idx, 1)"
                                        class="px-2.5 py-1.5 text-xs text-rose-600 hover:bg-rose-50 rounded-lg">
                                    Hapus
                                </button>
                            </div>
                        </template>
                    </div>

                    <!-- Identitas Terenkripsi AES-256 -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-3 border-t border-slate-100 items-start">
                        <!-- Input NIK -->
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between gap-2 min-h-[24px]">
                                <label class="text-xs font-semibold text-slate-700">
                                    NIK (16 Digit • AES-256)
                                </label>
                                <span x-show="user?.has_nik"
                                    class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                    &#10003; Data Sudah Ada
                                </span>
                                <span x-show="!user?.has_nik"
                                    class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 text-slate-500 border border-slate-200">
                                    Belum Diisi
                                </span>
                            </div>
                            <input type="text" x-model="profileForm.nik" maxlength="16"
                                :placeholder="user?.has_nik ? '•••••••••••• (Ketik untuk memperbarui)' : 'Masukkan 16 digit NIK Anda'"
                                class="w-full rounded-xl border-slate-300 text-xs font-mono">
                            <p x-show="user?.has_nik" class="text-[11px] text-slate-500">
                                NIK sudah tersimpan terenkripsi. Biarkan kosong jika tidak ingin mengubah.
                            </p>
                        </div>

                        <!-- Input Nomor KK -->
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between gap-2 min-h-[24px]">
                                <label class="text-xs font-semibold text-slate-700">
                                    Nomor KK (16 Digit • AES-256)
                                </label>
                                <span x-show="user?.has_kk"
                                    class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                    &#10003; Data Sudah Ada
                                </span>
                                <span x-show="!user?.has_kk"
                                    class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-100 text-slate-500 border border-slate-200">
                                    Belum Diisi
                                </span>
                            </div>
                            <input type="text" x-model="profileForm.kk" maxlength="16"
                                :placeholder="user?.has_kk ? '•••••••••••• (Ketik untuk memperbarui)' : 'Masukkan 16 digit Nomor KK'"
                                class="w-full rounded-xl border-slate-300 text-xs font-mono">
                            <p x-show="user?.has_kk" class="text-[11px] text-slate-500">
                                Nomor KK sudah tersimpan terenkripsi. Biarkan kosong jika tidak ingin mengubah.
                            </p>
                        </div>
                    </div>

                    <button type="submit" :disabled="savingProfile"
                            class="px-5 py-2.5 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white text-sm font-semibold shadow-sm transition">
                        <span x-show="!savingProfile">Simpan Profil &amp; Privasi</span>
                        <span x-show="savingProfile">Menyimpan...</span>
                    </button>
                </form>
            </section>

            <!-- Kolom Kanan (5 Kolom) -->
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
                                    <div class="text-slate-500" x-text="item.phone + ' (' + item.sub_role.replace('_', ' ') + ')'"></div>
                                </div>
                                <button type="button" @click="approveResident(item)"
                                        class="px-3 py-1.5 rounded-lg bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-semibold shrink-0">
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
                                class="w-full py-2 px-4 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-semibold transition">
                            Buat Tautan Undangan
                        </button>
                    </form>

                    <div x-show="generatedInviteUrl" class="p-3 rounded-xl bg-emerald-50 border border-emerald-200 space-y-2">
                        <div class="text-[11px] font-semibold text-emerald-900">Tautan Undangan Siap Dikirim via WhatsApp:</div>
                        <input type="text" readonly :value="generatedInviteUrl"
                               class="w-full rounded-lg bg-white border-emerald-300 text-[11px] font-mono text-slate-700">
                        <button type="button" @click="copyInviteUrl()"
                                class="w-full py-1.5 rounded-lg bg-emerald-700 text-white text-xs font-semibold">
                            Salin Tautan Undangan
                        </button>
                    </div>
                </section>

                <!-- Panel 3: Penyematan Jabatan & Hak Akses -->
                <section x-show="user?.is_global_governor"
                         class="bg-indigo-50/60 rounded-2xl p-5 shadow-sm border border-indigo-200 space-y-4">
                    <div class="border-b border-indigo-200/70 pb-3">
                        <h2 class="text-base font-bold text-indigo-950">Penyematan Jabatan &amp; Hak Akses</h2>
                        <p class="text-xs text-indigo-800">Satu warga dapat merangkap lebih dari satu jabatan tata kelola lingkungan.</p>
                    </div>

                    <form @submit.prevent="assignGovernance()" class="space-y-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Pilih Warga Terverifikasi *</label>
                            <select x-model="govForm.target_user_id" required class="w-full rounded-xl border-slate-300 text-xs">
                                <option value="">-- Pilih Warga PPMT --</option>
                                <template x-for="r in verifiedResidents" :key="r.id">
                                    <option :value="r.id" x-text="r.name + ' (' + r.units.map(u => u.unit_code).join(', ') + ')'"></option>
                                </template>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Jabatan yang Disematkan *</label>
                            <select x-model="govForm.role_code" @change="updateGovScopeDefault()" class="w-full rounded-xl border-slate-300 text-xs">
                                <option value="koordinator_zona">Koordinator Zona</option>
                                <option value="koordinator_blok">Koordinator Blok</option>
                                <option value="ketua_paguyuban">Ketua Paguyuban</option>
                                <option value="sekretaris_paguyuban">Sekretaris Paguyuban</option>
                                <option value="bendahara_paguyuban">Bendahara Paguyuban</option>
                                <option value="keamanan_lingkungan">Keamanan Lingkungan</option>
                                <option value="operator_aplikasi" x-show="user?.is_operator_aplikasi">Operator Aplikasi</option>
                            </select>
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
                                class="w-full py-2 px-4 rounded-xl bg-indigo-700 hover:bg-indigo-800 text-white text-xs font-semibold transition">
                            Sematkan Jabatan
                        </button>
                    </form>

                    <!-- Daftar Jabatan Aktif -->
                    <div class="space-y-2 pt-2 border-t border-indigo-200/70">
                        <div class="text-[11px] font-bold uppercase tracking-wider text-indigo-900">Pengurus Aktif Saat Ini</div>
                        <template x-for="item in governanceList" :key="item.id">
                            <div class="bg-white p-2.5 rounded-xl border border-indigo-100 flex items-center justify-between gap-2 text-xs">
                                <div>
                                    <div class="font-bold text-slate-900" x-text="item.user_name + ' (' + item.unit_codes + ')'"></div>
                                    <div class="text-indigo-700 font-semibold" x-text="item.display_label"></div>
                                </div>
                                <button type="button" @click="revokeGovernance(item.id)"
                                        class="px-2 py-1 rounded text-[11px] text-rose-600 hover:bg-rose-50 font-semibold">
                                    Cabut
                                </button>
                            </div>
                        </template>
                    </div>
                </section>

                <!-- Panel 4: Pendaftaran Langsung Tetangga Terkenal (Assisted Onboarding) -->
                <section x-show="user?.is_global_governor"
                         class="bg-emerald-50/60 rounded-2xl p-5 shadow-sm border border-emerald-200 space-y-4">
                    <div class="border-b border-emerald-200/70 pb-3">
                        <h2 class="text-base font-bold text-emerald-950">Daftarkan Tetangga Terkenal</h2>
                        <p class="text-xs text-emerald-800">Daftarkan tetangga yang sudah Anda kenal secara langsung ke kavlingnya (otomatis terverifikasi atas jaminan Anda).</p>
                    </div>

                    <form @submit.prevent="submitAssistedRegister()" class="space-y-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Kavling Tetangga *</label>
                            <select x-model="assistForm.unit_code" required class="w-full rounded-xl border-slate-300 text-xs">
                                <option value="">-- Pilih Kavling PPMT --</option>
                                <template x-for="u in allUnits" :key="u.id">
                                    <option :value="u.code" x-text="u.code + ' (' + u.zone + ')'"></option>
                                </template>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap Tetangga *</label>
                            <input type="text" x-model="assistForm.name" required placeholder="Contoh: Pak Ahmad"
                                   class="w-full rounded-xl border-slate-300 text-xs">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Nomor WhatsApp Tetangga *</label>
                            <input type="tel" x-model="assistForm.phone" required placeholder="0812xxxxxxxx"
                                   class="w-full rounded-xl border-slate-300 text-xs">
                        </div>

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
                                        </optgroup>
                                    </template>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Kata Sandi Sementara (Min. 8 Karakter) *</label>
                            <input type="text" x-model="assistForm.password" required minlength="8" placeholder="Contoh: ppmt12345"
                                   class="w-full rounded-xl border-slate-300 text-xs font-mono">
                        </div>

                        <button type="submit"
                                class="w-full py-2 px-4 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-semibold transition">
                            Daftarkan &amp; Aktifkan Warga
                        </button>
                    </form>
                </section>

            </div>
        </div>
    </div>
</div>

<script>
function ppmtDashboard() {
    return {
        token: localStorage.getItem('ppmt_token'),
        user: null,
        loading: true,
        savingProfile: false,
        toastMessage: '',
        toastType: 'success',
        pendingApprovals: [],
        generatedInviteUrl: '',
        verifiedResidents: [],
        governanceList: [],
        allUnits: [],
        profileForm: {
            nik: '',
            kk: '',
            vehicles: [],
            privacy_settings: {
                show_phone: false,
                show_ownership: false
            }
        },
        inviteForm: {
            unit_code: '',
            entity_type: 'resident',
            sub_role: 'family_member',
            member_valid_until: ''
        },
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
            if (this.user?.is_verified) {
                await this.loadPendingApprovals();
                if (this.user?.is_global_governor) {
                    await this.loadGovernanceData();
                }
            }
        },

        headers() {
            return {
                'Authorization': 'Bearer ' + this.token,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            };
        },

        async loadMe() {
            try {
                const res = await fetch('/api/v1/me', { headers: this.headers() });
                if (!res.ok) throw new Error('Unauthorized');
                const json = await res.json();
                this.user = json.data;

                this.profileForm.vehicles = Array.isArray(this.user.vehicles) ? this.user.vehicles : [];
                this.profileForm.privacy_settings = {
                    show_phone: Boolean(this.user.privacy_settings?.show_phone),
                    show_ownership: Boolean(this.user.privacy_settings?.show_ownership)
                };

                if (this.manageableUnits.length > 0) {
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

        addVehicle() {
            this.profileForm.vehicles.push({ type: 'motor', plate: '' });
        },

        async saveProfile() {
            this.savingProfile = true;
            this.toastMessage = '';
            const payload = {
                vehicles: this.profileForm.vehicles,
                privacy_settings: this.profileForm.privacy_settings
            };
            if (this.profileForm.nik) payload.nik = this.profileForm.nik;
            if (this.profileForm.kk) payload.kk = this.profileForm.kk;

            try {
                const res = await fetch('/api/v1/profile', {
                    method: 'PATCH',
                    headers: this.headers(),
                    body: JSON.stringify(payload)
                });
                const json = await res.json();
                if (!res.ok) {
                    this.toastType = 'error';
                    this.toastMessage = json.message || 'Gagal menyimpan profil.';
                    return;
                }
                this.profileForm.nik = '';
                this.profileForm.kk = '';
                this.toastType = 'success';
                this.toastMessage = json.message;
                await this.loadMe();
            } finally {
                this.savingProfile = false;
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
@extends('layouts.app')

@section('title', 'Buku Induk Kavling & Tata Kelola — Panel Pengurus PPMT')
@section('subpage_title', 'Buku Induk & Tata Kelola')
@section('subpage_back_url', '/pengurus')

@section('content')
<div x-data="ppmtRegistryGovernancePanel()" x-init="init()" class="max-w-6xl mx-auto px-4 py-6 sm:py-8 space-y-5">

    <!-- Loading State -->
    <div x-show="loading" class="bg-white rounded-2xl p-12 text-center text-sm text-slate-500 border border-slate-200">
        Memuat buku induk kavling...
    </div>

    <div x-show="!loading && user" x-cloak class="space-y-5">

        <!-- Notifikasi Global -->
        <div x-show="toastMessage"
             x-cloak
             :class="toastType === 'error' ? 'bg-rose-50 text-rose-800 border-rose-200' : 'bg-emerald-50 text-emerald-800 border-emerald-200'"
             class="p-4 rounded-xl border text-sm font-medium flex justify-between items-center shadow-sm">
            <span x-text="toastMessage"></span>
            <button type="button" @click="toastMessage = ''" class="text-sm opacity-70 hover:opacity-100 cursor-pointer">✕</button>
        </div>

        <!-- ANTREAN PERMINTAAN PEMULIHAN SANDI WARGA -->
        <div x-show="recoveryTickets.length > 0"
             x-cloak
             class="bg-amber-50 border border-amber-300 rounded-2xl p-4 sm:p-5 space-y-3 shadow-sm">
            <div class="flex items-center justify-between gap-2">
                <h3 class="text-sm font-extrabold text-amber-950">🔑 Permintaan Pemulihan Kata Sandi Aktif</h3>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-200 text-amber-950"
                      x-text="recoveryTickets.length + ' tiket'"></span>
            </div>
            <p class="text-xs text-amber-900">
                Pastikan identitas pemohon valid secara langsung atau via chat WhatsApp pribadi sebelum menyerahkan 6-digit kode berikut:
            </p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-2.5">
                <template x-for="t in recoveryTickets" :key="t.id">
                    <div class="bg-white rounded-xl p-3 border border-amber-200 flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <div class="font-bold text-sm text-slate-900 truncate" x-text="t.user_name + ' (' + (t.unit_code || '-') + ')'"></div>
                            <div class="text-xs text-slate-500 font-mono" x-text="t.contact + ' • Sisa ' + t.expires_in_minutes + ' mnt'"></div>
                        </div>
                        <div class="px-3 py-1.5 rounded-lg bg-slate-900 text-amber-300 font-mono font-extrabold text-base tracking-widest shrink-0"
                             x-text="t.recovery_code"></div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Navigasi Tab (Muncul jika Gubernur / Pengurus Pusat) -->
        <div x-show="user?.is_global_governor" class="flex flex-wrap items-center gap-2 border-b border-slate-200 pb-3">
            <button type="button"
                    @click="activeTab = 'registry'"
                    :class="activeTab === 'registry' ? 'bg-slate-900 text-white' : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-50'"
                    class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition cursor-pointer">
                Buku Induk Kavling (<span x-text="registryUnits.length"></span>)
            </button>
            <button type="button"
                    @click="activeTab = 'governance'"
                    :class="activeTab === 'governance' ? 'bg-slate-900 text-white' : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-50'"
                    class="px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition cursor-pointer">
                Pendaftaran Tetangga &amp; Jabatan
            </button>
        </div>

        <!-- ================================================================= -->
        <!-- TAB 1: BUKU INDUK KAVLING (PADAT, ADAPTIF & PAGINATION)           -->
        <!-- ================================================================= -->
        <section x-show="activeTab === 'registry'" class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">

            <!-- Bilah Header & Filter Kompak -->
            <div class="p-4 sm:px-5 border-b border-slate-200 bg-slate-50/70 flex flex-col lg:flex-row lg:items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-base sm:text-lg font-bold text-slate-900">Buku Induk Kavling</h2>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200"
                          x-text="filteredRegistryUnits.length + ' unit'"></span>
                    <a href="/peta-kavling"
                       class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 transition">
                        Peta ↗
                    </a>
                </div>

                <div class="grid grid-cols-2 sm:flex sm:flex-wrap items-center gap-2">
                    <input type="text"
                           x-model="registrySearch"
                           placeholder="Cari kavling / nama / kontak..."
                           class="col-span-2 sm:w-52 rounded-xl border-slate-300 text-xs sm:text-sm py-2 focus:border-emerald-600 focus:ring-emerald-600">

                    <select x-model="registryFilterBlock" class="rounded-xl border-slate-300 text-xs sm:text-sm py-2">
                        <option value="">Semua Blok</option>
                        <option value="R">Blok R</option>
                        <option value="A">Blok A</option>
                        <option value="B">Blok B</option>
                        <option value="C">Blok C</option>
                        <option value="D">Blok D</option>
                    </select>

                    <select x-model="registryFilterZone" class="rounded-xl border-slate-300 text-xs sm:text-sm py-2">
                        <option value="">Semua Zona</option>
                        <option value="ZONA-1">ZONA-1</option>
                        <option value="ZONA-2">ZONA-2</option>
                        <option value="ZONA-3">ZONA-3</option>
                        <option value="ZONA-4">ZONA-4</option>
                    </select>

                    <select x-model="registryFilterStatus" class="col-span-2 sm:col-span-1 rounded-xl border-slate-300 text-xs sm:text-sm py-2">
                        <option value="">Semua Status</option>
                        <option value="occupied">Terdata</option>
                        <option value="rented">Disewakan</option>
                        <option value="pending">Pending</option>
                        <option value="unclaimed">Belum Terdata</option>
                    </select>
                </div>
            </div>

            <div x-show="filteredRegistryUnits.length === 0"
                 class="p-10 text-center text-sm text-slate-500">
                Tidak ada kavling yang sesuai dengan filter.
            </div>

            <!-- TAMPILAN DESKTOP: TABEL PADAT (md ke atas) -->
            <div x-show="filteredRegistryUnits.length > 0" class="hidden md:block overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-xs font-bold uppercase tracking-wider text-slate-500">
                            <th class="py-3 px-4 w-32">Kavling</th>
                            <th class="py-3 px-3 w-36">Status</th>
                            <th class="py-3 px-4">Warga &amp; Kepemilikan SHM</th>
                            <th class="py-3 px-4 w-24 text-right">Detail</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="u in paginatedRegistryUnits" :key="'dt-' + u.id">
                            <tr class="hover:bg-slate-50/80 align-top">
                                <!-- Kolom 1: Kavling & Zona -->
                                <td class="py-3 px-4 whitespace-nowrap">
                                    <div class="flex items-center gap-2">
                                        <a :href="'/peta-kavling?unit=' + encodeURIComponent(u.code)"
                                           class="px-2.5 py-0.5 rounded bg-slate-900 hover:bg-emerald-800 text-white font-mono font-extrabold text-xs transition"
                                           x-text="u.code"></a>
                                        <span class="text-xs text-slate-400 font-medium" x-text="u.zone.replace('ZONA-', 'Z')"></span>
                                    </div>
                                </td>

                                <!-- Kolom 2: Status Pendataan -->
                                <td class="py-3 px-3 whitespace-nowrap">
                                    <span :class="{
                                            'bg-emerald-50 text-emerald-700 border-emerald-200': u.occupancy_status === 'occupied',
                                            'bg-sky-50 text-sky-700 border-sky-200': u.occupancy_status === 'rented',
                                            'bg-amber-50 text-amber-800 border-amber-300': u.occupancy_status === 'pending',
                                            'bg-slate-100 text-slate-600 border-slate-300': u.occupancy_status === 'unclaimed'
                                          }"
                                          class="px-2.5 py-0.5 rounded-full text-xs font-bold border"
                                          x-text="formatUnitStatusShort(u.occupancy_status)">
                                    </span>
                                </td>

                                <!-- Kolom 3: Daftar Warga Ringkas -->
                                <td class="py-3 px-4">
                                    <div x-show="u.members.length === 0" class="text-slate-400">—</div>

                                    <div x-show="u.members.length > 0" class="space-y-2">
                                        <template x-for="m in u.members" :key="'dtm-' + m.membership_id">
                                            <div class="space-y-1">
                                                <div class="flex flex-wrap items-center justify-between gap-2">
                                                    <div class="flex flex-wrap items-center gap-2">
                                                        <span class="font-bold text-slate-900" x-text="m.name"></span>
                                                        <span class="text-xs text-slate-500" x-text="'• ' + m.sub_role_label"></span>
                                                        <span x-show="!m.is_approved"
                                                              class="px-2 py-0.5 rounded bg-amber-100 text-amber-800 text-xs font-bold">
                                                            Pending
                                                        </span>
                                                        <span class="font-mono text-xs text-slate-500" x-text="m.contact"></span>
                                                    </div>

                                                    <!-- Indikator & Aksi Ringkas SHM -->
                                                    <div x-show="m.is_owner" class="flex items-center gap-1.5">
                                                        <span x-show="m.ownership_verification_status === 'verified_shm'"
                                                              class="px-2 py-0.5 rounded bg-emerald-50 text-emerald-800 border border-emerald-200 text-xs font-bold"
                                                              x-text="'SHM ✓ (' + m.endorsement_count + ')'">
                                                        </span>
                                                        <span x-show="m.ownership_verification_status !== 'verified_shm'"
                                                              class="px-2 py-0.5 rounded bg-amber-50 text-amber-800 border border-amber-200 text-xs font-semibold">
                                                            SHM ?
                                                        </span>

                                                        <button x-show="m.can_verify_ownership && !m.is_endorsed_by_me"
                                                                type="button"
                                                                @click="verifyUnitOwnership(u.code, m, 'approve_shm')"
                                                                class="px-2.5 py-0.5 rounded bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold transition cursor-pointer">
                                                            Sahkan
                                                        </button>
                                                        <button x-show="m.can_verify_ownership && m.is_endorsed_by_me"
                                                                type="button"
                                                                @click="verifyUnitOwnership(u.code, m, 'revoke_shm')"
                                                                class="px-2.5 py-0.5 rounded border border-slate-200 hover:bg-rose-50 text-rose-600 text-xs font-semibold transition cursor-pointer">
                                                            Cabut
                                                        </button>
                                                    </div>
                                                </div>

                                                <!-- Detail Terlipat (Muncul saat tombol Detail diklik) -->
                                                <div x-show="expandedUnitIds.includes(u.id)"
                                                     class="pl-3 border-l-2 border-slate-200 text-xs text-slate-600 space-y-1.5 py-1.5">
                                                    <div x-show="m.valid_until" class="text-sky-700 font-medium"
                                                         x-text="'Masa sewa s/d ' + m.valid_until"></div>
                                                    <div x-show="m.has_nib_shm" class="font-mono">
                                                        NIB/SHM: <strong class="text-slate-800" x-text="m.can_see_raw_nib ? m.nib_shm_plain : m.nib_shm_masked"></strong>
                                                    </div>
                                                    <div x-show="m.endorsed_by_sentence" class="text-emerald-700"
                                                         x-text="m.endorsed_by_sentence"></div>

                                                    <!-- Bantuan Pemulihan Sandi & Sesi Bantuan Teknis Admin -->
                                                    <div class="pt-1 flex flex-wrap items-center gap-2">
                                                        <button type="button"
                                                                @click="issuePasswordRecoveryTicket(m)"
                                                                class="px-2.5 py-1 rounded-lg bg-amber-50 hover:bg-amber-100 text-amber-900 border border-amber-300 text-xs font-bold transition cursor-pointer">
                                                            🔑 Terbitkan Kode Reset Sandi
                                                        </button>
                                                        <button x-show="user?.is_pure_system_admin && m.user_id !== user?.id"
                                                                type="button"
                                                                @click="startAuditedImpersonation(m)"
                                                                class="px-2.5 py-1 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-800 border border-rose-200 text-xs font-bold transition cursor-pointer">
                                                            🛡️ Bantu Akses (15 Menit)
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </td>

                                <!-- Kolom 4: Tombol Expand Detail -->
                                <td class="py-3 px-4 text-right whitespace-nowrap">
                                    <button x-show="hasExpandableDetail(u)"
                                            type="button"
                                            @click="toggleExpandUnit(u.id)"
                                            class="px-2.5 py-1 rounded border border-slate-200 hover:bg-slate-100 text-slate-600 text-xs font-semibold cursor-pointer"
                                            x-text="expandedUnitIds.includes(u.id) ? 'Tutup ▴' : 'Detail ▾'">
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <!-- TAMPILAN MOBILE: DAFTAR PADAT (< md) -->
            <div x-show="filteredRegistryUnits.length > 0" class="md:hidden divide-y divide-slate-100">
                <template x-for="u in paginatedRegistryUnits" :key="'mb-' + u.id">
                    <div class="p-4 space-y-2.5 text-sm">
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex items-center gap-2">
                                <a :href="'/peta-kavling?unit=' + encodeURIComponent(u.code)"
                                   class="px-2.5 py-0.5 rounded bg-slate-900 text-white font-mono font-extrabold text-xs"
                                   x-text="u.code"></a>
                                <span :class="{
                                        'bg-emerald-50 text-emerald-700 border-emerald-200': u.occupancy_status === 'occupied',
                                        'bg-sky-50 text-sky-700 border-sky-200': u.occupancy_status === 'rented',
                                        'bg-amber-50 text-amber-800 border-amber-300': u.occupancy_status === 'pending',
                                        'bg-slate-100 text-slate-600 border-slate-300': u.occupancy_status === 'unclaimed'
                                      }"
                                      class="px-2.5 py-0.5 rounded-full text-xs font-bold border"
                                      x-text="formatUnitStatusShort(u.occupancy_status)">
                                </span>
                            </div>

                            <button x-show="hasExpandableDetail(u)"
                                    type="button"
                                    @click="toggleExpandUnit(u.id)"
                                    class="text-xs font-semibold text-slate-600 hover:text-slate-900 cursor-pointer"
                                    x-text="expandedUnitIds.includes(u.id) ? 'Tutup ▴' : 'Detail ▾'">
                            </button>
                        </div>

                        <div x-show="u.members.length > 0" class="space-y-2 pl-1">
                            <template x-for="m in u.members" :key="'mbm-' + m.membership_id">
                                <div class="space-y-1.5">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="min-w-0 leading-snug">
                                            <span class="font-bold text-slate-900" x-text="m.name"></span>
                                            <span class="text-xs text-slate-500" x-text="' (' + m.sub_role_label + ')'"></span>
                                        </div>

                                        <div x-show="m.is_owner" class="flex items-center gap-1.5 shrink-0">
                                            <span x-show="m.ownership_verification_status === 'verified_shm'"
                                                  class="px-2 py-0.5 rounded bg-emerald-50 text-emerald-800 border border-emerald-200 text-xs font-bold"
                                                  x-text="'SHM ✓' + m.endorsement_count"></span>
                                            <button x-show="m.can_verify_ownership && !m.is_endorsed_by_me"
                                                    type="button"
                                                    @click="verifyUnitOwnership(u.code, m, 'approve_shm')"
                                                    class="px-2.5 py-0.5 rounded bg-emerald-700 text-white text-xs font-bold cursor-pointer">
                                                Sahkan
                                            </button>
                                            <button x-show="m.can_verify_ownership && m.is_endorsed_by_me"
                                                    type="button"
                                                    @click="verifyUnitOwnership(u.code, m, 'revoke_shm')"
                                                    class="px-2.5 py-0.5 rounded border border-slate-200 text-rose-600 text-xs font-semibold cursor-pointer">
                                                Cabut
                                            </button>
                                        </div>
                                    </div>

                                    <div x-show="expandedUnitIds.includes(u.id)"
                                         class="pl-3 border-l-2 border-slate-200 text-xs text-slate-600 space-y-1.5 py-1">
                                        <div class="font-mono" x-text="m.contact"></div>
                                        <div x-show="m.valid_until" class="text-sky-700" x-text="'Sewa s/d ' + m.valid_until"></div>
                                        <div x-show="m.has_nib_shm" class="font-mono">
                                            NIB/SHM: <strong x-text="m.can_see_raw_nib ? m.nib_shm_plain : m.nib_shm_masked"></strong>
                                        </div>
                                        <div x-show="m.endorsed_by_sentence" class="text-emerald-700" x-text="m.endorsed_by_sentence"></div>
                                        <div class="pt-1 flex flex-wrap items-center gap-2">
                                            <button type="button"
                                                    @click="issuePasswordRecoveryTicket(m)"
                                                    class="px-2.5 py-1 rounded-lg bg-amber-50 text-amber-900 border border-amber-300 text-xs font-bold cursor-pointer">
                                                🔑 Kode Reset Sandi
                                            </button>
                                            <button x-show="user?.is_pure_system_admin && m.user_id !== user?.id"
                                                    type="button"
                                                    @click="startAuditedImpersonation(m)"
                                                    class="px-2.5 py-1 rounded-lg bg-rose-50 text-rose-800 border border-rose-200 text-xs font-bold cursor-pointer">
                                                🛡️ Bantu Akses (15 Mnt)
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Bilah Pagination Kompak (Desktop & Mobile) -->
            <div x-show="filteredRegistryUnits.length > 0"
                 class="px-4 py-3.5 bg-slate-50/70 border-t border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs sm:text-sm">
                <div class="flex items-center justify-between sm:justify-start gap-3 text-slate-600">
                    <span>
                        Menampilkan <strong class="text-slate-900" x-text="paginationRangeText"></strong>
                        dari <strong class="text-slate-900" x-text="filteredRegistryUnits.length"></strong> unit
                    </span>

                    <select x-model.number="perPage"
                            @change="currentPage = 1"
                            aria-label="Jumlah baris per halaman"
                            class="rounded-lg border-slate-300 text-xs sm:text-sm py-1.5 pr-8 bg-white">
                        <option :value="15">15 / hal</option>
                        <option :value="30">30 / hal</option>
                        <option :value="50">50 / hal</option>
                        <option :value="999">Semua</option>
                    </select>
                </div>

                <div x-show="totalPages > 1" class="flex items-center justify-end gap-2">
                    <button type="button"
                            @click="goToPage(currentPage - 1)"
                            :disabled="currentPage <= 1"
                            class="px-3 py-1.5 rounded-lg border border-slate-300 bg-white text-slate-700 font-semibold hover:bg-slate-100 disabled:opacity-40 disabled:pointer-events-none transition cursor-pointer">
                        &lsaquo; Sebelumnya
                    </button>

                    <span class="px-2.5 py-1 font-mono text-xs font-bold text-slate-700"
                          x-text="currentPage + ' / ' + totalPages"></span>

                    <button type="button"
                            @click="goToPage(currentPage + 1)"
                            :disabled="currentPage >= totalPages"
                            class="px-3 py-1.5 rounded-lg border border-slate-300 bg-white text-slate-700 font-semibold hover:bg-slate-100 disabled:opacity-40 disabled:pointer-events-none transition cursor-pointer">
                        Berikutnya &rsaquo;
                    </button>
                </div>
            </div>
        </section>

        <!-- ================================================================= -->
        <!-- TAB 2: PENDAFTARAN TETANGGA & PENYEMATAN JABATAN (Gubernur)       -->
        <!-- ================================================================= -->
        <div x-show="user?.is_global_governor && activeTab === 'governance'"
             class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

            <!-- Kolom Kiri (5 Kolom): Pendaftaran Langsung Tetangga Terkenal -->
            <section id="assisted-register"
                     class="lg:col-span-5 bg-emerald-50/60 rounded-2xl p-5 sm:p-6 shadow-sm border border-emerald-200 space-y-4">
                <div class="border-b border-emerald-200/70 pb-3">
                    <h2 class="text-base sm:text-lg font-bold text-emerald-950">Daftarkan Tetangga Terkenal</h2>
                    <p class="text-xs sm:text-sm text-emerald-800 mt-0.5">Otomatis aktif sebagai <strong>Warga PPMT</strong> tanpa menunggu antrean verifikasi.</p>
                </div>

                <form @submit.prevent="submitAssistedRegister()" class="space-y-3.5">
                    <div class="space-y-3 p-3.5 rounded-xl bg-white/90 border border-emerald-200">
                        <div>
                            <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1">Nomor WhatsApp</label>
                            <input type="tel" x-model="assistForm.phone" placeholder="0812xxxxxxxx"
                                   class="w-full rounded-xl border-slate-300 text-sm">
                        </div>

                        <div>
                            <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1">Username <span class="text-slate-400 font-normal text-xs">(opsional jika isi WA)</span></label>
                            <input type="text"
                                   x-model="assistForm.username"
                                   @input="assistForm.username = (assistForm.username || '').toLowerCase().replace(/[^a-z0-9._]/g, '')"
                                   placeholder="paksugeng.b12a"
                                   class="w-full rounded-xl border-slate-300 text-sm font-mono lowercase">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1">Kata Sandi Sementara (Min. 8 Karakter) *</label>
                        <input type="text" x-model="assistForm.password" required minlength="8" placeholder="ppmt12345"
                               class="w-full rounded-xl border-slate-300 text-sm font-mono">
                    </div>

                    <div>
                        <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1">Nama Lengkap *</label>
                        <input type="text" x-model="assistForm.name" required placeholder="Ahmad Fauzi"
                               class="w-full rounded-xl border-slate-300 text-sm">
                    </div>

                    <div>
                        <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1">Kavling *</label>
                        <select x-model="assistForm.unit_code" required class="w-full rounded-xl border-slate-300 text-sm">
                            <option value="">-- Pilih Kavling --</option>
                            <template x-for="u in allUnits" :key="u.id">
                                <option :value="u.code"
                                        x-text="u.code + ' (' + u.zone + ') • ' + formatUnitStatusShort(u.occupancy_status)">
                                </option>
                            </template>
                        </select>
                    </div>

                    <div class="space-y-3 p-3.5 rounded-xl bg-white/95 border border-emerald-200 text-sm">
                        <label class="flex items-start gap-2.5 cursor-pointer">
                            <input type="checkbox" x-model="assistForm.is_owner" class="mt-1 rounded text-amber-600 focus:ring-amber-500">
                            <div class="flex-1 space-y-2">
                                <span class="font-semibold text-slate-900">Pemilik Kavling</span>
                                <div x-show="assistForm.is_owner" class="space-y-2">
                                    <select x-model="assistForm.owner_role" class="w-full rounded-lg border-slate-300 text-sm">
                                        <option value="primary_owner">Pemilik Utama</option>
                                        <option value="co_owner">Pemilik Bersama</option>
                                    </select>
                                    <input type="text" x-model="assistForm.nib_shm"
                                           placeholder="Nomor NIB SHM (opsional)"
                                           class="w-full rounded-lg border-slate-300 text-sm font-mono uppercase">
                                </div>
                            </div>
                        </label>

                        <label class="flex items-start gap-2.5 cursor-pointer pt-2 border-t border-slate-100">
                            <input type="checkbox" x-model="assistForm.is_occupant" class="mt-1 rounded text-emerald-600 focus:ring-emerald-600">
                            <div class="flex-1 space-y-2">
                                <span class="font-semibold text-slate-900">Menghuni / Tinggal di Kavling</span>
                                <div x-show="assistForm.is_occupant">
                                    <select x-model="assistForm.resident_role" class="w-full rounded-lg border-slate-300 text-sm">
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
                            class="w-full py-2.5 px-4 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white text-sm font-bold transition cursor-pointer">
                        Daftarkan &amp; Aktifkan Warga
                    </button>
                </form>
            </section>

            <!-- Kolom Kanan (7 Kolom): Penyematan Jabatan & Hak Akses -->
            <section class="lg:col-span-7 bg-white rounded-2xl p-5 sm:p-6 shadow-sm border border-slate-200 space-y-4">
                <div class="border-b border-slate-100 pb-3 flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <h2 class="text-base sm:text-lg font-bold text-slate-900">
                            <span x-text="user?.is_ketua_paguyuban ? 'Susunan Kabinet Pengurus & Koordinator' : 'Penetapan Mandat & Jabatan'"></span>
                        </h2>
                        <p class="text-xs text-slate-500 mt-0.5"
                        x-text="user?.is_ketua_paguyuban ? 'Hak prerogatif Ketua Paguyuban dalam menyusun sekretaris, bendahara, seksi bidang, dan koordinator wilayah.' : 'Mandat teknis Admin Sistem (Operator Aplikasi & pengukuhan Ketua Paguyuban).'"></p>
                    </div>
                    <a x-show="user?.is_operator_aplikasi"
                    href="/pengurus/hak-akses"
                    class="px-3 py-1.5 rounded-xl bg-rose-50 hover:bg-rose-100 text-rose-800 border border-rose-200 text-xs font-bold transition">
                        Audit Matriks Hak Akses &rarr;
                    </a>
                </div>

                <div class="border-b border-slate-100 pb-3">
                    <h2 class="text-base sm:text-lg font-bold text-slate-900">Struktur Jabatan &amp; Hak Akses</h2>
                </div>

                <form x-show="availableRoleOptions.length > 0"
                      @submit.prevent="assignGovernance()"
                      class="bg-indigo-50/60 p-4 rounded-xl border border-indigo-200 space-y-3.5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1">Warga Terverifikasi *</label>
                            <select x-model="govForm.target_user_id" required class="w-full rounded-xl border-slate-300 text-sm">
                                <option value="">-- Pilih Warga --</option>
                                <template x-for="r in verifiedResidents" :key="r.id">
                                    <option :value="r.id" x-text="(r.display_name || r.name) + ' (' + r.units.map(u => u.unit_code).join(', ') + ')'"></option>
                                </template>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1">Jabatan *</label>
                            <select x-model="govForm.role_code" @change="updateGovScopeDefault()" class="w-full rounded-xl border-slate-300 text-sm">
                                <template x-for="opt in availableRoleOptions" :key="opt.code">
                                    <option :value="opt.code" x-text="opt.label"></option>
                                </template>
                            </select>
                        </div>
                    </div>

                    <div x-show="govForm.role_code === 'koordinator_zona'">
                        <select x-model="govForm.scope_value" class="w-full rounded-xl border-slate-300 text-sm">
                            <option value="ZONA-1">ZONA-1 (R1-R2, A-1..16)</option>
                            <option value="ZONA-2">ZONA-2 (B-1..24)</option>
                            <option value="ZONA-3">ZONA-3 (A-17..25, C-1..14)</option>
                            <option value="ZONA-4">ZONA-4 (C-15..22, D-1..17)</option>
                        </select>
                    </div>

                    <div x-show="govForm.role_code === 'koordinator_blok'">
                        <select x-model="govForm.scope_value" class="w-full rounded-xl border-slate-300 text-sm">
                            <option value="R">Blok R (Ruko)</option>
                            <option value="A">Blok A</option>
                            <option value="B">Blok B</option>
                            <option value="C">Blok C</option>
                            <option value="D">Blok D</option>
                        </select>
                    </div>

                    <button type="submit"
                            class="w-full sm:w-auto px-5 py-2.5 rounded-xl bg-indigo-700 hover:bg-indigo-800 text-white text-sm font-bold transition cursor-pointer">
                        + Sematkan Jabatan
                    </button>
                </form>

                <!-- Daftar Jabatan Aktif -->
                <div class="divide-y divide-slate-100 border border-slate-200 rounded-xl">
                    <template x-for="item in governanceList" :key="item.id">
                        <div class="p-3.5 flex items-center justify-between gap-2 text-sm">
                            <div>
                                <span class="font-bold text-slate-900" x-text="item.user_name"></span>
                                <span class="text-slate-500" x-text="' (' + item.unit_codes + ')'"></span>
                                <div class="text-indigo-700 font-semibold text-xs mt-0.5" x-text="item.display_label"></div>
                            </div>
                            <button x-show="item.can_revoke"
                                    type="button"
                                    @click="revokeGovernance(item.id)"
                                    class="px-3 py-1 rounded-lg text-xs text-rose-600 hover:bg-rose-50 font-semibold transition cursor-pointer">
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
        { code: 'seksi_ekonomi', label: 'Seksi Ekonomi & UMKM' },
        { code: 'koordinator_zona', label: 'Koordinator Zona' },
        { code: 'koordinator_blok', label: 'Koordinator Blok' },
        { code: 'keamanan_lingkungan', label: 'Keamanan Lingkungan' }
    ];

    return {
        token: localStorage.getItem('ppmt_token'),
        user: null,
        loading: true,
        activeTab: 'registry',
        expandedUnitIds: [],
        currentPage: 1,
        perPage: 15,
        toastMessage: '',
        toastType: 'success',
        recoveryTickets: [],
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
            if (Array.isArray(this.user?.assignable_role_options) && this.user.assignable_role_options.length > 0) {
                return this.user.assignable_role_options;
            }

            const allowed = Array.isArray(this.user?.assignable_governance_roles)
                ? this.user.assignable_governance_roles
                : [];
            return ALL_ROLE_OPTIONS.filter(opt => allowed.includes(opt.code));
        },

        get filteredRegistryUnits() {
            const q = (this.registrySearch || '').trim().toLowerCase();
            const blockOrder = { 'R': 1, 'A': 2, 'B': 3, 'C': 4, 'D': 5 };

            return this.registryUnits
                .filter(u => {
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
                })
                .sort((a, b) => {
                    const orderA = blockOrder[a.block] ?? 99;
                    const orderB = blockOrder[b.block] ?? 99;
                    if (orderA !== orderB) return orderA - orderB;

                    return (a.code || '').localeCompare(b.code || '', 'id', {
                        numeric: true,
                        sensitivity: 'base'
                    });
                });
        },

        get totalPages() {
            const total = this.filteredRegistryUnits.length;
            if (total === 0) return 1;
            return Math.ceil(total / this.perPage);
        },

        get paginatedRegistryUnits() {
            const start = (this.currentPage - 1) * this.perPage;
            return this.filteredRegistryUnits.slice(start, start + this.perPage);
        },

        get paginationRangeText() {
            const total = this.filteredRegistryUnits.length;
            if (total === 0) return '0';
            const start = (this.currentPage - 1) * this.perPage + 1;
            const end = Math.min(this.currentPage * this.perPage, total);
            return `${start}–${end}`;
        },

        goToPage(page) {
            if (page < 1 || page > this.totalPages) return;
            this.currentPage = page;
        },

        toggleExpandUnit(unitId) {
            const idx = this.expandedUnitIds.indexOf(unitId);
            if (idx >= 0) {
                this.expandedUnitIds.splice(idx, 1);
            } else {
                this.expandedUnitIds.push(unitId);
            }
        },

        hasExpandableDetail(u) {
            return (u.members || []).length > 0;
        },

        async init() {
            if (!this.token) {
                window.location.href = '/';
                return;
            }

            this.$watch('registrySearch', () => { this.currentPage = 1; });
            this.$watch('registryFilterBlock', () => { this.currentPage = 1; });
            this.$watch('registryFilterZone', () => { this.currentPage = 1; });
            this.$watch('registryFilterStatus', () => { this.currentPage = 1; });

            await this.loadMe();
            if (!this.user?.has_admin_access) {
                window.location.href = '/dashboard';
                return;
            }
            if (this.availableRoleOptions.length > 0) {
                this.govForm.role_code = this.availableRoleOptions[0].code;
                this.updateGovScopeDefault();
            }

            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('prefill_phone') || urlParams.has('prefill_name') || urlParams.has('prefill_unit')) {
                this.activeTab = 'governance';
                this.assistForm.phone = urlParams.get('prefill_phone') || '';
                this.assistForm.name = urlParams.get('prefill_name') || '';
                this.assistForm.unit_code = urlParams.get('prefill_unit') || '';
                this.toastType = 'success';
                this.toastMessage = 'Data kontak dari Grup WhatsApp telah diisikan ke formulir pendaftaran.';
                window.history.replaceState({}, '', '/pengurus/buku-induk#assisted-register');
            }

            const tasks = [this.loadOccupancyRegistry(), this.loadRecoveryTickets()];
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

        formatUnitStatusShort(status) {
            const labels = {
                'unclaimed': 'Belum Terdata',
                'pending': 'Pending',
                'occupied': 'Terdata',
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

        async loadRecoveryTickets() {
            const res = await fetch('/api/v1/governance/recovery-tickets', { headers: this.headers() });
            if (res.ok) {
                const json = await res.json();
                this.recoveryTickets = json.data || [];
            }
        },

        async issuePasswordRecoveryTicket(member) {
            if (!confirm(`Terbitkan kode pemulihan sandi 6-digit (berlaku 15 menit) untuk ${member.name}?`)) {
                return;
            }
            const res = await fetch(`/api/v1/governance/recovery-tickets/${member.user_id}`, {
                method: 'POST',
                headers: this.headers()
            });
            const json = await res.json();
            if (res.ok) {
                this.toastType = 'success';
                this.toastMessage = `Kode pemulihan untuk ${member.name}: ${json.data.recovery_code} (berlaku 15 menit).`;
                await this.loadRecoveryTickets();
            } else {
                this.toastType = 'error';
                this.toastMessage = json.message || 'Gagal menerbitkan kode pemulihan.';
            }
        },

        async startAuditedImpersonation(member) {
            const reason = prompt(
                `Masukkan alasan teknis mengakses sementara akun ${member.name} (minimal 15 karakter — tercatat permanen di log keamanan warga):`
            );
            if (!reason || reason.trim().length < 15) {
                if (reason !== null) {
                    this.toastType = 'error';
                    this.toastMessage = 'Alasan bantuan teknis wajib diisi minimal 15 karakter.';
                }
                return;
            }

            const res = await fetch(`/api/v1/governance/impersonate/${member.user_id}`, {
                method: 'POST',
                headers: this.headers(),
                body: JSON.stringify({ reason: reason.trim() })
            });
            const json = await res.json();
            if (res.ok && json.impersonation_token) {
                sessionStorage.setItem('ppmt_admin_restore_token', this.token);
                sessionStorage.setItem('ppmt_is_impersonated', '1');
                sessionStorage.setItem('ppmt_impersonated_name', member.name);
                sessionStorage.removeItem('ppmt_nav_profile');
                localStorage.setItem('ppmt_token', json.impersonation_token);
                window.location.href = '/dashboard';
            } else {
                this.toastType = 'error';
                this.toastMessage = json.message || 'Gagal memulai sesi bantuan teknis.';
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
                if (!confirm(`Cabut pengesahan Anda atas klaim kepemilikan ${member.name} pada Kavling ${unitCode}?`)) {
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
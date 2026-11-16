@extends('layouts.app')

@section('title', 'Panel Pengurus PPMT — PPMT Digital')
@section('subpage_title', 'Panel Pengurus')
@section('subpage_back_url', '/dashboard')

@section('content')
<div x-data="ppmtAdminPanel()" x-init="init()" @keydown.escape.window="editingAnnouncement = null; editingMinute = null" class="max-w-6xl mx-auto px-4 py-6 sm:py-8 space-y-6">

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

        <!-- Header Panel Pengurus -->
        <section class="bg-indigo-950 text-white rounded-2xl p-6 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="space-y-1.5">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-indigo-800/80 text-indigo-200 text-xs font-semibold">
                    <span>&#9733; Ruang Kerja Tata Kelola &amp; Administrasi PPMT</span>
                </div>
                <h1 class="text-2xl font-extrabold"
                    x-text="'Halo, ' + (user?.display_name || user?.name)"></h1>
                <p class="text-xs text-indigo-200">
                    Anda masuk sebagai warga dengan mandat jabatan aktif. Seluruh aktivitas kepengurusan tercatat dalam jejak audit lingkungan.
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

        <!-- ================================================================= -->
        <!-- MODUL 1: PAPAN PENGUMUMAN PAGUYUBAN (Sekretaris & Ketua)          -->
        <!-- ================================================================= -->
        <section x-show="user?.can_manage_announcements"
                 class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

            <!-- Form Terbitkan Pengumuman Baru (5 Kolom) -->
            <div class="lg:col-span-5 bg-white rounded-2xl p-5 sm:p-6 shadow-sm border border-slate-200 space-y-4">
                <div class="border-b border-slate-100 pb-3">
                    <h2 class="text-base font-bold text-slate-900">Terbitkan Pengumuman Paguyuban</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Pengumuman yang diterbitkan akan langsung tampil di <strong>Beranda Warga</strong>.</p>
                </div>

                <form @submit.prevent="createAnnouncement()" class="space-y-3.5">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Judul Pengumuman *</label>
                        <input type="text"
                               x-model="announcementForm.title"
                               required
                               maxlength="180"
                               placeholder="Contoh: Jadwal Kerja Bakti Drainase Blok A–D"
                               class="w-full rounded-xl border-slate-300 text-xs sm:text-sm focus:border-emerald-600 focus:ring-emerald-600">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Kategori / Label *</label>
                        <select x-model="announcementForm.category"
                                class="w-full rounded-xl border-slate-300 text-xs sm:text-sm focus:border-emerald-600 focus:ring-emerald-600">
                            <option value="info">Info Lingkungan</option>
                            <option value="penting">Penting / Mendesak</option>
                            <option value="kerja_bakti">Kegiatan Warga / Kerja Bakti</option>
                            <option value="musyawarah">Musyawarah Warga</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Isi Pengumuman Lengkap *</label>
                        <textarea x-model="announcementForm.body"
                                  required
                                  rows="4"
                                  placeholder="Tuliskan detail waktu, tempat, dan informasi penting bagi warga PPMT..."
                                  class="w-full rounded-xl border-slate-300 text-xs sm:text-sm focus:border-emerald-600 focus:ring-emerald-600"></textarea>
                    </div>

                    <label class="flex items-center gap-2.5 p-3 rounded-xl bg-amber-50/70 border border-amber-200 text-xs text-slate-700 cursor-pointer select-none">
                        <input type="checkbox" x-model="announcementForm.is_pinned" class="rounded text-emerald-700 focus:ring-emerald-600">
                        <span>Sematkan (<strong>Pin</strong>) di urutan paling atas papan pengumuman</span>
                    </label>

                    <button type="submit"
                            :disabled="savingAnnouncement"
                            class="w-full py-2.5 px-4 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white text-xs sm:text-sm font-bold shadow-sm transition disabled:opacity-50 cursor-pointer">
                        <span x-show="!savingAnnouncement">Terbitkan ke Papan Pengumuman</span>
                        <span x-show="savingAnnouncement">Menerbitkan...</span>
                    </button>
                </form>
            </div>

            <!-- Daftar Pengumuman Terbit & Arsip (7 Kolom) -->
            <div class="lg:col-span-7 bg-white rounded-2xl p-5 sm:p-6 shadow-sm border border-slate-200 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Kelola Papan Pengumuman Paguyuban</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Edit isi, sematkan pengumuman prioritas, atau arsipkan pengumuman lama.</p>
                    </div>
                    <span class="px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-700 border border-slate-200 text-xs font-bold shrink-0"
                          x-text="announcements.length + ' Total'"></span>
                </div>

                <div x-show="announcements.length === 0"
                     class="p-8 rounded-xl bg-slate-50 border border-dashed border-slate-300 text-center text-xs text-slate-500">
                    Belum ada pengumuman yang diterbitkan. Gunakan formulir di samping untuk membuat pengumuman pertama.
                </div>

                <div class="space-y-3">
                    <template x-for="item in announcements" :key="item.id">
                        <div :class="item.is_published ? 'bg-white border-slate-200' : 'bg-slate-50/80 border-slate-200 opacity-75'"
                             class="p-4 rounded-xl border space-y-2.5">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span x-show="item.is_pinned"
                                          class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-amber-100 text-amber-900 border border-amber-300">
                                        &#128204; Disematkan
                                    </span>
                                    <span :class="item.badgeClass"
                                          class="px-2 py-0.5 rounded-md text-[10px] font-bold border"
                                          x-text="item.category">
                                    </span>
                                    <span :class="item.is_published ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-200 text-slate-700 border-slate-300'"
                                          class="px-2 py-0.5 rounded-md text-[10px] font-bold border"
                                          x-text="item.is_published ? 'Tayang' : 'Diarsipkan'">
                                    </span>
                                </div>
                                <span class="text-[11px] text-slate-400" x-text="item.date"></span>
                            </div>

                            <div>
                                <div class="text-sm font-bold text-slate-900" x-text="item.title"></div>
                                <p class="text-xs text-slate-600 line-clamp-2 mt-1 leading-relaxed" x-text="item.excerpt"></p>
                            </div>

                            <div class="pt-2 border-t border-slate-100 flex flex-wrap items-center justify-between gap-2 text-xs">
                                <span class="text-[11px] text-slate-400" x-text="'Oleh: ' + item.author"></span>

                                <div class="flex flex-wrap items-center gap-1.5">
                                    <button type="button"
                                            @click="toggleAnnouncementPin(item)"
                                            class="px-2.5 py-1 rounded-lg border border-slate-200 hover:bg-amber-50 text-slate-700 font-semibold transition cursor-pointer"
                                            x-text="item.is_pinned ? 'Lepas Pin' : 'Sematkan'">
                                    </button>
                                    <button type="button"
                                            @click="openEditAnnouncement(item)"
                                            class="px-2.5 py-1 rounded-lg border border-slate-200 hover:bg-slate-100 text-slate-700 font-semibold transition cursor-pointer">
                                        Edit
                                    </button>
                                    <button type="button"
                                            @click="toggleAnnouncementPublish(item)"
                                            class="px-2.5 py-1 rounded-lg border border-slate-200 hover:bg-indigo-50 text-indigo-700 font-semibold transition cursor-pointer"
                                            x-text="item.is_published ? 'Arsipkan' : 'Tayangkan'">
                                    </button>
                                    <button type="button"
                                            @click="deleteAnnouncement(item)"
                                            class="px-2.5 py-1 rounded-lg text-rose-600 hover:bg-rose-50 font-semibold transition cursor-pointer">
                                        Hapus
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </section>

        <!-- ================================================================= -->
        <!-- MODUL 2: ARSIP NOTULENSI RAPAT & TATA TERTIB (Sekretaris & Ketua) -->
        <!-- ================================================================= -->
        <section x-show="user?.can_manage_announcements"
                 class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

            <!-- Form Catat Notulensi / Tata Tertib (5 Kolom) -->
            <div class="lg:col-span-5 bg-white rounded-2xl p-5 sm:p-6 shadow-sm border border-slate-200 space-y-4">
                <div class="border-b border-slate-100 pb-3">
                    <h2 class="text-base font-bold text-slate-900">Catat Notulensi Rapat &amp; Tata Tertib</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Dokumentasikan hasil musyawarah warga atau aturan lingkungan agar menjadi rujukan resmi.</p>
                </div>

                <form @submit.prevent="createMinute()" class="space-y-3.5">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Judul Agenda / Dokumen *</label>
                        <input type="text"
                               x-model="minuteForm.title"
                               required
                               maxlength="180"
                               placeholder="Contoh: Kesepakatan Musyawarah Keamanan & Iuran Bulanan"
                               class="w-full rounded-xl border-slate-300 text-xs sm:text-sm focus:border-emerald-600 focus:ring-emerald-600">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Rapat / Penetapan *</label>
                            <input type="date"
                                   x-model="minuteForm.meeting_date"
                                   required
                                   class="w-full rounded-xl border-slate-300 text-xs sm:text-sm focus:border-emerald-600 focus:ring-emerald-600">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Kategori Dokumen *</label>
                            <select x-model="minuteForm.category"
                                    class="w-full rounded-xl border-slate-300 text-xs sm:text-sm focus:border-emerald-600 focus:ring-emerald-600">
                                <option value="musyawarah_warga">Musyawarah Warga</option>
                                <option value="tata_tertib">Tata Tertib Lingkungan</option>
                                <option value="rapat_pengurus">Rapat Pengurus</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Lokasi / Forum Pertemuan (Opsional)</label>
                        <input type="text"
                               x-model="minuteForm.location"
                               maxlength="120"
                               placeholder="Contoh: Fasum Tengah / Pos Utama PPMT"
                               class="w-full rounded-xl border-slate-300 text-xs sm:text-sm focus:border-emerald-600 focus:ring-emerald-600">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Poin Kesepakatan &amp; Isi Lengkap *</label>
                        <textarea x-model="minuteForm.decisions"
                                  required
                                  rows="5"
                                  placeholder="Tuliskan poin-poin keputusan musyawarah, aturan yang disepakati, atau tindak lanjut pengurus..."
                                  class="w-full rounded-xl border-slate-300 text-xs sm:text-sm focus:border-emerald-600 focus:ring-emerald-600"></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Visibilitas Dokumen *</label>
                        <select x-model="minuteForm.visibility"
                                class="w-full rounded-xl border-slate-300 text-xs sm:text-sm focus:border-emerald-600 focus:ring-emerald-600">
                            <option value="public_residents">Terbuka untuk Semua Warga PPMT (Tampil di Komunitas)</option>
                            <option value="internal_pengurus">Internal Pengurus Saja</option>
                        </select>
                    </div>

                    <button type="submit"
                            :disabled="savingMinute"
                            class="w-full py-2.5 px-4 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs sm:text-sm font-bold shadow-sm transition disabled:opacity-50 cursor-pointer">
                        <span x-show="!savingMinute">Simpan ke Arsip Notulensi</span>
                        <span x-show="savingMinute">Menyimpan...</span>
                    </button>
                </form>
            </div>

            <!-- Daftar Arsip Notulensi Rapat & Tata Tertib (7 Kolom) -->
            <div class="lg:col-span-7 bg-white rounded-2xl p-5 sm:p-6 shadow-sm border border-slate-200 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Arsip Notulensi Rapat &amp; Tata Tertib</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Rekam jejak keputusan resmi paguyuban yang tersusun kronologis.</p>
                    </div>
                    <span class="px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-700 border border-slate-200 text-xs font-bold shrink-0"
                          x-text="meetingMinutes.length + ' Dokumen'"></span>
                </div>

                <div x-show="meetingMinutes.length === 0"
                     class="p-8 rounded-xl bg-slate-50 border border-dashed border-slate-300 text-center text-xs text-slate-500">
                    Belum ada catatan notulensi rapat atau tata tertib yang disimpan.
                </div>

                <div class="space-y-3">
                    <template x-for="m in meetingMinutes" :key="m.id">
                        <div class="p-4 rounded-xl border border-slate-200 bg-white space-y-2.5">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span :class="m.badgeClass"
                                          class="px-2 py-0.5 rounded-md text-[10px] font-bold border"
                                          x-text="m.category">
                                    </span>
                                    <span :class="m.visibility === 'public_residents' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-700 border-slate-300'"
                                          class="px-2 py-0.5 rounded-md text-[10px] font-bold border"
                                          x-text="m.visibility === 'public_residents' ? 'Terbuka Warga' : 'Internal Pengurus'">
                                    </span>
                                </div>
                                <span class="text-[11px] font-medium text-slate-500"
                                      x-text="m.meeting_date_formatted + (m.location ? ' • ' + m.location : '')">
                                </span>
                            </div>

                            <div>
                                <div class="text-sm font-bold text-slate-900" x-text="m.title"></div>
                                <p class="text-xs text-slate-600 line-clamp-2 mt-1 leading-relaxed" x-text="m.excerpt"></p>
                            </div>

                            <div class="pt-2 border-t border-slate-100 flex flex-wrap items-center justify-between gap-2 text-xs">
                                <span class="text-[11px] text-slate-400" x-text="'Dicatat oleh: ' + m.author"></span>

                                <div class="flex items-center gap-1.5">
                                    <button type="button"
                                            @click="openEditMinute(m)"
                                            class="px-2.5 py-1 rounded-lg border border-slate-200 hover:bg-slate-100 text-slate-700 font-semibold transition cursor-pointer">
                                        Edit
                                    </button>
                                    <button type="button"
                                            @click="deleteMinute(m)"
                                            class="px-2.5 py-1 rounded-lg text-rose-600 hover:bg-rose-50 font-semibold transition cursor-pointer">
                                        Hapus
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </section>

        <!-- ================================================================= -->
        <!-- MODUL 3: VERIFIKASI WILAYAH & TATA KELOLA JABATAN                 -->
        <!-- ================================================================= -->
        <div x-show="showVerificationPanel || user?.is_global_governor"
             class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

            <!-- Kolom Kiri (5 Kolom): Antrean Verifikasi Sosial & Assisted Onboarding -->
            <div class="lg:col-span-5 space-y-6">

                <!-- Panel 1: Antrean Verifikasi Sosial -->
                <section x-show="showVerificationPanel"
                         class="bg-white rounded-2xl p-5 shadow-sm border border-slate-200 space-y-3">
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
                                    <div class="text-slate-600" x-text="(item.phone || item.username || '-') + ' (' + item.sub_role.replace('_', ' ') + ')'"></div>
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
                        <div class="space-y-2.5 p-3 rounded-xl bg-white/90 border border-emerald-200">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">
                                    Nomor WhatsApp Tetangga <span class="text-slate-400 font-normal">(Opsional jika isi Username)</span>
                                </label>
                                <input type="tel" x-model="assistForm.phone" placeholder="0812xxxxxxxx"
                                       class="w-full rounded-xl border-slate-300 text-xs">
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">
                                    Username <span class="text-slate-400 font-normal">(Opsional jika isi No. WA)</span>
                                </label>
                                <input type="text"
                                       x-model="assistForm.username"
                                       @input="assistForm.username = (assistForm.username || '').toLowerCase().replace(/[^a-z0-9._]/g, '')"
                                       placeholder="Contoh: paksugeng.b12a"
                                       class="w-full rounded-xl border-slate-300 text-xs font-mono lowercase">
                            </div>
                            <p class="text-[11px] text-emerald-800">Isi minimal salah satu untuk dipakai masuk ke aplikasi.</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Kata Sandi Sementara (Min. 8 Karakter) *</label>
                            <input type="text" x-model="assistForm.password" required minlength="8" placeholder="Contoh: ppmt12345"
                                   class="w-full rounded-xl border-slate-300 text-xs font-mono">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap Tetangga *</label>
                            <input type="text" x-model="assistForm.name" required placeholder="Contoh: Ahmad Fauzi"
                                   class="w-full rounded-xl border-slate-300 text-xs">
                        </div>

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

                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Entitas</label>
                                <select x-model="assistForm.entity_type"
                                        @change="assistForm.sub_role = assistForm.entity_type === 'owner' ? 'primary_owner' : 'primary_resident'"
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

            <!-- Kolom Kanan (7 Kolom): Penyematan Jabatan & Hak Akses -->
            <section x-show="user?.is_global_governor"
                     class="lg:col-span-7 bg-white rounded-2xl p-6 shadow-sm border border-slate-200 space-y-5">
                <div class="border-b border-slate-100 pb-3">
                    <h2 class="text-lg font-bold text-slate-900">Penyematan Jabatan &amp; Hak Akses Warga</h2>
                    <p class="text-xs text-slate-500">
                        Wewenang penyematan jabatan mengikuti pemisahan peran tata kelola: <strong>Admin Sistem</strong> menetapkan infrastruktur &amp; pelantikan Ketua Paguyuban, sedangkan <strong>Ketua Paguyuban</strong> menyusun pengurus dan koordinator wilayah.
                    </p>
                </div>

                <form x-show="availableRoleOptions.length > 0"
                      @submit.prevent="assignGovernance()"
                      class="bg-indigo-50/60 p-4 rounded-xl border border-indigo-200 space-y-3">
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
                                <template x-for="opt in availableRoleOptions" :key="opt.code">
                                    <option :value="opt.code" x-text="opt.label"></option>
                                </template>
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
                            <button x-show="item.can_revoke"
                                    type="button"
                                    @click="revokeGovernance(item.id)"
                                    class="px-3 py-1.5 rounded-lg text-xs text-rose-600 hover:bg-rose-100 font-semibold transition cursor-pointer">
                                Cabut
                            </button>
                        </div>
                    </template>
                </div>
            </section>

        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- MODAL EDIT PENGUMUMAN PAGUYUBAN                                       -->
    <!-- ===================================================================== -->
    <div x-show="editingAnnouncement !== null"
         x-cloak
         class="fixed inset-0 z-50 !mt-0 flex items-center justify-center p-3 sm:p-4 bg-slate-900/50 backdrop-blur-[1px] overflow-y-auto">
        <div @click.away="editingAnnouncement = null"
             class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-[calc(100vw-1.5rem)] sm:max-w-lg max-h-[90dvh] flex flex-col overflow-hidden my-auto">
            <div class="px-5 sm:px-6 py-4 border-b border-slate-100 flex items-center justify-between gap-3 shrink-0">
                <h3 class="text-base font-bold text-slate-900">Edit Pengumuman Paguyuban</h3>
                <button type="button" @click="editingAnnouncement = null" class="text-slate-400 hover:text-slate-700 text-sm p-1 cursor-pointer">✕</button>
            </div>

            <form @submit.prevent="saveEditedAnnouncement()" class="p-5 sm:p-6 space-y-4 overflow-y-auto">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Judul Pengumuman *</label>
                    <input type="text" x-model="editAnnouncementForm.title" required maxlength="180"
                           class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Kategori / Label *</label>
                    <select x-model="editAnnouncementForm.category"
                            class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                        <option value="info">Info Lingkungan</option>
                        <option value="penting">Penting / Mendesak</option>
                        <option value="kerja_bakti">Kegiatan Warga / Kerja Bakti</option>
                        <option value="musyawarah">Musyawarah Warga</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Isi Pengumuman Lengkap *</label>
                    <textarea x-model="editAnnouncementForm.body" required rows="6"
                              class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600"></textarea>
                </div>

                <label class="flex items-center gap-2.5 p-3 rounded-xl bg-amber-50/70 border border-amber-200 text-xs text-slate-700 cursor-pointer select-none">
                    <input type="checkbox" x-model="editAnnouncementForm.is_pinned" class="rounded text-emerald-700">
                    <span>Sematkan (<strong>Pin</strong>) di urutan paling atas</span>
                </label>

                <div class="pt-2 flex items-center justify-end gap-2 border-t border-slate-100">
                    <button type="button" @click="editingAnnouncement = null"
                            class="px-4 py-2 rounded-xl border border-slate-300 text-slate-700 text-xs font-semibold cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" :disabled="savingAnnouncement"
                            class="px-5 py-2 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold shadow-sm cursor-pointer">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- MODAL EDIT NOTULENSI RAPAT & TATA TERTIB                              -->
    <!-- ===================================================================== -->
    <div x-show="editingMinute !== null"
         x-cloak
         class="fixed inset-0 z-50 !mt-0 flex items-center justify-center p-3 sm:p-4 bg-slate-900/50 backdrop-blur-[1px] overflow-y-auto">
        <div @click.away="editingMinute = null"
             class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-[calc(100vw-1.5rem)] sm:max-w-lg max-h-[90dvh] flex flex-col overflow-hidden my-auto">
            <div class="px-5 sm:px-6 py-4 border-b border-slate-100 flex items-center justify-between gap-3 shrink-0">
                <h3 class="text-base font-bold text-slate-900">Edit Notulensi Rapat &amp; Tata Tertib</h3>
                <button type="button" @click="editingMinute = null" class="text-slate-400 hover:text-slate-700 text-sm p-1 cursor-pointer">✕</button>
            </div>

            <form @submit.prevent="saveEditedMinute()" class="p-5 sm:p-6 space-y-4 overflow-y-auto">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Judul Agenda / Dokumen *</label>
                    <input type="text" x-model="editMinuteForm.title" required maxlength="180"
                           class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Tanggal Rapat / Penetapan *</label>
                        <input type="date" x-model="editMinuteForm.meeting_date" required
                               class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Kategori Dokumen *</label>
                        <select x-model="editMinuteForm.category"
                                class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                            <option value="musyawarah_warga">Musyawarah Warga</option>
                            <option value="tata_tertib">Tata Tertib Lingkungan</option>
                            <option value="rapat_pengurus">Rapat Pengurus</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Lokasi / Forum Pertemuan</label>
                    <input type="text" x-model="editMinuteForm.location" maxlength="120"
                           class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Poin Kesepakatan &amp; Isi Lengkap *</label>
                    <textarea x-model="editMinuteForm.decisions" required rows="6"
                              class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600"></textarea>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Visibilitas Dokumen *</label>
                    <select x-model="editMinuteForm.visibility"
                            class="w-full rounded-xl border-slate-300 text-sm focus:border-emerald-600 focus:ring-emerald-600">
                        <option value="public_residents">Terbuka untuk Semua Warga PPMT</option>
                        <option value="internal_pengurus">Internal Pengurus Saja</option>
                    </select>
                </div>

                <div class="pt-2 flex items-center justify-end gap-2 border-t border-slate-100">
                    <button type="button" @click="editingMinute = null"
                            class="px-4 py-2 rounded-xl border border-slate-300 text-slate-700 text-xs font-semibold cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" :disabled="savingMinute"
                            class="px-5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-xs font-bold shadow-sm cursor-pointer">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function ppmtAdminPanel() {
    const ALL_ROLE_OPTIONS = [
        { code: 'admin', label: 'Admin Sistem' },
        { code: 'operator_aplikasi', label: 'Operator Aplikasi' },
        { code: 'ketua_paguyuban', label: 'Ketua Paguyuban' },
        { code: 'sekretaris_paguyuban', label: 'Sekretaris Paguyuban' },
        { code: 'bendahara_paguyuban', label: 'Bendahara Paguyuban' },
        { code: 'koordinator_zona', label: 'Koordinator Zona' },
        { code: 'koordinator_blok', label: 'Koordinator Blok' },
        { code: 'keamanan_lingkungan', label: 'Keamanan Lingkungan' }
    ];

    const todayStr = new Date().toISOString().slice(0, 10);

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
        announcements: [],
        meetingMinutes: [],
        savingAnnouncement: false,
        editingAnnouncement: null,
        savingMinute: false,
        editingMinute: null,
        announcementForm: {
            title: '',
            category: 'info',
            body: '',
            is_pinned: false
        },
        editAnnouncementForm: {
            id: null,
            title: '',
            category: 'info',
            body: '',
            is_pinned: false
        },
        minuteForm: {
            title: '',
            meeting_date: todayStr,
            location: '',
            category: 'musyawarah_warga',
            decisions: '',
            visibility: 'public_residents'
        },
        editMinuteForm: {
            id: null,
            title: '',
            meeting_date: todayStr,
            location: '',
            category: 'musyawarah_warga',
            decisions: '',
            visibility: 'public_residents'
        },
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
            entity_type: 'owner',
            sub_role: 'primary_owner',
            password: ''
        },

        get showVerificationPanel() {
            if (!this.user) return false;
            if (this.user.is_global_governor) return true;
            const roles = (this.user.governance_roles || []).map(r => r.role_code);
            return roles.includes('koordinator_zona') || roles.includes('koordinator_blok') || this.pendingApprovals.length > 0;
        },

        get availableRoleOptions() {
            const allowed = Array.isArray(this.user?.assignable_governance_roles)
                ? this.user.assignable_governance_roles
                : [];
            return ALL_ROLE_OPTIONS.filter(opt => allowed.includes(opt.code));
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
            if (this.availableRoleOptions.length > 0) {
                this.govForm.role_code = this.availableRoleOptions[0].code;
                this.updateGovScopeDefault();
            }

            const tasks = [this.loadPendingApprovals()];
            if (this.user?.can_manage_announcements) {
                tasks.push(this.loadAnnouncements(), this.loadMeetingMinutes());
            }
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

        async loadAnnouncements() {
            const res = await fetch('/api/v1/announcements?all=1', { headers: this.headers() });
            if (res.ok) {
                const json = await res.json();
                this.announcements = json.data || [];
            }
        },

        async loadMeetingMinutes() {
            const res = await fetch('/api/v1/meeting-minutes', { headers: this.headers() });
            if (res.ok) {
                const json = await res.json();
                this.meetingMinutes = json.data || [];
            }
        },

        async createAnnouncement() {
            this.savingAnnouncement = true;
            try {
                const res = await fetch('/api/v1/announcements', {
                    method: 'POST',
                    headers: this.headers(),
                    body: JSON.stringify(this.announcementForm)
                });
                const json = await res.json();
                if (res.ok) {
                    this.toastType = 'success';
                    this.toastMessage = json.message;
                    this.announcementForm = { title: '', category: 'info', body: '', is_pinned: false };
                    await this.loadAnnouncements();
                } else {
                    this.toastType = 'error';
                    this.toastMessage = json.message || 'Gagal menerbitkan pengumuman.';
                }
            } finally {
                this.savingAnnouncement = false;
            }
        },

        openEditAnnouncement(item) {
            this.editAnnouncementForm = {
                id: item.id,
                title: item.title,
                category: item.category_code,
                body: item.body,
                is_pinned: Boolean(item.is_pinned)
            };
            this.editingAnnouncement = item;
        },

        async saveEditedAnnouncement() {
            this.savingAnnouncement = true;
            try {
                const res = await fetch(`/api/v1/announcements/${this.editAnnouncementForm.id}`, {
                    method: 'PATCH',
                    headers: this.headers(),
                    body: JSON.stringify({
                        title: this.editAnnouncementForm.title,
                        category: this.editAnnouncementForm.category,
                        body: this.editAnnouncementForm.body,
                        is_pinned: this.editAnnouncementForm.is_pinned
                    })
                });
                const json = await res.json();
                if (res.ok) {
                    this.editingAnnouncement = null;
                    this.toastType = 'success';
                    this.toastMessage = json.message;
                    await this.loadAnnouncements();
                } else {
                    this.toastType = 'error';
                    this.toastMessage = json.message || 'Gagal memperbarui pengumuman.';
                }
            } finally {
                this.savingAnnouncement = false;
            }
        },

        async toggleAnnouncementPin(item) {
            const res = await fetch(`/api/v1/announcements/${item.id}`, {
                method: 'PATCH',
                headers: this.headers(),
                body: JSON.stringify({ is_pinned: !item.is_pinned })
            });
            if (res.ok) {
                this.toastType = 'success';
                this.toastMessage = item.is_pinned ? 'Sematkan pengumuman dilepas.' : 'Pengumuman disematkan di urutan teratas.';
                await this.loadAnnouncements();
            }
        },

        async toggleAnnouncementPublish(item) {
            const res = await fetch(`/api/v1/announcements/${item.id}`, {
                method: 'PATCH',
                headers: this.headers(),
                body: JSON.stringify({ is_published: !item.is_published })
            });
            if (res.ok) {
                this.toastType = 'success';
                this.toastMessage = item.is_published ? 'Pengumuman diarsipkan dari beranda warga.' : 'Pengumuman kembali ditayangkan.';
                await this.loadAnnouncements();
            }
        },

        async deleteAnnouncement(item) {
            const res = await fetch(`/api/v1/announcements/${item.id}`, {
                method: 'DELETE',
                headers: this.headers()
            });
            const json = await res.json();
            if (res.ok) {
                this.toastType = 'success';
                this.toastMessage = json.message;
                await this.loadAnnouncements();
            }
        },

        async createMinute() {
            this.savingMinute = true;
            try {
                const res = await fetch('/api/v1/meeting-minutes', {
                    method: 'POST',
                    headers: this.headers(),
                    body: JSON.stringify(this.minuteForm)
                });
                const json = await res.json();
                if (res.ok) {
                    this.toastType = 'success';
                    this.toastMessage = json.message;
                    this.minuteForm = {
                        title: '',
                        meeting_date: todayStr,
                        location: '',
                        category: 'musyawarah_warga',
                        decisions: '',
                        visibility: 'public_residents'
                    };
                    await this.loadMeetingMinutes();
                } else {
                    this.toastType = 'error';
                    this.toastMessage = json.message || 'Gagal menyimpan notulensi.';
                }
            } finally {
                this.savingMinute = false;
            }
        },

        openEditMinute(m) {
            this.editMinuteForm = {
                id: m.id,
                title: m.title,
                meeting_date: m.meeting_date,
                location: m.location || '',
                category: m.category_code,
                decisions: m.decisions,
                visibility: m.visibility
            };
            this.editingMinute = m;
        },

        async saveEditedMinute() {
            this.savingMinute = true;
            try {
                const res = await fetch(`/api/v1/meeting-minutes/${this.editMinuteForm.id}`, {
                    method: 'PATCH',
                    headers: this.headers(),
                    body: JSON.stringify({
                        title: this.editMinuteForm.title,
                        meeting_date: this.editMinuteForm.meeting_date,
                        location: this.editMinuteForm.location || null,
                        category: this.editMinuteForm.category,
                        decisions: this.editMinuteForm.decisions,
                        visibility: this.editMinuteForm.visibility
                    })
                });
                const json = await res.json();
                if (res.ok) {
                    this.editingMinute = null;
                    this.toastType = 'success';
                    this.toastMessage = json.message;
                    await this.loadMeetingMinutes();
                } else {
                    this.toastType = 'error';
                    this.toastMessage = json.message || 'Gagal memperbarui notulensi.';
                }
            } finally {
                this.savingMinute = false;
            }
        },

        async deleteMinute(m) {
            const res = await fetch(`/api/v1/meeting-minutes/${m.id}`, {
                method: 'DELETE',
                headers: this.headers()
            });
            const json = await res.json();
            if (res.ok) {
                this.toastType = 'success';
                this.toastMessage = json.message;
                await this.loadMeetingMinutes();
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
            if (!this.assistForm.phone && !this.assistForm.username) {
                this.toastType = 'error';
                this.toastMessage = 'Isi minimal Nomor WhatsApp atau Username untuk warga tersebut.';
                return;
            }

            const payload = {
                unit_code: this.assistForm.unit_code,
                name: this.assistForm.name,
                phone: this.assistForm.phone || null,
                username: this.assistForm.username || null,
                entity_type: this.assistForm.entity_type,
                sub_role: this.assistForm.sub_role,
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
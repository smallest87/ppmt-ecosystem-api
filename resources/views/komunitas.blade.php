@extends('layouts.app')

@section('title', 'Komunitas, Aspirasi & Peta Kavling — PPMT Digital')

@section('content')
<div x-data="ppmtCommunity()" x-init="init()" @keydown.escape.window="selectedMinute = null; signModalAspiration = null; reviewModalAspiration = null" class="max-w-6xl mx-auto px-4 py-8 space-y-6">

    <!-- Tab Switcher Utama (6 Tab Internal Terproteksi) -->
    <div class="flex flex-col xl:flex-row xl:items-center justify-between gap-4 bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
        <div>
            <h1 class="text-xl font-extrabold text-slate-900">Komunitas &amp; Peta Lingkungan PPMT</h1>
            <p class="text-xs text-slate-500">Eksklusif bagi warga terverifikasi &bull; Aspirasi kolektif, transparansi kas, arsip musyawarah &amp; ekonomi tetangga.</p>
        </div>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 bg-slate-100 p-1 rounded-xl text-xs font-bold shrink-0 gap-1">
            <button type="button" @click="setTab('umkm')"
                    :class="tab === 'umkm' ? 'bg-emerald-700 text-white shadow' : 'text-slate-600 hover:text-slate-900'"
                    class="px-3 py-2 rounded-lg transition cursor-pointer">
                Pasar Tetangga
            </button>
            <button type="button" @click="setTab('aspirasi')"
                    :class="tab === 'aspirasi' ? 'bg-emerald-700 text-white shadow' : 'text-slate-600 hover:text-slate-900'"
                    class="px-3 py-2 rounded-lg transition cursor-pointer flex items-center justify-center gap-1.5">
                <span>Aspirasi</span>
                <span x-show="openAspirationsCount > 0"
                      :class="tab === 'aspirasi' ? 'bg-amber-400 text-slate-950' : 'bg-emerald-100 text-emerald-800'"
                      class="px-1.5 py-0.2 rounded-full text-[10px] font-extrabold"
                      x-text="openAspirationsCount"></span>
            </button>
            <button type="button" @click="setTab('kas')"
                    :class="tab === 'kas' ? 'bg-emerald-700 text-white shadow' : 'text-slate-600 hover:text-slate-900'"
                    class="px-3 py-2 rounded-lg transition cursor-pointer">
                Kas &amp; Iuran
            </button>
            <button type="button" @click="setTab('directory')"
                    :class="tab === 'directory' ? 'bg-emerald-700 text-white shadow' : 'text-slate-600 hover:text-slate-900'"
                    class="px-3 py-2 rounded-lg transition cursor-pointer">
                Direktori Warga
            </button>
            <button type="button" @click="setTab('minutes')"
                    :class="tab === 'minutes' ? 'bg-emerald-700 text-white shadow' : 'text-slate-600 hover:text-slate-900'"
                    class="px-3 py-2 rounded-lg transition cursor-pointer">
                Notulensi &amp; Tatib
            </button>
            <a href="/peta-kavling"
               class="px-3 py-2 rounded-lg transition text-slate-600 hover:text-slate-900 text-center">
                Peta 91 Kavling ↗
            </a>
        </div>
    </div>

    <!-- Notifikasi -->
    <div x-show="toastMessage"
         x-cloak
         :class="toastType === 'error' ? 'bg-rose-50 text-rose-800 border-rose-200' : 'bg-emerald-50 text-emerald-800 border-emerald-200'"
         class="p-4 rounded-xl border text-xs font-medium flex justify-between items-center">
        <span x-text="toastMessage"></span>
        <button @click="toastMessage = ''" class="cursor-pointer">✕</button>
    </div>

    <!-- BAGIAN 1: PASAR TETANGGA (UMKM) -->
    <div x-show="tab === 'umkm'" class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        <!-- Etalase Lapak Warga (8 Kolom) -->
        <div class="lg:col-span-8 space-y-4">
            <div class="flex flex-wrap gap-2">
                <select x-model="filterCategory" @change="loadStores()" class="rounded-xl border-slate-300 text-xs">
                    <option value="">Semua Kategori Usaha</option>
                    <option value="kuliner">Kuliner &amp; Katering</option>
                    <option value="sembako">Sembako &amp; Air/Gas</option>
                    <option value="jasa">Jasa &amp; Perbaikan</option>
                    <option value="pinjam_alat">Pinjam / Sewa Alat</option>
                    <option value="lainnya">Lainnya</option>
                </select>
                <select x-model="filterStatus" @change="loadStores()" class="rounded-xl border-slate-300 text-xs">
                    <option value="">Semua Status Lapak</option>
                    <option value="open">Buka Sekarang</option>
                    <option value="open_po">Open PO</option>
                    <option value="closed">Tutup</option>
                </select>
            </div>

            <div x-show="stores.length === 0" class="bg-white rounded-2xl p-10 text-center text-sm text-slate-400 border border-slate-200">
                Belum ada lapak usaha warga yang ditampilkan untuk filter ini.
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <template x-for="store in stores" :key="store.id">
                    <div class="bg-white rounded-2xl p-5 border border-slate-200 shadow-sm flex flex-col justify-between space-y-4">
                        <div class="space-y-2">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded"
                                          x-text="store.category.replace('_', ' ')"></span>
                                    <h3 class="font-bold text-base text-slate-900 mt-1" x-text="store.store_name"></h3>
                                </div>
                                <span :class="{
                                        'bg-emerald-100 text-emerald-800': store.status === 'open',
                                        'bg-amber-100 text-amber-800': store.status === 'open_po',
                                        'bg-slate-200 text-slate-600': store.status === 'closed'
                                      }"
                                      class="px-2.5 py-1 rounded-full text-[11px] font-bold shrink-0"
                                      x-text="store.status === 'open' ? '● Buka' : (store.status === 'open_po' ? '◐ Open PO' : '○ Tutup')">
                                </span>
                            </div>

                            <p class="text-xs text-slate-600 leading-relaxed" x-text="store.description || 'Lapak usaha warga internal PPMT.'"></p>
                            <div class="text-[11px] text-slate-500">
                                <a :href="'/peta-kavling?unit=' + encodeURIComponent(store.unit?.code || '')"
                                   class="font-bold text-emerald-700 hover:underline"
                                   x-text="'Kavling ' + store.unit?.code"></a> &bull; <span x-text="store.owner?.name"></span>
                            </div>
                        </div>

                        <div class="pt-3 border-t border-slate-100">
                            <template x-if="store.owner_user_id === me?.id">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-[11px] font-semibold text-slate-500">Ubah Status:</span>
                                    <div class="flex gap-1">
                                        <button type="button" @click="changeStoreStatus(store.id, 'open')" class="px-2 py-1 rounded text-[11px] font-bold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 cursor-pointer">Buka</button>
                                        <button type="button" @click="changeStoreStatus(store.id, 'open_po')" class="px-2 py-1 rounded text-[11px] font-bold bg-amber-50 text-amber-700 hover:bg-amber-100 cursor-pointer">PO</button>
                                        <button type="button" @click="changeStoreStatus(store.id, 'closed')" class="px-2 py-1 rounded text-[11px] font-bold bg-slate-100 text-slate-700 hover:bg-slate-200 cursor-pointer">Tutup</button>
                                    </div>
                                </div>
                            </template>

                            <template x-if="store.owner_user_id !== me?.id">
                                <a :href="buildWaUrl(store)" target="_blank"
                                   class="w-full py-2 px-3 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold flex items-center justify-center gap-1.5 transition">
                                    <span>Pesan via WhatsApp Tetangga</span>
                                </a>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </div>

        <!-- Form Buka Lapak Baru (4 Kolom) -->
        <div class="lg:col-span-4 bg-white rounded-2xl p-5 border border-slate-200 shadow-sm space-y-4">
            <div class="border-b border-slate-100 pb-3">
                <h2 class="text-base font-bold text-slate-900">Buka Lapak Usaha Warga</h2>
                <p class="text-xs text-slate-500">Promosikan usaha rumahan, stok galon/gas, atau jasa ke seluruh tetangga PPMT.</p>
            </div>

            <form @submit.prevent="createStore()" class="space-y-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Kavling Lokasi Usaha *</label>
                    <select x-model="storeForm.unit_code" required class="w-full rounded-xl border-slate-300 text-xs">
                        <template x-for="m in (me?.memberships || [])" :key="m.id">
                            <option :value="m.unit.code" x-text="'Kavling ' + m.unit.code"></option>
                        </template>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lapak / Usaha *</label>
                    <input type="text" x-model="storeForm.store_name" required placeholder="Contoh: Warung Sarapan Bu Siti"
                           class="w-full rounded-xl border-slate-300 text-xs">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Kategori *</label>
                    <select x-model="storeForm.category" class="w-full rounded-xl border-slate-300 text-xs">
                        <option value="kuliner">Kuliner &amp; Katering</option>
                        <option value="sembako">Sembako &amp; Air/Gas</option>
                        <option value="jasa">Jasa &amp; Perbaikan</option>
                        <option value="pinjam_alat">Pinjam / Sewa Alat</option>
                        <option value="lainnya">Lainnya</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Deskripsi Singkat &amp; Daftar Menu/Jasa</label>
                    <textarea x-model="storeForm.description" rows="3" placeholder="Contoh: Sedia nasi pecel pagi, galon isi ulang, dan gas LPG 3kg siap antar blok."
                              class="w-full rounded-xl border-slate-300 text-xs"></textarea>
                </div>

                <button type="submit"
                        class="w-full py-2.5 px-4 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold shadow-sm transition cursor-pointer">
                    Publikasikan ke Pasar Tetangga
                </button>
            </form>
        </div>
    </div>

    <!-- BAGIAN 2: ASPIRASI & PETISI KOLEKTIF WARGA -->
    <div x-show="tab === 'aspirasi'" x-cloak class="space-y-6">

        <!-- Banner Penjelasan Prinsip 1 Kavling = 1 Suara -->
        <div class="bg-gradient-to-r from-emerald-950 via-emerald-900 to-teal-900 text-white rounded-2xl p-5 sm:p-6 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="space-y-1.5 max-w-2xl">
                <div class="inline-flex items-center gap-2 px-2.5 py-0.5 rounded-full bg-amber-400/20 border border-amber-300/30 text-amber-200 text-[11px] font-bold">
                    <span>Kanal Konsolidasi Warga &bull; 1 Kavling = 1 Dukungan Sah</span>
                </div>
                <h2 class="text-lg sm:text-xl font-extrabold">Aspirasi &amp; Petisi Kolektif Lingkungan PPMT</h2>
                <p class="text-xs text-emerald-100/90 leading-relaxed">
                    Himpun keluhan fasilitas umum, drainase, maupun kualitas garapan unit secara terukur. Dukungan dicatat per nomor kavling resmi sebagai dasar audiensi satu pintu bersama pengembang maupun pemangku wilayah.
                </p>
            </div>
            <div class="grid grid-cols-2 gap-3 shrink-0">
                <div class="bg-white/10 border border-white/15 rounded-xl px-4 py-3 text-center">
                    <div class="text-xl font-extrabold text-amber-300" x-text="aspirations.filter(a => ['open', 'submitted'].includes(a.status)).length"></div>
                    <div class="text-[11px] text-emerald-100">Isu Dikawal</div>
                </div>
                <div class="bg-white/10 border border-white/15 rounded-xl px-4 py-3 text-center">
                    <div class="text-xl font-extrabold text-emerald-300" x-text="aspirations.filter(a => a.is_signed_by_me).length"></div>
                    <div class="text-[11px] text-emerald-100">Didukung Kavlingmu</div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

            <!-- Daftar Petisi & Aspirasi (8 Kolom) -->
            <div class="lg:col-span-8 space-y-4">
                <!-- Filter Bar -->
                <div class="flex flex-wrap items-center justify-between gap-2 bg-white p-3.5 rounded-2xl border border-slate-200 shadow-sm">
                    <div class="flex flex-wrap gap-2">
                        <select x-model="filterAspirationStatus" class="rounded-xl border-slate-300 text-xs">
                            <option value="">Semua Status</option>
                            <option value="open">Sedang Digalang</option>
                            <option value="submitted">Diajukan ke Pengembang</option>
                            <option value="resolved">Selesai Ditangani</option>
                            <option value="pending" x-show="me?.has_admin_access">Menunggu Kurasi</option>
                        </select>
                        <select x-model="filterAspirationCategory" class="rounded-xl border-slate-300 text-xs">
                            <option value="">Semua Kategori Isu</option>
                            <option value="Infrastruktur & Drainase">Infrastruktur &amp; Drainase</option>
                            <option value="Kualitas Bangunan">Kualitas Bangunan</option>
                            <option value="Lingkungan & Kebersihan">Lingkungan &amp; Kebersihan</option>
                            <option value="Keamanan & Tata Tertib">Keamanan &amp; Tata Tertib</option>
                        </select>
                    </div>
                    <span class="text-[11px] text-slate-500 font-medium"
                          x-text="'Menampilkan ' + filteredAspirations.length + ' aspirasi'"></span>
                </div>

                <div x-show="filteredAspirations.length === 0"
                     class="bg-white rounded-2xl p-10 text-center text-xs text-slate-400 border border-slate-200">
                    Belum ada daftar aspirasi warga pada filter yang dipilih.
                </div>

                <div class="space-y-4">
                    <template x-for="asp in filteredAspirations" :key="asp.id">
                        <article class="bg-white rounded-2xl p-5 sm:p-6 border border-slate-200 shadow-sm space-y-4">

                            <!-- Header Kartu: Kategori, Status, Tanggal -->
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <span class="px-2.5 py-0.5 rounded-md text-[10px] font-bold uppercase tracking-wider bg-slate-100 text-slate-700 border border-slate-200"
                                          x-text="asp.category"></span>
                                    <span :class="{
                                            'bg-emerald-100 text-emerald-900 border-emerald-200': asp.status === 'open',
                                            'bg-indigo-100 text-indigo-900 border-indigo-200': asp.status === 'submitted',
                                            'bg-teal-100 text-teal-900 border-teal-200': asp.status === 'resolved',
                                            'bg-amber-100 text-amber-900 border-amber-300': asp.status === 'pending',
                                            'bg-rose-100 text-rose-800 border-rose-200': asp.status === 'rejected'
                                          }"
                                          class="px-2.5 py-0.5 rounded-full text-[11px] font-bold border"
                                          x-text="aspirationStatusLabel(asp.status)">
                                    </span>
                                </div>
                                <span class="text-[11px] text-slate-400"
                                      x-text="'Diajukan ' + asp.created_at + (asp.author_unit ? ' • Kav. ' + asp.author_unit : '')"></span>
                            </div>

                            <!-- Judul, Lokasi & Deskripsi -->
                            <div class="space-y-2">
                                <h3 class="text-base font-extrabold text-slate-900 leading-snug" x-text="asp.title"></h3>
                                <div x-show="asp.location_scope" class="text-xs font-semibold text-emerald-800">
                                    Lokasi / Cakupan: <span class="font-normal text-slate-700" x-text="asp.location_scope"></span>
                                </div>
                                <p class="text-xs text-slate-600 leading-relaxed whitespace-pre-line" x-text="asp.description"></p>
                            </div>

                            <!-- Progress Bar Dukungan Kavling -->
                            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/80 space-y-2.5">
                                <div class="flex items-center justify-between text-xs">
                                    <span class="font-bold text-slate-800">
                                        Dukungan Kolektif:
                                        <span class="text-emerald-700 text-sm font-extrabold" x-text="asp.signatures_count"></span>
                                        <span class="text-slate-500 font-normal" x-text="' / ' + asp.target_signatures + ' kavling target'"></span>
                                    </span>
                                    <span class="text-[11px] font-bold text-slate-600"
                                          x-text="Math.min(100, Math.round((asp.signatures_count / Math.max(1, asp.target_signatures)) * 100)) + '%'"></span>
                                </div>

                                <div class="w-full h-2.5 rounded-full bg-slate-200 overflow-hidden">
                                    <div class="h-full rounded-full bg-emerald-600 transition-all duration-300"
                                         :style="'width: ' + Math.min(100, Math.round((asp.signatures_count / Math.max(1, asp.target_signatures)) * 100)) + '%'">
                                    </div>
                                </div>

                                <!-- Daftar Kode Kavling Pendukung -->
                                <div x-show="asp.signed_unit_codes.length > 0" class="pt-1 flex flex-wrap items-center gap-1">
                                    <span class="text-[10px] font-semibold text-slate-500 mr-1">Kavling pendukung:</span>
                                    <template x-for="code in asp.signed_unit_codes" :key="code">
                                        <span class="px-1.5 py-0.5 rounded bg-white border border-slate-200 text-[10px] font-mono font-bold text-slate-700"
                                              x-text="code"></span>
                                    </template>
                                </div>
                            </div>

                            <!-- Catatan Tindak Lanjut / Hasil Audiensi Pengembang -->
                            <div x-show="asp.developer_response"
                                 class="p-3.5 rounded-xl bg-indigo-50/80 border border-indigo-200 text-xs space-y-1">
                                <div class="font-bold text-indigo-950">Catatan Tindak Lanjut / Audiensi Pengembang:</div>
                                <p class="text-indigo-900 leading-relaxed whitespace-pre-line" x-text="asp.developer_response"></p>
                            </div>

                            <!-- Kesaksian Singkat Warga Terdampak -->
                            <div x-show="asp.testimonials.length > 0" class="space-y-1.5">
                                <div class="text-[11px] font-bold text-slate-500">Kesaksian Lapangan Kavling (<span x-text="asp.testimonials.length"></span>):</div>
                                <div class="space-y-1.5 max-h-36 overflow-y-auto pr-1">
                                    <template x-for="(t, idx) in asp.testimonials" :key="idx">
                                        <div class="px-3 py-2 rounded-lg bg-amber-50/60 border border-amber-200/70 text-[11px] text-slate-700">
                                            <span class="font-mono font-bold text-amber-900" x-text="'[' + t.unit_code + '] '"></span>
                                            <span x-text="t.note"></span>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <!-- Tombol Aksi Warga & Kontrol Kurasi Pengurus -->
                            <div class="pt-3 border-t border-slate-100 flex flex-wrap items-center justify-between gap-2">
                                <!-- Aksi Warga -->
                                <div class="flex flex-wrap items-center gap-2">
                                    <template x-if="['open', 'submitted'].includes(asp.status) && !asp.is_signed_by_me">
                                        <button type="button"
                                                @click="openSignModal(asp)"
                                                class="px-4 py-2 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold shadow-sm transition cursor-pointer">
                                            &#9997; Dukung Atas Nama Kavling
                                        </button>
                                    </template>

                                    <template x-if="asp.is_signed_by_me">
                                        <div class="flex items-center gap-2">
                                            <span class="inline-flex items-center gap-1 px-3 py-1.5 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-200 text-xs font-bold">
                                                &#10003; Kavling Anda Mendukung
                                            </span>
                                            <button type="button"
                                                    @click="openSignModal(asp)"
                                                    class="text-[11px] font-semibold text-slate-500 hover:text-emerald-700 underline cursor-pointer">
                                                + Tambah Kesaksian
                                            </button>
                                        </div>
                                    </template>
                                </div>

                                <!-- Kontrol Pengurus (Kurasi, Update Progres & Salin Rekap Audiensi) -->
                                <div x-show="me?.has_admin_access" class="flex flex-wrap items-center gap-1.5">
                                    <button type="button"
                                            @click="copyAspirationBrief(asp)"
                                            class="px-2.5 py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-[11px] font-bold transition cursor-pointer"
                                            title="Salin teks ringkasan untuk surat atau WhatsApp audiensi">
                                        Salin Rekap
                                    </button>
                                    <button type="button"
                                            @click="openReviewModal(asp)"
                                            class="px-2.5 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-800 border border-indigo-200 text-[11px] font-bold transition cursor-pointer">
                                        Kelola Status
                                    </button>
                                </div>
                            </div>
                        </article>
                    </template>
                </div>
            </div>

            <!-- Form Ajukan Aspirasi Baru (4 Kolom) -->
            <div class="lg:col-span-4 bg-white rounded-2xl p-5 border border-slate-200 shadow-sm space-y-4">
                <div class="border-b border-slate-100 pb-3">
                    <h3 class="text-base font-bold text-slate-900">Ajukan Aspirasi Lingkungan</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Laporkan masalah fasum, drainase, atau garapan bangunan yang berdampak kolektif.</p>
                </div>

                <form @submit.prevent="createAspiration()" class="space-y-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Kavling Pengusul *</label>
                        <select x-model="aspirationForm.unit_id" required class="w-full rounded-xl border-slate-300 text-xs">
                            <template x-for="m in (me?.memberships || [])" :key="m.id">
                                <option :value="m.unit.id" x-text="'Kavling ' + m.unit.code"></option>
                            </template>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Kategori Isu *</label>
                        <select x-model="aspirationForm.category" required class="w-full rounded-xl border-slate-300 text-xs">
                            <option value="Infrastruktur & Drainase">Infrastruktur &amp; Drainase</option>
                            <option value="Kualitas Bangunan">Kualitas Bangunan</option>
                            <option value="Lingkungan & Kebersihan">Lingkungan &amp; Kebersihan</option>
                            <option value="Keamanan & Tata Tertib">Keamanan &amp; Tata Tertib</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Judul Aspirasi / Usulan *</label>
                        <input type="text" x-model="aspirationForm.title" required minlength="8"
                               placeholder="Contoh: Perbaikan Saluran Air Blok C yang Meluap"
                               class="w-full rounded-xl border-slate-300 text-xs">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Lokasi / Blok Terdampak</label>
                        <input type="text" x-model="aspirationForm.location_scope"
                               placeholder="Contoh: Jalan Sisi Barat / Blok C-10 s/d C-20"
                               class="w-full rounded-xl border-slate-300 text-xs">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Uraian Masalah &amp; Harapan Solusi *</label>
                        <textarea x-model="aspirationForm.description" rows="4" required minlength="20"
                                  placeholder="Jelaskan kronologi kondisi lapangan dan solusi yang diharapkan dari pengembang atau paguyuban..."
                                  class="w-full rounded-xl border-slate-300 text-xs"></textarea>
                    </div>

                    <button type="submit" :disabled="submittingAspiration"
                            class="w-full py-2.5 px-4 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold shadow-sm transition disabled:opacity-50 cursor-pointer">
                        <span x-show="!submittingAspiration">Kirim Usulan Aspirasi</span>
                        <span x-show="submittingAspiration">Mengirim...</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- BAGIAN 3: TRANSPARANSI KAS PAGUYUBAN & KARTU IURAN SAMPAH -->
    <div x-show="tab === 'kas'" class="space-y-6">

        <!-- Header Saldo & Pemilih Periode -->
        <div class="bg-emerald-950 text-white rounded-2xl p-5 sm:p-6 shadow-sm space-y-4">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-emerald-800/80 pb-3">
                <div>
                    <div class="text-[11px] font-bold uppercase tracking-wider text-emerald-300">Laporan Transparansi Keuangan Paguyuban</div>
                    <h2 class="text-lg font-extrabold">Kas Lingkungan &amp; Iuran Sampah (Rp 20.000 / Tgl 1)</h2>
                </div>
                <div class="flex items-center gap-2">
                    <label class="text-xs text-emerald-200 font-semibold">Bulan Laporan:</label>
                    <input type="month"
                           x-model="treasuryMonth"
                           @change="loadTreasuryReport()"
                           class="rounded-xl border-emerald-700 bg-emerald-900 text-white text-xs font-bold focus:border-amber-400 focus:ring-amber-400">
                </div>
            </div>

            <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                <div class="bg-emerald-900/70 rounded-xl p-4 border border-emerald-800">
                    <div class="text-[11px] text-emerald-300 font-semibold">Saldo Kas Paguyuban</div>
                    <div class="text-lg sm:text-xl font-extrabold text-amber-300 mt-1"
                         x-text="formatRupiah(treasuryReport?.summary?.current_balance ?? 0)"></div>
                    <div class="text-[11px] text-emerald-200/80 mt-0.5">Total saldo bersih kas</div>
                </div>

                <div class="bg-emerald-900/70 rounded-xl p-4 border border-emerald-800">
                    <div class="text-[11px] text-emerald-300 font-semibold" x-text="'Pemasukan (' + (treasuryReport?.period_label || '') + ')'"></div>
                    <div class="text-lg sm:text-xl font-extrabold text-white mt-1"
                         x-text="formatRupiah(treasuryReport?.summary?.month_income ?? 0)"></div>
                    <div class="text-[11px] text-emerald-200/80 mt-0.5">Iuran &amp; pemasukan kas</div>
                </div>

                <div class="bg-emerald-900/70 rounded-xl p-4 border border-emerald-800">
                    <div class="text-[11px] text-emerald-300 font-semibold" x-text="'Pengeluaran (' + (treasuryReport?.period_label || '') + ')'"></div>
                    <div class="text-lg sm:text-xl font-extrabold text-rose-300 mt-1"
                         x-text="formatRupiah(treasuryReport?.summary?.month_expense ?? 0)"></div>
                    <div class="text-[11px] text-emerald-200/80 mt-0.5">Operasional lingkungan</div>
                </div>

                <div class="bg-emerald-900/70 rounded-xl p-4 border border-emerald-800">
                    <div class="text-[11px] text-emerald-300 font-semibold">Partisipasi Iuran Sampah</div>
                    <div class="text-lg sm:text-xl font-extrabold text-white mt-1">
                        <span x-text="treasuryReport?.summary?.paid_units ?? 0"></span>
                        <span class="text-xs font-normal text-emerald-300" x-text="'/ ' + (treasuryReport?.summary?.billable_units ?? 0) + ' rumah'"></span>
                    </div>
                    <div class="text-[11px] text-emerald-200/80 mt-0.5">Sudah melunasi bulan ini</div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

            <!-- Kartu Iuran Sampah Kavling Pribadi (5 Kolom) -->
            <div class="lg:col-span-5 bg-white rounded-2xl p-5 sm:p-6 border border-slate-200 shadow-sm space-y-4">
                <div class="border-b border-slate-100 pb-3">
                    <h3 class="text-base font-bold text-slate-900">Kartu Iuran Sampah Kavling Anda</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Tagihan rutin <strong>Rp 20.000</strong> jatuh tempo setiap <strong>tanggal 1</strong>.</p>
                </div>

                <template x-for="uDues in (treasuryReport?.my_units_dues || [])" :key="uDues.unit_id">
                    <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/60 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-extrabold text-slate-900"
                                  x-text="'Kavling ' + uDues.unit_code + ' (' + uDues.zone + ')'"></span>
                            <span :class="{
                                    'bg-emerald-100 text-emerald-800 border-emerald-200': uDues.current?.status === 'paid',
                                    'bg-amber-100 text-amber-900 border-amber-300': uDues.current?.status === 'unpaid',
                                    'bg-slate-200 text-slate-700 border-slate-300': uDues.current?.status === 'exempt'
                                  }"
                                  class="px-2.5 py-0.5 rounded-full text-[11px] font-bold border"
                                  x-text="uDues.current?.status === 'paid' ? 'Lunas' : (uDues.current?.status === 'exempt' ? 'Dibebaskan' : 'Belum Terbayar')">
                            </span>
                        </div>

                        <div class="divide-y divide-slate-200/80 bg-white rounded-xl border border-slate-200 overflow-hidden">
                            <template x-for="h in uDues.history" :key="h.period_month">
                                <div class="px-3.5 py-2.5 flex items-center justify-between gap-2 text-xs">
                                    <div>
                                        <div class="font-bold text-slate-800" x-text="h.period_label"></div>
                                        <div class="text-[11px] text-slate-400" x-text="'Jatuh tempo: ' + h.due_date"></div>
                                    </div>
                                    <div class="text-right">
                                        <span :class="{
                                                'text-emerald-700': h.status === 'paid',
                                                'text-amber-700': h.status === 'unpaid',
                                                'text-slate-500': h.status === 'exempt'
                                              }"
                                              class="font-bold block"
                                              x-text="h.status === 'paid' ? '✓ Lunas (' + formatRupiah(h.amount) + ')' : (h.status === 'exempt' ? 'Dibebaskan' : 'Tagihan Rp 20.000')">
                                        </span>
                                        <span x-show="h.paid_at" class="text-[10px] text-slate-400"
                                              x-text="(h.payment_method === 'transfer' ? 'Transfer' : 'Tunai') + ' • ' + h.paid_at"></span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>

            <!-- Rekap Pos & Mutasi Kas Bulanan (7 Kolom) -->
            <div class="lg:col-span-7 bg-white rounded-2xl p-5 sm:p-6 border border-slate-200 shadow-sm space-y-5">
                <div class="border-b border-slate-100 pb-3">
                    <h3 class="text-base font-bold text-slate-900"
                        x-text="'Rekapitulasi & Mutasi Kas — ' + (treasuryReport?.period_label || '')"></h3>
                    <p class="text-xs text-slate-500 mt-0.5">Rincian pemasukan dan penggunaan dana kas paguyuban per kategori.</p>
                </div>

                <!-- Rekap Pengeluaran per Pos -->
                <div x-show="(treasuryReport?.expense_by_category?.length || 0) > 0" class="space-y-2">
                    <div class="text-xs font-bold uppercase tracking-wider text-slate-500">Penggunaan Dana per Pos</div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <template x-for="exp in (treasuryReport?.expense_by_category || [])" :key="exp.category">
                            <div class="p-3 rounded-xl bg-rose-50/60 border border-rose-200/80 flex items-center justify-between text-xs">
                                <span class="font-semibold text-slate-700" x-text="exp.label"></span>
                                <span class="font-extrabold text-rose-700" x-text="formatRupiah(exp.total)"></span>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Daftar Mutasi Kas Bulan Terpilih -->
                <div class="space-y-2">
                    <div class="text-xs font-bold uppercase tracking-wider text-slate-500">Catatan Mutasi Kas Bulan Ini</div>

                    <div x-show="(treasuryReport?.recent_transactions?.length || 0) === 0"
                         class="p-8 rounded-xl bg-slate-50 border border-dashed border-slate-300 text-center text-xs text-slate-500">
                        Belum ada mutasi kas yang tercatat pada bulan ini.
                    </div>

                    <div class="divide-y divide-slate-100 border border-slate-200 rounded-xl overflow-hidden"
                         x-show="(treasuryReport?.recent_transactions?.length || 0) > 0">
                        <template x-for="tx in (treasuryReport?.recent_transactions || [])" :key="tx.id">
                            <div class="px-4 py-3 flex items-center justify-between gap-3 text-xs">
                                <div class="space-y-0.5 min-w-0">
                                    <div class="font-bold text-slate-900 leading-snug" x-text="tx.description"></div>
                                    <div class="text-[11px] text-slate-400"
                                         x-text="tx.entry_date_formatted + ' • ' + tx.category_label + ' (' + (tx.payment_method === 'transfer' ? 'Transfer' : 'Tunai') + ')'">
                                    </div>
                                </div>
                                <span :class="tx.type === 'income' ? 'text-emerald-700' : 'text-rose-600'"
                                      class="font-extrabold shrink-0"
                                      x-text="(tx.type === 'income' ? '+ ' : '- ') + formatRupiah(tx.amount)">
                                </span>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- BAGIAN 4: DIREKTORI WARGA TERFILTER PRIVASI -->
    <div x-show="tab === 'directory'" class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-4">
        <div class="border-b border-slate-100 pb-3">
            <h2 class="text-lg font-bold text-slate-900">Direktori Warga Terverifikasi</h2>
            <p class="text-xs text-slate-500">Nomor WhatsApp dan status kepemilikan hanya ditampilkan jika diizinkan oleh pengaturan privasi masing-masing warga.</p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <template x-for="resident in residents" :key="resident.id">
                <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/60 space-y-2">
                    <div class="flex items-center justify-between gap-2">
                        <div class="font-bold text-sm text-slate-900" x-text="resident.name"></div>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800"
                              x-text="resident.reputation_badge?.label"></span>
                    </div>

                    <div class="flex flex-wrap gap-1.5">
                        <template x-for="u in resident.units" :key="u.unit_code">
                            <a :href="'/peta-kavling?unit=' + encodeURIComponent(u.unit_code)"
                               title="Lihat lokasi kavling di Peta"
                               class="px-2 py-0.5 rounded bg-white hover:bg-emerald-50 border border-slate-200 hover:border-emerald-300 text-[11px] font-semibold text-slate-700 hover:text-emerald-800 transition"
                               x-text="u.unit_code + ' (' + u.zone + ')' + (u.sub_role ? ' • ' + u.sub_role : '')">
                            </a>
                        </template>
                    </div>

                    <div class="pt-1 text-xs">
                        <template x-if="resident.phone">
                            <a :href="'https://wa.me/' + resident.phone.replace(/^0/, '62')" target="_blank"
                               class="text-emerald-700 font-semibold hover:underline"
                               x-text="'WA: ' + resident.phone"></a>
                        </template>
                        <template x-if="!resident.phone">
                            <span class="text-slate-400 italic">Nomor WhatsApp disamarkan (Privat)</span>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- BAGIAN 5: NOTULENSI MUSYAWARAH & TATA TERTIB PAGUYUBAN -->
    <div x-show="tab === 'minutes'" class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
            <div>
                <h2 class="text-lg font-bold text-slate-900">Arsip Notulensi Rapat &amp; Tata Tertib Lingkungan</h2>
                <p class="text-xs text-slate-500">Dokumen kesepakatan musyawarah warga dan tata tertib yang berlaku di Perumahan Pesona Mandiri Turen.</p>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <select x-model="filterMinuteCategory" class="rounded-xl border-slate-300 text-xs">
                    <option value="">Semua Dokumen</option>
                    <option value="musyawarah_warga">Musyawarah Warga</option>
                    <option value="tata_tertib">Tata Tertib Lingkungan</option>
                    <option value="rapat_pengurus">Rapat Pengurus</option>
                </select>
                <a x-show="me?.can_manage_announcements"
                   href="/pengurus"
                   class="px-3 py-2 rounded-xl bg-indigo-50 text-indigo-700 border border-indigo-200 hover:bg-indigo-100 text-xs font-bold transition">
                    + Catat
                </a>
            </div>
        </div>

        <div x-show="filteredMinutes.length === 0"
             class="p-10 rounded-xl bg-slate-50 border border-dashed border-slate-300 text-center text-xs text-slate-500">
            Belum ada arsip notulensi musyawarah atau tata tertib pada kategori ini.
        </div>

        <div class="divide-y divide-slate-100 border border-slate-200 rounded-xl overflow-hidden" x-show="filteredMinutes.length > 0">
            <template x-for="m in filteredMinutes" :key="m.id">
                <button type="button"
                        @click="selectedMinute = m"
                        class="w-full px-5 py-4 flex items-start justify-between gap-4 text-left hover:bg-slate-50/90 transition cursor-pointer group">
                    <div class="space-y-1.5 min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span :class="m.badgeClass"
                                  class="px-2 py-0.5 rounded-md text-[10px] font-bold border"
                                  x-text="m.category">
                            </span>
                            <span x-show="m.visibility === 'internal_pengurus'"
                                  class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-100 text-slate-700 border border-slate-300">
                                Internal Pengurus
                            </span>
                            <span class="text-[11px] text-slate-400 font-medium"
                                  x-text="m.meeting_date_formatted + (m.location ? ' • ' + m.location : '')">
                            </span>
                        </div>
                        <div class="text-sm font-bold text-slate-900 group-hover:text-emerald-800 transition leading-snug"
                             x-text="m.title">
                        </div>
                        <p class="text-xs text-slate-500 line-clamp-2 leading-relaxed" x-text="m.excerpt"></p>
                    </div>
                    <span class="text-slate-400 group-hover:text-emerald-700 group-hover:translate-x-0.5 transition text-lg font-medium shrink-0 mt-1">&rsaquo;</span>
                </button>
            </template>
        </div>
    </div>

    <!-- BAGIAN 6: PETA 91 KAVLING INTERNAL -->
    <div x-show="tab === 'siteplan'" class="bg-white rounded-2xl p-6 border border-slate-200 shadow-sm space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-slate-100 pb-4">
            <div>
                <h2 class="text-lg font-bold text-slate-900">Peta Kavling Internal PPMT (<span x-text="units.length"></span> Unit)</h2>
                <p class="text-xs text-slate-500">Pemetaan blok, zona koordinasi, dan status keterisian hunian (khusus internal warga).</p>
            </div>
            <div class="flex flex-wrap gap-3 text-xs">
                <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-slate-200 border border-slate-300"></span> Belum Klaim</span>
                <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-amber-200 border border-amber-400"></span> Menunggu</span>
                <span class="inline-flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-emerald-200 border border-emerald-400"></span> Terisi</span>
            </div>
        </div>

        <div class="space-y-6">
            <template x-for="(groupUnits, blockName) in groupedUnits" :key="blockName">
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-600"
                            x-text="formatBlockTitle(blockName)"></h3>
                        <span class="text-[11px] text-slate-400" x-text="groupUnits.length + ' unit'"></span>
                    </div>

                    <div class="grid grid-cols-5 sm:grid-cols-8 md:grid-cols-10 gap-2">
                        <template x-for="unit in groupUnits" :key="unit.id">
                            <div :class="{
                                    'bg-teal-50 text-teal-800 border-teal-300': unit.unit_type === 'fasum',
                                    'bg-emerald-50 text-emerald-900 border-emerald-300': ['occupied', 'rented'].includes(unit.occupancy_status) && unit.unit_type !== 'fasum',
                                    'bg-amber-50 text-amber-900 border-amber-300': unit.occupancy_status === 'pending',
                                    'bg-slate-100 text-slate-700 border-slate-200': unit.occupancy_status === 'unclaimed' && unit.unit_type !== 'fasum'
                                 }"
                                 class="px-2 py-2 rounded-lg border text-xs text-center flex flex-col items-center justify-center">
                                <span class="font-bold" x-text="unit.code"></span>
                                <span class="text-[9px] opacity-75" x-text="unit.zone.replace('ZONA-', 'Z')"></span>
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- MODAL 1: DUKUNG ASPIRASI ATAS NAMA KAVLING + KESAKSIAN SINGKAT         -->
    <!-- ===================================================================== -->
    <div x-show="signModalAspiration !== null"
         x-cloak
         class="fixed inset-0 z-50 !mt-0 flex items-center justify-center p-3 sm:p-4 bg-slate-900/50 backdrop-blur-[1px] overflow-y-auto">
        <div @click.away="signModalAspiration = null"
             class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-[calc(100vw-1.5rem)] sm:max-w-md max-h-[90dvh] flex flex-col overflow-hidden my-auto">
            <div class="px-5 py-4 bg-emerald-950 text-white flex items-center justify-between gap-3 shrink-0">
                <div>
                    <h3 class="text-sm font-extrabold">Dukungan Resmi Kavling</h3>
                    <p class="text-[11px] text-emerald-200">1 Kavling = 1 Suara Sah dalam Rekap Audiensi</p>
                </div>
                <button type="button" @click="signModalAspiration = null" class="text-emerald-200 hover:text-white text-sm p-1 cursor-pointer">✕</button>
            </div>
            <form @submit.prevent="submitSignature()" class="p-5 space-y-4 overflow-y-auto">
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 text-xs font-bold text-slate-800 leading-snug"
                     x-text="signModalAspiration?.title"></div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Pilih Kavling yang Mewakili *</label>
                    <select x-model="signForm.unit_id" required class="w-full rounded-xl border-slate-300 text-xs">
                        <template x-for="m in (me?.memberships || [])" :key="m.id">
                            <option :value="m.unit.id" x-text="'Kavling ' + m.unit.code"></option>
                        </template>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                        Catatan Kesaksian Lapangan <span class="text-slate-400 font-normal">(Opsional)</span>
                    </label>
                    <textarea x-model="signForm.note" rows="3" maxlength="255"
                              placeholder="Contoh: Di kavling kami juga mengalami hal serupa di bagian plafon ruang belakang..."
                              class="w-full rounded-xl border-slate-300 text-xs"></textarea>
                    <p class="text-[10px] text-slate-400 mt-1">Kesaksian singkat akan dilampirkan bersama kode kavling Anda sebagai bukti lapangan.</p>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="signModalAspiration = null"
                            class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 text-xs font-semibold cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" :disabled="submittingSign"
                            class="px-4 py-2 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold shadow-sm transition disabled:opacity-50 cursor-pointer">
                        <span x-show="!submittingSign">Simpan Dukungan Kavling</span>
                        <span x-show="submittingSign">Menyimpan...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- MODAL 2: KELOLA STATUS & PROGRES AUDIENSI (KHUSUS PENGURUS)           -->
    <!-- ===================================================================== -->
    <div x-show="reviewModalAspiration !== null"
         x-cloak
         class="fixed inset-0 z-50 !mt-0 flex items-center justify-center p-3 sm:p-4 bg-slate-900/50 backdrop-blur-[1px] overflow-y-auto">
        <div @click.away="reviewModalAspiration = null"
             class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-[calc(100vw-1.5rem)] sm:max-w-md max-h-[90dvh] flex flex-col overflow-hidden my-auto">
            <div class="px-5 py-4 bg-indigo-950 text-white flex items-center justify-between gap-3 shrink-0">
                <div>
                    <h3 class="text-sm font-extrabold">Kurasi &amp; Progres Tindak Lanjut</h3>
                    <p class="text-[11px] text-indigo-200">Panel Pengurus Paguyuban PPMT</p>
                </div>
                <button type="button" @click="reviewModalAspiration = null" class="text-indigo-200 hover:text-white text-sm p-1 cursor-pointer">✕</button>
            </div>
            <form @submit.prevent="submitAspirationReview()" class="p-5 space-y-4 overflow-y-auto">
                <div class="text-xs font-bold text-slate-800 leading-snug" x-text="reviewModalAspiration?.title"></div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Status Aspirasi *</label>
                    <select x-model="reviewForm.status" required class="w-full rounded-xl border-slate-300 text-xs">
                        <option value="pending">Menunggu Kurasi (Draft)</option>
                        <option value="open">Buka Penggalangan Dukungan Warga</option>
                        <option value="submitted">Diajukan / Agenda Audiensi Pengembang</option>
                        <option value="resolved">Selesai Ditangani</option>
                        <option value="rejected">Ditolak / Digabung ke Isu Serupa</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Target Dukungan Kavling</label>
                    <input type="number" x-model.number="reviewForm.target_signatures" min="5" max="91"
                           class="w-full rounded-xl border-slate-300 text-xs">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Catatan Hasil Audiensi / Respons Pengembang</label>
                    <textarea x-model="reviewForm.developer_response" rows="3"
                              placeholder="Catat perkembangan terbaru, jadwal survei teknis, atau kesepakatan dengan pengembang..."
                              class="w-full rounded-xl border-slate-300 text-xs"></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="reviewModalAspiration = null"
                            class="px-4 py-2 rounded-xl bg-slate-100 text-slate-700 text-xs font-semibold cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" :disabled="submittingReview"
                            class="px-4 py-2 rounded-xl bg-indigo-700 hover:bg-indigo-800 text-white text-xs font-bold shadow-sm transition disabled:opacity-50 cursor-pointer">
                        <span x-show="!submittingReview">Simpan Perubahan</span>
                        <span x-show="submittingReview">Menyimpan...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- MODAL 3: BACA DETAIL NOTULENSI / TATA TERTIB                          -->
    <!-- ===================================================================== -->
    <div x-show="selectedMinute !== null"
         x-cloak
         class="fixed inset-0 z-50 !mt-0 flex items-center justify-center p-3 sm:p-4 bg-slate-900/50 backdrop-blur-[1px] overflow-y-auto">
        <div @click.away="selectedMinute = null"
             class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-[calc(100vw-1.5rem)] sm:max-w-lg max-h-[90dvh] flex flex-col overflow-hidden my-auto">
            <div class="px-5 sm:px-6 py-4 border-b border-slate-100 flex items-center justify-between gap-3 shrink-0">
                <div class="flex flex-wrap items-center gap-2">
                    <span :class="selectedMinute?.badgeClass"
                          class="px-2 py-0.5 rounded-md text-[10px] font-bold border"
                          x-text="selectedMinute?.category"></span>
                    <span class="text-xs text-slate-400" x-text="selectedMinute?.meeting_date_formatted"></span>
                </div>
                <button type="button" @click="selectedMinute = null" class="text-slate-400 hover:text-slate-700 text-sm p-1 cursor-pointer">✕</button>
            </div>
            <div class="p-5 sm:p-6 space-y-3 overflow-y-auto">
                <h3 class="text-base sm:text-lg font-extrabold text-slate-900 leading-snug" x-text="selectedMinute?.title"></h3>
                <div class="text-xs text-slate-500 space-y-0.5">
                    <div x-show="selectedMinute?.location" x-text="'Lokasi / Forum: ' + selectedMinute?.location"></div>
                    <div x-text="'Dicatat oleh: ' + (selectedMinute?.author || 'Sekretariat Paguyuban PPMT')"></div>
                </div>
                <p class="text-xs sm:text-sm text-slate-700 leading-relaxed whitespace-pre-line pt-2 border-t border-slate-100"
                   x-text="selectedMinute?.decisions"></p>
            </div>
            <div class="px-5 sm:px-6 py-3.5 bg-slate-50 border-t border-slate-100 flex justify-end shrink-0">
                <button type="button" @click="selectedMinute = null"
                        class="px-4 py-2 rounded-xl bg-slate-900 text-white text-xs font-semibold cursor-pointer">
                    Tutup
                </button>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function ppmtCommunity() {
    const urlParams = new URLSearchParams(window.location.search);
    const validTabs = ['umkm', 'aspirasi', 'kas', 'directory', 'minutes', 'siteplan'];
    const initialTab = validTabs.includes(urlParams.get('tab'))
        ? urlParams.get('tab')
        : 'umkm';
    const currentMonthStr = new Date().toISOString().slice(0, 7);

    return {
        token: localStorage.getItem('ppmt_token'),
        tab: initialTab,
        me: null,
        stores: [],
        aspirations: [],
        residents: [],
        units: [],
        meetingMinutes: [],
        selectedMinute: null,
        treasuryMonth: currentMonthStr,
        treasuryReport: null,
        filterCategory: '',
        filterStatus: '',
        filterAspirationStatus: '',
        filterAspirationCategory: '',
        filterMinuteCategory: '',
        toastMessage: '',
        toastType: 'success',
        submittingAspiration: false,
        submittingSign: false,
        submittingReview: false,
        signModalAspiration: null,
        reviewModalAspiration: null,
        storeForm: {
            unit_code: '',
            store_name: '',
            category: 'kuliner',
            description: ''
        },
        aspirationForm: {
            unit_id: '',
            category: 'Infrastruktur & Drainase',
            title: '',
            location_scope: '',
            description: ''
        },
        signForm: {
            unit_id: '',
            note: ''
        },
        reviewForm: {
            status: 'open',
            target_signatures: 30,
            developer_response: ''
        },

        async init() {
            if (!this.token) {
                window.location.href = '/';
                return;
            }
            const meRes = await fetch('/api/v1/me', { headers: this.headers() });
            if (!meRes.ok) {
                window.location.href = '/';
                return;
            }
            const meJson = await meRes.json();
            this.me = meJson.data;

            if (!this.me.is_verified) {
                window.location.href = '/dashboard';
                return;
            }

            if (this.me.memberships?.length > 0) {
                this.storeForm.unit_code = this.me.memberships[0].unit.code;
                this.aspirationForm.unit_id = this.me.memberships[0].unit.id;
                this.signForm.unit_id = this.me.memberships[0].unit.id;
            }

            await Promise.all([
                this.loadStores(),
                this.loadAspirations(),
                this.loadDirectory(),
                this.loadUnits(),
                this.loadMeetingMinutes(),
                this.loadTreasuryReport()
            ]);
        },

        setTab(newTab) {
            this.tab = newTab;
            const url = new URL(window.location);
            url.searchParams.set('tab', newTab);
            window.history.replaceState({}, '', url);
        },

        headers() {
            return {
                'Authorization': 'Bearer ' + this.token,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            };
        },

        formatRupiah(value) {
            return 'Rp ' + Number(value || 0).toLocaleString('id-ID');
        },

        get openAspirationsCount() {
            return this.aspirations.filter(a => a.status === 'open' && !a.is_signed_by_me).length;
        },

        get filteredAspirations() {
            return this.aspirations.filter(a => {
                if (this.filterAspirationStatus && a.status !== this.filterAspirationStatus) return false;
                if (this.filterAspirationCategory && a.category !== this.filterAspirationCategory) return false;
                return true;
            });
        },

        aspirationStatusLabel(status) {
            const labels = {
                'pending': 'Menunggu Kurasi',
                'open': '● Penggalangan Dukungan',
                'submitted': 'Diajukan ke Pengembang',
                'resolved': '✓ Selesai Ditangani',
                'rejected': 'Ditolak / Digabung'
            };
            return labels[status] || status;
        },

        get filteredMinutes() {
            if (!this.filterMinuteCategory) return this.meetingMinutes;
            return this.meetingMinutes.filter(m => m.category_code === this.filterMinuteCategory);
        },

        async loadAspirations() {
            const res = await fetch('/api/v1/aspirations', { headers: this.headers() });
            if (res.ok) {
                const json = await res.json();
                this.aspirations = json.data || [];
            }
        },

        async createAspiration() {
            this.submittingAspiration = true;
            try {
                const res = await fetch('/api/v1/aspirations', {
                    method: 'POST',
                    headers: this.headers(),
                    body: JSON.stringify(this.aspirationForm)
                });
                const json = await res.json();
                if (res.ok) {
                    this.toastType = 'success';
                    this.toastMessage = json.message;
                    this.aspirationForm.title = '';
                    this.aspirationForm.location_scope = '';
                    this.aspirationForm.description = '';
                    await this.loadAspirations();
                } else {
                    this.toastType = 'error';
                    this.toastMessage = json.message || 'Gagal mengirim aspirasi.';
                }
            } finally {
                this.submittingAspiration = false;
            }
        },

        openSignModal(asp) {
            this.signModalAspiration = asp;
            this.signForm.unit_id = this.me?.memberships?.[0]?.unit?.id || '';
            this.signForm.note = '';
        },

        async submitSignature() {
            if (!this.signModalAspiration) return;
            this.submittingSign = true;
            try {
                const res = await fetch(`/api/v1/aspirations/${this.signModalAspiration.id}/sign`, {
                    method: 'POST',
                    headers: this.headers(),
                    body: JSON.stringify(this.signForm)
                });
                const json = await res.json();
                if (res.ok) {
                    this.toastType = 'success';
                    this.toastMessage = json.message;
                    this.signModalAspiration = null;
                    await this.loadAspirations();
                } else {
                    this.toastType = 'error';
                    this.toastMessage = json.message || 'Gagal mencatat dukungan.';
                }
            } finally {
                this.submittingSign = false;
            }
        },

        openReviewModal(asp) {
            this.reviewModalAspiration = asp;
            this.reviewForm.status = asp.status;
            this.reviewForm.target_signatures = asp.target_signatures || 30;
            this.reviewForm.developer_response = asp.developer_response || '';
        },

        async submitAspirationReview() {
            if (!this.reviewModalAspiration) return;
            this.submittingReview = true;
            try {
                const res = await fetch(`/api/v1/aspirations/${this.reviewModalAspiration.id}/status`, {
                    method: 'PATCH',
                    headers: this.headers(),
                    body: JSON.stringify(this.reviewForm)
                });
                const json = await res.json();
                if (res.ok) {
                    this.toastType = 'success';
                    this.toastMessage = json.message;
                    this.reviewModalAspiration = null;
                    await this.loadAspirations();
                } else {
                    this.toastType = 'error';
                    this.toastMessage = json.message || 'Gagal memperbarui status aspirasi.';
                }
            } finally {
                this.submittingReview = false;
            }
        },

        async copyAspirationBrief(asp) {
            const unitList = asp.signed_unit_codes.length > 0 ? asp.signed_unit_codes.join(', ') : '-';
            const lines = [
                `*REKAP ASPIRASI KOLEKTIF WARGA PPMT*`,
                `Kategori: ${asp.category}`,
                `Isu: ${asp.title}`,
                asp.location_scope ? `Lokasi: ${asp.location_scope}` : null,
                `Dukungan Sah: ${asp.signatures_count} Kavling (${unitList})`,
                ``,
                `Uraian:`,
                asp.description
            ].filter(line => line !== null).join('\n');

            try {
                await navigator.clipboard.writeText(lines);
                this.toastType = 'success';
                this.toastMessage = 'Rekap aspirasi & daftar kavling berhasil disalin ke clipboard.';
            } catch (e) {
                this.toastType = 'error';
                this.toastMessage = 'Gagal menyalin teks ke clipboard.';
            }
        },

        async loadTreasuryReport() {
            const params = new URLSearchParams({ month: this.treasuryMonth });
            const res = await fetch('/api/v1/treasury/public-report?' + params.toString(), { headers: this.headers() });
            if (res.ok) {
                const json = await res.json();
                this.treasuryReport = json.data || null;
            }
        },

        async loadStores() {
            const params = new URLSearchParams();
            if (this.filterCategory) params.append('category', this.filterCategory);
            if (this.filterStatus) params.append('status', this.filterStatus);

            const res = await fetch('/api/v1/umkm/stores?' + params.toString(), { headers: this.headers() });
            if (res.ok) {
                const json = await res.json();
                this.stores = json.data || [];
            }
        },

        async loadDirectory() {
            const res = await fetch('/api/v1/residents/directory', { headers: this.headers() });
            if (res.ok) {
                const json = await res.json();
                this.residents = json.data || [];
            }
        },

        async loadMeetingMinutes() {
            const res = await fetch('/api/v1/meeting-minutes', { headers: this.headers() });
            if (res.ok) {
                const json = await res.json();
                this.meetingMinutes = json.data || [];
            }
        },

        async loadUnits() {
            const res = await fetch('/api/v1/public/units');
            if (res.ok) {
                const json = await res.json();
                this.units = json.data || [];
            }
        },

        get groupedUnits() {
            return this.units.reduce((groups, unit) => {
                if (!groups[unit.block]) groups[unit.block] = [];
                groups[unit.block].push(unit);
                return groups;
            }, {});
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

        async createStore() {
            const res = await fetch('/api/v1/umkm/stores', {
                method: 'POST',
                headers: this.headers(),
                body: JSON.stringify(this.storeForm)
            });
            const json = await res.json();
            if (res.ok) {
                this.toastType = 'success';
                this.toastMessage = json.message;
                this.storeForm.store_name = '';
                this.storeForm.description = '';
                await this.loadStores();
            } else {
                this.toastType = 'error';
                this.toastMessage = json.message || 'Gagal membuka lapak.';
            }
        },

        async changeStoreStatus(storeId, status) {
            const res = await fetch(`/api/v1/umkm/stores/${storeId}/status`, {
                method: 'PATCH',
                headers: this.headers(),
                body: JSON.stringify({ status })
            });
            const json = await res.json();
            if (res.ok) {
                this.toastType = 'success';
                this.toastMessage = json.message;
                await this.loadStores();
            }
        },

        buildWaUrl(store) {
            const phone = (store.whatsapp_number || '').replace(/^0/, '62').replace(/[^0-9]/g, '');
            const myUnit = this.me?.memberships?.[0]?.unit?.code || 'PPMT';
            const text = encodeURIComponent(
                `Halo *${store.store_name}*, saya *${this.me?.name}* dari *Kavling ${myUnit}* (PPMT). Saya ingin memesan produk/jasa dari Pasar Tetangga PPMT.`
            );
            return `https://wa.me/${phone}?text=${text}`;
        }
    }
}
</script>
@endpush
@extends('layouts.app')

@section('title', 'Pengaturan Lapak Usaha — PPMT Digital')
@section('subpage_title', 'Lapak Usaha & Pasar Tetangga')
@section('subpage_back_url', '/pengaturan')

@section('content')
<div x-data="ppmtMyLapakSettings()"
     x-init="init()"
     @keydown.escape.window="if (!uploadingImage) { showItemModal = false; showEditStoreModal = false; }"
     class="max-w-3xl mx-auto px-4 py-6 sm:py-8 space-y-6">

    <!-- Loading -->
    <div x-show="loading" class="bg-white rounded-2xl p-10 text-center text-sm text-slate-500 border border-slate-200">
        Memuat data lapak usaha Anda...
    </div>

    <div x-show="!loading && user" x-cloak class="space-y-6">

        <!-- Toast Notifikasi -->
        <div x-show="toastMessage"
             x-cloak
             :class="toastType === 'error' ? 'bg-rose-50 text-rose-800 border-rose-200' : 'bg-emerald-50 text-emerald-800 border-emerald-200'"
             class="p-3.5 rounded-xl border text-xs sm:text-sm font-medium flex justify-between items-center shadow-2xs">
            <span x-text="toastMessage"></span>
            <button type="button" @click="toastMessage = ''" class="text-xs opacity-70 hover:opacity-100 cursor-pointer">✕</button>
        </div>

        <!-- ================================================================= -->
        <!-- KONDISI A: BELUM MEMILIKI LAPAK (FORM BUKA LAPAK BARU)            -->
        <!-- ================================================================= -->
        <template x-if="!store">
            <div class="space-y-5">
                <section class="bg-gradient-to-r from-emerald-950 via-emerald-900 to-teal-900 text-white rounded-2xl p-5 sm:p-6 shadow-sm space-y-2">
                    <div class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-amber-400/20 border border-amber-300/30 text-amber-200 text-[11px] font-bold">
                        Etalase Ekonomi Warga PPMT
                    </div>
                    <h2 class="text-lg sm:text-xl font-extrabold">Buka Lapak Usaha di Pasar Tetangga</h2>
                    <p class="text-xs sm:text-sm text-emerald-100/90 leading-relaxed">
                        Anda belum memiliki lapak terdaftar. Daftarkan usaha rumahan, kuliner, stok galon/gas, jasa keahlian, maupun peminjaman alat agar mudah ditemukan oleh tetangga satu perumahan.
                    </p>
                </section>

                <section class="bg-white rounded-2xl p-5 sm:p-6 border border-slate-200 shadow-sm">
                    <form @submit.prevent="createStore()" class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1">Kavling Lokasi Usaha *</label>
                                <select x-model="createForm.unit_code" required class="w-full rounded-xl border-slate-300 text-sm">
                                    <template x-for="m in approvedMemberships" :key="m.id">
                                        <option :value="m.unit.code" x-text="'Kavling ' + m.unit.code + ' (' + m.unit.zone + ')'"></option>
                                    </template>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1">Kategori Utama Lapak *</label>
                                <select x-model="createForm.category" required class="w-full rounded-xl border-slate-300 text-sm">
                                    <option value="kuliner">Kuliner &amp; Minuman</option>
                                    <option value="sembako">Sembako, Galon &amp; Gas</option>
                                    <option value="jasa">Jasa &amp; Pertukangan</option>
                                    <option value="pinjam_alat">Pinjam / Sewa Alat</option>
                                    <option value="lainnya">Lainnya</option>
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1">Nama Lapak / Usaha *</label>
                                <input type="text"
                                       x-model="createForm.store_name"
                                       required
                                       maxlength="120"
                                       placeholder="Contoh: Dapur Sarapan Bu Siti"
                                       class="w-full rounded-xl border-slate-300 text-sm">
                            </div>

                            <div>
                                <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1">Nomor WhatsApp Pemesanan *</label>
                                <input type="text"
                                       x-model="createForm.whatsapp_number"
                                       required
                                       maxlength="20"
                                       placeholder="Contoh: 081234567890"
                                       class="w-full rounded-xl border-slate-300 text-sm font-mono">
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs sm:text-sm font-semibold text-slate-700 mb-1">Deskripsi Singkat &amp; Jam Layanan</label>
                            <textarea x-model="createForm.description"
                                      rows="3"
                                      maxlength="500"
                                      placeholder="Contoh: Melayani pesanan sarapan pagi setiap hari pukul 06.00 – 09.30 WIB, siap antar ke seluruh blok PPMT."
                                      class="w-full rounded-xl border-slate-300 text-sm"></textarea>
                        </div>

                        <div class="pt-2 flex items-center justify-end">
                            <button type="submit"
                                    :disabled="saving || approvedMemberships.length === 0"
                                    class="w-full sm:w-auto px-6 py-2.5 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white text-sm font-bold shadow-sm transition disabled:opacity-50 cursor-pointer">
                                <span x-show="!saving">Buka Lapak Sekarang</span>
                                <span x-show="saving">Mendaftarkan Lapak...</span>
                            </button>
                        </div>
                    </form>
                </section>
            </div>
        </template>

        <!-- ================================================================= -->
        <!-- KONDISI B: SUDAH MEMILIKI LAPAK (KELOLA LAPAK + PRODUK & JASA)    -->
        <!-- ================================================================= -->
        <template x-if="store">
            <div class="space-y-6">

                <!-- Banner Status Kurasi Seksi Ekonomi (Jika Ditangguhkan / Sorotan / Ada Catatan) -->
                <div x-show="store.is_suspended"
                     class="p-4 sm:p-5 rounded-2xl bg-rose-50 border-2 border-rose-300 text-rose-950 space-y-1.5 shadow-2xs">
                    <div class="flex items-center gap-2 text-xs font-extrabold uppercase tracking-wider text-rose-700">
                        <span>&#9888; Lapak Ditangguhkan Sementara oleh Seksi Ekonomi</span>
                    </div>
                    <p class="text-xs sm:text-sm leading-relaxed">
                        Untuk sementara waktu, produk dan profil lapak Anda disembunyikan dari etalase publik Pasar Tetangga. Silakan lakukan penyesuaian data lapak lalu hubungi Seksi Ekonomi atau Ketua Paguyuban untuk pengaktifan kembali.
                    </p>
                    <div x-show="store.moderation_note"
                         class="p-3 rounded-xl bg-white border border-rose-200 text-xs text-rose-900">
                        <strong>Catatan Seksi Ekonomi:</strong> <span x-text="store.moderation_note"></span>
                    </div>
                </div>

                <div x-show="!store.is_suspended && store.is_currently_featured"
                     class="p-4 rounded-2xl bg-amber-50 border border-amber-300 text-amber-950 flex flex-col sm:flex-row sm:items-center justify-between gap-2 shadow-2xs">
                    <div class="space-y-0.5">
                        <div class="text-xs font-extrabold uppercase tracking-wider text-amber-800">
                            &#9733; Lapak Anda Terpilih sebagai Sorotan Pekan Ini
                        </div>
                        <p class="text-xs text-amber-900/90">
                            Produk Anda ditampilkan di barisan teratas Pasar Tetangga dengan pita emas <strong>Sorotan</strong>. Pastikan status buka dan stok selalu diperbarui!
                        </p>
                    </div>
                    <span x-show="store.featured_until"
                          class="px-2.5 py-1 rounded-lg bg-amber-200/80 text-amber-950 text-[11px] font-bold shrink-0"
                          x-text="'Hingga ' + formatDateId(store.featured_until)"></span>
                </div>

                <div x-show="!store.is_suspended && store.moderation_note"
                     class="p-4 rounded-2xl bg-sky-50 border border-sky-200 text-sky-950 text-xs space-y-1">
                    <div class="font-extrabold text-sky-900">Catatan Pembinaan dari Seksi Ekonomi:</div>
                    <p class="leading-relaxed" x-text="store.moderation_note"></p>
                </div>

                <!-- Kartu Header & Kontrol Status Lapak -->
                <section class="bg-white rounded-2xl p-5 sm:p-6 border border-slate-200 shadow-sm space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3 border-b border-slate-100 pb-4">
                        <div class="space-y-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-lg font-extrabold text-slate-900" x-text="store.store_name"></h2>
                                <span class="px-2 py-0.5 rounded-md text-[11px] font-mono font-bold bg-slate-100 text-slate-800 border border-slate-200"
                                      x-text="'Kav. ' + (store.unit?.code || '')"></span>
                                <span x-show="!store.is_suspended && store.is_currently_featured"
                                      class="px-2 py-0.5 rounded-full text-[10px] font-extrabold bg-amber-400 text-slate-950">
                                    &#9733; Sorotan
                                </span>
                            </div>
                            <p class="text-xs text-slate-500 leading-relaxed"
                               x-text="store.description || 'Lapak usaha warga internal PPMT.'"></p>
                            <div class="text-[11px] text-slate-500 pt-0.5">
                                WA Pesanan: <span class="font-mono font-semibold text-slate-700" x-text="store.whatsapp_number"></span>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 shrink-0">
                            <a x-show="!store.is_suspended"
                               href="/komunitas/pasar-tetangga"
                               class="px-3 py-1.5 rounded-xl bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200 text-xs font-bold transition">
                                Lihat Etalase &nearr;
                            </a>
                            <button type="button"
                                    @click="openEditStoreModal()"
                                    class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition cursor-pointer">
                                Edit Lapak
                            </button>
                        </div>
                    </div>

                    <!-- Pemilih Status Operasional Lapak -->
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <span class="text-xs font-semibold text-slate-600">Status Operasional Saat Ini:</span>
                        <div class="inline-flex rounded-xl bg-slate-100 p-1 gap-1">
                            <button type="button"
                                    @click="changeStatus('open')"
                                    :class="store.status === 'open' ? 'bg-emerald-600 text-white shadow-2xs' : 'text-slate-600 hover:text-slate-900'"
                                    class="px-3 py-1.5 rounded-lg text-xs font-bold transition cursor-pointer">
                                ● Buka
                            </button>
                            <button type="button"
                                    @click="changeStatus('open_po')"
                                    :class="store.status === 'open_po' ? 'bg-amber-500 text-slate-950 shadow-2xs' : 'text-slate-600 hover:text-slate-900'"
                                    class="px-3 py-1.5 rounded-lg text-xs font-bold transition cursor-pointer">
                                ◐ Open PO
                            </button>
                            <button type="button"
                                    @click="changeStatus('closed')"
                                    :class="store.status === 'closed' ? 'bg-slate-700 text-white shadow-2xs' : 'text-slate-600 hover:text-slate-900'"
                                    class="px-3 py-1.5 rounded-lg text-xs font-bold transition cursor-pointer">
                                ○ Tutup
                            </button>
                        </div>
                    </div>
                </section>

                <!-- Daftar Katalog Produk & Jasa Lapak -->
                <section class="bg-white rounded-2xl p-5 sm:p-6 border border-slate-200 shadow-sm space-y-4">
                    <div class="flex items-center justify-between gap-3 border-b border-slate-100 pb-3.5">
                        <div>
                            <h3 class="text-base font-bold text-slate-900">
                                Katalog Produk &amp; Jasa (<span x-text="catalogItems.length"></span>)
                            </h3>
                            <p class="text-xs text-slate-500">Item yang ditambahkan akan tampil di etalase Pasar Tetangga PPMT.</p>
                        </div>
                        <button type="button"
                                @click="openNewItemModal()"
                                class="px-3.5 py-2 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold shadow-xs transition cursor-pointer shrink-0">
                            + Tambah Produk / Jasa
                        </button>
                    </div>

                    <!-- Empty State Katalog -->
                    <div x-show="catalogItems.length === 0"
                         class="p-8 rounded-xl bg-slate-50 border border-dashed border-slate-300 text-center space-y-2">
                        <div class="text-sm font-bold text-slate-700">Etalase lapak Anda masih kosong</div>
                        <p class="text-xs text-slate-500">Tambahkan menu makanan, stok galon/gas, layanan jasa, atau alat yang bisa dipinjam tetangga.</p>
                        <button type="button"
                                @click="openNewItemModal()"
                                class="mt-1 px-4 py-2 rounded-xl bg-emerald-700 text-white text-xs font-bold cursor-pointer">
                            + Tambah Item Pertama
                        </button>
                    </div>

                    <!-- List Produk / Jasa -->
                    <div x-show="catalogItems.length > 0" class="divide-y divide-slate-100">
                        <template x-for="(item, idx) in catalogItems" :key="item.id || idx">
                            <div class="py-4 first:pt-1 last:pb-1 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div class="flex items-start gap-3 min-w-0">
                                    <div class="w-12 h-12 rounded-xl bg-slate-100 border border-slate-200 flex items-center justify-center text-xl shrink-0 overflow-hidden">
                                        <img x-show="item.image_url"
                                            :src="item.image_url"
                                            :alt="item.name"
                                            x-on:error="item.image_url = ''"
                                            class="w-full h-full object-cover">
                                        <span x-show="!item.image_url" x-text="categoryEmoji(item.category)"></span>
                                    </div>
                                    <div class="min-w-0 space-y-1">
                                        <div class="flex flex-wrap items-center gap-1.5">
                                            <span class="font-bold text-sm text-slate-900" x-text="item.name"></span>
                                            <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-emerald-50 text-emerald-800 border border-emerald-200"
                                                  x-text="badgeLabel(item.badge_type)"></span>
                                            <span x-show="item.is_available === false"
                                                  class="px-2 py-0.5 rounded text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                                Stok Habis
                                            </span>
                                        </div>
                                        <div class="text-xs font-extrabold text-emerald-800"
                                             x-text="Number(item.price || 0) > 0 ? formatRupiah(item.price) + (item.unit_note ? ' / ' + String(item.unit_note).replace(/^(\/\s*|per\s+)/i, '') : '') : ('Gratis / Pinjam' + (item.unit_note ? ' • ' + item.unit_note : ''))"></div>
                                        <p x-show="item.description"
                                           class="text-xs text-slate-500 line-clamp-2"
                                           x-text="item.description"></p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-1.5 shrink-0 self-end sm:self-center">
                                    <button type="button"
                                            @click="toggleItemAvailability(idx)"
                                            class="px-2.5 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 text-[11px] font-semibold text-slate-700 cursor-pointer"
                                            x-text="item.is_available === false ? 'Aktifkan' : 'Tandai Habis'">
                                    </button>
                                    <button type="button"
                                            @click="openEditItemModal(idx)"
                                            class="px-2.5 py-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-800 text-[11px] font-bold cursor-pointer">
                                        Edit
                                    </button>
                                    <button type="button"
                                            @click="removeCatalogItem(idx)"
                                            class="px-2.5 py-1.5 rounded-lg bg-rose-50 hover:bg-rose-100 text-rose-700 text-[11px] font-bold cursor-pointer">
                                        Hapus
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </section>

            </div>
        </template>

    </div>

    <!-- ===================================================================== -->
    <!-- MODAL TAMBAH / EDIT PRODUK ATAU JASA                                  -->
    <!-- ===================================================================== -->
    <div x-show="showItemModal"
         x-cloak
         class="fixed inset-0 z-50 !mt-0 flex items-center justify-center p-3 sm:p-4 bg-slate-900/55 backdrop-blur-[1px] overflow-y-auto">
        <div @click.away="if (!uploadingImage) showItemModal = false"
             class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-[calc(100vw-1.5rem)] sm:max-w-lg max-h-[90dvh] flex flex-col overflow-hidden my-auto">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between gap-3 shrink-0">
                <h3 class="text-base font-bold text-slate-900"
                    x-text="editingItemIndex !== null ? 'Edit Produk / Jasa' : 'Tambah Produk / Jasa Baru'"></h3>
                <button type="button" @click="if (!uploadingImage) showItemModal = false" class="text-slate-400 hover:text-slate-700 text-sm p-1 cursor-pointer">✕</button>
            </div>

            <form @submit.prevent="saveCatalogItem()" class="p-5 space-y-3.5 overflow-y-auto">
                <!-- Unggah Foto Produk / Jasa -->
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1.5">Foto Produk / Jasa <span class="font-normal text-slate-400">(Opsional, otomatis disesuaikan ke rasio kotak)</span></label>
                    <div class="flex items-center gap-3.5 p-3 rounded-xl bg-slate-50 border border-slate-200">
                        <div class="w-16 h-16 rounded-xl bg-white border border-slate-200 flex items-center justify-center overflow-hidden shrink-0">
                            <img x-show="itemForm.image_url"
                                 :src="itemForm.image_url"
                                 alt="Pratinjau foto"
                                 class="w-full h-full object-cover">
                            <span x-show="!itemForm.image_url" class="text-2xl" x-text="categoryEmoji(itemForm.category)"></span>
                        </div>
                        <div class="flex-1 min-w-0 space-y-1.5">
                            <div class="flex flex-wrap items-center gap-2">
                                <label :class="uploadingImage ? 'opacity-60 pointer-events-none' : ''"
                                       class="px-3 py-1.5 rounded-lg bg-white hover:bg-slate-100 text-slate-800 border border-slate-300 text-xs font-bold cursor-pointer transition">
                                    <span x-text="uploadingImage ? 'Mengompresi & Mengunggah...' : (itemForm.image_url ? 'Ganti Foto' : 'Pilih Foto')"></span>
                                    <input type="file"
                                           accept="image/jpeg,image/png,image/webp"
                                           :disabled="uploadingImage"
                                           @change="handleItemImageSelected($event)"
                                           class="hidden">
                                </label>
                                <button type="button"
                                        x-show="itemForm.image_url && !uploadingImage"
                                        @click="itemForm.image_url = ''"
                                        class="px-2.5 py-1.5 rounded-lg text-rose-600 hover:bg-rose-50 text-xs font-semibold cursor-pointer">
                                    Hapus Foto
                                </button>
                            </div>
                            <p class="text-[11px] text-slate-500">JPG, PNG, atau WebP (maks. 5 MB). Otomatis dikompres agar hemat kuota.</p>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Produk atau Jasa *</label>
                    <input type="text" x-model="itemForm.name" required maxlength="120"
                           placeholder="Contoh: Nasi Pecel Madiun / Jasa Cuci AC"
                           class="w-full rounded-xl border-slate-300 text-sm">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Kategori *</label>
                        <select x-model="itemForm.category" class="w-full rounded-xl border-slate-300 text-sm">
                            <option value="kuliner">Kuliner &amp; Minuman</option>
                            <option value="sembako">Sembako, Galon &amp; Gas</option>
                            <option value="jasa">Jasa &amp; Pertukangan</option>
                            <option value="pinjam_alat">Pinjam / Sewa Alat</option>
                            <option value="lainnya">Lainnya</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Jenis Penawaran *</label>
                        <select x-model="itemForm.badge_type" class="w-full rounded-xl border-slate-300 text-sm">
                            <option value="ready">Ready Stock</option>
                            <option value="po">Open PO</option>
                            <option value="service">Layanan Jasa</option>
                            <option value="loan">Pinjam / Sewa Alat</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Harga (Rp) * <span class="font-normal text-slate-400">(isi 0 jika gratis / pinjam)</span></label>
                    <input type="number" x-model.number="itemForm.price" required min="0" step="500"
                           class="w-full rounded-xl border-slate-300 text-sm">
                </div>

                <!-- Pilihan Satuan (Toggle Buttons) -->
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-xs font-semibold text-slate-700">Satuan / Keterangan Harga</label>
                        <span x-show="itemForm.unit_note"
                              class="text-[11px] font-semibold text-emerald-700"
                              x-text="itemForm.unit_note"></span>
                    </div>
                    <div class="flex flex-wrap gap-1.5">
                        <template x-for="unit in availableUnitOptions" :key="unit">
                            <button type="button"
                                    @click="toggleUnitOption(unit)"
                                    :aria-pressed="itemForm.unit_note === unit"
                                    :class="itemForm.unit_note === unit
                                        ? 'bg-emerald-700 text-white border-emerald-700 shadow-2xs'
                                        : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100'"
                                    class="px-3 py-1.5 rounded-xl border text-xs font-semibold transition cursor-pointer">
                                <span x-text="unit"></span>
                            </button>
                        </template>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Info Pengantaran / Pengambilan</label>
                    <input type="text" x-model="itemForm.delivery_tag" maxlength="100"
                           placeholder="Contoh: Gratis antar seluruh blok PPMT"
                           class="w-full rounded-xl border-slate-300 text-sm">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Deskripsi Lengkap</label>
                    <textarea x-model="itemForm.description" rows="3" maxlength="500"
                              placeholder="Tuliskan rincian menu, spesifikasi jasa, atau ketentuan peminjaman..."
                              class="w-full rounded-xl border-slate-300 text-sm"></textarea>
                </div>

                <div class="pt-2 flex justify-end gap-2 border-t border-slate-100">
                    <button type="button" @click="if (!uploadingImage) showItemModal = false"
                            :disabled="uploadingImage"
                            class="px-4 py-2 rounded-xl border border-slate-300 text-slate-700 text-xs font-semibold disabled:opacity-50 cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" :disabled="saving || uploadingImage"
                            class="px-5 py-2 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold shadow-sm disabled:opacity-50 cursor-pointer">
                        <span x-show="!saving">Simpan ke Etalase</span>
                        <span x-show="saving">Menyimpan...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- MODAL EDIT PROFIL LAPAK                                               -->
    <!-- ===================================================================== -->
    <div x-show="showEditStoreModal"
         x-cloak
         class="fixed inset-0 z-50 !mt-0 flex items-center justify-center p-3 sm:p-4 bg-slate-900/55 backdrop-blur-[1px] overflow-y-auto">
        <div @click.away="showEditStoreModal = false"
             class="bg-white rounded-2xl shadow-xl border border-slate-200 w-full max-w-[calc(100vw-1.5rem)] sm:max-w-lg max-h-[90dvh] flex flex-col overflow-hidden my-auto">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between gap-3 shrink-0">
                <h3 class="text-base font-bold text-slate-900">Edit Profil Lapak</h3>
                <button type="button" @click="showEditStoreModal = false" class="text-slate-400 hover:text-slate-700 text-sm p-1 cursor-pointer">✕</button>
            </div>

            <form @submit.prevent="updateStoreProfile()" class="p-5 space-y-3.5 overflow-y-auto">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lapak / Usaha *</label>
                    <input type="text" x-model="editStoreForm.store_name" required maxlength="120"
                           class="w-full rounded-xl border-slate-300 text-sm">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Kategori Utama *</label>
                        <select x-model="editStoreForm.category" class="w-full rounded-xl border-slate-300 text-sm">
                            <option value="kuliner">Kuliner &amp; Minuman</option>
                            <option value="sembako">Sembako, Galon &amp; Gas</option>
                            <option value="jasa">Jasa &amp; Pertukangan</option>
                            <option value="pinjam_alat">Pinjam / Sewa Alat</option>
                            <option value="lainnya">Lainnya</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nomor WhatsApp *</label>
                        <input type="text" x-model="editStoreForm.whatsapp_number" required maxlength="20"
                               class="w-full rounded-xl border-slate-300 text-sm font-mono">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Deskripsi &amp; Jam Operasional</label>
                    <textarea x-model="editStoreForm.description" rows="3" maxlength="500"
                              class="w-full rounded-xl border-slate-300 text-sm"></textarea>
                </div>

                <div class="pt-2 flex justify-end gap-2 border-t border-slate-100">
                    <button type="button" @click="showEditStoreModal = false"
                            class="px-4 py-2 rounded-xl border border-slate-300 text-slate-700 text-xs font-semibold cursor-pointer">
                        Batal
                    </button>
                    <button type="submit" :disabled="saving"
                            class="px-5 py-2 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold shadow-sm cursor-pointer">
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function ppmtMyLapakSettings() {
    return {
        token: localStorage.getItem('ppmt_token'),
        user: null,
        store: null,
        loading: true,
        saving: false,
        uploadingImage: false,
        toastMessage: '',
        toastType: 'success',
        showItemModal: false,
        showEditStoreModal: false,
        editingItemIndex: null,

        // Daftar pilihan satuan (mudah ditambah di kemudian hari)
        unitOptions: [
            'Porsi',
            'Bungkus',
            'Kilogram',
            'Liter',
            'Galon',
            'Unit',
            'Jam',
            'Gratis',
        ],

        createForm: {
            unit_code: '',
            store_name: '',
            category: 'kuliner',
            whatsapp_number: '',
            description: ''
        },

        editStoreForm: {
            store_name: '',
            category: 'kuliner',
            whatsapp_number: '',
            description: ''
        },

        itemForm: {
            name: '',
            category: 'kuliner',
            badge_type: 'ready',
            price: 10000,
            unit_note: 'Porsi',
            delivery_tag: '',
            description: '',
            image_url: '',
            is_available: true
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

                if (this.approvedMemberships.length > 0) {
                    this.createForm.unit_code = this.approvedMemberships[0].unit.code;
                }
                if (this.user?.phone) {
                    this.createForm.whatsapp_number = this.user.phone;
                }

                await this.loadMyStore();
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

        get approvedMemberships() {
            return (this.user?.memberships || []).filter(m => m.is_approved && m.unit);
        },

        get catalogItems() {
            return Array.isArray(this.store?.catalog_items) ? this.store.catalog_items : [];
        },

        // Gabungkan opsi standar dengan nilai lama jika ada item lawas yang ditulis manual
        get availableUnitOptions() {
            const current = (this.itemForm.unit_note || '').trim();
            if (current && !this.unitOptions.includes(current)) {
                return [...this.unitOptions, current];
            }
            return this.unitOptions;
        },

        toggleUnitOption(unit) {
            this.itemForm.unit_note = this.itemForm.unit_note === unit ? '' : unit;
        },

        formatRupiah(val) {
            return 'Rp ' + Number(val || 0).toLocaleString('id-ID');
        },

        formatDateId(isoString) {
            if (!isoString) return '';
            const d = new Date(isoString);
            if (Number.isNaN(d.getTime())) return '';
            return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
        },

        categoryEmoji(cat) {
            const map = {
                kuliner: '🍱',
                sembako: '💧',
                jasa: '🛠️',
                pinjam_alat: '🪜',
                lainnya: '🏪'
            };
            return map[cat] || '🏪';
        },

        badgeLabel(type) {
            const map = {
                ready: 'Ready Stock',
                po: 'Open PO',
                service: 'Layanan Jasa',
                loan: 'Pinjam / Sewa'
            };
            return map[type] || 'Ready Stock';
        },

        async compressImageClientSide(file) {
            return new Promise((resolve) => {
                const img = new Image();
                const objectUrl = URL.createObjectURL(file);
                img.onload = () => {
                    URL.revokeObjectURL(objectUrl);
                    const maxSide = 1000;
                    let w = img.width;
                    let h = img.height;
                    if (w > maxSide || h > maxSide) {
                        if (w >= h) {
                            h = Math.round((h * maxSide) / w);
                            w = maxSide;
                        } else {
                            w = Math.round((w * maxSide) / h);
                            h = maxSide;
                        }
                    }
                    const canvas = document.createElement('canvas');
                    canvas.width = w;
                    canvas.height = h;
                    canvas.getContext('2d').drawImage(img, 0, 0, w, h);
                    canvas.toBlob((blob) => resolve(blob || file), 'image/webp', 0.82);
                };
                img.onerror = () => {
                    URL.revokeObjectURL(objectUrl);
                    resolve(file);
                };
                img.src = objectUrl;
            });
        },

        async handleItemImageSelected(event) {
            const file = event.target.files?.[0];
            event.target.value = '';
            if (!file || !this.store) return;

            if (file.size > 5 * 1024 * 1024) {
                this.toastType = 'error';
                this.toastMessage = 'Ukuran foto maksimal 5 MB.';
                return;
            }

            this.uploadingImage = true;
            try {
                const compressedBlob = await this.compressImageClientSide(file);
                const formData = new FormData();
                formData.append('image', compressedBlob, 'upload.webp');

                const res = await fetch(`/api/v1/umkm/stores/${this.store.id}/items/image`, {
                    method: 'POST',
                    headers: {
                        'Authorization': 'Bearer ' + this.token,
                        'Accept': 'application/json'
                    },
                    body: formData
                });
                const json = await res.json();
                if (res.ok && json.image_url) {
                    this.itemForm.image_url = json.image_url;
                } else {
                    this.toastType = 'error';
                    this.toastMessage = json.message || 'Gagal mengunggah foto.';
                }
            } catch (e) {
                this.toastType = 'error';
                this.toastMessage = 'Terjadi gangguan jaringan saat mengunggah foto.';
            } finally {
                this.uploadingImage = false;
            }
        },

        async loadMyStore() {
            const res = await fetch('/api/v1/umkm/my-store', { headers: this.headers() });
            if (res.ok) {
                const json = await res.json();
                this.store = json.data || null;
            }
        },

        async createStore() {
            this.saving = true;
            try {
                const res = await fetch('/api/v1/umkm/stores', {
                    method: 'POST',
                    headers: this.headers(),
                    body: JSON.stringify(this.createForm)
                });
                const json = await res.json();
                if (res.ok) {
                    this.store = json.data;
                    this.toastType = 'success';
                    this.toastMessage = json.message;
                } else {
                    this.toastType = 'error';
                    this.toastMessage = json.message || 'Gagal membuka lapak.';
                }
            } finally {
                this.saving = false;
            }
        },

        openEditStoreModal() {
            if (!this.store) return;
            this.editStoreForm = {
                store_name: this.store.store_name,
                category: this.store.category,
                whatsapp_number: this.store.whatsapp_number,
                description: this.store.description || ''
            };
            this.showEditStoreModal = true;
        },

        async updateStoreProfile() {
            if (!this.store) return;
            this.saving = true;
            try {
                const res = await fetch(`/api/v1/umkm/stores/${this.store.id}`, {
                    method: 'PATCH',
                    headers: this.headers(),
                    body: JSON.stringify(this.editStoreForm)
                });
                const json = await res.json();
                if (res.ok) {
                    this.store = json.data;
                    this.showEditStoreModal = false;
                    this.toastType = 'success';
                    this.toastMessage = json.message;
                } else {
                    this.toastType = 'error';
                    this.toastMessage = json.message || 'Gagal memperbarui profil lapak.';
                }
            } finally {
                this.saving = false;
            }
        },

        async changeStatus(status) {
            if (!this.store || this.store.status === status) return;
            const res = await fetch(`/api/v1/umkm/stores/${this.store.id}/status`, {
                method: 'PATCH',
                headers: this.headers(),
                body: JSON.stringify({ status })
            });
            const json = await res.json();
            if (res.ok) {
                this.store.status = status;
                this.toastType = 'success';
                this.toastMessage = json.message;
            }
        },

        openNewItemModal() {
            this.editingItemIndex = null;
            const defaultUnitByCat = {
                kuliner: 'Porsi',
                sembako: 'Kilogram',
                jasa: 'Unit',
                pinjam_alat: 'Gratis',
                lainnya: 'Unit',
            };
            const cat = this.store?.category || 'kuliner';
            this.itemForm = {
                name: '',
                category: cat,
                badge_type: 'ready',
                price: 10000,
                unit_note: defaultUnitByCat[cat] || 'Porsi',
                delivery_tag: '',
                description: '',
                image_url: '',
                is_available: true
            };
            this.showItemModal = true;
        },

        openEditItemModal(index) {
            const target = this.catalogItems[index];
            if (!target) return;
            this.editingItemIndex = index;
            this.itemForm = {
                id: target.id || Date.now(),
                name: target.name,
                category: target.category || 'kuliner',
                badge_type: target.badge_type || 'ready',
                price: Number(target.price || 0),
                unit_note: target.unit_note || '',
                delivery_tag: target.delivery_tag || '',
                description: target.description || '',
                image_url: target.image_url || '',
                is_available: target.is_available !== false
            };
            this.showItemModal = true;
        },

        async saveCatalogItem() {
            if (this.uploadingImage) return;
            const nextItems = [...this.catalogItems];
            const payloadItem = {
                id: this.editingItemIndex !== null ? (this.itemForm.id || Date.now()) : Date.now(),
                name: this.itemForm.name.trim(),
                category: this.itemForm.category,
                badge_type: this.itemForm.badge_type,
                price: Number(this.itemForm.price || 0),
                unit_note: this.itemForm.unit_note.trim(),
                delivery_tag: this.itemForm.delivery_tag.trim(),
                description: this.itemForm.description.trim(),
                image_url: (this.itemForm.image_url || '').trim(),
                is_available: this.itemForm.is_available !== false
            };

            if (this.editingItemIndex !== null) {
                nextItems[this.editingItemIndex] = payloadItem;
            } else {
                nextItems.unshift(payloadItem);
            }

            await this.syncCatalogItems(nextItems);
            this.showItemModal = false;
        },

        async toggleItemAvailability(index) {
            const nextItems = [...this.catalogItems];
            if (!nextItems[index]) return;
            nextItems[index] = {
                ...nextItems[index],
                is_available: nextItems[index].is_available === false
            };
            await this.syncCatalogItems(nextItems);
        },

        async removeCatalogItem(index) {
            if (!confirm('Hapus item ini dari etalase lapak?')) return;
            const nextItems = this.catalogItems.filter((_, i) => i !== index);
            await this.syncCatalogItems(nextItems);
        },

        async syncCatalogItems(items) {
            if (!this.store) return;
            this.saving = true;
            try {
                const res = await fetch(`/api/v1/umkm/stores/${this.store.id}`, {
                    method: 'PATCH',
                    headers: this.headers(),
                    body: JSON.stringify({ catalog_items: items })
                });
                const json = await res.json();
                if (res.ok) {
                    this.store = json.data;
                    this.toastType = 'success';
                    this.toastMessage = json.message;
                } else {
                    this.toastType = 'error';
                    this.toastMessage = json.message || 'Gagal menyimpan katalog.';
                }
            } finally {
                this.saving = false;
            }
        }
    }
}
</script>
@endpush
<div class="lg:col-span-5 space-y-6">

    <!-- Panel 1: Antrean Verifikasi Sosial (Live API) -->
    <section x-show="pendingApprovals.length > 0"
             class="bg-amber-50/80 rounded-2xl p-5 shadow-sm border border-amber-300 space-y-3">
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
                        <div class="text-slate-500" x-text="(item.phone || item.username || '-') + ' (' + subRoleLabel(item.sub_role) + ')'"></div>
                    </div>
                    <button type="button" @click="approveResident(item)"
                            class="px-3 py-1.5 rounded-lg bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-semibold shrink-0 cursor-pointer">
                        ✓ Sahkan
                    </button>
                </div>
            </template>
        </div>
    </section>

    <!-- Panel 2: Status Iuran Sampah & Saldo Kas Paguyuban (Live API) -->
    <section class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between gap-2">
            <div>
                <h2 class="text-base font-bold text-slate-900">Iuran Sampah Kavling</h2>
                <p class="text-xs text-slate-500 mt-0.5"
                   x-text="'Periode ' + (treasuryReport?.period_label || 'Bulan Ini') + ' • Jatuh Tempo Tgl 1'"></p>
            </div>
            <span class="px-2.5 py-1 rounded-full text-[11px] font-bold bg-slate-100 text-slate-700 border border-slate-200 shrink-0">
                Rp 20.000 / bln
            </span>
        </div>

        <div class="p-5 space-y-3">
            <template x-for="uDues in (treasuryReport?.my_units_dues || [])" :key="uDues.unit_id">
                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/80 flex items-center justify-between gap-3">
                    <div>
                        <div class="text-xs font-bold text-slate-800" x-text="'Kavling ' + uDues.unit_code"></div>
                        <div class="text-base font-extrabold text-slate-900 mt-0.5"
                             x-text="uDues.current?.status === 'exempt' ? 'Rp 0' : formatRupiah(uDues.current?.amount ?? 20000)"></div>
                    </div>
                    <div class="text-right space-y-1">
                        <span :class="{
                                'bg-emerald-100 text-emerald-900 border-emerald-200': uDues.current?.status === 'paid',
                                'bg-amber-100 text-amber-900 border-amber-300': uDues.current?.status === 'unpaid',
                                'bg-slate-200 text-slate-700 border-slate-300': uDues.current?.status === 'exempt'
                              }"
                              class="inline-block px-2.5 py-0.5 rounded-full text-[11px] font-bold border"
                              x-text="uDues.current?.status === 'paid' ? '✓ Lunas' : (uDues.current?.status === 'exempt' ? 'Dibebaskan' : 'Belum Terbayar')">
                        </span>
                        <div class="text-[11px] text-slate-500"
                             x-text="uDues.current?.paid_at ? ((uDues.current?.payment_method === 'transfer' ? 'Transfer' : 'Tunai') + ' • ' + uDues.current.paid_at) : 'Tagihan rutin tgl 1'">
                        </div>
                    </div>
                </div>
            </template>

            <div x-show="treasuryReport !== null"
                 class="px-3.5 py-2.5 rounded-xl bg-emerald-50/70 border border-emerald-200/80 flex items-center justify-between text-xs">
                <span class="text-emerald-900 font-medium">Saldo Kas Paguyuban:</span>
                <strong class="text-emerald-950 font-extrabold"
                        x-text="formatRupiah(treasuryReport?.summary?.current_balance ?? 0)"></strong>
            </div>

            <div class="grid grid-cols-2 gap-2 pt-1">
                <button type="button"
                        @click="showDuesModal = true"
                        class="w-full py-2 px-3 rounded-xl border border-slate-300 hover:bg-slate-50 text-slate-700 text-xs font-semibold transition cursor-pointer">
                    Riwayat 3 Bulan
                </button>
                <a href="/komunitas?tab=kas"
                   class="w-full py-2 px-3 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold text-center transition">
                    Laporan Kas &rarr;
                </a>
            </div>
        </div>
    </section>

    <!-- Panel 2A: Peta 91 Kavling & Direktori Lingkungan -->
    <section class="bg-gradient-to-br from-emerald-950 to-teal-900 text-white rounded-2xl p-5 shadow-sm border border-emerald-800 space-y-3">
        <div class="flex items-start justify-between gap-3">
            <div class="space-y-1">
                <span class="inline-block px-2 py-0.5 rounded-md bg-emerald-800/90 text-emerald-200 text-[10px] font-bold uppercase tracking-wider">
                    Pemetaan Internal Warga
                </span>
                <h2 class="text-base font-extrabold leading-snug">Peta 91 Kavling PPMT</h2>
                <p class="text-xs text-emerald-100/85 leading-relaxed">
                    Cek tata letak blok &amp; zona, status hunian, serta lapak Pasar Tetangga yang sedang buka.
                </p>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-2 pt-1">
            <a href="/peta-kavling"
               class="py-2 px-3 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-950 text-xs font-extrabold text-center transition shadow-xs">
                Buka Peta Kavling
            </a>
            <a x-show="approvedUnits.length > 0"
               :href="'/peta-kavling?unit=' + encodeURIComponent(approvedUnits[0]?.unit?.code || '')"
               class="py-2 px-3 rounded-xl bg-white/10 hover:bg-white/20 border border-white/15 text-white text-xs font-bold text-center transition">
                Sorot <span x-text="approvedUnits[0]?.unit?.code"></span> &rarr;
            </a>
        </div>
    </section>

    <!-- Panel 2B: Sorotan Aspirasi & Petisi Kolektif Warga -->
    <section x-show="aspirations.length > 0"
             x-cloak
             class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between gap-2">
            <div>
                <h2 class="text-base font-bold text-slate-900">Aspirasi &amp; Petisi Warga</h2>
                <p class="text-xs text-slate-500 mt-0.5">Suara kolektif kavling untuk audiensi pengembang.</p>
            </div>
            <span x-show="unsignedOpenAspirations.length > 0"
                  class="px-2.5 py-0.5 rounded-full text-[11px] font-extrabold bg-amber-100 text-amber-900 border border-amber-300/80 shrink-0"
                  x-text="unsignedOpenAspirations.length + ' Menunggu'">
            </span>
        </div>

        <!-- Mode Aktif: Tampilkan maksimal 2 petisi terbuka yang belum didukung kavling user -->
        <div x-show="unsignedOpenAspirations.length > 0" class="p-5 space-y-3">
            <template x-for="asp in unsignedOpenAspirations.slice(0, 2)" :key="asp.id">
                <a href="/komunitas?tab=aspirasi"
                   class="block p-3.5 rounded-xl bg-slate-50 hover:bg-emerald-50/70 border border-slate-200/90 hover:border-emerald-300 transition space-y-2 group">
                    <div class="flex items-center justify-between gap-2">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-800 bg-emerald-100/80 px-2 py-0.5 rounded"
                              x-text="asp.category"></span>
                        <span class="text-[11px] font-bold text-slate-600"
                              x-text="asp.signatures_count + '/' + asp.target_signatures + ' kavling'"></span>
                    </div>

                    <div class="text-xs font-bold text-slate-900 group-hover:text-emerald-900 leading-snug line-clamp-2"
                         x-text="asp.title"></div>

                    <div class="w-full h-1.5 rounded-full bg-slate-200 overflow-hidden">
                        <div class="h-full rounded-full bg-emerald-600"
                             :style="'width: ' + Math.min(100, Math.round((asp.signatures_count / Math.max(1, asp.target_signatures)) * 100)) + '%'">
                        </div>
                    </div>
                </a>
            </template>

            <a href="/komunitas?tab=aspirasi"
               class="w-full py-2.5 px-4 rounded-xl bg-emerald-700 hover:bg-emerald-800 text-white text-xs font-bold flex items-center justify-center gap-1.5 shadow-xs transition">
                <span>Tinjau &amp; Beri Dukungan Kavling</span>
                <span>&rarr;</span>
            </a>
        </div>

        <!-- Mode Ringkas: Jika kavling user sudah mendukung semua petisi terbuka -->
        <div x-show="unsignedOpenAspirations.length === 0"
             class="px-5 py-4 flex items-center justify-between gap-3 text-xs bg-slate-50/60">
            <div class="text-slate-600">
                <strong class="text-slate-800" x-text="aspirations.filter(a => ['open', 'submitted'].includes(a.status)).length + ' isu kolektif'"></strong>
                sedang dikawal bersama warga.
            </div>
            <a href="/komunitas?tab=aspirasi"
               class="font-bold text-emerald-700 hover:text-emerald-900 shrink-0">
                Lihat Semua &rarr;
            </a>
        </div>
    </section>

    <!-- Panel 3: Generator Tautan Undangan Keluarga / Penyewa (Live API) -->
    <section x-show="manageableUnits.length > 0"
             class="bg-white rounded-2xl p-5 shadow-sm border border-slate-200 space-y-4">
        <div class="border-b border-slate-100 pb-3">
            <h2 class="text-base font-bold text-slate-900">Undang Anggota Keluarga / Penyewa</h2>
            <p class="text-xs text-slate-500 mt-0.5">Buat tautan undangan berbatas waktu (48 jam) agar penghuni serumah langsung aktif tanpa antre verifikasi.</p>
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
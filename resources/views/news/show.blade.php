@extends('layouts.app')

@section('title', 'Kabar Warga — PPMT Digital')

@section('content')
<div x-data="ppmtNewsReader('{{ $year }}', '{{ $month }}', '{{ $day }}', '{{ $slug }}')"
     x-init="init()"
     class="max-w-4xl mx-auto px-4 py-6 sm:py-8 space-y-6">

    <!-- Navigasi Breadcrumb -->
    <nav class="flex items-center gap-2 text-xs text-slate-500">
        <a href="/dashboard" class="font-semibold text-emerald-700 hover:text-emerald-900">&larr; Beranda Warga</a>
        <span>/</span>
        <span>Berita</span>
        <span>/</span>
        <span class="font-mono text-slate-600" x-text="`${year}/${month}/${day}`"></span>
    </nav>

    <!-- Konten Artikel Utama -->
    <article x-show="article" class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <!-- Gambar Utama (Jika Ada) -->
        <div x-show="article?.thumbnail" class="aspect-[16/9] w-full bg-slate-100 border-b border-slate-200 overflow-hidden">
            <img :src="article?.thumbnail" :alt="article?.title" class="w-full h-full object-cover">
        </div>

        <div class="p-6 sm:p-8 space-y-5">
            <!-- Kategori & Tanggal Terbit -->
            <div class="flex flex-wrap items-center justify-between gap-2">
                <div class="flex items-center gap-2">
                    <span class="px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-800 border border-emerald-200 text-xs font-bold"
                          x-text="article?.category"></span>
                    <span class="text-xs text-slate-500" x-text="formattedDate"></span>
                </div>

                <button type="button"
                        @click="copyShareLink()"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 hover:border-emerald-300 hover:bg-emerald-50 text-xs font-semibold text-slate-700 hover:text-emerald-800 transition cursor-pointer">
                    <span x-text="copied ? '✓ Tautan Tersalin' : 'Salin Tautan'"></span>
                </button>
            </div>

            <!-- Judul Berita -->
            <h1 class="text-xl sm:text-2xl lg:text-3xl font-extrabold text-slate-900 leading-tight tracking-tight"
                x-text="article?.title"></h1>

            <!-- Info Kontributor Warga -->
            <div class="flex items-center gap-3 py-3 border-y border-slate-100">
                <div class="w-9 h-9 rounded-full bg-emerald-100 text-emerald-900 font-extrabold text-sm flex items-center justify-center shrink-0"
                     x-text="(article?.author || 'W').charAt(0).toUpperCase()"></div>
                <div class="text-xs">
                    <div class="font-bold text-slate-900" x-text="article?.author"></div>
                    <div class="text-slate-500" x-text="'Kontributor Warga • Kavling ' + article?.unit"></div>
                </div>
            </div>

            <!-- Isi Berita -->
            <div class="text-sm sm:text-base text-slate-700 leading-relaxed whitespace-pre-line space-y-4"
                 x-text="article?.body"></div>
        </div>
    </article>

    <!-- Fallback Jika Slug Tidak Ditemukan di Mockup -->
    <div x-show="!article" class="bg-white rounded-2xl p-8 border border-slate-200 text-center space-y-3">
        <h2 class="text-base font-bold text-slate-900">Artikel Tidak Ditemukan</h2>
        <p class="text-xs text-slate-500">Tautan berita mungkin sudah diperbarui atau masih dalam tahap kurasi pengurus.</p>
        <a href="/dashboard" class="inline-block px-4 py-2 rounded-xl bg-emerald-700 text-white text-xs font-bold">
            Kembali ke Beranda
        </a>
    </div>

    <!-- Berita Lainnya -->
    <section x-show="relatedNews.length > 0" class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 sm:p-6 space-y-4">
        <h2 class="text-sm font-extrabold uppercase tracking-wider text-slate-500">Kabar Warga Lainnya</h2>
        <div class="divide-y divide-slate-100">
            <template x-for="rel in relatedNews" :key="rel.id">
                <a :href="`/berita/${rel.date_path}/${rel.slug}`"
                   class="py-3 first:pt-0 last:pb-0 flex items-center justify-between gap-4 group">
                    <div class="min-w-0 flex-1 space-y-0.5">
                        <div class="text-[11px] font-bold text-emerald-700" x-text="rel.category"></div>
                        <div class="text-sm font-bold text-slate-900 group-hover:text-emerald-800 transition line-clamp-1"
                             x-text="rel.title"></div>
                        <div class="text-[11px] text-slate-500" x-text="`Oleh ${rel.author} (${rel.unit})`"></div>
                    </div>
                    <span class="text-slate-400 group-hover:text-emerald-700 font-bold">&rsaquo;</span>
                </a>
            </template>
        </div>
    </section>

</div>
@endsection

@push('scripts')
<script>
function ppmtNewsReader(year, month, day, slug) {
    return {
        year, month, day, slug,
        copied: false,
        article: null,
        relatedNews: [],

        catalog: [
            {
                id: 101,
                date_path: '2026/11/17',
                slug: 'gotong-royong-pengecatan-kanstin-dan-pemasangan-papan-nama-blok-rampung-100-persen',
                category: 'Kegiatan Warga',
                title: 'Gotong Royong Pengecatan Kanstin & Pemasangan Papan Nama Blok Rampung 100%',
                body: 'Suasana guyub terlihat sejak pukul 06.30 WIB di gerbang utama Perumahan Pesona Mandiri Turen. Warga membawa peralatan cat, kuas, serta konsumsi swadaya untuk menyelesaikan pengecatan kanstin jalan utama dan pemasangan penunjuk blok.\n\nKoordinator lapangan menyampaikan bahwa kegiatan ini merupakan hasil kesepakatan musyawarah bulanan agar tamu maupun kurir paket lebih mudah menemukan alamat kavling.',
                thumbnail: 'https://images.unsplash.com/photo-1593113598332-cd288d649433?auto=format&fit=crop&w=900&q=80',
                author: 'Hendra Wijaya',
                unit: 'A-08'
            },
            {
                id: 102,
                date_path: '2026/11/16',
                slug: 'uji-coba-penerangan-lampu-led-tenaga-surya-di-area-taman-dan-musholla',
                category: 'Fasilitas & Lingkungan',
                title: 'Uji Coba Penerangan Lampu LED Tenaga Surya di Area Taman & Musholla',
                body: 'Tim sarana prasarana paguyuban telah merampungkan instalasi 6 unit lampu penerangan jalan berbasis panel surya di sekitar fasum musholla dan taman bermain anak. Lampu menyala otomatis sejak pukul 17.45 WIB hingga subuh.',
                thumbnail: 'https://images.unsplash.com/photo-1513828583688-c52646db42da?auto=format&fit=crop&w=600&q=80',
                author: 'Rizky Pratama',
                unit: 'B-14'
            },
            {
                id: 103,
                date_path: '2026/11/15',
                slug: 'evaluasi-sistem-satu-pintu-malam-hari-dan-penambahan-cermin-cembung-tikungan',
                category: 'Keamanan & Tertib',
                title: 'Evaluasi Sistem Satu Pintu Malam Hari & Penambahan Cermin Cembung Tikungan',
                body: 'Menindaklanjuti masukan warga di forum aspirasi, pengurus memasang cermin cembung (convex mirror) di persimpangan Blok B dan Blok C serta menertibkan jam tutup portal sekunder mulai pukul 23.00 WIB.',
                thumbnail: 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&w=600&q=80',
                author: 'Bambang Sutrisno',
                unit: 'C-03'
            },
            {
                id: 104,
                date_path: '2026/11/14',
                slug: 'jadwal-kajian-rutin-malam-jumat-dan-persiapan-santunan-anak-yatim',
                category: 'Sosial & Musholla',
                title: 'Jadwal Kajian Rutin Malam Jumat & Persiapan Santunan Anak Yatim',
                body: 'Takmir Musholla PPMT mengundang seluruh jamaah untuk hadir dalam kajian rutin ba’da Maghrib sekaligus koordinasi panitia santunan bulanan warga.',
                thumbnail: 'https://images.unsplash.com/photo-1564769625905-50e93615e769?auto=format&fit=crop&w=600&q=80',
                author: 'Ust. Ahmad Fauzi',
                unit: 'B-02'
            },
            {
                id: 105,
                date_path: '2026/11/13',
                slug: 'panen-perdana-kebun-hidroponik-kelompok-ibu-ibu-pkk-blok-a',
                category: 'Kabar Tetangga',
                title: 'Panen Perdana Kebun Hidroponik Kelompok Ibu-Ibu PKK Blok A',
                body: 'Memanfaatkan lahan kosong di samping pos kamling Blok A, kelompok PKK berhasil memanen 18 kilogram sayuran hidroponik bebas pestisida pada panen perdana pekan ini.',
                thumbnail: 'https://images.unsplash.com/photo-1530836369250-ef72a3f5cda8?auto=format&fit=crop&w=600&q=80',
                author: 'Ibu Ratna Dewi',
                unit: 'A-19'
            },
            {
                id: 106,
                date_path: '2026/11/13',
                slug: 'jadwal-pengangkutan-sampah-rumah-tangga-pekan-ini-tetap-normal-selasa-dan-jumat-pagi',
                category: 'Info Lingkungan',
                title: 'Jadwal Pengangkutan Sampah Rumah Tangga Pekan Ini Tetap Normal Selasa & Jumat Pagi',
                body: 'Petugas kebersihan menginformasikan bahwa jadwal pengangkutan sampah domestik tetap berjalan normal pada hari Selasa dan Jumat mulai pukul 06.30 WIB.',
                thumbnail: null,
                author: 'Sekretariat Paguyuban',
                unit: 'A-01'
            },
            {
                id: 107,
                date_path: '2026/11/12',
                slug: 'selamat-datang-keluarga-baru-bapak-dimas-dan-ibu-nisa-di-kavling-c-17',
                category: 'Kabar Tetangga',
                title: 'Selamat Datang Keluarga Baru Bapak Dimas & Ibu Nisa di Kavling C-17',
                body: 'Keluarga Bapak Dimas resmi menempati Kavling C-17 mulai pekan ini. Semoga betah dan senantiasa rukun bertetangga di Perumahan Pesona Mandiri Turen.',
                thumbnail: null,
                author: 'Koordinator Zona C',
                unit: 'C-01'
            }
        ],

        init() {
            const targetPath = `${this.year}/${this.month}/${this.day}`;
            this.article = this.catalog.find(item =>
                item.date_path === targetPath && item.slug === this.slug
            ) || null;

            this.relatedNews = this.catalog
                .filter(item => item.slug !== this.slug)
                .slice(0, 3);
        },

        get formattedDate() {
            const months = {
                '01': 'Januari', '02': 'Februari', '03': 'Maret', '04': 'April',
                '05': 'Mei', '06': 'Juni', '07': 'Juli', '08': 'Agustus',
                '09': 'September', '10': 'Oktober', '11': 'November', '12': 'Desember'
            };
            return `${parseInt(this.day, 10)} ${months[this.month] || this.month} ${this.year}`;
        },

        async copyShareLink() {
            await navigator.clipboard.writeText(window.location.href);
            this.copied = true;
            setTimeout(() => { this.copied = false; }, 2000);
        }
    };
}
</script>
@endpush
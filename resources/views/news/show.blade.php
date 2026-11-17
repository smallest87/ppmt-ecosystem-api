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

    <!-- Loading State -->
    <div x-show="loading" class="bg-white rounded-2xl p-12 text-center text-slate-500 border border-slate-200 text-xs">
        Memuat kabar warga PPMT...
    </div>

    <!-- Konten Artikel Utama -->
    <article x-show="!loading && article" class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
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
                    <span class="text-xs text-slate-500" x-text="article?.published_date_id || formattedDate"></span>
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

            <!-- Info Pengurus Penulis -->
            <div class="flex items-center gap-3 py-3 border-y border-slate-100">
                <div class="w-9 h-9 rounded-full bg-emerald-100 text-emerald-900 font-extrabold text-sm flex items-center justify-center shrink-0"
                     x-text="(article?.author || 'P').charAt(0).toUpperCase()"></div>
                <div class="text-xs">
                    <div class="font-bold text-slate-900" x-text="article?.author"></div>
                    <div class="text-slate-500" x-text="article?.author_role || 'Pengurus Paguyuban'"></div>
                </div>
            </div>

            <!-- Isi Berita -->
            <div class="text-sm sm:text-base text-slate-700 leading-relaxed whitespace-pre-line space-y-4"
                 x-text="article?.body"></div>
        </div>
    </article>

    <!-- Fallback Jika Tidak Ditemukan / Akun Belum Terverifikasi -->
    <div x-show="!loading && !article" class="bg-white rounded-2xl p-8 border border-slate-200 text-center space-y-3">
        <h2 class="text-base font-bold text-slate-900" x-text="errorTitle"></h2>
        <p class="text-xs text-slate-500" x-text="errorMessage"></p>
        <a href="/dashboard" class="inline-block px-4 py-2 rounded-xl bg-emerald-700 text-white text-xs font-bold">
            Kembali ke Beranda
        </a>
    </div>

    <!-- Berita Lainnya -->
    <section x-show="!loading && relatedNews.length > 0" class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 sm:p-6 space-y-4">
        <h2 class="text-sm font-extrabold uppercase tracking-wider text-slate-500">Kabar Warga Lainnya</h2>
        <div class="divide-y divide-slate-100">
            <template x-for="rel in relatedNews" :key="rel.id">
                <a :href="`/berita/${rel.date_path}/${rel.slug}`"
                   class="py-3 first:pt-0 last:pb-0 flex items-center justify-between gap-4 group">
                    <div class="min-w-0 flex-1 space-y-0.5">
                        <div class="text-[11px] font-bold text-emerald-700" x-text="rel.category"></div>
                        <div class="text-sm font-bold text-slate-900 group-hover:text-emerald-800 transition line-clamp-1"
                             x-text="rel.title"></div>
                        <div class="text-[11px] text-slate-500" x-text="`${rel.author} • ${rel.author_role}`"></div>
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
        token: localStorage.getItem('ppmt_token'),
        year, month, day, slug,
        loading: true,
        copied: false,
        article: null,
        relatedNews: [],
        errorTitle: 'Artikel Tidak Ditemukan',
        errorMessage: 'Tautan berita mungkin sudah diperbarui atau dihapus oleh pengurus.',

        async init() {
            if (!this.token) {
                window.location.href = '/';
                return;
            }

            try {
                const res = await fetch(`/api/v1/news/${this.year}/${this.month}/${this.day}/${this.slug}`, {
                    headers: {
                        'Authorization': 'Bearer ' + this.token,
                        'Accept': 'application/json'
                    }
                });

                if (res.status === 401) {
                    localStorage.removeItem('ppmt_token');
                    window.location.href = '/';
                    return;
                }

                const json = await res.json();

                if (res.status === 403) {
                    this.errorTitle = 'Akses Khusus Warga Terverifikasi';
                    this.errorMessage = json.message || 'Akun Anda harus terverifikasi sebagai warga PPMT untuk membaca kabar internal lingkungan.';
                    return;
                }

                if (res.ok && json.data?.article) {
                    this.article = json.data.article;
                    this.relatedNews = json.data.related || [];
                    document.title = `${this.article.title} — PPMT Digital`;
                }
            } catch (e) {
                this.errorTitle = 'Gagal Memuat Berita';
                this.errorMessage = 'Terjadi gangguan jaringan saat mengambil artikel.';
            } finally {
                this.loading = false;
            }
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
@extends('layouts.app')

@section('title', 'Beranda Warga — PPMT Digital')

@section('content')
<div x-data="ppmtFamilyRoom()"
    x-init="init()"
    x-effect="document.body.classList.toggle('overflow-hidden', Boolean(selectedAnnouncement || showDuesModal || showContributeModal))"
    @keydown.escape.window="selectedAnnouncement = null; showDuesModal = false; showContributeModal = false"
    class="max-w-6xl mx-auto px-4 py-6 sm:py-8 space-y-6">

    <!-- Loading State -->
    <div x-show="loading" class="bg-white rounded-2xl p-12 text-center text-slate-500 border border-slate-200">
        Memuat beranda warga PPMT...
    </div>

    <div x-show="!loading && user" x-cloak class="space-y-6">

        <!-- Notifikasi Global -->
        <div x-show="toastMessage"
             x-cloak
             :class="toastType === 'error' ? 'bg-rose-50 text-rose-800 border-rose-200' : 'bg-emerald-50 text-emerald-800 border-emerald-200'"
             class="p-4 rounded-xl border text-sm font-medium flex justify-between items-center shadow-sm">
            <span x-text="toastMessage"></span>
            <button type="button" @click="toastMessage = ''" class="text-xs opacity-70 hover:opacity-100 cursor-pointer">✕</button>
        </div>

        <!-- Banner Peringatan Akun Pending -->
        <div x-show="!user?.is_verified"
             x-cloak
             class="bg-amber-50 border-l-4 border-amber-500 p-5 rounded-r-2xl shadow-sm space-y-1">
            <h2 class="font-bold text-amber-900 text-base">⏳ Akun Anda Menunggu Verifikasi Sosial Tetangga</h2>
            <p class="text-xs sm:text-sm text-amber-800 leading-relaxed">
                Demi keamanan lingkungan PPMT, akses ke Direktori Warga, Peta Kavling, dan Pasar Tetangga baru dibuka setelah klaim kavling Anda disahkan oleh <strong>Koordinator Zona/Blok</strong> terkait atau <strong>Pemilik Utama</strong> unit Anda.
            </p>
        </div>

        <!-- Konten Utama (Hanya aktif jika sudah terverifikasi) -->
        <div x-show="user?.is_verified" x-cloak class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

            <!-- KOLOM KIRI (7 Kolom): KABAR PAGUYUBAN & AGENDA LINGKUNGAN -->
            <div class="lg:col-span-7 space-y-6">
                @include('partials.dashboard.announcements')
                @include('partials.dashboard.agendas')
                @include('partials.dashboard.news-feed')
            </div>

            <!-- KOLOM KANAN (5 Kolom): VERIFIKASI, IURAN UNIT & UNDANGAN -->
            @include('partials.dashboard.sidebar-actions')

        </div>
    </div>

    <!-- Modal Interaktif Beranda Warga -->
    @include('partials.dashboard.modals')

</div>
@endsection

@push('scripts')
    @include('partials.dashboard.scripts')
@endpush
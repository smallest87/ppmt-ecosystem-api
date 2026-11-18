@extends('layouts.app')

@section('title', 'Publikasi, Agenda & Notulensi — Panel Pengurus PPMT')
@section('subpage_title', 'Publikasi, Agenda & Notulensi')
@section('subpage_back_url', '/pengurus')

@section('content')
<div x-data="ppmtEditorialPanel()"
     x-init="init()"
     x-effect="document.body.classList.toggle('overflow-hidden', Boolean(showCreateAnnouncement || showCreateAgenda || showCreateMinute || editingAnnouncement || editingNews || editingAgenda || editingMinute))"
     @keydown.escape.window="closeAllModals(); openMenuKey = null"
     class="max-w-6xl mx-auto px-4 py-6 sm:py-8 space-y-6">

    <!-- Loading State -->
    <div x-show="loading" class="bg-white rounded-2xl p-12 text-center text-sm text-slate-500 border border-slate-200">
        Memuat papan pengumuman, kabar warga, agenda &amp; arsip notulensi...
    </div>

    <div x-show="!loading && user" x-cloak class="space-y-6">

        <!-- Notifikasi Global -->
        <div x-show="toastMessage"
             x-cloak
             :class="toastType === 'error' ? 'bg-rose-50 text-rose-800 border-rose-200' : 'bg-emerald-50 text-emerald-800 border-emerald-200'"
             class="p-4 rounded-xl border text-sm font-medium flex justify-between items-center shadow-sm">
            <span x-text="toastMessage"></span>
            <button type="button" @click="toastMessage = ''" class="text-sm opacity-70 hover:opacity-100 cursor-pointer">✕</button>
        </div>

        <!-- Navigasi Menu Tab -->
        <nav aria-label="Menu Publikasi, Agenda & Notulensi"
             class="flex flex-wrap items-center gap-2 border-b border-slate-200 pb-3">
            <button type="button"
                    @click="switchTab('publikasi')"
                    :class="activeTab === 'publikasi'
                        ? 'bg-slate-900 text-white shadow-xs'
                        : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-50'"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition cursor-pointer">
                <span>Publikasi (Pengumuman &amp; Berita)</span>
                <span :class="activeTab === 'publikasi' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600'"
                      class="px-2 py-0.5 rounded-full text-xs font-extrabold"
                      x-text="announcements.length + newsArticles.length"></span>
            </button>

            <button type="button"
                    @click="switchTab('agenda')"
                    :class="activeTab === 'agenda'
                        ? 'bg-slate-900 text-white shadow-xs'
                        : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-50'"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition cursor-pointer">
                <span>Agenda &amp; Jadwal</span>
                <span :class="activeTab === 'agenda' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600'"
                      class="px-2 py-0.5 rounded-full text-xs font-extrabold"
                      x-text="agendas.length"></span>
            </button>

            <button type="button"
                    @click="switchTab('notulensi')"
                    :class="activeTab === 'notulensi'
                        ? 'bg-slate-900 text-white shadow-xs'
                        : 'bg-white text-slate-700 border border-slate-200 hover:bg-slate-50'"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs sm:text-sm font-bold transition cursor-pointer">
                <span>Arsip Notulensi &amp; Tatib</span>
                <span :class="activeTab === 'notulensi' ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600'"
                      class="px-2 py-0.5 rounded-full text-xs font-extrabold"
                      x-text="meetingMinutes.length"></span>
            </button>
        </nav>

        <!-- Konten Tab -->
        @include('pengurus.partials.publikasi.tab-publikasi')
        @include('pengurus.partials.publikasi.tab-agenda')
        @include('pengurus.partials.publikasi.tab-notulensi')
    </div>

    <!-- Modal Tambah & Edit -->
    @include('pengurus.partials.publikasi.modals-create')
    @include('pengurus.partials.publikasi.modals-edit')
</div>

@push('scripts')
    @include('pengurus.partials.publikasi.scripts')
@endpush
@endsection
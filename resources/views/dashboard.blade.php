@extends('layouts.app')

@section('content')
<div x-data="{ user: null, loading: true }"
     x-init="
        const token = localStorage.getItem('ppmt_token');
        if (!token) { window.location.href = '/'; return; }
        fetch('/api/v1/me', { headers: { 'Authorization': 'Bearer ' + token, 'Accept': 'application/json' } })
            .then(r => r.ok ? r.json() : Promise.reject())
            .then(j => { user = j.data; loading = false; })
            .catch(() => { localStorage.removeItem('ppmt_token'); window.location.href = '/'; });
     "
     class="max-w-4xl mx-auto px-4 py-10">

    <div x-show="loading" class="text-center text-slate-500 py-12">Memuat profil warga PPMT...</div>

    <div x-show="!loading && user" class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200 space-y-4">
        <div class="flex items-center justify-between border-b pb-4">
            <div>
                <h1 class="text-xl font-bold text-slate-900" x-text="'Halo, ' + user?.name"></h1>
                <p class="text-xs text-slate-500" x-text="'Peran Sistem: ' + user?.global_role"></p>
            </div>
            <span :class="user?.is_verified ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'"
                  class="px-3 py-1 rounded-full text-xs font-bold"
                  x-text="user?.is_verified ? '✓ Warga PPMT Terverifikasi' : '⏳ Menunggu Verifikasi Sosial'">
            </span>
        </div>

        <p class="text-sm text-slate-600">
            Selamat datang di Portal Internal Warga PPMT. Modul Profil, Privasi, Undangan Unit, dan Pasar Tetangga siap dibangun pada tahap berikutnya.
        </p>
    </div>
</div>
@endsection
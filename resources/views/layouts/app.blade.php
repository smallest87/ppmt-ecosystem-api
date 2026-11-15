<!DOCTYPE html>
<html lang="id" class="overflow-x-hidden">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Paguyuban Warga PPMT — Perumahan Pesona Mandiri Turen')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen flex flex-col overflow-x-hidden">

    <!-- Navbar Utama -->
    <header class="bg-emerald-950 text-white shadow-md sticky top-0 z-40"
            x-data="ppmtNavbar()" x-init="init()" @click.away="mobileMenuOpen = false">
        <div class="max-w-6xl mx-auto px-4 py-3.5 flex items-center justify-between gap-3">
            <!-- Brand / Logo -->
            <a href="/" class="flex items-center gap-2.5 min-w-0">
                <div class="w-9 h-9 rounded-lg bg-emerald-500 flex items-center justify-center font-bold text-white shadow shrink-0">
                    PM
                </div>
                <div class="min-w-0">
                    <div class="font-bold text-base leading-tight tracking-wide truncate">PPMT Digital</div>
                    <div class="text-[11px] text-emerald-200 truncate">Perumahan Pesona Mandiri Turen</div>
                </div>
            </a>

            <!-- Navigasi Desktop (md ke atas) -->
            <nav class="hidden md:flex items-center gap-2.5 text-sm font-medium shrink-0">
                <template x-if="!hasToken">
                    <div class="flex items-center gap-3">
                        <a href="#kegiatan-warga" class="px-3 py-1.5 rounded-lg text-emerald-100 hover:bg-emerald-900 transition">
                            Kegiatan Warga
                        </a>
                        <a href="#lingkungan" class="px-3 py-1.5 rounded-lg text-emerald-100 hover:bg-emerald-900 transition">
                            Profil Lingkungan
                        </a>
                        <button type="button"
                                @click="window.dispatchEvent(new CustomEvent('open-warga-modal', { detail: 'choose' }))"
                                class="px-4 py-2 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-950 font-bold transition shadow-sm cursor-pointer">
                            Saya Warga Perumahan
                        </button>
                    </div>
                </template>

                <template x-if="hasToken">
                    <div class="flex items-center gap-2.5">
                        <a href="/dashboard"
                           class="px-3 py-1.5 rounded-lg hover:bg-emerald-900 transition {{ request()->is('dashboard') ? 'bg-emerald-900 text-white font-semibold' : 'text-emerald-100' }}">
                            Portal Warga
                        </a>
                        <a href="/komunitas"
                           class="px-3 py-1.5 rounded-lg hover:bg-emerald-900 transition {{ request()->is('komunitas') ? 'bg-emerald-900 text-white font-semibold' : 'text-emerald-100' }}">
                            Komunitas &amp; Peta
                        </a>
                        <a x-show="hasAdminAccess" x-cloak href="/admin"
                           class="px-3 py-1.5 rounded-lg transition flex items-center gap-1.5 {{ request()->is('admin') ? 'bg-amber-400 text-slate-950 font-bold' : 'bg-indigo-600/90 hover:bg-indigo-600 text-white font-semibold' }}">
                            <span>&#9733; Admin</span>
                        </a>
                        <button @click="logout()"
                                class="px-3 py-1.5 rounded-lg bg-rose-600/90 hover:bg-rose-600 text-xs transition cursor-pointer">
                            Keluar
                        </button>
                    </div>
                </template>
            </nav>

            <!-- Kontrol Mobile (< md) -->
            <div class="flex md:hidden items-center gap-2 shrink-0">
                <template x-if="!hasToken">
                    <button type="button"
                            @click="window.dispatchEvent(new CustomEvent('open-warga-modal', { detail: 'choose' }))"
                            class="px-3 py-1.5 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-950 text-xs font-bold transition shadow-sm cursor-pointer">
                        Masuk Warga
                    </button>
                </template>

                <template x-if="hasToken">
                    <button type="button"
                            @click="mobileMenuOpen = !mobileMenuOpen"
                            aria-label="Buka Menu Navigasi"
                            class="p-2 rounded-lg bg-emerald-900/80 hover:bg-emerald-800 text-emerald-100 border border-emerald-800 cursor-pointer">
                        <svg x-show="!mobileMenuOpen" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                        <svg x-show="mobileMenuOpen" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </template>
            </div>
        </div>

        <!-- Drawer Menu Mobile (Hanya muncul saat tombol menu diklik) -->
        <div x-show="hasToken && mobileMenuOpen"
             x-cloak
             class="md:hidden border-t border-emerald-900 bg-emerald-950/95 px-4 py-3 space-y-1.5 text-sm font-medium">
            <a href="/dashboard"
               class="block px-3.5 py-2.5 rounded-xl transition {{ request()->is('dashboard') ? 'bg-emerald-900 text-white font-semibold' : 'text-emerald-100 hover:bg-emerald-900/70' }}">
                Portal Warga
            </a>
            <a href="/komunitas"
               class="block px-3.5 py-2.5 rounded-xl transition {{ request()->is('komunitas') ? 'bg-emerald-900 text-white font-semibold' : 'text-emerald-100 hover:bg-emerald-900/70' }}">
                Komunitas &amp; Peta
            </a>
            <a x-show="hasAdminAccess" x-cloak href="/admin"
               class="block px-3.5 py-2.5 rounded-xl transition {{ request()->is('admin') ? 'bg-amber-400 text-slate-950 font-bold' : 'bg-indigo-600/90 text-white font-semibold' }}">
                &#9733; Panel Pengurus / Admin
            </a>
            <div class="pt-1.5 border-t border-emerald-900/80">
                <button type="button" @click="logout()"
                        class="w-full text-left px-3.5 py-2.5 rounded-xl bg-rose-600/90 hover:bg-rose-600 text-white text-xs font-semibold transition cursor-pointer">
                    Keluar dari Akun
                </button>
            </div>
        </div>
    </header>

    <!-- Konten Halaman -->
    <main class="flex-1 w-full">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-slate-900 text-slate-400 text-xs py-6 mt-12">
        <div class="max-w-6xl mx-auto px-4 flex flex-col sm:flex-row justify-between items-center gap-2 text-center sm:text-left">
            <div>&copy; {{ date('Y') }} Paguyuban Warga Perumahan Pesona Mandiri Turen (PPMT).</div>
            <div>Poncokusumo &bull; Turen, Kabupaten Malang</div>
        </div>
    </footer>

    <script>
    function ppmtNavbar() {
        return {
            hasToken: !!localStorage.getItem('ppmt_token'),
            hasAdminAccess: sessionStorage.getItem('ppmt_has_admin') === '1',
            mobileMenuOpen: false,

            async init() {
                const token = localStorage.getItem('ppmt_token');
                if (!token) return;

                try {
                    const res = await fetch('/api/v1/me', {
                        headers: {
                            'Authorization': 'Bearer ' + token,
                            'Accept': 'application/json'
                        }
                    });
                    if (res.ok) {
                        const json = await res.json();
                        this.hasAdminAccess = Boolean(json.data?.has_admin_access);
                        sessionStorage.setItem('ppmt_has_admin', this.hasAdminAccess ? '1' : '0');
                    }
                } catch (e) {
                    // Abaikan gangguan jaringan sementara
                }
            },

            logout() {
                localStorage.removeItem('ppmt_token');
                sessionStorage.removeItem('ppmt_has_admin');
                window.location.href = '/';
            }
        }
    }
    </script>

</body>
</html>
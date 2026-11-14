<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Paguyuban Warga PPMT — Perumahan Pesona Mandiri Turen')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen flex flex-col">

    <!-- Navbar Utama -->
    <header class="bg-emerald-950 text-white shadow-md sticky top-0 z-40"
            x-data="ppmtNavbar()" x-init="init()">
        <div class="max-w-6xl mx-auto px-4 py-3.5 flex items-center justify-between">
            <a href="/" class="flex items-center gap-2.5">
                <div class="w-9 h-9 rounded-lg bg-emerald-500 flex items-center justify-center font-bold text-white shadow">
                    PM
                </div>
                <div>
                    <div class="font-bold text-base leading-tight tracking-wide">PPMT Digital</div>
                    <div class="text-[11px] text-emerald-200">Perumahan Pesona Mandiri Turen</div>
                </div>
            </a>

            <nav class="flex items-center gap-2 sm:gap-3 text-sm font-medium">
                <template x-if="!hasToken">
                    <div class="flex items-center gap-3">
                        <a href="#kegiatan-warga" class="hidden md:inline-block px-3 py-1.5 rounded-lg text-emerald-100 hover:bg-emerald-900 transition">
                            Kegiatan Warga
                        </a>
                        <a href="#lingkungan" class="hidden md:inline-block px-3 py-1.5 rounded-lg text-emerald-100 hover:bg-emerald-900 transition">
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
                    <div class="flex items-center gap-1.5 sm:gap-2.5">
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
        </div>
    </header>

    <!-- Konten Halaman -->
    <main class="flex-1">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-slate-900 text-slate-400 text-xs py-6 mt-12">
        <div class="max-w-6xl mx-auto px-4 flex flex-col sm:flex-row justify-between items-center gap-2">
            <div>&copy; {{ date('Y') }} Paguyuban Warga Perumahan Pesona Mandiri Turen (PPMT).</div>
            <div>Poncokusumo &bull; Turen, Kabupaten Malang</div>
        </div>
    </footer>

    <script>
    function ppmtNavbar() {
        return {
            hasToken: !!localStorage.getItem('ppmt_token'),
            hasAdminAccess: sessionStorage.getItem('ppmt_has_admin') === '1',

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
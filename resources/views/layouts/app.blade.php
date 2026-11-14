<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Ekosistem Digital PPMT — Perumahan Pesona Mandiri Turen')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
<body class="bg-slate-50 text-slate-800 antialiased min-h-screen flex flex-col">

    <!-- Navbar Utama -->
    <header class="bg-emerald-900 text-white shadow-md sticky top-0 z-40"
            x-data="{ hasToken: !!localStorage.getItem('ppmt_token') }">
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

            <nav class="flex items-center gap-3 text-sm font-medium">
                <template x-if="!hasToken">
                    <div class="flex items-center gap-2">
                        <a href="#peta-kavling" class="hidden sm:inline-block px-3 py-1.5 rounded-lg hover:bg-emerald-800 transition">
                            Peta 91 Kavling
                        </a>
                        <a href="#akses-warga" class="px-3.5 py-1.5 rounded-lg bg-amber-500 hover:bg-amber-400 text-slate-950 font-semibold transition shadow-sm">
                            Masuk / Klaim Unit
                        </a>
                    </div>
                </template>

                <template x-if="hasToken">
                    <div class="flex items-center gap-2">
                        <a href="/dashboard" class="px-3.5 py-1.5 rounded-lg bg-emerald-700 hover:bg-emerald-600 transition">
                            Portal Warga
                        </a>
                        <button @click="localStorage.removeItem('ppmt_token'); window.location.href='/'"
                                class="px-3 py-1.5 rounded-lg bg-rose-600/90 hover:bg-rose-600 text-xs transition">
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
            <div>Arsitektur API-First &bull; Keamanan Privasi AES-256</div>
        </div>
    </footer>

</body>
</html>
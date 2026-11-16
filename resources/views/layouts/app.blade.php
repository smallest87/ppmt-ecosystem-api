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

    @hasSection('subpage_title')
        <!-- Sub-Page Header (Layar Penuh Terfokus tanpa Navbar Utama) -->
        <header class="bg-white text-slate-900 border-b border-slate-200 sticky top-0 z-40">
            <div class="max-w-6xl mx-auto px-4 h-14 flex items-center gap-3">
                <button type="button"
                        onclick="if (document.referrer && document.referrer.indexOf(window.location.origin) === 0) { window.history.back(); } else { window.location.href = '{{ View::yieldContent('subpage_back_url', '/dashboard') }}'; }"
                        aria-label="Kembali"
                        title="Kembali"
                        class="w-9 h-9 -ml-1.5 rounded-xl flex items-center justify-center text-slate-700 hover:bg-slate-100 active:bg-slate-200 transition cursor-pointer shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>
                <div class="min-w-0 flex-1">
                    <h1 class="text-base sm:text-lg font-bold text-slate-900 tracking-tight truncate">
                        @yield('subpage_title')
                    </h1>
                </div>
            </div>
        </header>
    @else
        <!-- Navbar Utama -->
        <header class="bg-emerald-950 text-white shadow-md sticky top-0 z-40"
                x-data="ppmtNavbar()"
                x-init="init()"
                @keydown.escape.window="mobileMenuOpen = false; accountMenuOpen = false">
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

                <!-- Navigasi Belum Login (Desktop & Mobile) -->
                <template x-if="!hasToken">
                    <nav class="flex items-center gap-3 text-sm font-medium shrink-0">
                        <a href="#kegiatan-warga" class="hidden md:inline-block px-3 py-1.5 rounded-lg text-emerald-100 hover:bg-emerald-900 transition">
                            Kegiatan Warga
                        </a>
                        <a href="#lingkungan" class="hidden md:inline-block px-3 py-1.5 rounded-lg text-emerald-100 hover:bg-emerald-900 transition">
                            Profil Lingkungan
                        </a>
                        <button type="button"
                                @click="window.dispatchEvent(new CustomEvent('open-warga-modal', { detail: 'login' }))"
                                class="px-4 py-2 rounded-xl bg-amber-400 hover:bg-amber-300 text-slate-950 text-xs sm:text-sm font-bold transition shadow-sm cursor-pointer">
                            Masuk
                        </button>
                    </nav>
                </template>

                <!-- Navigasi Sudah Login: Desktop (md ke atas) -->
                <template x-if="hasToken">
                    <div class="hidden md:flex items-center gap-3 shrink-0">
                        <nav class="flex items-center gap-1.5 text-sm font-medium">
                            <a href="/dashboard"
                               class="px-3 py-1.5 rounded-lg hover:bg-emerald-900 transition {{ request()->is('dashboard') ? 'bg-emerald-900 text-white font-semibold' : 'text-emerald-100' }}">
                                Portal Warga
                            </a>
                            <a href="/komunitas"
                               class="px-3 py-1.5 rounded-lg hover:bg-emerald-900 transition {{ request()->is('komunitas') ? 'bg-emerald-900 text-white font-semibold' : 'text-emerald-100' }}">
                                Komunitas &amp; Peta
                            </a>
                        </nav>

                        <!-- Tombol Menu Akun Desktop (Paling Kanan) -->
                        <div class="relative" @click.away="accountMenuOpen = false">
                            <button type="button"
                                    @click="accountMenuOpen = !accountMenuOpen"
                                    aria-label="Menu Akun"
                                    title="Menu Akun"
                                    class="flex items-center gap-1.5 pl-1 pr-2 py-1 rounded-full bg-emerald-900/90 hover:bg-emerald-800 border border-emerald-700/80 transition cursor-pointer focus:outline-none">
                                <template x-if="profile.avatar_url">
                                    <img :src="profile.avatar_url"
                                         :alt="profile.name"
                                         class="w-8 h-8 rounded-full object-cover border border-emerald-400/50">
                                </template>
                                <template x-if="!profile.avatar_url">
                                    <span class="w-8 h-8 rounded-full bg-emerald-600 text-white text-xs font-extrabold flex items-center justify-center tracking-wider shadow-inner"
                                          x-text="userInitials">
                                    </span>
                                </template>
                                <svg class="w-3.5 h-3.5 text-emerald-200 transition-transform duration-150"
                                     :class="accountMenuOpen ? 'rotate-180' : ''"
                                     fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>

                            <!-- Dropdown Menu Akun Desktop -->
                            <div x-show="accountMenuOpen"
                                 x-cloak
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="opacity-0 scale-95"
                                 x-transition:enter-end="opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="opacity-100 scale-100"
                                 x-transition:leave-end="opacity-0 scale-95"
                                 class="absolute right-0 mt-2 w-72 rounded-2xl bg-white text-slate-800 shadow-xl border border-slate-200 overflow-hidden z-50">

                                <!-- Identitas, Nama Kavling (sejajar kanan), & Level Warga -->
                                <div class="px-4 py-3.5 bg-slate-50/90 border-b border-slate-100 flex items-center gap-3">
                                    <template x-if="profile.avatar_url">
                                        <img :src="profile.avatar_url"
                                             :alt="profile.name"
                                             class="w-10 h-10 rounded-full object-cover border border-slate-200 shrink-0">
                                    </template>
                                    <template x-if="!profile.avatar_url">
                                        <span class="w-10 h-10 rounded-full bg-emerald-600 text-white text-xs font-extrabold flex items-center justify-center tracking-wider shrink-0"
                                              x-text="userInitials">
                                        </span>
                                    </template>
                                    <div class="min-w-0 flex-1 space-y-1">
                                        <div class="flex items-center justify-between gap-2">
                                            <div class="text-sm font-bold text-slate-900 leading-snug truncate"
                                                 x-text="profile.name || 'Warga PPMT'"></div>
                                            <span x-show="profile.unit_codes"
                                                  x-text="profile.unit_codes"
                                                  class="px-2 py-0.5 rounded-md text-[11px] font-bold font-mono bg-slate-200/80 text-slate-800 border border-slate-300/80 shrink-0">
                                            </span>
                                        </div>
                                        <div>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-900 border border-emerald-200"
                                                  x-text="'Lv. ' + profile.level + (profile.level_label ? ' • ' + profile.level_label : '')">
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Item Menu Akun -->
                                <div class="py-1.5 text-xs font-semibold divide-y divide-slate-100 border-b border-slate-100">
                                    <a href="/pengaturan"
                                       class="px-4 py-2.5 flex items-center justify-between transition {{ request()->is('pengaturan') ? 'bg-emerald-50 text-emerald-900 font-bold' : 'text-slate-700 hover:bg-slate-50' }}">
                                        <span>Pengaturan Akun</span>
                                        <span class="text-slate-400 text-sm">&rsaquo;</span>
                                    </a>
                                    <a x-show="hasAdminAccess"
                                       x-cloak
                                       href="/admin"
                                       class="px-4 py-2.5 flex items-center justify-between text-indigo-700 hover:bg-indigo-50 transition">
                                        <span class="flex items-center gap-2">
                                            <span class="text-sm">&#9733;</span>
                                            <span>Ruang Admin</span>
                                        </span>
                                        <span class="text-indigo-400 text-sm">&rsaquo;</span>
                                    </a>
                                </div>

                                <!-- Tombol Keluar (Paling Bawah) -->
                                <div class="p-2 bg-slate-50/50">
                                    <button type="button"
                                            @click="logout()"
                                            class="w-full px-3.5 py-2 rounded-xl text-left text-xs font-bold text-rose-600 hover:bg-rose-50 transition flex items-center justify-between cursor-pointer">
                                        <span>Keluar</span>
                                        <span class="text-rose-400">&rarr;</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <!-- Tombol Hamburger Tunggal: Mobile (< md) saat Sudah Login -->
                <template x-if="hasToken">
                    <button type="button"
                            @click="mobileMenuOpen = !mobileMenuOpen"
                            aria-label="Buka Menu Navigasi & Akun"
                            class="md:hidden p-2 rounded-lg bg-emerald-900/80 hover:bg-emerald-800 text-emerald-100 border border-emerald-800 cursor-pointer shrink-0">
                        <svg x-show="!mobileMenuOpen" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                        <svg x-show="mobileMenuOpen" x-cloak class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </template>
            </div>

            <!-- Backdrop Redup saat Menu Mobile Terbuka -->
            <div x-show="hasToken && mobileMenuOpen"
                 x-cloak
                 @click="mobileMenuOpen = false"
                 x-transition.opacity.duration.150ms
                 class="md:hidden fixed inset-0 top-[64px] bg-slate-900/40 backdrop-blur-[1px] z-30">
            </div>

            <!-- Drawer Menu Terpadu Mobile (< md): Floating Overlay Sheet -->
            <div x-show="hasToken && mobileMenuOpen"
                 x-cloak
                 x-transition:enter="transition ease-out duration-150"
                 x-transition:enter-start="opacity-0 -translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-100"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 -translate-y-2"
                 class="md:hidden absolute inset-x-0 top-full bg-white text-slate-800 shadow-2xl border-b border-slate-200 z-40 divide-y divide-slate-100">

                <!-- Bagian Menu Akun: Foto/Inisial, Nama Lengkap + Kavling (sejajar kanan), & Level Warga -->
                <div class="px-5 py-4 bg-slate-50/90 flex items-center gap-3.5">
                    <template x-if="profile.avatar_url">
                        <img :src="profile.avatar_url"
                             :alt="profile.name"
                             class="w-11 h-11 rounded-full object-cover border border-slate-200 shrink-0">
                    </template>
                    <template x-if="!profile.avatar_url">
                        <span class="w-11 h-11 rounded-full bg-emerald-600 text-white text-sm font-extrabold flex items-center justify-center tracking-wider shrink-0 shadow-sm"
                              x-text="userInitials">
                        </span>
                    </template>
                    <div class="min-w-0 flex-1 space-y-1">
                        <div class="flex items-center justify-between gap-2">
                            <div class="text-sm font-bold text-slate-900 leading-snug truncate"
                                 x-text="profile.name || 'Warga PPMT'"></div>
                            <span x-show="profile.unit_codes"
                                  x-text="profile.unit_codes"
                                  class="px-2 py-0.5 rounded-md text-[11px] font-bold font-mono bg-slate-200/80 text-slate-800 border border-slate-300/80 shrink-0">
                            </span>
                        </div>
                        <div>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-100 text-emerald-900 border border-emerald-200"
                                  x-text="'Lv. ' + profile.level + (profile.level_label ? ' • ' + profile.level_label : '')">
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Tautan Navigasi Utama -->
                <div class="py-1.5 text-sm font-semibold">
                    <a href="/dashboard"
                       class="px-5 py-3 flex items-center justify-between transition {{ request()->is('dashboard') ? 'bg-emerald-50/80 text-emerald-900 font-bold border-l-4 border-emerald-600' : 'text-slate-700 hover:bg-slate-50' }}">
                        <span>Portal Warga</span>
                        <span class="text-slate-400 text-base">&rsaquo;</span>
                    </a>
                    <a href="/komunitas"
                       class="px-5 py-3 flex items-center justify-between transition {{ request()->is('komunitas') ? 'bg-emerald-50/80 text-emerald-900 font-bold border-l-4 border-emerald-600' : 'text-slate-700 hover:bg-slate-50' }}">
                        <span>Komunitas &amp; Peta</span>
                        <span class="text-slate-400 text-base">&rsaquo;</span>
                    </a>
                    <a href="/pengaturan"
                       class="px-5 py-3 flex items-center justify-between transition {{ request()->is('pengaturan') ? 'bg-emerald-50/80 text-emerald-900 font-bold border-l-4 border-emerald-600' : 'text-slate-700 hover:bg-slate-50' }}">
                        <span>Pengaturan Akun</span>
                        <span class="text-slate-400 text-base">&rsaquo;</span>
                    </a>
                </div>

                <!-- Ruang Admin (Khusus Admin Sistem) -->
                <div x-show="hasAdminAccess" x-cloak class="py-1.5 text-sm font-semibold">
                    <a href="/admin"
                       class="px-5 py-3 flex items-center justify-between transition {{ request()->is('admin') ? 'bg-indigo-50/80 text-indigo-900 font-bold border-l-4 border-indigo-600' : 'text-indigo-700 hover:bg-indigo-50/60' }}">
                        <span class="flex items-center gap-2">
                            <span>&#9733;</span>
                            <span>Ruang Admin</span>
                        </span>
                        <span class="text-indigo-400 text-base">&rsaquo;</span>
                    </a>
                </div>

                <!-- Tombol Keluar (Sejajar Presisi px-5) -->
                <div class="py-1.5 bg-slate-50/40">
                    <button type="button"
                            @click="logout()"
                            class="w-full px-5 py-3 text-left text-sm font-bold text-rose-600 hover:bg-rose-50/80 transition flex items-center justify-between cursor-pointer">
                        <span>Keluar</span>
                        <span class="text-rose-400 text-base">&rarr;</span>
                    </button>
                </div>
            </div>
        </header>
    @endif

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
        const cached = JSON.parse(sessionStorage.getItem('ppmt_nav_profile') || '{}');
        return {
            hasToken: !!localStorage.getItem('ppmt_token'),
            hasAdminAccess: sessionStorage.getItem('ppmt_has_admin') === '1',
            mobileMenuOpen: false,
            accountMenuOpen: false,
            profile: {
                name: cached.name || '',
                level: cached.level ?? 0,
                level_label: cached.level_label || '',
                avatar_url: cached.avatar_url || null,
                unit_codes: cached.unit_codes || ''
            },

            get userInitials() {
                const raw = (this.profile.name || '').trim();
                if (!raw) return 'W';
                const parts = raw.split(/\s+/);
                if (parts.length === 1) {
                    return parts[0].slice(0, 2).toUpperCase();
                }
                return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
            },

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
                        const data = json.data || {};
                        const codes = (data.memberships || [])
                            .map(m => m.unit?.code)
                            .filter(Boolean);

                        this.hasAdminAccess = Boolean(data.has_admin_access);
                        this.profile = {
                            name: data.name || '',
                            level: data.reputation_badge?.level ?? 0,
                            level_label: data.reputation_badge?.label || '',
                            avatar_url: data.avatar_url || null,
                            unit_codes: [...new Set(codes)].join(', ')
                        };
                        sessionStorage.setItem('ppmt_has_admin', this.hasAdminAccess ? '1' : '0');
                        sessionStorage.setItem('ppmt_nav_profile', JSON.stringify(this.profile));
                    }
                } catch (e) {
                    // Abaikan gangguan jaringan sementara
                }
            },

            logout() {
                localStorage.removeItem('ppmt_token');
                sessionStorage.removeItem('ppmt_has_admin');
                sessionStorage.removeItem('ppmt_nav_profile');
                window.location.href = '/';
            }
        }
    }
    </script>

</body>
</html>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>My PPMT 2.5D — Peta Komunitas Interaktif</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Press+Start+2P&family=VT323&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #141824;
            color: #f4f1de;
            overflow: hidden;
            user-select: none;
            -webkit-user-select: none;
            touch-action: none;
        }
        .font-pixel {
            font-family: 'Press Start 2P', monospace;
        }
        .font-vt {
            font-family: 'VT323', monospace;
        }
        .retro-panel {
            background-color: #f4f1de;
            color: #141824;
            border: 2px solid #141824;
            box-shadow: 0 4px 0 #141824;
        }
        .retro-btn {
            border: 2px solid #141824;
            box-shadow: 0 3px 0 #141824;
            transition: transform 0.08s ease, box-shadow 0.08s ease, background-color 0.15s ease;
        }
        .retro-btn:active:not(:disabled) {
            transform: translateY(2px);
            box-shadow: 0 1px 0 #141824;
        }
    </style>
</head>
<body class="w-screen h-[100dvh] overflow-hidden relative bg-[#141824]">

    <!-- Guard Autentikasi & Verifikasi Warga (Selaras dengan Peta Kavling) -->
    <div id="authGateOverlay" class="fixed inset-0 z-50 bg-[#141824]/95 flex items-center justify-center p-4">
        <div class="retro-panel max-w-md w-full rounded-xl p-5 text-center space-y-3">
            <div class="font-pixel text-xs text-[#1b2234]">MY PPMT 2.5D</div>
            <p id="authGateMessage" class="text-sm text-slate-700 font-medium">
                Memverifikasi sesi warga PPMT...
            </p>
            <div id="authGateActions" class="hidden pt-1">
                <a id="authGateLink" href="/" class="retro-btn inline-block px-4 py-2 rounded-lg bg-[#f2cc8f] text-[#141824] text-xs font-extrabold">
                    Kembali
                </a>
            </div>
        </div>
    </div>

    <!-- Kanvas Utama Isometrik 2.5D -->
    <canvas id="gameCanvas" class="block w-full h-full cursor-grab active:cursor-grabbing"></canvas>

    <!-- HUD Atas -->
    <header class="fixed top-3 left-3 right-3 z-20 flex flex-wrap items-center justify-between gap-2 pointer-events-none">
        <div class="flex items-center gap-2 pointer-events-auto">
            <a href="/komunitas"
               class="retro-btn px-3 py-1.5 rounded-lg bg-[#29324a] hover:bg-[#384463] text-[#f4f1de] text-xs font-extrabold">
                ← Komunitas
            </a>
            <div class="retro-panel px-3 py-1.5 rounded-lg flex items-center gap-2.5">
                <span class="font-pixel text-[10px] sm:text-xs tracking-tight text-[#1b2234]">MY PPMT</span>
                <span class="hidden md:inline text-xs font-semibold text-slate-600">Siteplan 2.5D</span>
            </div>
        </div>

        <!-- Switcher Lapisan -->
        <div class="retro-panel px-1.5 py-1 rounded-lg flex items-center gap-1 pointer-events-auto text-xs">
            <button type="button" data-layer="occupancy"
                    class="layer-btn retro-btn px-2.5 sm:px-3 py-1 font-bold transition border border-[#141824] shrink-0 cursor-pointer bg-[#f2cc8f] text-[#141824]">
                Hunian
            </button>
            <button type="button" data-layer="umkm"
                    class="layer-btn retro-btn px-2.5 sm:px-3 py-1 font-bold transition border border-[#141824] shrink-0 cursor-pointer bg-[#29324a] text-[#f4f1de] hover:bg-[#384463]">
                Pasar
            </button>
            <button type="button" data-layer="treasury"
                    class="layer-btn retro-btn px-2.5 sm:px-3 py-1 font-bold transition border border-[#141824] shrink-0 cursor-pointer bg-[#29324a] text-[#f4f1de] hover:bg-[#384463]">
                Iuran
            </button>
        </div>

        <!-- Skor Misi & Poin -->
        <div class="retro-panel px-3 py-1.5 rounded-lg flex items-center gap-3 pointer-events-auto text-xs font-bold">
            <div>
                <span class="text-slate-500 mr-1">MISI</span>
                <span id="hudScore" class="font-pixel text-[10px] text-[#1b2234]">0 / 0</span>
            </div>
            <div class="h-4 w-[1.5px] bg-slate-300"></div>
            <div>
                <span class="text-slate-500 mr-1">POIN</span>
                <span id="hudPoints" class="font-pixel text-[10px] text-amber-700">0</span>
            </div>
        </div>
    </header>

    <!-- Toast Notifikasi -->
    <div id="toastBox"
         class="hidden fixed top-16 left-1/2 -translate-x-1/2 z-40 retro-panel px-4 py-2 rounded-lg text-xs sm:text-sm font-bold text-[#141824] pointer-events-none">
    </div>

    <!-- Radar Minimap & Kontrol Kamera Kanan Bawah -->
    <div class="fixed bottom-3 right-3 z-20 flex flex-col items-end gap-2 pointer-events-auto">
        <!-- Drawer Minimap Menempel di Sisi Kanan Frame -->
        <div class="flex items-stretch justify-end">
            <!-- Tombol Buka Minimap (Tampil Saat Tersembunyi) -->
            <button id="btnOpenMinimap" type="button" title="Tampilkan minimap" aria-label="Tampilkan minimap"
                    class="retro-btn px-2.5 py-2 rounded-lg bg-[#f4f1de] hover:bg-[#e9e4c8] text-[#141824] text-xs font-extrabold cursor-pointer flex items-center gap-1">
                <span>◀</span>
                <span class="hidden sm:inline">RADAR</span>
            </button>

            <!-- Panel Minimap Terbuka ke Kiri (Menempel di Kanan Frame) -->
            <div id="minimapDrawer" class="hidden retro-panel p-2 rounded-xl flex items-stretch gap-2">
                <div>
                    <div class="flex items-center justify-between text-[10px] font-bold text-slate-600 mb-1 px-0.5">
                        <span>RADAR</span>
                        <span id="radarCompassText">U ▲</span>
                    </div>
                    <canvas id="minimapCanvas" width="112" height="156" class="block rounded border border-[#141824] bg-[#e9e4c8] cursor-pointer"></canvas>
                </div>
                <button id="btnHideMinimap" type="button" title="Sembunyikan minimap" aria-label="Sembunyikan minimap"
                        class="retro-btn px-1.5 rounded-lg bg-[#29324a] hover:bg-[#384463] text-[#f4f1de] text-xs font-extrabold cursor-pointer flex items-center justify-center self-stretch">
                    ▶
                </button>
            </div>
        </div>

        <div class="flex items-center gap-1.5">
            <button id="btnToggleBgm" type="button" title="Musik Latar"
                    class="retro-btn px-2.5 py-1.5 rounded-lg bg-[#29324a] hover:bg-[#384463] text-[#f4f1de] text-xs font-extrabold cursor-pointer">
                🔇 <span class="hidden sm:inline">BGM</span>
            </button>
            <button id="btnDayNight" type="button"
                    class="retro-btn px-2.5 py-1.5 rounded-lg bg-[#f2cc8f] text-[#141824] text-xs font-extrabold cursor-pointer">
                ☀ <span class="hidden sm:inline">SIANG</span>
            </button>
            <button id="btnRotCCW" type="button" title="Putar Kiri (CCW)"
                    class="retro-btn px-2.5 py-1.5 rounded-lg bg-[#29324a] hover:bg-[#384463] text-[#f4f1de] text-xs font-extrabold cursor-pointer">
                ↺
            </button>
            <button id="btnRotDir" type="button" title="Arah Hadap Kamera"
                    class="retro-btn px-2.5 py-1.5 rounded-lg bg-[#29324a] hover:bg-[#384463] text-[#f4f1de] text-xs font-extrabold cursor-pointer">
                🧭 U
            </button>
            <button id="btnRotCW" type="button" title="Putar Kanan (CW)"
                    class="retro-btn px-2.5 py-1.5 rounded-lg bg-[#29324a] hover:bg-[#384463] text-[#f4f1de] text-xs font-extrabold cursor-pointer">
                ↻
            </button>
        </div>

        <div class="flex items-center gap-1.5">
            <button id="btnZoomOut" type="button" title="Perkecil"
                    class="retro-btn w-8 h-8 rounded-lg bg-[#f4f1de] text-[#141824] text-sm font-extrabold cursor-pointer">
                −
            </button>
            <span id="zoomLevelText" class="retro-panel px-2 py-1 rounded-lg text-[11px] font-bold min-w-[48px] text-center">
                100%
            </span>
            <button id="btnZoomIn" type="button" title="Perbesar"
                    class="retro-btn w-8 h-8 rounded-lg bg-[#f4f1de] text-[#141824] text-sm font-extrabold cursor-pointer">
                +
            </button>
            <button id="btnResetCam" type="button" title="Reset Kamera"
                    class="retro-btn px-2.5 h-8 rounded-lg bg-[#81b29a] text-[#141824] text-xs font-extrabold cursor-pointer">
                ⌖
            </button>
            @if (config('app.debug'))
                <button id="btnRunTests" type="button" title="Jalankan Uji Otomatis M1-M4"
                        class="retro-btn px-2.5 h-8 rounded-lg bg-[#1b2234] text-[#a7c957] text-[10px] font-bold cursor-pointer">
                    🧪 <span id="testBadgeText">M1-M4</span>
                </button>
            @else
                <span id="testBadgeText" class="hidden"></span>
            @endif
        </div>
    </div>

    <!-- Context Menu Kavling -->
    <div id="contextMenu"
         class="hidden fixed z-30 bottom-20 left-1/2 -translate-x-1/2 sm:translate-x-0 sm:bottom-auto sm:left-auto w-64 retro-panel rounded-xl p-3 space-y-2.5">
        <div class="flex items-start justify-between gap-2 border-b border-slate-300 pb-2">
            <div>
                <div id="ctxTitle" class="font-extrabold text-sm text-[#141824]">Kavling -</div>
                <div id="ctxSubtitle" class="text-[11px] text-slate-600">Blok - • ZONA-1</div>
            </div>
            <button id="btnCloseCtx" type="button" class="text-xs font-bold px-1.5 py-0.5 rounded bg-slate-200 text-slate-800 cursor-pointer">
                ✕
            </button>
        </div>

        <div id="ctxStatusBadge" class="text-xs font-bold px-2.5 py-1 rounded bg-[#e6e1c5] border border-[#141824] text-[#141824]">
            Status: -
        </div>

        <div class="flex flex-col gap-1.5">
            <button id="ctxMissionBtn" type="button"
                    class="hidden retro-btn w-full py-1.5 px-3 rounded-lg bg-[#81b29a] text-[#141824] text-xs font-extrabold text-left cursor-pointer">
                <span id="ctxMissionLabel">✓ Selesaikan Misi</span>
            </button>
            <button id="ctxInspectBtn" type="button"
                    class="retro-btn w-full py-1.5 px-3 rounded-lg bg-[#f2cc8f] text-[#141824] text-xs font-extrabold text-left cursor-pointer">
                🔍 Inspeksi Detail Kavling
            </button>
            <button id="ctxFocusBtn" type="button"
                    class="retro-btn w-full py-1.5 px-3 rounded-lg bg-white text-[#141824] text-xs font-bold text-left cursor-pointer">
                🎯 Fokus Kamera ke Kavling
            </button>
        </div>
    </div>

    <!-- Modal Inspector Detail Kavling -->
    <div id="inspectorModal" class="hidden fixed inset-0 z-50 bg-black/65 items-center justify-center p-4">
        <div class="retro-panel w-full max-w-lg rounded-2xl overflow-hidden">
            <div class="px-4 py-3 bg-[#1b2234] text-[#f4f1de] flex items-center justify-between border-b-2 border-[#141824]">
                <div>
                    <h2 id="inspTitle" class="font-extrabold text-sm sm:text-base">Detail Kavling</h2>
                    <p id="inspSub" class="text-[11px] text-slate-300">Informasi Hunian, Iuran &amp; UMKM</p>
                </div>
                <button id="btnCloseInspector" type="button"
                        class="retro-btn px-2.5 py-1 rounded-lg bg-[#e07a5f] text-[#141824] text-xs font-extrabold cursor-pointer">
                    Tutup
                </button>
            </div>
            <div id="inspBody" class="p-4 max-h-[70vh] overflow-y-auto space-y-3 bg-[#f4f1de] text-[#141824]"></div>
        </div>
    </div>

    <script id="laravel-my-ppmt-payload" type="application/json">
        {!! json_encode([
            'use_live_api' => true,
            'endpoints' => [
                'me' => '/api/v1/me',
                'siteplan' => '/api/v1/siteplan/units',
                'approve' => '/api/v1/verifications/approve',
                'dues' => '/api/v1/treasury/dues',
            ],
            'audio' => [
                'bgm_url' => asset('audio/my-ppmt-bgm.ogg'),
                'volume' => 0.25,
            ],
            'viewer_context' => [
                'can_view_treasury' => false,
                'can_manage_treasury' => false,
                'is_scoped_coordinator' => false,
                'active_scopes' => [],
                'current_month' => now()->format('Y-m'),
            ],
            'unit_overrides' => [],
        ], JSON_UNESCAPED_SLASHES) !!}
    </script>

    <script>
        (async () => {
            const overlay = document.getElementById('authGateOverlay');
            const messageEl = document.getElementById('authGateMessage');
            const actionsEl = document.getElementById('authGateActions');
            const linkEl = document.getElementById('authGateLink');
            const payloadEl = document.getElementById('laravel-my-ppmt-payload');
            const payload = payloadEl ? JSON.parse(payloadEl.textContent) : {};
            const token = localStorage.getItem('ppmt_token');

            function showGateBlock(msg, href = '/', label = 'Kembali') {
                messageEl.textContent = msg;
                linkEl.href = href;
                linkEl.textContent = label;
                actionsEl.classList.remove('hidden');
            }

            if (!token) {
                showGateBlock('Sesi masuk warga tidak ditemukan. Silakan masuk terlebih dahulu.', '/', 'Masuk ke Aplikasi');
                return;
            }

            try {
                const res = await fetch(payload.endpoints?.me || '/api/v1/me', {
                    headers: {
                        'Authorization': 'Bearer ' + token,
                        'Accept': 'application/json',
                    },
                });
                if (!res.ok) {
                    showGateBlock('Sesi masuk Anda telah berakhir. Silakan masuk ulang.', '/', 'Masuk ke Aplikasi');
                    return;
                }
                const meJson = await res.json();
                const me = meJson?.data || {};
                if (!me.is_verified) {
                    showGateBlock('Akun Anda belum terverifikasi sebagai warga PPMT aktif.', '/dashboard', 'Ke Dasbor');
                    return;
                }

                payload.viewer_context = {
                    ...(payload.viewer_context || {}),
                    can_view_treasury: Boolean(me.can_view_treasury || me.is_operator_aplikasi),
                    can_manage_treasury: Boolean(me.can_manage_treasury),
                    is_global_governor: Boolean(me.is_global_governor),
                };
                if (payloadEl) {
                    payloadEl.textContent = JSON.stringify(payload);
                }

                overlay.classList.add('hidden');
                window.dispatchEvent(new CustomEvent('my-ppmt:auth-ready', { detail: { me, payload } }));
            } catch (e) {
                showGateBlock('Gagal memeriksa status akun warga. Periksa koneksi lalu coba lagi.', '/komunitas', 'Kembali ke Komunitas');
            }
        })();
    </script>

    @vite([
        'resources/js/my-ppmt/config/map-layout.js',
        'resources/js/my-ppmt/engine/iso-math.js',
        'resources/js/my-ppmt/renderers/building-renderer.js',
        'resources/js/my-ppmt/renderers/environment-renderer.js',
        'resources/js/my-ppmt/services/my-ppmt-api.js',
        'resources/js/my-ppmt/ui/hud-controller.js',
        'resources/js/my-ppmt/main.js',
    ])
</body>
</html>
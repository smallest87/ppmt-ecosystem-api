/**
 * ============================================================================
 * SUB-SCENE FASAD 2D PANDANGAN DEPAN (MyPpmtModules.FacadeSubscene)
 * Target File: resources/js/my-ppmt/ui/facade-subscene.js
 * ============================================================================
 */
window.MyPpmtModules = window.MyPpmtModules || {};

window.MyPpmtModules.FacadeSubscene = (() => {
    const BASE_W = 640;
    const BASE_H = 360;

    function mount({ state, onBackToSiteplan, onShowToast }) {
        const overlay = document.getElementById('facadeSubsceneOverlay');
        const canvas = document.getElementById('facadeCanvas');
        const fadeLayer = document.getElementById('sceneFadeLayer');
        const btnBack = document.getElementById('btnFacadeBack');
        const btnGreet = document.getElementById('btnFacadeGreet');
        const btnStore = document.getElementById('btnFacadeStore');

        if (!overlay || !canvas) {
            return {
                openForUnit: () => {},
                closeToSiteplan: () => {},
                isOpen: () => false
            };
        }

        const ctx = canvas.getContext('2d');
        canvas.width = BASE_W;
        canvas.height = BASE_H;

        let activeUnit = null;
        let isTransitioning = false;
        let currentMode = 'overview'; // 'overview' | 'greet' | 'store'

        // Muat gambar fasad (utamakan PNG hasil Aseprite, fallback ke JPG yang tersedia)
        const facadeImg = new Image();
        let facadeLoaded = false;
        facadeImg.onload = () => {
            facadeLoaded = true;
            renderFacade();
        };
        facadeImg.onerror = () => {
            if (!facadeImg.dataset.triedJpg) {
                facadeImg.dataset.triedJpg = '1';
                facadeImg.src = '/images/my-ppmt/fasad-fix.jpg';
            }
        };
        facadeImg.src = '/images/my-ppmt/fasad-fix.png';

        function getUnitCode(u) {
            return u?.code || u?.unit_code || 'UNIT';
        }

        function getZone(u) {
            return u?.zone || u?.zone_code || 'ZONA-1';
        }

        function drawFallbackFacade(u) {
            const isNight = Boolean(state?.isNight);
            ctx.fillStyle = isNight ? '#162038' : '#7dd3fc';
            ctx.fillRect(0, 0, BASE_W, BASE_H);

            // Tanah / teras bawah
            ctx.fillStyle = isNight ? '#273043' : '#cbd5e1';
            ctx.fillRect(0, 275, BASE_W, 85);

            // Badan rumah & panel oranye selaras fasad
            ctx.fillStyle = isNight ? '#c4b896' : '#fef3c7';
            ctx.fillRect(110, 60, 420, 220);
            ctx.strokeStyle = '#141824';
            ctx.lineWidth = 3;
            ctx.strokeRect(110, 60, 420, 220);

            ctx.fillStyle = isNight ? '#b45309' : '#f59e0b';
            ctx.fillRect(150, 125, 165, 130);
            ctx.strokeRect(150, 125, 165, 130);

            // Pintu utama
            ctx.fillStyle = '#f8fafc';
            ctx.fillRect(355, 135, 65, 145);
            ctx.strokeRect(355, 135, 65, 145);

            ctx.fillStyle = '#141824';
            ctx.font = 'bold 14px monospace';
            ctx.textAlign = 'center';
            ctx.fillText(`KAVLING ${getUnitCode(u)}`, BASE_W / 2, 36);
        }

        function renderFacade() {
            if (!activeUnit) return;

            ctx.save();
            ctx.imageSmoothingEnabled = false;
            ctx.clearRect(0, 0, BASE_W, BASE_H);

            if (facadeLoaded && facadeImg.naturalWidth > 0) {
                ctx.drawImage(facadeImg, 0, 0, BASE_W, BASE_H);
                if (state?.isNight) {
                    ctx.fillStyle = 'rgba(13, 17, 26, 0.35)';
                    ctx.fillRect(0, 0, BASE_W, BASE_H);
                }
            } else {
                drawFallbackFacade(activeUnit);
            }

            // Indikator badge mode aktif di pojok kanan bawah stage
            if (currentMode !== 'overview') {
                const label = currentMode === 'greet' ? 'MODE TEGUR SAPA' : 'MODE ETALASE LAPAK';
                ctx.fillStyle = 'rgba(20, 24, 36, 0.88)';
                ctx.fillRect(BASE_W - 178, 12, 166, 26);
                ctx.strokeStyle = '#f2cc8f';
                ctx.lineWidth = 1.5;
                ctx.strokeRect(BASE_W - 178, 12, 166, 26);

                ctx.fillStyle = '#f4f1de';
                ctx.font = 'bold 11px monospace';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText(label, BASE_W - 95, 25);
            }

            ctx.restore();
        }

        function syncHeaderAndBar(u) {
            const code = getUnitCode(u);
            const zone = getZone(u);
            const membersCount = Array.isArray(u.members) ? u.members.length : 0;
            const storesCount = Array.isArray(u.active_stores) ? u.active_stores.length : 0;

            const titleEl = document.getElementById('facadeUnitTitle');
            const subEl = document.getElementById('facadeUnitSub');
            const hintEl = document.getElementById('facadeHintText');

            if (titleEl) {
                titleEl.textContent = `Fasad Kavling ${code} (Blok ${u.block || '-'})`;
            }
            if (subEl) {
                subEl.textContent = `${zone} • ${membersCount} Warga • ${storesCount} Lapak`;
            }
            if (hintEl) {
                if (currentMode === 'greet') {
                    hintEl.textContent = membersCount > 0
                        ? `Warga terdata di ${code}: ${u.members.map(m => m.name).join(', ')}.`
                        : `Belum ada warga terdata pada Kavling ${code} untuk disapa.`;
                } else if (currentMode === 'store') {
                    hintEl.textContent = storesCount > 0
                        ? `Lapak aktif di ${code}: ${u.active_stores.map(s => s.store_name).join(', ')}.`
                        : `Belum ada lapak UMKM aktif yang tercatat pada Kavling ${code}.`;
                } else {
                    hintEl.textContent = 'Pilih Tegur Sapa untuk melihat warga atau Lapak untuk melihat usaha.';
                }
            }
        }

        function runFadeTransition(midCallback, doneCallback) {
            if (isTransitioning) return;
            isTransitioning = true;

            if (!fadeLayer) {
                midCallback?.();
                isTransitioning = false;
                doneCallback?.();
                return;
            }

            fadeLayer.classList.remove('opacity-0');
            fadeLayer.classList.add('opacity-100');

            setTimeout(() => {
                midCallback?.();
                requestAnimationFrame(() => {
                    fadeLayer.classList.remove('opacity-100');
                    fadeLayer.classList.add('opacity-0');
                    setTimeout(() => {
                        isTransitioning = false;
                        doneCallback?.();
                    }, 200);
                });
            }, 180);
        }

        function openForUnit(unit) {
            if (!unit || isTransitioning) return;
            activeUnit = unit;
            currentMode = 'overview';
            syncHeaderAndBar(activeUnit);
            renderFacade();

            overlay.classList.remove('hidden');
            if (fadeLayer) {
                fadeLayer.classList.remove('opacity-0');
                fadeLayer.classList.add('opacity-100');
                isTransitioning = true;
                requestAnimationFrame(() => {
                    fadeLayer.classList.remove('opacity-100');
                    fadeLayer.classList.add('opacity-0');
                    setTimeout(() => {
                        isTransitioning = false;
                    }, 200);
                });
            }
        }

        function closeToSiteplan() {
            if (overlay.classList.contains('hidden') || isTransitioning) return;
            runFadeTransition(
                () => {
                    overlay.classList.add('hidden');
                    activeUnit = null;
                    currentMode = 'overview';
                },
                () => {
                    if (typeof onBackToSiteplan === 'function') {
                        onBackToSiteplan();
                    }
                }
            );
        }

        if (btnBack) {
            btnBack.addEventListener('click', closeToSiteplan);
        }

        if (btnGreet) {
            btnGreet.addEventListener('click', () => {
                if (!activeUnit) return;
                currentMode = 'greet';
                syncHeaderAndBar(activeUnit);
                renderFacade();
                if (typeof onShowToast === 'function') {
                    onShowToast(`👋 Mode Tegur Sapa — Kavling ${getUnitCode(activeUnit)}`);
                }
            });
        }

        if (btnStore) {
            btnStore.addEventListener('click', () => {
                if (!activeUnit) return;
                currentMode = 'store';
                syncHeaderAndBar(activeUnit);
                renderFacade();
                if (typeof onShowToast === 'function') {
                    onShowToast(`🛍️ Mode Lapak UMKM — Kavling ${getUnitCode(activeUnit)}`);
                }
            });
        }

        return {
            openForUnit,
            closeToSiteplan,
            renderFacade,
            isOpen: () => !overlay.classList.contains('hidden')
        };
    }

    return { mount };
})();
/**
 * ============================================================================
 * MILESTONE 4B: PEMISAHAN KONTROLER HUD & KAMERA (MyPpmtModules.HudController)
 * Target File: resources/js/my-ppmt/ui/hud-controller.js
 * ============================================================================
 */
window.MyPpmtModules.HudController = (() => {
    const { GRID_COLS, GRID_ROWS } = window.MyPpmtModules.MapLayout;
    const { rotatedToIso, gridToIso, hitTestUnit } = window.MyPpmtModules.IsoMath;

    const ORIENTATIONS = [
        { code: 'U', name: 'Utara' },
        { code: 'T', name: 'Timur' },
        { code: 'S', name: 'Selatan' },
        { code: 'B', name: 'Barat' }
    ];

function mount({ canvas, minimapCanvas, units, state, apiClient, onCacheDirty, onRunTests, onReloadWorld }) {
        const contextMenu = document.getElementById('contextMenu');
        const inspectorModal = document.getElementById('inspectorModal');

        const btnOpenMinimap = document.getElementById('btnOpenMinimap');
        const btnHideMinimap = document.getElementById('btnHideMinimap');
        const minimapDrawer = document.getElementById('minimapDrawer');

        function setMinimapOpen(isOpen) {
            if (!minimapDrawer || !btnOpenMinimap) return;
            minimapDrawer.classList.toggle('hidden', !isOpen);
            btnOpenMinimap.classList.toggle('hidden', isOpen);
        }

        if (btnOpenMinimap) {
            btnOpenMinimap.addEventListener('click', () => setMinimapOpen(true));
        }
        if (btnHideMinimap) {
            btnHideMinimap.addEventListener('click', () => setMinimapOpen(false));
        }

        function getCode(u) {
            return u.code || u.unit_code || 'UNIT';
        }

        function getZone(u) {
            return u.zone || u.zone_code || 'ZONA-1';
        }

        function canApproveClaimOnUnit(u) {
            const hasPendingMember = Array.isArray(u.members) && u.members.some((m) => !m.is_approved);
            return Boolean(u.can_manage_unit) && (u.occupancy_status === 'pending' || hasPendingMember);
        }

        function canRecordDuesOnUnit(u) {
            const canManageTreasury = Boolean(state.viewerContext?.can_manage_treasury);
            return canManageTreasury && u.dues_status === 'unpaid';
        }

        function canInspectDuesOnUnit(u) {
            return Boolean(state.viewerContext?.can_view_treasury) || Boolean(u.is_my_unit);
        }

        function showToast(msg) {
            const box = document.getElementById('toastBox');
            box.textContent = msg;
            box.classList.remove('hidden');
            clearTimeout(box._timer);
            box._timer = setTimeout(() => box.classList.add('hidden'), 2600);
        }

        function resizeCanvas() {
            canvas.width = window.innerWidth;
            canvas.height = window.innerHeight;
        }

        function resetCamera() {
            const center = rotatedToIso(GRID_COLS / 2, GRID_ROWS / 2);
            state.zoom = Math.min(canvas.width / 760, canvas.height / 660, 1.35);
            state.zoom = Math.max(0.65, state.zoom);
            state.camX = -center.x * state.zoom;
            state.camY = -center.y * state.zoom + 16;
            document.getElementById('zoomLevelText').textContent = `${Math.round(state.zoom * 100)}%`;
        }

        function applyZoomAt(factor, pivotX = canvas.width / 2, pivotY = canvas.height / 2) {
            const prevZoom = state.zoom;
            const nextZoom = Math.min(3.2, Math.max(0.45, prevZoom * factor));
            if (nextZoom === prevZoom) return;

            const wx = (pivotX - canvas.width / 2 - state.camX) / prevZoom;
            const wy = (pivotY - canvas.height / 2 - state.camY) / prevZoom;

            state.zoom = nextZoom;
            state.camX = pivotX - canvas.width / 2 - wx * nextZoom;
            state.camY = pivotY - canvas.height / 2 - wy * nextZoom;
            document.getElementById('zoomLevelText').textContent = `${Math.round(state.zoom * 100)}%`;
        }

        function rotateOrientation(delta) {
            state.rotation = (state.rotation + delta + 4) % 4;
            const ori = ORIENTATIONS[state.rotation];
            document.getElementById('btnRotDir').textContent = `🧭 ${ori.code}`;
            document.getElementById('radarCompassText').textContent = `${ori.code} ▲`;
            onCacheDirty();
            resetCamera();
            closeContextMenu();
            showToast(`Sudut Pandang: Hadap ${ori.name}`);
        }

        function openContextMenu(u, clientX, clientY) {
            state.selectedUnit = u;
            const ownTag = u.is_my_unit ? ' • Kavling Anda' : '';
            document.getElementById('ctxTitle').textContent = `Kavling ${getCode(u)}`;
            document.getElementById('ctxSubtitle').textContent = `Blok ${u.block} • ${getZone(u)} • Hadap ${u.facing_direction}${ownTag}`;

            const statusLabels = {
                occupied: 'Berpenghuni Tetap',
                rented: 'Disewakan / Kontrak',
                pending: 'Menunggu Verifikasi SHM',
                unclaimed: 'Lahan Kosong / Belum Klaim'
            };
            document.getElementById('ctxStatusBadge').textContent = `Status: ${statusLabels[u.occupancy_status] || u.occupancy_status}`;

            const missionBtn = document.getElementById('ctxMissionBtn');
            const missionLabel = document.getElementById('ctxMissionLabel');

            if (canApproveClaimOnUnit(u)) {
                missionBtn.classList.remove('hidden');
                missionLabel.textContent = '✓ Sahkan Klaim & SHM (+150)';
            } else if (canRecordDuesOnUnit(u)) {
                missionBtn.classList.remove('hidden');
                missionLabel.textContent = 'Rp Catat Lunas Iuran (+100)';
            } else {
                missionBtn.classList.add('hidden');
            }

            contextMenu.classList.remove('hidden');
            if (window.innerWidth >= 640) {
                const left = Math.min(window.innerWidth - 272, Math.max(12, clientX + 12));
                const top = Math.min(window.innerHeight - 210, Math.max(76, clientY - 20));
                contextMenu.style.left = `${left}px`;
                contextMenu.style.top = `${top}px`;
            } else {
                contextMenu.style.left = '';
                contextMenu.style.top = '';
            }
        }

        function closeContextMenu() {
            contextMenu.classList.add('hidden');
            state.selectedUnit = null;
        }

        function openInspector(u) {
            const ownBadge = u.is_my_unit ? ' • KAVLING ANDA' : '';
            document.getElementById('inspTitle').textContent = `Kavling ${getCode(u)} (Blok ${u.block})${ownBadge}`;
            document.getElementById('inspSub').textContent = `${getZone(u)} • Grid (${u.coord_x}, ${u.coord_y}) • ${u.width}×${u.height}m • Hadap ${u.facing_direction}`;

            const members = Array.isArray(u.members) ? u.members : [];
            const membersHtml = members.length > 0
                ? members.map((m) => {
                    const badgeApproved = Boolean(m.is_approved);
                    const badgeText = badgeApproved ? 'Terverifikasi' : 'Menunggu';
                    const badgeClass = badgeApproved ? 'bg-[#81b29a] text-[#141824]' : 'bg-[#f2cc8f] text-[#141824]';
                    const detailLine = m.phone ? `${m.sub_role_label} • ${m.phone}` : (m.sub_role_label || 'Warga Terhubung');

                    return `
                        <div class="p-2.5 bg-white border border-[#141824] rounded flex items-center justify-between gap-2">
                            <div>
                                <div class="font-bold text-[#141824]">${m.name}</div>
                                <div class="text-xs text-slate-600">${detailLine}</div>
                            </div>
                            <span class="px-2 py-0.5 text-xs font-bold border border-[#141824] shrink-0 ${badgeClass}">
                                ${badgeText}
                            </span>
                        </div>
                    `;
                }).join('')
                : '<div class="p-2.5 bg-[#e6e1c5] border border-[#b5ae8c] text-sm text-slate-700">Belum ada data penghuni yang dapat ditampilkan pada kavling ini.</div>';

            const stores = Array.isArray(u.active_stores) ? u.active_stores : [];
            const storesHtml = stores.length > 0
                ? stores.map((s) => `
                    <div class="p-2 bg-[#f2cc8f]/45 border border-[#141824] rounded flex items-center justify-between">
                        <span class="font-bold">★ ${s.store_name}</span>
                        <span class="text-xs uppercase px-1.5 py-0.5 bg-[#1b2234] text-[#f2cc8f]">${s.category}</span>
                    </div>
                `).join('')
                : '';

            const showDuesBox = canInspectDuesOnUnit(u);
            const duesValue = showDuesBox ? (u.dues_status || 'unpaid') : 'TERBATAS';
            const duesColor = showDuesBox && u.dues_status === 'unpaid'
                ? 'text-red-700'
                : (showDuesBox ? 'text-emerald-800' : 'text-slate-500');

            document.getElementById('inspBody').innerHTML = `
                <div class="grid grid-cols-2 gap-2 text-sm">
                    <div class="p-2 bg-white border border-[#141824] rounded">
                        <span class="block text-xs text-slate-500">STATUS HUNIAN</span>
                        <strong class="uppercase">${u.occupancy_status}</strong>
                    </div>
                    <div class="p-2 bg-white border border-[#141824] rounded">
                        <span class="block text-xs text-slate-500">IURAN KAS BULANAN</span>
                        <strong class="uppercase ${duesColor}">${duesValue}</strong>
                    </div>
                </div>
                <div>
                    <div class="font-bold text-sm mb-1">Data Warga / Penghuni:</div>
                    <div class="space-y-1.5">${membersHtml}</div>
                </div>
                ${storesHtml ? `<div><div class="font-bold text-sm mb-1">Unit Usaha UMKM Warga:</div><div class="space-y-1.5">${storesHtml}</div></div>` : ''}
            `;

            inspectorModal.classList.remove('hidden');
            inspectorModal.classList.add('flex');
        }

        async function executeUnitMission(u) {
            if (!u) return;
            try {
                let result = null;
                if (canApproveClaimOnUnit(u)) {
                    result = await apiClient.approveUnitClaim(u);
                } else if (canRecordDuesOnUnit(u)) {
                    result = await apiClient.markDuesPaid(u, state.viewerContext);
                } else {
                    throw new Error('Aksi tidak diizinkan untuk kavling atau peran Anda.');
                }

                if (result?.ok) {
                    state.score++;
                    state.points += result.pointsAwarded;
                    showToast(result.message);
                    if (result.shouldReload && typeof onReloadWorld === 'function') {
                        await onReloadWorld();
                    }
                    document.getElementById('hudScore').textContent = `${state.score} / ${state.totalMissions}`;
                    document.getElementById('hudPoints').textContent = state.points;
                    onCacheDirty();
                    closeContextMenu();
                }
            } catch (err) {
                showToast(`❌ ${err.message}`);
            }
        }

        canvas.addEventListener('mousedown', (e) => {
            state.isDragging = true;
            state.dragMoved = false;
            state.dragStartX = e.clientX - state.camX;
            state.dragStartY = e.clientY - state.camY;
        });

        window.addEventListener('mousemove', (e) => {
            if (state.isDragging) {
                const nx = e.clientX - state.dragStartX;
                const ny = e.clientY - state.dragStartY;
                if (Math.hypot(nx - state.camX, ny - state.camY) > 4) state.dragMoved = true;
                state.camX = nx;
                state.camY = ny;
                return;
            }
            const hit = hitTestUnit(e.clientX, e.clientY, units, state, canvas);
            state.hoveredUnit = hit;
            canvas.style.cursor = hit ? 'pointer' : 'grab';
        });

        window.addEventListener('mouseup', (e) => {
            if (state.isDragging && !state.dragMoved && e.target === canvas) {
                const hit = hitTestUnit(e.clientX, e.clientY, units, state, canvas);
                if (hit) openContextMenu(hit, e.clientX, e.clientY);
                else closeContextMenu();
            }
            state.isDragging = false;
        });

        canvas.addEventListener('wheel', (e) => {
            e.preventDefault();
            applyZoomAt(e.deltaY < 0 ? 1.12 : 0.89, e.clientX, e.clientY);
        }, { passive: false });

        canvas.addEventListener('touchstart', (e) => {
            if (e.touches.length === 1) {
                const t = e.touches[0];
                state.isDragging = true;
                state.dragMoved = false;
                state.dragStartX = t.clientX - state.camX;
                state.dragStartY = t.clientY - state.camY;
            } else if (e.touches.length === 2) {
                state.isDragging = false;
                state.pinchStartDist = Math.hypot(
                    e.touches[0].clientX - e.touches[1].clientX,
                    e.touches[0].clientY - e.touches[1].clientY
                );
                state.pinchStartZoom = state.zoom;
            }
        }, { passive: true });

        canvas.addEventListener('touchmove', (e) => {
            if (e.touches.length === 1 && state.isDragging) {
                const t = e.touches[0];
                const nx = t.clientX - state.dragStartX;
                const ny = t.clientY - state.dragStartY;
                if (Math.hypot(nx - state.camX, ny - state.camY) > 6) state.dragMoved = true;
                state.camX = nx;
                state.camY = ny;
            } else if (e.touches.length === 2 && state.pinchStartDist > 0) {
                const dist = Math.hypot(
                    e.touches[0].clientX - e.touches[1].clientX,
                    e.touches[0].clientY - e.touches[1].clientY
                );
                const midX = (e.touches[0].clientX + e.touches[1].clientX) / 2;
                const midY = (e.touches[0].clientY + e.touches[1].clientY) / 2;
                const targetZoom = state.pinchStartZoom * (dist / state.pinchStartDist);
                applyZoomAt(targetZoom / state.zoom, midX, midY);
            }
        }, { passive: true });

        canvas.addEventListener('touchend', (e) => {
            if (state.isDragging && !state.dragMoved && e.changedTouches.length === 1) {
                const t = e.changedTouches[0];
                const hit = hitTestUnit(t.clientX, t.clientY, units, state, canvas);
                if (hit) openContextMenu(hit, t.clientX, t.clientY);
                else closeContextMenu();
            }
            state.isDragging = false;
            state.pinchStartDist = 0;
        });

        minimapCanvas.addEventListener('click', (e) => {
            const rect = minimapCanvas.getBoundingClientRect();
            const gx = ((e.clientX - rect.left) / rect.width) * GRID_COLS;
            const gy = ((e.clientY - rect.top) / rect.height) * GRID_ROWS;
            const iso = gridToIso(gx, gy, state.rotation);
            state.camX = -iso.x * state.zoom;
            state.camY = -iso.y * state.zoom;
        });

        const treasuryLayerBtn = document.querySelector('.layer-btn[data-layer="treasury"]');
        if (treasuryLayerBtn && state.viewerContext && state.viewerContext.can_view_treasury === false) {
            treasuryLayerBtn.classList.add('hidden');
        }

        document.querySelectorAll('.layer-btn').forEach((btn) => {
            btn.addEventListener('click', () => {
                if (btn.dataset.layer === 'treasury' && state.viewerContext && state.viewerContext.can_view_treasury === false) {
                    showToast('🔒 Layer Iuran hanya tersedia bagi pengurus berwenang.');
                    return;
                }
                state.layer = btn.dataset.layer;
                document.querySelectorAll('.layer-btn').forEach((b) => {
                    const active = b.dataset.layer === state.layer;
                    b.className = `layer-btn retro-btn px-2.5 sm:px-3 py-1 font-bold transition border border-[#141824] shrink-0 cursor-pointer ${
                        active ? 'bg-[#f2cc8f] text-[#141824]' : 'bg-[#29324a] text-[#f4f1de] hover:bg-[#384463]'
                    }`;
                });
                onCacheDirty();
                closeContextMenu();
            });
        });

        const bgmBtn = document.getElementById('btnToggleBgm');
        const payloadEl = document.getElementById('laravel-my-ppmt-payload');
        let bgmUrl = '/audio/my-ppmt-bgm.ogg';
        let bgmVolume = 0.25;

        if (payloadEl) {
            try {
                const parsedPayload = JSON.parse(payloadEl.textContent);
                if (parsedPayload?.audio?.bgm_url) bgmUrl = parsedPayload.audio.bgm_url;
                if (typeof parsedPayload?.audio?.volume === 'number') bgmVolume = parsedPayload.audio.volume;
            } catch (_) {
                // Gunakan nilai default jika JSON gagal di-parse
            }
        }

        if (bgmBtn && bgmUrl) {
            const bgmAudio = new Audio(bgmUrl);
            bgmAudio.loop = true;
            bgmAudio.volume = bgmVolume;

            const syncBgmButton = () => {
                const isPlaying = !bgmAudio.paused;
                bgmBtn.innerHTML = isPlaying
                    ? '🔊 <span class="hidden sm:inline">BGM</span>'
                    : '🔇 <span class="hidden sm:inline">BGM</span>';
            };

            bgmBtn.addEventListener('click', async () => {
                try {
                    if (bgmAudio.paused) {
                        await bgmAudio.play();
                    } else {
                        bgmAudio.pause();
                    }
                    syncBgmButton();
                } catch (err) {
                    showToast('❌ Gagal memutar musik latar.');
                    console.warn('Gagal memutar BGM:', err);
                }
            });

            document.addEventListener('visibilitychange', () => {
                if (document.hidden && !bgmAudio.paused) {
                    bgmAudio.pause();
                    syncBgmButton();
                }
            });
        }

        document.getElementById('btnDayNight').addEventListener('click', (e) => {
            state.isNight = !state.isNight;
            e.currentTarget.innerHTML = state.isNight
                ? '🌙 <span class="hidden sm:inline">MALAM</span>'
                : '☀ <span class="hidden sm:inline">SIANG</span>';
            onCacheDirty();
        });

        document.getElementById('btnRotCCW').addEventListener('click', () => rotateOrientation(-1));
        document.getElementById('btnRotCW').addEventListener('click', () => rotateOrientation(1));
        document.getElementById('btnRotDir').addEventListener('click', () => rotateOrientation(1));
        document.getElementById('btnZoomIn').addEventListener('click', () => applyZoomAt(1.2));
        document.getElementById('btnZoomOut').addEventListener('click', () => applyZoomAt(0.83));
        document.getElementById('btnResetCam').addEventListener('click', resetCamera);

        const btnRunTests = document.getElementById('btnRunTests');
        if (btnRunTests) {
            btnRunTests.addEventListener('click', () => onRunTests(true));
        }

        document.getElementById('btnCloseCtx').addEventListener('click', closeContextMenu);
        document.getElementById('ctxMissionBtn').addEventListener('click', () => executeUnitMission(state.selectedUnit));
        document.getElementById('ctxInspectBtn').addEventListener('click', () => {
            if (state.selectedUnit) openInspector(state.selectedUnit);
        });
        document.getElementById('ctxFocusBtn').addEventListener('click', () => {
            if (!state.selectedUnit) return;
            const u = state.selectedUnit;
            const iso = gridToIso(u.coord_x + u.width / 2, u.coord_y + u.height / 2, state.rotation);
            state.zoom = 1.9;
            state.camX = -iso.x * state.zoom;
            state.camY = -iso.y * state.zoom;
            document.getElementById('zoomLevelText').textContent = '190%';
        });

        document.getElementById('btnCloseInspector').addEventListener('click', () => {
            inspectorModal.classList.add('hidden');
            inspectorModal.classList.remove('flex');
        });
        inspectorModal.addEventListener('click', (e) => {
            if (e.target === inspectorModal) {
                inspectorModal.classList.add('hidden');
                inspectorModal.classList.remove('flex');
            }
        });

        window.addEventListener('resize', resizeCanvas);

        return {
            showToast,
            resizeCanvas,
            resetCamera
        };
    }

    return { mount };
})();
/**
 * ============================================================================
 * ENTRY POINT / BOOTSTRAP APLIKASI (My PPMT 2.5D)
 * Target File: resources/js/my-ppmt/main.js
 * ============================================================================
 */
(() => {
    async function startMyPpmtGame() {
        if (window.__myPpmtBooted) return;
        window.__myPpmtBooted = true;

        const canvas = document.getElementById('gameCanvas');
        const ctx = canvas.getContext('2d');
        const minimapCanvas = document.getElementById('minimapCanvas');
        const mCtx = minimapCanvas.getContext('2d');

        const {
            MapLayout,
            IsoMath,
            EnvironmentRenderer: EnvRenderer,
            BuildingRenderer,
            MyPpmtApi,
            HudController
        } = window.MyPpmtModules;

        const hydrationData = MyPpmtApi.parseHydrationPayload('laravel-my-ppmt-payload');
        const apiClient = MyPpmtApi.createClient(hydrationData);
        const AUTUMN_FOLIAGE = MapLayout.generateAutumnFoliage();

        const units = [];
        const state = {
            viewerContext: hydrationData.viewer_context || {},
            layer: 'occupancy',
            isNight: false,
            rotation: 0,
            camX: 0,
            camY: 0,
            zoom: 1.0,
            hoveredUnit: null,
            selectedUnit: null,
            isDragging: false,
            dragMoved: false,
            dragStartX: 0,
            dragStartY: 0,
            pinchStartDist: 0,
            pinchStartZoom: 1.0,
            score: 0,
            totalMissions: 0,
            points: 0,
            citizens: [],
            vehicles: [],
            gateAngle: 0,
            gateBeaconTimer: 0
        };

        function computeTotalMissions(list, viewerCtx) {
            const canManageTreasury = Boolean(viewerCtx?.can_manage_treasury);
            return list.filter((u) => {
                const hasPendingMember = Array.isArray(u.members) && u.members.some((m) => !m.is_approved);
                const canApprove = Boolean(u.can_manage_unit) && (u.occupancy_status === 'pending' || hasPendingMember);
                const canDues = canManageTreasury && u.dues_status === 'unpaid';
                return canApprove || canDues;
            }).length;
        }

        const staticCache = EnvRenderer.createStaticCacheManager(3.0);

        window.addEventListener('my-ppmt:sprite-loaded', () => {
            staticCache.dirty = true;
        });

        let hud = null;

        async function refreshWorldFromApi() {
            const payload = await apiClient.loadWorldPayload();
            units.splice(0, units.length, ...payload.units);
            state.viewerContext = {
                ...(hydrationData.viewer_context || {}),
                ...(payload.viewerContext || {}),
            };
            state.totalMissions = computeTotalMissions(units, state.viewerContext);
            const scoreEl = document.getElementById('hudScore');
            if (scoreEl) {
                scoreEl.textContent = `${state.score} / ${state.totalMissions}`;
            }
            staticCache.dirty = true;
        }

        async function executeAllMilestoneTests(notify = false) {
            if (staticCache.dirty) {
                EnvRenderer.rebuildStaticCache(staticCache, units, AUTUMN_FOLIAGE, state);
            }
            const resM1 = MapLayout.runSelfTest(units);
            const resM2 = IsoMath.runSelfTest(units);
            const resM3 = EnvRenderer.runSelfTest(staticCache, units, AUTUMN_FOLIAGE);
            const resM4 = await MyPpmtApi.runSelfTest(hydrationData);

            const passed = resM1.passed && resM2.passed && resM3.passed && resM4.passed;
            const totalPassed =
                (resM1.passed ? resM1.totalChecks : 0) +
                (resM2.passed ? resM2.totalChecks : 0) +
                (resM3.passed ? resM3.totalChecks : 0) +
                (resM4.passed ? resM4.totalChecks : 0);
            const totalChecks = resM1.totalChecks + resM2.totalChecks + resM3.totalChecks + resM4.totalChecks;
            const allErrors = [...resM1.errors, ...resM2.errors, ...resM3.errors, ...resM4.errors];

            const badge = document.getElementById('testBadgeText');
            if (badge) {
                badge.textContent = passed ? `${totalPassed}/${totalChecks} OK` : 'FAIL';
                badge.parentElement.classList.toggle('text-[#a7c957]', passed);
                badge.parentElement.classList.toggle('text-[#e07a5f]', !passed);
            }
            if (notify && hud) {
                hud.showToast(
                    passed
                        ? `🧪 Uji M1-M4 Lengkap: ${totalPassed}/${totalChecks} Lulus!`
                        : `❌ Uji Gagal: ${allErrors[0]}`
                );
            }
            return { passed, errors: allErrors };
        }

        function initAmbientLife() {
            const patrolWaypoints = [
                { x: 20.5, y: 2.0 }, { x: 20.5, y: 43.5 }, { x: 2.5, y: 43.5 },
                { x: 2.5, y: 52.5 }, { x: 20.5, y: 52.5 }, { x: 20.5, y: 67.5 },
                { x: 20.5, y: 52.5 }, { x: 34.5, y: 52.5 }, { x: 34.5, y: 43.5 },
                { x: 20.5, y: 43.5 }
            ];

            const citizenConfigs = [
                { role: 'guard', hair: '#1e293b', shirt: '#1d4ed8', pants: '#0f172a', skin: '#f8c8a0', speed: 0.75, startIdx: 0, emote: '🛡' },
                { role: 'jogger', hair: '#7c2d12', shirt: '#f97316', pants: '#1e293b', skin: '#f5cba7', speed: 1.45, startIdx: 2, emote: '♪' },
                { role: 'resident', hair: '#3f2e21', shirt: '#10b981', pants: '#334155', skin: '#fad7a0', speed: 0.9, startIdx: 4, emote: '♥' },
                { role: 'resident', hair: '#111827', shirt: '#ec4899', pants: '#475569', skin: '#f8c8a0', speed: 0.85, startIdx: 6, emote: '!' },
                { role: 'resident', hair: '#5c3a21', shirt: '#eab308', pants: '#1e3a8a', skin: '#f5cba7', speed: 0.95, startIdx: 8, emote: '☺' },
                { role: 'resident', hair: '#1e293b', shirt: '#8b5cf6', pants: '#0f172a', skin: '#fad7a0', speed: 0.8, startIdx: 1, emote: '' }
            ];

            state.citizens = citizenConfigs.map((cfg, idx) => {
                const wp = patrolWaypoints[cfg.startIdx % patrolWaypoints.length];
                const nextWp = patrolWaypoints[(cfg.startIdx + 1) % patrolWaypoints.length];
                return {
                    ...cfg,
                    id: idx + 1,
                    x: wp.x + (idx % 2 === 0 ? -0.45 : 0.45),
                    y: wp.y,
                    targetIdx: (cfg.startIdx + 1) % patrolWaypoints.length,
                    waypoints: patrolWaypoints,
                    dx: nextWp.x - wp.x,
                    dy: nextWp.y - wp.y,
                    phase: idx * 1.4,
                    pauseTimer: 0
                };
            });

            const roadLoops = [
                [
                    { x: 20.5, y: 1.5 }, { x: 20.5, y: 43.5 }, { x: 34.5, y: 43.5 },
                    { x: 34.5, y: 52.5 }, { x: 20.5, y: 52.5 }, { x: 20.5, y: 68.5 },
                    { x: 20.5, y: 52.5 }, { x: 2.5, y: 52.5 }, { x: 2.5, y: 43.5 },
                    { x: 20.5, y: 43.5 }
                ],
                [
                    { x: 20.5, y: 43.5 }, { x: 2.5, y: 43.5 }, { x: 2.5, y: 52.5 },
                    { x: 4.5, y: 52.5 }, { x: 4.5, y: 61.5 }, { x: 17.5, y: 61.5 },
                    { x: 17.5, y: 68.5 }, { x: 20.5, y: 68.5 }, { x: 20.5, y: 52.5 },
                    { x: 34.5, y: 52.5 }, { x: 34.5, y: 43.5 }
                ]
            ];

            state.vehicles = [
                { kind: 'car', color: '#ef4444', roof: '#fecaca', speed: 3.6, loop: roadLoops[0], wpIdx: 1, x: 20.5, y: 10, dx: 0, dy: 1 },
                { kind: 'car', color: '#3b82f6', roof: '#dbeafe', speed: 3.3, loop: roadLoops[1], wpIdx: 5, x: 10, y: 61.5, dx: 1, dy: 0 },
                { kind: 'moto', color: '#f59e0b', riderShirt: '#10b981', speed: 4.4, loop: roadLoops[0], wpIdx: 4, x: 28, y: 52.5, dx: -1, dy: 0 },
                { kind: 'moto', color: '#14b8a6', riderShirt: '#f43f5e', speed: 4.1, loop: roadLoops[1], wpIdx: 1, x: 12, y: 43.5, dx: -1, dy: 0 },
                { kind: 'bike', color: '#eab308', riderShirt: '#6366f1', speed: 2.1, loop: roadLoops[1], wpIdx: 2, x: 2.5, y: 48, dx: 0, dy: 1 }
            ];
        }

        function updateAmbientLife(dt, now) {
            for (const c of state.citizens) {
                if (c.pauseTimer > 0) {
                    c.pauseTimer -= dt;
                    continue;
                }
                const target = c.waypoints[c.targetIdx];
                const vx = target.x - c.x;
                const vy = target.y - c.y;
                const dist = Math.hypot(vx, vy);

                if (dist < 0.25) {
                    c.targetIdx = (c.targetIdx + 1) % c.waypoints.length;
                    if ((c.id + c.targetIdx) % 3 === 0) c.pauseTimer = 1.4;
                } else {
                    const nx = vx / dist;
                    const ny = vy / dist;
                    c.dx = nx;
                    c.dy = ny;
                    c.x += nx * c.speed * dt;
                    c.y += ny * c.speed * dt;
                }
            }

            let nearGate = false;
            for (const v of state.vehicles) {
                const target = v.loop[v.wpIdx];
                const vx = target.x - v.x;
                const vy = target.y - v.y;
                const dist = Math.hypot(vx, vy);

                if (dist < 0.35) {
                    v.wpIdx = (v.wpIdx + 1) % v.loop.length;
                } else {
                    const nx = vx / dist;
                    const ny = vy / dist;
                    v.dx = nx;
                    v.dy = ny;
                    v.x += nx * v.speed * dt;
                    v.y += ny * v.speed * dt;
                }

                if (Math.hypot(v.x - 20.5, v.y - 3.5) < 5.5) nearGate = true;
            }

            const targetAngle = nearGate ? Math.PI * 0.44 : 0;
            state.gateAngle += (targetAngle - state.gateAngle) * Math.min(1, dt * 6);
            if (nearGate) state.gateBeaconTimer = now;
        }

        let lastFrameTime = performance.now();
        function renderLoop(now) {
            const dt = Math.min(0.05, (now - lastFrameTime) / 1000);
            lastFrameTime = now;

            updateAmbientLife(dt, now);

            if (staticCache.dirty) {
                EnvRenderer.rebuildStaticCache(staticCache, units, AUTUMN_FOLIAGE, state);
            }

            ctx.clearRect(0, 0, canvas.width, canvas.height);
            ctx.fillStyle = state.isNight ? '#0d111a' : '#141824';
            ctx.fillRect(0, 0, canvas.width, canvas.height);

            ctx.save();
            ctx.translate(canvas.width / 2 + state.camX, canvas.height / 2 + state.camY);
            ctx.scale(state.zoom, state.zoom);

            ctx.drawImage(staticCache.groundCanvas, staticCache.offsetX, staticCache.offsetY, staticCache.width, staticCache.height);

            const drawFootprintHighlight = (u, strokeCol, fillCol) => {
                const tr = IsoMath.transformRect(u.coord_x, u.coord_y, u.width, u.height, state.rotation);
                const h00 = IsoMath.rotatedToIso(tr.x, tr.y);
                const h10 = IsoMath.rotatedToIso(tr.x + tr.w, tr.y);
                const h11 = IsoMath.rotatedToIso(tr.x + tr.w, tr.y + tr.h);
                const h01 = IsoMath.rotatedToIso(tr.x, tr.y + tr.h);
                IsoMath.drawIsoQuad(ctx, h00, h10, h11, h01, fillCol, strokeCol, 2.2);
            };

            if (state.hoveredUnit && state.hoveredUnit !== state.selectedUnit) {
                drawFootprintHighlight(state.hoveredUnit, '#38bdf8', 'rgba(56, 189, 248, 0.25)');
            }
            if (state.selectedUnit) {
                drawFootprintHighlight(state.selectedUnit, '#facc15', 'rgba(250, 204, 21, 0.35)');
            }

            ctx.drawImage(staticCache.buildingCanvas, staticCache.offsetX, staticCache.offsetY, staticCache.width, staticCache.height);

            const dynamicEntities = [];
            for (const c of state.citizens) {
                const pt = IsoMath.transformGrid(c.x, c.y, state.rotation);
                dynamicEntities.push({ kind: 'citizen', depth: pt.x + pt.y, data: c });
            }
            for (const v of state.vehicles) {
                const pt = IsoMath.transformGrid(v.x, v.y, state.rotation);
                dynamicEntities.push({ kind: 'vehicle', depth: pt.x + pt.y, data: v });
            }
            const gatePt = IsoMath.transformGrid(20.5, 3.5, state.rotation);
            dynamicEntities.push({ kind: 'gate', depth: gatePt.x + gatePt.y, data: null });

            dynamicEntities.sort((a, b) => a.depth - b.depth);
            for (const ent of dynamicEntities) {
                if (ent.kind === 'citizen') EnvRenderer.drawChibiCitizen(ctx, ent.data, now, state.rotation);
                else if (ent.kind === 'vehicle') EnvRenderer.drawAmbientVehicle(ctx, ent.data, now, state);
                else if (ent.kind === 'gate') EnvRenderer.drawAnimatedSecurityGate(ctx, now, state.gateAngle, state.rotation);
            }

            const canViewTreasury = Boolean(state.viewerContext?.can_view_treasury);
            const pulse = Math.sin(now / 260) * 1.8;
            for (const u of units) {
                let badgeText = null;
                let badgeBg = '#facc15';
                let badgeFg = '#141824';

                if (state.layer === 'occupancy' && u.occupancy_status === 'pending') {
                    badgeText = '!';
                    badgeBg = '#facc15';
                } else if (state.layer === 'treasury' && (canViewTreasury || u.is_my_unit) && u.dues_status === 'unpaid') {
                    badgeText = 'Rp';
                    badgeBg = '#ef4444';
                    badgeFg = '#ffffff';
                } else if (state.layer === 'umkm' && (u.active_stores?.length || 0) > 0) {
                    badgeText = '★';
                    badgeBg = '#f59e0b';
                }

                if (badgeText) {
                    const c = IsoMath.gridToIso(u.coord_x + u.width / 2, u.coord_y + u.height / 2, state.rotation);
                    const by = c.y - 28 + pulse;
                    ctx.fillStyle = badgeBg;
                    ctx.beginPath();
                    ctx.arc(c.x, by, 6.5, 0, Math.PI * 2);
                    ctx.fill();
                    ctx.strokeStyle = '#141824';
                    ctx.lineWidth = 1.2;
                    ctx.stroke();

                    ctx.fillStyle = badgeFg;
                    ctx.font = 'bold 8px "Press Start 2P", monospace';
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';
                    ctx.fillText(badgeText, c.x, by + 0.5);
                }
            }

            // Nomor kavling hanya muncul pada unit yang sedang diklik/dipilih
            if (state.selectedUnit && BuildingRenderer?.drawUnitLabel) {
                const u = state.selectedUnit;
                const c = IsoMath.gridToIso(u.coord_x + u.width / 2, u.coord_y + u.height / 2, state.rotation);
                BuildingRenderer.drawUnitLabel(ctx, u, c.x, c.y - 36, state);
            }

            ctx.restore();
            EnvRenderer.drawMinimap(mCtx, minimapCanvas, units, state);
            requestAnimationFrame(renderLoop);
        }

        try {
            await refreshWorldFromApi();
        } catch (err) {
            console.warn('Gagal memuat data dunia real-time:', err.message);
        }

        initAmbientLife();
        hud = HudController.mount({
            canvas,
            minimapCanvas,
            units,
            state,
            apiClient,
            onCacheDirty: () => { staticCache.dirty = true; },
            onRunTests: executeAllMilestoneTests,
            onReloadWorld: refreshWorldFromApi
        });

        await executeAllMilestoneTests(false);
        document.getElementById('hudScore').textContent = `${state.score} / ${state.totalMissions}`;
        hud.resizeCanvas();
        hud.resetCamera();
        requestAnimationFrame(renderLoop);
    }

    const overlay = document.getElementById('authGateOverlay');
    if (overlay && !overlay.classList.contains('hidden')) {
        window.addEventListener('my-ppmt:auth-ready', () => startMyPpmtGame(), { once: true });
    } else {
        startMyPpmtGame();
    }
})();
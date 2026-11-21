/**
 * ============================================================================
 * MILESTONE 3B: PEMISAHAN RENDERER LINGKUNGAN & SPRITE CHIBI
 * Target File: js/renderers/environment-renderer.js
 * (Untuk Vite Laravel: resources/js/my-ppmt/renderers/environment-renderer.js)
 * ============================================================================
 */
window.MyPpmtModules.EnvironmentRenderer = (() => {
    const {
        GRID_COLS,
        GRID_ROWS,
        MATRIX_BACKGROUND_BLOCKS,
        OUTER_AUTUMN_GARDENS,
        TREE_COORDS
    } = window.MyPpmtModules.MapLayout;

    const {
        snapPt,
        makeIsoGrad,
        transformGrid,
        transformRect,
        rotatedToIso,
        gridToIso,
        drawIsoQuad
    } = window.MyPpmtModules.IsoMath;

    const {
        getUnitPalette,
        drawIsometricHouseAsset,
        drawGuardPostAsset
    } = window.MyPpmtModules.BuildingRenderer;

    function createStaticCacheManager(cacheScale = 2.0) {
        return {
            groundCanvas: document.createElement('canvas'),
            buildingCanvas: document.createElement('canvas'),
            dirty: true,
            offsetX: 0,
            offsetY: 0,
            width: 0,
            height: 0,
            scale: cacheScale,
            lastSortedCount: 0
        };
    }

    function rebuildStaticCache(cache, units, autumnFoliage, viewState) {
        const corners = [
            rotatedToIso(0, 0),
            rotatedToIso(GRID_COLS, 0),
            rotatedToIso(GRID_COLS, GRID_ROWS),
            rotatedToIso(0, GRID_ROWS)
        ];
        const minX = Math.min(...corners.map(c => c.x)) - 70;
        const maxX = Math.max(...corners.map(c => c.x)) + 70;
        const minY = Math.min(...corners.map(c => c.y)) - 80;
        const maxY = Math.max(...corners.map(c => c.y)) + 60;

        cache.offsetX = minX;
        cache.offsetY = minY;
        cache.width = maxX - minX;
        cache.height = maxY - minY;

        const cw = Math.ceil(cache.width * cache.scale);
        const ch = Math.ceil(cache.height * cache.scale);

        cache.groundCanvas.width = cw;
        cache.groundCanvas.height = ch;
        cache.buildingCanvas.width = cw;
        cache.buildingCanvas.height = ch;

        const gCtx = cache.groundCanvas.getContext('2d');
        const bCtx = cache.buildingCanvas.getContext('2d');

        gCtx.setTransform(cache.scale, 0, 0, cache.scale, -minX * cache.scale, -minY * cache.scale);
        bCtx.setTransform(cache.scale, 0, 0, cache.scale, -minX * cache.scale, -minY * cache.scale);

        const ink = '#141824';
        const rot = viewState.rotation;

        // 1. Lapisan Tanah Dasar + Plint 3D Pulau Siteplan
        const b00 = snapPt(rotatedToIso(0, 0));
        const b10 = snapPt(rotatedToIso(GRID_COLS, 0));
        const b11 = snapPt(rotatedToIso(GRID_COLS, GRID_ROWS));
        const b01 = snapPt(rotatedToIso(0, GRID_ROWS));

        const plinthH = 14;
        drawIsoQuad(
            gCtx, b01, b11,
            { x: b11.x, y: b11.y + plinthH },
            { x: b01.x, y: b01.y + plinthH },
            viewState.isNight ? '#1e293b' : '#78716c', ink, 1.5
        );
        drawIsoQuad(
            gCtx, b11, b10,
            { x: b10.x, y: b10.y + plinthH },
            { x: b11.x, y: b11.y + plinthH },
            viewState.isNight ? '#0f172a' : '#57534e', ink, 1.5
        );

        const baseGrad = makeIsoGrad(gCtx, b00, b11, viewState.isNight ? '#21293d' : '#faf7e8', viewState.isNight ? '#161c2b' : '#ebe5ce');
        drawIsoQuad(gCtx, b00, b10, b11, b01, baseGrad, ink, 1.5);

        // Lahan Luar Perumahan -> Kebun Musim Gugur
        for (const ag of OUTER_AUTUMN_GARDENS) {
            const tr = transformRect(ag.x, ag.y, ag.w, ag.h, rot);
            const q00 = snapPt(rotatedToIso(tr.x, tr.y));
            const q10 = snapPt(rotatedToIso(tr.x + tr.w, tr.y));
            const q11 = snapPt(rotatedToIso(tr.x + tr.w, tr.y + tr.h));
            const q01 = snapPt(rotatedToIso(tr.x, tr.y + tr.h));

            const soilGrad = makeIsoGrad(
                gCtx, q00, q11,
                viewState.isNight ? '#3d2518' : '#b86b35',
                viewState.isNight ? '#29180f' : '#8c4a20'
            );
            drawIsoQuad(gCtx, q00, q10, q11, q01, soilGrad, viewState.isNight ? '#1f120b' : '#6b3614', 1.0);

            gCtx.save();
            gCtx.beginPath();
            gCtx.moveTo(q00.x, q00.y);
            gCtx.lineTo(q10.x, q10.y);
            gCtx.lineTo(q11.x, q11.y);
            gCtx.lineTo(q01.x, q01.y);
            gCtx.closePath();
            gCtx.clip();

            for (let fy = ag.y + 0.8; fy < ag.y + ag.h; fy += 1.6) {
                const fStart = snapPt(gridToIso(ag.x + 0.3, fy, rot));
                const fEnd = snapPt(gridToIso(ag.x + ag.w - 0.3, fy, rot));
                gCtx.strokeStyle = viewState.isNight ? 'rgba(92, 51, 23, 0.55)' : 'rgba(232, 138, 56, 0.55)';
                gCtx.lineWidth = 1.6;
                gCtx.setLineDash([4, 3]);
                gCtx.beginPath();
                gCtx.moveTo(fStart.x, fStart.y);
                gCtx.lineTo(fEnd.x, fEnd.y);
                gCtx.stroke();
            }
            gCtx.setLineDash([]);
            gCtx.restore();
        }

        // Jalan ROW 6 & 7 serta Taman RTH
        for (const bg of MATRIX_BACKGROUND_BLOCKS) {
            const tr = transformRect(bg.x, bg.y, bg.w, bg.h, rot);
            const q00 = snapPt(rotatedToIso(tr.x, tr.y));
            const q10 = snapPt(rotatedToIso(tr.x + tr.w, tr.y));
            const q11 = snapPt(rotatedToIso(tr.x + tr.w, tr.y + tr.h));
            const q01 = snapPt(rotatedToIso(tr.x, tr.y + tr.h));

            if (bg.type === 'road') {
                const roadFill = viewState.isNight ? '#2b3446' : '#cbd5e1';
                drawIsoQuad(gCtx, q00, q10, q11, q01, roadFill, viewState.isNight ? '#1e2636' : '#94a3b8', 0.6);

                const cStart = bg.w >= bg.h
                    ? snapPt(rotatedToIso(tr.x + 0.3, tr.y + tr.h / 2))
                    : snapPt(rotatedToIso(tr.x + tr.w / 2, tr.y + 0.3));
                const cEnd = bg.w >= bg.h
                    ? snapPt(rotatedToIso(tr.x + tr.w - 0.3, tr.y + tr.h / 2))
                    : snapPt(rotatedToIso(tr.x + tr.w / 2, tr.y + tr.h - 0.3));

                gCtx.save();
                gCtx.strokeStyle = viewState.isNight ? 'rgba(248, 250, 252, 0.25)' : 'rgba(255, 255, 255, 0.72)';
                gCtx.lineWidth = 0.9;
                gCtx.setLineDash([3, 3]);
                gCtx.beginPath();
                gCtx.moveTo(cStart.x, cStart.y);
                gCtx.lineTo(cEnd.x, cEnd.y);
                gCtx.stroke();
                gCtx.restore();
            } else {
                const parkFill = viewState.isNight ? '#214027' : '#81b29a';
                drawIsoQuad(gCtx, q00, q10, q11, q01, parkFill, viewState.isNight ? '#162b1a' : '#5f947c', 0.7);
            }
        }

        // 2. Lapisan Bangunan & Pepohonan (Topological Depth Sorting)
        const renderables = [];
        for (const u of units) {
            const tr = transformRect(u.coord_x, u.coord_y, u.width, u.height, rot);
            const frontCorner = rotatedToIso(tr.x + tr.w, tr.y + tr.h);
            renderables.push({
                kind: 'unit',
                minX: tr.x, maxX: tr.x + tr.w,
                minY: tr.y, maxY: tr.y + tr.h,
                screenY: frontCorner.y,
                data: u
            });
        }

        const guardPos = transformRect(18, 3, 1, 1, rot);
        const guardFront = rotatedToIso(guardPos.x + guardPos.w, guardPos.y + guardPos.h);
        renderables.push({
            kind: 'guard',
            minX: guardPos.x, maxX: guardPos.x + guardPos.w,
            minY: guardPos.y, maxY: guardPos.y + guardPos.h,
            screenY: guardFront.y,
            data: guardPos
        });

        for (const [tx, ty] of TREE_COORDS) {
            const pt = transformGrid(tx, ty, rot);
            const iso = rotatedToIso(pt.x, pt.y);
            renderables.push({
                kind: 'tree',
                minX: pt.x - 0.2, maxX: pt.x + 0.2,
                minY: pt.y - 0.2, maxY: pt.y + 0.2,
                screenY: iso.y,
                data: pt
            });
        }

        for (const af of autumnFoliage) {
            const pt = transformGrid(af.x, af.y, rot);
            const iso = rotatedToIso(pt.x, pt.y);
            renderables.push({
                kind: af.kind,
                minX: pt.x - 0.2, maxX: pt.x + 0.2,
                minY: pt.y - 0.2, maxY: pt.y + 0.2,
                screenY: iso.y,
                data: { ...pt, variant: af.variant }
            });
        }

        const n = renderables.length;
        const visited = new Array(n).fill(false);
        const sortedRenderables = [];
        const isBehind = (a, b) => {
            if (a.maxX <= b.minX + 0.01 && a.maxY <= b.minY + 0.01) return true;
            if (a.maxX <= b.minX + 0.01 && a.minY < b.maxY - 0.01) return true;
            if (a.maxY <= b.minY + 0.01 && a.minX < b.maxX - 0.01) return true;
            return false;
        };

        renderables.sort((a, b) => a.screenY - b.screenY);
        function visitNode(idx) {
            if (visited[idx]) return;
            visited[idx] = true;
            const curr = renderables[idx];
            for (let j = 0; j < n; j++) {
                if (idx !== j && !visited[j] && isBehind(renderables[j], curr)) {
                    visitNode(j);
                }
            }
            sortedRenderables.push(curr);
        }
        for (let i = 0; i < n; i++) visitNode(i);

        for (const item of sortedRenderables) {
            if (item.kind === 'unit') {
                drawIsometricHouseAsset(bCtx, item.data, viewState);
            } else if (item.kind === 'guard') {
                drawGuardPostAsset(bCtx, item.data);
            } else if (item.kind === 'tree') {
                const tc = snapPt(rotatedToIso(item.data.x, item.data.y));
                bCtx.fillStyle = 'rgba(20, 24, 36, 0.25)';
                bCtx.beginPath();
                bCtx.ellipse(tc.x + 1.5, tc.y + 1, 5.2, 2.6, 0, 0, Math.PI * 2);
                bCtx.fill();

                bCtx.fillStyle = '#5c3a21';
                bCtx.fillRect(tc.x - 1.3, tc.y - 6.5, 2.6, 6.5);
                bCtx.strokeStyle = ink;
                bCtx.lineWidth = 0.7;
                bCtx.strokeRect(tc.x - 1.3, tc.y - 6.5, 2.6, 6.5);

                const crownGrad = bCtx.createRadialGradient(tc.x - 2, tc.y - 12.5, 1, tc.x, tc.y - 10, 7.5);
                crownGrad.addColorStop(0, viewState.isNight ? '#386641' : '#a7c957');
                crownGrad.addColorStop(0.65, viewState.isNight ? '#214027' : '#6a994e');
                crownGrad.addColorStop(1, viewState.isNight ? '#132617' : '#386641');
                bCtx.beginPath();
                bCtx.arc(tc.x, tc.y - 10.5, 6.4, 0, Math.PI * 2);
                bCtx.fillStyle = crownGrad;
                bCtx.fill();
                bCtx.strokeStyle = ink;
                bCtx.lineWidth = 0.85;
                bCtx.stroke();
            } else if (item.kind === 'autumnShrub') {
                const sc = snapPt(rotatedToIso(item.data.x, item.data.y));
                const v = item.data.variant;
                const palettes = [
                    { dark: '#7c2d12', mid: '#c2410c', light: '#fb923c' },
                    { dark: '#78350f', mid: '#b45309', light: '#f59e0b' },
                    { dark: '#5c2415', mid: '#9a3412', light: '#ea580c' }
                ];
                const nightPalettes = [
                    { dark: '#3b1509', mid: '#632208', light: '#8a3714' },
                    { dark: '#381908', mid: '#5e2b07', light: '#85420c' },
                    { dark: '#2e120a', mid: '#4f1b0a', light: '#732b10' }
                ];
                const pal = (viewState.isNight ? nightPalettes : palettes)[v % 3];
                const px = 1.0;

                bCtx.save();
                bCtx.imageSmoothingEnabled = false;
                bCtx.fillStyle = 'rgba(20, 24, 36, 0.32)';
                bCtx.fillRect(sc.x - 4 * px, sc.y - 0.5 * px, 8 * px, 2 * px);
                bCtx.fillStyle = ink;
                bCtx.fillRect(sc.x - 4 * px, sc.y - 3 * px, 8 * px, 3 * px);
                bCtx.fillRect(sc.x - 3 * px, sc.y - 4.5 * px, 6 * px, 2 * px);
                bCtx.fillStyle = pal.dark;
                bCtx.fillRect(sc.x - 3 * px, sc.y - 2.5 * px, 6 * px, 2 * px);
                bCtx.fillStyle = pal.mid;
                bCtx.fillRect(sc.x - 3 * px, sc.y - 3.5 * px, 5 * px, 2 * px);
                bCtx.fillStyle = pal.light;
                bCtx.fillRect(sc.x - 2 * px, sc.y - 4 * px, 3 * px, 1.5 * px);
                bCtx.restore();
            } else if (item.kind === 'autumnTree') {
                const tc = snapPt(rotatedToIso(item.data.x, item.data.y));
                const v = item.data.variant;
                const treePalettes = [
                    { shadow: '#7c2d12', mid: '#ea580c', highlight: '#fdba74' },
                    { shadow: '#6b2710', mid: '#c2410c', highlight: '#fb923c' },
                    { shadow: '#713f12', mid: '#d97706', highlight: '#fcd34d' }
                ];
                const nightTreePalettes = [
                    { shadow: '#381408', mid: '#6e2908', highlight: '#8f3c14' },
                    { shadow: '#331208', mid: '#5e2008', highlight: '#7d3012' },
                    { shadow: '#361e09', mid: '#663806', highlight: '#854e0f' }
                ];
                const pal = (viewState.isNight ? nightTreePalettes : treePalettes)[v % 3];
                const px = 1.05;

                bCtx.save();
                bCtx.imageSmoothingEnabled = false;
                bCtx.fillStyle = 'rgba(20, 24, 36, 0.34)';
                bCtx.fillRect(tc.x - 4 * px, tc.y - 0.5 * px, 9 * px, 2.5 * px);
                bCtx.fillStyle = ink;
                bCtx.fillRect(tc.x - 1.5 * px, tc.y - 6 * px, 3 * px, 6 * px);
                bCtx.fillStyle = '#5c3419';
                bCtx.fillRect(tc.x - 0.8 * px, tc.y - 5.5 * px, 1.8 * px, 5 * px);
                bCtx.fillStyle = ink;
                bCtx.fillRect(tc.x - 5 * px, tc.y - 11 * px, 10 * px, 5 * px);
                bCtx.fillRect(tc.x - 4 * px, tc.y - 13 * px, 8 * px, 2 * px);
                bCtx.fillRect(tc.x - 2.5 * px, tc.y - 14.2 * px, 5 * px, 1.5 * px);
                bCtx.fillStyle = pal.shadow;
                bCtx.fillRect(tc.x - 4.2 * px, tc.y - 10.2 * px, 8.4 * px, 3.6 * px);
                bCtx.fillStyle = pal.mid;
                bCtx.fillRect(tc.x - 4.2 * px, tc.y - 11.8 * px, 7.6 * px, 3.2 * px);
                bCtx.fillRect(tc.x - 3.2 * px, tc.y - 12.6 * px, 6.4 * px, 1.5 * px);
                bCtx.fillStyle = pal.highlight;
                bCtx.fillRect(tc.x - 2.5 * px, tc.y - 13.4 * px, 4.2 * px, 1.8 * px);
                bCtx.restore();
            }
        }

        cache.lastSortedCount = sortedRenderables.length;
        cache.dirty = false;
    }

    function drawChibiCitizen(targetCtx, c, now, rotation) {
        const iso = gridToIso(c.x, c.y, rotation);
        const stepFrame = Math.floor(now / 110) + c.id;
        const isWalking = c.pauseTimer <= 0;
        const bob = isWalking ? (stepFrame % 2 === 0 ? 0 : -1.5) : 0;
        const legSwing = isWalking ? ((stepFrame % 4) - 1.5) * 1.1 : 0;
        const ink = '#141824';
        const px = 1.0;

        targetCtx.save();
        targetCtx.imageSmoothingEnabled = false;

        targetCtx.fillStyle = 'rgba(20, 24, 36, 0.35)';
        targetCtx.fillRect(iso.x - 3.5 * px, iso.y - 0.5 * px, 7 * px, 2 * px);

        targetCtx.fillStyle = c.pants;
        targetCtx.fillRect(iso.x - 2.2 * px + legSwing * 0.5, iso.y - 3.5 * px, 1.8 * px, 3.2 * px);
        targetCtx.fillRect(iso.x + 0.4 * px - legSwing * 0.5, iso.y - 3.5 * px, 1.8 * px, 3.2 * px);

        const bodyY = iso.y - 7.5 * px + bob;
        targetCtx.fillStyle = ink;
        targetCtx.fillRect(iso.x - 3 * px, bodyY - 0.5 * px, 6 * px, 4.8 * px);
        targetCtx.fillStyle = c.shirt;
        targetCtx.fillRect(iso.x - 2.3 * px, bodyY, 4.6 * px, 3.8 * px);

        const headY = bodyY - 6.8 * px;
        targetCtx.fillStyle = ink;
        targetCtx.fillRect(iso.x - 4.2 * px, headY - 0.5 * px, 8.4 * px, 7.2 * px);
        targetCtx.fillStyle = c.skin;
        targetCtx.fillRect(iso.x - 3.5 * px, headY + 0.5 * px, 7 * px, 5.5 * px);

        targetCtx.fillStyle = c.hair;
        targetCtx.fillRect(iso.x - 3.8 * px, headY - 0.5 * px, 7.6 * px, 2.6 * px);
        if (c.role === 'guard') {
            targetCtx.fillStyle = '#1d4ed8';
            targetCtx.fillRect(iso.x - 4 * px, headY - 1.5 * px, 8 * px, 2 * px);
            targetCtx.fillStyle = '#facc15';
            targetCtx.fillRect(iso.x - 1 * px, headY - 1 * px, 2 * px, 1 * px);
        }

        targetCtx.fillStyle = ink;
        targetCtx.fillRect(iso.x - 2.2 * px, headY + 3.2 * px, 1.3 * px, 1.6 * px);
        targetCtx.fillRect(iso.x + 1.0 * px, headY + 3.2 * px, 1.3 * px, 1.6 * px);

        if (c.pauseTimer > 0 && c.emote) {
            const bx = iso.x;
            const by = headY - 6.5 * px;
            targetCtx.fillStyle = '#f4f1de';
            targetCtx.fillRect(bx - 4.5, by - 4.5, 9, 8);
            targetCtx.strokeStyle = ink;
            targetCtx.lineWidth = 0.9;
            targetCtx.strokeRect(bx - 4.5, by - 4.5, 9, 8);
            targetCtx.fillStyle = c.emote === '♥' ? '#e07a5f' : '#141824';
            targetCtx.font = 'bold 7px sans-serif';
            targetCtx.textAlign = 'center';
            targetCtx.textBaseline = 'middle';
            targetCtx.fillText(c.emote, bx, by);
        }

        targetCtx.restore();
    }

    function drawAmbientVehicle(targetCtx, v, now, viewState) {
        const iso = gridToIso(v.x, v.y, viewState.rotation);
        const rDir = transformGrid(v.dx, v.dy, viewState.rotation);
        const originDir = transformGrid(0, 0, viewState.rotation);
        const dirX = rDir.x - originDir.x;
        const dirY = rDir.y - originDir.y;
        const isoDirX = (dirX - dirY);
        const facingRight = isoDirX >= 0;

        const vib = (Math.floor(now / 110) % 2 === 0) ? 0 : -0.6;
        const ink = '#141824';
        const px = 1.05;

        targetCtx.save();
        targetCtx.imageSmoothingEnabled = false;

        if (v.kind === 'car') {
            targetCtx.fillStyle = 'rgba(20, 24, 36, 0.35)';
            targetCtx.fillRect(iso.x - 8 * px, iso.y - 1 * px, 16 * px, 4 * px);

            targetCtx.fillStyle = ink;
            targetCtx.fillRect(iso.x - 6 * px, iso.y - 1.5 * px, 3.2 * px, 3 * px);
            targetCtx.fillRect(iso.x + 2.8 * px, iso.y - 1.5 * px, 3.2 * px, 3 * px);

            const by = iso.y - 5.5 * px + vib;
            targetCtx.fillStyle = ink;
            targetCtx.fillRect(iso.x - 8 * px, by - 0.5 * px, 16 * px, 5.2 * px);
            targetCtx.fillStyle = v.color;
            targetCtx.fillRect(iso.x - 7.2 * px, by, 14.4 * px, 4.2 * px);

            targetCtx.fillStyle = ink;
            targetCtx.fillRect(iso.x - 5 * px, by - 4 * px, 10 * px, 4 * px);
            targetCtx.fillStyle = v.roof;
            targetCtx.fillRect(iso.x - 4.2 * px, by - 3.3 * px, 8.4 * px, 3.2 * px);
            targetCtx.fillStyle = '#38bdf8';
            targetCtx.fillRect(iso.x - 3.4 * px, by - 2.6 * px, 3 * px, 2 * px);
            targetCtx.fillRect(iso.x + 0.4 * px, by - 2.6 * px, 3 * px, 2 * px);

            if (viewState.isNight) {
                const lx = facingRight ? iso.x + 7.5 * px : iso.x - 7.5 * px;
                const beamDir = facingRight ? 1 : -1;
                const grad = targetCtx.createLinearGradient(lx, by + 2, lx + beamDir * 18, by + 4);
                grad.addColorStop(0, 'rgba(254, 240, 138, 0.7)');
                grad.addColorStop(1, 'rgba(254, 240, 138, 0)');
                targetCtx.fillStyle = grad;
                targetCtx.beginPath();
                targetCtx.moveTo(lx, by + 1);
                targetCtx.lineTo(lx + beamDir * 18, by - 3);
                targetCtx.lineTo(lx + beamDir * 18, by + 7);
                targetCtx.closePath();
                targetCtx.fill();
            }
        } else {
            targetCtx.fillStyle = 'rgba(20, 24, 36, 0.32)';
            targetCtx.fillRect(iso.x - 4.5 * px, iso.y - 0.5 * px, 9 * px, 2.2 * px);

            targetCtx.fillStyle = ink;
            targetCtx.fillRect(iso.x - 4 * px, iso.y - 2 * px, 2.4 * px, 2.4 * px);
            targetCtx.fillRect(iso.x + 1.6 * px, iso.y - 2 * px, 2.4 * px, 2.4 * px);

            const by = iso.y - 5 * px + vib;
            targetCtx.fillStyle = v.color;
            targetCtx.fillRect(iso.x - 3.5 * px, by, 7 * px, 3 * px);

            targetCtx.fillStyle = v.riderShirt;
            targetCtx.fillRect(iso.x - 2 * px, by - 3.5 * px, 4 * px, 3.5 * px);
            targetCtx.fillStyle = ink;
            targetCtx.fillRect(iso.x - 2.6 * px, by - 8 * px, 5.2 * px, 4.6 * px);
            targetCtx.fillStyle = v.kind === 'moto' ? '#f4f1de' : '#f8c8a0';
            targetCtx.fillRect(iso.x - 2 * px, by - 7.4 * px, 4 * px, 3.6 * px);
        }

        targetCtx.restore();
    }

    function drawAnimatedSecurityGate(targetCtx, now, gateAngle, rotation) {
        const hingeIso = gridToIso(19.1, 3.5, rotation);
        const ink = '#141824';

        targetCtx.save();
        targetCtx.fillStyle = '#e07a5f';
        targetCtx.fillRect(hingeIso.x - 2, hingeIso.y - 9, 4, 9);
        targetCtx.strokeStyle = ink;
        targetCtx.lineWidth = 0.9;
        targetCtx.strokeRect(hingeIso.x - 2, hingeIso.y - 9, 4, 9);

        if (gateAngle > 0.08) {
            const blink = Math.floor(now / 180) % 2 === 0;
            targetCtx.fillStyle = blink ? '#ef4444' : '#facc15';
            targetCtx.beginPath();
            targetCtx.arc(hingeIso.x, hingeIso.y - 10.5, 2.2, 0, Math.PI * 2);
            targetCtx.fill();
        }

        targetCtx.translate(hingeIso.x, hingeIso.y - 6.5);
        targetCtx.rotate(-gateAngle);
        const barLen = 24;
        for (let s = 0; s < 4; s++) {
            targetCtx.fillStyle = s % 2 === 0 ? '#ef4444' : '#ffffff';
            targetCtx.fillRect(s * (barLen / 4), -1.3, barLen / 4, 2.6);
        }
        targetCtx.strokeStyle = ink;
        targetCtx.lineWidth = 0.8;
        targetCtx.strokeRect(0, -1.3, barLen, 2.6);
        targetCtx.restore();
    }

    function drawMinimap(mCtx, minimapCanvas, units, viewState) {
        const sx = minimapCanvas.width / GRID_COLS;
        const sy = minimapCanvas.height / GRID_ROWS;

        mCtx.clearRect(0, 0, minimapCanvas.width, minimapCanvas.height);
        mCtx.fillStyle = viewState.isNight ? '#1b2234' : '#f4f1de';
        mCtx.fillRect(0, 0, minimapCanvas.width, minimapCanvas.height);

        for (const bg of MATRIX_BACKGROUND_BLOCKS) {
            mCtx.fillStyle = bg.type === 'road' ? '#94a3b8' : (bg.type === 'guard' ? '#e07a5f' : '#6a994e');
            mCtx.fillRect(bg.x * sx, bg.y * sy, bg.w * sx, bg.h * sy);
        }

        for (const ag of OUTER_AUTUMN_GARDENS) {
            mCtx.fillStyle = '#c26d38';
            mCtx.fillRect(ag.x * sx, ag.y * sy, ag.w * sx, ag.h * sy);
        }

        for (const u of units) {
            const pal = getUnitPalette(u, viewState);
            mCtx.fillStyle = pal.top;
            mCtx.fillRect(u.coord_x * sx, u.coord_y * sy, u.width * sx, u.height * sy);
            mCtx.strokeStyle = 'rgba(20, 24, 36, 0.35)';
            mCtx.lineWidth = 0.5;
            mCtx.strokeRect(u.coord_x * sx, u.coord_y * sy, u.width * sx, u.height * sy);
        }

        for (const v of viewState.vehicles) {
            mCtx.fillStyle = '#ef4444';
            mCtx.fillRect(v.x * sx - 1.2, v.y * sy - 1.2, 2.4, 2.4);
        }

        if (viewState.selectedUnit) {
            const su = viewState.selectedUnit;
            mCtx.strokeStyle = '#facc15';
            mCtx.lineWidth = 1.6;
            mCtx.strokeRect(su.coord_x * sx - 1, su.coord_y * sy - 1, su.width * sx + 2, su.height * sy + 2);
        }
    }

    // Pengujian Integritas Cache & Topological Depth-Sort Milestone 3
    function runSelfTest(cache, units, autumnFoliage) {
        const errors = [];
        const expectedTotal = units.length + 1 + TREE_COORDS.length + autumnFoliage.length;

        if (cache.dirty || cache.groundCanvas.width <= 0 || cache.buildingCanvas.width <= 0) {
            errors.push('Offscreen ground/building canvas belum ter-render dengan dimensi valid.');
        }
        if (cache.lastSortedCount !== expectedTotal) {
            errors.push(`Jumlah entitas depth-sort (${cache.lastSortedCount}) tidak cocok dengan total (${expectedTotal}).`);
        }

        return {
            passed: errors.length === 0,
            totalChecks: 2,
            errors
        };
    }

    return {
        createStaticCacheManager,
        rebuildStaticCache,
        drawChibiCitizen,
        drawAmbientVehicle,
        drawAnimatedSecurityGate,
        drawMinimap,
        runSelfTest
    };
})();
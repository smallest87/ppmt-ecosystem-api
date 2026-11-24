/**
 * ============================================================================
 * MILESTONE 3A: PEMISAHAN RENDERER BANGUNAN (MyPpmtModules.BuildingRenderer)
 * Target File: resources/js/my-ppmt/renderers/building-renderer.js
 * ============================================================================
 */
window.MyPpmtModules.BuildingRenderer = (() => {
    const {
        makeIsoGrad,
        transformRect,
        rotatedToIso,
        drawIsoQuad
    } = window.MyPpmtModules.IsoMath;

    const customSprites = new Map();

    // Anchor pixel pada gambar PNG 600x443
    const SPRITE_SPECS = {
        '2x3-fl-left': {
            url: '/images/my-ppmt/house-2x3-fl-left.png',
            anchorX: 319,
            anchorY: 431,
            leftX: 128,
            rightX: 607
        },
        '2x3-fl-right': {
            url: '/images/my-ppmt/house-2x3-fl-right.png',
            anchorX: 319,
            anchorY: 431,
            leftX: 128,
            rightX: 607
        },
        '2x3-fr-left': {
            url: '/images/my-ppmt/house-2x3-fr-left.png',
            anchorX: 319,
            anchorY: 431,
            leftX: 32,
            rightX: 511
        },
        '2x3-fr-right': {
            url: '/images/my-ppmt/house-2x3-fr-right.png',
            anchorX: 319,
            anchorY: 431,
            leftX: 32,
            rightX: 511
        },
        '2x3-bl-left': {
            url: '/images/my-ppmt/house-2x3-bl-left.png',
            anchorX: 300,
            anchorY: 439,
            leftX: 4,
            rightX: 508
        },
        '2x3-bl-right': {
            url: '/images/my-ppmt/house-2x3-bl-right.png',
            anchorX: 300,
            anchorY: 439,
            leftX: 4,
            rightX: 508
        },
        '2x3-br-left': {
            url: '/images/my-ppmt/house-2x3-br-left.png',
            anchorX: 319,
            anchorY: 431,
            leftX: 128,
            rightX: 607
        },
        '2x3-br-right': {
            url: '/images/my-ppmt/house-2x3-br-right.png',
            anchorX: 319,
            anchorY: 431,
            leftX: 128,
            rightX: 607
        }
    };

    function registerCustomSprite(key, imageElement, anchor = { x: 0.5, y: 0.85 }) {
        customSprites.set(key, { image: imageElement, anchor });
    }

    function initSpriteLoader() {
        Object.entries(SPRITE_SPECS).forEach(([key, spec]) => {
            if (customSprites.has(key)) return;
            const img = new Image();
            const entry = {
                image: img,
                loaded: false,
                anchorX: spec.anchorX,
                anchorY: spec.anchorY,
                leftX: spec.leftX,
                rightX: spec.rightX
            };
            img.onload = () => {
                entry.loaded = true;
                window.dispatchEvent(new CustomEvent('my-ppmt:sprite-loaded', { detail: { key } }));
            };
            img.onerror = () => {
                entry.loaded = false;
            };
            img.src = spec.url;
            customSprites.set(key, entry);
        });
    }

    initSpriteLoader();

    function getCarportSide(u) {
        if (u.carport_side === 'left' || u.carport_side === 'right') {
            return u.carport_side;
        }
        // Fallback sementara sebelum seluruh kavling diperiksa manual
        const numMatch = String(u.code || u.unit_code || '').match(/(\d+)/);
        if (numMatch) {
            return parseInt(numMatch[1], 10) % 2 === 1 ? 'left' : 'right';
        }
        return 'left';
    }

    function pickLoadedSprite(candidates) {
        for (const key of candidates) {
            const entry = customSprites.get(key);
            if (entry && entry.loaded && entry.image.naturalWidth > 0) {
                return entry;
            }
        }
        return null;
    }

    function resolveHouseSprite(u, gw, gh, effectiveFacing) {
        const is2x3 = (u.width === 2 && u.height === 3) || (u.width === 3 && u.height === 2);
        if (!is2x3) return null;

        const carportSide = getCarportSide(u);

        // effectiveFacing === 1: tampak depan kiri-bawah ('fl')
        if (effectiveFacing === 1) {
            const entry = pickLoadedSprite([
                `2x3-fl-${carportSide}`,
                '2x3-fl-left',
                '2x3-fl-right'
            ]);
            return entry ? { ...entry, flipX: false } : null;
        }

        // effectiveFacing === 0: tampak depan kanan-bawah ('fr')
        if (effectiveFacing === 0) {
            const entry = pickLoadedSprite([
                `2x3-fr-${carportSide}`,
                `2x3-fl-${carportSide}`,
                '2x3-fr-left',
                '2x3-fr-right'
            ]);
            return entry ? { ...entry, flipX: false } : null;
        }

        // Tampak belakang di orientasi ini memakai '2x3-br-right' dan '2x3-br-left'
        if (effectiveFacing === 2 || effectiveFacing === 3) {
            const entry = pickLoadedSprite([
                `2x3-br-${carportSide}`,
                '2x3-br-right',
                '2x3-br-left'
            ]);
            return entry ? { ...entry, flipX: false } : null;
        }

        return null;
    }

    function isPublicFacility(u) {
        return u.unit_type === 'fasum' || u.unit_type === 'public_facility';
    }

    function isCommercial(u) {
        return u.unit_type === 'ruko' || u.unit_type === 'commercial';
    }

    function getUnitCode(u) {
        return u.code || u.unit_code || 'UNIT';
    }

    function getUnitPalette(u, viewState) {
        const canViewTreasury = Boolean(viewState.viewerContext?.can_view_treasury);

        if (viewState.layer === 'treasury' && (canViewTreasury || u.is_my_unit)) {
            if (u.dues_status === 'paid') return { top: '#81b29a', left: '#5f947c', right: '#45735e' };
            if (u.dues_status === 'unpaid') return { top: '#e07a5f', left: '#c45b3e', right: '#9e4026' };
            return { top: '#cbd5e1', left: '#94a3b8', right: '#64748b' };
        }
        if (viewState.layer === 'umkm') {
            if ((u.active_stores?.length || 0) > 0) return { top: '#f2cc8f', left: '#d9a75b', right: '#b88337' };
            return { top: '#cbd5e1', left: '#94a3b8', right: '#64748b' };
        }

        if (isPublicFacility(u)) return { top: '#6a994e', left: '#4c7336', right: '#385726' };
        if (isCommercial(u)) return { top: '#f2cc8f', left: '#d9a75b', right: '#b88337' };
        if (u.occupancy_status === 'unclaimed') return { top: '#d8d4c5', left: '#b8b3a2', right: '#999482' };
        if (u.occupancy_status === 'pending') return { top: '#facc15', left: '#eab308', right: '#ca8a04' };

        const blockColors = {
            A: { top: '#f28482', left: '#d66563', right: '#b34846' },
            B: { top: '#8e9aaf', left: '#6f7c91', right: '#546073' },
            C: { top: '#669bbc', left: '#4a7c9c', right: '#355e78' },
            D: { top: '#81b29a', left: '#5f947c', right: '#45735e' }
        };
        return blockColors[u.block] || { top: '#94a3b8', left: '#64748b', right: '#475569' };
    }

    function drawUnitLabel(targetCtx, u, x, y, viewState) {
        const pal = getUnitPalette(u, viewState);
        targetCtx.font = 'bold 8px "VT323", monospace';
        targetCtx.textAlign = 'center';
        targetCtx.textBaseline = 'middle';
        const text = u.is_my_unit ? `★ ${getUnitCode(u)}` : getUnitCode(u);
        const w = targetCtx.measureText(text).width + 6;
        const h = 10;

        targetCtx.fillStyle = viewState.isNight ? 'rgba(20, 24, 36, 0.9)' : 'rgba(244, 241, 222, 0.94)';
        targetCtx.beginPath();
        targetCtx.roundRect(x - w / 2, y - h / 2, w, h, 3);
        targetCtx.fill();
        targetCtx.strokeStyle = u.is_my_unit ? '#facc15' : pal.top;
        targetCtx.lineWidth = u.is_my_unit ? 1.5 : 1.1;
        targetCtx.stroke();

        targetCtx.fillStyle = viewState.isNight ? '#f4f1de' : '#141824';
        targetCtx.fillText(text, x, y + 0.5);
    }

    function drawIsometricHouseAsset(targetCtx, u, viewState) {
        const tr = transformRect(u.coord_x, u.coord_y, u.width, u.height, viewState.rotation);
        const gx = tr.x;
        const gy = tr.y;
        const gw = tr.w;
        const gh = tr.h;
        const pal = getUnitPalette(u, viewState);
        const ink = '#141824';

        const p00 = rotatedToIso(gx, gy);
        const p10 = rotatedToIso(gx + gw, gy);
        const p11 = rotatedToIso(gx + gw, gy + gh);
        const p01 = rotatedToIso(gx, gy + gh);

        if (u.occupancy_status === 'unclaimed') {
            const lotFill = viewState.isNight ? '#2c362e' : '#dfe6c8';
            drawIsoQuad(targetCtx, p00, p10, p11, p01, lotFill, ink, 0.85);
            const c = rotatedToIso(gx + gw / 2, gy + gh / 2);
            targetCtx.fillStyle = '#5c3a21';
            targetCtx.fillRect(c.x - 0.8, c.y - 7, 1.6, 7);
            targetCtx.fillStyle = '#f2cc8f';
            targetCtx.fillRect(c.x - 4.5, c.y - 11, 9, 5);
            targetCtx.strokeStyle = ink;
            targetCtx.lineWidth = 0.7;
            targetCtx.strokeRect(c.x - 4.5, c.y - 11, 9, 5);
            return;
        }

        if (isPublicFacility(u)) {
            drawIsoQuad(targetCtx, p00, p10, p11, p01, viewState.isNight ? '#1e3326' : '#cbe0b4', ink, 0.9);
            const pad = 0.25;
            const b00 = rotatedToIso(gx + pad, gy + pad);
            const b10 = rotatedToIso(gx + gw - pad, gy + pad);
            const b11 = rotatedToIso(gx + gw - pad, gy + gh - pad);
            const b01 = rotatedToIso(gx + pad, gy + gh - pad);
            const wallH = 14;
            const t00 = { x: b00.x, y: b00.y - wallH };
            const t10 = { x: b10.x, y: b10.y - wallH };
            const t11 = { x: b11.x, y: b11.y - wallH };
            const t01 = { x: b01.x, y: b01.y - wallH };

            drawIsoQuad(targetCtx, t01, t11, b11, b01, '#f4f1de', ink, 0.9);
            drawIsoQuad(targetCtx, t11, t10, b10, b11, '#e6e1c5', ink, 0.9);
            drawIsoQuad(targetCtx, t00, t10, t11, t01, '#81b29a', ink, 0.9);

            const mc = rotatedToIso(gx + gw / 2, gy + gh / 2);
            targetCtx.beginPath();
            targetCtx.arc(mc.x, mc.y - wallH - 3, 9, Math.PI, 0);
            targetCtx.fillStyle = '#f2cc8f';
            targetCtx.fill();
            targetCtx.strokeStyle = ink;
            targetCtx.lineWidth = 1;
            targetCtx.stroke();
            return;
        }

        if (isCommercial(u)) {
            drawIsoQuad(targetCtx, p00, p10, p11, p01, viewState.isNight ? '#2c3545' : '#cbd5e1', ink, 0.9);
            const h = 22;
            const b00 = rotatedToIso(gx + 0.08, gy + 0.08);
            const b10 = rotatedToIso(gx + gw - 0.08, gy + 0.08);
            const b11 = rotatedToIso(gx + gw - 0.08, gy + gh - 0.08);
            const b01 = rotatedToIso(gx + 0.08, gy + gh - 0.08);
            const t00 = { x: b00.x, y: b00.y - h };
            const t10 = { x: b10.x, y: b10.y - h };
            const t11 = { x: b11.x, y: b11.y - h };
            const t01 = { x: b01.x, y: b01.y - h };

            const rLeft = makeIsoGrad(targetCtx, t01, b11, pal.left, ink);
            const rRight = makeIsoGrad(targetCtx, t11, b10, pal.right, ink);
            drawIsoQuad(targetCtx, t01, t11, b11, b01, rLeft, ink, 0.9);
            drawIsoQuad(targetCtx, t11, t10, b10, b11, rRight, ink, 0.9);
            drawIsoQuad(targetCtx, t00, t10, t11, t01, pal.top, ink, 0.9);

            const mid01 = { x: (t01.x + b01.x) / 2, y: (t01.y + b01.y) / 2 };
            const mid11 = { x: (t11.x + b11.x) / 2, y: (t11.y + b11.y) / 2 };
            const mid10 = { x: (t10.x + b10.x) / 2, y: (t10.y + b10.y) / 2 };
            targetCtx.strokeStyle = '#f4f1de';
            targetCtx.lineWidth = 1.1;
            targetCtx.beginPath();
            targetCtx.moveTo(mid01.x, mid01.y);
            targetCtx.lineTo(mid11.x, mid11.y);
            targetCtx.lineTo(mid10.x, mid10.y);
            targetCtx.stroke();

            const winGlass = viewState.isNight ? '#fde047' : '#7dd3fc';
            for (let k = 0; k < 2; k++) {
                const f0 = 0.18 + k * 0.42;
                const f1 = f0 + 0.26;
                const wt0 = { x: t11.x + (t10.x - t11.x) * f0, y: t11.y + (t10.y - t11.y) * f0 + 2.5 };
                const wt1 = { x: t11.x + (t10.x - t11.x) * f1, y: t11.y + (t10.y - t11.y) * f1 + 2.5 };
                const wb0 = { x: mid11.x + (mid10.x - mid11.x) * f0, y: mid11.y + (mid10.y - mid11.y) * f0 - 2.0 };
                const wb1 = { x: mid11.x + (mid10.x - mid11.x) * f1, y: mid11.y + (mid10.y - mid11.y) * f1 - 2.0 };
                drawIsoQuad(targetCtx, wt0, wt1, wb1, wb0, winGlass, '#ffffff', 0.65);

                const dt0 = { x: mid11.x + (mid10.x - mid11.x) * f0, y: mid11.y + (mid10.y - mid11.y) * f0 + 2.5 };
                const dt1 = { x: mid11.x + (mid10.x - mid11.x) * f1, y: mid11.y + (mid10.y - mid11.y) * f1 + 2.5 };
                const db0 = { x: b11.x + (b10.x - b11.x) * f0, y: b11.y + (b10.y - b11.y) * f0 };
                const db1 = { x: b11.x + (b10.x - b11.x) * f1, y: b11.y + (b10.y - b11.y) * f1 };
                drawIsoQuad(targetCtx, dt0, dt1, db1, db0, '#e2e8f0', ink, 0.65);
            }

            return;
        }

        const dirMap = { 'Timur': 0, 'Selatan': 1, 'Barat': 2, 'Utara': 3 };
        const effectiveFacing = ((dirMap[u.facing_direction] ?? 1) + viewState.rotation) % 4;

        const sprite = resolveHouseSprite(u, gw, gh, effectiveFacing);
        if (sprite) {
            const lotBase = viewState.isNight ? '#2c3545' : '#cbd5e1';
            drawIsoQuad(targetCtx, p00, p10, p11, p01, lotBase, ink, 0.75);

            const diamondW = p10.x - p01.x;
            const artWidth = Math.max(1, (sprite.rightX ?? sprite.image.naturalWidth) - (sprite.leftX ?? 0));
            const scale = diamondW / artWidth;

            const drawW = sprite.image.naturalWidth * scale;
            const drawH = sprite.image.naturalHeight * scale;

            const cacheScale = targetCtx.getTransform().a || 1;
            const snapToTargetPx = (val) => Math.round(val * cacheScale) / cacheScale;
            const rDrawW = snapToTargetPx(drawW);
            const rDrawH = snapToTargetPx(drawH);

            targetCtx.save();
            targetCtx.imageSmoothingEnabled = true;
            targetCtx.imageSmoothingQuality = 'high';
            if (viewState.isNight) {
                targetCtx.filter = 'brightness(0.75) contrast(1.05)';
            }

            if (sprite.flipX) {
                const mirroredAnchorX = sprite.image.naturalWidth - sprite.anchorX;
                const dx = snapToTargetPx(p11.x - mirroredAnchorX * scale);
                const dy = snapToTargetPx(p11.y - sprite.anchorY * scale);
                targetCtx.translate(dx + rDrawW, dy);
                targetCtx.scale(-1, 1);
                targetCtx.drawImage(sprite.image, 0, 0, rDrawW, rDrawH);
            } else {
                const dx = snapToTargetPx(p11.x - sprite.anchorX * scale);
                const dy = snapToTargetPx(p11.y - sprite.anchorY * scale);
                targetCtx.drawImage(sprite.image, dx, dy, rDrawW, rDrawH);
            }

            targetCtx.restore();

            return;
        }

        const wallCreamLight = viewState.isNight ? '#d6ceb8' : '#fef7dc';
        const wallCreamShade = viewState.isNight ? '#a89f88' : '#ebd9a8';
        const wallCreamBack = viewState.isNight ? '#8c8470' : '#dec892';
        const orangePanelLight = viewState.isNight ? '#c97a16' : '#f59e0b';
        const orangePanelDark = viewState.isNight ? '#9a580c' : '#d97706';
        const roofSlateTop = viewState.isNight ? '#111827' : '#263142';
        const roofSlateShade = viewState.isNight ? '#090d16' : '#18202c';
        const windowGlass = viewState.isNight ? '#fde047' : '#60a5fa';
        const carportFloor = viewState.isNight ? '#334155' : '#cbd5e1';
        const lawnGreen = viewState.isNight ? '#214027' : '#6a994e';

        drawIsoQuad(targetCtx, p00, p10, p11, p01, carportFloor, ink, 0.8);

        if (effectiveFacing === 0) {
            const sh00 = rotatedToIso(gx + gw * 0.56, gy + 0.1);
            const sh10 = rotatedToIso(gx + gw * 0.72, gy + 0.1);
            const sh11 = rotatedToIso(gx + gw * 0.72, gy + gh - 0.1);
            const sh01 = rotatedToIso(gx + gw * 0.56, gy + gh - 0.1);
            drawIsoQuad(targetCtx, sh00, sh10, sh11, sh01, 'rgba(20, 24, 36, 0.22)');

            const l00 = rotatedToIso(gx + gw * 0.62, gy + gh * 0.52);
            const l10 = rotatedToIso(gx + gw * 0.94, gy + gh * 0.52);
            const l11 = rotatedToIso(gx + gw * 0.94, gy + gh * 0.92);
            const l01 = rotatedToIso(gx + gw * 0.62, gy + gh * 0.92);
            drawIsoQuad(targetCtx, l00, l10, l11, l01, lawnGreen, ink, 0.7);

            const dw0 = rotatedToIso(gx + gw * 0.58, gy + gh - 0.08);
            const dw1 = rotatedToIso(gx + gw - 0.08, gy + gh - 0.08);
            drawIsoQuad(targetCtx, { x: dw0.x, y: dw0.y - 3.2 }, { x: dw1.x, y: dw1.y - 3.2 }, dw1, dw0, wallCreamShade, ink, 0.65);
        } else if (effectiveFacing === 1) {
            const sh00 = rotatedToIso(gx + 0.1, gy + gh * 0.56);
            const sh10 = rotatedToIso(gx + gw - 0.1, gy + gh * 0.56);
            const sh11 = rotatedToIso(gx + gw - 0.1, gy + gh * 0.72);
            const sh01 = rotatedToIso(gx + 0.1, gy + gh * 0.72);
            drawIsoQuad(targetCtx, sh00, sh10, sh11, sh01, 'rgba(20, 24, 36, 0.22)');

            const l00 = rotatedToIso(gx + gw * 0.08, gy + gh * 0.62);
            const l10 = rotatedToIso(gx + gw * 0.46, gy + gh * 0.62);
            const l11 = rotatedToIso(gx + gw * 0.46, gy + gh * 0.94);
            const l01 = rotatedToIso(gx + gw * 0.08, gy + gh * 0.94);
            drawIsoQuad(targetCtx, l00, l10, l11, l01, lawnGreen, ink, 0.7);

            const dw0 = rotatedToIso(gx + 0.08, gy + gh * 0.58);
            const dw1 = rotatedToIso(gx + 0.08, gy + gh - 0.08);
            drawIsoQuad(targetCtx, { x: dw0.x, y: dw0.y - 3.2 }, { x: dw1.x, y: dw1.y - 3.2 }, dw1, dw0, wallCreamLight, ink, 0.65);
        } else if (effectiveFacing === 2) {
            const l00 = rotatedToIso(gx + 0.06, gy + 0.08);
            const l10 = rotatedToIso(gx + gw * 0.36, gy + 0.08);
            const l11 = rotatedToIso(gx + gw * 0.36, gy + gh * 0.48);
            const l01 = rotatedToIso(gx + 0.06, gy + gh * 0.48);
            drawIsoQuad(targetCtx, l00, l10, l11, l01, lawnGreen, ink, 0.65);
        } else {
            const l00 = rotatedToIso(gx + gw * 0.52, gy + 0.06);
            const l10 = rotatedToIso(gx + gw * 0.92, gy + 0.06);
            const l11 = rotatedToIso(gx + gw * 0.92, gy + gh * 0.36);
            const l01 = rotatedToIso(gx + gw * 0.52, gy + gh * 0.36);
            drawIsoQuad(targetCtx, l00, l10, l11, l01, lawnGreen, ink, 0.65);
        }

        let hx0 = gx + 0.08;
        let hy0 = gy + 0.08;
        let hx1 = gx + gw - 0.08;
        let hy1 = gy + gh - 0.08;

        if (effectiveFacing === 0) hx1 = gx + gw * 0.60;
        else if (effectiveFacing === 1) hy1 = gy + gh * 0.60;
        else if (effectiveFacing === 2) hx0 = gx + gw * 0.40;
        else if (effectiveFacing === 3) hy0 = gy + gh * 0.40;

        const wallH = 11.5;
        const roofPeakH = 18.0;

        const b00 = rotatedToIso(hx0, hy0);
        const b10 = rotatedToIso(hx1, hy0);
        const b11 = rotatedToIso(hx1, hy1);
        const b01 = rotatedToIso(hx0, hy1);

        const t00 = { x: b00.x, y: b00.y - wallH };
        const t10 = { x: b10.x, y: b10.y - wallH };
        const t11 = { x: b11.x, y: b11.y - wallH };
        const t01 = { x: b01.x, y: b01.y - wallH };

        const leftWallGrad = makeIsoGrad(targetCtx, t01, b11, wallCreamLight, wallCreamShade);
        const rightWallGrad = makeIsoGrad(targetCtx, t11, b10, wallCreamShade, wallCreamBack);
        drawIsoQuad(targetCtx, t01, t11, b11, b01, leftWallGrad, ink, 0.85);
        drawIsoQuad(targetCtx, t11, t10, b10, b11, rightWallGrad, ink, 0.85);

        if (effectiveFacing === 0) {
            const pTop1 = { x: (t11.x + b11.x) / 2, y: (t11.y + b11.y) / 2 - 1 };
            const pTop0 = { x: (t10.x + b10.x) / 2, y: (t10.y + b10.y) / 2 - 1 };
            const orangeGrad = makeIsoGrad(targetCtx, pTop1, b10, orangePanelLight, orangePanelDark);
            drawIsoQuad(targetCtx, pTop1, pTop0, b10, b11, orangeGrad, ink, 0.7);

            const midGroove0 = { x: (pTop1.x + b11.x) / 2, y: (pTop1.y + b11.y) / 2 };
            const midGroove1 = { x: (pTop0.x + b10.x) / 2, y: (pTop0.y + b10.y) / 2 };
            targetCtx.strokeStyle = '#ffffff';
            targetCtx.lineWidth = 0.65;
            targetCtx.beginPath();
            targetCtx.moveTo(midGroove0.x, midGroove0.y);
            targetCtx.lineTo(midGroove1.x, midGroove1.y);
            targetCtx.moveTo((pTop1.x + pTop0.x) / 2, (pTop1.y + pTop0.y) / 2);
            targetCtx.lineTo((b11.x + b10.x) / 2, (b11.y + b10.y) / 2);
            targetCtx.stroke();

            const jL = { x: t11.x + (t10.x - t11.x) * 0.18, y: t11.y + (t10.y - t11.y) * 0.18 + 3.5 };
            const jR = { x: t11.x + (t10.x - t11.x) * 0.27, y: t11.y + (t10.y - t11.y) * 0.27 + 3.5 };
            const jBL = { x: b11.x + (b10.x - b11.x) * 0.18, y: b11.y + (b10.y - b11.y) * 0.18 - 1.5 };
            const jBR = { x: b11.x + (b10.x - b11.x) * 0.27, y: b11.y + (b10.y - b11.y) * 0.27 - 1.5 };
            drawIsoQuad(targetCtx, jL, jR, jBR, jBL, windowGlass, '#ffffff', 0.6);

            const dL = { x: t11.x + (t10.x - t11.x) * 0.30, y: t11.y + (t10.y - t11.y) * 0.30 + 3.5 };
            const dR = { x: t11.x + (t10.x - t11.x) * 0.46, y: t11.y + (t10.y - t11.y) * 0.46 + 3.5 };
            const dBL = { x: b11.x + (b10.x - b11.x) * 0.30, y: b11.y + (b10.y - b11.y) * 0.30 };
            const dBR = { x: b11.x + (b10.x - b11.x) * 0.46, y: b11.y + (b10.y - b11.y) * 0.46 };
            drawIsoQuad(targetCtx, dL, dR, dBR, dBL, '#ffffff', ink, 0.65);

            drawIsoQuad(
                targetCtx,
                { x: jL.x - 0.5, y: jL.y - 1.2 },
                { x: dR.x + 0.5, y: dR.y - 1.2 },
                { x: dR.x + 2.2, y: dR.y + 0.2 },
                { x: jL.x + 1.2, y: jL.y + 0.2 },
                '#ffffff', ink, 0.55
            );

            const wL = { x: t11.x + (t10.x - t11.x) * 0.72, y: t11.y + (t10.y - t11.y) * 0.72 + 4 };
            const wR = { x: t11.x + (t10.x - t11.x) * 0.85, y: t11.y + (t10.y - t11.y) * 0.85 + 4 };
            const wBL = { x: b11.x + (b10.x - b11.x) * 0.72, y: b11.y + (b10.y - b11.y) * 0.72 - 1.8 };
            const wBR = { x: b11.x + (b10.x - b11.x) * 0.85, y: b11.y + (b10.y - b11.y) * 0.85 - 1.8 };
            drawIsoQuad(targetCtx, wL, wR, wBR, wBL, windowGlass, '#ffffff', 0.7);
            drawIsoQuad(
                targetCtx,
                { x: wL.x - 0.4, y: wL.y - 1.0 },
                { x: wR.x + 0.4, y: wR.y - 1.0 },
                { x: wR.x + 1.8, y: wR.y + 0.2 },
                { x: wL.x + 1.0, y: wL.y + 0.2 },
                '#ffffff', ink, 0.55
            );
        } else if (effectiveFacing === 1) {
            const pTop0 = { x: (t01.x + b01.x) / 2, y: (t01.y + b01.y) / 2 - 1 };
            const pTop1 = { x: (t11.x + b11.x) / 2, y: (t11.y + b11.y) / 2 - 1 };
            const orangeGrad = makeIsoGrad(targetCtx, pTop0, b11, orangePanelLight, orangePanelDark);
            drawIsoQuad(targetCtx, pTop0, pTop1, b11, b01, orangeGrad, ink, 0.7);

            const midGroove0 = { x: (pTop0.x + b01.x) / 2, y: (pTop0.y + b01.y) / 2 };
            const midGroove1 = { x: (pTop1.x + b11.x) / 2, y: (pTop1.y + b11.y) / 2 };
            targetCtx.strokeStyle = '#ffffff';
            targetCtx.lineWidth = 0.65;
            targetCtx.beginPath();
            targetCtx.moveTo(midGroove0.x, midGroove0.y);
            targetCtx.lineTo(midGroove1.x, midGroove1.y);
            targetCtx.moveTo((pTop0.x + pTop1.x) / 2, (pTop0.y + pTop1.y) / 2);
            targetCtx.lineTo((b01.x + b11.x) / 2, (b01.y + b11.y) / 2);
            targetCtx.stroke();

            const wL = { x: t01.x + (t11.x - t01.x) * 0.15, y: t01.y + (t11.y - t01.y) * 0.15 + 4 };
            const wR = { x: t01.x + (t11.x - t01.x) * 0.28, y: t01.y + (t11.y - t01.y) * 0.28 + 4 };
            const wBL = { x: b01.x + (b11.x - b01.x) * 0.15, y: b01.y + (b11.y - b01.y) * 0.15 - 1.8 };
            const wBR = { x: b01.x + (b11.x - b01.x) * 0.28, y: b01.y + (b11.y - b01.y) * 0.28 - 1.8 };
            drawIsoQuad(targetCtx, wL, wR, wBR, wBL, windowGlass, '#ffffff', 0.7);
            drawIsoQuad(
                targetCtx,
                { x: wL.x - 0.4, y: wL.y - 1.0 },
                { x: wR.x + 0.4, y: wR.y - 1.0 },
                { x: wR.x - 1.0, y: wR.y + 0.2 },
                { x: wL.x - 1.8, y: wL.y + 0.2 },
                '#ffffff', ink, 0.55
            );

            const dL = { x: t01.x + (t11.x - t01.x) * 0.54, y: t01.y + (t11.y - t01.y) * 0.54 + 3.5 };
            const dR = { x: t01.x + (t11.x - t01.x) * 0.70, y: t01.y + (t11.y - t01.y) * 0.70 + 3.5 };
            const dBL = { x: b01.x + (b11.x - b01.x) * 0.54, y: b01.y + (b11.y - b01.y) * 0.54 };
            const dBR = { x: b01.x + (b11.x - b01.x) * 0.70, y: b01.y + (b11.y - b01.y) * 0.70 };
            drawIsoQuad(targetCtx, dL, dR, dBR, dBL, '#ffffff', ink, 0.65);

            const jL = { x: t01.x + (t11.x - t01.x) * 0.73, y: t01.y + (t11.y - t01.y) * 0.73 + 3.5 };
            const jR = { x: t01.x + (t11.x - t01.x) * 0.82, y: t01.y + (t11.y - t01.y) * 0.82 + 3.5 };
            const jBL = { x: b01.x + (b11.x - b01.x) * 0.73, y: b01.y + (b11.y - b01.y) * 0.73 - 1.5 };
            const jBR = { x: b01.x + (b11.x - b01.x) * 0.82, y: b01.y + (b11.y - b01.y) * 0.82 - 1.5 };
            drawIsoQuad(targetCtx, jL, jR, jBR, jBL, windowGlass, '#ffffff', 0.6);

            drawIsoQuad(
                targetCtx,
                { x: dL.x - 0.5, y: dL.y - 1.2 },
                { x: jR.x + 0.5, y: jR.y - 1.2 },
                { x: jR.x - 1.2, y: jR.y + 0.2 },
                { x: dL.x - 2.2, y: dL.y + 0.2 },
                '#ffffff', ink, 0.55
            );
        } else if (effectiveFacing === 2) {
            const vL = { x: t11.x + (t10.x - t11.x) * 0.35, y: t11.y + (t10.y - t11.y) * 0.35 + 2.5 };
            const vR = { x: t11.x + (t10.x - t11.x) * 0.65, y: t11.y + (t10.y - t11.y) * 0.65 + 2.5 };
            const vBL = { x: vL.x, y: vL.y + 2.0 };
            const vBR = { x: vR.x, y: vR.y + 2.0 };
            drawIsoQuad(targetCtx, vL, vR, vBR, vBL, viewState.isNight ? '#fde047' : '#475569', '#ffffff', 0.5);
        } else {
            const vL = { x: t01.x + (t11.x - t01.x) * 0.35, y: t01.y + (t11.y - t01.y) * 0.35 + 2.5 };
            const vR = { x: t01.x + (t11.x - t01.x) * 0.65, y: t01.y + (t11.y - t01.y) * 0.65 + 2.5 };
            const vBL = { x: vL.x, y: vL.y + 2.0 };
            const vBR = { x: vR.x, y: vR.y + 2.0 };
            drawIsoQuad(targetCtx, vL, vR, vBR, vBL, viewState.isNight ? '#fde047' : '#475569', '#ffffff', 0.5);
        }

        if (effectiveFacing === 0 || effectiveFacing === 2) {
            const tMidF = { x: (t11.x + t10.x) / 2, y: (t11.y + t10.y) / 2 };
            const tMidB = { x: (t01.x + t00.x) / 2, y: (t01.y + t00.y) / 2 };

            const rLowF = { x: (t11.x + tMidF.x) / 2, y: (t11.y + tMidF.y) / 2 - (roofPeakH - wallH - 1.6) };
            const rLowB = { x: (t01.x + tMidB.x) / 2, y: (t01.y + tMidB.y) / 2 - (roofPeakH - wallH - 1.6) };

            targetCtx.beginPath();
            targetCtx.moveTo(t11.x, t11.y);
            targetCtx.lineTo(rLowF.x, rLowF.y);
            targetCtx.lineTo(tMidF.x, tMidF.y);
            targetCtx.closePath();
            targetCtx.fillStyle = wallCreamShade;
            targetCtx.fill();
            targetCtx.strokeStyle = '#ffffff';
            targetCtx.lineWidth = 0.85;
            targetCtx.stroke();

            const lowBack = makeIsoGrad(targetCtx, rLowB, tMidB, roofSlateShade, ink);
            const lowFront = makeIsoGrad(targetCtx, rLowF, t11, roofSlateTop, roofSlateShade);
            drawIsoQuad(targetCtx, rLowB, rLowF, tMidF, tMidB, lowBack, ink, 0.8);
            drawIsoQuad(targetCtx, rLowB, rLowF, t11, t01, lowFront, ink, 0.8);

            const rHighF = { x: (tMidF.x + t10.x) / 2, y: (tMidF.y + t10.y) / 2 - (roofPeakH - wallH) };
            const rHighB = { x: (tMidB.x + t00.x) / 2, y: (tMidB.y + t00.y) / 2 - (roofPeakH - wallH) };

            targetCtx.beginPath();
            targetCtx.moveTo(tMidF.x, tMidF.y);
            targetCtx.lineTo(rHighF.x, rHighF.y);
            targetCtx.lineTo(t10.x, t10.y);
            targetCtx.closePath();
            targetCtx.fillStyle = wallCreamShade;
            targetCtx.fill();
            targetCtx.strokeStyle = '#ffffff';
            targetCtx.lineWidth = 0.9;
            targetCtx.stroke();

            const highBack = makeIsoGrad(targetCtx, rHighB, t00, roofSlateShade, ink);
            const highFront = makeIsoGrad(targetCtx, rHighF, tMidF, roofSlateTop, roofSlateShade);
            drawIsoQuad(targetCtx, rHighB, rHighF, t10, t00, highBack, ink, 0.85);
            drawIsoQuad(targetCtx, rHighB, rHighF, tMidF, tMidB, highFront, ink, 0.85);
        } else {
            const tMidL = { x: (t01.x + t11.x) / 2, y: (t01.y + t11.y) / 2 };
            const tMidR = { x: (t00.x + t10.x) / 2, y: (t00.y + t10.y) / 2 };

            const rLowL = { x: (tMidL.x + t11.x) / 2, y: (tMidL.y + t11.y) / 2 - (roofPeakH - wallH - 1.6) };
            const rLowR = { x: (tMidR.x + t10.x) / 2, y: (tMidR.y + t10.y) / 2 - (roofPeakH - wallH - 1.6) };

            targetCtx.beginPath();
            targetCtx.moveTo(tMidL.x, tMidL.y);
            targetCtx.lineTo(rLowL.x, rLowL.y);
            targetCtx.lineTo(t11.x, t11.y);
            targetCtx.closePath();
            targetCtx.fillStyle = wallCreamLight;
            targetCtx.fill();
            targetCtx.strokeStyle = '#ffffff';
            targetCtx.lineWidth = 0.85;
            targetCtx.stroke();

            const lowBack = makeIsoGrad(targetCtx, rLowR, t10, roofSlateShade, ink);
            const lowFront = makeIsoGrad(targetCtx, rLowL, tMidL, roofSlateTop, roofSlateShade);
            drawIsoQuad(targetCtx, rLowL, rLowR, t10, t11, lowBack, ink, 0.8);
            drawIsoQuad(targetCtx, rLowL, rLowR, tMidR, tMidL, lowFront, ink, 0.8);

            const rHighL = { x: (t01.x + tMidL.x) / 2, y: (t01.y + tMidL.y) / 2 - (roofPeakH - wallH) };
            const rHighR = { x: (t00.x + tMidR.x) / 2, y: (t00.y + tMidR.y) / 2 - (roofPeakH - wallH) };

            targetCtx.beginPath();
            targetCtx.moveTo(t01.x, t01.y);
            targetCtx.lineTo(rHighL.x, rHighL.y);
            targetCtx.lineTo(tMidL.x, tMidL.y);
            targetCtx.closePath();
            targetCtx.fillStyle = wallCreamLight;
            targetCtx.fill();
            targetCtx.strokeStyle = '#ffffff';
            targetCtx.lineWidth = 0.9;
            targetCtx.stroke();

            const highBack = makeIsoGrad(targetCtx, rHighR, tMidR, roofSlateShade, ink);
            const highFront = makeIsoGrad(targetCtx, rHighL, t01, roofSlateTop, roofSlateShade);
            drawIsoQuad(targetCtx, rHighL, rHighR, tMidR, tMidL, highBack, ink, 0.85);
            drawIsoQuad(targetCtx, rHighL, rHighR, t00, t01, highFront, ink, 0.85);
        }

        if (viewState.layer === 'umkm' && (u.active_stores?.length || 0) > 0) {
            const uc = rotatedToIso(gx + gw * 0.72, gy + gh * 0.72);
            targetCtx.fillStyle = ink;
            targetCtx.fillRect(uc.x - 0.6, uc.y - 8, 1.2, 8);
            targetCtx.beginPath();
            targetCtx.arc(uc.x, uc.y - 8, 5, Math.PI, 0);
            targetCtx.fillStyle = '#e07a5f';
            targetCtx.fill();
            targetCtx.strokeStyle = '#f2cc8f';
            targetCtx.lineWidth = 1;
            targetCtx.stroke();
        }
    }

    function drawGuardPostAsset(targetCtx, pos) {
        const ink = '#141824';
        const b00 = rotatedToIso(pos.x, pos.y);
        const b10 = rotatedToIso(pos.x + 1, pos.y);
        const b11 = rotatedToIso(pos.x + 1, pos.y + 1);
        const b01 = rotatedToIso(pos.x, pos.y + 1);
        const h = 12;
        const t00 = { x: b00.x, y: b00.y - h };
        const t10 = { x: b10.x, y: b10.y - h };
        const t11 = { x: b11.x, y: b11.y - h };
        const t01 = { x: b01.x, y: b01.y - h };

        drawIsoQuad(targetCtx, t01, t11, b11, b01, '#264653', ink, 0.85);
        drawIsoQuad(targetCtx, t11, t10, b10, b11, '#1d3557', ink, 0.85);
        drawIsoQuad(targetCtx, t00, t10, t11, t01, '#e07a5f', ink, 0.85);
    }

    return {
        customSprites,
        registerCustomSprite,
        getUnitPalette,
        drawUnitLabel,
        drawIsometricHouseAsset,
        drawGuardPostAsset
    };
})();
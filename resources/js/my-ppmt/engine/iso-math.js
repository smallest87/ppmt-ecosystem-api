/**
 * ============================================================================
 * MILESTONE 2: PEMISAHAN MESIN MATEMATIKA ISOMETRIK (MyPpmtModules.IsoMath)
 * Target File: js/engine/iso-math.js
 * (Untuk Vite Laravel: resources/js/my-ppmt/engine/iso-math.js)
 * ============================================================================
 */
window.MyPpmtModules.IsoMath = (() => {
    const { GRID_COLS, GRID_ROWS, TILE_W, TILE_H } = window.MyPpmtModules.MapLayout;

    const snapPt = (p) => ({ x: Math.round(p.x * 2) / 2, y: Math.round(p.y * 2) / 2 });

    function makeIsoGrad(targetCtx, pStart, pEnd, colTop, colBottom) {
        const g = targetCtx.createLinearGradient(pStart.x, pStart.y, pEnd.x, pEnd.y);
        g.addColorStop(0, colTop);
        g.addColorStop(1, colBottom);
        return g;
    }

    function transformGrid(gx, gy, rotation) {
        switch (rotation) {
            case 1: return { x: GRID_ROWS - gy, y: gx };
            case 2: return { x: GRID_COLS - gx, y: GRID_ROWS - gy };
            case 3: return { x: gy, y: GRID_COLS - gx };
            default: return { x: gx, y: gy };
        }
    }

    function inverseTransformGrid(rx, ry, rotation) {
        switch (rotation) {
            case 1: return { x: ry, y: GRID_ROWS - rx };
            case 2: return { x: GRID_COLS - rx, y: GRID_ROWS - ry };
            case 3: return { x: GRID_COLS - ry, y: rx };
            default: return { x: rx, y: ry };
        }
    }

    function transformRect(gx, gy, gw, gh, rotation) {
        const p1 = transformGrid(gx, gy, rotation);
        const p2 = transformGrid(gx + gw, gy + gh, rotation);
        return {
            x: Math.min(p1.x, p2.x),
            y: Math.min(p1.y, p2.y),
            w: Math.abs(p2.x - p1.x),
            h: Math.abs(p2.y - p1.y)
        };
    }

    function rotatedToIso(rx, ry) {
        return {
            x: (rx - ry) * (TILE_W / 2),
            y: (rx + ry) * (TILE_H / 2)
        };
    }

    function gridToIso(gx, gy, rotation) {
        const pt = transformGrid(gx, gy, rotation);
        return rotatedToIso(pt.x, pt.y);
    }

    function screenToGrid(sx, sy, viewState, canvasEl) {
        const wx = (sx - canvasEl.width / 2 - viewState.camX) / viewState.zoom;
        const wy = (sy - canvasEl.height / 2 - viewState.camY) / viewState.zoom;
        const rx = (wx / (TILE_W / 2) + wy / (TILE_H / 2)) / 2;
        const ry = (wy / (TILE_H / 2) - wx / (TILE_W / 2)) / 2;
        return inverseTransformGrid(rx, ry, viewState.rotation);
    }

    function hitTestUnit(sx, sy, units, viewState, canvasEl) {
        const wx = (sx - canvasEl.width / 2 - viewState.camX) / viewState.zoom;
        const wy = (sy - canvasEl.height / 2 - viewState.camY) / viewState.zoom;
        const elevationOffsets = [0, 8, 16];

        for (const dy of elevationOffsets) {
            const adjWy = wy + dy;
            const rx = (wx / (TILE_W / 2) + adjWy / (TILE_H / 2)) / 2;
            const ry = (adjWy / (TILE_H / 2) - wx / (TILE_W / 2)) / 2;
            const g = inverseTransformGrid(rx, ry, viewState.rotation);

            for (let i = units.length - 1; i >= 0; i--) {
                const u = units[i];
                if (g.x >= u.coord_x && g.x <= u.coord_x + u.width &&
                    g.y >= u.coord_y && g.y <= u.coord_y + u.height) {
                    return u;
                }
            }
        }
        return null;
    }

    function drawIsoQuad(targetCtx, p0, p1, p2, p3, fill, stroke = null, lineWidth = 0.8) {
        targetCtx.beginPath();
        targetCtx.moveTo(p0.x, p0.y);
        targetCtx.lineTo(p1.x, p1.y);
        targetCtx.lineTo(p2.x, p2.y);
        targetCtx.lineTo(p3.x, p3.y);
        targetCtx.closePath();
        if (fill) {
            targetCtx.fillStyle = fill;
            targetCtx.fill();
        }
        if (stroke) {
            targetCtx.strokeStyle = stroke;
            targetCtx.lineWidth = lineWidth;
            targetCtx.stroke();
        }
    }

    // Pengujian Akurasi Proyeksi & Rotasi 4 Arah Milestone 2 (Self-Test Suite)
    function runSelfTest(units) {
        const errors = [];
        const mockCanvas = { width: 1000, height: 800 };
        const samplePoints = [
            { x: 16.5, y: 4.5 },
            { x: 17.5, y: 15.0 },
            { x: 25.0, y: 37.5 },
            { x: 8.5, y: 55.5 }
        ];

        // Test 1: Round-trip gridToIso -> screenToGrid di seluruh 4 orientasi (0, 1, 2, 3)
        for (let rot = 0; rot < 4; rot++) {
            const viewState = { rotation: rot, camX: 40, camY: -20, zoom: 1.5 };
            for (const pt of samplePoints) {
                const iso = gridToIso(pt.x, pt.y, rot);
                const sx = mockCanvas.width / 2 + viewState.camX + iso.x * viewState.zoom;
                const sy = mockCanvas.height / 2 + viewState.camY + iso.y * viewState.zoom;
                const back = screenToGrid(sx, sy, viewState, mockCanvas);
                if (Math.abs(back.x - pt.x) > 0.01 || Math.abs(back.y - pt.y) > 0.01) {
                    errors.push(`Round-trip gagal pada rotasi ${rot} untuk titik (${pt.x}, ${pt.y}).`);
                }
            }
        }

        // Test 2: Akurasi hitTestUnit pada kavling aktual di berbagai orientasi
        const targetUnit = units.find(u => u.unit_code === 'FASUM-IBADAH');
        if (targetUnit) {
            for (let rot = 0; rot < 4; rot++) {
                const viewState = { rotation: rot, camX: 0, camY: 0, zoom: 1.0 };
                const iso = gridToIso(targetUnit.coord_x + 2.5, targetUnit.coord_y + 1.5, rot);
                const hit = hitTestUnit(mockCanvas.width / 2 + iso.x, mockCanvas.height / 2 + iso.y, units, viewState, mockCanvas);
                if (!hit || hit.unit_code !== 'FASUM-IBADAH') {
                    errors.push(`Hit-test gagal mendeteksi FASUM-IBADAH pada rotasi ${rot}.`);
                }
            }
        }

        return {
            passed: errors.length === 0,
            totalChecks: 2,
            errors
        };
    }

    return {
        snapPt,
        makeIsoGrad,
        transformGrid,
        inverseTransformGrid,
        transformRect,
        rotatedToIso,
        gridToIso,
        screenToGrid,
        hitTestUnit,
        drawIsoQuad,
        runSelfTest
    };
})();
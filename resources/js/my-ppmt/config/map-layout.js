/**
 * ============================================================================
 * MILESTONE 1: PEMISAHAN MODUL DATA SPASIAL & KAVLING (MyPpmtModules.MapLayout)
 * Target File Vite Laravel: resources/js/my-ppmt/config/map-layout.js
 * ============================================================================
 */
window.MyPpmtModules = window.MyPpmtModules || {};

window.MyPpmtModules.MapLayout = (() => {
    const GRID_COLS = 36;
    const GRID_ROWS = 70;
    const TILE_W = 24;
    const TILE_H = 12;

    const MATRIX_BACKGROUND_BLOCKS = [
        { x: 16, y: 0, w: 6, h: 3, type: 'road', label: 'Jalan Depan Ruko R1 & R2' },
        { x: 19, y: 3, w: 3, h: 42, type: 'road', label: 'Jalan Utama ROW 6' },
        { x: 22, y: 33, w: 7, h: 3, type: 'road', label: 'Jalan Blok B' },
        { x: 1, y: 42, w: 18, h: 3, type: 'road', label: 'Jalan ROW 6 Tengah Utara 1' },
        { x: 22, y: 42, w: 14, h: 3, type: 'road', label: 'Jalan ROW 6 Tengah Utara 2' },
        { x: 1, y: 45, w: 3, h: 6, type: 'road', label: 'Jalan ROW 6 Barat' },
        { x: 33, y: 45, w: 3, h: 6, type: 'road', label: 'Jalan ROW 6 Timur' },
        { x: 1, y: 51, w: 18, h: 3, type: 'road', label: 'Jalan ROW 6 Tengah Selatan 1' },
        { x: 22, y: 51, w: 14, h: 3, type: 'road', label: 'Jalan ROW 6 Tengah Selatan 2' },
        { x: 3, y: 54, w: 3, h: 7, type: 'road', label: 'Jalan ROW 7 Barat Daya' },
        { x: 19, y: 51, w: 3, h: 19, type: 'road', label: 'Jalan ROW 6 Selatan' },
        { x: 13, y: 60, w: 6, h: 3, type: 'road', label: 'Jalan Blok D 1' },
        { x: 22, y: 60, w: 8, h: 3, type: 'road', label: 'Jalan Blok D 2' },
        { x: 16, y: 63, w: 3, h: 7, type: 'road', label: 'Jalan ROW 6 Blok D-14/D-15' },
        { x: 22, y: 66, w: 3, h: 4, type: 'road', label: 'Jalan ROW 6 Blok D-16/D-17' },
        { x: 18, y: 3, w: 1, h: 1, type: 'guard', label: 'Pos Satpam Gerbang Utama' },
        { x: 18, y: 4, w: 1, h: 3, type: 'park', label: 'Taman Samping Pos Satpam' },
        { x: 15, y: 0, w: 1, h: 39, type: 'park', label: 'Jalur Hijau Barat Utara' },
        { x: 0, y: 38, w: 15, h: 1, type: 'park', label: 'Jalur Hijau Utara Blok A' },
        { x: 0, y: 39, w: 1, h: 15, type: 'park', label: 'Jalur Hijau Barat' },
        { x: 0, y: 54, w: 3, h: 1, type: 'park', label: 'Taman Utara D-1' },
        { x: 30, y: 54, w: 6, h: 1, type: 'park', label: 'Jalur Hijau Tenggara' },
        { x: 4, y: 48, w: 1, h: 3, type: 'park', label: 'Taman Ujung Barat Pulau' },
        { x: 32, y: 45, w: 1, h: 3, type: 'park', label: 'Taman Ujung Timur Pulau' },
        { x: 6, y: 57, w: 9, h: 4, type: 'park', label: 'Fasum Taman Utama' },
        { x: 13, y: 63, w: 3, h: 3, type: 'park', label: 'Fasum Taman Selatan' },
        { x: 19, y: 69, w: 3, h: 1, type: 'park', label: 'Taman Ujung Selatan' }
    ];

    const OUTER_AUTUMN_GARDENS = [
        { x: 0, y: 0, w: 15, h: 38, label: 'Kebun Barat Laut' },
        { x: 22, y: 0, w: 14, h: 27, label: 'Kebun Timur Laut' },
        { x: 29, y: 27, w: 7, h: 15, label: 'Kebun Timur Tengah' },
        { x: 30, y: 54, w: 6, h: 16, label: 'Kebun Tenggara' },
        { x: 0, y: 61, w: 13, h: 10, label: 'Kebun Barat Daya' }
    ];

    const TREE_COORDS = [
        [15.5, 4.5], [15.5, 11.5], [15.5, 19.5], [15.5, 27.5], [15.5, 35.5],
        [3.5, 38.5], [9.5, 38.5], [0.5, 43.5], [0.5, 50.5],
        [7.5, 58.5], [10.5, 58.5], [13.5, 58.5],
        [14.5, 64.5], [32.5, 54.5], [4.5, 49.5], [32.5, 46.5]
    ];

    function generateAutumnFoliage() {
        const items = [];
        let seed = 1337;
        const nextRand = () => {
            seed = (seed * 16807) % 2147483647;
            return (seed - 1) / 2147483646;
        };

        for (const g of OUTER_AUTUMN_GARDENS) {
            for (let gx = g.x + 1.1; gx <= g.x + g.w - 1.1; gx += 2.2) {
                for (let gy = g.y + 1.1; gy <= g.y + g.h - 1.1; gy += 2.2) {
                    const r = nextRand();
                    const ox = gx + (nextRand() - 0.5) * 0.9;
                    const oy = gy + (nextRand() - 0.5) * 0.9;
                    if (r < 0.28) {
                        items.push({ kind: 'autumnTree', x: ox, y: oy, variant: Math.floor(nextRand() * 3) });
                    } else if (r < 0.78) {
                        items.push({ kind: 'autumnShrub', x: ox, y: oy, variant: Math.floor(nextRand() * 3) });
                    }
                }
            }
        }
        return items;
    }

    function buildSeededUnits(hydrationData) {
        const units = [];
        let idCounter = 1;
        const dirLabel = { north: 'Utara', south: 'Selatan', east: 'Timur', west: 'Barat' };
        const overrides = hydrationData?.unit_overrides || {};

        const addUnit = (code, block, zone, type, x, y, w, h, orientation) => {
            const ov = overrides[code] || null;
            const isOccupiedDefault = idCounter % 4 !== 0;

            const occupancy_status = ov
                ? ov.occupancy_status
                : (type === 'public_facility' ? 'occupied' : (isOccupiedDefault ? 'occupied' : 'unclaimed'));

            const dues_status = ov
                ? ov.dues_status
                : ((occupancy_status === 'occupied' || occupancy_status === 'rented') ? 'paid' : 'exempt');

            let members = ov ? JSON.parse(JSON.stringify(ov.members)) : [];
            if (!ov && occupancy_status === 'occupied' && type !== 'public_facility') {
                members = [{
                    membership_id: 200 + idCounter,
                    user_id: 50 + idCounter,
                    name: `Keluarga ${code}`,
                    sub_role_label: 'Penghuni Tetap',
                    is_owner: true,
                    is_occupant: true,
                    is_approved: true,
                    is_verified_shm: true,
                    phone: `08130000${String(idCounter).padStart(4, '0')}`
                }];
            }

            const active_stores = ov ? JSON.parse(JSON.stringify(ov.active_stores)) : [];

            units.push({
                id: idCounter++,
                unit_code: code,
                block,
                zone_code: zone,
                unit_type: type,
                coord_x: x,
                coord_y: y,
                width: w,
                height: h,
                facing_direction: dirLabel[orientation] || 'Selatan',
                occupancy_status,
                dues_status,
                members,
                active_stores,
                missionDone: false
            });
        };

        // 1. RUKO DEPAN (R1 - R2)
        addUnit('R1', 'R', 'ZONA-1', 'commercial', 16, 3, 1, 4, 'south');
        addUnit('R2', 'R', 'ZONA-1', 'commercial', 17, 3, 1, 4, 'south');

        // 2. BLOK A VERTIKAL (A-1 s/d A-16)
        const blockAVertical = [
            'A-1', 'A-2', 'A-3', 'A-4', 'A-5', 'A-6', 'A-7', 'A-8',
            'A-9', 'A-10', 'A-11', 'A-12A', 'A-12B', 'A-14', 'A-15', 'A-16'
        ];
        blockAVertical.forEach((code, idx) => {
            addUnit(code, 'A', 'ZONA-1', 'residential', 16, 7 + (idx * 2), 3, 2, 'east');
        });

        // 3. BLOK A HORIZONTAL (A-17 s/d A-25)
        const blockAHorizontal = [
            ['A-17', 17], ['A-18', 15], ['A-19', 13], ['A-20', 11],
            ['A-21', 9], ['A-22', 7], ['A-23', 5], ['A-24', 3], ['A-25', 1]
        ];
        blockAHorizontal.forEach(([code, x]) => {
            addUnit(code, 'A', 'ZONA-1', 'residential', x, 39, 2, 3, 'south');
        });

        // 4. BLOK B KLASTER TIMUR ATAS (B-1..B-4)
        addUnit('B-1', 'B', 'ZONA-2', 'residential', 22, 27, 7, 3, 'west');
        addUnit('B-2', 'B', 'ZONA-2', 'residential', 22, 30, 2, 3, 'south');
        addUnit('B-3', 'B', 'ZONA-2', 'residential', 24, 30, 2, 3, 'south');
        addUnit('B-4', 'B', 'ZONA-2', 'residential', 26, 30, 3, 3, 'south');

        // 5. BLOK B KLASTER TIMUR TENGAH (B-7..B-5 & B-8..B-10)
        addUnit('B-7', 'B', 'ZONA-2', 'residential', 22, 36, 2, 3, 'north');
        addUnit('B-6', 'B', 'ZONA-2', 'residential', 24, 36, 2, 3, 'north');
        addUnit('B-5', 'B', 'ZONA-2', 'residential', 26, 36, 3, 3, 'north');
        addUnit('B-8', 'B', 'ZONA-2', 'residential', 22, 39, 2, 3, 'south');
        addUnit('B-9', 'B', 'ZONA-2', 'residential', 24, 39, 2, 3, 'south');
        addUnit('B-10', 'B', 'ZONA-2', 'residential', 26, 39, 3, 3, 'south');

        // 6. PULAU TENGAH: Blok B (B-24..B-11) & Blok C (C-1..C-14)
        const islandColumns = [
            ['B-24', 4,  'C-1',   5],
            ['B-23', 6,  'C-2',   7],
            ['B-22', 8,  'C-3',   9],
            ['B-21', 10, 'C-4',   11],
            ['B-20', 12, 'C-5',   13],
            ['B-19', 14, 'C-6',   15],
            ['B-18', 16, 'C-7',   17],
            ['B-17', 18, 'C-8',   19],
            ['B-16', 20, 'C-9',   21],
            ['B-15', 22, 'C-10',  23],
            ['B-14', 24, 'C-11',  25],
            ['B-12B', 26, 'C-12A', 27],
            ['B-12A', 28, 'C-12B', 29],
            ['B-11', 30, 'C-14',  31]
        ];
        islandColumns.forEach(([bCode, bx, cCode, cx]) => {
            addUnit(bCode, 'B', 'ZONA-2', 'residential', bx, 45, 2, 3, 'north');
            addUnit(cCode, 'C', 'ZONA-3', 'residential', cx, 48, 2, 3, 'south');
        });

        // 7. BLOK C KLASTER TENGGARA (C-18..C-15) & BLOK D (D-6..D-9)
        const southeastPairs = [
            ['C-18', 'D-6', 22],
            ['C-17', 'D-7', 24],
            ['C-16', 'D-8', 26],
            ['C-15', 'D-9', 28]
        ];
        southeastPairs.forEach(([cCode, dCode, x]) => {
            addUnit(cCode, 'C', 'ZONA-3', 'residential', x, 54, 2, 3, 'north');
            addUnit(dCode, 'D', 'ZONA-4', 'residential', x, 57, 2, 3, 'south');
        });

        // 8. BLOK C KLASTER BARAT DAYA (C-22..C-19) & BLOK D (D-4, D-5)
        addUnit('C-22', 'C', 'ZONA-3', 'residential', 11, 54, 2, 3, 'north');
        addUnit('C-21', 'C', 'ZONA-3', 'residential', 13, 54, 2, 3, 'north');
        addUnit('C-20', 'C', 'ZONA-3', 'residential', 15, 54, 2, 3, 'north');
        addUnit('C-19', 'C', 'ZONA-3', 'residential', 17, 54, 2, 3, 'north');

        addUnit('D-4', 'D', 'ZONA-4', 'residential', 15, 57, 2, 3, 'south');
        addUnit('D-5', 'D', 'ZONA-4', 'residential', 17, 57, 2, 3, 'south');

        // 9. BLOK D UJUNG BARAT DAYA (D-1 s/d D-3)
        addUnit('D-1', 'D', 'ZONA-4', 'residential', 0, 55, 3, 2, 'east');
        addUnit('D-2', 'D', 'ZONA-4', 'residential', 0, 57, 3, 2, 'east');
        addUnit('D-3', 'D', 'ZONA-4', 'residential', 0, 59, 3, 2, 'east');

        // 10. BLOK D DERET SELATAN (D-12B s/d D-10)
        addUnit('D-12B', 'D', 'ZONA-4', 'residential', 22, 63, 2, 3, 'north');
        addUnit('D-12A', 'D', 'ZONA-4', 'residential', 24, 63, 2, 3, 'north');
        addUnit('D-11', 'D', 'ZONA-4', 'residential', 26, 63, 2, 3, 'north');
        addUnit('D-10', 'D', 'ZONA-4', 'residential', 28, 63, 2, 3, 'north');

        // 11. BLOK D POJOK BAWAH BARAT (D-14, D-15) & POJOK BAWAH TIMUR (D-16, D-17)
        addUnit('D-14', 'D', 'ZONA-4', 'residential', 13, 66, 3, 2, 'east');
        addUnit('D-15', 'D', 'ZONA-4', 'residential', 13, 68, 3, 2, 'east');

        addUnit('D-16', 'D', 'ZONA-4', 'residential', 25, 66, 5, 2, 'west');
        addUnit('D-17', 'D', 'ZONA-4', 'residential', 25, 68, 5, 2, 'west');

        // 12. FASILITAS UMUM TEMPAT IBADAH (FASUM-IBADAH)
        addUnit('FASUM-IBADAH', 'FASUM', 'ZONA-4', 'public_facility', 6, 54, 5, 3, 'north');

        return units;
    }

    // Pengujian Integritas Data Milestone 1 (Self-Test Suite)
    function runSelfTest(units) {
        const errors = [];

        // Test 1: Total unit wajib 91 kavling
        if (units.length !== 91) {
            errors.push(`Total unit ${units.length}, seharusnya 91.`);
        }

        // Test 2: Tidak boleh ada nomor 13 murni, wajib 12A & 12B
        const hasForbidden13 = units.some(u => /-13$/.test(u.unit_code));
        if (hasForbidden13) {
            errors.push('Ditemukan kode kavling berakhiran -13.');
        }

        // Test 3: Tidak ada bounding-box kavling yang saling bertabrakan (overlap)
        for (let i = 0; i < units.length; i++) {
            for (let j = i + 1; j < units.length; j++) {
                const a = units[i];
                const b = units[j];
                const overlapX = a.coord_x < b.coord_x + b.width && a.coord_x + a.width > b.coord_x;
                const overlapY = a.coord_y < b.coord_y + b.height && a.coord_y + a.height > b.coord_y;
                if (overlapX && overlapY) {
                    errors.push(`Tabrakan koordinat antara ${a.unit_code} dan ${b.unit_code}.`);
                }
            }
        }

        // Test 4: Seluruh kavling berada di dalam kanvas 36 x 70
        const outOfBounds = units.filter(
            u => u.coord_x < 0 || u.coord_y < 0 || u.coord_x + u.width > GRID_COLS || u.coord_y + u.height > GRID_ROWS
        );
        if (outOfBounds.length > 0) {
            errors.push(`${outOfBounds.length} unit keluar dari batas matriks 36x70.`);
        }

        return {
            passed: errors.length === 0,
            totalChecks: 4,
            errors
        };
    }

    return {
        GRID_COLS,
        GRID_ROWS,
        TILE_W,
        TILE_H,
        MATRIX_BACKGROUND_BLOCKS,
        OUTER_AUTUMN_GARDENS,
        TREE_COORDS,
        generateAutumnFoliage,
        buildSeededUnits,
        runSelfTest
    };
})();
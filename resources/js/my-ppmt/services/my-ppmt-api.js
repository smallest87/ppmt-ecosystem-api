/**
 * ============================================================================
 * MILESTONE 4A: JEMBATAN API LARAVEL REAL-TIME (MyPpmtModules.MyPpmtApi)
 * Target File: resources/js/my-ppmt/services/my-ppmt-api.js
 * ============================================================================
 */
window.MyPpmtModules.MyPpmtApi = (() => {
    const { buildSeededUnits } = window.MyPpmtModules.MapLayout;

    const ORIENTATION_LABEL = {
        north: 'Utara',
        south: 'Selatan',
        east: 'Timur',
        west: 'Barat',
    };

    function parseHydrationPayload(scriptElementId = 'laravel-my-ppmt-payload') {
        const el = document.getElementById(scriptElementId);
        if (!el) {
            return {
                use_live_api: true,
                endpoints: {
                    siteplan: '/api/v1/siteplan/units',
                    approve: '/api/v1/verifications/approve',
                    dues: '/api/v1/treasury/dues',
                },
                viewer_context: {},
                unit_overrides: {},
            };
        }
        return JSON.parse(el.textContent);
    }

    function buildLayoutLookup(hydrationData = {}) {
        const seeded = buildSeededUnits(hydrationData);
        const byCode = new Map();
        for (const item of seeded) {
            const key = String(item.unit_code || item.code || '').trim().toUpperCase();
            if (key) byCode.set(key, item);
        }
        return byCode;
    }

    function normalizeSiteplanUnit(rawUnit, index = 0, layoutByCode = new Map()) {
        const rawCode = String(rawUnit.code || rawUnit.unit_code || `UNIT-${index + 1}`).trim();
        const layoutRef = layoutByCode.get(rawCode.toUpperCase()) || null;

        const coords = rawUnit.map_coordinates || {};
        const hasApiCoords =
            typeof coords.x === 'number' &&
            typeof coords.y === 'number' &&
            typeof coords.w === 'number' &&
            typeof coords.h === 'number';

        const coordX = hasApiCoords
            ? coords.x
            : (typeof rawUnit.coord_x === 'number' ? rawUnit.coord_x : (layoutRef?.coord_x ?? 0));
        const coordY = hasApiCoords
            ? coords.y
            : (typeof rawUnit.coord_y === 'number' ? rawUnit.coord_y : (layoutRef?.coord_y ?? 0));
        const width = hasApiCoords
            ? coords.w
            : (typeof rawUnit.width === 'number' ? rawUnit.width : (layoutRef?.width ?? 2));
        const height = hasApiCoords
            ? coords.h
            : (typeof rawUnit.height === 'number' ? rawUnit.height : (layoutRef?.height ?? 3));

        const orientationKey = String(coords.orientation || '').toLowerCase();
        const facingDirection =
            rawUnit.facing_direction ||
            ORIENTATION_LABEL[orientationKey] ||
            layoutRef?.facing_direction ||
            'Selatan';

        const block = rawUnit.block || layoutRef?.block || '';
        const zone = rawUnit.zone || rawUnit.zone_code || layoutRef?.zone_code || 'ZONA-1';
        const unitType = rawUnit.unit_type || layoutRef?.unit_type || 'rumah';

        const rawCarportSide = rawUnit.carport_side ?? layoutRef?.carport_side ?? null;
        const carportSide = (rawCarportSide === 'left' || rawCarportSide === 'right')
            ? rawCarportSide
            : null;

        const members = Array.isArray(rawUnit.members) ? rawUnit.members : [];
        const activeStores = Array.isArray(rawUnit.active_stores) ? rawUnit.active_stores : [];

        return {
            id: rawUnit.id ?? layoutRef?.id ?? index + 1,
            code: rawCode,
            unit_code: rawCode,
            block,
            number: rawUnit.number ?? null,
            zone,
            zone_code: zone,
            unit_type: unitType,
            carport_side: carportSide,
            coord_x: coordX,
            coord_y: coordY,
            width,
            height,
            map_coordinates: hasApiCoords ? coords : (layoutRef ? {
                x: layoutRef.coord_x,
                y: layoutRef.coord_y,
                w: layoutRef.width,
                h: layoutRef.height,
            } : null),
            facing_direction: facingDirection,
            occupancy_status: rawUnit.occupancy_status || 'unclaimed',
            dues_status: rawUnit.dues_status ?? null,
            is_my_unit: Boolean(rawUnit.is_my_unit),
            is_interactive: rawUnit.is_interactive !== false,
            can_manage_unit: Boolean(rawUnit.can_manage_unit),
            has_owner_claim: Boolean(rawUnit.has_owner_claim),
            has_verified_shm: Boolean(rawUnit.has_verified_shm),
            has_unverified_claim: Boolean(rawUnit.has_unverified_claim),
            owners: Array.isArray(rawUnit.owners) ? rawUnit.owners : [],
            pending_count: Number(rawUnit.pending_count ?? 0),
            active_stores_count: Number(rawUnit.active_stores_count ?? activeStores.length),
            active_stores: activeStores,
            members,
            missionDone: false,
        };
    }

    function createClient(hydrationData = {}) {
        const endpoints = hydrationData?.endpoints || {
            siteplan: '/api/v1/siteplan/units',
            approve: '/api/v1/verifications/approve',
            dues: '/api/v1/treasury/dues',
        };

        const getToken = () => localStorage.getItem('ppmt_token') || '';

        const authHeaders = () => ({
            'Authorization': 'Bearer ' + getToken(),
            'Content-Type': 'application/json',
            'Accept': 'application/json',
        });

        async function loadWorldPayload() {
            const layoutByCode = buildLayoutLookup(hydrationData);

            if (hydrationData?.use_live_api) {
                const token = getToken();
                if (!token) {
                    throw new Error('Sesi token PPMT tidak ditemukan. Silakan masuk ulang.');
                }

                const res = await fetch(endpoints.siteplan, { headers: authHeaders() });
                const json = await res.json().catch(() => ({}));

                if (!res.ok) {
                    throw new Error(json.message || 'Gagal memuat data siteplan real-time.');
                }

                const rawList = Array.isArray(json.data) ? json.data : [];
                return {
                    units: rawList.map((u, idx) => normalizeSiteplanUnit(u, idx, layoutByCode)),
                    viewerContext: json.viewer_context || hydrationData.viewer_context || {},
                    stats: json.stats || null,
                    isLive: true,
                };
            }

            const seeded = buildSeededUnits(hydrationData).map((u, idx) =>
                normalizeSiteplanUnit(u, idx, layoutByCode)
            );
            return {
                units: seeded,
                viewerContext: hydrationData.viewer_context || {},
                stats: null,
                isLive: false,
            };
        }

        async function loadWorldUnits() {
            const payload = await loadWorldPayload();
            return payload.units;
        }

        async function approveUnitClaim(unit) {
            if (!unit.can_manage_unit) {
                throw new Error('Anda tidak memiliki wewenang mengesahkan klaim pada kavling ini.');
            }

            const pendingMember = (unit.members || []).find((m) => !m.is_approved);

            if (hydrationData?.use_live_api) {
                if (!pendingMember) {
                    throw new Error('Tidak ada anggota berstatus menunggu verifikasi pada kavling ini.');
                }

                const res = await fetch(endpoints.approve, {
                    method: 'POST',
                    headers: authHeaders(),
                    body: JSON.stringify({
                        target_user_id: pendingMember.user_id,
                        unit_code: unit.code || unit.unit_code,
                    }),
                });
                const json = await res.json().catch(() => ({}));
                if (!res.ok) {
                    throw new Error(json.message || 'Gagal mengesahkan klaim ke server.');
                }

                return {
                    ok: true,
                    pointsAwarded: 150,
                    message: json.message || `✓ Klaim Kavling ${unit.code || unit.unit_code} disahkan! (+150 Poin)`,
                    shouldReload: true,
                };
            }

            unit.occupancy_status = 'occupied';
            unit.pending_count = 0;
            (unit.members || []).forEach((m) => {
                m.is_approved = true;
                m.is_verified_shm = Boolean(m.is_owner);
            });

            return {
                ok: true,
                pointsAwarded: 150,
                message: `✓ Klaim Kavling ${unit.code || unit.unit_code} disahkan! (+150 Poin)`,
                shouldReload: false,
            };
        }

        async function markDuesPaid(unit, viewerContext = {}) {
            const canManageTreasury = Boolean(viewerContext?.can_manage_treasury);
            if (!canManageTreasury && hydrationData?.use_live_api) {
                throw new Error('Hanya Bendahara Paguyuban yang berwenang mencatat pelunasan iuran.');
            }

            const periodMonth =
                viewerContext?.current_month ||
                hydrationData?.viewer_context?.current_month ||
                new Date().toISOString().slice(0, 7);

            if (hydrationData?.use_live_api) {
                const res = await fetch(endpoints.dues, {
                    method: 'POST',
                    headers: authHeaders(),
                    body: JSON.stringify({
                        unit_id: unit.id,
                        period_month: periodMonth,
                        action: 'paid',
                        payment_method: 'cash',
                    }),
                });
                const json = await res.json().catch(() => ({}));
                if (!res.ok) {
                    throw new Error(json.message || 'Gagal mencatat pelunasan iuran.');
                }

                return {
                    ok: true,
                    pointsAwarded: 100,
                    message: json.message || `✓ Iuran Kavling ${unit.code || unit.unit_code} tercatat lunas! (+100 Poin)`,
                    shouldReload: true,
                };
            }

            unit.dues_status = 'paid';
            return {
                ok: true,
                pointsAwarded: 100,
                message: `✓ Iuran Kavling ${unit.code || unit.unit_code} tercatat lunas! (+100 Poin)`,
                shouldReload: false,
            };
        }

        return {
            loadWorldPayload,
            loadWorldUnits,
            approveUnitClaim,
            markDuesPaid,
        };
    }

    async function runSelfTest(hydrationData) {
        const errors = [];
        const testPayload = {
            ...hydrationData,
            use_live_api: false,
            viewer_context: {
                ...(hydrationData?.viewer_context || {}),
                can_manage_treasury: true,
                current_month: '2026-11',
            },
        };
        const mockClient = createClient(testPayload);
        const { units, viewerContext } = await mockClient.loadWorldPayload();

        if (!Array.isArray(units) || units.length !== 91) {
            errors.push(`Jumlah unit hasil normalisasi ${units?.length ?? 0}, diharapkan 91.`);
        }

        const sampleUnit = units.find((u) => u.code === 'A-5') || units.find((u) => u.occupancy_status === 'pending');
        if (!sampleUnit) {
            errors.push('Unit uji untuk pengesahan klaim tidak ditemukan.');
        } else {
            const unitForApprove = { ...sampleUnit, can_manage_unit: true };
            const resApprove = await mockClient.approveUnitClaim(unitForApprove);
            if (!resApprove.ok || unitForApprove.occupancy_status !== 'occupied') {
                errors.push('Simulasi pengesahan klaim gagal memperbarui status unit.');
            }
        }

        const duesTarget = units.find((u) => u.unit_type !== 'fasum' && u.dues_status !== 'paid');
        if (duesTarget) {
            const resDues = await mockClient.markDuesPaid(duesTarget, viewerContext);
            if (!resDues.ok || duesTarget.dues_status !== 'paid') {
                errors.push('Simulasi pencatatan iuran lunas gagal memperbarui dues_status.');
            }
        }

        return {
            passed: errors.length === 0,
            totalChecks: 2,
            errors,
        };
    }

    return {
        parseHydrationPayload,
        normalizeSiteplanUnit,
        createClient,
        runSelfTest,
    };
})();
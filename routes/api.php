<?php

use App\Http\Controllers\Api\V1\AuthClaimController;
use App\Http\Controllers\Api\V1\PublicUnitController;
use App\Http\Controllers\Api\V1\VerificationController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\UnitInviteController;
use App\Http\Controllers\Api\V1\UmkmStoreController;
use App\Http\Controllers\Api\V1\GovernanceController;
use App\Http\Controllers\Api\V1\AnnouncementController;
use App\Http\Controllers\Api\V1\MeetingMinuteController;
use App\Http\Controllers\Api\V1\TreasuryController;
use App\Http\Controllers\Api\V1\AgendaController;
use App\Http\Controllers\Api\V1\AspirationController;
use App\Http\Controllers\Api\V1\NewsController;
use App\Http\Controllers\Api\V1\WhatsappContactReconciliationController;
use App\Http\Controllers\Api\V1\SiteplanController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // 1. Endpoint Publik (Warga Umum & Form Registrasi)
    Route::get('/public/units', [PublicUnitController::class, 'index']);
    Route::post('/auth/register-claim', [AuthClaimController::class, 'registerClaim']);
    Route::post('/auth/login', [AuthClaimController::class, 'login']);

    // 2. Endpoint Terautentikasi Dasar (Termasuk akun Pending untuk cek status profil)
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', function (Request $request) {
            $user = $request->user()->load(['profile', 'unitMemberships.unit', 'governanceRoles']);

            return response()->json([
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'display_name' => $user->profile?->display_name,
                    'username' => $user->profile?->username,
                    'phone' => $user->profile?->phone,
                    'global_role' => $user->profile?->global_role,
                    'is_verified' => $user->profile?->is_verified,
                    'reputation_badge' => $user->profile?->reputation_badge,
                    'is_operator_aplikasi' => $user->isSystemAdmin(),
                    'is_ketua_paguyuban' => $user->isKetuaPaguyuban(),
                    'is_sekretaris_paguyuban' => $user->isSekretarisPaguyuban(),
                    'is_bendahara_paguyuban' => $user->isBendaharaPaguyuban(),
                    'can_manage_announcements' => $user->canManageAnnouncements(),
                    'can_manage_treasury' => $user->canManageTreasury(),
                    'can_view_treasury' => $user->canViewTreasury(),
                    'is_global_governor' => $user->isGlobalGovernor(),
                    'has_admin_access' => $user->hasAdminAccess(),
                    'assignable_governance_roles' => $user->assignableGovernanceRoles(),
                    'governance_roles' => $user->governanceRoles->map(fn ($r) => [
                        'id' => $r->id,
                        'role_code' => $r->role_code,
                        'display_label' => $r->display_label,
                    ]),
                    'vehicles' => $user->profile?->vehicles ?? [],
                    'privacy_settings' => array_merge([
                        'show_phone' => false,
                        'show_ownership' => false,
                        'show_full_name' => false,
                    ], $user->profile?->privacy_settings ?? []),
                    'has_nik' => ! empty($user->profile?->nik_encrypted),
                    'has_kk' => ! empty($user->profile?->kk_encrypted),
                    'has_identity_docs' => ! empty($user->profile?->nik_encrypted) && ! empty($user->profile?->kk_encrypted),
                    'memberships' => $user->unitMemberships,
                ],
            ]);
        });

        // 3. Endpoint Eksklusif Warga PPMT Terverifikasi & Aktif
        Route::middleware('warga.ppmt')->group(function () {
            Route::get('/verifications/pending', [VerificationController::class, 'pending']);
            Route::post('/verifications/approve', [VerificationController::class, 'approve']);
            Route::get('/verifications/ownership-pending', [GovernanceController::class, 'pendingOwnershipVerifications']);
            Route::post('/verifications/ownership/{membership}', [VerificationController::class, 'verifyOwnership']);

            Route::post('/units/{unit_code}/invites', [UnitInviteController::class, 'store']);
            Route::patch('/profile', [ProfileController::class, 'updateProgressiveProfile']);
            Route::patch('/memberships/{membership}', [ProfileController::class, 'updateMembershipRole']);
            Route::get('/residents/directory', [ProfileController::class, 'directory']);

            // Pasar Tetangga & Lapak Usaha Warga
            Route::get('/umkm/my-store', [UmkmStoreController::class, 'myStore']);
            Route::get('/umkm/stores', [UmkmStoreController::class, 'index']);
            Route::post('/umkm/stores', [UmkmStoreController::class, 'store']);
            Route::patch('/umkm/stores/{store}', [UmkmStoreController::class, 'update']);
            Route::patch('/umkm/stores/{store}/status', [UmkmStoreController::class, 'updateStatus']);

            // Papan Pengumuman Paguyuban
            Route::get('/announcements', [AnnouncementController::class, 'index']);
            Route::post('/announcements', [AnnouncementController::class, 'store']);
            Route::patch('/announcements/{announcement}', [AnnouncementController::class, 'update']);
            Route::delete('/announcements/{announcement}', [AnnouncementController::class, 'destroy']);

            // Agenda & Jadwal Lingkungan (Sekretaris & Ketua Paguyuban)
            Route::get('/agendas', [AgendaController::class, 'index']);
            Route::post('/agendas', [AgendaController::class, 'store']);
            Route::patch('/agendas/{agenda}', [AgendaController::class, 'update']);
            Route::delete('/agendas/{agenda}', [AgendaController::class, 'destroy']);

            // Arsip Notulensi Rapat & Tata Tertib
            Route::get('/meeting-minutes', [MeetingMinuteController::class, 'index']);
            Route::post('/meeting-minutes', [MeetingMinuteController::class, 'store']);
            Route::patch('/meeting-minutes/{meetingMinute}', [MeetingMinuteController::class, 'update']);
            Route::delete('/meeting-minutes/{meetingMinute}', [MeetingMinuteController::class, 'destroy']);

            // Keuangan & Kas Paguyuban (Laporan Warga + Pembukuan Bendahara)
            Route::get('/treasury/public-report', [TreasuryController::class, 'publicReport']);
            Route::get('/treasury/overview', [TreasuryController::class, 'overview']);
            Route::post('/treasury/dues', [TreasuryController::class, 'setDuesStatus']);
            Route::post('/treasury/ledger', [TreasuryController::class, 'storeLedger']);
            Route::delete('/treasury/ledger/{entry}', [TreasuryController::class, 'destroyLedger']);

            // Tata Kelola Jabatan, Buku Induk & Assisted Register
            Route::get('/governance/occupancy-registry', [GovernanceController::class, 'occupancyRegistry']);
            Route::get('/governance/roles', [GovernanceController::class, 'index']);
            Route::post('/governance/assign', [GovernanceController::class, 'assign']);
            Route::delete('/governance/roles/{role}', [GovernanceController::class, 'revoke']);
            Route::post('/governance/assisted-register', [GovernanceController::class, 'assistedRegister']);

            // Aspirasi & Petisi Kolektif Warga
            Route::get('/aspirations', [AspirationController::class, 'index']);
            Route::post('/aspirations', [AspirationController::class, 'store']);
            Route::post('/aspirations/{aspiration}/sign', [AspirationController::class, 'sign']);
            Route::patch('/aspirations/{aspiration}/status', [AspirationController::class, 'updateStatus']);

            Route::get('/news', [NewsController::class, 'index']);
            Route::get('/news/{year}/{month}/{day}/{slug}', [NewsController::class, 'show'])
                ->where(['year' => '\d{4}', 'month' => '\d{2}', 'day' => '\d{2}']);
            Route::post('/news', [NewsController::class, 'store']);

            Route::get('/admin/news', [NewsController::class, 'adminIndex']);
            Route::patch('/admin/news/{newsArticle}', [NewsController::class, 'update']);
            Route::delete('/admin/news/{newsArticle}', [NewsController::class, 'destroy']);

            // Pemadupadanan Kontak Grup WhatsApp (Khusus Admin Sistem)
            Route::get('/admin/wa-contacts', [WhatsappContactReconciliationController::class, 'index']);
            Route::post('/admin/wa-contacts/import', [WhatsappContactReconciliationController::class, 'importCsv']);
            Route::post('/admin/wa-contacts/reconcile', [WhatsappContactReconciliationController::class, 'reconcileAll']);
            Route::patch('/admin/wa-contacts/{contact}', [WhatsappContactReconciliationController::class, 'update']);

            // Peta Kavling / Siteplan Internal (Hanya Warga PPMT Terverifikasi & Pengurus)
            Route::get('/siteplan/units', [SiteplanController::class, 'index']);
        });
    });
});
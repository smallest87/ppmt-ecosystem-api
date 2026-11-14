<?php

use App\Http\Controllers\Api\V1\AuthClaimController;
use App\Http\Controllers\Api\V1\PublicUnitController;
use App\Http\Controllers\Api\V1\VerificationController;
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
            $user = $request->user()->load(['profile', 'unitMemberships.unit']);

            return response()->json([
                'data' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'global_role' => $user->profile?->global_role,
                    'is_verified' => $user->profile?->is_verified,
                    'reputation_badge' => $user->profile?->reputation_badge,
                    'memberships' => $user->unitMemberships,
                ],
            ]);
        });

        // 3. Endpoint Eksklusif Warga PPMT Terverifikasi & Aktif
        Route::middleware('warga.ppmt')->group(function () {
            Route::post('/verifications/approve', [VerificationController::class, 'approve']);
        });
    });
});
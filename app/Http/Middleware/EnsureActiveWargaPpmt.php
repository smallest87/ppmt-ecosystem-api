<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveWargaPpmt
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->profile?->is_verified) {
            return response()->json([
                'message' => 'Akses ditolak. Akun Anda belum terverifikasi sebagai Warga PPMT.',
                'code' => 'UNVERIFIED_WARGA_PPMT',
            ], 403);
        }

        $hasActiveMembership = $user->unitMemberships()
            ->where('is_approved', true)
            ->where(function ($query) {
                $query->whereNull('valid_until')
                      ->orWhereDate('valid_until', '>=', now()->toDateString());
            })
            ->exists();

        if (! $hasActiveMembership) {
            return response()->json([
                'message' => 'Keanggotaan unit atau masa sewa Anda telah berakhir.',
                'code' => 'MEMBERSHIP_EXPIRED',
            ], 403);
        }

        return $next($request);
    }
}
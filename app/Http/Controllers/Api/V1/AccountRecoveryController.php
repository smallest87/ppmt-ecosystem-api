<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AccountAuditLog;
use App\Models\PasswordRecoveryTicket;
use App\Models\UnitMembership;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AccountRecoveryController extends Controller
{
    /**
     * Helper eksak untuk memastikan token benar-benar token impersonation (bukan wildcard ['*']).
     */
    private function isImpersonationToken($token): bool
    {
        if (! $token) {
            return false;
        }

        return str_starts_with((string) $token->name, 'impersonated-by:')
            || in_array('impersonated', $token->abilities ?? [], true);
    }

    /**
     * 1. Warga mengajukan permintaan reset sandi (Publik / Tanpa Login)
     */
    public function requestReset(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'login_id' => ['required', 'string', 'max:60'],
            'unit_code' => ['required', 'string', 'max:20'],
        ], [
            'login_id.required' => 'Masukkan Nomor WhatsApp atau Username Anda.',
            'unit_code.required' => 'Pilih atau ketik nomor kavling Anda.',
        ]);

        $identifier = trim($validated['login_id']);
        $lowerIdentifier = strtolower($identifier);
        $normalizedUnit = strtoupper(trim($validated['unit_code']));
        $normalizedUnit = preg_replace('/^([A-D])-0+([1-9][0-9]*[A-B]?)$/', '$1-$2', $normalizedUnit);

        $user = User::whereHas('profile', function ($q) use ($identifier, $lowerIdentifier) {
            $q->where('phone', $identifier)->orWhere('username', $lowerIdentifier);
        })->whereHas('unitMemberships.unit', function ($q) use ($normalizedUnit) {
            $q->where('code', $normalizedUnit);
        })->first();

        // Pesan generik agar tidak membocorkan enumerasi akun jika tidak cocok
        if (! $user) {
            return response()->json([
                'message' => 'Jika Nomor WA/Username dan Kavling cocok, permintaan pemulihan telah dikirim ke Koordinator Wilayah / Admin Sistem.',
            ]);
        }

        // Batalkan tiket lama yang belum terpakai
        PasswordRecoveryTicket::where('user_id', $user->id)
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        $otpCode = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

        PasswordRecoveryTicket::create([
            'user_id' => $user->id,
            'unit_code' => $normalizedUnit,
            'code_plain' => $otpCode,
            'requested_ip' => $request->ip(),
            'expires_at' => now()->addMinutes(15),
        ]);

        AccountAuditLog::create([
            'target_user_id' => $user->id,
            'actor_user_id' => null,
            'action' => 'password_recovery_requested',
            'reason' => "Pengajuan pemulihan kata sandi mandiri untuk Kavling {$normalizedUnit}.",
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Permintaan pemulihan berhasil dicatat (berlaku 15 menit). Silakan hubungi Koordinator Blok/Zona atau Admin Sistem untuk menerima 6-digit Kode Pemulihan.',
        ]);
    }

    /**
     * 2. Warga mengeksekusi reset sandi menggunakan 6-digit Kode Pemulihan
     */
    public function confirmReset(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'login_id' => ['required', 'string', 'max:60'],
            'recovery_code' => ['required', 'digits:6'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'recovery_code.digits' => 'Kode pemulihan harus berupa 6 digit angka dari pengurus.',
            'password.confirmed' => 'Konfirmasi kata sandi baru tidak cocok.',
        ]);

        $identifier = trim($validated['login_id']);
        $lowerIdentifier = strtolower($identifier);

        $user = User::whereHas('profile', function ($q) use ($identifier, $lowerIdentifier) {
            $q->where('phone', $identifier)->orWhere('username', $lowerIdentifier);
        })->first();

        if (! $user) {
            return response()->json(['message' => 'Identitas akun atau kode pemulihan tidak valid.'], 422);
        }

        $ticket = PasswordRecoveryTicket::where('user_id', $user->id)
            ->where('code_plain', $validated['recovery_code'])
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if (! $ticket) {
            return response()->json([
                'message' => 'Kode pemulihan salah atau sudah kedaluwarsa (batas waktu 15 menit). Ajukan ulang permintaan pemulihan.',
            ], 422);
        }

        DB::transaction(function () use ($user, $ticket, $validated, $request) {
            $user->update([
                'password' => Hash::make($validated['password']),
            ]);

            // Cabut seluruh sesi lama agar perangkat lama otomatis keluar
            $user->tokens()->delete();

            $ticket->update(['used_at' => now()]);

            AccountAuditLog::create([
                'target_user_id' => $user->id,
                'actor_user_id' => $user->id,
                'action' => 'password_reset_completed',
                'reason' => 'Warga berhasil mengatur ulang kata sandi menggunakan kode pemulihan 6-digit.',
                'ip_address' => $request->ip(),
            ]);
        });

        $token = $user->createToken('ppmt-client')->plainTextToken;

        return response()->json([
            'message' => 'Kata sandi baru berhasil disimpan. Anda telah masuk kembali ke akun Anda.',
            'token' => $token,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
            ],
        ]);
    }

    /**
     * 3. Antrean Kode Pemulihan bagi Admin Sistem & Koordinator Wilayah
     */
    public function pendingTickets(Request $request): JsonResponse
    {
        $actor = $request->user();

        if (! $actor->hasAdminAccess()) {
            return response()->json(['message' => 'Akses ditolak.'], 403);
        }

        $tickets = PasswordRecoveryTicket::query()
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->with(['user:id,name', 'user.profile:user_id,phone,username', 'user.unitMemberships.unit:id,code,block,zone'])
            ->latest('id')
            ->get()
            ->filter(function (PasswordRecoveryTicket $t) use ($actor) {
                if ($actor->isGlobalGovernor()) {
                    return true;
                }
                $unit = $t->user?->unitMemberships?->first()?->unit;
                return $unit ? $actor->canVouchUnit($unit) : false;
            })
            ->map(fn (PasswordRecoveryTicket $t) => [
                'id' => $t->id,
                'user_id' => $t->user_id,
                'user_name' => $t->user?->name,
                'contact' => $t->user?->profile?->phone ?: ('@' . $t->user?->profile?->username),
                'unit_code' => $t->unit_code,
                'recovery_code' => $t->code_plain,
                'expires_in_minutes' => max(1, (int) now()->diffInMinutes($t->expires_at, false)),
                'requested_at' => $t->created_at?->diffForHumans(),
            ])
            ->values();

        return response()->json(['data' => $tickets]);
    }

    /**
     * 4. Penerbitan Kode Pemulihan Langsung (Admin Sistem / Koordinator / Pemilik Utama Kavling)
     */
    public function issueTicketForUser(Request $request, User $targetUser): JsonResponse
    {
        $actor = $request->user();
        $targetUser->loadMissing(['profile', 'unitMemberships.unit']);

        $targetUnit = $targetUser->unitMemberships->first()?->unit;
        $isUnitPrimaryOwner = $targetUnit && UnitMembership::where('unit_id', $targetUnit->id)
            ->where('user_id', $actor->id)
            ->where('is_owner', true)
            ->where('owner_role', 'primary_owner')
            ->where('is_approved', true)
            ->exists();

        $canIssue = $actor->isGlobalGovernor()
            || ($targetUnit && $actor->canVouchUnit($targetUnit))
            || $isUnitPrimaryOwner;

        if (! $canIssue) {
            return response()->json([
                'message' => 'Anda tidak berwenang menerbitkan kode pemulihan untuk akun warga ini.',
            ], 403);
        }

        PasswordRecoveryTicket::where('user_id', $targetUser->id)
            ->whereNull('used_at')
            ->update(['used_at' => now()]);

        $otpCode = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

        $ticket = PasswordRecoveryTicket::create([
            'user_id' => $targetUser->id,
            'unit_code' => $targetUnit?->code,
            'code_plain' => $otpCode,
            'requested_ip' => $request->ip(),
            'issued_by_user_id' => $actor->id,
            'expires_at' => now()->addMinutes(15),
        ]);

        AccountAuditLog::create([
            'target_user_id' => $targetUser->id,
            'actor_user_id' => $actor->id,
            'action' => 'password_recovery_issued',
            'reason' => "Kode pemulihan 6-digit diterbitkan oleh {$actor->name}.",
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'message' => "Kode pemulihan 6-digit berhasil diterbitkan untuk {$targetUser->name} (berlaku 15 menit).",
            'data' => [
                'user_name' => $targetUser->name,
                'recovery_code' => $ticket->code_plain,
                'expires_at' => $ticket->expires_at->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * 5. Peminjaman Akun Sementara oleh Admin Sistem (Audited Impersonation 15 Menit)
     */
    public function startImpersonation(Request $request, User $targetUser): JsonResponse
    {
        $actor = $request->user();

        if ($this->isImpersonationToken($actor->currentAccessToken())) {
            return response()->json([
                'message' => 'Akhiri sesi bantuan teknis saat ini terlebih dahulu sebelum memulai sesi baru.',
            ], 403);
        }

        // Hanya Admin Sistem murni yang boleh melakukan bantuan akses teknis sementara
        if (! $actor->isPureSystemAdmin()) {
            return response()->json([
                'message' => 'Akses ditolak. Hanya Admin Sistem yang dapat melakukan sesi bantuan teknis sementara.',
            ], 403);
        }

        if ((int) $actor->id === (int) $targetUser->id) {
            return response()->json(['message' => 'Anda sudah masuk menggunakan akun Anda sendiri.'], 422);
        }

        $targetUser->loadMissing('governanceRoles');

        // Larang meng-impersonate sesama Admin Sistem agar tidak terjadi eskalasi sirkular
        if ($targetUser->isPureSystemAdmin()) {
            return response()->json([
                'message' => 'Demi keamanan tata kelola, akun Admin Sistem lain tidak dapat dipinjam.',
            ], 403);
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:15', 'max:300'],
        ], [
            'reason.required' => 'Alasan bantuan teknis wajib diisi untuk dicatat pada riwayat keamanan warga.',
            'reason.min' => 'Jelaskan alasan bantuan teknis minimal 15 karakter.',
        ]);

        AccountAuditLog::create([
            'target_user_id' => $targetUser->id,
            'actor_user_id' => $actor->id,
            'action' => 'impersonation_started',
            'reason' => trim($validated['reason']),
            'ip_address' => $request->ip(),
        ]);

        // Terbitkan token terbatas berdurasi 15 menit dengan ability 'impersonated' & ID admin pelaksana
        $expiresAt = now()->addMinutes(15);
        $impersonationToken = $targetUser->createToken(
            "impersonated-by:{$actor->id}",
            ['impersonated', "impersonator:{$actor->id}"],
            $expiresAt
        )->plainTextToken;

        return response()->json([
            'message' => "Sesi bantuan teknis pada akun {$targetUser->name} dimulai (berlaku 15 menit dan tercatat di riwayat keamanan warga).",
            'impersonation_token' => $impersonationToken,
            'expires_at' => $expiresAt->toIso8601String(),
            'target_user' => [
                'id' => $targetUser->id,
                'name' => $targetUser->name,
            ],
        ]);
    }

    /**
     * 6. Mengakhiri Sesi Pinjam & Mencatat Log Penutupan
     */
    public function stopImpersonation(Request $request): JsonResponse
    {
        $user = $request->user();
        $token = $user->currentAccessToken();

        if (! $this->isImpersonationToken($token)) {
            return response()->json(['message' => 'Sesi saat ini bukan sesi bantuan teknis sementara.'], 422);
        }

        $actorId = null;
        foreach ($token->abilities ?? [] as $ability) {
            if (str_starts_with($ability, 'impersonator:')) {
                $actorId = (int) substr($ability, strlen('impersonator:'));
            }
        }

        AccountAuditLog::create([
            'target_user_id' => $user->id,
            'actor_user_id' => $actorId ?: null,
            'action' => 'impersonation_ended',
            'reason' => 'Sesi bantuan teknis sementara diakhiri oleh Admin Sistem.',
            'ip_address' => $request->ip(),
        ]);

        $token->delete();

        return response()->json([
            'message' => 'Sesi bantuan teknis sementara telah diakhiri.',
        ]);
    }

    /**
     * 7. Riwayat Keamanan Transparan bagi Pemilik Akun (Tidak dapat dihapus Admin)
     */
    public function mySecurityLogs(Request $request): JsonResponse
    {
        $logs = AccountAuditLog::query()
            ->where('target_user_id', $request->user()->id)
            ->with('actor:id,name')
            ->latest('id')
            ->limit(30)
            ->get()
            ->map(fn (AccountAuditLog $log) => [
                'id' => $log->id,
                'action' => $log->action,
                'actor_name' => $log->actor?->name ?? 'Pemilik Akun / Mandiri',
                'reason' => $log->reason,
                'created_at' => $log->created_at?->format('d M Y H:i') . ' WIB',
            ]);

        return response()->json(['data' => $logs]);
    }
}
flowchart TD
    Start([Pengguna Membuka Halaman]) --> CheckToken{"Ada ppmt_token di localStorage?"}

    %% Belum Login
    CheckToken -- Tidak --> GuestMode["Mode Tamu: Navbar Publik (Masuk / Daftar / Kegiatan)"]
    GuestMode --> PublicAPI["Akses API Publik: /public/units, /auth/login, /auth/register-claim, /auth/forgot-password"]

    %% Sudah Punya Token
    CheckToken -- Ya --> CallMe["Client Fetch: GET /api/v1/me (Bearer Token)"]
    CallMe --> SanctumCheck{"auth:sanctum Valid?"}

    SanctumCheck -- "401 Unauthorized" --> ResetSession["Hapus Token & Cache Sesi -> Kembali ke Mode Tamu"]
    SanctumCheck -- "200 OK" --> CheckImp{"data.is_impersonated == true?"}

    CheckImp -- Ya --> ImpBanner["Tampilkan Banner Merah Sesi Bantuan Teknis (/auth/stop-impersonation)"]
    CheckImp -- Tidak --> CheckVerified
    ImpBanner --> CheckVerified{"data.is_verified == true?"}

    %% Jalur Akun Pending
    CheckVerified -- "False (Pending)" --> PendingMode["Mode Terbatas: Hanya /me & /me/security-logs"]

    %% Jalur Warga Terverifikasi -> Middleware warga.ppmt
    CheckVerified -- "True (Terverifikasi)" --> WargaMW["Middleware Backend: warga.ppmt (EnsureActiveWargaPpmt)"]
    WargaMW --> CheckMembership{"Punya UnitMembership Approved & valid_until >= Hari Ini?"}

    CheckMembership -- "Tidak (403 MEMBERSHIP_EXPIRED)" --> ExpiredBlock["Blokir API Warga (Masa Sewa / Keanggotaan Berakhir)"]
    CheckMembership -- Ya --> ActiveAccess["Akses Penuh Endpoint Warga & Evaluasi Capability Flags"]
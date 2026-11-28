flowchart TD
    Req([Request Lolos Middleware warga.ppmt]) --> ActionType{Jenis Aksi Sensitif}

    %% 1. UnitPolicy
    ActionType -- "Buat Tautan Undangan Kavling" --> PolUnit["UnitPolicy::inviteMember(User, Unit)"]
    PolUnit --> CekVerif1{"user.profile.is_verified?"}
    CekVerif1 -- Tidak --> Deny1(["403 Forbidden"])
    CekVerif1 -- Ya --> CekPrimary{"Anggota Approved (primary_owner / primary_resident) & valid_until >= Hari Ini?"}
    CekPrimary -- Tidak --> Deny1
    CekPrimary -- Ya --> Allow1(["201 Created: Token Undangan 48 Jam"])

    %% 2. VerificationPolicy
    ActionType -- "Verifikasi Warga / Sahkan NIB SHM" --> PolVerif["VerificationPolicy::vouchUser(Voucher, Unit)"]
    PolVerif --> CanVouch["User::canVouchUnit(Unit)"]
    CanVouch --> CekVouch{"Global Governor OR Koordinator Zona/Blok Unit OR Primary Owner Unit OR Penjamin Klaim?"}
    CekVouch -- Tidak --> Deny2(["403 Forbidden"])
    CekVouch -- Ya --> Allow2(["Lolos Otorisasi Verifikasi"])

    %% 3. Impersonation & Role Guardrails
    ActionType -- "Ubah Sandi/NIK atau Atur Jabatan" --> CekImp{"Token Aktif adalah Impersonation?"}
    CekImp -- Ya --> Deny3(["403 Forbidden (Terkunci Selama Sesi Bantuan)"])
    CekImp -- Tidak --> CekGov{"User::canAssignGovernanceRole() / isPureSystemAdmin()?"}
    CekGov -- Tidak --> Deny3
    CekGov -- Ya --> Allow3(["Lolos Otorisasi Tata Kelola"])
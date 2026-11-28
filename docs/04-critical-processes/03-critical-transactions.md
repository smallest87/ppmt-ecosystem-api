sequenceDiagram
    autonumber
    actor Aktor as Warga / Bendahara / Admin
    participant Ctrl as Controller Terkait
    participant Gate as Policy & Guardrails
    participant DB as Database (DB::transaction)

    Note over Aktor,DB: 1. Pelunasan Iuran Sampah -> Otomatis Buku Kas (TreasuryController@setDuesStatus)
    Aktor->>Ctrl: POST /treasury/dues (action: paid | exempt | unpaid)
    Ctrl->>Gate: Cek $actor->canManageTreasury()
    Ctrl->>DB: Mulai Transaksi
    alt action == 'paid'
        DB->>DB: Create/Update CashLedgerEntry (income: Rp 20.000)
        DB->>DB: UpdateOrCreate DuesPayment (link cash_ledger_entry_id)
    else action == 'unpaid' / 'exempt'
        DB->>DB: Hapus CashLedgerEntry terkait & update/hapus DuesPayment
    end
    DB-->>Ctrl: Commit Sukses
    Ctrl-->>Aktor: 200 OK (Status Iuran & Kas Tersinkron)

    Note over Aktor,DB: 2. Guardrail Sesi Bantuan Teknis (AccountRecovery -> Profile & Governance)
    Aktor->>Ctrl: POST /governance/impersonate/{targetUser}
    Ctrl->>Gate: Cek isPureSystemAdmin() & target bukan Admin Sistem
    Ctrl->>DB: Catat AccountAuditLog + Terbitkan Token 15 Menit ['impersonated']
    Ctrl-->>Aktor: Token Impersonation Aktif
    Note right of Aktor: Saat Admin memakai token ini ke Controller lain:
    Aktor->>Ctrl: PATCH /profile (ubah sandi/NIK) atau POST /governance/assign
    Ctrl->>Gate: Deteksi isImpersonatedRequest() / isImpersonationToken()
    Gate-->>Aktor: 403 Forbidden (Aksi sensitif diblokir otomatis)

    Note over Aktor,DB: 3. Pengesahan Kepemilikan NIB SHM Berjenjang (VerificationController@verifyOwnership)
    Aktor->>Ctrl: POST /verifications/ownership/{membership}
    Ctrl->>Gate: Gate::authorize('vouchUser', $unit)
    Ctrl->>DB: Mulai Transaksi
    DB->>DB: UpdateOrCreate OwnershipVerification (per actor_id)
    DB->>DB: Cek sisa pengesah -> Update UnitMembership (verified_shm / unverified_claim)
    DB->>DB: Sinkronkan ResidentProfile.is_ownership_doc_verified (Badge Level 4)
    DB-->>Ctrl: Commit Sukses
    Ctrl-->>Aktor: 200 OK (Ringkasan pengesah diperbarui)
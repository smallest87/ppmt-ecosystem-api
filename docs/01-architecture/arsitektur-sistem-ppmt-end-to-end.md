flowchart LR
    %% =========================================================
    %% 1. LAPISAN PRESENTASI (BLADE VIEWS)
    %% =========================================================
    subgraph VIEW["1. Lapisan Web UI (resources/views/*)"]
        V_PUB["Publik & Auth<br/>- index / beranda<br/>- siteplan (2.5D)<br/>- login & register"]
        V_KOM["Komunitas (/komunitas/*)<br/>- berita, agenda, notulensi<br/>- pasar-tetangga"]
        V_SET["Pengaturan (/pengaturan/*)<br/>- index, akun, hunian, lapak"]
        V_ADM["Panel Pengurus (/pengurus/*)<br/>- buku-induk, keuangan, pasar-tetangga<br/>- publikasi, hak-akses"]
    end

    %% =========================================================
    %% 2. LAPISAN CLI CONSOLE
    %% =========================================================
    subgraph CLI["2. Lapisan CLI (app/Console/Commands/*)"]
        CMD_GOV["AssignGovernanceRoleCommand<br/>RevokeGovernanceRoleCommand<br/>(ppmt:assign-governance / revoke)"]
    end

    %% =========================================================
    %% 3. LAPISAN API CONTROLLERS (App\Http\Controllers\Api\V1)
    %% =========================================================
    subgraph CTRL["3. API Controllers (/api/v1/*)"]
        C_AUTH["Auth & Akun<br/>- AuthClaimController<br/>- ProfileController<br/>- AccountRecoveryController"]
        C_UNIT["Hunian & Verifikasi<br/>- PublicUnitController<br/>- SiteplanController<br/>- UnitInviteController<br/>- VerificationController"]
        C_GOV["Tata Kelola & Kontak WA<br/>- GovernanceController<br/>- WhatsappContactReconciliationController"]
        C_KAS["Keuangan & Iuran<br/>- TreasuryController"]
        C_KOM["Komunitas & Editorial<br/>- AnnouncementController<br/>- NewsController<br/>- AgendaController<br/>- MeetingMinuteController<br/>- AspirationController"]
        C_UMKM["Pasar Tetangga<br/>- UmkmStoreController"]
    end

    %% =========================================================
    %% 4. LAPISAN MODEL & DATABASE (App\Models\*)
    %% =========================================================
    subgraph DB["4. Model Eloquent & Database"]
        M_USER["User, ResidentProfile,<br/>AccountAuditLog, PasswordRecoveryTicket"]
        M_UNIT["Unit, UnitMembership, UnitInvite,<br/>VerificationVoucher, OwnershipVerification"]
        M_GOV["UserGovernanceRole,<br/>GovernanceRoleDefinition,<br/>WhatsappGroupContact"]
        M_KAS["CashLedgerEntry, DuesPayment"]
        M_KOM["Announcement, NewsArticle,<br/>EnvironmentAgenda, MeetingMinute,<br/>Aspiration, AspirationSignature"]
        M_UMKM["UmkmStore"]
    end

    %% =========================================================
    %% ALUR VIEW -> CONTROLLER
    %% =========================================================
    V_PUB --> C_AUTH
    V_PUB --> C_UNIT
    V_PUB --> C_KOM
    V_KOM --> C_KOM
    V_KOM --> C_UMKM
    V_SET --> C_AUTH
    V_SET --> C_UNIT
    V_SET --> C_UMKM
    V_ADM --> C_AUTH
    V_ADM --> C_UNIT
    V_ADM --> C_GOV
    V_ADM --> C_KAS
    V_ADM --> C_KOM
    V_ADM --> C_UMKM

    %% =========================================================
    %% ALUR CONTROLLER -> MODEL
    %% =========================================================
    C_AUTH --> M_USER
    C_AUTH --> M_UNIT
    C_UNIT --> M_UNIT
    C_UNIT --> M_USER
    C_UNIT -. "Status iuran & UMKM" .-> M_UMKM
    C_GOV --> M_GOV
    C_GOV --> M_UNIT
    C_GOV --> M_USER
    C_KAS --> M_KAS
    C_KAS --> M_UNIT
    C_KOM --> M_KOM
    C_UMKM --> M_UMKM
    C_UMKM --> M_UNIT

    %% =========================================================
    %% ALUR CLI -> MODEL (BYPASS HTTP)
    %% =========================================================
    CMD_GOV --> M_GOV
    CMD_GOV --> M_USER
    CMD_GOV --> M_UNIT
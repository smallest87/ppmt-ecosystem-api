flowchart TD
    %% =========================================================
    %% JALUR 1: ANTARMUKA WEB (BROWSER)
    %% =========================================================
    subgraph WEB["1. Lapisan Web UI (resources/views/*)"]
        ADM_BUKU["pengurus/buku-induk.blade.php<br/>Penyematan Jabatan & Daftarkan Tetangga"]
        ADM_ROLE["pengurus/hak-akses.blade.php<br/>Audit Pemegang Role & Mandat Teknis"]
    end

    %% =========================================================
    %% JALUR 2: REST API
    %% =========================================================
    subgraph API["2. Lapisan REST API (/api/v1/governance/*)"]
        API_GOV["/api/v1/governance/assign<br/>/api/v1/governance/roles/{id}<br/>/api/v1/governance/access-matrix"]
    end

    %% =========================================================
    %% JALUR 3: CLI / ARTISAN CONSOLE (2 FILE BARU)
    %% =========================================================
    subgraph CLI["3. Lapisan Console / CLI (app/Console/Commands/*)"]
        CMD_ASSIGN["AssignGovernanceRoleCommand.php<br/>php artisan ppmt:assign-governance<br/>- Bootstrap Genesis Node (auto-verify warga)<br/>- Sematkan jabatan via terminal"]
        CMD_REVOKE["RevokeGovernanceRoleCommand.php<br/>php artisan ppmt:revoke-governance<br/>- Cabut jabatan via terminal<br/>- Kunci pengaman Admin terakhir (--force)"]
    end

    %% =========================================================
    %% LAPISAN DATA / MODEL ELOQUENT
    %% =========================================================
    subgraph DB["4. Lapisan Model & Database (App\Models\*)"]
        M_GOV["UserGovernanceRole<br/>(role_code, scope_type, scope_value, is_active)"]
        M_RES["ResidentProfile & UnitMembership<br/>(global_role, is_verified, is_approved)"]
        M_VOUCH["VerificationVoucher<br/>(method: genesis_seed)"]
    end

    %% Relasi Web -> API -> Model
    ADM_BUKU --> API_GOV
    ADM_ROLE --> API_GOV
    API_GOV --> M_GOV

    %% Relasi CLI -> Model (Bypass HTTP/API, langsung ke Model)
    CMD_ASSIGN --> M_GOV
    CMD_ASSIGN --> M_RES
    CMD_ASSIGN --> M_VOUCH
    CMD_REVOKE --> M_GOV
    CMD_REVOKE -. "Baca profil via phone" .-> M_RES
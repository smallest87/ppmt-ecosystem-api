flowchart TD
    %% =========================================================
    %% MASTER LAYOUT
    %% =========================================================
    LAYOUT["layouts/app.blade.php<br/>Master Layout & Navigasi Global"]

    %% =========================================================
    %% 1. HALAMAN PUBLIK & AUTENTIKASI
    %% =========================================================
    subgraph PUB["1. Halaman Publik & Portal Warga"]
        INDEX["index / beranda<br/>Portal Utama, Pengumuman, Berita, Agenda & Direktori"]
        SITEPLAN["siteplan<br/>Peta Interaktif 2.5D My PPMT"]
        LOGIN["login / register<br/>Autentikasi Token (ppmt_token)"]
    end

    %% =========================================================
    %% 2. MODUL KOMUNITAS WARGA
    %% =========================================================
    subgraph KOM["2. Modul Komunitas (/komunitas/*)"]
        KOM_BERITA["komunitas/berita<br/>Kabar Warga & Artikel"]
        KOM_AGENDA["komunitas/agenda<br/>Jadwal Kegiatan Lingkungan"]
        KOM_NOTULENSI["komunitas/notulensi<br/>Arsip Notulensi & Tata Tertib"]
        KOM_PASAR["komunitas/pasar-tetangga<br/>Etalase UMKM Warga (Round-Robin + Sorotan)"]
    end

    %% =========================================================
    %% 3. MODUL PENGATURAN AKUN WARGA
    %% =========================================================
    subgraph SET["3. Pengaturan Warga (/pengaturan/*)"]
        SET_IDX["pengaturan/index.blade.php<br/>Hub Menu Pengaturan (ppmtSettingsHub)"]
        SET_AKUN["pengaturan/akun<br/>Profil & Dokumen Sipil (NIK/KK)"]
        SET_HUNIAN["pengaturan/hunian.blade.php<br/>Kepemilikan Kavling (NIB SHM), Penghuni & Kendaraan"]
        SET_LAPAK["pengaturan/lapak.blade.php<br/>Manajemen Lapak UMKM & Katalog WebP"]
    end

    %% =========================================================
    %% 4. MODUL PANEL PENGURUS & TATA KELOLA
    %% =========================================================
    subgraph ADM["4. Panel Pengurus (/pengurus/*)"]
        ADM_HUB["pengurus/index<br/>Dashboard Utama Pengurus"]
        ADM_BUKU["pengurus/buku-induk.blade.php<br/>Buku Induk Kavling, NIB SHM, Tiket Sandi & Kabinet"]
        ADM_KEU["pengurus/keuangan.blade.php<br/>Kas Umum & Kartu Iuran Sampah Rp 20.000 (ppmtTreasuryPanel)"]
        ADM_UMKM["pengurus/pasar-tetangga.blade.php<br/>Kurasi Sorotan & Moderasi Lapak (ppmtMarketplaceGovernance)"]
        ADM_PUB["pengurus/publikasi.blade.php<br/>Manajemen Publikasi, Agenda & Notulensi (ppmtEditorialPanel)"]
        ADM_ROLE["pengurus/hak-akses.blade.php<br/>Role Management & Audit Hak Akses (ppmtRoleAuditPanel)"]
    end

    %% =========================================================
    %% PARSIAL EDITORIAL (REFERENSI AKTUAL VS INCLUDE)
    %% =========================================================
    subgraph PARTIALS["Parsial Editorial Pengurus"]
        P_ACTUAL["pengurus/partials/editorial/*<br/>- tab-publikasi.blade.php<br/>- tab-agenda.blade.php<br/>- tab-notulensi.blade.php<br/>- create-modals.blade.php<br/>- edit-modals.blade.php<br/>- scripts.blade.php"]
        P_EXPECTED["Path @include di publikasi.blade.php:<br/>pengurus.partials.publikasi.*<br/>(modals-create, modals-edit, dll.)"]
    end

    %% =========================================================
    %% RELASI LAYOUT & NAVIGASI
    %% =========================================================
    LAYOUT -.-> PUB
    LAYOUT -.-> KOM
    LAYOUT -.-> SET
    LAYOUT -.-> ADM

    INDEX --> KOM_BERITA
    INDEX --> KOM_AGENDA
    INDEX --> KOM_NOTULENSI
    INDEX --> KOM_PASAR
    INDEX --> SITEPLAN
    INDEX --> SET_IDX
    INDEX --> ADM_HUB

    SET_IDX --> SET_AKUN
    SET_IDX --> SET_HUNIAN
    SET_IDX -. "x-show='myStore'" .-> SET_LAPAK

    ADM_HUB --> ADM_BUKU
    ADM_HUB --> ADM_KEU
    ADM_HUB --> ADM_UMKM
    ADM_HUB --> ADM_PUB
    ADM_HUB --> ADM_ROLE

    ADM_UMKM --> KOM_PASAR
    ADM_PUB -. "@include" .-> P_EXPECTED
    P_EXPECTED x--x|"Mismatch nama folder/file"| P_ACTUAL

    %% =========================================================
    %% ENDPOINT API (/api/v1/*)
    %% =========================================================
    subgraph API["Backend REST API (/api/v1/*)"]
        API_ME["/api/v1/me<br/>/api/v1/residents/directory"]
        API_UNIT["/api/v1/units/* & /api/v1/vehicles/*"]
        API_TREASURY["/api/v1/treasury/overview<br/>/api/v1/treasury/dues<br/>/api/v1/treasury/ledger"]
        API_UMKM["/api/v1/umkm/my-store<br/>/api/v1/umkm/governance/stores/*"]
        API_GOV["/api/v1/governance/access-matrix<br/>/api/v1/governance/role-definitions/*<br/>/api/v1/governance/assign & /roles/*"]
        API_EDIT["/api/v1/announcements, /news,<br/>/agendas, /meeting-minutes"]
    end

    SET_HUNIAN --> API_UNIT
    SET_LAPAK --> API_UMKM
    ADM_BUKU --> API_ME
    ADM_BUKU --> API_UNIT
    ADM_KEU --> API_TREASURY
    ADM_UMKM --> API_UMKM
    ADM_ROLE --> API_GOV
    ADM_ROLE --> API_ME
    P_ACTUAL --> API_EDIT
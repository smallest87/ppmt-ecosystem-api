flowchart LR
    Me["GET /api/v1/me"] --> Flags{Evaluasi Flags & Scope Wilayah}

    %% 1. Kewilayahan
    Flags -- "koordinator_zona / koordinator_blok" --> ScopeWilayah["Cakupan Teritorial (canVouchUnit)"]
    ScopeWilayah --> T_Verif["Antrean Verifikasi Warga & NIB SHM Blok Binaan"]
    ScopeWilayah --> T_Dir["Buka Privasi Nama, WA & Kepemilikan di Blok Binaan"]

    %% 2. Keuangan
    Flags -- "can_view_treasury / can_manage_treasury" --> R_Keu["/pengurus/keuangan"]
    R_Keu --> E_Keu["GET /treasury/overview, POST /dues, POST/DELETE /ledger"]

    %% 3. Publikasi
    Flags -- "can_manage_announcements / is_global_governor" --> R_Pub["/pengurus/publikasi"]
    R_Pub --> E_Pub["CRUD /announcements, /agendas, /meeting-minutes, /admin/news & /aspirations/{id}/status"]

    %% 4. Pasar Tetangga
    Flags -- "can_manage_marketplace / is_seksi_ekonomi" --> R_Pasar["/pengurus/pasar-tetangga"]
    R_Pasar --> E_Pasar["GET /umkm/governance/stores & PATCH .../curate"]

    %% 5. Buku Induk & Jabatan
    Flags -- "Pengurus Aktif / is_global_governor" --> R_Buku["/pengurus/buku-induk"]
    R_Buku --> E_Buku["GET /occupancy-registry, POST /assisted-register, CRUD /governance/roles & /recovery-tickets"]

    %% 6. Admin Sistem
    Flags -- "is_operator_aplikasi / is_pure_system_admin" --> R_Sys["/pengurus/hak-akses & /pengurus/pemadupadanan-kontak"]
    R_Sys --> E_Sys["GET /access-matrix, CRUD /role-definitions (Pure Admin) & CRUD /admin/wa-contacts"]
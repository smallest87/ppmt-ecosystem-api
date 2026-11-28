# Dokumentasi Arsitektur Sistem — PPMT Digital

Dokumentasi arsitektur aplikasi **Paguyuban Warga Perumahan Pesona Mandiri Turen (PPMT Digital)** berbasis **Laravel 13**, **Sanctum**, **Blade**, **Tailwind CSS**, dan **Alpine.js**.

| Lapisan | Dokumen | Jenis Diagram | Fokus & Kegunaan |
| :--- | :--- | :--- | :--- |
| **1. Infrastruktur & Rute** | `01-architecture/01-system-context.md` | `flowchart LR` | Interaksi browser, Vite/Alpine.js, Laravel API V1, database, dan disk penyimpanan. |
| **1. Infrastruktur & Rute** | `01-architecture/02-security-and-session-gates.md` | `flowchart TD` | Alur token Sanctum, mode Pending, middleware `warga.ppmt`, dan banner impersonation. |
| **1. Infrastruktur & Rute** | `01-architecture/03-web-navigation-and-layout.md` | `flowchart LR` | Peta rute `web.php`, pemilihan Navbar Utama vs Sub-Page Header, dan 4 Hub utama. |
| **1. Infrastruktur & Rute** | `01-architecture/04-api-controllers-map.md` | `flowchart LR` | Distribusi 16 Controller API V1 berdasarkan lapisan keamanan dan domain fitur. |
| **2. Domain Model (ORM)** | `02-domain-models/01-core-identity-and-governance.md` | `classDiagram` | Relasi `User`, `ResidentProfile`, `Unit`, `UnitMembership`, `OwnershipVerification`, dan `UserGovernanceRole`. |
| **2. Domain Model (ORM)** | `02-domain-models/02-operations-and-community.md` | `classDiagram` | Relasi Keuangan (`DuesPayment`, `CashLedgerEntry`), UMKM, Aspirasi, Publikasi, dan `WhatsappGroupContact`. |
| **3. Alur Pengguna (UX)** | `03-user-flows/01-resident-journey.md` | `flowchart LR` | Perjalanan warga terverifikasi di Beranda, tab Komunitas, Pasar Tetangga, dan Peta Kavling. |
| **3. Alur Pengguna (UX)** | `03-user-flows/02-rbac-admin-matrix.md` | `flowchart LR` | Pemetaan capability flags `/api/v1/me` ke 6 modul ruang kerja `/pengurus`. |
| **4. Proses Kritis** | `04-critical-processes/01-entity-state-machines.md` | `stateDiagram-v2` | Aturan transisi status Kavling, Verifikasi SHM, Aspirasi Warga, Iuran Sampah, dan Kontak WA. |
| **4. Proses Kritis** | `04-critical-processes/02-policies-and-authorization.md` | `flowchart TD` | Otorisasi tingkat objek (`UnitPolicy`, `VerificationPolicy`) dan guardrail sesi bantuan teknis. |
| **4. Proses Kritis** | `04-critical-processes/03-critical-transactions.md` | `sequenceDiagram` | Urutan transaksi lintas tabel (pelunasan iuran -> kas otomatis, verifikasi berjenjang, dan audit log). |
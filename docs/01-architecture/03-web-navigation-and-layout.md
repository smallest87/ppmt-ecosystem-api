flowchart LR
    Layout["layouts/app.blade.php"] --> HeaderSwitch{"Halaman Punya @section('subpage_title')?"}

    %% Mode 1: Navbar Utama
    HeaderSwitch -- Tidak --> MainNavbar["Navbar Utama (Desktop Dropdown + Mobile Drawer)"]

    MainNavbar --> PubPages["Halaman Umum & Autentikasi"]
    PubPages --> W_Home["/ (welcome)"]
    PubPages --> W_Reg["/register"]

    MainNavbar --> CoreHubs["Hub Utama Warga (Login)"]
    CoreHubs --> W_Dash["/dashboard (Beranda)"]
    CoreHubs --> W_Kom["/komunitas (Hub Komunitas)"]
    CoreHubs --> W_SetHub["/pengaturan (Hub Pengaturan)"]
    CoreHubs --> AdminGate{"sessionStorage: ppmt_has_admin == 1?"}

    AdminGate -- Ya --> W_AdminHub["/pengurus (Hub Panel Pengurus, /admin -> redirect)"]

    %% Mode 2: Sub-Page Header
    HeaderSwitch -- Ya --> SubHeader["Sub-Page Header Terfokus (Tombol Kembali)"]

    SubHeader --> SubSet["Sub-Halaman Pengaturan (/pengaturan/...)"]
    SubSet --> S_Akun["/akun"]
    SubSet --> S_Hunian["/hunian"]
    SubSet --> S_Lapak["/lapak"]

    SubHeader --> SubKom["Sub-Halaman Komunitas & Peta"]
    SubKom --> K_Agenda["/komunitas/agenda (/agenda -> 301 redirect)"]
    SubKom --> K_Pasar["/komunitas/pasar-tetangga & /p/{store}/{itemId} (OG Preview)"]
    SubKom --> K_Game["/komunitas/my-ppmt"]
    SubKom --> K_Peta["/peta-kavling (siteplan)"]
    SubKom --> K_News["/berita/{year}/{month}/{day}/{slug}"]

    SubHeader --> SubAdm["Sub-Halaman Panel Pengurus (/pengurus/...)"]
    SubAdm --> A_Keu["/keuangan"]
    SubAdm --> A_Pub["/publikasi"]
    SubAdm --> A_Pasar["/pasar-tetangga"]
    SubAdm --> A_Buku["/buku-induk"]
    SubAdm --> A_Akses["/hak-akses"]
    SubAdm --> A_Wa["/pemadupadanan-kontak"]
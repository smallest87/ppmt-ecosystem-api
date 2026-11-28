flowchart LR
    Warga([Warga Terverifikasi]) --> PilihArea{Pilih Area Aktivitas}

    %% Area 1: Beranda
    PilihArea -- Beranda --> Dash["/dashboard"]
    Dash --> D_Info["Baca Pengumuman, Agenda & Berita (/announcements, /agendas, /news)"]
    Dash --> D_Iuran["Cek Kartu Iuran Sampah Rp 20rb/Tgl 1 (/treasury/public-report)"]
    Dash --> D_Invite["Undang Keluarga/Penyewa (POST /units/{unit_code}/invites)"]

    %% Area 2: Komunitas (4 Tab Internal)
    PilihArea -- Komunitas Tab --> Kom["/komunitas"]
    Kom --> K_Asp["Tab Aspirasi (1 Kavling = 1 Suara): Ajukan & Tandatangani (/aspirations)"]
    Kom --> K_Kas["Tab Kas & Iuran: Pantau Saldo, Pemasukan & Mutasi Bulanan"]
    Kom --> K_Dir["Tab Direktori: Cari Tetangga Terfilter Privasi (/residents/directory)"]
    Kom --> K_Min["Tab Notulensi: Baca Arsip Musyawarah & Tatib (/meeting-minutes)"]

    %% Area 3: Halaman Mandiri Komunitas & Peta
    PilihArea -- Layanan Mandiri --> Mandiri["Rute Mandiri Komunitas & Peta"]
    Mandiri --> M_Pasar["/komunitas/pasar-tetangga (Belanja, Kelola Lapak & Share Link /p/{store}/{itemId})"]
    Mandiri --> M_Agenda["/komunitas/agenda (Kalender Kegiatan Lingkungan)"]
    Mandiri --> M_Peta["/peta-kavling (Siteplan 91 Kavling: /siteplan/units)"]
    Mandiri --> M_Game["/komunitas/my-ppmt (Simulasi Interaktif Lingkungan)"]
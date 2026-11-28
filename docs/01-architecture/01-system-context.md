flowchart LR
    subgraph Client ["Sisi Pengguna (Browser Desktop & Mobile)"]
        Blade["Blade Views (resources/views)"]
        Vite["Vite Bundle (Tailwind CSS & app.js)"]
        Alpine["Alpine.js State & Fetch API"]
        StorageClient["localStorage (ppmt_token) & sessionStorage"]
    end

    subgraph App ["Aplikasi Laravel 13 (PHP ^8.3)"]
        WebRoutes["Web Routes (routes/web.php)"]
        ApiRoutes["API V1 Routes (routes/api.php)"]
        Sanctum["Laravel Sanctum ^4.0 (Token Auth)"]
        WargaMW["Middleware: warga.ppmt (EnsureActiveWargaPpmt)"]
        Controllers["16 API V1 Controllers"]
    end

    subgraph Infra ["Infrastruktur & Penyimpanan (.env)"]
        DB[("Database Utama (SQLite / MySQL)")]
        SessionCache[("Session, Cache & Queue (Database Driver)")]
        FileDisk[("Local Storage Disk (Foto Berita & UMKM)")]
    end

    Blade --> Vite
    Blade --> Alpine
    Alpine <--> StorageClient

    Blade -- "HTTP Request (Halaman Web)" --> WebRoutes
    Alpine -- "REST JSON + Bearer Token" --> ApiRoutes

    ApiRoutes --> Sanctum
    Sanctum --> WargaMW
    WargaMW --> Controllers
    WebRoutes -- "Deep Link OG Meta" --> DB

    Controllers <--> DB
    Controllers <--> SessionCache
    Controllers <--> FileDisk
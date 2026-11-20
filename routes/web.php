<?php

use App\Models\UmkmStore;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');
Route::view('/register', 'register')->name('register');
Route::view('/dashboard', 'dashboard')->name('dashboard');
Route::view('/agenda', 'agenda.index')->name('agenda.index');

// Pengaturan Modular (Hub + Sub-halaman Terfokus)
Route::prefix('pengaturan')->name('pengaturan.')->group(function () {
    Route::view('/', 'pengaturan.index')->name('index');
    Route::view('/akun', 'pengaturan.akun')->name('akun');
    Route::view('/hunian', 'pengaturan.hunian')->name('hunian');
    Route::view('/lapak', 'pengaturan.lapak')->name('lapak');
});

// Panel Pengurus Modular (Hub + Sub-halaman Terfokus)
Route::prefix('pengurus')->name('pengurus.')->group(function () {
    Route::view('/', 'admin')->name('index');
    Route::view('/keuangan', 'pengurus.keuangan')->name('keuangan');
    Route::view('/publikasi', 'pengurus.publikasi')->name('publikasi');
    Route::view('/pasar-tetangga', 'pengurus.pasar-tetangga')->name('pasar-tetangga');
    Route::view('/buku-induk', 'pengurus.buku-induk')->name('buku-induk');
    Route::view('/hak-akses', 'pengurus.hak-akses')->name('hak-akses');
    Route::view('/pemadupadanan-kontak', 'admin-wa-contacts')->name('wa-contacts');
});
Route::redirect('/admin', '/pengurus');

// Komunitas & Pasar Tetangga
Route::view('/komunitas', 'komunitas')->name('komunitas');
Route::view('/komunitas/pasar-tetangga', 'komunitas.pasar-tetangga')->name('komunitas.pasar-tetangga');

// Sharable Deep Link Produk / Jasa Pasar Tetangga (dengan Open Graph Preview WhatsApp)
Route::get('/komunitas/pasar-tetangga/p/{store}/{itemId}', function (int $store, string $itemId) {
    $storeModel = UmkmStore::query()
        ->with(['unit:id,code', 'owner:id,name', 'owner.profile:user_id,display_name,privacy_settings'])
        ->where('id', $store)
        ->where('is_suspended', false)
        ->first();

    $sharedItem = null;

    if ($storeModel) {
        $ownerProfile = $storeModel->owner?->profile;
        $privacy = $ownerProfile?->privacy_settings ?? [];
        $showFullName = (bool) ($privacy['show_full_name'] ?? false);
        $publicOwnerName = $ownerProfile?->display_name
            ?: ($showFullName ? $storeModel->owner?->name : explode(' ', trim((string) $storeModel->owner?->name))[0]);

        $catalog = is_array($storeModel->catalog_items) ? $storeModel->catalog_items : [];
        $foundProduct = null;
        $foundKey = $itemId;

        if ($itemId !== 'store') {
            foreach ($catalog as $idx => $prod) {
                $currentKey = (string) ($prod['id'] ?? $idx);
                if ($currentKey === $itemId) {
                    $foundProduct = $prod;
                    $foundKey = $currentKey;
                    break;
                }
            }
        }

        if ($foundProduct) {
            $priceNum = (float) ($foundProduct['price'] ?? 0);
            $sharedItem = [
                'id' => "live-{$storeModel->id}-{$foundKey}",
                'storeId' => $storeModel->id,
                'itemKey' => $foundKey,
                'name' => $foundProduct['name'] ?? $storeModel->store_name,
                'category' => $foundProduct['category'] ?? $storeModel->category ?? 'lainnya',
                'badgeType' => $foundProduct['badge_type'] ?? ($storeModel->status === 'open_po' ? 'po' : 'ready'),
                'numericPrice' => $priceNum,
                'unitNote' => $foundProduct['unit_note'] ?? '',
                'unitCode' => $storeModel->unit?->code ?? 'PPMT',
                'storeName' => $storeModel->store_name,
                'ownerName' => $publicOwnerName ?: 'Warga PPMT',
                'storeStatus' => $storeModel->status === 'closed' ? 'closed' : 'open',
                'operatingHours' => $storeModel->operating_hours ?? '',
                'isFeatured' => (bool) $storeModel->is_currently_featured,
                'isAvailable' => ($foundProduct['is_available'] ?? true) !== false,
                'deliveryTag' => $foundProduct['delivery_tag'] ?? '',
                'imageUrl' => $foundProduct['image_url'] ?? '',
                'description' => $foundProduct['description'] ?? $storeModel->description ?? '',
            ];
        } elseif ($itemId === 'store' || empty($catalog)) {
            $catCode = $storeModel->category ?? 'lainnya';
            $sharedItem = [
                'id' => "store-{$storeModel->id}",
                'storeId' => $storeModel->id,
                'itemKey' => 'store',
                'name' => $storeModel->store_name,
                'category' => $catCode,
                'badgeType' => $storeModel->status === 'open_po' ? 'po' : ($catCode === 'jasa' ? 'service' : ($catCode === 'pinjam_alat' ? 'loan' : 'ready')),
                'numericPrice' => 0,
                'unitNote' => 'Lapak Usaha Warga',
                'unitCode' => $storeModel->unit?->code ?? 'PPMT',
                'storeName' => $storeModel->store_name,
                'ownerName' => $publicOwnerName ?: 'Warga PPMT',
                'storeStatus' => $storeModel->status === 'closed' ? 'closed' : 'open',
                'operatingHours' => $storeModel->operating_hours ?? '',
                'isFeatured' => (bool) $storeModel->is_currently_featured,
                'isAvailable' => true,
                'deliveryTag' => '',
                'imageUrl' => '',
                'description' => $storeModel->description ?? '',
            ];
        }
    }

    return view('komunitas.pasar-tetangga', [
        'sharedItem' => $sharedItem,
    ]);
})->where([
    'store' => '[0-9]+',
    'itemId' => '[A-Za-z0-9\-_]+',
])->name('komunitas.pasar-tetangga.item');

Route::view('/peta-kavling', 'siteplan')->name('siteplan');

// Permalink Berita Warga: /berita/2026/11/17/judul-berita-slug
Route::get('/berita/{year}/{month}/{day}/{slug}', function ($year, $month, $day, $slug) {
    return view('news.show', compact('year', 'month', 'day', 'slug'));
})->where([
    'year'  => '[0-9]{4}',
    'month' => '[0-9]{2}',
    'day'   => '[0-9]{2}',
    'slug'  => '[a-z0-9\-]+'
])->name('news.show');
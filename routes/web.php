<?php

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
});

// Panel Pengurus Modular (Hub + Sub-halaman Terfokus)
Route::prefix('pengurus')->name('pengurus.')->group(function () {
    Route::view('/', 'admin')->name('index');
    Route::view('/keuangan', 'pengurus.keuangan')->name('keuangan');
    Route::view('/publikasi', 'pengurus.publikasi')->name('publikasi');
    Route::view('/buku-induk', 'pengurus.buku-induk')->name('buku-induk');
    Route::view('/pemadupadanan-kontak', 'admin-wa-contacts')->name('wa-contacts');
});
Route::redirect('/admin', '/pengurus');

// Komunitas & Pasar Tetangga
Route::view('/komunitas', 'komunitas')->name('komunitas');
Route::view('/komunitas/pasar-tetangga', 'komunitas.pasar-tetangga')->name('komunitas.pasar-tetangga');

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
<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');
Route::view('/register', 'register')->name('register');
Route::view('/dashboard', 'dashboard')->name('dashboard');
Route::view('/pengaturan', 'pengaturan')->name('pengaturan');
Route::view('/komunitas', 'komunitas')->name('komunitas');
Route::view('/peta-kavling', 'siteplan')->name('siteplan');
Route::view('/pengurus', 'admin')->name('pengurus');
Route::view('/pengurus/pemadupadanan-kontak', 'admin-wa-contacts')->name('pengurus.wa-contacts');
Route::redirect('/admin', '/pengurus');

// Permalink Berita Warga: /berita/2026/11/17/judul-berita-slug
Route::get('/berita/{year}/{month}/{day}/{slug}', function ($year, $month, $day, $slug) {
    return view('news.show', compact('year', 'month', 'day', 'slug'));
})->where([
    'year'  => '[0-9]{4}',
    'month' => '[0-9]{2}',
    'day'   => '[0-9]{2}',
    'slug'  => '[a-z0-9\-]+'
])->name('news.show');
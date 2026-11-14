<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');
Route::view('/dashboard', 'dashboard')->name('dashboard');
Route::view('/komunitas', 'komunitas')->name('komunitas');
Route::view('/admin', 'admin')->name('admin');
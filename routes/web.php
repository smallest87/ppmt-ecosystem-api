<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');
Route::view('/dashboard', 'dashboard')->name('dashboard');
Route::view('/pengaturan', 'pengaturan')->name('pengaturan');
Route::view('/komunitas', 'komunitas')->name('komunitas');
Route::view('/pengurus', 'admin')->name('pengurus');
Route::redirect('/admin', '/pengurus');
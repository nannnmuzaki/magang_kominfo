<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
->middleware(['auth', 'verified'])
->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('dashboard', 'dashboard')->name('dashboard');
    
    Volt::route('pengajuan', 'pengajuan.index')->name('pengajuan.index');
    Volt::route('pengajuan/create', 'pengajuan.create')->name('pengajuan.create');
    Volt::route('pengajuan/{pengajuan}/edit', 'pengajuan.edit')->name('pengajuan.edit');


    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
});

Route::middleware(['auth', 'admin'])->group(function () {
    Volt::route('admin/dashboard', 'admin.dashboard')->name('admin.dashboard');

    Volt::route('admin/pengajuan', 'admin.pengajuan.index')->name('admin.pengajuan.index');
    Volt::route('admin/pengajuan/create', 'admin.pengajuan.create')->name('admin.pengajuan.create');
    Volt::route('admin/pengajuan/{pengajuan}/edit', 'admin.pengajuan.edit')->name('admin.pengajuan.edit');
    
    Volt::route('admin/bidang', 'admin.bidang.index')->name('admin.bidang.index');
});


require __DIR__.'/auth.php';

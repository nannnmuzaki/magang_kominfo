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
    
    Volt::route('admin/pengajuan', 'admin.pengajuan.index')->name('admin.pengajuan.index');
    Volt::route('admin/pengajuan/create', 'admin.pengajuan.create')->name('admin.pengajuan.create');
    Volt::route('admin/pengajuan/{pengajuan}/edit', 'admin.pengajuan.edit')->name('admin.pengajuan.edit');
    
    Volt::route('admin/bidang', 'admin.bidang.index')->name('admin.bidang.index');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
});


require __DIR__.'/auth.php';

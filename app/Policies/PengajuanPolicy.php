<?php

namespace App\Policies;

use App\Models\Pengajuan;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class PengajuanPolicy
{
    /**
     * Perform pre-authorization checks.
     * Metode ini akan dijalankan SEBELUM metode lain di policy ini.
     */
    public function before(User $user, string $ability): bool|null
    {
        // Gunakan Gate 'is-admin' yang sudah Anda buat.
        // Jika Gate ini mengembalikan true, user langsung diizinkan dan
        // metode lain (seperti view, update) tidak akan dijalankan.
        if ($user->role === 'admin') {
            return true;
        }

        // Jika bukan admin, kembalikan null agar Laravel melanjutkan
        // pengecekan ke metode spesifik (view, update, dll).
        return null;
    }

    /**
     * Tentukan apakah pengguna dapat melihat pengajuan ini.
     * Metode ini hanya akan dijalankan jika before() mengembalikan null.
     */
    public function view(User $user, Pengajuan $pengajuan): bool
    {
        // Izinkan jika ID pengguna sama dengan user_id di pengajuan.
        return $user->id === $pengajuan->user_id;
    }

    /**
     * Tentukan apakah pengguna dapat mengedit pengajuan ini.
     */
    public function update(User $user, Pengajuan $pengajuan): bool
    {
        // Logikanya sama: izinkan jika dia pemiliknya.
        return $user->id === $pengajuan->user_id;
    }

    /**
     * Tentukan apakah pengguna dapat menghapus pengajuan ini.
     */
    public function delete(User $user, Pengajuan $pengajuan): bool
    {
        // Logikanya sama: izinkan jika dia pemiliknya.
        return $user->id === $pengajuan->user_id;
    }
}
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        // Hapus data user yang ada untuk menghindari duplikat saat seeding ulang
        // User::truncate(); // Opsional: gunakan jika Anda ingin membersihkan tabel setiap kali seed

        // 1. Buat satu pengguna statis untuk testing/development
        User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'role' => 'admin', 
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);

        User::create([
            'name' => 'User',
            'email' => 'user@example.com',
            'role' => 'user', 
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);

        // 2. Buat 10 pengguna dummy tambahan menggunakan factory
        // Pastikan Anda sudah memiliki UserFactory yang sesuai.
        // Jika Anda menginstal Laravel, factory ini sudah ada secara default.
        User::factory()->count(10)->create();
    }
}
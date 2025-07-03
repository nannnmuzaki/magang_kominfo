<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Bidang;
use Illuminate\Support\Facades\DB;

class BidangSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        // Bersihkan tabel bidang sebelum diisi untuk menghindari duplikasi
        // DB::table('bidang')->truncate(); // Gunakan jika Anda ingin tabel bersih setiap kali seeding

        // Daftar bidang yang akan di-seed
        $bidang = [
            ['nama' => 'Aplikasi Informatika', 'kuota' => 30],
            ['nama' => 'Informasi dan Komunikasi Publik', 'kuota' => 10],
            ['nama' => 'Sumber Daya dan Perangkat Pos dan Informatika', 'kuota' => 20],
            ['nama' => 'Penyelenggara Pos dan Informatika', 'kuota' => 15],
        ];

        // Looping dan masukkan setiap bidang ke dalam database
        foreach ($bidang as $item) {
            Bidang::create($item);
        }
    }
}
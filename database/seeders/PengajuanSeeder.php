<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Pengajuan;
use App\Models\User;
use App\Models\Bidang;
use Faker\Factory as Faker;

class PengajuanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        // Inisialisasi Faker
        $faker = Faker::create('id_ID');

        // Ambil semua ID dari tabel User dan Bidang yang ada.
        // Pastikan Anda sudah menjalankan seeder untuk User dan Bidang sebelumnya.
        $userIds = User::pluck('id')->all();
        $bidangIds = Bidang::pluck('id')->all();

        // Jika tidak ada user atau bidang, hentikan seeder.
        if (empty($userIds) || empty($bidangIds)) {
            $this->command->warn('Tidak dapat menjalankan PengajuanSeeder karena tidak ada data User atau Bidang. Silakan jalankan seeder yang relevan terlebih dahulu.');
            return;
        }

        // Daftar status yang mungkin
        $statuses = ['review', 'diterima', 'ditolak', 'berlangsung', 'selesai'];

        // Buat 50 data pengajuan dummy
        for ($i = 0; $i < 50; $i++) {
            // Tentukan tanggal mulai dan tanggal selesai
            $tanggalMulai = $faker->dateTimeBetween('+1 month', '+2 months');
            $tanggalSelesai = (clone $tanggalMulai)->modify('+3 months');

            Pengajuan::create([
                'user_id' => $faker->randomElement($userIds),
                'nama' => $faker->name,
                'nim_nis' => $faker->unique()->numerify('G1A021###'),
                'no_hp' => $faker->unique()->phoneNumber,
                'email' => $faker->unique()->safeEmail,
                'sekolah_universitas' => $faker->randomElement(['Universitas Jenderal Soedirman', 'Institut Teknologi Telkom Purwokerto', 'Universitas Muhammadiyah Purwokerto']),
                'jurusan_prodi' => $faker->randomElement(['Informatika', 'Sistem Informasi', 'Teknik Elektro', 'Manajemen']),
                'bidang_id' => $faker->randomElement($bidangIds),
                'tanggal_mulai' => $tanggalMulai,
                'tanggal_selesai' => $tanggalSelesai,
                'no_surat_pengantar' => '123/UNIV/X/' . (2025 - $i),
                'tanggal_surat_pengantar' => $faker->dateTimeBetween('-1 month', 'now'),
                'surat_pengantar_path' => 'seeders/files/surat_pengantar.pdf',
                'cv_path' => 'seeders/files/cv.pdf',
                'status' => $faker->randomElement($statuses),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Pengajuan;
use App\Models\User;
use App\Models\Bidang;
use Faker\Factory as Faker;
use Carbon\Carbon;

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
            // PERUBAHAN: Buat tanggal pengajuan (created_at) secara acak dalam 3 tahun terakhir.
            $tanggalPengajuan = $faker->dateTimeBetween('-3 years', 'now');

            // Buat tanggal surat pengantar sebelum tanggal pengajuan
            $tanggalSuratPengantar = (clone Carbon::instance($tanggalPengajuan))->subDays($faker->numberBetween(7, 30));

            // Buat tanggal mulai magang setelah tanggal pengajuan
            $tanggalMulai = (clone Carbon::instance($tanggalPengajuan))->addDays($faker->numberBetween(14, 60));
            $tanggalSelesai = (clone $tanggalMulai)->addMonths(3);

            Pengajuan::create([
                'user_id' => $faker->randomElement($userIds),
                'nama' => $faker->name,
                'nim_nis' => $faker->unique()->numerify('G1A0#####'),
                'no_hp' => $faker->unique()->phoneNumber,
                'email' => $faker->unique()->safeEmail,
                'sekolah_universitas' => $faker->randomElement(['Universitas Jenderal Soedirman', 'Institut Teknologi Telkom Purwokerto', 'Universitas Muhammadiyah Purwokerto']),
                'jurusan_prodi' => $faker->randomElement(['Informatika', 'Sistem Informasi', 'Teknik Elektro', 'Manajemen']),
                'bidang_id' => $faker->randomElement($bidangIds),
                'tanggal_mulai' => $tanggalMulai,
                'tanggal_selesai' => $tanggalSelesai,
                'no_surat_pengantar' => '123/UNIV/X/' . $tanggalSuratPengantar->format('Y'),
                'tanggal_surat_pengantar' => $tanggalSuratPengantar,
                'surat_pengantar_path' => 'seeders/files/surat_pengantar.pdf',
                'cv_path' => 'seeders/files/cv.pdf',
                'status' => $faker->randomElement($statuses),
                'created_at' => $tanggalPengajuan,
                'updated_at' => $tanggalPengajuan,
            ]);
        }
    }
}

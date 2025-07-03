<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('pengajuan', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')
                  ->constrained('users') 
                  ->onUpdate('cascade')
                  ->onDelete('cascade');
            $table->string('nama');
            $table->string('nim_nis')->unique(); 
            $table->string('no_hp')->unique();
            $table->string('email')->unique();
            $table->string('sekolah_universitas');
            $table->string('jurusan_prodi');

            $table->foreignUuid('bidang_id')
                  ->constrained('bidang') 
                  ->onUpdate('cascade')
                  ->onDelete('cascade'); 

            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');

            $table->string('no_surat_pengantar');
            $table->date('tanggal_surat_pengantar');

            $table->string('surat_pengantar_path');
            $table->string('cv_path');

            $table->enum('status', ['review', 'diterima', 'ditolak', 'berlangsung', 'selesai'])->default('review');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('pengajuan');
    }
};
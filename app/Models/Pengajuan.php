<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pengajuan extends Model
{
    use HasFactory, HasUuids;

    /**
     *
     * @var string
     */
    protected $table = 'pengajuan';

    /**
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'nama',
        'nim_nis',
        'no_hp',
        'email',
        'sekolah_universitas',
        'jurusan_prodi',
        'bidang_id',
        'tanggal_mulai',
        'tanggal_selesai',
        'no_surat_pengantar',
        'tanggal_surat_pengantar',
        'surat_pengantar_path',
        'cv_path',
        'status',
    ];

    /**
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
        'tanggal_surat_pengantar' => 'date',
        'status' => 'string',
    ];

    public function bidang(): BelongsTo
    {
        return $this->belongsTo(Bidang::class, 'bidang_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
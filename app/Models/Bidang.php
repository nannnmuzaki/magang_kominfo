<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Bidang extends Model
{
    use HasFactory, HasUuids;

    /**
     *
     * @var string
     */
    protected $table = 'bidang';

    /**
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nama',
        'kuota',
    ];

    /**
     *
     * @var array<string, string>
     */
    protected $casts = [
        'kuota' => 'integer',
    ];

    /**
     * 
     * Membuat atribut virtual 'sisa_kuota'.
     */
    protected function sisaKuota(): Attribute
    {
        return Attribute::make(
            get: function ($value, $attributes) {
                // Periksa apakah 'pengajuan_diterima_count' sudah di-load oleh withCount untuk mencegah error.
                if (!isset($attributes['pengajuan_diterima_berlangsung_count'])) {
                    return 'N/A';
                }

                // Hitung sisa kuota: kuota total - jumlah pengajuan yang diterima/berlangsung.
                return $attributes['kuota'] - $attributes['pengajuan_diterima_berlangsung_count'];
            }
        );
    }

    public function pengajuan(): HasMany
    {
        return $this->hasMany(Pengajuan::class);
    }
}
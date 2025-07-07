<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function pengajuan(): HasMany
    {
        return $this->hasMany(Pengajuan::class);
    }
}
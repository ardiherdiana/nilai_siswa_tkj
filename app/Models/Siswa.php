<?php

namespace App\Models;

use App\Models\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['kelasId', 'nomor', 'nama'])]
class Siswa extends Model
{
    use HasFactory, HasUlid;

    protected $table = 'siswa';

    public function kelas(): BelongsTo
    {
        return $this->belongsTo(Kelas::class, 'kelasId');
    }

    public function nilai(): HasOne
    {
        return $this->hasOne(Nilai::class, 'siswaId');
    }

    /** @return array<string, mixed> */
    public function toProps(): array
    {
        return [
            'id' => $this->id,
            'nomor' => $this->nomor,
            'nama' => $this->nama,
            'nilai' => $this->nilai?->toProps() ?? Nilai::kosong(),
        ];
    }
}

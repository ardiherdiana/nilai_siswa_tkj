<?php

namespace App\Models;

use App\Models\Concerns\HasUlid;
use App\Support\SupervisiInstrumen;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['userId', 'guruDinilai', 'pemberiUmpanBalik', 'tanggal', 'butir', 'refleksi'])]
class Supervisi extends Model
{
    use HasFactory, HasUlid;

    protected $table = 'supervisi';

    protected function casts(): array
    {
        return [
            'tanggal' => 'date:Y-m-d',
            'butir' => 'array',
            'refleksi' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'userId');
    }

    /**
     * Bentuk lengkap untuk klien: selalu 15 butir dan 3 jawaban refleksi, walau belum diisi.
     *
     * @return array<string, mixed>
     */
    public function toProps(): array
    {
        return [
            'id' => $this->id,
            'guruDinilai' => $this->guruDinilai,
            'pemberiUmpanBalik' => $this->pemberiUmpanBalik,
            'tanggal' => $this->tanggal->format('Y-m-d'),
            'butir' => SupervisiInstrumen::lengkapiButir($this->butir ?? []),
            'refleksi' => SupervisiInstrumen::lengkapiRefleksi($this->refleksi ?? []),
        ];
    }
}

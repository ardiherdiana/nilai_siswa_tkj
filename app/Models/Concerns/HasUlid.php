<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Konvensi model Nilai Siswa TKJ & Supervisi Guru: primary key ULID string dan timestamp camelCase (createdAt/updatedAt).
 */
trait HasUlid
{
    public function initializeHasUlid(): void
    {
        $this->incrementing = false;
        $this->keyType = 'string';
    }

    public static function bootHasUlid(): void
    {
        static::creating(function ($model) {
            $model->id ??= (string) Str::ulid();
        });
    }

    public function getCreatedAtColumn(): string
    {
        return 'createdAt';
    }

    public function getUpdatedAtColumn(): string
    {
        return 'updatedAt';
    }
}

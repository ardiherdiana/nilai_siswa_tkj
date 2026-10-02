<?php

namespace App\Models;

use App\Models\Concerns\HasUlid;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * Akun Nilai Siswa TKJ & Supervisi Guru: login memakai username + kata sandi, dibuat dan
 * dinonaktifkan admin lewat halaman Akun. Kelas, siswa, nilai, dan supervisi selalu milik satu akun.
 */
#[Fillable(['username', 'name', 'password', 'role', 'aktif'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    use HasFactory, HasUlid;

    public const ADMIN = 'ADMIN';

    public const GURU = 'GURU';

    /** Aturan username: dipakai halaman Akun dan perintah `nilai:buat-admin` (keunikan diperiksa di pemanggil). */
    public const ATURAN_USERNAME = ['required', 'string', 'min:3', 'max:50', 'regex:/^[A-Za-z0-9._-]+$/'];

    public const PESAN_USERNAME = 'Username hanya boleh huruf, angka, titik, garis bawah, dan garis hubung';

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'aktif' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ADMIN;
    }

    public function kelas(): HasMany
    {
        return $this->hasMany(Kelas::class, 'userId');
    }

    public function supervisi(): HasMany
    {
        return $this->hasMany(Supervisi::class, 'userId');
    }
}

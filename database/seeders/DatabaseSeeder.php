<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    /**
     * Akun contoh untuk pengembangan lokal dengan sandi bawaan yang mudah ditebak. Menolak berjalan di produksi:
     * admin pertama di produksi dibuat lewat `php artisan nilai:buat-admin`, akun lain lewat halaman Akun.
     */
    public function run(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('DatabaseSeeder hanya untuk pengembangan lokal.');
        }

        User::firstOrCreate(['username' => 'admin'], ['name' => 'Admin', 'password' => 'admin123', 'role' => User::ADMIN]);
        User::firstOrCreate(['username' => 'guru'], ['name' => 'Guru Contoh', 'password' => 'guru123', 'role' => User::GURU]);

        $this->command->info('Seed selesai.');
        $this->command->info('Admin -> username: admin | password: admin123');
        $this->command->info('Guru  -> username: guru  | password: guru123');
    }
}

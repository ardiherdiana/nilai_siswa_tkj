<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class BuatAdmin extends Command
{
    protected $signature = 'nilai:buat-admin {username : username untuk masuk} {--name= : nama tampilan (bawaan: sama dengan username)} {--reset : atur ulang akun yang sudah ada: sandi baru, jadi ADMIN, dan aktif}';

    protected $description = 'Buat akun admin (admin pertama di produksi, atau pemulihan bila semua admin lupa sandi). Sandi ditanyakan tanpa tampil di layar';

    public function handle(): int
    {
        $username = trim((string) $this->argument('username'));

        $galat = Validator::make(['username' => $username], ['username' => User::ATURAN_USERNAME], ['username.regex' => User::PESAN_USERNAME])->errors()->first();
        if ($galat !== '') {
            $this->error($galat);

            return self::FAILURE;
        }

        $ada = User::where('username', $username)->first();
        if ($ada && ! $this->option('reset')) {
            $this->error("Username \"{$username}\" sudah ada. Pakai --reset untuk mengatur ulang sandinya dan menjadikannya admin aktif.");

            return self::FAILURE;
        }
        if (! $ada && $this->option('reset')) {
            $this->error("Username \"{$username}\" tidak ditemukan, jadi tidak ada yang bisa diatur ulang.");

            return self::FAILURE;
        }

        $sandi = (string) $this->secret('Password (minimal 8 karakter)');
        if (mb_strlen($sandi) < 8 || mb_strlen($sandi) > 100) {
            $this->error('Password harus 8 sampai 100 karakter.');

            return self::FAILURE;
        }
        if ($sandi !== (string) $this->secret('Ulangi password')) {
            $this->error('Kedua password tidak sama.');

            return self::FAILURE;
        }

        if ($ada) {
            $ada->forceFill(['password' => $sandi, 'role' => User::ADMIN, 'aktif' => true])->save();
            $this->info("Akun \"{$username}\" diatur ulang: admin aktif dengan password baru.");

            return self::SUCCESS;
        }

        User::create([
            'username' => $username,
            'name' => trim((string) $this->option('name')) ?: $username,
            'password' => $sandi,
            'role' => User::ADMIN,
        ]);
        $this->info("Admin \"{$username}\" dibuat. Masuk lewat halaman login, lalu buat akun guru di menu Akun.");

        return self::SUCCESS;
    }
}

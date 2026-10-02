<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BuatAdminTest extends TestCase
{
    public function test_membuat_admin_baru_yang_bisa_masuk(): void
    {
        $this->artisan('nilai:buat-admin rio.admin --name="Rio Admin"')
            ->expectsQuestion('Password (minimal 8 karakter)', 'rahasia123')
            ->expectsQuestion('Ulangi password', 'rahasia123')
            ->assertSuccessful();

        $user = User::where('username', 'rio.admin')->sole();
        $this->assertSame(['Rio Admin', 'ADMIN', true], [$user->name, $user->role, $user->aktif]);
        $this->assertTrue(Hash::check('rahasia123', $user->password));
        $this->post('/login', ['username' => 'rio.admin', 'password' => 'rahasia123'])->assertRedirect('/dashboard');
    }

    public function test_nama_bawaan_sama_dengan_username(): void
    {
        $this->artisan('nilai:buat-admin kepala')
            ->expectsQuestion('Password (minimal 8 karakter)', 'rahasia123')
            ->expectsQuestion('Ulangi password', 'rahasia123')
            ->assertSuccessful();

        $this->assertSame('kepala', User::where('username', 'kepala')->value('name'));
    }

    public function test_menolak_username_yang_sudah_ada_tanpa_reset(): void
    {
        User::factory()->create(['username' => 'rio']);

        $this->artisan('nilai:buat-admin rio')->expectsOutputToContain('sudah ada')->assertFailed();

        $this->assertSame('GURU', User::where('username', 'rio')->value('role'));
    }

    public function test_reset_mengatur_ulang_sandi_menjadikan_admin_dan_mengaktifkan(): void
    {
        User::factory()->nonaktif()->create(['username' => 'rio', 'password' => 'lama12345']);

        $this->artisan('nilai:buat-admin rio --reset')
            ->expectsQuestion('Password (minimal 8 karakter)', 'baru12345')
            ->expectsQuestion('Ulangi password', 'baru12345')
            ->assertSuccessful();

        $user = User::where('username', 'rio')->sole();
        $this->assertSame(['ADMIN', true], [$user->role, $user->aktif]);
        $this->assertTrue(Hash::check('baru12345', $user->password));
    }

    public function test_reset_username_yang_tidak_ada_ditolak(): void
    {
        $this->artisan('nilai:buat-admin hantu --reset')->expectsOutputToContain('tidak ditemukan')->assertFailed();
    }

    public function test_menolak_username_tidak_valid_password_pendek_dan_konfirmasi_beda(): void
    {
        $this->artisan('nilai:buat-admin "a b"')->expectsOutputToContain('Username hanya boleh')->assertFailed();

        $this->artisan('nilai:buat-admin valid')
            ->expectsQuestion('Password (minimal 8 karakter)', 'pendek')
            ->expectsOutputToContain('8 sampai 100')
            ->assertFailed();

        $this->artisan('nilai:buat-admin valid')
            ->expectsQuestion('Password (minimal 8 karakter)', 'rahasia123')
            ->expectsQuestion('Ulangi password', 'rahasia124')
            ->expectsOutputToContain('tidak sama')
            ->assertFailed();

        $this->assertSame(0, User::count());
    }
}

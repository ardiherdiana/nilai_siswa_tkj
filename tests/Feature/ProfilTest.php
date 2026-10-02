<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class ProfilTest extends TestCase
{
    public function test_halaman_profil_tampil_untuk_guru_dan_admin(): void
    {
        $this->actingAsGuru();
        $this->get('/profil')->assertOk()->assertInertia(fn (AssertableInertia $page) => $page->component('profil-page'));

        $this->actingAsAdmin();
        $this->get('/profil')->assertOk();
    }

    public function test_props_bersama_tidak_memuat_hash_sandi(): void
    {
        $user = $this->actingAsGuru(['name' => 'Rio']);

        $this->get('/profil')->assertInertia(fn (AssertableInertia $page) => $page
            ->where('auth.user.username', $user->username)
            ->where('auth.user.name', 'Rio')
            ->where('auth.user.role', 'GURU')
            ->missing('auth.user.password'));
    }

    public function test_ganti_password_dengan_password_lama_yang_benar(): void
    {
        $user = $this->actingAsGuru(['password' => 'lama12345']);

        $this->patch('/profil/password', ['oldPassword' => 'lama12345', 'newPassword' => 'baru12345'])->assertRedirect();

        $this->assertTrue(Hash::check('baru12345', $user->fresh()->password));
    }

    public function test_ganti_password_ditolak_bila_password_lama_salah_atau_baru_lemah(): void
    {
        $user = $this->actingAsGuru(['password' => 'lama12345']);

        $this->patch('/profil/password', ['oldPassword' => 'salah', 'newPassword' => 'baru12345'])->assertSessionHasErrors(['oldPassword' => 'Password lama salah']);
        $this->patch('/profil/password', ['oldPassword' => 'lama12345', 'newPassword' => 'pendek'])->assertSessionHasErrors('newPassword');
        $this->patch('/profil/password', ['oldPassword' => 'lama12345', 'newPassword' => 'lama12345'])->assertSessionHasErrors('newPassword');

        $this->assertTrue(Hash::check('lama12345', $user->fresh()->password));
    }
}

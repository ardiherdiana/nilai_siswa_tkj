<?php

namespace Tests\Feature\Akun;

use App\Models\Kelas;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class AkunTest extends TestCase
{
    public function test_guru_tidak_bisa_membuka_atau_mengubah_akun(): void
    {
        $lain = User::factory()->create();
        $this->actingAsGuru();

        $this->get('/akun')->assertForbidden();
        $this->post('/akun', ['name' => 'X', 'username' => 'xxx', 'password' => 'password1', 'role' => 'GURU'])->assertForbidden();
        $this->patch("/akun/{$lain->id}", ['name' => 'X', 'role' => 'ADMIN', 'aktif' => true])->assertForbidden();
        $this->delete("/akun/{$lain->id}")->assertForbidden();
        $this->assertSame('GURU', $lain->fresh()->role);
    }

    public function test_admin_melihat_daftar_akun_tanpa_hash_sandi(): void
    {
        $this->actingAsAdmin(['name' => 'Admin Utama']);
        User::factory()->create(['name' => 'Budi']);

        $this->get('/akun')->assertOk()->assertInertia(fn (AssertableInertia $page) => $page
            ->component('akun/index-page')
            ->has('akunList', 2)
            ->where('akunList.0.name', 'Admin Utama')
            ->missing('akunList.0.password'));
    }

    public function test_admin_menambah_akun_yang_langsung_bisa_masuk(): void
    {
        $this->actingAsAdmin();

        $this->post('/akun', ['name' => '  Rio Falentino ', 'username' => 'rio.falentino', 'password' => 'rahasia123', 'role' => 'GURU'])->assertRedirect();

        $user = User::where('username', 'rio.falentino')->sole();
        $this->assertSame('Rio Falentino', $user->name);
        $this->assertSame('GURU', $user->role);
        $this->assertTrue($user->aktif);
        $this->assertTrue(Hash::check('rahasia123', $user->password));
        $this->assertNotSame('rahasia123', $user->password);
    }

    public function test_validasi_akun_baru(): void
    {
        $this->actingAsAdmin();
        User::factory()->create(['username' => 'sudahada']);

        $this->post('/akun', [])->assertSessionHasErrors(['name', 'username', 'password', 'role']);
        $this->post('/akun', ['name' => 'X', 'username' => 'SudahAda', 'password' => 'password1', 'role' => 'GURU'])->assertSessionHasErrors('username');
        $this->post('/akun', ['name' => 'X', 'username' => 'a b', 'password' => 'password1', 'role' => 'GURU'])->assertSessionHasErrors('username');
        $this->post('/akun', ['name' => 'X', 'username' => 'ab', 'password' => 'password1', 'role' => 'GURU'])->assertSessionHasErrors('username');
        $this->post('/akun', ['name' => 'X', 'username' => 'valid', 'password' => 'pendek', 'role' => 'GURU'])->assertSessionHasErrors('password');
        $this->post('/akun', ['name' => 'X', 'username' => 'valid', 'password' => 'password1', 'role' => 'KEPSEK'])->assertSessionHasErrors('role');
    }

    public function test_admin_mengubah_nama_role_status_dan_sandi(): void
    {
        $this->actingAsAdmin();
        $guru = User::factory()->create(['name' => 'Lama', 'password' => 'lama12345']);

        $this->patch("/akun/{$guru->id}", ['name' => 'Baru', 'role' => 'ADMIN', 'aktif' => false, 'password' => 'baru12345'])->assertRedirect();

        $guru->refresh();
        $this->assertSame(['Baru', 'ADMIN', false], [$guru->name, $guru->role, $guru->aktif]);
        $this->assertTrue(Hash::check('baru12345', $guru->password));

        // Sandi kosong = tidak diubah.
        $this->patch("/akun/{$guru->id}", ['name' => 'Baru', 'role' => 'ADMIN', 'aktif' => true])->assertRedirect();
        $this->assertTrue(Hash::check('baru12345', $guru->fresh()->password));
    }

    public function test_admin_terakhir_tidak_bisa_diturunkan_atau_dinonaktifkan(): void
    {
        $admin = $this->actingAsAdmin();

        // Permintaan dari halaman (Inertia): galat abort() menjadi pesan form.
        $inertia = ['X-Inertia' => 'true'];
        $this->withHeaders($inertia)->patch("/akun/{$admin->id}", ['name' => $admin->name, 'role' => 'GURU', 'aktif' => true])->assertSessionHasErrors(['message' => 'Tidak bisa menurunkan atau menonaktifkan admin aktif terakhir']);
        $this->withHeaders($inertia)->patch("/akun/{$admin->id}", ['name' => $admin->name, 'role' => 'ADMIN', 'aktif' => false])->assertSessionHasErrors('message');

        $admin->refresh();
        $this->assertSame(['ADMIN', true], [$admin->role, $admin->aktif]);
    }

    public function test_admin_boleh_diturunkan_bila_ada_admin_aktif_lain(): void
    {
        $this->actingAsAdmin();
        $lain = User::factory()->admin()->create();

        $this->patch("/akun/{$lain->id}", ['name' => $lain->name, 'role' => 'GURU', 'aktif' => true])->assertSessionHasNoErrors();

        $this->assertSame('GURU', $lain->fresh()->role);
    }

    public function test_admin_tidak_bisa_menghapus_akunnya_sendiri(): void
    {
        $admin = $this->actingAsAdmin();
        User::factory()->admin()->create();

        $this->withHeaders(['X-Inertia' => 'true'])->delete("/akun/{$admin->id}")->assertSessionHasErrors(['message' => 'Anda tidak bisa menghapus akun sendiri']);

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_hapus_akun_ikut_menghapus_data_nilai_miliknya(): void
    {
        $this->actingAsAdmin();
        $guru = User::factory()->create();
        Kelas::factory()->create(['userId' => $guru->id]);

        $this->delete("/akun/{$guru->id}")->assertRedirect();

        $this->assertDatabaseMissing('users', ['id' => $guru->id]);
        $this->assertSame(0, Kelas::count());
    }
}

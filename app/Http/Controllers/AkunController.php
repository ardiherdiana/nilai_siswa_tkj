<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/**
 * Kelola akun (khusus ADMIN). Nilai Siswa TKJ & Supervisi Guru tidak punya pendaftaran mandiri: akun guru dibuat dan dinonaktifkan di sini.
 */
class AkunController extends Controller
{
    private const ATRIBUT = ['name' => 'Nama', 'username' => 'Username', 'password' => 'Password', 'role' => 'Role'];

    public function index()
    {
        return Inertia::render('akun/index-page', [
            'akunList' => User::orderBy('name')->get()->map(fn (User $u) => [
                'id' => $u->id,
                'username' => $u->username,
                'name' => $u->name,
                'role' => $u->role,
                'aktif' => $u->aktif,
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'username' => [...User::ATURAN_USERNAME, 'unique:users,username'],
            'password' => ['required', 'string', 'min:8', 'max:100'],
            'role' => ['required', Rule::in([User::ADMIN, User::GURU])],
        ], [
            'username.regex' => User::PESAN_USERNAME,
        ], self::ATRIBUT);

        User::create(['name' => trim($data['name']), 'username' => $data['username'], 'password' => $data['password'], 'role' => $data['role']]);

        return back()->with('toast', ['type' => 'success', 'message' => 'Akun baru berhasil ditambahkan']);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'password' => ['nullable', 'string', 'min:8', 'max:100'],
            'role' => ['required', Rule::in([User::ADMIN, User::GURU])],
            'aktif' => ['required', 'boolean'],
        ], [], self::ATRIBUT);

        // Admin terakhir yang aktif tidak boleh diturunkan atau dinonaktifkan: tidak akan ada lagi yang bisa
        // mengelola akun.
        if ($user->isAdmin() && $user->aktif && ($data['role'] !== User::ADMIN || ! $data['aktif'])) {
            $this->pastikanAdaAdminLain($user, 'Tidak bisa menurunkan atau menonaktifkan admin aktif terakhir');
        }

        $user->fill(['name' => trim($data['name']), 'role' => $data['role'], 'aktif' => $data['aktif']]);
        if (! empty($data['password'])) {
            $user->password = $data['password'];
        }
        $user->save();

        return back()->with('toast', ['type' => 'success', 'message' => 'Akun berhasil diperbarui']);
    }

    public function destroy(Request $request, User $user)
    {
        // Pemanggil selalu admin aktif, jadi selama bukan menghapus dirinya sendiri, masih ada admin aktif yang tersisa.
        abort_if($user->is($request->user()), 403, 'Anda tidak bisa menghapus akun sendiri');

        $user->delete();

        return back()->with('toast', ['type' => 'success', 'message' => 'Akun berhasil dihapus']);
    }

    private function pastikanAdaAdminLain(User $user, string $pesan): void
    {
        $lain = User::where('role', User::ADMIN)->where('aktif', true)->whereKeyNot($user->id)->exists();
        abort_unless($lain, 403, $pesan);
    }
}

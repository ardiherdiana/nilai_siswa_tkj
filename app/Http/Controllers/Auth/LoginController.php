<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class LoginController extends Controller
{
    private const MAKS_PERCOBAAN = 5;

    public function create()
    {
        return Inertia::render('login-page');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:191'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        $username = trim($data['username']);
        $kunci = 'nilai-login:'.sha1(Str::lower($username).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($kunci, self::MAKS_PERCOBAAN)) {
            throw ValidationException::withMessages([
                'username' => 'Terlalu banyak percobaan masuk. Coba lagi dalam '.RateLimiter::availableIn($kunci).' detik',
            ]);
        }

        $user = User::where('username', $username)->first();

        if ($user === null) {
            // Tetap menghitung hash supaya waktu respons tidak membocorkan username mana yang terdaftar.
            Hash::make($data['password']);
            $cocok = false;
        } else {
            $cocok = Hash::check($data['password'], $user->password);
        }

        if (! $cocok) {
            RateLimiter::hit($kunci, 60);

            throw ValidationException::withMessages(['username' => 'Username atau password salah']);
        }

        if (! $user->aktif) {
            throw ValidationException::withMessages(['username' => 'Akun dinonaktifkan. Hubungi admin sekolah.']);
        }

        RateLimiter::clear($kunci);

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        Inertia::clearHistory();

        return redirect()->intended('/dashboard');
    }

    public function destroy(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Inertia::clearHistory();

        return redirect('/login');
    }
}

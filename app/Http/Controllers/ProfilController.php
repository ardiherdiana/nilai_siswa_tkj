<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class ProfilController extends Controller
{
    public function edit()
    {
        return Inertia::render('profil-page');
    }

    public function updatePassword(Request $request)
    {
        $data = $request->validate([
            'oldPassword' => ['required', 'string'],
            'newPassword' => ['required', 'string', 'min:8', 'max:100', 'different:oldPassword'],
        ], [
            'newPassword.different' => 'Password baru harus berbeda dari password lama',
        ], ['newPassword' => 'Password baru']);

        $user = $request->user();

        if (! Hash::check($data['oldPassword'], $user->password)) {
            return back()->withErrors(['oldPassword' => 'Password lama salah']);
        }

        $user->update(['password' => $data['newPassword']]);
        $request->session()->regenerate();

        return back()->with('toast', ['type' => 'success', 'message' => 'Password berhasil diganti']);
    }
}

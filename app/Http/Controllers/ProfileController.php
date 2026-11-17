<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(): View { return view('profile.edit'); }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'current_password' => ['required_with:password', 'current_password'],
            'password' => ['nullable', 'string', 'min:10', 'confirmed'],
        ], [
            'current_password.current_password' => 'Kata sandi saat ini tidak sesuai.',
            'password.min' => 'Kata sandi baru minimal 10 karakter.',
            'password.confirmed' => 'Konfirmasi kata sandi baru tidak sama.',
        ]);
        $user = $request->user();
        $user->name = $data['name'];
        if (filled($data['password'] ?? null)) $user->password = $data['password'];
        $user->save();

        return back()->with('success', 'Profil berhasil diperbarui.');
    }
}

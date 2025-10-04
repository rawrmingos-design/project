<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Berita;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function create()
    {
        return view('template.login', [
            'logoheader' => Berita::where('tipe', 'logoheader')->latest()->first(),
            'logofooter' => Berita::where('tipe', 'logofooter')->latest()->first(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'username' => 'required|string|max:255',
            'password' => 'required|string|min:6',
        ]);

        $credentials = $request->only('username', 'password');

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            // Cek apakah role pengguna adalah Admin
            if ($user->role === 'Admin') {
                Auth::logout();
                return redirect()->route('login')->withErrors(['error' => 'Username / password mismatch']);
            }

            // Cek apakah role pengguna adalah Member, Platinum, atau Gold
            if (in_array($user->role, ['Member', 'Platinum', 'Gold'])) {
                return redirect()->intended(route('home'));
            } else {
                Auth::logout();
                return redirect()->route('login')->withErrors(['error' => 'Username / password mismatch']);
            }
        }

        throw ValidationException::withMessages([
            'error' => ['Username / password mismatch'],
        ]);
    }

    public function destroy(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}

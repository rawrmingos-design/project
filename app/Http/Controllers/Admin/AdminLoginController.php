<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Berita;
use App\Models\WhitelistedIP;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class AdminLoginController extends Controller
{
    private function getWhitelistedIps()
    {
        return WhitelistedIP::pluck('ip_address')->toArray();
    }

    public function create()
    {
        return view('components.admin.adminlogin', [
            'logoheader' => Berita::where('tipe', 'logoheader')->latest()->first(),
            'logofooter' => Berita::where('tipe', 'logofooter')->latest()->first(),
        ]);
    }
    public function store(Request $request)
    {
        $request->validate([
            'username' => 'required|string|max:255',
            'password' => 'required|string|min:8',
        ]);

        // Try to get location, but don't fail if it doesn't work
        try {
            $response = Http::timeout(5)->get('https://ipinfo.io/' . $request->ip() . '?token=e879d202101b78');

            if ($response->successful()) {
                $data = $response->json();
                $country = $data['country'] ?? null;
            } else {
                // Location check failed, but continue anyway for localhost
                $country = null;
            }
        } catch (\Exception $e) {
            // If there's any network error, continue anyway for localhost
            $country = null;
        }

        $whitelistedIps = $this->getWhitelistedIps();

        if (!in_array($request->ip(), $whitelistedIps)) {
            return redirect()->route('adminlogin')->withErrors(['error' => 'Access denied from your IP: ' . $request->ip()]);
        }

        $credentials = $request->only('username', 'password');

        if (Auth::attempt($credentials)) {
            $user = Auth::user();

            if ($user->role === 'Admin') {
                return redirect()->intended(route('dashboard'));
            } else {
                Auth::logout();
                return redirect()->route('adminlogin')->withErrors(['error' => 'Username / password mismatch']);
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

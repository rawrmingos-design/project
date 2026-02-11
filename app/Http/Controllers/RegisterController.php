<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\Berita;
use Illuminate\Support\Str;

class RegisterController extends Controller
{
    public function create()
    {
        return view('template.register', [
            'logoheader' => Berita::where('tipe', 'logoheader')->latest()->first(),
            'logofooter' => Berita::where('tipe', 'logofooter')->latest()->first(),
        ]);
    }

    public function store(Request $request)
    {
        // Validasi input
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|max:255',
            'username' => 'required|string|min:3|unique:users,username|max:255',
            'password' => 'required|string|min:6|max:255',
            'email' => 'required',
            'no_wa' => 'required|numeric|unique:users,no_wa'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Hash password
        $hashedPassword = Hash::make($request->password);

        // Sanitasi nomor WhatsApp
        $no_wa = $request->no_wa;
        if ($no_wa[0] == '0') {
            $no_wa = '62' . substr($no_wa, 1);
        }

        // Generate Referral Code
        do {
            $referralCode = 'REF-' . Str::upper(Str::random(6));
        } while (User::where('referral_code', $referralCode)->exists());

        // Check Uplink (Referral)
        $uplink = null;
        if ($request->filled('kode_referral')) {
            $uplinkUser = User::where('referral_code', $request->kode_referral)->first();
            if ($uplinkUser) {
                $uplink = $uplinkUser->username; // Or ID, migration comment said username/ID. Let's stick to username for readability or ID for strictness. 
                // Migration comment said: "Stores uplink username or ID". 
                // Let's use username to match current pattern where relationships often use username (e.g. Pembelian).
            }
        }

        // Simpan data pengguna
        $user = new User();
        $user->name = htmlspecialchars($request->nama, ENT_QUOTES, 'UTF-8');
        $user->username = htmlspecialchars($request->username, ENT_QUOTES, 'UTF-8');
        $user->password = $hashedPassword;
        $user->email = htmlspecialchars($request->email, ENT_QUOTES, 'UTF-8');
        $user->api_key = Str::random(32);
        $user->balance = 0;
        $user->no_wa = htmlspecialchars($no_wa, ENT_QUOTES, 'UTF-8');
        $user->role = 'Member';
        $user->referral_code = $referralCode;
        $user->uplink = $uplink;
        $user->save();

        return redirect(route('login'))->with('success', 'Berhasil mendaftar silahkan login menggunakan akun anda.');
    }
}

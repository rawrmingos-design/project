<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\Berita;
use Illuminate\Support\Facades\DB;

class ForgotPasswordController extends Controller
{
    public function create()
    {
        return view('template.forgotpassword', [
            'logoheader' => Berita::where('tipe', 'logoheader')->latest()->first(),
            'logofooter' => Berita::where('tipe', 'logofooter')->latest()->first(),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'username' => 'required',
        ], [
            'username.required' => 'Harap isi kolom username!',
        ]);
        
        $user = User::where('username', $request->username)->first();

        if (!$user) return back()->with('error', 'Username tidak ditemukan');
        
        $forgot = 'WeJizy' . Str::random('6');
        
        $user->update([
            'password'  => Hash::make($forgot)
        ]);

$content = "Password baru anda *$forgot*";
$requestPesan = $this->msg($user->no_wa, $content);
        return back()->with('success', 'Kata sandi baru anda telah dikirim ke whatsapp Anda');        
        
    }
    public function msg($nomor, $msg)
    {

        $api = \DB::table('setting_webs')->where('id', 1)->first();
        $data = [
            'number'  => "$nomor",
            'message' => "$msg"
        ];

        $curl = curl_init();
        curl_setopt_array($curl, array(
                CURLOPT_URL => "https://api.fonnte.com/send",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => array(
                    'target' => $nomor,
                    'message' => $msg,
                    'countryCode' => '0'),
                CURLOPT_HTTPHEADER => array(
                    "Authorization: ".$api->wa_key,
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;
    }
    
}

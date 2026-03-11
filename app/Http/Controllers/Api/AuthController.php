<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $credentials = $request->only('username', 'password');

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Username / password mismatch'
            ], 401);
        }

        $user = Auth::user();

        // Block Admin from API Auth (as per original LoginController logic)
        if ($user->role === 'Admin') {
            Auth::logout();
            return response()->json([
                'success' => false,
                'message' => 'Username / password mismatch'
            ], 401);
        }

        // Generate Sanctum token
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'role' => $user->role,
                    'balance' => $user->balance,
                ],
                'access_token' => $token,
                'token_type' => 'Bearer',
            ]
        ]);
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|max:255',
            'username' => 'required|string|min:3|unique:users,username|max:255',
            'password' => 'required|string|min:6|max:255',
            'email' => 'required|email|unique:users,email',
            'no_wa' => 'required|numeric|unique:users,no_wa',
            'kode_referral' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // WhatsApp normalization
        $no_wa = $request->no_wa;
        if ($no_wa[0] == '0') {
            $no_wa = '62' . substr($no_wa, 1);
        }

        // Generate Referral Code
        do {
            $referralCode = 'REF-' . Str::upper(Str::random(6));
        } while (User::where('referral_code', $referralCode)->exists());

        // Process referral/uplink
        $uplink = null;
        $kodeReferral = $request->kode_referral;
        if ($kodeReferral) {
            $uplinkUser = User::where('referral_code', $kodeReferral)->first();
            if ($uplinkUser) {
                $uplink = $uplinkUser->username;
            }
        }

        $user = User::create([
            'name' => htmlspecialchars($request->nama, ENT_QUOTES, 'UTF-8'),
            'username' => htmlspecialchars($request->username, ENT_QUOTES, 'UTF-8'),
            'password' => Hash::make($request->password),
            'email' => htmlspecialchars($request->email, ENT_QUOTES, 'UTF-8'),
            'api_key' => Str::random(32),
            'balance' => 0,
            'no_wa' => htmlspecialchars($no_wa, ENT_QUOTES, 'UTF-8'),
            'role' => 'Member',
            'referral_code' => $referralCode,
            'uplink' => $uplink,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Registration successful',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'role' => $user->role,
                ],
                'access_token' => $token,
                'token_type' => 'Bearer',
            ]
        ], 201);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }

    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('username', $request->username)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Username not found'
            ], 404);
        }

        $newPassword = 'WeJizy' . Str::random(6);
        $user->update([
            'password' => Hash::make($newPassword)
        ]);

        $content = "Password baru anda *$newPassword*";
        $this->sendWhatsapp($user->no_wa, $content);

        return response()->json([
            'success' => true,
            'message' => 'New password has been sent to your WhatsApp'
        ]);
    }

    protected function sendWhatsapp($nomor, $msg)
    {
        $api = DB::table('setting_webs')->where('id', 1)->first();
        if (!$api || !$api->wa_key) {
            return null;
        }

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
                'countryCode' => '0'
            ),
            CURLOPT_HTTPHEADER => array(
                "Authorization: " . $api->wa_key,
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);
        return $response;
    }
}

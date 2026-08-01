<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\PasswordRecoveryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function __construct(
        private readonly PasswordRecoveryService $passwordRecoveryService,
    ) {
    }

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
                'errors' => $validator->errors(),
            ], 422);
        }

        $credentials = $request->only('username', 'password');

        if (! Auth::attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Username / password mismatch',
            ], 401);
        }

        $user = Auth::user();

        if ($user->role === 'Admin') {
            Auth::logout();

            return response()->json([
                'success' => false,
                'message' => 'Username / password mismatch',
            ], 401);
        }

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
            ],
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
            'kode_referral' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $noWa = $request->no_wa;
        if ($noWa[0] == '0') {
            $noWa = '62' . substr($noWa, 1);
        }

        do {
            $referralCode = 'REF-' . Str::upper(Str::random(6));
        } while (User::where('referral_code', $referralCode)->exists());

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
            'no_wa' => htmlspecialchars($noWa, ENT_QUOTES, 'UTF-8'),
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
            ],
        ], 201);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully',
        ]);
    }

    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => ['required', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $this->passwordRecoveryService->requestRecovery($validator->validated()['username']);

        return response()->json([
            'success' => true,
            'message' => PasswordRecoveryService::REQUEST_ACCEPTED_MESSAGE,
        ], 202);
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'confirmed', Password::min(12)],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        if (! $this->passwordRecoveryService->resetPassword(
            $validated['token'],
            $validated['email'],
            $validated['password'],
        )) {
            return response()->json([
                'success' => false,
                'message' => PasswordRecoveryService::RESET_FAILURE_MESSAGE,
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Password reset successful. Please sign in again.',
        ]);
    }
}

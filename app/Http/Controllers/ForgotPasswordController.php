<?php

namespace App\Http\Controllers;

use App\Models\Berita;
use App\Services\PasswordRecoveryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ForgotPasswordController extends Controller
{
    public function __construct(
        private readonly PasswordRecoveryService $passwordRecoveryService,
    ) {
    }

    public function create(): View
    {
        return view('template.forgotpassword', [
            'logoheader' => Berita::where('tipe', 'logoheader')->latest()->first(),
            'logofooter' => Berita::where('tipe', 'logofooter')->latest()->first(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:255'],
        ], [
            'username.required' => 'Harap isi kolom username.',
        ]);

        $this->passwordRecoveryService->requestRecovery($validated['username']);

        return back()->with('success', PasswordRecoveryService::REQUEST_ACCEPTED_MESSAGE);
    }

    public function showResetForm(Request $request, string $token)
    {
        $email = (string) $request->query('email', '');

        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return response()->view('password-reset', [
                'token' => '',
                'email' => '',
                'invalidLink' => true,
            ], 422)->withHeaders($this->resetResponseHeaders());
        }

        return response()->view('password-reset', [
            'token' => $token,
            'email' => $email,
            'invalidLink' => false,
        ])->withHeaders($this->resetResponseHeaders());
    }

    public function reset(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'confirmed', Password::min(12)],
        ]);

        if (! $this->passwordRecoveryService->resetPassword(
            $validated['token'],
            $validated['email'],
            $validated['password'],
        )) {
            return back()
                ->withInput($request->except(['token', 'password', 'password_confirmation']))
                ->withErrors(['email' => PasswordRecoveryService::RESET_FAILURE_MESSAGE]);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Kata sandi berhasil diperbarui. Silakan masuk kembali.');
    }

    private function resetResponseHeaders(): array
    {
        return [
            'Cache-Control' => 'no-store, private',
            'Pragma' => 'no-cache',
            'Referrer-Policy' => 'no-referrer',
        ];
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Berita;
use App\Models\Deposit;
use App\Models\Method;
use App\Services\Deposit\DepositService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DepositController extends Controller
{
    public function reloadd()
    {
        $showDemoMethods = app()->environment('local');

        return view('template.reload', [
            'data' => Deposit::where('username', Auth::user()->username)->orderBy('created_at', 'desc')->get(),
            'logoheader' => Berita::where('tipe', 'logoheader')->latest()->first(),
            'logofooter' => Berita::where('tipe', 'logofooter')->latest()->first(),
            'pay_method' => Method::availableForDeposit($showDemoMethods),
        ]);
    }

    public function create()
    {
        if (Auth::user()->isAffiliateActive()) {
            return redirect()->route('dashboard')->with('error', 'Akun Affiliate tidak dapat melakukan deposit. Silakan hubungi Admin.');
        }

        $showDemoMethods = app()->environment('local');

        return view('template.deposit', [
            'data' => Deposit::where('username', Auth::user()->username)->orderBy('created_at', 'desc')->get(),
            'logoheader' => Berita::where('tipe', 'logoheader')->latest()->first(),
            'logofooter' => Berita::where('tipe', 'logofooter')->latest()->first(),
            'pay_method' => Method::availableForDeposit($showDemoMethods),
        ]);
    }

    public function store(Request $request, DepositService $depositService)
    {
        if (Auth::user()->isAffiliateActive()) {
            return back()->with('error', 'Akun Affiliate tidak dapat melakukan deposit. Silakan hubungi Admin.');
        }

        $validated = $request->validate([
            'jumlah' => ['required', 'numeric', 'min:10000'],
            'metode' => ['required', 'string', 'max:50'],
            'no_telfon' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+\-\s]*$/'],
        ], [
            'jumlah.required' => 'Mohon isi jumlah deposit',
            'jumlah.numeric' => 'Jumlah harus berupa angka',
            'jumlah.min' => 'Minimal deposit Rp 10.000',
            'metode.required' => 'Mohon pilih metode pembayaran',
            'no_telfon.regex' => 'Format nomor WhatsApp tidak valid.',
        ]);

        try {
            $result = $depositService->create(Auth::user(), [
                ...$validated,
                'source' => 'web',
            ]);

            if (! ($result['success'] ?? false)) {
                return back()->withInput()->withErrors([
                    $result['field'] ?? 'msg' => $result['message'] ?? 'Deposit tidak dapat dibuat.',
                ]);
            }

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'order_id' => $result['order_id'],
                    'amount' => $result['amount'],
                    'fee' => $result['fee'],
                    'gross_amount' => $result['gross_amount'],
                    'pay_url' => $result['pay_url'] ?? null,
                    'va_number' => $result['va_number'] ?? null,
                    'expired_at' => $result['expired_at'] ?? null,
                    'message' => 'Silakan lakukan pembayaran',
                ]);
            }

            return redirect()->route('deposit.invoice', $result['order_id'])
                ->with('success', 'Silakan lakukan pembayaran');
        } catch (\Throwable $exception) {
            report($exception);

            return back()->withInput()->withErrors([
                'msg' => 'Terjadi kesalahan: ' . $exception->getMessage(),
            ]);
        }
    }
}

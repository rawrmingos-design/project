<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\Deposit;
use App\Models\Berita;
use App\Models\Pembayaran;
use App\Http\Controllers\TokoPayController;
use Illuminate\Support\Facades\Auth;
use Duitku\Config;
use Duitku\Pop;
use App\Http\Controllers\TriPayController;

class DepositController extends Controller
{
    public function reloadd()
    {
        return view('template.reload', ['data' => Deposit::where('username', Auth::user()->username)->orderBy('created_at', 'desc')->get(),
        'logoheader' => Berita::where('tipe', 'logoheader')->latest()->first(),
          'logofooter' => Berita::where('tipe', 'logofooter')->latest()->first(),
          'pay_method' => \App\Models\Method::all()
        ]);
    }
    public function create()
    {
        // 1. Block Active Affiliates
        if (Auth::user()->isAffiliateActive()) {
            return redirect()->route('dashboard')->with('error', 'Akun Affiliate tidak dapat melakukan deposit. Silakan hubungi Admin.');
        }

        // return view('components.deposit', ['data' => Deposit::where('username', Auth::user()->username)->orderBy('created_at', 'desc')->paginate(10),
        // 'logoheader' => Berita::where('tipe', 'logoheader')->latest()->first(),
        //   'logofooter' => Berita::where('tipe', 'logofooter')->latest()->first(),
        // ]);
        
        return view('template.deposit', ['data' => Deposit::where('username', Auth::user()->username)->orderBy('created_at', 'desc')->get(),
        'logoheader' => Berita::where('tipe', 'logoheader')->latest()->first(),
        'logofooter' => Berita::where('tipe', 'logofooter')->latest()->first(),
        'pay_method' => \App\Models\Method::all()
        ]);
    }

    public function store(Request $request)
    {
        // 1. Block Active Affiliates
        if (Auth::user()->isAffiliateActive()) {
            return back()->with('error', 'Akun Affiliate tidak dapat melakukan deposit. Silakan hubungi Admin.');
        }

        $request->validate([
            'jumlah' => 'required|numeric|min:10000',
            'metode' => 'required',
            'no_pembayaran' => 'required_if:metode,OVO,DANA,SHOPEEPAY,LINKAJA,GOPAY', // Validate phone if e-wallet
        ], [
            'jumlah.numeric' => "Jumlah harus berupa angka",
            "jumlah.min" => "Minimal deposit Rp 10.000",
            'jumlah.required' => "Mohon isi jumlah deposit",
            'metode.required' => "Mohon pilih metode pembayaran"
        ]);

        $api = \DB::table('setting_webs')->where('id',1)->first();
        
        $unik = date('Hs');
        $kode_unik = substr(str_shuffle("0123456789"), 0, 8);
        $order_id = 'DP'.$unik.$kode_unik; // DP for Deposit
        
        // --- 1. Fee Calculation (Server-Side) ---
        $method = \App\Models\Method::where('code', $request->metode)->first();
        if (!$method) {
            return back()->withErrors(['msg' => 'Metode pembayaran tidak valid']);
        }

        $fee_percent = $method->fee_percent ?? 0; // e.g. 0.70 means 0.7%
        $fix_fee = $method->fix_fee ?? 0;
        
        $net_amount = $request->jumlah;
        $fee_amount = ceil($net_amount * ($fee_percent / 100)) + $fix_fee;
        $gross_amount = $net_amount + $fee_amount;

        // Determine Payment Gateway for Deposit
        $gateway = $api->deposit_jalur ?? 'duitku'; // Default Duitku
        $paymentMethodCode = $request->metode;
        
        $result = [];
        $merchantOrderId = $order_id;
        $paymentUrl = null;
        $reference = null;
        $vaNumber = null;
        
        try {
            switch ($gateway) {
                case 'duitku':
                    // Map generic 'QRIS' to Duitku 'SQ' or others
                    $paymentMethodCode = $this->mapPaymentMethod($request->metode);
                    
                    // Duitku Config
                    $duitkuConfig = new Config($api->duitku_merchant_key, $api->duitku_merchant_code);
                    $duitkuConfig->setSandboxMode($api->duitku_mode === 'sandbox');
                    $duitkuConfig->setSanitizedMode(true);
                    $duitkuConfig->setDuitkuLogs(true);
            
                    $params = [
                        'paymentAmount' => (int) $gross_amount, // User pays GROSS
                        'merchantOrderId' => $merchantOrderId,
                        'productDetails' => 'Deposit Saldo',
                        'email' => Auth::user()->email ?? 'user@example.com',
                        'phoneNumber' => $request->no_telfon ?? '08123456789',
                        'customerVaName' => Auth::user()->name ?? Auth::user()->username,
                        'paymentMethod' => $paymentMethodCode,
                        'callbackUrl' => route('duitku.callback'),
                        'returnUrl' => route('riwayat'),
                        'expiryPeriod' => 60,
                        'customerDetail' => [
                            'firstName' => Auth::user()->username,
                            'lastName' => '',
                            'email' => Auth::user()->email ?? 'user@example.com',
                            'phoneNumber' => $request->no_telfon ?? '08123456789',
                        ],
                        'itemDetails' => [
                            [
                                'name' => 'Deposit Saldo',
                                'price' => (int) $gross_amount, 
                                'quantity' => 1
                            ]
                        ],
                    ];

                    $response = Pop::createInvoice($params, $duitkuConfig);
                    $res = json_decode($response, true);
                    
                    if (isset($res['statusCode']) && $res['statusCode'] == '00') {
                        $result = [
                            'success' => true,
                            'reference' => $res['reference'],
                            'pay_url' => $res['paymentUrl'] ?? null,
                            'va_number' => $res['vaNumber'] ?? $res['qrString'] ?? null,
                            'amount' => $gross_amount, // Duitku confirmed amount
                            'gateway_ref' => $res['reference'],
                            'expired_at' => now()->addMinutes(60)->toIso8601String(),
                        ];
                    } else {
                        throw new \Exception('Duitku Error: ' . ($res['statusMessage'] ?? 'Unknown'));
                    }
                    break;

                case 'tripay':
                    $tripay = new \App\Http\Controllers\TriPayController();
                    // Map 'QRIS' to 'QRIS' (Tripay usually uses QRIS or QRISC)
                    // Assuming 'QRIS' input maps to Tripay 'QRIS' code
                    $tp_method = ($request->metode == 'QRIS') ? 'QRIS' : $request->metode; 
                    
                    $res = $tripay->request(
                        $merchantOrderId, 
                        $gross_amount, 
                        $tp_method, 
                        Auth::user()->email ?? 'user@example.com', 
                        $request->no_telfon ?? '08123456789'
                    );
                    
                    if ($res['success']) {
                        $result = [
                            'success' => true,
                            'reference' => $res['reference'],
                            'pay_url' => null, // Tripay usually gives checkout URL or QR string
                            'va_number' => $res['no_pembayaran'], // Can be QR String or VA
                            'amount' => $res['amount'],
                            'gateway_ref' => $res['reference'],
                            'expired_at' => $res['expired_at'] ?? null,
                        ];
                        // If it's a URL, handle it
                        if (filter_var($res['no_pembayaran'], FILTER_VALIDATE_URL)) {
                             $result['pay_url'] = $res['no_pembayaran'];
                        }
                    } else {
                        throw new \Exception('Tripay Error: ' . ($res['msg'] ?? 'Unknown'));
                    }
                    break;

                case 'tokopay':
                    $tokopay = new \App\Http\Controllers\TokoPayController();
                    // Mapping needs verification, assuming 'QRIS'
                    $tp_code = ($request->metode == 'QRIS') ? 'QRIS' : $request->metode;
                    
                    // Using createAdvanceOrder as it looks more complete in reference
                    $res = $tokopay->createAdvanceOrder(
                        $merchantOrderId,
                        $tp_code,
                        $gross_amount,
                        Auth::user()->username,
                        $request->no_telfon ?? '08123456789',
                        'Deposit Saldo'
                    );

                     if (isset($res['status']) && $res['status'] == true) { // Check Tokopay success response structure
                         $data = $res['data'];
                         $result = [
                            'success' => true,
                            'reference' => $data['trx_id'] ?? $merchantOrderId, // Use Trx ID if available
                            'pay_url' => $data['pay_url'] ?? null,
                            'va_number' => $data['pay_url'] ?? null, // Tokopay often gives pay_url for QRIS
                            'amount' => $data['amount'] ?? $gross_amount,
                            'gateway_ref' => $data['trx_id'] ?? null,
                            'expired_at' => $data['expired_at'] ?? $data['expired_ts'] ?? null,
                        ];
                     } else {
                         throw new \Exception('Tokopay Error: ' . ($res['error_msg'] ?? 'Unknown'));
                     }
                    break;
                    
                default:
                    throw new \Exception('Gateway Deposit tidak valid');
            }
            
            // --- 2. Save to Database ---
            if (isset($result['success']) && $result['success']) {
                $deposit = new Deposit();
                $deposit->order_id = $merchantOrderId;
                $deposit->username = Auth::user()->username;
                $deposit->metode = $request->metode;
                $deposit->no_pembayaran = $result['va_number'] ?? $result['pay_url'] ?? '-';
                $deposit->jumlah = $net_amount; // NET AMOUNT (Verified)
                $deposit->status = "Pending";
                $deposit->save();
                
                $pembayaran = new Pembayaran();
                $pembayaran->order_id = $merchantOrderId;
                $pembayaran->harga = $result['amount']; // GROSS AMOUNT
                $pembayaran->no_pembayaran = $deposit->no_pembayaran;
                $pembayaran->no_pembeli = $request->no_telfon ?? '-';
                $pembayaran->status = 'Belum Lunas';
                $pembayaran->metode = $request->metode; // Store Method Code (e.g. QRIS)
                $pembayaran->reference = $result['gateway_ref'];
                $pembayaran->expired_at = $this->resolvePaymentExpiryAt($result, $gateway);
                // Gateway specific columns if needed
                if ($gateway == 'duitku') {
                    $pembayaran->duitku_reference = $result['gateway_ref'];
                    $pembayaran->duitku_merchant_order_id = $merchantOrderId;
                }
                $pembayaran->save();

                // Redirect
                // if (!empty($result['pay_url'])) {
                //      return redirect($result['pay_url']);
                // }
                
                return redirect()->route('deposit.invoice', $merchantOrderId)->with('success', 'Silakan lakukan pembayaran');

            } else {
                 return back()->withErrors(['msg' => 'Gagal membuat invoice via ' . ucfirst($gateway)]);
            }

        } catch (\Exception $e) {
            return back()->withErrors(['msg' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
    }

    private function mapPaymentMethod($code)
    {
        $maps = [
            'OVO' => 'OV',
            'DANA' => 'DA',
            'SHOPEEPAY' => 'SA',
            'LINKAJA' => 'LF',
            'QRIS' => 'SQ',
            'BNC' => 'NC',
        ];
        return $maps[$code] ?? $code;
    }

    private function resolvePaymentExpiryAt(array $result, string $gateway): ?Carbon
    {
        $candidates = [
            $result['expired_at'] ?? null,
            $result['expires_at'] ?? null,
            $result['expired_time'] ?? null,
            $result['expired_ts'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (blank($candidate)) {
                continue;
            }

            if (is_numeric($candidate)) {
                $timestamp = (int) $candidate;

                // Normalize millisecond epoch values from some gateways.
                if ($timestamp > 9_999_999_999) {
                    $timestamp = (int) floor($timestamp / 1000);
                }

                return Carbon::createFromTimestamp($timestamp, config('app.timezone'));
            }

            try {
                return Carbon::parse($candidate, config('app.timezone'));
            } catch (\Throwable) {
                continue;
            }
        }

        return match (strtolower($gateway)) {
            'duitku' => now()->addMinutes(60),
            'tripay' => now()->addHours(24),
            'tokopay' => now()->addHours(3),
            default => now()->addHours(3),
        };
    }
}

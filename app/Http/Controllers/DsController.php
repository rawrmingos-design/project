<?php

namespace App\Http\Controllers;

use App\Models\Berita;
use Illuminate\Http\Request;
use App\Models\Pembelian;
use App\Models\Pembayaran;
use Illuminate\Support\Carbon;
use App\Models\Kategori;
use App\Models\AffiliateHistory;
use Auth;


class DsController extends Controller
{
    
    public function dashboard()
    {
        $today = Carbon::today(); // Mengambil waktu hari ini (00:00:00)
        $user = Auth::user();
        $username = $user->username;

        // 1. Optimize: Fetch Today's Transactions ONCE
        $todaysTransactions = Pembelian::where('username', $username)
                            ->whereDate('created_at', $today)
                            ->get();

        // 2. Calculate Daily Stats from Collection (No extra DB queries)
        $totalPembelian = $todaysTransactions->sum('harga');
        $banyakPembelian = $todaysTransactions->count();
        $banyakPembelianPending = $todaysTransactions->where('status', 'Pending')->count();
        $banyakPembelianSuccess = $todaysTransactions->whereIn('status', ['Sukses', 'Success'])->count(); // Handle both namings
        $banyakPembelianBatal = $todaysTransactions->whereIn('status', ['Batal', 'Gagal'])->count();

        // 3. Fix Tier Logic: Use LIFETIME Success Transactions
        $lifetimeSuccessCount = Pembelian::where('username', $username)
                                ->whereIn('status', ['Sukses', 'Success'])
                                ->count();

        $setting = \App\Models\SettingWeb::first();
        $goldThreshold = $setting->trx_count_gold ?? 50;
        $platinumThreshold = $setting->trx_count_platinum ?? 100;
        
        $currentCount = $lifetimeSuccessCount; // Updated to use lifetime count
        $currentRole = $user->role;
        $nextRole = '';
        $progress = 0;
        $target = 0;

        if ($currentRole == 'Member') {
            $target = $goldThreshold;
            $nextRole = 'Gold';
            $progress = ($target > 0) ? ($currentCount / $target) * 100 : 0;
        } elseif ($currentRole == 'Gold') {
            $target = $platinumThreshold;
            $nextRole = 'Platinum';
            $progress = ($target > 0) ? ($currentCount / $target) * 100 : 0;
        } else { // Platinum or Admin
            $progress = 100;
            $nextRole = 'Max Level';
            $target = $platinumThreshold; // Just for display
        }
        
        if ($progress > 100) $progress = 100;

        // --- Affiliate System Logic ---
        $referralCode = $user->referral_code ?? '-';
        $totalCommission = \App\Models\AffiliateHistory::where('uplink_id', $user->id)->sum('amount');
        $affiliateHistory = \App\Models\AffiliateHistory::where('uplink_id', $user->id)
                            ->latest()
                            ->take(5)
                            ->get();

        // 4. Fetch Recent Transactions for Dashboard Table (Limit 10)
        $todaysTransactions = $todaysTransactions; // Keep for stats
        $recentTransactions = Pembelian::where('username', $username)
                                ->latest()
                                ->take(10)
                                ->get();

        return view('template.dashboard', [
            'data' => $recentTransactions, // Pass recent history instead of today's
            'logoheader' => Berita::where('tipe', 'logoheader')->latest()->first(),
            'logofooter' => Berita::where('tipe', 'logofooter')->latest()->first(),
            'total_pembelian' => $totalPembelian,
            'banyak_pembelian' => $banyakPembelian,
            'banyak_pembelian_pending' => $banyakPembelianPending,
            'banyak_pembelian_success' => $banyakPembelianSuccess,
            'banyak_pembelian_batal' => $banyakPembelianBatal,
            // Tier Data
            'tier_progress' => $progress,
            'tier_current' => $currentRole,
            'tier_next' => $nextRole,
            'tier_count' => $currentCount,
            'tier_target' => $target,
            // Affiliate Data
            'referral_code' => $referralCode,
            'total_commission' => $totalCommission,
            'affiliate_history' => $affiliateHistory,
        ]);
    }
    
    public function editProfile()
    {
         return view('template.profile',[
        'logoheader' => Berita::where('tipe', 'logoheader')->latest()->first(),
        'logofooter' => Berita::where('tipe', 'logofooter')->latest()->first(),
        ]);
    }
    
    public function saveEditProfile(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'username' => 'required|min:3|max:255|unique:users,username,'.Auth()->user()->id,
            'password' => 'nullable|min:6|max:255'
        ], [
            'nama.required' => 'Harap isi kolom nama!',
            'username.required' => 'Harap isi kolom username!',
            'username.min' => 'Panjang username minimal 3 huruf',
            'username.unique' => 'Username telah digunakan',
            'username.max' => 'Panjang username maximal 255 huruf',
            'password.min' => 'Panjang password minimal 6 huruf',
            'password.max' => 'Panjang password maximal 255 huruf',
            'no_wa.required' => 'Harap isi no whatsapp!',
            'no_wa.numeric' => 'No whatsapp tidak valid!',
            'no_wa.unique' => 'No whatsapp telah digunakan',
        ]);

        
        $data = [
          'name' => $request->name,
          'username' => $request->username,
        ];
        
        if(!empty($request->password)){
            
            $data['password'] = bcrypt($request->password);
            
        }
        
        \App\Models\User::where('id',Auth()->user()->id)->update($data);
        
        return redirect()->back()->with('success', 'Berhasil mengedit profile!');

    }
    

    public function affiliate(Request $request) // Added Request $request
    {
        $user = Auth::user();
        $referral_code = '-'; // Initialize with default
        $total_commission = 0; // Initialize with default
        $affiliate_history = collect(); // Initialize with empty collection

        if ($user && $user->role !== "Admin") {
            
             // 1. Handle Request Action
            if ($request->has('action') && $request->action === 'request') {
                if ($user->affiliate_status === 'inactive' || $user->affiliate_status === null) {
                    $user->affiliate_status = 'pending';
                    $user->save();
                    return redirect()->back()->with('success', 'Permintaan Affiliate berhasil dikirim. Mohon tunggu persetujuan Admin.');
                } else {
                    return redirect()->back()->with('error', 'Anda sudah memiliki status affiliate atau permintaan sedang diproses.');
                }
            }

            // 2. Block direct access for inactive users
            if ($user->affiliate_status === 'inactive' || $user->affiliate_status === null) {
                return redirect()->route('dashboard')->with('error', 'Halaman affiliate hanya untuk member yang telah bergabung.');
            }

            // 3. Affiliate Logic (Only for Active/Pending/Rejected)
            if ($user->affiliate_status !== 'inactive' && $user->affiliate_status !== null) {
                
                 if (!$user->referral_code) {
                    // Generate if validation passed but code missing (Edge case)
                    $user->referral_code = 'REF-' . strtoupper(Str::random(6));
                    $user->save();
                }

                $referral_code = $user->referral_code;
                
                // Calculate Total Commission
                $total_commission = AffiliateHistory::where('uplink_id', $user->id) // Changed to $user->id
                    ->sum('amount');
                    
                // Get History
                $affiliate_history = AffiliateHistory::where('uplink_id', $user->id) // Changed to $user->id
                    // ->with('downlink') // Eager load - assuming 'downlink' relationship exists
                    ->latest()
                    ->paginate(10);
            }
        }

        return view('template.affiliate', [
            'logoheader' => Berita::where('tipe', 'logoheader')->latest()->first(),
            'logofooter' => Berita::where('tipe', 'logofooter')->latest()->first(),
            'affiliate_history' => $affiliate_history,
            'referral_code' => $referral_code,
            'total_commission' => $total_commission,
            'kategoris' => Kategori::where('status', 'active')->orderBy('nama', 'asc')->get()
        ]);
    }

    public function withdrawal()
    {
        $user = Auth::user();

        $withdrawals = \App\Models\Withdrawal::where('user_id', $user->id)
            ->latest()
            ->paginate(5);

        // Check if user already requested today
        $hasRequestedToday = \App\Models\Withdrawal::where('user_id', $user->id)
            ->whereDate('created_at', now()->toDateString())
            ->exists();

        return view('template.withdrawal', [
            'logoheader' => Berita::where('tipe', 'logoheader')->latest()->first(),
            'logofooter' => Berita::where('tipe', 'logofooter')->latest()->first(),
            'withdrawals' => $withdrawals,
            'hasRequestedToday' => $hasRequestedToday,
        ]);
    }

    public function processWithdrawal(Request $request)
    {
        $request->validate([
            'bank_destination' => 'required',
            'account_number' => 'required|numeric',
            'account_name' => 'required',
            'amount' => 'required|numeric|min:10000',
        ]);

        $user = \App\Models\User::find(Auth::id());
        
        // 1. Check if user already requested today
        $hasRequestedToday = \App\Models\Withdrawal::where('user_id', $user->id)
            ->whereDate('created_at', now()->toDateString())
            ->exists();

        if ($hasRequestedToday) {
            return back()->with('error', 'Anda hanya dapat melakukan penarikan 1 kali dalam sehari. Silakan coba lagi besok.');
        }

        // 2. Check balance
        if ($user->balance < $request->amount) {
            return back()->with('error', 'Saldo tidak mencukupi!');
        }

        // Deduct Balance Immediately
        $user->balance -= $request->amount;
        $user->save();

        // Create Withdrawal Request
        \App\Models\Withdrawal::create([
            'user_id' => $user->id,
            'rekening' => $request->bank_destination . ' - ' . $request->account_number . ' - ' . $request->account_name,
            'total_transfer' => $request->amount,
            'biaya_admin' => 0, // Or set a fee
            'status' => 'pending',
        ]);

        return redirect()->back()->with('success', 'Permintaan penarikan berhasil dikirim. Menunggu persetujuan Admin.');
    }
}
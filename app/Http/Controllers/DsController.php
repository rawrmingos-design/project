<?php

namespace App\Http\Controllers;

use App\Models\Berita;
use Illuminate\Http\Request;
use App\Models\Pembelian;
use App\Models\Pembayaran;
use Illuminate\Support\Carbon;
use App\Models\Kategori;
use Auth;


class DsController extends Controller
{
    
    public function dashboard()
    {
    $today = Carbon::today();
    $username = Auth::user()->username;

    $totalPembelian = Pembelian::where('username', $username)
                        ->whereDate('created_at', $today)
                        ->sum('harga');
    $banyakPembelian = Pembelian::where('username', $username)
                        ->whereDate('created_at', $today)
                        ->count();
    $banyakPembelianPending = Pembelian::where('username', $username)
                        ->whereDate('created_at', $today)
                        ->where('status', 'Pending')
                        ->count();
    $banyakPembelianSuccess = Pembelian::where('username', $username)
                        ->whereDate('created_at', $today)
                        ->where('status', 'Sukses')
                        ->count();
    $banyakPembelianBatal = Pembelian::where('username', $username)
                        ->whereDate('created_at', $today)
                        ->where('status', 'Batal')
                        ->count();

    // --- Tier System Logic ---
    $setting = \App\Models\SettingWeb::first();
    $goldThreshold = $setting->trx_count_gold ?? 50;
    $platinumThreshold = $setting->trx_count_platinum ?? 100;
    
    $currentCount = $banyakPembelianSuccess; // Total Success Transactions
    $currentRole = Auth::user()->role;
    $nextRole = '';
    $progress = 0;
    $target = 0;

    if ($currentRole == 'Member') {
        $target = $goldThreshold;
        $nextRole = 'Gold';
        $progress = ($currentCount / $target) * 100;
    } elseif ($currentRole == 'Gold') {
        $target = $platinumThreshold;
        $nextRole = 'Platinum';
        $progress = ($currentCount / $target) * 100;
    } else {
        $progress = 100; // Platinum or Admin
        $nextRole = 'Max Level';
    }
    
    // Cap progress at 100
    if ($progress > 100) $progress = 100;

    // --- Affiliate System Logic ---
    $referralCode = Auth::user()->referral_code ?? '-';
    $totalCommission = \App\Models\AffiliateHistory::where('uplink_id', Auth::user()->id)->sum('amount');
    $affiliateHistory = \App\Models\AffiliateHistory::where('uplink_id', Auth::user()->id)
                        ->latest()
                        ->take(5)
                        ->get();

    return view('template.dashboard', [
        'data' => \App\Models\Pembelian::where('username', $username)
                    ->whereDate('created_at', $today)
                    ->get(),
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
    
    
}
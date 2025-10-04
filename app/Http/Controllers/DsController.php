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
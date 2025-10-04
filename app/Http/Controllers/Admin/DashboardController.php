<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pembelian;
use App\Models\Pembayaran;
use Illuminate\Support\Carbon;
use App\Models\User;
use App\Models\Kategori;
use App\Models\Layanan;

use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    public function create()
    {
        $total_users = User::count();
        $active_users = User::whereNotNull('updated_at')->count();
        $total_games = Kategori::count();
        $total_services = Layanan::count();
        $users = User::selectRaw('DATE(created_at) as date, COUNT(*) as total_users')
                     ->groupBy('date')
                     ->get();
        $userChartData = $users->map(function ($item) {
            return [
                'tanggal' => Carbon::parse($item->date)->toDateString(),
                'pengguna' => $item->total_users,
            ];
        })->toArray();
        $visitors = Cache::get('visitors', []);
        $visitorChartData = [];
        foreach ($visitors as $date => $visitorsArray) {
            $visitorChartData[] = [
                'tanggal' => Carbon::parse($date)->toDateString(),
                'pengunjung' => count($visitorsArray),
            ];
        }
        $date = now();
        $dayPlusOne = $date->copy()->addDays(1);
        $pastWeek = $date->copy()->subWeeks(1);
    
        // Menyiapkan data grafik pembelian selama 7 hari terakhir
        $grafikPesanan = Pembelian::whereBetween('created_at', [$pastWeek, $dayPlusOne])
            ->selectRaw('DATE(created_at) as tanggal, COUNT(*) as jumlah')
            ->groupBy('tanggal')
            ->orderBy('tanggal', 'asc')
            ->get()
            ->map(function ($item) {
                return [
                    'x' => $item->tanggal,
                    'y' => $item->jumlah,
                ];
            });

        $totalPembelian = Pembelian::whereDay('created_at', $date->day)
            ->whereMonth('created_at', $date->month)
            ->whereYear('created_at', $date->year)
            ->sum('harga');

        $banyakPembelian = Pembelian::whereDay('created_at', $date->day)
            ->whereMonth('created_at', $date->month)
            ->whereYear('created_at', $date->year)
            ->count();

        $totalPembelianSuccess = Pembelian::whereDay('created_at', $date->day)
            ->whereMonth('created_at', $date->month)
            ->whereYear('created_at', $date->year)
              ->where(function($query) {
                $query->where('status', 'Sukses')
                      ->orWhere('status', 'Success');
            })
            ->sum('harga');

        $banyakPembelianSuccess = Pembelian::whereDay('created_at', $date->day)
            ->whereMonth('created_at', $date->month)
            ->whereYear('created_at', $date->year)
              ->where(function($query) {
                $query->where('status', 'Sukses')
                      ->orWhere('status', 'Success');
            })
            ->count();

        $totalPembelianBatal = Pembelian::whereDay('created_at', $date->day)
            ->whereMonth('created_at', $date->month)
            ->whereYear('created_at', $date->year)
            ->where(function($query) {
                $query->where('status', 'Batal')
                      ->orWhere('status', 'Gagal');
            })
            ->sum('harga');

        $banyakPembelianBatal = Pembelian::whereDay('created_at', $date->day)
            ->whereMonth('created_at', $date->month)
            ->whereYear('created_at', $date->year)
            ->where(function($query) {
                $query->where('status', 'Batal')
                      ->orWhere('status', 'Gagal');
            })
            ->count();

        $totalPembelianPending = Pembelian::whereDay('created_at', $date->day)
            ->whereMonth('created_at', $date->month)
            ->whereYear('created_at', $date->year)
            ->where('status', 'Pending')
            ->sum('harga');

        $banyakPembelianPending = Pembelian::whereDay('created_at', $date->day)
            ->whereMonth('created_at', $date->month)
            ->whereYear('created_at', $date->year)
            ->where('status', 'Pending')
            ->count();

        $totalPembayaran = Pembayaran::whereDay('created_at', $date->day)
            ->whereMonth('created_at', $date->month)
            ->whereYear('created_at', $date->year)
            ->where('status', 'Lunas')
            ->sum('harga');

        $banyakPembayaran = Pembayaran::whereDay('created_at', $date->day)
            ->whereMonth('created_at', $date->month)
            ->whereYear('created_at', $date->year)
            ->where('status', 'Lunas')
            ->count();
            
        // Keuntungan hari ini
        $today_profit = Pembelian::where(function($query) {
                $query->where('status', 'Sukses')
                      ->orWhere('status', 'Success');
                
                })
            ->whereDate('created_at', Carbon::today())
            ->sum('profit');
        
        // Keuntungan kemarin
        $yesterday_profit = Pembelian::where(function($query) {
                $query->where('status', 'Sukses')
                      ->orWhere('status', 'Success');
                
                })
            ->whereDate('created_at', Carbon::yesterday())
            ->sum('profit');

        // Keuntungan 7 hari terakhir
        $last7Days_profit =Pembelian::where(function($query) {
                $query->where('status', 'Sukses')
                      ->orWhere('status', 'Success');
                
                })
            ->whereBetween('created_at', [Carbon::now()->subDays(7), Carbon::now()])
            ->sum('profit');

        // Keuntungan 1 bulan terakhir
        $last30Days_profit = Pembelian::where(function($query) {
                $query->where('status', 'Sukses')
                      ->orWhere('status', 'Success');
                
                })
            ->whereBetween('created_at', [
                Carbon::now()->subDays(30)->startOfDay()->toDateTimeString(),
                Carbon::now()->endOfDay()->toDateTimeString()
            ])
            ->sum('profit');        

        return view('components.admin.dashboard', [
            'grafik_pesanan' => json_encode($grafikPesanan),
            'total_pembelian' => $totalPembelian,
            'total_pembelian_batal' => $totalPembelianBatal,
            'total_pembelian_pending' => $totalPembelianPending,
            'total_pembelian_success' => $totalPembelianSuccess,
            'banyak_pembelian' => $banyakPembelian,
            'banyak_pembelian_batal' => $banyakPembelianBatal,
            'banyak_pembelian_pending' => $banyakPembelianPending,
            'banyak_pembelian_success' => $banyakPembelianSuccess,
            'total_deposit' => $totalPembayaran,
            'banyak_deposit' => $banyakPembayaran,
            'today_profit' => $today_profit,
            'yesterday_profit' => $yesterday_profit,
            'last7day_profit' => $last7Days_profit,
            'last30day_profit' => $last30Days_profit,
            'total_keseluruhan_pembelian' => Pembelian::sum('harga'),
            'banyak_keseluruhan_pembelian' => Pembelian::count(),
            'total_keseluruhan_pembelian_berhasil' => Pembelian::where('status', 'Sukses')->orWhere('status', 'Success')->sum('harga'),
            'banyak_keseluruhan_pembelian_berhasil' => Pembelian::where('status', 'Sukses')->orWhere('status', 'Success')->count(),
            'total_keseluruhan_pembelian_pending' => Pembelian::where('status', 'Pending')->sum('harga'),
            'banyak_keseluruhan_pembelian_pending' => Pembelian::where('status', 'Pending')->count(),
            'total_keseluruhan_pembelian_batal' => Pembelian::where(function($query) {
                $query->where('status', 'Batal')
                      ->orWhere('status', 'Gagal');
            })->sum('harga'),
            'banyak_keseluruhan_pembelian_batal' => Pembelian::where(function($query) {
                $query->where('status', 'Batal')
                      ->orWhere('status', 'Gagal');
            })->count(),
            'total_keseluruhan_deposit' => Pembayaran::where('status', 'Lunas')->sum('harga'),
            'banyak_keseluruhan_deposit' => Pembayaran::where('status', 'Lunas')->count(),
            'keuntungan_bersih' => Pembelian::where('status', 'Sukses')->orWhere('status', 'Success')->sum('profit'),
            'grafik_chart' => json_encode($userChartData),
            'total_users' => $total_users,
            'active_users' => $active_users,
            'total_games' => $total_games,
            'total_services' => $total_services,
            'visitor_chart' => json_encode($visitorChartData),
        ]);
    }
}

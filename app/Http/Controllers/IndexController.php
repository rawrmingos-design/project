<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Kategori;
use App\Models\Berita;
use App\Models\Tabpills;
use Illuminate\Support\Facades\Cache;

class IndexController extends Controller
{
     public function create()
    {
        // Cache TTL: 5 minutes (300 seconds)
        $ttl = 300;

        // $customOrder = [8507, 8639, 8640, 8641, 8644, 8663, 8646];
        $customOrder = [8507, 8639, 8640, 8641, 8644, 8664];
        
        $kategori = Cache::remember('kategori_active', $ttl, function () {
            return Kategori::where('status', 'active')->get();
        });

        $mlbb = Cache::remember('kategori_mlbb', $ttl, function () use ($customOrder) {
            return Kategori::whereIn('id', $customOrder)
                ->orderByRaw("FIELD(id, " . implode(',', $customOrder) . ")")
                ->get()
                ->map(function ($item) {
                    $item->tipe = "mlbb";
                    return $item;
                });
        });

        $banner = Cache::remember('banner_list', $ttl, function () {
            return Berita::where('tipe', 'banner')
                ->orderBy('urutan')
                ->orderByDesc('id')
                ->get();
        });

        $logoheader = Cache::remember('logo_header', $ttl, function () {
            return Berita::where('tipe', 'logoheader')->latest()->first();
        });

        $logofooter = Cache::remember('logo_footer', $ttl, function () {
            return Berita::where('tipe', 'logofooter')->latest()->first();
        });

        $popup = Cache::remember('popup_latest', $ttl, function () {
            return Berita::where('tipe', 'popup')
                ->orderBy('urutan')
                ->orderByDesc('id')
                ->first();
        });

        $pay_method = Cache::remember('payment_methods', $ttl, function () {
            return \App\Models\Method::all();
        });

        // Flashsale, CategoryTypes, and Articles are now handled by Livewire components
        // No need to fetch them here anymore
        
        return view('template.id.index', compact('kategori', 'mlbb', 'banner', 'logoheader', 'logofooter', 'popup', 'pay_method'));
}


    
    public function cariIndex(Request $request)
    {
        if($request->ajax()){
            $requestData = $request->validate([
                'data' => 'required|string',
            ]);

            $data = Kategori::where('nama', 'LIKE', '%'.$requestData['data'].'%')
                            ->where('status', 'active')
                            ->limit(6)
                            ->get();

            $res = '';

            foreach($data as $d){
                $res .= '
                    <li class="p-2 dropdown-item">
                        <a href="'.url("/id").'/'.$d->kode.'" class="text-white">
                            <div class="flex cursor-pointer select-none items-center rounded-md px-3 py-2" role="option" tabindex="-1" aria-selected="false">
                                <img alt="'.$d->nama.'" class="aspect-square w-24 rounded-2xl object-cover" src="'.$d->thumbnail.'" />
                                <span class="ml-3 flex-auto truncate">'.$d->nama.'</span>
                            </div>
                        </a>
                    </li>';
            }
            
            return $res;
        }
    }

    public function recentPurchases()
    {
        // Cache for 30 seconds to prevent query spam on high traffic
        $recent = Cache::remember('recent_purchases_popup', 30, function() {
            return \App\Models\Pembelian::where('status', 'Success')
                ->orWhere('status', 'Sukses')
                ->latest('updated_at')
                ->take(20)
                ->get()
                ->map(function ($pembelian) {
                    $rawName = $pembelian->username;
                    // Provide a default name if it's null
                    $name = !empty($rawName) ? $rawName : 'Seseorang';
                    
                    // Mask username
                    $len = strlen($name);
                    if ($len > 3) {
                        $visibleCount = max(1, floor($len / 2));
                        $name = substr($name, 0, $visibleCount) . str_repeat('*', $len - $visibleCount);
                    }

                    // Manual lookup for Category and Image
                    $layananData = \App\Models\Layanan::where('layanan', $pembelian->layanan)->first();
                    $kategoriName = 'Sebuah Game';
                    $kategoriImage = null;

                    if ($layananData && $layananData->kategori_id) {
                        $kategoriData = \App\Models\Kategori::find($layananData->kategori_id);
                        if ($kategoriData) {
                            $kategoriName = $kategoriData->nama;
                            $kategoriImage = $kategoriData->thumbnail;
                        }
                    }

                    $itemName = $pembelian->layanan ?? 'Item';
                    $timeAgo = $pembelian->updated_at ? $pembelian->updated_at->diffForHumans() : 'Baru saja';
                    
                    return [
                        'name' => $name,
                        'item' => $itemName,
                        'game' => $kategoriName,
                        'time_ago' => $timeAgo,
                        'image' => $kategoriImage
                    ];
                });
        });

        return response()->json($recent);
    }
}

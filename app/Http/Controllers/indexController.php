<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Kategori;
use App\Models\Berita;
use App\Models\Tabpills;
class IndexController extends Controller
{
     public function create()
    {
        // $customOrder = [8507, 8639, 8640, 8641, 8644, 8663, 8646];
        $customOrder = [8507, 8639, 8640, 8641, 8644, 8664];
        $kategori = Kategori::where('status', 'active')->get();
        $mlbb = Kategori::whereIn('id', $customOrder)
            ->orderByRaw("FIELD(id, " . implode(',', $customOrder) . ")")
            ->get()
            ->map(function ($item) {
                $item->tipe = "mlbb";
                return $item;
            });
        $banner = Berita::where('tipe', 'banner')->get();
        $logoheader = Berita::where('tipe', 'logoheader')->latest()->first();
        $logofooter = Berita::where('tipe', 'logofooter')->latest()->first();
        $popup = Berita::where('tipe', 'popup')->latest()->first();
        $pay_method = \App\Models\Method::all();
        // Fetch flash sale items from Layanan model
        $flashsale = \App\Models\Layanan::join('kategoris', 'kategoris.id', '=', 'layanans.kategori_id')
        ->join('paket_layanans', 'paket_layanans.layanan_id', '=', 'layanans.id')
        ->select(
            'kategoris.thumbnail AS gmr_thumb', 
            'kategoris.kode AS kode_game', 
            'layanans.*', 
            'paket_layanans.product_logo', 
            'layanans.stock_flash_sale AS sisa_stok'
        )
        ->where('layanans.is_flash_sale', 1)
        ->where('layanans.expired_flash_sale', '>=', now())
        ->where('layanans.stock_flash_sale', '>', 0)
        ->where('layanans.stock_flash_sale', '>', 0)
        ->get();

        $categoryTypes = \App\Models\CategoryType::orderBy('sort', 'asc')
            ->with(['kategoris' => function ($query) {
                $query->where('status', 'active');
            }])
            ->get();
        
        return view('template.id.index', compact('kategori', 'mlbb', 'banner', 'logoheader', 'logofooter', 'popup', 'pay_method', 'flashsale', 'categoryTypes'));
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
}
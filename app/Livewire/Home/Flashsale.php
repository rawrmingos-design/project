<?php

namespace App\Livewire\Home;

use Livewire\Component;
use Illuminate\Support\Facades\Cache;
use App\Models\Layanan;

class Flashsale extends Component
{
    public function placeholder()
    {
        return view('livewire.home.flashsale-skeleton');
    }

    public function render()
    {   
        $flashsale = Cache::remember('flashsale_items', 60, function () {
            return Layanan::join('kategoris', 'kategoris.id', '=', 'layanans.kategori_id')
                ->join('paket_layanans', 'paket_layanans.layanan_id', '=', 'layanans.id')
                ->select(
                    'layanans.id',
                    'kategoris.thumbnail AS gmr_thumb',
                    'kategoris.kode AS kode_game',
                    'kategoris.nama AS kategori_nama',
                    'layanans.judul_flash_sale',
                    'layanans.harga',
                    'layanans.harga_flash_sale',
                    'layanans.stock_flash_sale',
                    'layanans.expired_flash_sale',
                    'paket_layanans.product_logo',
                    'layanans.stock_flash_sale AS sisa_stok'
                )
                ->where('layanans.is_flash_sale', 1)
                ->where('layanans.expired_flash_sale', '>=', now())
                ->where('layanans.stock_flash_sale', '>', 0)
                ->orderBy('layanans.expired_flash_sale')
                ->get();
        });

        return view('livewire.home.flashsale', compact('flashsale'));
    }
}

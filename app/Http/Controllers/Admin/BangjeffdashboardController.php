<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\provider\BangJeffController;
use Illuminate\Http\Request;

class BangjeffdashboardController extends Controller
{
    public function balance()
    {
        try {
            $data = (new BangJeffController())->balance();
            $balance = $data['data']['balance']['value'] ?? $data['data']['balance'] ?? null;

            if ($balance !== null) {
                return view('components.admin.bangjeff.ceksaldobj', ['saldo' => $balance]);
            }

            return view('components.admin.bangjeff.ceksaldobj', ['error' => $data['message'] ?? 'Gagal mengambil saldo BangJeff']);
        } catch (\Exception $e) {
            return view('components.admin.bangjeff.ceksaldobj', ['error' => $e->getMessage()]);
        }
    }
    
    public function getProduct()
{
    try {
        $data = (new BangJeffController())->getProduct();
        return view('components.admin.bangjeff.products', ['products' => $data]);
    } catch (\Exception $e) {
        return view('components.admin.bangjeff.products', ['error' => $e->getMessage()]);
    }
}

}

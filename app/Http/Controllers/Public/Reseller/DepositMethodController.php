<?php

namespace App\Http\Controllers\Public\Reseller;

use App\Http\Controllers\Controller;
use App\Models\Method;

class DepositMethodController extends Controller
{
    public function index()
    {
        $showDemoMethods = app()->environment('local');
        $methods = Method::availableForDeposit($showDemoMethods);
        
        return response()->json([
            'success' => true,
            'data' => $methods
        ]);
    }
}

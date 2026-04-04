<?php

namespace Tests\Feature;

use App\Http\Controllers\provider\BangJeffController;
use App\Http\Controllers\provider\TopupediaController;
use App\Models\Pembelian;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ProviderCallbackStatusNormalizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_bangjeff_callback_normalizes_provider_status_codes(): void
    {
        $pembelian = Pembelian::create([
            'order_id' => 'INV-BJ-CB-001',
            'username' => 'callback-user',
            'user_id' => '10001',
            'zone' => '2001',
            'nickname' => 'Callback User',
            'layanan' => 'Weekly Pass',
            'provider_order_id' => 'BJ-INV-001',
            'status' => 'Pending',
            'harga' => 15000,
            'profit' => 1000,
            'tipe_transaksi' => 'game',
        ]);

        $controller = new BangJeffController();
        $request = Request::create('/callback/bangjeff', 'POST', [], [], [], [], json_encode([
            'invoice_number' => 'BJ-INV-001',
            'status_code' => 'REFUNDED',
        ]));

        $controller->handleCallback($request);

        $this->assertSame('Refunded', $pembelian->fresh()->status);
    }

    public function test_topupedia_callback_normalizes_provider_status_codes(): void
    {
        $pembelian = Pembelian::create([
            'order_id' => 'INV-TOPUPEDIA-CB-001',
            'username' => 'callback-user',
            'user_id' => '10001',
            'zone' => '2001',
            'nickname' => 'Callback User',
            'layanan' => 'Weekly Pass',
            'provider_order_id' => 'TP-INV-001',
            'status' => 'Pending',
            'harga' => 15000,
            'profit' => 1000,
            'tipe_transaksi' => 'game',
        ]);

        $controller = new TopupediaController();
        $request = Request::create('/callback/topupedia', 'POST', [], [], [], [], json_encode([
            'invoice_number' => 'TP-INV-001',
            'status_code' => 'PROCESSING',
        ]));

        $controller->handleCallback($request);

        $this->assertSame('Processing', $pembelian->fresh()->status);
    }
}

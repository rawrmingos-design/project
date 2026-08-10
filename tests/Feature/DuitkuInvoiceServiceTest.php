<?php

namespace Tests\Unit;

use App\Models\Pembelian;
use App\Models\User;
use App\Services\Payments\DuitkuInvoiceService;
use App\Services\Payments\DuitkuPopClient;
use Duitku\Config;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DuitkuInvoiceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Use updateOrInsert instead of insert to handle SQLite /memory unique constraint on id=1 if already exists
        DB::table('setting_webs')->updateOrInsert(['id' => 1], [
            'judul_web' => 'Test Web',
            'deskripsi_web' => 'Test Desc',
            'keywords' => 'test',
            'url_wa' => '081234567890',
            'url_ig' => 'test',
            'url_tiktok' => 'test',
            'url_youtube' => 'test',
            'url_fb' => 'test',
            'topupindo_api' => 'test',
            'warna1' => '#000000',
            'warna2' => '#000000',
            'warna3' => '#000000',
            'warna4' => '#000000',
            'order_prefik' => 'TRX',
            'paydisini_apikey' => 'test',
            'tripay_api' => 'test',
            'tripay_merchant_code' => 'test',
            'tripay_private_key' => 'test',
            'vip_apiid' => 'test',
            'vip_apikey' => 'test',
            'duitku_merchant_code' => 'TEST-MERCHANT',
            'duitku_merchant_key' => 'test-secret',
            'duitku_mode' => 'sandbox',
            'duitku_callback_url' => 'https://example.com/callback',
            'duitku_return_url' => 'https://example.com/return',
        ]);
    }

    public function test_it_maps_payment_method_codes_correctly(): void
    {
        $mockClient = $this->createMock(DuitkuPopClient::class);
        $service = new DuitkuInvoiceService($mockClient);

        $this->assertEquals('SP', $service->mapPaymentMethodCode('QRIS'));
        $this->assertEquals('SA', $service->mapPaymentMethodCode('SHOPEEPAY'));
        $this->assertEquals('OV', $service->mapPaymentMethodCode('OVO'));
        $this->assertEquals('DA', $service->mapPaymentMethodCode('DANA'));
        $this->assertEquals('LA', $service->mapPaymentMethodCode('LINKAJA'));
        $this->assertEquals('BC', $service->mapPaymentMethodCode('BC'));
    }

    public function test_it_detects_direct_payment_methods(): void
    {
        $mockClient = $this->createMock(DuitkuPopClient::class);
        $service = new DuitkuInvoiceService($mockClient);

        $this->assertTrue($service->isDirectPaymentMethod('SP'));
        $this->assertTrue($service->isDirectPaymentMethod('BC'));
        $this->assertTrue($service->isDirectPaymentMethod('VA'));
        $this->assertFalse($service->isDirectPaymentMethod('DUITKU'));
        $this->assertFalse($service->isDirectPaymentMethod('ALFAMART'));
    }

    public function test_it_creates_invoice_with_payment_url(): void
    {
        $user = User::factory()->create(['no_wa' => '08123456789']);
        $order = new Pembelian([
            'order_id' => 'ORD-001',
            'harga' => 50000,
            'layanan' => 'Diamond ML',
            'nickname' => 'John Doe',
            'email_pembeli' => 'john@example.com',
        ]);
        $order->setRelation('user', $user);

        $mockClient = $this->createMock(DuitkuPopClient::class);
        $mockClient->expects($this->once())
            ->method('createInvoice')
            ->with($this->callback(function (array $params) {
                return $params['paymentAmount'] === 50000
                    && $params['merchantOrderId'] === 'DUITKU-ORD-001'
                    && $params['customerVaName'] === 'John Doe'
                    && $params['email'] === 'john@example.com'
                    && $params['phoneNumber'] === '08123456789'
                    && $params['paymentMethod'] === 'DUITKU';
            }))
            ->willReturn([
                'statusCode' => '00',
                'statusMessage' => 'SUCCESS',
                'reference' => 'REF-123',
                'paymentUrl' => 'https://sandbox.duitku.com/pay',
                'amount' => '50000',
            ]);

        $service = new DuitkuInvoiceService($mockClient);
        $result = $service->createForPembelian($order, 'DUITKU');

        $this->assertTrue($result['success']);
        $this->assertEquals('REF-123', $result['reference']);
        $this->assertEquals('https://sandbox.duitku.com/pay', $result['paymentUrl']);
        $this->assertEquals('https://sandbox.duitku.com/pay', $result['payment_url']);
        $this->assertEquals('https://sandbox.duitku.com/pay', $result['payment_value']);
        $this->assertEquals(50000, $result['amount']);
        $this->assertEquals('DUITKU-ORD-001', $result['merchant_order_id']);
    }

    public function test_it_creates_direct_invoice_with_qris(): void
    {
        $order = new Pembelian([
            'order_id' => 'ORD-002',
            'harga' => 15000,
            'layanan' => 'Diamond FF',
        ]);

        $mockClient = $this->createMock(DuitkuPopClient::class);
        $mockClient->expects($this->once())
            ->method('createDirectInvoice')
            ->with($this->callback(function (array $params) {
                return $params['paymentMethod'] === 'SP';
            }))
            ->willReturn([
                'statusCode' => '00',
                'statusMessage' => 'SUCCESS',
                'reference' => 'REF-456',
                'qrString' => '000201010211...',
            ]);

        $service = new DuitkuInvoiceService($mockClient);
        $result = $service->createForPembelian($order, 'QRIS');

        $this->assertTrue($result['success']);
        $this->assertEquals('REF-456', $result['reference']);
        $this->assertEquals('000201010211...', $result['qrString']);
        $this->assertEquals('000201010211...', $result['payment_value']);
        $this->assertEquals('SP', $result['duitku_payment_method']);
    }

    public function test_it_creates_direct_invoice_with_va(): void
    {
        $order = new Pembelian([
            'order_id' => 'ORD-003',
            'harga' => 20000,
            'layanan' => 'Voucher',
        ]);

        $mockClient = $this->createMock(DuitkuPopClient::class);
        $mockClient->expects($this->once())
            ->method('createDirectInvoice')
            ->with($this->callback(function (array $params) {
                return $params['paymentMethod'] === 'BC';
            }))
            ->willReturn([
                'statusCode' => '00',
                'statusMessage' => 'SUCCESS',
                'reference' => 'REF-789',
                'vaNumber' => '888800001234',
            ]);

        $service = new DuitkuInvoiceService($mockClient);
        $result = $service->createForPembelian($order, 'BC');

        $this->assertTrue($result['success']);
        $this->assertEquals('REF-789', $result['reference']);
        $this->assertEquals('888800001234', $result['vaNumber']);
        $this->assertEquals('888800001234', $result['payment_value']);
    }

    public function test_it_returns_failure_when_duitku_returns_error_status(): void
    {
        $order = new Pembelian([
            'order_id' => 'ORD-ERR',
            'harga' => 10000,
        ]);

        $mockClient = $this->createMock(DuitkuPopClient::class);
        $mockClient->method('createDirectInvoice')->willReturn([
            'statusCode' => '01',
            'statusMessage' => 'Invalid merchant code',
        ]);

        $service = new DuitkuInvoiceService($mockClient);
        $result = $service->createForPembelian($order, 'SP');

        $this->assertFalse($result['success']);
        $this->assertEquals('Invalid merchant code', $result['message']);
    }
}


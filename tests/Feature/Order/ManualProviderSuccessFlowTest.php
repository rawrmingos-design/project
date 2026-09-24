<?php

namespace Tests\Feature\Order;

use App\Jobs\SendPembelianToProviderJob;
use App\Models\Kategori;
use App\Models\Layanan;
use App\Models\Pembelian;
use App\Models\Pembayaran;
use App\Models\Provider;
use App\Services\ProviderRoutingService;
use App\Support\PembelianStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Alur order SUKSES lewat provider internal `manual`.
 *
 * Provider `manual` adalah jalur internal tanpa panggilan API provider:
 * OrderProcessingService mengembalikan success=true + status Sukses seketika.
 * Test ini mengunci kontrak itu supaya alur "order sukses" selalu bisa diuji
 * end-to-end tanpa kredensial provider sungguhan.
 *
 * Fokus pembuktian:
 *   1. Routing memilih provider `manual` untuk layanan tsb.
 *   2. Dispatch TIDAK memanggil API provider apa pun (tidak ada HTTP keluar).
 *   3. Status order berakhir `Sukses`, dan pembayaran tetap utuh.
 *   4. Perbaikan bug Digiflazz (deteksi error konfigurasi) TIDAK mengganggu
 *      jalur manual — hasilnya tetap Sukses, bukan Pending.
 */
class ManualProviderSuccessFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
        Http::fake(['*' => Http::response(['ok' => true], 200)]);

        Provider::query()->updateOrCreate(
            ['code' => 'manual'],
            ['name' => 'MANUAL', 'type' => 'manual', 'is_active' => 1, 'status' => 1],
        );
    }

    /** @return array{0: Layanan, 1: Pembelian} */
    private function fixtures(array $orderOverrides = []): array
    {
        $kategori = Kategori::factory()->create([
            'kode' => 'manual-test',
            'tipe' => 'game',
            'require_user_id' => true,
        ]);

        $layanan = Layanan::factory()->create([
            'kategori_id' => $kategori->id,
            'layanan' => '1 Diamonds (Test Manual)',
            // Jalur legacy: provider + provider_id di baris layanan itu sendiri.
            'provider' => 'manual',
            'provider_id' => 'ML_MANUAL_1',
            'harga' => 1000,
            'harga_member' => 1050,
            'harga_platinum' => 1040,
            'harga_gold' => 1030,
            'status' => 'available',
        ]);

        $order = Pembelian::query()->create(array_merge([
            'order_id' => 'EM260924000000MANUAL',
            'username' => '1840180550',
            'user_id' => '1840180550',
            'layanan' => '1 Diamonds (Test Manual)',
            'active_layanan_id' => $layanan->id,
            'active_provider_code' => 'manual',
            'active_provider_sku' => 'ML_MANUAL_1',
            'harga' => 1000,
            'profit' => 0,
            'status' => PembelianStatus::preferredDatabaseLabel(PembelianStatus::PENDING),
            'traffic_source' => 'telegram_gateway',
        ], $orderOverrides));

        Pembayaran::query()->create([
            'order_id' => $order->order_id,
            'harga' => '1000',
            'no_pembayaran' => 'TEST-PAY-0001',
            'no_pembeli' => '628123456789',
            'status' => 'Lunas',
            'metode' => 'MANUAL',
        ]);

        return [$layanan, $order];
    }

    public function test_routing_selects_manual_provider_for_the_test_service(): void
    {
        [$layanan] = $this->fixtures();

        $route = app(ProviderRoutingService::class)->findBestProvider($layanan);

        $this->assertNotNull($route, 'Layanan manual harus punya rute provider.');
        $this->assertSame('manual', $route['provider_code']);
        $this->assertSame('ML_MANUAL_1', $route['sku']);
        $this->assertSame('manual', $route['credentials']['type']);
    }

    public function test_manual_provider_order_becomes_success_without_calling_any_provider_api(): void
    {
        [, $order] = $this->fixtures();

        Http::fake(['*' => Http::response(['ok' => true], 200)]);

        // Jalankan job dispatch ASLI — bukan memanggil service langsung.
        (new SendPembelianToProviderJob($order->id))->handle(app(\App\Services\OrderProcessingService::class));

        $fresh = $order->fresh();

        $this->assertSame(
            PembelianStatus::preferredDatabaseLabel(PembelianStatus::SUCCESS),
            $fresh->status,
            'Order provider manual harus otomatis Sukses.',
        );

        // Provider manual = jalur internal: TIDAK boleh ada HTTP ke provider.
        Http::assertNothingSent();

        // Pembayaran tidak boleh diubah oleh dispatch provider.
        $this->assertSame('Lunas', (string) Pembayaran::query()->where('order_id', $order->order_id)->value('status'));
    }

    public function test_manual_provider_success_survives_digiflazz_configuration_error_guard(): void
    {
        // Guard error konfigurasi Digiflazz (rc=41) hanya boleh berlaku di
        // cabang digiflazz. Cabang manual tidak pernah mengevaluasinya.
        [, $order] = $this->fixtures();

        (new SendPembelianToProviderJob($order->id))->handle(app(\App\Services\OrderProcessingService::class));

        $fresh = $order->fresh();

        $this->assertSame(
            PembelianStatus::preferredDatabaseLabel(PembelianStatus::SUCCESS),
            $fresh->status,
        );
        $this->assertNotSame(
            PembelianStatus::preferredDatabaseLabel(PembelianStatus::PENDING),
            $fresh->status,
            'Jalur manual tidak boleh ikut jadi Pending karena guard Digiflazz.',
        );
    }

    public function test_manual_provider_order_is_visible_in_sender_transaction_list(): void
    {
        [, $order] = $this->fixtures(['gateway_principal' => 'telegram:6252007210']);

        (new SendPembelianToProviderJob($order->id))->handle(app(\App\Services\OrderProcessingService::class));

        $order->refresh();
        $this->assertSame(PembelianStatus::preferredDatabaseLabel(PembelianStatus::SUCCESS), $order->status);

        $list = app(\App\Services\Gateway\GatewayInvoiceService::class)
            ->senderOrdersForSender('telegram_gateway', 'telegram:6252007210', 1, 5);

        $this->assertGreaterThanOrEqual(1, $list['total'], 'Order manual harus muncul di daftar transaksi sender.');
        $this->assertSame(
            $order->order_id,
            (string) $list['items']->first()->order_id,
        );
    }
}

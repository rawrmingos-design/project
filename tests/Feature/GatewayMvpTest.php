<?php

namespace Tests\Feature;

use App\Models\CategoryType;
use App\Models\Kategori;
use App\Models\Layanan;
use App\Models\Method;
use App\Models\Pembayaran;
use App\Models\Pembelian;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GatewayMvpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    public function test_gateway_category_types_return_display_groups_before_categories(): void
    {
        $topUpGames = CategoryType::query()->create([
            'name' => '🎮Top Up Games',
            'slug' => 'top-up-games',
            'sort' => 1,
        ]);
        $pulsaData = CategoryType::query()->create([
            'name' => '📞Pulsa & Data',
            'slug' => 'pulsa-data',
            'sort' => 2,
        ]);
        $emptyType = CategoryType::query()->create([
            'name' => 'Empty Type',
            'slug' => 'empty-type',
            'sort' => 3,
        ]);

        $gameCategory = Kategori::factory()->create([
            'category_type_id' => $topUpGames->id,
            'kode' => 'mobile-legends',
            'status' => 'active',
        ]);
        $pulsaCategory = Kategori::factory()->create([
            'category_type_id' => $pulsaData->id,
            'kode' => 'telkomsel',
            'status' => 'active',
        ]);
        Kategori::factory()->create([
            'category_type_id' => $emptyType->id,
            'kode' => 'inactive-game',
            'status' => 'inactive',
        ]);

        Layanan::factory()->create([
            'kategori_id' => $gameCategory->id,
            'status' => 'available',
        ]);
        Layanan::factory()->create([
            'kategori_id' => $pulsaCategory->id,
            'status' => 'available',
        ]);

        $this->getJson('/api/gateway/category-types')
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('message', 'Tipe kategori berhasil dimuat.')
            ->assertJsonPath('data.0.slug', 'top-up-games')
            ->assertJsonPath('data.0.name', '🎮Top Up Games')
            ->assertJsonPath('data.0.category_count', 1)
            ->assertJsonPath('data.0.service_count', 1)
            ->assertJsonPath('data.1.slug', 'pulsa-data')
            ->assertJsonMissing(['slug' => 'empty-type']);

        $this->getJson('/api/gateway/category-types?q=pulsa')
            ->assertOk()
            ->assertJsonPath('data.0.slug', 'pulsa-data');
    }

    public function test_gateway_categories_return_active_categories_for_user_direction(): void
    {
        $topUpGames = CategoryType::query()->create([
            'name' => '🎮Top Up Games',
            'slug' => 'top-up-games',
            'sort' => 1,
        ]);
        $pulsaData = CategoryType::query()->create([
            'name' => '📞Pulsa & Data',
            'slug' => 'pulsa-data',
            'sort' => 2,
        ]);

        $activeCategory = Kategori::factory()->create([
            'category_type_id' => $topUpGames->id,
            'kode' => 'mobile-legends',
            'nama' => 'Mobile Legends',
            'sub_nama' => 'MLBB',
            'status' => 'active',
            'server_id' => true,
            'require_user_id' => true,
        ]);
        $pulsaCategory = Kategori::factory()->create([
            'category_type_id' => $pulsaData->id,
            'kode' => 'telkomsel',
            'nama' => 'Telkomsel',
            'status' => 'active',
        ]);
        $inactiveCategory = Kategori::factory()->create([
            'category_type_id' => $topUpGames->id,
            'kode' => 'inactive-game',
            'status' => 'inactive',
        ]);

        Layanan::factory()->create([
            'kategori_id' => $activeCategory->id,
            'status' => 'available',
        ]);
        Layanan::factory()->create([
            'kategori_id' => $activeCategory->id,
            'status' => 'unavailable',
        ]);
        Layanan::factory()->create([
            'kategori_id' => $pulsaCategory->id,
            'status' => 'available',
        ]);
        Layanan::factory()->create([
            'kategori_id' => $inactiveCategory->id,
            'status' => 'available',
        ]);

        $this->getJson('/api/gateway/categories')
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('message', 'Kategori berhasil dimuat.')
            ->assertJsonPath('data.0.code', 'mobile-legends')
            ->assertJsonPath('data.0.name', 'Mobile Legends')
            ->assertJsonPath('data.0.sub_name', 'MLBB')
            ->assertJsonPath('data.0.category_type.slug', 'top-up-games')
            ->assertJsonPath('data.0.requires_zone_id', true)
            ->assertJsonPath('data.0.service_count', 1)
            ->assertJsonMissing(['code' => 'inactive-game']);

        $this->getJson('/api/gateway/categories?type=top-up-games')
            ->assertOk()
            ->assertJsonPath('data.0.code', 'mobile-legends')
            ->assertJsonMissing(['code' => 'telkomsel']);

        $this->getJson('/api/gateway/categories?q=legend')
            ->assertOk()
            ->assertJsonPath('data.0.code', 'mobile-legends');
    }

    public function test_gateway_services_query_requires_category_parameter(): void
    {
        $category = Kategori::factory()->create([
            'kode' => 'mobile-legends',
            'nama' => 'Mobile Legends',
            'status' => 'active',
        ]);

        $service1 = Layanan::factory()->create([
            'kategori_id' => $category->id,
            'layanan' => '100 Diamonds',
            'harga_member' => 10000,
            'status' => 'available',
        ]);
        $service2 = Layanan::factory()->create([
            'kategori_id' => $category->id,
            'layanan' => '250 Diamonds',
            'harga_member' => 25000,
            'status' => 'available',
        ]);
        Layanan::factory()->create([
            'kategori_id' => $category->id,
            'layanan' => 'Unavailable Diamonds',
            'status' => 'unavailable',
        ]);

        $this->getJson('/api/gateway/services')
            ->assertOk()
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error_code', 'CATEGORY_REQUIRED')
            ->assertJsonPath('message', 'Query parameter "category" wajib diisi.');

        $this->getJson('/api/gateway/services?category=invalid')
            ->assertOk()
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error_code', 'CATEGORY_NOT_FOUND');

        $this->getJson('/api/gateway/services?category=mobile-legends')
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('message', 'Daftar layanan berhasil dimuat.')
            ->assertJsonPath('data.category.code', 'mobile-legends')
            ->assertJsonPath('data.services.0.service_id', $service1->id)
            ->assertJsonPath('data.services.1.service_id', $service2->id)
            ->assertJsonCount(2, 'data.services')
            ->assertJsonMissing(['name' => 'Unavailable Diamonds']);

        $this->getJson('/api/gateway/services?category=mobile-legends&service_id=' . $service2->id)
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('message', 'Layanan berhasil dimuat.')
            ->assertJsonPath('data.services.0.service_id', $service2->id)
            ->assertJsonPath('data.services.0.name', '250 Diamonds')
            ->assertJsonPath('data.services.0.price', 25000)
            ->assertJsonCount(1, 'data.services');

        $this->getJson('/api/gateway/services?category=mobile-legends&service_id=99999')
            ->assertOk()
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error_code', 'SERVICE_NOT_FOUND');

        $this->getJson('/api/gateway/services?category=mobile-legends&q=250')
            ->assertOk()
            ->assertJsonPath('data.services.0.service_id', $service2->id)
            ->assertJsonCount(1, 'data.services');
    }

    public function test_gateway_products_and_services_return_active_available_catalog(): void
    {
        $activeCategory = Kategori::factory()->create([
            'kode' => 'mobile-legends',
            'nama' => 'Mobile Legends',
            'status' => 'active',
            'server_id' => true,
            'require_user_id' => true,
        ]);
        $inactiveCategory = Kategori::factory()->create([
            'kode' => 'inactive-game',
            'status' => 'inactive',
        ]);

        $availableService = Layanan::factory()->create([
            'kategori_id' => $activeCategory->id,
            'layanan' => '100 Diamonds',
            'status' => 'available',
            'harga_member' => 10000,
        ]);
        Layanan::factory()->create([
            'kategori_id' => $activeCategory->id,
            'layanan' => 'Unavailable Diamonds',
            'status' => 'unavailable',
        ]);
        Layanan::factory()->create([
            'kategori_id' => $inactiveCategory->id,
            'status' => 'available',
        ]);

        $this->getJson('/api/gateway/products')
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.0.code', 'mobile-legends')
            ->assertJsonPath('data.0.service_count', 1)
            ->assertJsonMissing(['code' => 'inactive-game']);

        $this->getJson('/api/gateway/services?category=mobile-legends')
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.category.code', 'mobile-legends')
            ->assertJsonPath('data.services.0.service_id', $availableService->id)
            ->assertJsonPath('data.services.0.price', 10000)
            ->assertJsonMissing(['name' => 'Unavailable Diamonds']);
    }

    public function test_gateway_price_quotes_without_consuming_voucher_or_flash_stock(): void
    {
        [$service] = $this->createManualCheckoutFixtures([
            'harga_member' => 10000,
            'is_flash_sale' => true,
            'harga_flash_sale' => 8000,
            'stock_flash_sale' => 3,
            'expired_flash_sale' => now()->addHour(),
        ], [
            'fee_percent' => 10,
            'fix_fee' => 500,
        ]);

        $voucher = Voucher::query()->create([
            'kode' => 'DISC10',
            'promo' => 10,
            'max_potongan' => 1000,
            'mintrx' => 0,
            'stock' => 5,
            'expired_at' => now()->addDay(),
        ]);

        $this->postJson('/api/gateway/price', [
            'service_id' => $service->id,
            'payment_method' => 'MANUAL',
            'voucher' => 'DISC10',
        ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.base_amount', 8000)
            ->assertJsonPath('data.discount', 800)
            ->assertJsonPath('data.payment_fee', 1220)
            ->assertJsonPath('data.total_amount', 8420)
            ->assertJsonPath('data.flash_sale_applied', true);

        $this->assertSame(5, $voucher->fresh()->stock);
        $this->assertSame(3, $service->fresh()->stock_flash_sale);
    }

    public function test_gateway_check_id_skips_non_game_categories(): void
    {
        Kategori::factory()->create([
            'kode' => 'voucher-game',
            'tipe' => 'voucher',
            'status' => 'active',
        ]);

        $this->postJson('/api/gateway/check-id', [
            'category_code' => 'voucher-game',
            'uid' => 'CUSTOM_UID',
        ])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.skip_check', true)
            ->assertJsonPath('data.valid', null);

        Http::assertNothingSent();
    }

    public function test_gateway_invoice_create_status_and_idempotency_are_source_scoped(): void
    {
        [$service] = $this->createManualCheckoutFixtures();

        $payload = [
            'service' => $service->id,
            'payment_method' => 'MANUAL',
            'nomor' => '081234567890',
            'uid' => '123456',
            'zone' => '1234',
            'nickname' => 'Gateway Nick',
            'external_user_id' => 'user-1',
            'idempotency_key' => 'message-1',
        ];

        $first = $this->postJson('/api/gateway/invoices', $payload + [
            'source' => 'whatsapp_gateway',
        ])->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.status', true)
            ->json('data.order_id');

        $second = $this->postJson('/api/gateway/invoices', $payload + [
            'source' => 'whatsapp_gateway',
        ])->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.message', 'Order sudah diproses sebelumnya.')
            ->json('data.order_id');

        $telegram = $this->postJson('/api/gateway/invoices', $payload + [
            'source' => 'telegram_gateway',
        ])->assertOk()
            ->assertJsonPath('ok', true)
            ->json('data.order_id');

        $this->assertSame($first, $second);
        $this->assertNotSame($first, $telegram);
        $this->assertSame(2, Pembelian::query()->count());

        $order = Pembelian::query()->where('order_id', $first)->firstOrFail();
        $payment = Pembayaran::query()->where('order_id', $first)->firstOrFail();
        $log = json_decode((string) $order->log, true);

        $this->assertSame('whatsapp_gateway', $order->traffic_source);
        $this->assertSame('Gateway Nick', $order->nickname);
        $this->assertSame('081234567890', $payment->no_pembeli);
        $this->assertSame('whatsapp_gateway_checkout', $log['source']);
        $this->assertSame('user-1', $log['gateway_context']['external_user_id']);

        $this->getJson('/api/gateway/invoices/' . $first . '?source=whatsapp_gateway&external_user_id=user-1')
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.order_id', $first)
            ->assertJsonPath('data.payment.method', 'MANUAL');

        $this->getJson('/api/gateway/invoices/' . $first . '?source=whatsapp_gateway&external_user_id=other-user')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['order_id']);
    }

    public function test_gateway_payment_methods_return_visible_methods_for_tenant(): void
    {
        Method::query()->create([
            'name' => 'QRIS',
            'code' => 'QRIS',
            'payment' => 'duitku',
            'tipe' => 'qris',
            'images' => '/images/qris.png',
            'keterangan' => 'Bayar dengan QRIS scan',
            'fee_percent' => 0.7,
            'fix_fee' => 0,
            'min_pembelian' => 10000,
            'max_pembelian' => 10000000,
            'statuspayment' => 1,
        ]);

        Method::query()->create([
            'name' => 'SALDO',
            'code' => 'SALDO',
            'payment' => 'saldo',
            'tipe' => 'saldo',
            'images' => '/images/saldo.png',
            'keterangan' => 'Bayar dengan saldo akun',
            'fee_percent' => 0,
            'fix_fee' => 0,
            'statuspayment' => 1,
        ]);

        Method::query()->create([
            'name' => 'Disabled Method',
            'code' => 'DISABLED',
            'payment' => 'manual',
            'tipe' => 'manual',
            'images' => '/images/disabled.png',
            'keterangan' => 'Disabled payment method',
            'statuspayment' => 0,
        ]);

        $this->getJson('/api/gateway/payment-methods')
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('message', 'Metode pembayaran berhasil dimuat.')
            ->assertJsonPath('data.0.code', 'QRIS')
            ->assertJsonPath('data.0.name', 'QRIS')
            ->assertJsonPath('data.0.type', 'qris')
            ->assertJsonPath('data.0.description', 'Bayar dengan QRIS scan')
            ->assertJsonPath('data.0.fee.percent', 0.7)
            ->assertJsonPath('data.0.fee.fixed', 0)
            ->assertJsonPath('data.0.limits.min', 10000)
            ->assertJsonPath('data.0.limits.max', 10000000)
            ->assertJsonMissing(['code' => 'SALDO'])
            ->assertJsonMissing(['code' => 'DISABLED']);
    }

    public function test_gateway_service_detail_by_id_returns_single_service(): void
    {
        $category = Kategori::factory()->create([
            'kode' => 'mobile-legends',
            'nama' => 'Mobile Legends',
            'status' => 'active',
        ]);

        $service = Layanan::factory()->create([
            'kategori_id' => $category->id,
            'layanan' => '100 Diamonds',
            'harga_member' => 10000,
            'status' => 'available',
        ]);

        $this->getJson('/api/gateway/services/' . $service->id)
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.service_id', $service->id)
            ->assertJsonPath('data.name', '100 Diamonds')
            ->assertJsonPath('data.price', 10000)
            ->assertJsonPath('data.category.code', 'mobile-legends')
            ->assertJsonPath('data.category.name', 'Mobile Legends');

        $this->getJson('/api/gateway/services/99999')
            ->assertStatus(404)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error_code', 'SERVICE_NOT_FOUND');
    }

    public function test_gateway_voucher_validation_checks_stock_expiry_and_min_transaction(): void
    {
        $validVoucher = Voucher::query()->create([
            'kode' => 'DISC10',
            'promo' => 10,
            'max_potongan' => 1000,
            'mintrx' => 50000,
            'stock' => 5,
            'expired_at' => now()->addDay(),
        ]);

        $expiredVoucher = Voucher::query()->create([
            'kode' => 'EXPIRED',
            'promo' => 10,
            'max_potongan' => 1000,
            'mintrx' => 0,
            'stock' => 5,
            'expired_at' => now()->subDay(),
        ]);

        $this->postJson('/api/gateway/vouchers/validate', [
            'code' => 'DISC10',
        ])->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.code', 'DISC10')
            ->assertJsonPath('data.valid', true)
            ->assertJsonPath('data.min_transaction', 50000);

        $this->postJson('/api/gateway/vouchers/validate', [
            'code' => 'DISC10',
            'amount' => 100000,
        ])->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('data.valid', true)
            ->assertJsonPath('data.estimated_discount', 1000);

        $this->postJson('/api/gateway/vouchers/validate', [
            'code' => 'DISC10',
            'amount' => 10000,
        ])->assertStatus(422)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error_code', 'MIN_TRANSACTION_NOT_MET')
            ->assertJsonPath('data.valid', false);

        $this->postJson('/api/gateway/vouchers/validate', [
            'code' => 'EXPIRED',
        ])->assertStatus(422)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error_code', 'VOUCHER_EXPIRED');

        $this->postJson('/api/gateway/vouchers/validate', [
            'code' => 'INVALID',
        ])->assertStatus(404)
            ->assertJsonPath('ok', false)
            ->assertJsonPath('error_code', 'VOUCHER_NOT_FOUND');
    }

    private function createManualCheckoutFixtures(array $serviceOverrides = [], array $methodOverrides = []): array
    {
        $category = Kategori::factory()->create([
            'kode' => 'mobile-legends',
            'tipe' => 'game',
            'require_user_id' => true,
        ]);

        $service = Layanan::factory()->create(array_merge([
            'kategori_id' => $category->id,
            'layanan' => '100 Diamonds',
            'provider' => 'manual',
            'provider_id' => 'ml-100',
            'harga_member' => 10000,
            'harga_platinum' => 10000,
            'harga_gold' => 10000,
            'profit_member' => 1000,
            'profit_platinum' => 1000,
            'profit_gold' => 1000,
        ], $serviceOverrides));

        $method = Method::query()->create(array_merge([
            'name' => 'Manual Transfer',
            'code' => 'MANUAL',
            'payment' => 'manual',
            'tipe' => 'manual',
            'images' => 'manual.png',
            'keterangan' => 'Manual transfer desc',
            'fee_percent' => 0,
            'fix_fee' => 0,
            'statuspayment' => 1,
        ], $methodOverrides));

        return [$service, $method];
    }
}

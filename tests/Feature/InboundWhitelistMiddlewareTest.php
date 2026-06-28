<?php

namespace Tests\Feature;

use App\Models\Deposit;
use App\Models\InboundSourceEntry;
use App\Models\InboundSourcePolicy;
use App\Models\Pembayaran;
use App\Models\Pembelian;
use App\Models\SettingWeb;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InboundWhitelistMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_tokopay_callback_is_denied_by_middleware_in_enforce_mode_without_mutating_payment(): void
    {
        $settings = $this->createSettings();
        $user = User::factory()->create([
            'username' => 'tokopay-deny-user',
            'balance' => 10_000,
        ]);

        Deposit::query()->create([
            'order_id' => 'DEP-TOKOPAY-DENY-001',
            'username' => $user->username,
            'metode' => 'QRIS',
            'no_pembayaran' => 'TOKOPAY-VA-DENY-001',
            'jumlah' => 50_000,
            'status' => 'Pending',
        ]);

        Pembayaran::query()->create([
            'order_id' => 'DEP-TOKOPAY-DENY-001',
            'harga' => '50000',
            'no_pembayaran' => 'TOKOPAY-VA-DENY-001',
            'no_pembeli' => '081234567890',
            'status' => 'Belum Lunas',
            'metode' => 'TOKOPAY',
            'reference' => 'TP-DENY-REF-001',
        ]);

        $this->createPolicy('payment_gateway', 'tokopay', 'enforce', ['203.0.113.10']);

        $payload = [
            'status' => 'Success',
            'reference' => 'TP-DENY-REF-001',
            'reff_id' => 'DEP-TOKOPAY-DENY-001',
            'signature' => md5($settings->tokopay_merchant_id . ':' . $settings->tokopay_secret_key . ':DEP-TOKOPAY-DENY-001'),
        ];

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.10'])
            ->postJson('/wejizy/tokopay/callback', $payload)
            ->assertStatus(403)
            ->assertJson(['message' => 'Forbidden']);

        $this->assertDatabaseHas('deposits', [
            'order_id' => 'DEP-TOKOPAY-DENY-001',
            'status' => 'Pending',
        ]);
        $this->assertDatabaseHas('pembayarans', [
            'order_id' => 'DEP-TOKOPAY-DENY-001',
            'status' => 'Belum Lunas',
        ]);
        $this->assertSame(10_000, (int) $user->fresh()->balance);
    }

    public function test_tokopay_callback_reaches_controller_in_log_only_mode_and_returns_signature_error(): void
    {
        $this->createSettings();
        $this->createPolicy('payment_gateway', 'tokopay', 'log_only', ['203.0.113.10']);

        $payload = [
            'status' => 'Success',
            'reference' => 'TP-LOG-ONLY-REF-001',
            'reff_id' => 'DEP-TOKOPAY-LOG-001',
            'signature' => 'invalid-signature',
        ];

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.10'])
            ->postJson('/wejizy/tokopay/callback', $payload)
            ->assertStatus(401)
            ->assertJson([
                'status' => false,
                'message' => 'Invalid Signature',
            ]);
    }

    public function test_digiflazz_callback_is_denied_by_middleware_in_enforce_mode_without_mutating_order(): void
    {
        config(['providers.digiflazz.webhook_secret' => 'digiflazz-secret']);

        $user = User::factory()->create([
            'username' => 'digiflazz-deny-user',
        ]);

        Pembelian::query()->create([
            'order_id' => 'INV-DIGI-DENY-001',
            'username' => $user->username,
            'user_id' => '12345678',
            'zone' => '2001',
            'nickname' => 'Whitelist User',
            'layanan' => 'ML 86',
            'harga' => 15000,
            'profit' => 1000,
            'provider_order_id' => 'TRX-OLD',
            'status' => 'Pending',
            'tipe_transaksi' => 'game',
        ]);

        Pembayaran::query()->create([
            'order_id' => 'INV-DIGI-DENY-001',
            'harga' => '15000',
            'no_pembayaran' => '08123456789',
            'no_pembeli' => '08123456789',
            'status' => 'Lunas',
            'metode' => 'QRIS',
            'reference' => 'REF-INV-DIGI-DENY-001',
        ]);

        $this->createPolicy('supplier_callback', 'digiflazz', 'enforce', ['203.0.113.10']);

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.10'])
            ->withHeaders([
                'X-Hub-Signature' => 'sha1=invalid',
            ])
            ->postJson('/wejizy/digi/payload', [
                'data' => [
                    'ref_id' => 'INV-DIGI-DENY-001',
                    'status' => 'Sukses',
                ],
            ])
            ->assertStatus(403)
            ->assertJson(['message' => 'Forbidden']);

        $this->assertDatabaseHas('pembelians', [
            'order_id' => 'INV-DIGI-DENY-001',
            'status' => 'Pending',
            'provider_order_id' => 'TRX-OLD',
        ]);
    }

    public function test_digiflazz_callback_reaches_controller_in_log_only_mode_and_returns_signature_error(): void
    {
        config(['providers.digiflazz.webhook_secret' => 'digiflazz-secret']);
        $this->createPolicy('supplier_callback', 'digiflazz', 'log_only', ['203.0.113.10']);

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.10'])
            ->withHeaders([
                'X-Hub-Signature' => 'sha1=invalid',
            ])
            ->postJson('/wejizy/digi/payload', [
                'data' => [
                    'ref_id' => 'INV-DIGI-LOG-001',
                    'status' => 'Sukses',
                ],
            ])
            ->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Invalid signature',
            ]);
    }

    public function test_apigames_get_and_post_routes_share_the_same_policy(): void
    {
        $this->createPolicy('supplier_callback', 'apigames', 'enforce', ['203.0.113.10']);

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.10'])
            ->get('/wejizy/apigames/callback?ref_id=INV-GET-001')
            ->assertStatus(403)
            ->assertJson(['message' => 'Forbidden']);

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.10'])
            ->postJson('/wejizy/apigames/callback', ['ref_id' => 'INV-POST-001'])
            ->assertStatus(403)
            ->assertJson(['message' => 'Forbidden']);
    }

    public function test_generic_webhook_route_uses_route_parameter_source_name(): void
    {
        $this->createPolicy('supplier_callback', 'mystery-supplier', 'enforce', ['203.0.113.10']);

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.10'])
            ->postJson('/api/webhooks/mystery-supplier', [])
            ->assertStatus(403)
            ->assertJson(['message' => 'Forbidden']);
    }

    public function test_callback_routes_are_bound_to_inbound_whitelist_middleware(): void
    {
        $this->createSettings();

        $routes = collect(app('router')->getRoutes()->getRoutes());

        $expectations = [
            ['uri' => 'wejizy/digi/payload', 'method' => 'POST', 'middleware' => 'inbound.whitelist:supplier_callback,digiflazz,log_only'],
            ['uri' => 'wejizy/vip/callback', 'method' => 'POST', 'middleware' => 'inbound.whitelist:supplier_callback,vip,log_only'],
            ['uri' => 'wejizy/apigames/callback', 'method' => 'GET', 'middleware' => 'inbound.whitelist:supplier_callback,apigames,log_only'],
            ['uri' => 'wejizy/apigames/callback', 'method' => 'POST', 'middleware' => 'inbound.whitelist:supplier_callback,apigames,log_only'],
            ['uri' => 'wejizy/tokopay/callback', 'method' => 'POST', 'middleware' => 'inbound.whitelist:payment_gateway,tokopay,log_only'],
            ['uri' => 'wejizy/tripay/callback', 'method' => 'POST', 'middleware' => 'inbound.whitelist:payment_gateway,tripay,log_only'],
            ['uri' => 'wejizy/paydisini/callback', 'method' => 'POST', 'middleware' => 'inbound.whitelist:payment_gateway,paydisini,log_only'],
            ['uri' => 'wejizy/duitku/callback', 'method' => 'POST', 'middleware' => 'inbound.whitelist:payment_gateway,duitku,log_only'],
            ['uri' => 'api/webhooks/digiflazz', 'method' => 'POST', 'middleware' => 'inbound.whitelist:supplier_callback,digiflazz,log_only'],
            ['uri' => 'api/webhooks/bangjeff', 'method' => 'POST', 'middleware' => 'inbound.whitelist:supplier_callback,bangjeff,log_only'],
            ['uri' => 'api/webhooks/topupedia', 'method' => 'POST', 'middleware' => 'inbound.whitelist:supplier_callback,topupedia,log_only'],
            ['uri' => 'api/webhooks/apigames', 'method' => 'POST', 'middleware' => 'inbound.whitelist:supplier_callback,apigames,log_only'],
            ['uri' => 'api/webhooks/{provider}', 'method' => 'POST', 'middleware' => 'inbound.whitelist:supplier_callback,@provider,log_only'],
        ];

        foreach ($expectations as $expectation) {
            $route = $routes->first(function ($route) use ($expectation) {
                return $route->uri() === $expectation['uri']
                    && in_array($expectation['method'], $route->methods(), true);
            });

            $this->assertNotNull($route, sprintf('Route %s %s was not found.', $expectation['method'], $expectation['uri']));
            $this->assertContains(
                $expectation['middleware'],
                $route->gatherMiddleware(),
                sprintf('Route %s %s is missing middleware %s.', $expectation['method'], $expectation['uri'], $expectation['middleware'])
            );
        }
    }

    private function createPolicy(string $domain, string $name, string $mode, array $entries): InboundSourcePolicy
    {
        $policy = InboundSourcePolicy::query()->create([
            'source_domain' => $domain,
            'source_name' => $name,
            'route_scope' => sprintf('%s:%s', $domain, $name),
            'mode' => $mode,
            'is_active' => true,
        ]);

        foreach ($entries as $entry) {
            InboundSourceEntry::query()->create([
                'policy_id' => $policy->id,
                'value' => $entry,
                'value_type' => str_contains($entry, '/') ? 'cidr_v4' : 'ipv4',
                'is_active' => true,
            ]);
        }

        return $policy;
    }

    private function createSettings(): SettingWeb
    {
        return SettingWeb::query()->create([
            'id' => 1,
            'judul_web' => 'Test Web',
            'deskripsi_web' => 'Test Description',
            'keywords' => 'test',
            'url_wa' => 'https://wa.me/628123456789',
            'url_ig' => 'https://instagram.com/test',
            'url_tiktok' => 'https://tiktok.com/@test',
            'url_youtube' => 'https://youtube.com/test',
            'url_fb' => 'https://facebook.com/test',
            'topupindo_api' => 'topupindo-test',
            'warna1' => '#111111',
            'warna2' => '#222222',
            'warna3' => '#333333',
            'warna4' => '#444444',
            'paydisini_apikey' => 'paydisini-test-key',
            'order_prefik' => 'INV',
            'tokopay_merchant_id' => 'M123456TEST',
            'tokopay_secret_key' => 'tokopay-secret-test',
            'tripay_private_key' => 'tripay-private-test-key',
            'username_digi' => 'test_digi',
            'api_key_digi' => 'test_digi_key',
            'apigames_secret' => 'secret-123',
            'apigames_merchant' => 'merchant-123',
            'vip_apiid' => 'vip-id',
            'vip_apikey' => 'vip-key',
        ]);
    }
}

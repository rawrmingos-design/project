<?php

namespace Tests\Feature;

use App\Models\Deposit;
use App\Models\Pembayaran;
use App\Models\Pembelian;
use App\Models\User;
use App\Services\EmailNotificationService;
use App\Services\OrderProcessingService;
use App\Services\WhatsappNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PaydisiniCallbackTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedSettingWeb([
            'paydisini_apikey' => 'paydisini-test-key',
        ]);
    }

    public function test_callback_rejects_invalid_api_key(): void
    {
        $response = $this->postJson('/wejizy/paydisini/callback', [
            'key' => 'wrong-key',
            'pay_id' => 'PAY-001',
            'unique_code' => 'INV-PAYDISINI-001',
            'status' => 'success',
            'signature' => md5('paydisini-test-key' . 'INV-PAYDISINI-001' . 'CallbackStatus'),
        ]);

        $response
            ->assertStatus(400)
            ->assertJsonPath('message', 'Invalid API Key');
    }

    public function test_callback_rejects_invalid_signature(): void
    {
        $response = $this->postJson('/wejizy/paydisini/callback', [
            'key' => 'paydisini-test-key',
            'pay_id' => 'PAY-002',
            'unique_code' => 'INV-PAYDISINI-002',
            'status' => 'success',
            'signature' => 'invalid-signature',
        ]);

        $response
            ->assertStatus(400)
            ->assertJsonPath('message', 'Invalid signature');
    }

    public function test_callback_acknowledges_missing_invoice_when_signature_is_valid(): void
    {
        $uniqueCode = 'INV-PAYDISINI-MISSING';

        $response = $this->postJson('/wejizy/paydisini/callback', [
            'key' => 'paydisini-test-key',
            'pay_id' => 'PAY-003',
            'unique_code' => $uniqueCode,
            'status' => 'success',
            'signature' => md5('paydisini-test-key' . $uniqueCode . 'CallbackStatus'),
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'ignored_transaction_not_found');
    }

    public function test_success_callback_marks_unpaid_invoice_as_paid(): void
    {
        $invoice = $this->createInvoice([
            'order_id' => 'INV-PAYDISINI-PAID',
            'status' => 'Belum Lunas',
        ]);

        $response = $this->postJson('/wejizy/paydisini/callback', [
            'key' => 'paydisini-test-key',
            'pay_id' => 'PAY-004',
            'unique_code' => $invoice->order_id,
            'status' => 'success',
            'signature' => md5('paydisini-test-key' . $invoice->order_id . 'CallbackStatus'),
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonMissingPath('message');

        $invoice->refresh();
        $this->assertSame('Lunas', $invoice->status);
        $this->assertNotNull($invoice->paid_at);
    }

    public function test_canceled_callback_marks_unpaid_invoice_as_expired(): void
    {
        $invoice = $this->createInvoice([
            'order_id' => 'INV-PAYDISINI-CANCELED',
            'status' => 'Belum Lunas',
        ]);

        $response = $this->postJson('/wejizy/paydisini/callback', [
            'key' => 'paydisini-test-key',
            'pay_id' => 'PAY-005',
            'unique_code' => $invoice->order_id,
            'status' => 'canceled',
            'signature' => md5('paydisini-test-key' . $invoice->order_id . 'CallbackStatus'),
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonMissingPath('message');

        $invoice->refresh();
        $this->assertSame('Expired', $invoice->status);
    }

    public function test_already_processed_invoice_returns_idempotent_ack(): void
    {
        $invoice = $this->createInvoice([
            'order_id' => 'INV-PAYDISINI-ALREADY-PAID',
            'status' => 'Lunas',
            'paid_at' => now()->subMinute(),
        ]);

        $response = $this->postJson('/wejizy/paydisini/callback', [
            'key' => 'paydisini-test-key',
            'pay_id' => 'PAY-006',
            'unique_code' => $invoice->order_id,
            'status' => 'success',
            'signature' => md5('paydisini-test-key' . $invoice->order_id . 'CallbackStatus'),
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'already_processed');

        $invoice->refresh();
        $this->assertSame('Lunas', $invoice->status);
    }

    public function test_success_callback_marks_pending_deposit_success_and_increments_balance(): void
    {
        $user = $this->createUser(username: 'deposit-paydisini-user', balance: 1000);
        $invoice = $this->createInvoice([
            'order_id' => 'INV-PAYDISINI-DEPOSIT-SUCCESS',
            'status' => 'Belum Lunas',
        ]);
        $deposit = $this->createDeposit($user, [
            'order_id' => $invoice->order_id,
            'jumlah' => 25000,
            'status' => 'Pending',
        ]);

        $response = $this->postJson('/wejizy/paydisini/callback', [
            'key' => 'paydisini-test-key',
            'pay_id' => 'PAY-007',
            'unique_code' => $invoice->order_id,
            'status' => 'success',
            'signature' => md5('paydisini-test-key' . $invoice->order_id . 'CallbackStatus'),
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true);

        $invoice->refresh();
        $deposit->refresh();
        $user->refresh();

        $this->assertSame('Lunas', $invoice->status);
        $this->assertSame('Success', $deposit->status);
        $this->assertSame(26000, (int) $user->balance);
    }

    public function test_repeated_success_callback_does_not_double_increment_deposit_balance(): void
    {
        $user = $this->createUser(username: 'deposit-paydisini-repeat', balance: 500);
        $invoice = $this->createInvoice([
            'order_id' => 'INV-PAYDISINI-DEPOSIT-REPEAT',
            'status' => 'Belum Lunas',
        ]);
        $deposit = $this->createDeposit($user, [
            'order_id' => $invoice->order_id,
            'jumlah' => 10000,
            'status' => 'Pending',
        ]);

        $payload = [
            'key' => 'paydisini-test-key',
            'pay_id' => 'PAY-008',
            'unique_code' => $invoice->order_id,
            'status' => 'success',
            'signature' => md5('paydisini-test-key' . $invoice->order_id . 'CallbackStatus'),
        ];

        $this->postJson('/wejizy/paydisini/callback', $payload)->assertOk();
        $this->postJson('/wejizy/paydisini/callback', $payload)
            ->assertOk()
            ->assertJsonPath('message', 'already_processed');

        $invoice->refresh();
        $deposit->refresh();
        $user->refresh();

        $this->assertSame('Lunas', $invoice->status);
        $this->assertSame('Success', $deposit->status);
        $this->assertSame(10500, (int) $user->balance);
    }

    public function test_success_callback_updates_pembelian_for_mocked_provider_success(): void
    {
        $user = $this->createUser(username: 'order-paydisini-success');
        $invoice = $this->createInvoice([
            'order_id' => 'INV-PAYDISINI-ORDER-SUCCESS',
            'status' => 'Belum Lunas',
            'harga' => '43000',
        ]);
        $pembelian = $this->createPembelian($user, [
            'order_id' => $invoice->order_id,
            'harga' => 43000,
            'status' => 'Pending',
            'keterangan_sn' => null,
        ]);

        $this->bindNotificationStubs();
        $this->app->instance(OrderProcessingService::class, new class {
            public function process($pembelian, string $dispatchMode = 'auto'): array
            {
                return [
                    'success' => true,
                    'order_status' => 'Success',
                    'transaction_id' => 'PD-SUCCESS-123',
                    'message' => 'Order accepted',
                    'sn' => 'PD-SN-123',
                ];
            }
        });

        $response = $this->postPaydisiniSuccessCallback($invoice->order_id, 'PAY-009');

        $response->assertOk()->assertJsonPath('success', true);

        $invoice->refresh();
        $pembelian->refresh();

        $this->assertSame('Lunas', $invoice->status);
        $this->assertSame('Sukses', $pembelian->status);
        $this->assertSame('PD-SUCCESS-123', $pembelian->provider_order_id);
        $this->assertSame('PD-SN-123', $pembelian->keterangan_sn);
        $this->assertStringContainsString('PD-SUCCESS-123', (string) $pembelian->log);
    }

    public function test_success_callback_updates_pembelian_for_mocked_provider_failed_result(): void
    {
        $user = $this->createUser(username: 'order-paydisini-failed');
        $invoice = $this->createInvoice([
            'order_id' => 'INV-PAYDISINI-ORDER-FAILED',
            'status' => 'Belum Lunas',
            'harga' => '44000',
        ]);
        $pembelian = $this->createPembelian($user, [
            'order_id' => $invoice->order_id,
            'harga' => 44000,
            'status' => 'Pending',
            'keterangan_sn' => 'PD-SN-LAMA',
        ]);

        $this->bindNotificationStubs();
        $this->app->instance(OrderProcessingService::class, new class {
            public function process($pembelian, string $dispatchMode = 'auto'): array
            {
                return [
                    'success' => false,
                    'order_status' => 'Failed',
                    'transaction_id' => 'PD-FAILED-123',
                    'message' => 'Provider failed',
                    'sn' => null,
                ];
            }
        });

        $response = $this->postPaydisiniSuccessCallback($invoice->order_id, 'PAY-010');

        $response->assertOk()->assertJsonPath('success', true);

        $invoice->refresh();
        $pembelian->refresh();

        $this->assertSame('Lunas', $invoice->status);
        $this->assertSame('Gagal', $pembelian->status);
        $this->assertSame('PD-FAILED-123', $pembelian->provider_order_id);
        $this->assertSame('PD-SN-LAMA', $pembelian->keterangan_sn);
        $this->assertStringContainsString('Provider failed', (string) $pembelian->log);
    }

    public function test_success_callback_only_processes_pembelian_once_after_invoice_is_claimed(): void
    {
        $user = $this->createUser(username: 'order-paydisini-idempotent');
        $invoice = $this->createInvoice([
            'order_id' => 'INV-PAYDISINI-ORDER-IDEMPOTENT',
            'status' => 'Belum Lunas',
            'harga' => '46000',
        ]);
        $pembelian = $this->createPembelian($user, [
            'order_id' => $invoice->order_id,
            'harga' => 46000,
            'status' => 'Pending',
        ]);

        $this->bindNotificationStubs();

        $processor = new class {
            public int $calls = 0;
            public function process($pembelian, string $dispatchMode = 'auto'): array
            {
                $this->calls++;
                return [
                    'success' => true,
                    'order_status' => 'Success',
                    'transaction_id' => 'PD-IDEMPOTENT-123',
                    'message' => 'Order accepted once',
                    'sn' => 'PD-SN-IDEMPOTENT',
                ];
            }
        };
        $this->app->instance(OrderProcessingService::class, $processor);

        $this->postPaydisiniSuccessCallback($invoice->order_id, 'PAY-011')
            ->assertOk()
            ->assertJsonPath('success', true);

        $secondResponse = $this->postPaydisiniSuccessCallback($invoice->order_id, 'PAY-011');
        $secondResponse
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'already_processed');

        $invoice->refresh();
        $pembelian->refresh();

        $this->assertSame(1, $processor->calls);
        $this->assertSame('Lunas', $invoice->status);
        $this->assertSame('Sukses', $pembelian->status);
        $this->assertSame('PD-IDEMPOTENT-123', $pembelian->provider_order_id);
        $this->assertSame('PD-SN-IDEMPOTENT', $pembelian->keterangan_sn);
    }

    private function createInvoice(array $overrides = []): Pembayaran
    {
        return Pembayaran::create(array_merge([
            'order_id' => 'INV-PAYDISINI-DEFAULT',
            'harga' => '15000',
            'no_pembayaran' => '08123456789',
            'no_pembeli' => '08123456789',
            'status' => 'Belum Lunas',
            'metode' => 'Paydisini',
            'reference' => null,
        ], $overrides));
    }

    private function createUser(string $username, int $balance = 0): User
    {
        return User::create([
            'name' => 'Deposit User',
            'username' => $username,
            'email' => $username . '@example.com',
            'password' => bcrypt('password'),
            'role' => 'Member',
            'balance' => $balance,
            'point_balance' => 0,
            'email_verified_at' => now(),
        ]);
    }

    private function createDeposit(User $user, array $overrides = []): Deposit
    {
        return Deposit::create(array_merge([
            'order_id' => 'DEP-PAYDISINI-DEFAULT',
            'username' => $user->username,
            'metode' => 'Paydisini',
            'no_pembayaran' => '08123456789',
            'jumlah' => 15000,
            'status' => 'Pending',
        ], $overrides));
    }

    private function createPembelian(User $user, array $overrides = []): Pembelian
    {
        return Pembelian::create(array_merge([
            'order_id' => 'ORDER-PAYDISINI-DEFAULT',
            'username' => $user->username,
            'user_id' => '123456789',
            'zone' => '1',
            'nickname' => 'Tester',
            'layanan' => 'Mock Service',
            'harga' => 15000,
            'profit' => 1000,
            'status' => 'Pending',
            'provider_order_id' => null,
            'keterangan_sn' => null,
            'email_pembeli' => $user->email,
        ], $overrides));
    }

    private function postPaydisiniSuccessCallback(string $orderId, string $payId)
    {
        return $this->postJson('/wejizy/paydisini/callback', [
            'key' => 'paydisini-test-key',
            'pay_id' => $payId,
            'unique_code' => $orderId,
            'status' => 'success',
            'signature' => md5('paydisini-test-key' . $orderId . 'CallbackStatus'),
        ]);
    }

    private function bindNotificationStubs(): void
    {
        $this->app->instance(WhatsappNotificationService::class, new class {
            public function sendNotification(string $target, string $templateSlug, array $data = []): array
            {
                return ['success' => true, 'message' => 'stubbed'];
            }
        });

        $this->app->instance(EmailNotificationService::class, new class {
            public function sendTransactionEmail($email, $data): bool
            {
                return true;
            }
        });
    }

    private function seedSettingWeb(array $overrides = []): void
    {
        DB::table('setting_webs')->insert(array_merge([
            'id' => 1,
            'judul_web' => 'Demo Store',
            'deskripsi_web' => 'Demo description',
            'keywords' => 'demo,store',
            'logo_header' => null,
            'logo_footer' => null,
            'logo_favicon' => null,
            'url_wa' => 'https://wa.me/620000000000',
            'url_ig' => 'https://instagram.com/demo',
            'url_tiktok' => 'https://tiktok.com/@demo',
            'url_youtube' => 'https://youtube.com/@demo',
            'url_fb' => 'https://facebook.com/demo',
            'topupindo_api' => 'demo-topupindo-api',
            'apikey_bangjeff' => null,
            'apikey_aoshi' => null,
            'api_mobilegamestore' => null,
            'warna1' => '#111111',
            'warna2' => '#222222',
            'warna3' => '#333333',
            'warna4' => '#444444',
            'paydisini_apikey' => 'paydisini-default-key',
            'tripay_api' => null,
            'tripay_merchant_code' => null,
            'tripay_private_key' => null,
            'duitku_merchant_code' => null,
            'duitku_merchant_key' => null,
            'duitku_callback_url' => null,
            'duitku_return_url' => null,
            'duitku_mode' => 'sandbox',
            'deposit_jalur' => 'duitku',
            'duitku_enabled' => false,
            'tokopay_merchant_id' => null,
            'tokopay_secret_key' => null,
            'username_digi' => null,
            'api_key_digi' => null,
            'apigames_secret' => null,
            'apigames_merchant' => null,
            'vip_apiid' => null,
            'vip_apikey' => null,
            'nomor_admin' => null,
            'wa_key' => null,
            'wa_number' => null,
            'ovo_admin' => null,
            'ovo1_admin' => null,
            'gopay_admin' => null,
            'gopay1_admin' => null,
            'dana_admin' => null,
            'shopeepay_admin' => null,
            'bca_admin' => null,
            'order_prefik' => 'INV',
            'commission_percent' => 20,
            'point_per_nominal' => 1,
            'point_value' => 100,
            'max_point_usage_percent' => 50,
            'profit_member' => null,
            'profit_platinum' => null,
            'profit_gold' => null,
            'trx_count_gold' => 50,
            'trx_count_platinum' => 100,
            'created_at' => now(),
            'updated_at' => now(),
            'google_analytics_id' => null,
            'facebook_pixel_id' => null,
            'google_tag_manager_id' => null,
        ], $overrides));
    }
}

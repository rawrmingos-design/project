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

class TriPayCallbackTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedSettingWeb([
            'tripay_private_key' => 'tripay-private-test',
        ]);
    }

    public function test_callback_rejects_invalid_signature(): void
    {
        $payload = json_encode([
            'reference' => 'TRX-INVALID-SIGNATURE',
            'status' => 'PAID',
            'total_amount' => 15000,
        ], JSON_UNESCAPED_SLASHES);

        $response = $this->call('POST', '/wejizy/tripay/callback', server: [
            'HTTP_X_CALLBACK_SIGNATURE' => 'invalid-signature',
            'HTTP_X_CALLBACK_EVENT' => 'payment_status',
            'CONTENT_TYPE' => 'application/json',
        ], content: $payload);

        $this->assertSame('Invalid signature', $response->getContent());
    }

    public function test_callback_acknowledges_missing_invoice_when_signature_is_valid(): void
    {
        $payload = json_encode([
            'reference' => 'TRX-MISSING-INVOICE',
            'status' => 'PAID',
            'total_amount' => 15000,
        ], JSON_UNESCAPED_SLASHES);

        $signature = hash_hmac('sha256', $payload, 'tripay-private-test');

        $response = $this->call('POST', '/wejizy/tripay/callback', server: [
            'HTTP_X_CALLBACK_SIGNATURE' => $signature,
            'HTTP_X_CALLBACK_EVENT' => 'payment_status',
            'CONTENT_TYPE' => 'application/json',
        ], content: $payload);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'ignored_invoice_not_found');
    }

    public function test_paid_callback_marks_unpaid_invoice_as_paid_when_amount_matches(): void
    {
        $invoice = $this->createInvoice([
            'order_id' => 'INV-TRIPAY-PAID',
            'reference' => 'REF-TRIPAY-PAID',
            'status' => 'Belum Lunas',
            'harga' => '15000',
        ]);

        $payload = json_encode([
            'reference' => $invoice->reference,
            'status' => 'PAID',
            'total_amount' => 15000,
        ], JSON_UNESCAPED_SLASHES);

        $signature = hash_hmac('sha256', $payload, 'tripay-private-test');

        $response = $this->call('POST', '/wejizy/tripay/callback', server: [
            'HTTP_X_CALLBACK_SIGNATURE' => $signature,
            'HTTP_X_CALLBACK_EVENT' => 'payment_status',
            'CONTENT_TYPE' => 'application/json',
        ], content: $payload);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'ignored_order_not_found');

        $invoice->refresh();
        $this->assertSame('Lunas', $invoice->status);
        $this->assertNotNull($invoice->paid_at);
    }

    public function test_paid_callback_rejects_invalid_amount_without_changing_invoice_status(): void
    {
        $invoice = $this->createInvoice([
            'order_id' => 'INV-TRIPAY-AMOUNT',
            'reference' => 'REF-TRIPAY-AMOUNT',
            'status' => 'Belum Lunas',
            'harga' => '15000',
        ]);

        $payload = json_encode([
            'reference' => $invoice->reference,
            'status' => 'PAID',
            'total_amount' => 12000,
        ], JSON_UNESCAPED_SLASHES);

        $signature = hash_hmac('sha256', $payload, 'tripay-private-test');

        $response = $this->call('POST', '/wejizy/tripay/callback', server: [
            'HTTP_X_CALLBACK_SIGNATURE' => $signature,
            'HTTP_X_CALLBACK_EVENT' => 'payment_status',
            'CONTENT_TYPE' => 'application/json',
        ], content: $payload);

        $response
            ->assertStatus(400)
            ->assertJsonPath('message', 'invalid_amount');

        $invoice->refresh();
        $this->assertSame('Belum Lunas', $invoice->status);
        $this->assertNull($invoice->paid_at);
    }

    public function test_already_paid_invoice_returns_idempotent_ack(): void
    {
        $invoice = $this->createInvoice([
            'order_id' => 'INV-TRIPAY-ALREADY-PAID',
            'reference' => 'REF-TRIPAY-ALREADY-PAID',
            'status' => 'Lunas',
            'paid_at' => now()->subMinute(),
        ]);

        $payload = json_encode([
            'reference' => $invoice->reference,
            'status' => 'PAID',
            'total_amount' => 15000,
        ], JSON_UNESCAPED_SLASHES);

        $signature = hash_hmac('sha256', $payload, 'tripay-private-test');

        $response = $this->call('POST', '/wejizy/tripay/callback', server: [
            'HTTP_X_CALLBACK_SIGNATURE' => $signature,
            'HTTP_X_CALLBACK_EVENT' => 'payment_status',
            'CONTENT_TYPE' => 'application/json',
        ], content: $payload);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'already_processed');

        $invoice->refresh();
        $this->assertSame('Lunas', $invoice->status);
    }

    public function test_expired_callback_marks_unpaid_invoice_as_expired(): void
    {
        $invoice = $this->createInvoice([
            'order_id' => 'INV-TRIPAY-EXPIRED',
            'reference' => 'REF-TRIPAY-EXPIRED',
            'status' => 'Belum Lunas',
        ]);

        $payload = json_encode([
            'reference' => $invoice->reference,
            'status' => 'EXPIRED',
            'total_amount' => 15000,
        ], JSON_UNESCAPED_SLASHES);

        $signature = hash_hmac('sha256', $payload, 'tripay-private-test');

        $response = $this->call('POST', '/wejizy/tripay/callback', server: [
            'HTTP_X_CALLBACK_SIGNATURE' => $signature,
            'HTTP_X_CALLBACK_EVENT' => 'payment_status',
            'CONTENT_TYPE' => 'application/json',
        ], content: $payload);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'ignored_order_not_found');

        $invoice->refresh();
        $this->assertSame('Expired', $invoice->status);
    }

    public function test_paid_callback_marks_pending_deposit_success_and_increments_balance(): void
    {
        $user = $this->createUser(username: 'deposit-tripay-user', balance: 2000);
        $invoice = $this->createInvoice([
            'order_id' => 'INV-TRIPAY-DEPOSIT-SUCCESS',
            'reference' => 'REF-TRIPAY-DEPOSIT-SUCCESS',
            'status' => 'Belum Lunas',
            'harga' => '30000',
        ]);
        $deposit = $this->createDeposit($user, [
            'order_id' => $invoice->order_id,
            'jumlah' => 30000,
            'status' => 'Pending',
        ]);

        $payload = json_encode([
            'reference' => $invoice->reference,
            'status' => 'PAID',
            'total_amount' => 30000,
        ], JSON_UNESCAPED_SLASHES);
        $signature = hash_hmac('sha256', $payload, 'tripay-private-test');

        $response = $this->call('POST', '/wejizy/tripay/callback', server: [
            'HTTP_X_CALLBACK_SIGNATURE' => $signature,
            'HTTP_X_CALLBACK_EVENT' => 'payment_status',
            'CONTENT_TYPE' => 'application/json',
        ], content: $payload);

        $response->assertOk()->assertJsonPath('success', true);

        $invoice->refresh();
        $deposit->refresh();
        $user->refresh();

        $this->assertSame('Lunas', $invoice->status);
        $this->assertSame('Success', $deposit->status);
        $this->assertSame(32000, (int) $user->balance);
    }

    public function test_failed_callback_marks_pending_deposit_failed_without_incrementing_balance(): void
    {
        $user = $this->createUser(username: 'deposit-tripay-failed', balance: 2000);
        $invoice = $this->createInvoice([
            'order_id' => 'INV-TRIPAY-DEPOSIT-FAILED',
            'reference' => 'REF-TRIPAY-DEPOSIT-FAILED',
            'status' => 'Belum Lunas',
            'harga' => '30000',
        ]);
        $deposit = $this->createDeposit($user, [
            'order_id' => $invoice->order_id,
            'jumlah' => 30000,
            'status' => 'Pending',
        ]);

        $payload = json_encode([
            'reference' => $invoice->reference,
            'status' => 'FAILED',
            'total_amount' => 30000,
        ], JSON_UNESCAPED_SLASHES);
        $signature = hash_hmac('sha256', $payload, 'tripay-private-test');

        $response = $this->call('POST', '/wejizy/tripay/callback', server: [
            'HTTP_X_CALLBACK_SIGNATURE' => $signature,
            'HTTP_X_CALLBACK_EVENT' => 'payment_status',
            'CONTENT_TYPE' => 'application/json',
        ], content: $payload);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'claimed_with_processing_error');

        $invoice->refresh();
        $deposit->refresh();
        $user->refresh();

        $this->assertSame('Batal', $invoice->status);
        $this->assertSame('Pending', $deposit->status);
        $this->assertSame(2000, (int) $user->balance);
    }

    public function test_repeated_paid_callback_does_not_double_increment_deposit_balance(): void
    {
        $user = $this->createUser(username: 'deposit-tripay-repeat', balance: 1000);
        $invoice = $this->createInvoice([
            'order_id' => 'INV-TRIPAY-DEPOSIT-REPEAT',
            'reference' => 'REF-TRIPAY-DEPOSIT-REPEAT',
            'status' => 'Belum Lunas',
            'harga' => '12000',
        ]);
        $deposit = $this->createDeposit($user, [
            'order_id' => $invoice->order_id,
            'jumlah' => 12000,
            'status' => 'Pending',
        ]);

        $payload = json_encode([
            'reference' => $invoice->reference,
            'status' => 'PAID',
            'total_amount' => 12000,
        ], JSON_UNESCAPED_SLASHES);
        $signature = hash_hmac('sha256', $payload, 'tripay-private-test');
        $server = [
            'HTTP_X_CALLBACK_SIGNATURE' => $signature,
            'HTTP_X_CALLBACK_EVENT' => 'payment_status',
            'CONTENT_TYPE' => 'application/json',
        ];

        $this->call('POST', '/wejizy/tripay/callback', server: $server, content: $payload)->assertOk();
        $this->call('POST', '/wejizy/tripay/callback', server: $server, content: $payload)
            ->assertOk()
            ->assertJsonPath('message', 'already_processed');

        $invoice->refresh();
        $deposit->refresh();
        $user->refresh();

        $this->assertSame('Lunas', $invoice->status);
        $this->assertSame('Success', $deposit->status);
        $this->assertSame(13000, (int) $user->balance);
    }

    public function test_paid_callback_updates_pembelian_for_mocked_provider_success(): void
    {
        $user = $this->createUser(username: 'order-tripay-success');
        $invoice = $this->createInvoice([
            'order_id' => 'INV-TRIPAY-ORDER-SUCCESS',
            'reference' => 'REF-TRIPAY-ORDER-SUCCESS',
            'status' => 'Belum Lunas',
            'harga' => '45000',
        ]);
        $pembelian = $this->createPembelian($user, [
            'order_id' => $invoice->order_id,
            'harga' => 45000,
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
                    'transaction_id' => 'PROVIDER-SUCCESS-123',
                    'message' => 'Order accepted',
                    'sn' => 'SN-OK-123',
                ];
            }
        });

        $response = $this->postTripayPaidCallback($invoice->reference, 45000);

        $response->assertOk()->assertJsonPath('success', true);

        $invoice->refresh();
        $pembelian->refresh();

        $this->assertSame('Lunas', $invoice->status);
        $this->assertSame('Sukses', $pembelian->status);
        $this->assertSame('PROVIDER-SUCCESS-123', $pembelian->provider_order_id);
        $this->assertSame('SN-OK-123', $pembelian->keterangan_sn);
        $this->assertStringContainsString('PROVIDER-SUCCESS-123', (string) $pembelian->log);
    }

    public function test_paid_callback_updates_pembelian_for_mocked_provider_failed_result(): void
    {
        $user = $this->createUser(username: 'order-tripay-failed');
        $invoice = $this->createInvoice([
            'order_id' => 'INV-TRIPAY-ORDER-FAILED',
            'reference' => 'REF-TRIPAY-ORDER-FAILED',
            'status' => 'Belum Lunas',
            'harga' => '47000',
        ]);
        $pembelian = $this->createPembelian($user, [
            'order_id' => $invoice->order_id,
            'harga' => 47000,
            'status' => 'Pending',
            'keterangan_sn' => 'SN-LAMA',
        ]);

        $this->bindNotificationStubs();
        $this->app->instance(OrderProcessingService::class, new class {
            public function process($pembelian, string $dispatchMode = 'auto'): array
            {
                return [
                    'success' => false,
                    'order_status' => 'Failed',
                    'transaction_id' => 'PROVIDER-FAILED-123',
                    'message' => 'Provider failed',
                    'sn' => null,
                ];
            }
        });

        $response = $this->postTripayPaidCallback($invoice->reference, 47000);

        $response->assertOk()->assertJsonPath('success', true);

        $invoice->refresh();
        $pembelian->refresh();

        $this->assertSame('Lunas', $invoice->status);
        $this->assertSame('Gagal', $pembelian->status);
        $this->assertSame('PROVIDER-FAILED-123', $pembelian->provider_order_id);
        $this->assertSame('SN-LAMA', $pembelian->keterangan_sn);
        $this->assertStringContainsString('Provider failed', (string) $pembelian->log);
    }

    public function test_paid_callback_returns_processing_error_when_mocked_processor_throws(): void
    {
        $user = $this->createUser(username: 'order-tripay-exception');
        $invoice = $this->createInvoice([
            'order_id' => 'INV-TRIPAY-ORDER-EXCEPTION',
            'reference' => 'REF-TRIPAY-ORDER-EXCEPTION',
            'status' => 'Belum Lunas',
            'harga' => '49000',
        ]);
        $pembelian = $this->createPembelian($user, [
            'order_id' => $invoice->order_id,
            'harga' => 49000,
            'status' => 'Pending',
        ]);

        $this->bindNotificationStubs();
        $this->app->instance(OrderProcessingService::class, new class {
            public function process($pembelian, string $dispatchMode = 'auto'): array
            {
                throw new \RuntimeException('processor boom');
            }
        });

        $response = $this->postTripayPaidCallback($invoice->reference, 49000);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'claimed_with_processing_error');

        $invoice->refresh();
        $pembelian->refresh();

        $this->assertSame('Lunas', $invoice->status);
        $this->assertSame('Pending', $pembelian->status);
        $this->assertStringContainsString('processor boom', (string) $pembelian->log);
    }

    public function test_paid_callback_only_processes_pembelian_once_after_invoice_is_claimed(): void
    {
        $user = $this->createUser(username: 'order-tripay-idempotent');
        $invoice = $this->createInvoice([
            'order_id' => 'INV-TRIPAY-ORDER-IDEMPOTENT',
            'reference' => 'REF-TRIPAY-ORDER-IDEMPOTENT',
            'status' => 'Belum Lunas',
            'harga' => '51000',
        ]);
        $pembelian = $this->createPembelian($user, [
            'order_id' => $invoice->order_id,
            'harga' => 51000,
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
                    'transaction_id' => 'PROVIDER-IDEMPOTENT-123',
                    'message' => 'Order accepted once',
                    'sn' => 'SN-IDEMPOTENT-123',
                ];
            }
        };
        $this->app->instance(OrderProcessingService::class, $processor);

        $this->postTripayPaidCallback($invoice->reference, 51000)
            ->assertOk()
            ->assertJsonPath('success', true);

        $secondResponse = $this->postTripayPaidCallback($invoice->reference, 51000);
        $secondResponse
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'already_processed');

        $invoice->refresh();
        $pembelian->refresh();

        $this->assertSame(1, $processor->calls);
        $this->assertSame('Lunas', $invoice->status);
        $this->assertSame('Sukses', $pembelian->status);
        $this->assertSame('PROVIDER-IDEMPOTENT-123', $pembelian->provider_order_id);
        $this->assertSame('SN-IDEMPOTENT-123', $pembelian->keterangan_sn);
    }

    private function createInvoice(array $overrides = []): Pembayaran
    {
        return Pembayaran::create(array_merge([
            'order_id' => 'INV-TRIPAY-DEFAULT',
            'harga' => '15000',
            'no_pembayaran' => '08123456789',
            'no_pembeli' => '08123456789',
            'status' => 'Belum Lunas',
            'metode' => 'TriPay',
            'reference' => 'REF-TRIPAY-DEFAULT',
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
            'order_id' => 'DEP-TRIPAY-DEFAULT',
            'username' => $user->username,
            'metode' => 'TriPay',
            'no_pembayaran' => '08123456789',
            'jumlah' => 15000,
            'status' => 'Pending',
        ], $overrides));
    }

    private function createPembelian(User $user, array $overrides = []): Pembelian
    {
        return Pembelian::create(array_merge([
            'order_id' => 'ORDER-TRIPAY-DEFAULT',
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

    private function postTripayPaidCallback(string $reference, int $amount)
    {
        $payload = json_encode([
            'reference' => $reference,
            'status' => 'PAID',
            'total_amount' => $amount,
        ], JSON_UNESCAPED_SLASHES);

        return $this->call('POST', '/wejizy/tripay/callback', server: [
            'HTTP_X_CALLBACK_SIGNATURE' => hash_hmac('sha256', $payload, 'tripay-private-test'),
            'HTTP_X_CALLBACK_EVENT' => 'payment_status',
            'CONTENT_TYPE' => 'application/json',
        ], content: $payload);
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

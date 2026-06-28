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

class TokoPayCallbackTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedSettingWeb([
            'tokopay_merchant_id' => 'merchant-test',
            'tokopay_secret_key' => 'secret-test',
        ]);
    }

    public function test_callback_rejects_invalid_signature(): void
    {
        $response = $this->postJson('/wejizy/tokopay/callback', [
            'status' => 'success',
            'reff_id' => 'TOKO-001',
            'reference' => 'INV-TOKO-001',
            'signature' => 'invalid-signature',
        ]);

        $response
            ->assertStatus(401)
            ->assertJsonPath('message', 'Invalid Signature');
    }

    public function test_callback_acknowledges_missing_invoice_when_signature_is_valid(): void
    {
        $refId = 'TOKO-MISSING';

        $response = $this->postJson('/wejizy/tokopay/callback', [
            'status' => 'success',
            'reff_id' => $refId,
            'reference' => 'INV-TOKO-MISSING',
            'signature' => md5('merchant-test:secret-test:' . $refId),
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('message', 'ignored_invoice_not_found');
    }

    public function test_paid_callback_marks_unpaid_invoice_as_paid(): void
    {
        $invoice = $this->createInvoice([
            'order_id' => 'INV-TOKO-PAID',
            'reference' => 'REF-TOKO-PAID',
            'status' => 'Belum Lunas',
        ]);

        $refId = 'TOKO-PAID';

        $response = $this->postJson('/wejizy/tokopay/callback', [
            'status' => 'success',
            'reff_id' => $refId,
            'reference' => $invoice->reference,
            'signature' => md5('merchant-test:secret-test:' . $refId),
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('message', 'ignored_order_not_found');

        $invoice->refresh();
        $this->assertSame('Lunas', $invoice->status);
        $this->assertNotNull($invoice->paid_at);
    }

    public function test_already_paid_invoice_returns_idempotent_ack(): void
    {
        $invoice = $this->createInvoice([
            'order_id' => 'INV-TOKO-ALREADY-PAID',
            'reference' => 'REF-TOKO-ALREADY-PAID',
            'status' => 'Lunas',
            'paid_at' => now()->subMinute(),
        ]);

        $refId = 'TOKO-ALREADY';

        $response = $this->postJson('/wejizy/tokopay/callback', [
            'status' => 'success',
            'reff_id' => $refId,
            'reference' => $invoice->reference,
            'signature' => md5('merchant-test:secret-test:' . $refId),
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('message', 'already_processed');

        $invoice->refresh();
        $this->assertSame('Lunas', $invoice->status);
    }

    public function test_non_paid_status_is_ignored_without_changing_invoice(): void
    {
        $invoice = $this->createInvoice([
            'order_id' => 'INV-TOKO-NON-PAID',
            'reference' => 'REF-TOKO-NON-PAID',
            'status' => 'Belum Lunas',
        ]);

        $refId = 'TOKO-NON-PAID';

        $response = $this->postJson('/wejizy/tokopay/callback', [
            'status' => 'failed',
            'reff_id' => $refId,
            'reference' => $invoice->reference,
            'signature' => md5('merchant-test:secret-test:' . $refId),
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('message', 'ignored_non_paid_status');

        $invoice->refresh();
        $this->assertSame('Belum Lunas', $invoice->status);
        $this->assertNull($invoice->paid_at);
    }

    public function test_paid_callback_marks_pending_deposit_success_and_increments_balance(): void
    {
        $user = $this->createUser(username: 'deposit-tokopay-user', balance: 4000);
        $invoice = $this->createInvoice([
            'order_id' => 'INV-TOKO-DEPOSIT-SUCCESS',
            'reference' => 'REF-TOKO-DEPOSIT-SUCCESS',
            'status' => 'Belum Lunas',
        ]);
        $deposit = $this->createDeposit($user, [
            'order_id' => $invoice->order_id,
            'jumlah' => 18000,
            'status' => 'Pending',
        ]);

        $refId = 'TOKO-DEPOSIT-SUCCESS';

        $response = $this->postJson('/wejizy/tokopay/callback', [
            'status' => 'success',
            'reff_id' => $refId,
            'reference' => $invoice->reference,
            'signature' => md5('merchant-test:secret-test:' . $refId),
        ]);

        $response->assertOk()->assertJsonPath('status', true);

        $invoice->refresh();
        $deposit->refresh();
        $user->refresh();

        $this->assertSame('Lunas', $invoice->status);
        $this->assertSame('Success', $deposit->status);
        $this->assertSame(22000, (int) $user->balance);
    }

    public function test_repeated_paid_callback_does_not_double_increment_deposit_balance(): void
    {
        $user = $this->createUser(username: 'deposit-tokopay-repeat', balance: 5000);
        $invoice = $this->createInvoice([
            'order_id' => 'INV-TOKO-DEPOSIT-REPEAT',
            'reference' => 'REF-TOKO-DEPOSIT-REPEAT',
            'status' => 'Belum Lunas',
        ]);
        $deposit = $this->createDeposit($user, [
            'order_id' => $invoice->order_id,
            'jumlah' => 7000,
            'status' => 'Pending',
        ]);

        $refId = 'TOKO-DEPOSIT-REPEAT';
        $payload = [
            'status' => 'success',
            'reff_id' => $refId,
            'reference' => $invoice->reference,
            'signature' => md5('merchant-test:secret-test:' . $refId),
        ];

        $this->postJson('/wejizy/tokopay/callback', $payload)->assertOk();
        $this->postJson('/wejizy/tokopay/callback', $payload)
            ->assertOk()
            ->assertJsonPath('message', 'already_processed');

        $invoice->refresh();
        $deposit->refresh();
        $user->refresh();

        $this->assertSame('Lunas', $invoice->status);
        $this->assertSame('Success', $deposit->status);
        $this->assertSame(12000, (int) $user->balance);
    }

    public function test_paid_callback_updates_pembelian_for_mocked_provider_success(): void
    {
        $user = $this->createUser(username: 'order-tokopay-success');
        $invoice = $this->createInvoice([
            'order_id' => 'INV-TOKO-ORDER-SUCCESS',
            'reference' => 'REF-TOKO-ORDER-SUCCESS',
            'status' => 'Belum Lunas',
            'harga' => '42000',
        ]);
        $pembelian = $this->createPembelian($user, [
            'order_id' => $invoice->order_id,
            'harga' => 42000,
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
                    'transaction_id' => 'TK-SUCCESS-123',
                    'message' => 'Order accepted',
                    'sn' => 'TK-SN-123',
                ];
            }
        });

        $response = $this->postTokoPaySuccessCallback($invoice->reference, 'TOKO-ORDER-009');

        $response->assertOk()->assertJsonPath('status', true);

        $invoice->refresh();
        $pembelian->refresh();

        $this->assertSame('Lunas', $invoice->status);
        $this->assertSame('Sukses', $pembelian->status);
        $this->assertSame('TK-SUCCESS-123', $pembelian->provider_order_id);
        $this->assertSame('TK-SN-123', $pembelian->keterangan_sn);
        $this->assertStringContainsString('TK-SUCCESS-123', (string) $pembelian->log);
    }

    public function test_paid_callback_updates_pembelian_for_mocked_provider_failed_result(): void
    {
        $user = $this->createUser(username: 'order-tokopay-failed');
        $invoice = $this->createInvoice([
            'order_id' => 'INV-TOKO-ORDER-FAILED',
            'reference' => 'REF-TOKO-ORDER-FAILED',
            'status' => 'Belum Lunas',
            'harga' => '41000',
        ]);
        $pembelian = $this->createPembelian($user, [
            'order_id' => $invoice->order_id,
            'harga' => 41000,
            'status' => 'Pending',
            'keterangan_sn' => 'TK-SN-LAMA',
        ]);

        $this->bindNotificationStubs();
        $this->app->instance(OrderProcessingService::class, new class {
            public function process($pembelian, string $dispatchMode = 'auto'): array
            {
                return [
                    'success' => false,
                    'order_status' => 'Failed',
                    'transaction_id' => 'TK-FAILED-123',
                    'message' => 'Provider failed',
                    'sn' => null,
                ];
            }
        });

        $response = $this->postTokoPaySuccessCallback($invoice->reference, 'TOKO-ORDER-010');

        $response->assertOk()->assertJsonPath('status', true);

        $invoice->refresh();
        $pembelian->refresh();

        $this->assertSame('Lunas', $invoice->status);
        $this->assertSame('Gagal', $pembelian->status);
        $this->assertSame('TK-FAILED-123', $pembelian->provider_order_id);
        $this->assertSame('TK-SN-LAMA', $pembelian->keterangan_sn);
        $this->assertStringContainsString('Provider failed', (string) $pembelian->log);
    }

    public function test_paid_callback_only_processes_pembelian_once_after_invoice_is_claimed(): void
    {
        $user = $this->createUser(username: 'order-tokopay-idempotent');
        $invoice = $this->createInvoice([
            'order_id' => 'INV-TOKO-ORDER-IDEMPOTENT',
            'reference' => 'REF-TOKO-ORDER-IDEMPOTENT',
            'status' => 'Belum Lunas',
            'harga' => '40000',
        ]);
        $pembelian = $this->createPembelian($user, [
            'order_id' => $invoice->order_id,
            'harga' => 40000,
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
                    'transaction_id' => 'TK-IDEMPOTENT-123',
                    'message' => 'Order accepted once',
                    'sn' => 'TK-SN-IDEMPOTENT',
                ];
            }
        };
        $this->app->instance(OrderProcessingService::class, $processor);

        $this->postTokoPaySuccessCallback($invoice->reference, 'TOKO-ORDER-011')
            ->assertOk()
            ->assertJsonPath('status', true);

        $secondResponse = $this->postTokoPaySuccessCallback($invoice->reference, 'TOKO-ORDER-011');
        $secondResponse
            ->assertOk()
            ->assertJsonPath('status', true)
            ->assertJsonPath('message', 'already_processed');

        $invoice->refresh();
        $pembelian->refresh();

        $this->assertSame(1, $processor->calls);
        $this->assertSame('Lunas', $invoice->status);
        $this->assertSame('Sukses', $pembelian->status);
        $this->assertSame('TK-IDEMPOTENT-123', $pembelian->provider_order_id);
        $this->assertSame('TK-SN-IDEMPOTENT', $pembelian->keterangan_sn);
    }

    private function createInvoice(array $overrides = []): Pembayaran
    {
        return Pembayaran::create(array_merge([
            'order_id' => 'INV-TOKO-DEFAULT',
            'harga' => '15000',
            'no_pembayaran' => '08123456789',
            'no_pembeli' => '08123456789',
            'status' => 'Belum Lunas',
            'metode' => 'TokoPay',
            'reference' => 'REF-TOKO-DEFAULT',
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
            'order_id' => 'DEP-TOKO-DEFAULT',
            'username' => $user->username,
            'metode' => 'TokoPay',
            'no_pembayaran' => '08123456789',
            'jumlah' => 15000,
            'status' => 'Pending',
        ], $overrides));
    }

    private function createPembelian(User $user, array $overrides = []): Pembelian
    {
        return Pembelian::create(array_merge([
            'order_id' => 'ORDER-TOKO-DEFAULT',
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

    private function postTokoPaySuccessCallback(string $reference, string $refId)
    {
        return $this->postJson('/wejizy/tokopay/callback', [
            'status' => 'success',
            'reff_id' => $refId,
            'reference' => $reference,
            'signature' => md5('merchant-test:secret-test:' . $refId),
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

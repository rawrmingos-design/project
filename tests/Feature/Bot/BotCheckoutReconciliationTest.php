<?php

namespace Tests\Feature\Bot;

use App\Models\BotCheckoutIntent;
use App\Models\Pembelian;
use App\Models\SettingWeb;
use App\Services\Checkout\BotCheckoutIntentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * P1 — status `requires_reconciliation` dulu adalah DEAD-END.
 *
 * Alur yang menghasilkannya:
 *
 *     markProviderDispatch($intent)   → provider_dispatched_at = now()
 *     requestGatewayInvoice()         → panggilan provider
 *     ... gagal (termasuk TIMEOUT)    → markFailure($providerDispatched = true)
 *                                     → status requires_reconciliation
 *
 * `createForPembelian()` menangkap `Throwable` — termasuk timeout — lalu
 * mengembalikan `success = false`. Artinya `success = false` TIDAK bisa
 * dibedakan antara "provider menolak" dan "provider tidak pernah menerima".
 * Jadi menandai `provider_dispatched_at` lebih dulu itu BENAR (konservatif);
 * yang hilang adalah PEMBACANYA. Di produksi status ini ditulis, dibalas ke
 * user dengan kalimat "sedang direkonsiliasi, jangan membuat transaksi ulang",
 * dan TIDAK ADA satu pun command/job/scheduler yang membacanya — user
 * terjebak permanen (staging: 3 intent, umur >50 jam).
 *
 * Yang dikunci di sini:
 *  1. Sebelum jendela rekonsiliasi lewat, intent TIDAK boleh dipulihkan
 *     (kita belum boleh mengklaim provider tidak pernah menerima).
 *  2. Setelah jendela lewat, intent yang TIDAK punya order harus jadi
 *     `failed_retryable` — bukan lagi mengunci user.
 *  3. Kalau ternyata ADA order dengan `order_id` itu, statusnya `completed`.
 *  4. Command `bot:reconcile-checkout` memprosesnya, idempoten, dan
 *     NONAKTIF secara default (saklar `bot.reconcile_checkout_enabled`).
 *
 * ⚠️ Rekonsiliasi status provider TIDAK diuji lewat HTTP di sini (butuh
 * jaringan). Yang diuji adalah mesin statusnya; pembacaan status provider
 * disuntik lewat fake yang menggantikan `BotCheckoutReconciler`.
 */
class BotCheckoutReconciliationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        SettingWeb::query()->updateOrInsert(['id' => 1], [
            'judul_web' => 'Test Store',
            'deskripsi_web' => 'Test Description',
            'keywords' => 'test',
            'url_wa' => 'https://wa.me/628123456789',
            'url_ig' => 'https://instagram.com/test',
            'url_tiktok' => 'https://tiktok.com/@test',
            'url_youtube' => 'https://youtube.com/test',
            'url_fb' => 'https://facebook.com/test',
            'topupindo_api' => 'test',
            'warna1' => '#000000',
            'warna2' => '#000000',
            'warna3' => '#000000',
            'warna4' => '#000000',
            'paydisini_apikey' => 'test',
            'order_prefik' => 'EM',
            'nomor_admin' => '628123456789',
        ]);
    }

    /**
     * Bikin satu intent yang benar-benar nyangkut di `requires_reconciliation`,
     * lewat jalur aslinya (create → claim → markProviderDispatch → markFailure),
     * bukan dengan menulis status langsung ke DB.
     */
    private function stuckIntent(array $overrides = []): BotCheckoutIntent
    {
        $service = app(BotCheckoutIntentService::class);
        $context = $this->context();

        $created = $service->create(
            array_replace([
                'service' => 1,
                'payment_method' => 'QRIS',
                'uid' => '12345',
                'email' => '9876@telegram.user',
            ], $overrides['payload'] ?? []),
            $this->quote(),
            $context,
        );

        $claimed = $service->claim($created['token'], $this->context([
            'message_id' => 'confirm-1',
        ]));

        $this->assertSame('claimed', $claimed['status'], 'Intent harus bisa di-claim lebih dulu.');

        $intent = $claimed['intent'];
        $service->markProviderDispatch($intent);
        $service->markFailure($intent, true);

        $intent->refresh();
        $this->assertSame(
            BotCheckoutIntent::STATUS_REQUIRES_RECONCILIATION,
            $intent->status,
            'Prasyarat test: intent harus nyangkut di requires_reconciliation.',
        );

        return $intent;
    }

    /**
     * Prasyarat paling penting: jangan pernah memulihkan intent yang jendela
     * rekonsiliasinya belum lewat — di situ kita masih belum tahu apakah
     * provider sudah membuat tagihan.
     */
    public function test_reconciliation_belum_jatuh_tempo_belum_boleh_dipulihkan(): void
    {
        $intent = $this->stuckIntent();

        $this->assertFalse(
            app(BotCheckoutIntentService::class)->isReconciliationDue($intent),
            'Intent baru nyangkut TIDAK boleh dianggap siap direkonsiliasi.',
        );
        $this->assertNotNull(
            app(BotCheckoutIntentService::class)->reconciliationReadyAt($intent),
            'Harus ada waktu tunggu yang bisa diperiksa.',
        );
    }

    public function test_reconciliation_jatuh_tempo_setelah_jendela(): void
    {
        $intent = $this->stuckIntent();
        $service = app(BotCheckoutIntentService::class);

        // Majukan jejak dispatch ke masa lalu melewati jendela rekonsiliasi.
        $intent->forceFill([
            'provider_dispatched_at' => now()->subMinutes(
                (int) config('bot.reconcile_checkout_after_minutes', 10) + 1,
            ),
        ])->save();

        $this->assertTrue($service->isReconciliationDue($intent->refresh()));
    }

    /**
     * Inti P1: setelah jendela lewat dan provider TERBUKTI tidak menghasilkan
     * order, intent harus jadi `failed_retryable` supaya user bisa lanjut —
     * bukan lagi dibalas "jangan membuat transaksi ulang" selamanya.
     */
    public function test_intent_tanpa_order_jadi_failed_retryable(): void
    {
        $intent = $this->stuckIntent();
        $service = app(BotCheckoutIntentService::class);

        $intent->forceFill(['provider_dispatched_at' => now()->subHours(2)])->save();

        $service->markReconciled($intent->refresh(), false);

        $intent->refresh();
        $this->assertSame(BotCheckoutIntent::STATUS_FAILED_RETRYABLE, $intent->status);
        $this->assertSame('reconciled_no_order', $intent->failure_code);
        $this->assertNull($intent->order_id);
    }

    /**
     * Kalau provider ternyata MEMBUAT order (tagihan menggantung), intent harus
     * selesai dan menunjuk order itu — bukan dilepas sebagai bisa dicoba ulang,
     * yang bisa menghasilkan tagihan ganda.
     */
    public function test_intent_yang_punya_order_jadi_completed(): void
    {
        $intent = $this->stuckIntent();
        $service = app(BotCheckoutIntentService::class);

        $intent->forceFill(['provider_dispatched_at' => now()->subHours(2)])->save();

        $service->markReconciled($intent->refresh(), true, 'EM-TEST-RECON-001');

        $intent->refresh();
        $this->assertSame(BotCheckoutIntent::STATUS_COMPLETED, $intent->status);
        $this->assertSame('EM-TEST-RECON-001', $intent->order_id);
        $this->assertNotNull($intent->completed_at);
        $this->assertNull($intent->failure_code);
    }

    /**
     * Rekonsiliasi tidak boleh menyentuh intent yang masih sehat.
     */
    public function test_rekonsiliasi_tidak_menyentuh_intent_sehat(): void
    {
        $service = app(BotCheckoutIntentService::class);
        $created = $service->create(
            ['service' => 1, 'payment_method' => 'QRIS', 'uid' => '12345'],
            $this->quote(),
            $this->context(),
        );

        $intent = $created['intent'];
        $service->markReconciled($intent, false);

        $this->assertSame(
            BotCheckoutIntent::STATUS_AWAITING_CONFIRMATION,
            $intent->refresh()->status,
            'Intent yang belum pernah dispatch tidak boleh diubah rekonsiliasi.',
        );
    }

    /**
     * Command harus ADA dan memanggil reconciler hanya untuk intent yang
     * benar-benar jatuh tempo.
     */
    public function test_command_merekonsiliasi_intent_jatuh_tempo(): void
    {
        $intent = $this->stuckIntent();
        $intent->forceFill(['provider_dispatched_at' => now()->subHours(3)])->save();

        config(['bot.reconcile_checkout_enabled' => true]);
        $this->mockReconciler(orderFound: false);

        $this->artisan('bot:reconcile-checkout --apply')
            ->assertExitCode(0);

        $this->assertSame(
            BotCheckoutIntent::STATUS_FAILED_RETRYABLE,
            $intent->refresh()->status,
        );
    }

    /**
     * Dry-run: melaporkan tanpa menulis. Ini pengaman supaya status bisa
     * diperiksa di produksi sebelum ada perubahan.
     */
    public function test_command_tanpa_apply_tidak_menulis(): void
    {
        $intent = $this->stuckIntent();
        $intent->forceFill(['provider_dispatched_at' => now()->subHours(3)])->save();

        config(['bot.reconcile_checkout_enabled' => true]);
        $this->mockReconciler(orderFound: false);

        $this->artisan('bot:reconcile-checkout')->assertExitCode(0);

        $this->assertSame(
            BotCheckoutIntent::STATUS_REQUIRES_RECONCILIATION,
            $intent->refresh()->status,
            'Tanpa --apply tidak boleh ada perubahan status.',
        );
    }

    public function test_command_tidak_memanggil_reconciler_sebelum_jatuh_tempo(): void
    {
        $this->stuckIntent();

        $this->mockReconciler(orderFound: false, expectCall: false);

        $this->artisan('bot:reconcile-checkout')->assertExitCode(0);
    }

    /**
     * Idempoten: dijalankan dua kali tidak menggandakan efek apa pun.
     */
    public function test_command_idempoten(): void
    {
        $intent = $this->stuckIntent();
        $intent->forceFill(['provider_dispatched_at' => now()->subHours(3)])->save();

        config(['bot.reconcile_checkout_enabled' => true]);
        $this->mockReconciler(orderFound: true, orderId: 'EM-TEST-RECON-002');

        $this->artisan('bot:reconcile-checkout --apply')->assertExitCode(0);
        $this->assertSame(BotCheckoutIntent::STATUS_COMPLETED, $intent->refresh()->status);

        $this->artisan('bot:reconcile-checkout --apply')->assertExitCode(0);
        $this->assertSame(BotCheckoutIntent::STATUS_COMPLETED, $intent->refresh()->status);
        $this->assertSame('EM-TEST-RECON-002', $intent->refresh()->order_id);
    }

    /**
     * Saklar harus NONAKTIF secara default: status ini dihasilkan jalur uang,
     * jadi jangan pernah mengaktifkannya diam-diam lewat default `true`.
     */
    public function test_saklar_rekonsiliasi_nonaktif_secara_default(): void
    {
        $this->assertFalse(
            (bool) config('bot.reconcile_checkout_enabled'),
            'Rekonsiliasi tidak boleh aktif tanpa dinyalakan eksplisit.',
        );
    }

    /**
     * Saat saklar mati, command TIDAK boleh mengubah apa pun (mode identifikasi
     * tetap boleh, dan tetap tidak menulis).
     */
    public function test_saklar_mati_tidak_mengubah_status(): void
    {
        $intent = $this->stuckIntent();
        $intent->forceFill(['provider_dispatched_at' => now()->subHours(3)])->save();

        config(['bot.reconcile_checkout_enabled' => false]);
        $this->mockReconciler(orderFound: false, expectCall: false);

        $this->artisan('bot:reconcile-checkout')->assertExitCode(0);

        $this->assertSame(
            BotCheckoutIntent::STATUS_REQUIRES_RECONCILIATION,
            $intent->refresh()->status,
        );
    }

    /**
     * Perilaku yang selama ini menyesatkan user: intent nyangkut yang sudah
     * LEWAT masa berlaku dibalas "jangan membuat transaksi ulang". Setelah
     * rekonsiliasi menjawab "tidak ada order", user harus diarahkan memulai
     * ulang — bukan dilarang.
     */
    public function test_claim_intent_nyangkut_yang_kedaluwarsa_mengarahkan_mulai_ulang(): void
    {
        $service = app(BotCheckoutIntentService::class);
        $context = $this->context();

        $created = $service->create(
            ['service' => 1, 'payment_method' => 'QRIS', 'uid' => '12345'],
            $this->quote(),
            $context,
        );

        $claimed = $service->claim($created['token'], $this->context(['message_id' => 'confirm-1']));
        $intent = $claimed['intent'];
        $service->markProviderDispatch($intent);
        $service->markFailure($intent, true);

        // Nyangkut >50 jam seperti data nyata, dan provider terbukti
        // tidak menghasilkan order.
        $intent->forceFill([
            'provider_dispatched_at' => now()->subHours(52),
            'expires_at' => now()->subHours(51),
        ])->save();
        $service->markReconciled($intent->refresh(), false);

        $retry = $service->claim($created['token'], $this->context(['message_id' => 'confirm-2']));

        $this->assertSame(
            'expired',
            $retry['status'],
            'User harus diarahkan memulai checkout ulang, bukan dilarang selamanya.',
        );
    }

    private function mockReconciler(bool $orderFound, ?string $orderId = null, bool $expectCall = true): void
    {
        $this->mock(\App\Services\Checkout\BotCheckoutReconciler::class, function (MockInterface $mock) use ($orderFound, $orderId, $expectCall): void {
            $expectation = $mock->shouldReceive('resolveOrderForIntent');

            if (! $expectCall) {
                $expectation->never();
            }

            $expectation->andReturn($orderFound ? ($orderId ?? 'EM-TEST-RECON-000') : null);
        });
    }

    private function quote(): array
    {
        return [
            'data' => [
                'service_id' => 1,
                'service_name' => '100 Diamond',
                'payment_method' => [
                    'code' => 'QRIS',
                    'name' => 'QRIS',
                    'type' => 'tripay',
                ],
                'base_amount' => 10000,
                'discount' => 0,
                'payment_fee' => 500,
                'total_amount' => 10500,
            ],
        ];
    }

    private function context(array $overrides = []): array
    {
        return array_replace([
            'source' => 'telegram_gateway',
            'external_user_id' => 'telegram:9876',
            'message_id' => 'message-1',
        ], $overrides);
    }
}

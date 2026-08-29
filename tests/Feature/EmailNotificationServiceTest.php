<?php

namespace Tests\Feature;

use App\Models\EmailTemplate;
use App\Models\SettingWeb;
use App\Services\EmailNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EmailNotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_transaction_template_does_not_send_mail(): void
    {
        $this->createSettings();
        Mail::fake();

        $result = app(EmailNotificationService::class)->sendTransactionEmail(
            'buyer@example.com',
            [
                'order_id' => 'INV-MISSING-001',
                'status' => 'Pending',
                'nickname' => 'Buyer',
            ],
        );

        $this->assertFalse($result);
        Mail::assertNothingSent();
    }

    public function test_inactive_transaction_template_does_not_send_mail(): void
    {
        $this->createSettings();
        EmailTemplate::query()->create([
            'slug' => 'transaction_pending',
            'name' => 'Transaksi Pending',
            'subject' => 'Pending {order_id}',
            'details' => null,
            'content' => '<p>Pending</p>',
            'is_active' => false,
        ]);
        Mail::fake();

        $result = app(EmailNotificationService::class)->sendTransactionEmail(
            'buyer@example.com',
            ['order_id' => 'INV-INACTIVE-001', 'status' => 'Pending'],
        );

        $this->assertFalse($result);
        Mail::assertNothingSent();
    }

    public function test_active_transaction_template_sends_mail(): void
    {
        $this->createSettings();
        EmailTemplate::query()->create([
            'slug' => 'transaction_success',
            'name' => 'Transaksi Sukses',
            'subject' => 'Berhasil {order_id}',
            'details' => null,
            'content' => '<p>{product}</p>',
            'is_active' => true,
        ]);
        Mail::fake();

        $result = app(EmailNotificationService::class)->sendTransactionEmail(
            'buyer@example.com',
            [
                'order_id' => 'INV-ACTIVE-001',
                'status' => 'success',
                'product' => 'Free Fire 250 Diamond',
            ],
        );

        $this->assertTrue($result);
        Mail::assertSent(\App\Mail\TransactionMail::class, function ($mail): bool {
            return $mail->to[0]['address'] === 'buyer@example.com'
                && $mail->subjectLine === 'Berhasil INV-ACTIVE-001'
                && $mail->contentBody === '<p>Free Fire 250 Diamond</p>';
        });
    }

    private function createSettings(array $overrides = []): void
    {
        SettingWeb::query()->create(array_merge([
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
            'paydisini_apikey' => 'paydisini-test',
            'order_prefik' => 'INV',
            'wa_provider' => 'fonnte',
            'wa_key' => 'fonnte-token',
        ], $overrides));
    }
}

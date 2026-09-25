<?php

namespace Tests\Feature\Bot;

use App\Services\Bot\BotMessageFormatter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Command `bot:gate-check` adalah satu-satunya cara cepat bagi admin untuk
 * tahu bahwa gerbang "wajib gabung" sedang TIDAK BISA berfungsi. Tanpa ini,
 * gejalanya cuma "semua user tertahan" tanpa sebab yang terlihat.
 */
class BotGateCheckCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        config([
            'services.telegram-bot-api.token' => 'dummy-token',
            'services.telegram-bot-api.required_channel.enabled' => true,
            'services.telegram-bot-api.required_channel.id' => null,
            'services.telegram-bot-api.required_channel.url' => null,
            'services.telegram-bot-api.required_channel.channels' => [
                ['id' => '@jasakodings', 'url' => 'https://t.me/jasakodings', 'label' => 'Jasakoding'],
            ],
        ]);
    }

    private function fakeBotIdentity(): array
    {
        return [
            'https://api.telegram.org/botdummy-token/getMe' => Http::response([
                'ok' => true,
                'result' => ['id' => 4242, 'username' => 'jasakoding_bot'],
            ]),
        ];
    }

    public function test_reports_success_when_bot_can_read_the_channel(): void
    {
        Http::fake($this->fakeBotIdentity() + [
            'https://api.telegram.org/botdummy-token/getChatMember' => Http::response([
                'ok' => true,
                'result' => ['status' => 'administrator'],
            ]),
        ]);

        $this->artisan('bot:gate-check')
            ->expectsOutputToContain('@jasakodings')
            ->expectsOutputToContain('administrator')
            ->assertExitCode(0);
    }

    public function test_reports_failure_and_how_to_fix_when_bot_has_no_access(): void
    {
        Http::fake($this->fakeBotIdentity() + [
            'https://api.telegram.org/botdummy-token/getChatMember' => Http::response([
                'ok' => false,
                'description' => 'Bad Request: member list is inaccessible',
            ], 400),
        ]);

        $this->artisan('bot:gate-check')
            ->expectsOutputToContain('member list is inaccessible')
            ->expectsOutputToContain('SEMUA user akan tertahan')
            ->assertExitCode(1);
    }

    public function test_disabled_gate_exits_cleanly_without_calling_telegram(): void
    {
        config(['services.telegram-bot-api.required_channel.enabled' => false]);
        Http::fake();

        $this->artisan('bot:gate-check')
            ->expectsOutputToContain('dimatikan')
            ->assertExitCode(0);

        Http::assertNothingSent();
    }

    /**
     * Pesan untuk user saat setelan salah: jujur, tanpa istilah teknis,
     * dan tidak menyuruh user melakukan hal yang tidak akan menolong.
     */
    public function test_misconfigured_message_never_blames_the_user(): void
    {
        config(['services.telegram-bot-api.admin_contact_url' => 'https://wa.me/6285792464508']);

        $message = app(BotMessageFormatter::class)->formatTelegramMembershipMisconfigured();

        $this->assertStringContainsString('Layanan Sedang Diperbaiki', $message['text']);
        $this->assertStringContainsString('bukan karena kamu belum bergabung', $message['text']);
        $this->assertStringNotContainsString('Akses Terbatas', $message['text']);
        $this->assertStringNotContainsString('member list', $message['text']);

        // Tombol hubungi admin tersedia supaya user punya jalan keluar nyata.
        $this->assertSame('https://wa.me/6285792464508', $message['buttons'][0][0]['url']);
    }

    public function test_misconfigured_message_has_no_button_when_admin_url_is_not_set(): void
    {
        config(['services.telegram-bot-api.admin_contact_url' => '']);

        $message = app(BotMessageFormatter::class)->formatTelegramMembershipMisconfigured();

        $this->assertSame([], $message['buttons'], 'Tombol kosong lebih baik daripada tombol rusak.');
    }
}

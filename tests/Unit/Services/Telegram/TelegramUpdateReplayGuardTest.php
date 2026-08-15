<?php

namespace Tests\Unit\Services\Telegram;

use App\Services\Telegram\TelegramUpdateReplayGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TelegramUpdateReplayGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_is_claimed_once_per_bot_scope(): void
    {
        $guard = app(TelegramUpdateReplayGuard::class);

        $this->assertTrue($guard->claim('primary', 12345));
        $this->assertFalse($guard->claim('primary', 12345));
        $this->assertTrue($guard->claim('secondary', 12345));
    }

    public function test_invalid_update_id_is_not_persisted_as_a_replay_receipt(): void
    {
        $guard = app(TelegramUpdateReplayGuard::class);

        $this->assertTrue($guard->claim('primary', null));
        $this->assertTrue($guard->claim('primary', 'not-a-number'));
    }
}

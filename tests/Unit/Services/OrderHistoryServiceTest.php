<?php

namespace Tests\Unit\Services\Order;

use App\Models\Pembelian;
use App\Models\User;
use App\Services\Order\OrderHistoryCursorCodec;
use App\Services\Order\OrderHistoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class OrderHistoryServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_only_the_users_orders_in_newest_deterministic_order(): void
    {
        $user = User::factory()->create(['username' => 'owner', 'no_wa' => '6281234567890']);
        $other = User::factory()->create(['username' => 'other']);

        Pembelian::factory()->create([
            'username' => $user->username,
            'order_id' => 'OWNER-OLD',
            'created_at' => now()->subDay(),
        ]);
        $new = Pembelian::factory()->create([
            'username' => $user->username,
            'order_id' => 'OWNER-NEW',
            'created_at' => now(),
        ]);
        Pembelian::factory()->create([
            'username' => $other->username,
            'order_id' => 'OTHER-ORDER',
            'created_at' => now()->addMinute(),
        ]);

        $result = app(OrderHistoryService::class)->listForUser($user);

        $this->assertFalse($result['invalid_cursor']);
        $this->assertSame(['OWNER-NEW', 'OWNER-OLD'], array_column($result['items'], 'order_key'));
        $this->assertSame('OW••••NEW', $result['items'][0]['order_id']);
        $this->assertSame((string) $new->getKey(), $result['items'][0]['reference']);
        $this->assertSame('processing', $result['items'][0]['status']);
        $this->assertNotContains('OTHER-ORDER', array_column($result['items'], 'order_key'));
    }

    public function test_it_traverses_older_and_newer_windows_without_duplicates_for_identical_timestamps(): void
    {
        $user = User::factory()->create(['username' => 'owner']);
        $createdAt = Carbon::parse('2026-08-16 10:00:00');
        collect(range(1, 7))->each(fn (int $number) => Pembelian::factory()->create([
            'username' => $user->username,
            'order_id' => 'OWNER-' . $number,
            'created_at' => $createdAt,
        ]));

        $service = app(OrderHistoryService::class);
        $first = $service->listForUser($user, null, 'telegram_gateway');
        $second = $service->listForUser($user, $first['next_cursor'], 'telegram_gateway');
        $back = $service->listForUser($user, $second['previous_cursor'], 'telegram_gateway');

        $this->assertCount(5, $first['items']);
        $this->assertCount(2, $second['items']);
        $this->assertSame(
            [],
            array_values(array_intersect(
                array_column($first['items'], 'reference'),
                array_column($second['items'], 'reference'),
            )),
        );
        $this->assertSame(
            array_column($first['items'], 'reference'),
            array_column($back['items'], 'reference'),
        );
        $this->assertNull($first['previous_cursor']);
        $this->assertNotNull($first['next_cursor']);
        $this->assertNotNull($second['previous_cursor']);
        $this->assertNull($second['next_cursor']);
    }

    public function test_it_preserves_a_window_when_new_orders_arrive(): void
    {
        $user = User::factory()->create(['username' => 'owner']);
        collect(range(1, 6))->each(fn (int $number) => Pembelian::factory()->create([
            'username' => $user->username,
            'order_id' => 'OWNER-' . $number,
            'created_at' => now()->subMinutes($number),
        ]));

        $service = app(OrderHistoryService::class);
        $original = $service->listForUser($user, null, 'telegram_gateway');
        Pembelian::factory()->create([
            'username' => $user->username,
            'order_id' => 'INSERTED-LATER',
            'created_at' => now(),
        ]);
        $restored = $service->listForUser(
            $user,
            $original['current_cursor'],
            'telegram_gateway',
        );

        $this->assertSame(
            array_column($original['items'], 'reference'),
            array_column($restored['items'], 'reference'),
        );
        $this->assertNotContains('INSERTED-LATER', array_column($restored['items'], 'order_key'));
    }

    public function test_whatsapp_uses_fifteen_item_windows(): void
    {
        $user = User::factory()->create(['username' => 'owner']);
        collect(range(1, 16))->each(fn (int $number) => Pembelian::factory()->create([
            'username' => $user->username,
            'order_id' => 'OWNER-' . $number,
            'created_at' => now()->subMinutes($number),
        ]));

        $first = app(OrderHistoryService::class)->listForUser(
            $user,
            null,
            'whatsapp_gateway',
        );

        $this->assertCount(15, $first['items']);
        $this->assertNotNull($first['next_cursor']);
    }

    public function test_it_rejects_tampered_cross_user_and_cross_source_cursors(): void
    {
        $owner = User::factory()->create(['username' => 'owner']);
        $other = User::factory()->create(['username' => 'other']);
        collect(range(1, 6))->each(fn (int $number) => Pembelian::factory()->create([
            'username' => $owner->username,
            'created_at' => now()->subMinutes($number),
        ]));

        $service = app(OrderHistoryService::class);
        $first = $service->listForUser($owner, null, 'telegram_gateway');
        $cursor = $first['next_cursor'];
        $tampered = substr($cursor, 0, -1) . ($cursor[-1] === 'A' ? 'B' : 'A');

        $this->assertTrue($service->listForUser($owner, $tampered, 'telegram_gateway')['invalid_cursor']);
        $this->assertTrue($service->listForUser($other, $cursor, 'telegram_gateway')['invalid_cursor']);
        $this->assertTrue($service->listForUser($owner, $cursor, 'whatsapp_gateway')['invalid_cursor']);
    }

    public function test_cursor_codec_rejects_invalid_direction_and_resolves_detail_reference(): void
    {
        $user = User::factory()->create(['username' => 'owner']);
        $order = Pembelian::factory()->create([
            'username' => $user->username,
            'order_id' => 'OWNER-DETAIL',
        ]);
        $codec = app(OrderHistoryCursorCodec::class);

        $this->expectException(\InvalidArgumentException::class);
        $codec->encode([
            'created_at' => now()->toDateTimeString(),
            'id' => (string) $order->getKey(),
        ], 'sideways', $user, 'telegram_gateway');
    }

    public function test_it_resolves_detail_by_opaque_numeric_reference_and_rejects_foreign_reference(): void
    {
        $owner = User::factory()->create(['username' => 'owner']);
        $other = User::factory()->create(['username' => 'other']);
        $owned = Pembelian::factory()->create([
            'username' => $owner->username,
            'order_id' => 'OWNER-DETAIL',
        ]);
        $foreign = Pembelian::factory()->create(['username' => $other->username]);
        $service = app(OrderHistoryService::class);

        $this->assertSame(
            'OWNER-DETAIL',
            $service->findForUserByReference($owner, (string) $owned->getKey())['order_id'],
        );
        $this->assertNull($service->findForUserByReference($owner, (string) $foreign->getKey()));
        $this->assertNull($service->findForUserByReference($owner, 'not-a-reference'));
    }
}

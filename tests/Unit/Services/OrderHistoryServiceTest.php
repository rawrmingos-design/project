<?php

namespace Tests\Unit\Services\Order;

use App\Models\Pembelian;
use App\Models\User;
use App\Services\Order\OrderHistoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $this->assertSame(2, $result['total']);
        $this->assertSame(['OWNER-NEW', 'OWNER-OLD'], array_column($result['items'], 'order_key'));
        $this->assertSame('OW••••NEW', $result['items'][0]['order_id']);
        $this->assertSame((string) $new->getKey(), $result['items'][0]['reference']);
        $this->assertSame('processing', $result['items'][0]['status']);
        $this->assertNotContains('OTHER-ORDER', array_column($result['items'], 'order_key'));
    }

    public function test_it_limits_pages_and_resolves_detail_by_opaque_numeric_reference(): void
    {
        $user = User::factory()->create(['username' => 'owner']);
        $orders = collect(range(1, 7))->map(fn (int $number) => Pembelian::factory()->create([
            'username' => $user->username,
            'order_id' => 'OWNER-' . $number,
            'created_at' => now()->subMinutes($number),
        ]));

        $service = app(OrderHistoryService::class);
        $page = $service->listForUser($user, 2, 99);
        $detail = $service->findForUserByReference($user, (string) $orders->last()->getKey());

        $this->assertSame(2, $page['page']);
        $this->assertSame(5, $page['per_page']);
        $this->assertSame(2, count($page['items']));
        $this->assertSame('OWNER-7', $detail['order_id']);
        $this->assertNull($service->findForUserByReference($user, 'not-a-reference'));
    }

    public function test_it_rejects_a_reference_owned_by_another_user(): void
    {
        $owner = User::factory()->create(['username' => 'owner']);
        $other = User::factory()->create(['username' => 'other']);
        $order = Pembelian::factory()->create(['username' => $other->username]);

        $this->assertNull(app(OrderHistoryService::class)->findForUserByReference($owner, (string) $order->getKey()));
    }
}
